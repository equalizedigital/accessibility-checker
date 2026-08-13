/**
 * <edac-highlight-issue-view>
 *
 * Renders the description/WCAG/severity/type badges, the Pro "why it
 * matters / how to fix" accordion, the affected-code toggle, and the
 * "Fix Issue" entry point for a single issue. Ported from
 * descriptionOpen()/codeToggle()/showFixSettings() in index.js.
 *
 * explanationExpanded/codeExpanded are kept as *instance* state (not reset
 * when `issue` is reassigned) to preserve the original behavior where
 * expand/collapse state persisted across issue navigation, since the
 * controller reuses a single instance of this element rather than
 * recreating it per issue.
 *
 * Fix-settings integration is intentionally decoupled from the fixes
 * modal: this component does not know edac-fixes-modal exists. On "Fix
 * Issue" click it detaches its own .edac-fix-settings node and dispatches
 * edac-open-fix-settings with { container, openingElement } in detail —
 * the controller (which does know about the modal) re-parents it.
 */

const SEVERITY_MAP = {
	1: { labelKey: 'Critical', slug: 'critical' },
	2: { labelKey: 'High', slug: 'high' },
	3: { labelKey: 'Medium', slug: 'medium' },
	4: { labelKey: 'Low', slug: 'low' },
};

const TYPE_LABEL_KEYS = {
	error: 'Problem',
	warning: 'Needs Review',
	ignored: 'Dismissed',
};

const TYPE_ICONS = {
	error: 'data:image/svg+xml,' + encodeURIComponent( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="16" height="16" fill="none"><path d="M10 7.5V10.625M17.5 10C17.5 10.9849 17.306 11.9602 16.9291 12.8701C16.5522 13.7801 15.9997 14.6069 15.3033 15.3033C14.6069 15.9997 13.7801 16.5522 12.8701 16.9291C11.9602 17.306 10.9849 17.5 10 17.5C9.01509 17.5 8.03982 17.306 7.12987 16.9291C6.21993 16.5522 5.39314 15.9997 4.6967 15.3033C4.00026 14.6069 3.44781 13.7801 3.0709 12.8701C2.69399 11.9602 2.5 10.9849 2.5 10C2.5 8.01088 3.29018 6.10322 4.6967 4.6967C6.10322 3.29018 8.01088 2.5 10 2.5C11.9891 2.5 13.8968 3.29018 15.3033 4.6967C16.7098 6.10322 17.5 8.01088 17.5 10ZM10 13.125H10.0067V13.1317H10V13.125Z" stroke="#970C0C" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>' ),
	warning: 'data:image/svg+xml,' + encodeURIComponent( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="16" height="16" fill="none"><path d="M9.99997 7.5V10.625M2.24747 13.4383C1.52581 14.6883 2.42831 16.25 3.87081 16.25H16.1291C17.5708 16.25 18.4733 14.6883 17.7525 13.4383L11.6241 2.815C10.9025 1.565 9.09747 1.565 8.37581 2.815L2.24747 13.4383ZM9.99997 13.125H10.0058V13.1317H9.99997V13.125Z" stroke="#CF8402" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>' ),
	ignored: 'data:image/svg+xml,' + encodeURIComponent( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 37 37" width="16" height="16" fill="none"><path d="M13.875 19.6562L17.3437 23.125L23.125 15.0312M32.375 18.5C32.375 20.3221 32.0161 22.1263 31.3188 23.8097C30.6215 25.4931 29.5995 27.0227 28.3111 28.3111C27.0227 29.5995 25.4931 30.6215 23.8097 31.3188C22.1263 32.0161 20.3221 32.375 18.5 32.375C16.6779 32.375 14.8737 32.0161 13.1903 31.3188C11.5069 30.6215 9.97731 29.5995 8.68889 28.3111C7.40048 27.0227 6.37846 25.4931 5.68117 23.8097C4.98389 22.1263 4.625 20.3221 4.625 18.5C4.625 14.8201 6.08683 11.291 8.68889 8.68889C11.291 6.08683 14.8201 4.625 18.5 4.625C22.1799 4.625 25.709 6.08683 28.3111 8.68889C30.9132 11.291 32.375 14.8201 32.375 18.5Z" stroke="#737373" stroke-width="2.775" stroke-linecap="round" stroke-linejoin="round"/></svg>' ),
};

const ARROW_ICON = 'data:image/svg+xml,' + encodeURIComponent( '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z" fill="#2271b1"/></svg>' );

class HighlightIssueView extends HTMLElement {
	constructor() {
		super();
		this.attachShadow( { mode: 'open' } );
		this._issue = null;
		this._fix = null;
		this._status = null;
		this._isPro = false;
		this._userCanFix = false;
		this._i18n = {};
		this._explanationExpanded = false;
		this._codeExpanded = false;
	}

	connectedCallback() {
		if ( ! this.shadowRoot.firstChild ) {
			this.render();
		}
		this._renderIssue();
	}

	render() {
		const template = document.createElement( 'template' );
		template.innerHTML = `
			<style>
				:host {
					all: initial;
					display: block;
					color: #1e1e1e;
					font-family: sans-serif;
					font-size: 14px;
					line-height: 22px;
				}

				a {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				}

				.edac-highlight-panel-description-title {
					font-size: 16px;
					display: flex;
					align-items: center;
					flex-wrap: wrap;
					gap: 8px;
					font-weight: 700;
					margin-bottom: 5px;
				}

				.edac-highlight-panel-description-title-text {
					font-weight: 700;
				}

				.edac-highlight-panel-description-notice {
					display: block;
					margin-bottom: 10px;
					padding: 8px 12px;
					border-left: 4px solid #f1c500;
					background-color: rgba(241, 197, 0, 0.15);
					color: #1e1e1e;
					font-size: 13px;
					font-weight: 400;
					line-height: 1.5;
				}

				.edac-highlight-panel-description-wcag {
					display: flex;
					align-items: center;
					flex-wrap: wrap;
					gap: 8px;
					margin-bottom: 10px;
					font-size: 12px;
				}

				.edac-highlight-panel-description-wcag-label {
					font-weight: 700;
					flex-shrink: 0;
				}

				.edac-highlight-panel-description-summary {
					display: block;
				}

				.edac-highlight-panel-description-reference {
					all: unset;
					display: inline;
					color: var(--wp-admin-theme-color, #3273aa);
					text-decoration: underline;
					cursor: pointer;
				}

				.edac-highlight-panel-description-reference:hover,
				.edac-highlight-panel-description-reference:focus {
					text-decoration: none;
				}

				.edac-highlight-panel-description-reference:focus {
					outline: 2px solid var(--wp-admin-theme-color, #3273aa);
					outline-offset: 2px;
				}

				.edac-highlight-panel-description-explanation-toggle,
				.edac-highlight-panel-description-code-button {
					all: unset;
					display: flex;
					align-items: center;
					gap: 2px;
					margin-top: 8px;
					font-size: 13px;
					color: var(--wp-admin-theme-color, #3273aa);
					cursor: pointer;
					text-decoration: underline;
				}

				.edac-highlight-panel-description-explanation-toggle:hover,
				.edac-highlight-panel-description-explanation-toggle:focus,
				.edac-highlight-panel-description-code-button:hover,
				.edac-highlight-panel-description-code-button:focus {
					text-decoration: none;
				}

				.edac-highlight-panel-description-explanation-toggle:focus,
				.edac-highlight-panel-description-code-button:focus {
					outline: 2px solid var(--wp-admin-theme-color, #3273aa);
					outline-offset: 2px;
				}

				.edac-highlight-panel-description-explanation-toggle-arrow,
				.edac-highlight-panel-description-code-button-arrow {
					transform: rotate(180deg);
					transition: transform 0.2s ease;
				}

				.edac-highlight-panel-description-explanation-toggle[aria-expanded="true"] .edac-highlight-panel-description-explanation-toggle-arrow,
				.edac-highlight-panel-description-code-button[aria-expanded="true"] .edac-highlight-panel-description-code-button-arrow {
					transform: rotate(0deg);
				}

				.edac-highlight-panel-description-explanation[hidden] {
					display: none;
				}

				.edac-highlight-panel-description-how-to-fix {
					display: block;
					margin-top: 12px;
				}

				.edac-highlight-panel-description-how-to-fix-title {
					display: block;
					font-size: 15px;
					font-weight: 700;
					margin-bottom: 5px;
				}

				.edac-highlight-panel-description-code {
					color: #000;
					font-family: monospace;
					font-size: 12px;
					background-color: #f6f7f7;
					border: 1px solid #ddd;
					border-radius: 3px;
					padding: 10px 15px;
					display: none;
					margin-top: 10px;
					white-space: pre-wrap;
					word-break: break-word;
				}

				.edac-highlight-panel-description--button {
					all: unset;
					color: #fff;
					background-color: var(--wp-admin-theme-color, #3273aa);
					padding: 8px 13px;
					display: inline-block;
					margin-top: 10px;
					margin-right: 10px;
					border-radius: 2px;
					cursor: pointer;
				}

				.edac-highlight-panel-description--button:hover,
				.edac-highlight-panel-description--button:focus-visible {
					background-color: color-mix(in srgb, var(--wp-admin-theme-color, #3273aa) 90%, #000);
					outline: 2px solid var(--wp-admin-theme-color, #3273aa);
					outline-offset: 2px;
				}

				.edac-badge {
					display: inline-flex;
					align-items: center;
					gap: 4px;
					padding: 3px 10px;
					border-radius: 20px;
					font-size: 12px;
					font-weight: 400;
					white-space: nowrap;
					color: #1e1e1e;
				}

				.edac-badge--large {
					font-size: 13px;
				}

				.edac-badge--error {
					background: transparent;
					border: 1px solid #970c0c;
				}

				.edac-badge--warning {
					background: transparent;
					border: 1px solid #cf8402;
				}

				.edac-badge--ignored {
					background: transparent;
					border: 1px solid #737373;
				}

				.edac-badge--severity-critical {
					background: rgba(151, 12, 12, 0.1);
					border: 1px solid #970c0c;
				}

				.edac-badge--severity-high {
					background: rgba(244, 157, 2, 0.1);
					border: 1px solid #f49d02;
				}

				.edac-badge--severity-medium {
					background: rgba(241, 197, 0, 0.1);
					border: 1px solid #f1c500;
				}

				.edac-badge--severity-low {
					background: rgba(1, 87, 152, 0.1);
					border: 1px solid #015798;
				}

				code {
					font-family: monospace;
					font-size: 12px;
					background-color: #f6f7f7;
					border: 1px solid #ddd;
					border-radius: 3px;
					padding: 1px 5px;
					color: #1e1e1e;
				}

				.edac-sr-only {
					position: absolute;
					width: 1px;
					height: 1px;
					padding: 0;
					margin: -1px;
					overflow: hidden;
					clip-path: inset(50%);
					white-space: nowrap;
					border: 0;
				}
			</style>
			<div class="edac-highlight-panel-description-title"></div>
			<div class="edac-highlight-panel-description-content"></div>
			<div class="edac-highlight-panel-description-code"><code></code></div>
			<div class="edac-highlight-panel-description-fix"></div>
		`;

		this.shadowRoot.appendChild( template.content.cloneNode( true ) );
		this._titleEl = this.shadowRoot.querySelector( '.edac-highlight-panel-description-title' );
		this._contentEl = this.shadowRoot.querySelector( '.edac-highlight-panel-description-content' );
		this._codeContainerEl = this.shadowRoot.querySelector( '.edac-highlight-panel-description-code' );
		this._codeEl = this._codeContainerEl.querySelector( 'code' );
		this._fixEl = this.shadowRoot.querySelector( '.edac-highlight-panel-description-fix' );
	}

	set issue( value ) {
		this._issue = value;
		this._renderIssue();
	}

	get issue() {
		return this._issue;
	}

	set fix( value ) {
		this._fix = value;
		this._renderIssue();
	}

	set status( value ) {
		this._status = value;
		this._renderIssue();
	}

	set isPro( value ) {
		this._isPro = !! value;
		this._renderIssue();
	}

	set userCanFix( value ) {
		this._userCanFix = !! value;
		this._renderIssue();
	}

	/**
	 * Translated strings, injected by the controller (this component has no
	 * i18n dependency of its own). Falls back to English keys if unset.
	 */
	set i18n( value ) {
		this._i18n = value || {};
		this._renderIssue();
	}

	_t( key, fallback ) {
		return this._i18n[ key ] || fallback;
	}

	_renderIssue() {
		if ( ! this._contentEl || ! this._issue ) {
			return;
		}

		const issue = this._issue;
		const newWindowHtml = `<span aria-hidden="true">↗︎</span><span class="edac-sr-only">${ this._t( 'opensNewWindow', ', opens a new window' ) }</span>`;

		let content = '';

		if ( issue.wcag ) {
			const wcagNumber = parseFloat( issue.wcag );
			const showWcagNumber = ! isNaN( wcagNumber ) && wcagNumber >= 1;
			const wcagLinkText = issue.wcag_title
				? `${ showWcagNumber ? issue.wcag + ' ' : '' }${ issue.wcag_title } ${ newWindowHtml }`
				: `${ showWcagNumber ? issue.wcag + ' ' : '' }${ newWindowHtml }`;

			let severityBadgeHtml = '';
			if ( issue.severity ) {
				const severity = SEVERITY_MAP[ issue.severity ];
				if ( severity ) {
					const label = this._t( 'severity_' + severity.slug, severity.labelKey );
					severityBadgeHtml = `<strong class="edac-highlight-panel-description-wcag-label" role="heading" aria-level="4">${ this._t( 'severityLabel', 'Severity:' ) }</strong> <span class="edac-badge edac-badge--severity-${ severity.slug }"><span class="edac-badge__label">${ label }</span></span>`;
				}
			}

			content += `<div class="edac-highlight-panel-description-wcag"><strong class="edac-highlight-panel-description-wcag-label" role="heading" aria-level="4">${ this._t( 'wcagLabel', 'WCAG:' ) }</strong> <a class="edac-highlight-panel-description-reference" href="${ issue.link }" target="_blank" rel="noopener noreferrer">${ wcagLinkText }</a>${ severityBadgeHtml ? ` ${ severityBadgeHtml }` : '' }</div>`;
		}

		const typeIconUri = TYPE_ICONS[ issue.rule_type ];
		const typeLabel = this._t( 'type_' + issue.rule_type, TYPE_LABEL_KEYS[ issue.rule_type ] ?? issue.rule_type );
		const typeBadgeHtml = `<span class="edac-badge edac-badge--${ issue.rule_type } edac-badge--large">
			${ typeIconUri ? `<img src="${ typeIconUri }" width="16" height="16" style="display:block;width:16px;height:16px;flex-shrink:0" alt="" />` : '' }
			<span class="edac-badge__label">${ typeLabel }</span>
		</span>`;

		if ( issue.summary ) {
			content += `<p class="edac-highlight-panel-description-summary">${ issue.summary }</p>`;
		}

		const hasExplanation = issue.why_it_matters || issue.how_to_fix;

		if ( this._isPro && hasExplanation ) {
			content += `<button class="edac-highlight-panel-description-explanation-toggle" aria-expanded="${ this._explanationExpanded }" aria-controls="edac-highlight-panel-description-explanation">${ this._t( 'showExplanation', 'Show explanation' ) } <img src="${ ARROW_ICON }" width="16" height="16" class="edac-highlight-panel-description-explanation-toggle-arrow" style="display:inline-block;width:16px;height:16px;vertical-align:middle" alt="" /></button>`;
			content += `<div id="edac-highlight-panel-description-explanation" class="edac-highlight-panel-description-explanation"${ this._explanationExpanded ? '' : ' hidden' }>`;

			if ( issue.why_it_matters ) {
				content += `<div class="edac-highlight-panel-description-how-to-fix">
					<div class="edac-highlight-panel-description-how-to-fix-title" role="heading" aria-level="4">${ this._t( 'whyItMatters', 'Why It Matters' ) }</div>
					<div class="edac-highlight-panel-description-how-to-fix-content">${ issue.why_it_matters }</div>
				</div>`;
			}

			if ( issue.how_to_fix ) {
				content += `<div class="edac-highlight-panel-description-how-to-fix">
					<div class="edac-highlight-panel-description-how-to-fix-title" role="heading" aria-level="4">${ this._t( 'howToFix', 'How to Fix' ) }</div>
					<div class="edac-highlight-panel-description-how-to-fix-content">${ issue.how_to_fix }</div>
				</div>`;
			}

			content += `<div><a class="edac-highlight-panel-description-reference" href="${ issue.link }" target="_blank" rel="noopener noreferrer">${ this._t( 'moreDocs', 'More Detailed Documentation' ) } ${ newWindowHtml }</a></div>`;
			content += `</div>`;
		} else {
			content += `<a class="edac-highlight-panel-description-reference" href="${ issue.link }" target="_blank" rel="noopener noreferrer">${ this._t( 'howToFix', 'How to Fix' ) } ${ newWindowHtml }</a>`;
		}

		content += `<div><button class="edac-highlight-panel-description-code-button" aria-expanded="${ this._codeExpanded }" aria-controls="edac-highlight-panel-description-code">${ this._t( 'showAffectedCode', 'Show Affected Code' ) } <img src="${ ARROW_ICON }" width="16" height="16" class="edac-highlight-panel-description-code-button-arrow" style="display:inline-block;width:16px;height:16px;vertical-align:middle" alt="" /></button></div>`;

		const noticeHtml = this._status
			? `<div class="edac-highlight-panel-description-notice">${ this._status }</div>`
			: '';
		this._titleEl.innerHTML = `${ noticeHtml }<span class="edac-highlight-panel-description-title-text" role="heading" aria-level="3">${ issue.rule_title }</span>${ typeBadgeHtml }`;

		this._contentEl.innerHTML = content;

		const htmlSnippet = issue.object || '';
		const parser = new DOMParser();
		const parsedHtml = parser.parseFromString( htmlSnippet, 'text/html' );
		const firstParsedElement = parsedHtml.body.firstElementChild;
		this._codeEl.innerText = firstParsedElement ? firstParsedElement.outerHTML : htmlSnippet;

		this._fixEl.innerHTML = '';
		if ( this._fix && this._fix.fields && this._userCanFix ) {
			const groupKey = Object.keys( this._fix ).find( ( key ) => key !== 'fields' );
			const groupName = groupKey ? this._fix[ groupKey ].group_name : '';
			this._fixEl.innerHTML = `
				<div style="display:none;" class="always-hide">
					<div class="edac-fix-settings">
						<div class="edac-fix-settings--fields">
							${ this._fix.fields }
							<div class="edac-fix-settings--action-row">
								<button role="button" class="button button-primary edac-fix-settings--button--save">
									${ this._t( 'save', 'Save' ) }
								</button>
								<span class="edac-fix-settings--notice-slot" aria-live="polite" role="alert"></span>
							</div>
						</div>
					</div>
				</div>
				<button role="button"
					class="edac-fix-settings--button--open edac-highlight-panel-description--button"
					aria-haspopup="true"
					aria-controls="edac-highlight-panel-description-fix"
					aria-label="${ this._t( 'fixIssue', 'Fix issue' ) }: ${ groupName }">
					${ this._t( 'fixIssue', 'Fix Issue' ) }
				</button>
			`;

			this._fixEl.querySelector( '.edac-fix-settings--button--open' )
				.addEventListener( 'click', this._onOpenFixSettings );

			this._fixEl.querySelector( '.edac-fix-settings--button--save' )
				.addEventListener( 'click', this._onSaveFixSettings );
		}

		this.shadowRoot.querySelector( '.edac-highlight-panel-description-explanation-toggle' )
			?.addEventListener( 'click', this._onExplanationToggle );

		this._codeButtonEl = this.shadowRoot.querySelector( '.edac-highlight-panel-description-code-button' );
		this._codeButtonEl.addEventListener( 'click', this._onCodeToggle );
		this._codeContainerEl.style.display = this._codeExpanded ? 'block' : 'none';
	}

	_onExplanationToggle = ( e ) => {
		this._explanationExpanded = ! this._explanationExpanded;
		e.target.setAttribute( 'aria-expanded', String( this._explanationExpanded ) );
		const explanationPanel = this.shadowRoot.querySelector( '#edac-highlight-panel-description-explanation' );
		if ( explanationPanel ) {
			explanationPanel.hidden = ! this._explanationExpanded;
		}
	};

	_onCodeToggle = () => {
		this._codeExpanded = ! this._codeExpanded;
		this._codeContainerEl.style.display = this._codeExpanded ? 'block' : 'none';
		this._codeButtonEl.setAttribute( 'aria-expanded', String( this._codeExpanded ) );
	};

	_onOpenFixSettings = ( e ) => {
		const container = this._fixEl.querySelector( '.edac-fix-settings' );
		if ( ! container ) {
			return;
		}
		const placeholder = document.createElement( 'span' );
		placeholder.classList.add( 'edac-fix-settings--origin-placeholder' );
		container.parentNode.insertBefore( placeholder, container );
		container.remove();

		this.dispatchEvent( new CustomEvent( 'edac-open-fix-settings', {
			bubbles: true,
			composed: true,
			detail: { container, openingElement: e.target },
		} ) );
	};

	_onSaveFixSettings = ( e ) => {
		this.dispatchEvent( new CustomEvent( 'edac-save-fix-settings', {
			bubbles: true,
			composed: true,
			detail: { fieldsContainer: e.target.closest( '.edac-fix-settings' ) },
		} ) );
	};
}

if ( ! customElements.get( 'edac-highlight-issue-view' ) ) {
	customElements.define( 'edac-highlight-issue-view', HighlightIssueView );
}

export { HighlightIssueView };
