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
		parentElementor.saver.on( 'after:save', () => {
			highlighter.rescanPage();
		} );
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
