/**
 * Elementor saves a post through its own Backbone app and never touches `wp.data`
 * (which is what the Gutenberg save-detection relies on), so the front-end highlighter
 * has no way to learn a save happened and keeps showing stale results until the page
 * is manually reloaded.
 *
 * This script runs inside Elementor's live-preview iframe, while the `elementor`
 * global and its `saver` save events live in the parent (editor) window. Reach into
 * the parent to listen for a completed save and trigger a rescan.
 *
 * @param {Object}   highlighter              The AccessibilityCheckerHighlight instance to rescan with.
 * @param {Function} highlighter.rescanPage   Triggers a rescan of the current page.
 * @param {Object}   [options]                Optional overrides, primarily for tests.
 * @param {number}   [options.pollIntervalMs] Milliseconds between readiness checks. Default 500.
 * @param {number}   [options.maxAttempts]    Maximum number of readiness checks before giving up. Default 20.
 */
export function setupElementorSaveListener( highlighter, options = {} ) {
	const { pollIntervalMs = 500, maxAttempts = 20 } = options;

	// Not inside an iframe, so this can't be an Elementor preview.
	if ( window === window.parent ) {
		return;
	}

	const attach = ( parentElementor ) => {
		const onAfterSave = ( saveOptions ) => {
			// Elementor autosaves periodically in the background; it isn't a
			// deliberate save and its content isn't published. Rescanning (and
			// persisting) on it would overwrite the post's saved issues with
			// in-progress draft state — the same problem the Gutenberg
			// save-detection in src/editorApp/checkPage.js already guards
			// against by ignoring wp.data's isAutosavingPost().
			if ( saveOptions?.status === 'autosave' ) {
				return;
			}
			highlighter.rescanPage();
		};

		parentElementor.saver.on( 'after:save', onAfterSave );

		// The preview iframe can be reloaded independently of the parent editor
		// (e.g. switching preview devices). Backbone's `.on()` stores the callback
		// on the parent's long-lived saver object, so without this the closure
		// above — and the highlighter/DOM it references — would be kept alive
		// and would still fire rescans after this iframe is gone.
		window.addEventListener( 'pagehide', () => {
			parentElementor.saver.off( 'after:save', onAfterSave );
		}, { once: true } );
	};

	// Elementor's editor may still be initializing when the preview iframe first
	// loads, so poll briefly for it to become available. A cross-origin parent
	// throws immediately on access, in which case there's nothing we can do.
	let attempts = 0;
	const interval = setInterval( () => {
		attempts++;

		let candidate;
		try {
			candidate = window.parent.elementor;
		} catch ( e ) {
			clearInterval( interval );
			return;
		}

		if ( candidate?.saver?.on ) {
			clearInterval( interval );
			attach( candidate );
		} else if ( attempts >= maxAttempts ) {
			clearInterval( interval );
		}
	}, pollIntervalMs );
}
