/* eslint-disable padded-blocks, no-multiple-empty-lines */

import { __ } from '@wordpress/i18n';

/**
 * Custom Web Component for the Accessibility Checker Highlight Button
 * This encapsulates the tooltip button styles to prevent theme CSS from leaking in
 */
class EdacHighlightButton extends HTMLElement {
	constructor() {
		super();
		this.attachShadow( { mode: 'open' } );
	}

	connectedCallback() {
		const ruleType = this.getAttribute( 'rule-type' ) || 'error';
		const ariaLabel = this.getAttribute( 'aria-label' ) || '';

		// Create template
		const template = document.createElement( 'template' );
		template.innerHTML = `
			<style>
				:host {
					all: initial;
					display: block;
					position: absolute;
					z-index: 2147483646;
				}
				
				button {
					all: unset;
					width: 40px;
					height: 40px;
					display: block;
					font-size: 0;
					border-radius: 50%;
					margin: 5px;
					cursor: pointer;
					background-size: 40px 40px;
					background-position: center center;
					background-repeat: no-repeat;
				}
				
				button.error {
					background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Ccircle cx='20' cy='20' r='18' fill='%23dc3232' stroke='%23fff' stroke-width='2'/%3E%3Ctext x='20' y='28' text-anchor='middle' fill='%23fff' font-size='24' font-weight='bold'%3E!%3C/text%3E%3C/svg%3E");
				}
				
				button.warning {
					background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Ccircle cx='20' cy='20' r='18' fill='%23ffb900' stroke='%23fff' stroke-width='2'/%3E%3Ctext x='20' y='28' text-anchor='middle' fill='%23000' font-size='24' font-weight='bold'%3E!%3C/text%3E%3C/svg%3E");
				}
				
				button.ignored {
					background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Ccircle cx='20' cy='20' r='18' fill='%230073aa' stroke='%23fff' stroke-width='2'/%3E%3Ctext x='20' y='28' text-anchor='middle' fill='%23fff' font-size='20' font-weight='bold'%3Ei%3C/text%3E%3C/svg%3E");
				}
				
				button.selected,
				button:hover,
				button:focus {
					outline: solid 5px rgba(0, 208, 255, .75);
				}
			</style>
			<button class="${ ruleType }" aria-label="${ ariaLabel }" aria-expanded="false" aria-haspopup="dialog">
			</button>
		`;

		this.shadowRoot.appendChild( template.content.cloneNode( true ) );
		this.button = this.shadowRoot.querySelector( 'button' );
	}

	setSelected( selected ) {
		if ( selected ) {
			this.button.classList.add( 'selected' );
		} else {
			this.button.classList.remove( 'selected' );
		}
	}
}

/**
 * Custom Web Component for the Accessibility Checker Highlight Panel
 * This encapsulates the panel styles to prevent theme CSS from leaking in
 */
class EdacHighlightPanel extends HTMLElement {
	constructor() {
		super();
		this.attachShadow( { mode: 'open' } );
	}

	connectedCallback() {
		const widgetPosition = this.getAttribute( 'widget-position' ) || 'right';
		const userCanEdit = this.getAttribute( 'user-can-edit' ) === 'true';

		const clearButtonMarkup = userCanEdit
			? `<button id="edac-highlight-clear-issues" class="edac-highlight-clear-issues">${ __( 'Clear Issues', 'accessibility-checker' ) }</button>`
			: '';

		const rescanButton = userCanEdit
			? `<button id="edac-highlight-rescan" class="edac-highlight-rescan">${ __( 'Rescan This Page', 'accessibility-checker' ) }</button>`
			: '';

		// Create template with styles
		const template = document.createElement( 'template' );
		template.innerHTML = `
			<style>
				:host {
					all: initial;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				}
				
				* {
					box-sizing: border-box;
				}
				
				.edac-highlight-panel {
					width: auto;
					max-width: 400px;
					position: fixed;
					z-index: 2147483647;
					bottom: 15px;
					${ widgetPosition === 'right' ? 'right: 15px;' : 'left: 15px;' }
				}
				
				.edac-highlight-panel-visible {
					width: 400px;
				}
				
				.edac-highlight-panel-toggle {
					width: 50px;
					height: 50px;
					display: block;
					background: transparent url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='45' fill='%23072446'/%3E%3Ctext x='50' y='70' text-anchor='middle' fill='%23fff' font-size='60' font-weight='bold'%3EA%3C/text%3E%3C/svg%3E") center center no-repeat;
					background-size: contain;
					box-shadow: 0 0 5px rgba(0, 0, 0, .5);
					border-radius: 50%;
					border: none;
					cursor: pointer;
					position: relative;
				}
				
				.edac-highlight-panel-toggle:hover,
				.edac-highlight-panel-toggle:focus {
					outline: solid 5px rgba(0, 208, 255, .75);
				}
				
				.edac-highlight-panel-description,
				.edac-highlight-panel-controls {
					background-color: #072446;
					border: solid 1px #ddd;
					color: #fff;
					font-size: 14px;
					line-height: 22px;
					padding: 15px;
					box-shadow: 0px 0px 5px rgba(0, 0, 0, .25);
					-webkit-font-smoothing: antialiased;
					-moz-osx-font-smoothing: grayscale;
				}
				
				.edac-highlight-panel-description {
					max-height: calc(100vh - 230px);
					margin-bottom: 15px;
					display: none;
					overflow-y: auto;
				}
				
				.edac-highlight-panel-controls {
					display: none;
					position: relative;
					background-color: #0073aa;
				}
				
				.edac-highlight-panel-controls-close,
				.edac-highlight-panel-description-close {
					width: 25px;
					height: 25px;
					color: #072446;
					background-color: #ffb900;
					font-size: 18px;
					line-height: 25px;
					position: absolute;
					top: 1px;
					right: 1px;
					text-align: center;
					border: none;
					cursor: pointer;
				}
				
				.edac-highlight-panel-controls-close:hover,
				.edac-highlight-panel-controls-close:focus,
				.edac-highlight-panel-description-close:hover,
				.edac-highlight-panel-description-close:focus {
					background-color: #fff;
				}
				
				.edac-highlight-panel-controls-title,
				.edac-highlight-panel-description-title {
					font-size: 16px;
					font-weight: bold;
					margin-bottom: 5px;
					display: block;
				}
				
				.edac-highlight-panel-controls-summary {
					display: block;
					margin-bottom: 10px;
				}
				
				.edac-highlight-panel-controls-buttons button {
					text-decoration: none;
					color: #fff;
					background-color: #072446;
					padding: 4px 10px;
					display: inline-block;
					margin-top: 10px;
					margin-right: 10px;
					border: none;
					cursor: pointer;
				}
				
				.edac-highlight-panel-controls-buttons button:hover,
				.edac-highlight-panel-controls-buttons button:focus {
					color: #072446;
					background-color: #fff;
				}
				
				.edac-highlight-panel-controls-buttons button:disabled {
					display: none;
				}
				
				.edac-highlight-disable-styles {
					float: right;
					margin-right: 0;
				}
				
				.edac-highlight-panel-description-type {
					font-size: 12px;
					padding: 5px 7px;
					border-radius: 4px;
					line-height: 12px;
					margin-left: 10px;
					display: inline-block;
					text-transform: capitalize;
					position: relative;
					top: -2px;
				}
				
				.edac-highlight-panel-description-type-error {
					color: #fff;
					background-color: #dc3232;
				}
				
				.edac-highlight-panel-description-type-warning {
					color: #072446;
					background-color: #ffb900;
				}
				
				.edac-highlight-panel-description-type-ignored {
					color: #fff;
					background-color: #0073aa;
				}
				
				.edac-highlight-panel-description-index,
				.edac-highlight-panel-description-status {
					font-size: 16px;
					font-weight: bold;
					margin-bottom: 5px;
					display: block;
				}
				
				.edac-highlight-panel-description-status {
					background-color: #dc3232;
					padding: 10px 15px;
					margin-top: 10px;
				}
				
				.edac-highlight-panel-description-reference,
				.edac-highlight-panel-description-code-button,
				.edac-highlight-panel-description--button {
					color: #072446;
					background-color: #ffb900;
					padding: 4px 10px;
					display: inline-block;
					margin-top: 10px;
					margin-right: 10px;
					text-decoration: none;
					border: none;
					cursor: pointer;
				}
				
				.edac-highlight-panel-description-reference:hover,
				.edac-highlight-panel-description-reference:focus,
				.edac-highlight-panel-description-code-button:hover,
				.edac-highlight-panel-description-code-button:focus,
				.edac-highlight-panel-description--button:hover,
				.edac-highlight-panel-description--button:focus {
					color: #072446;
					background-color: #fff;
				}
				
				.edac-highlight-panel-description-code {
					color: #000;
					background-color: #fff;
					padding: 10px 15px;
					display: none;
					margin-top: 10px;
				}
				
				.edac-highlight-panel-description-how-to-fix-title {
					font-weight: bold;
					margin-top: 10px;
					margin-bottom: 5px;
					display: block;
				}
				
				.always-hide {
					display: none;
				}
				
				a {
					color: #fff;
					text-decoration: underline;
				}
				
				a:hover,
				a:focus {
					text-decoration: none;
				}
				
				p {
					margin: 10px 0;
				}
				
				@media screen and (max-width: 768px) {
					.edac-highlight-panel {
						width: 100%;
						max-width: calc(100% - 30px);
					}
				}
			</style>
			<div class="edac-highlight-panel">
				<button id="edac-highlight-panel-toggle" class="edac-highlight-panel-toggle" aria-haspopup="dialog" aria-label="${ __( 'Accessibility Checker Tools', 'accessibility-checker' ) }"></button>
				<div id="edac-highlight-panel-description" class="edac-highlight-panel-description" role="dialog" aria-labelledby="edac-highlight-panel-description-title" tabindex="0">
					<button class="edac-highlight-panel-description-close edac-highlight-panel-controls-close" aria-label="${ __( 'Close', 'accessibility-checker' ) }">×</button>
					<div id="edac-highlight-panel-description-title" class="edac-highlight-panel-description-title"></div>
					<div class="edac-highlight-panel-description-content"></div>
					<div id="edac-highlight-panel-description-code" class="edac-highlight-panel-description-code"><code></code></div>
				</div>
				<div id="edac-highlight-panel-controls" class="edac-highlight-panel-controls" tabindex="0">
					<button id="edac-highlight-panel-controls-close" class="edac-highlight-panel-controls-close" aria-label="${ __( 'Close', 'accessibility-checker' ) }">×</button>
					<div class="edac-highlight-panel-controls-title">${ __( 'Accessibility Checker', 'accessibility-checker' ) }</div>
					<div class="edac-highlight-panel-controls-summary">${ __( 'Loading...', 'accessibility-checker' ) }</div>
					<div class="edac-highlight-panel-controls-buttons${ ! userCanEdit ? ' single_button' : '' }">
						<div>
							<button id="edac-highlight-previous" disabled="true"><span aria-hidden="true">« </span>${ __( 'Previous', 'accessibility-checker' ) }</button>
							<button id="edac-highlight-next" disabled="true">${ __( 'Next', 'accessibility-checker' ) }<span aria-hidden="true"> »</span></button><br />
						</div>
						<div>
							${ rescanButton }
							${ clearButtonMarkup }
							<button id="edac-highlight-disable-styles" class="edac-highlight-disable-styles" aria-live="polite" aria-label="${ __( 'Disable Page Styles', 'accessibility-checker' ) }">${ __( 'Disable Styles', 'accessibility-checker' ) }</button>
						</div>
					</div>
				</div>
			</div>
		`;

		this.shadowRoot.appendChild( template.content.cloneNode( true ) );
	}

	// Expose methods to interact with shadow DOM elements
	querySelector( selector ) {
		return this.shadowRoot.querySelector( selector );
	}

	querySelectorAll( selector ) {
		return this.shadowRoot.querySelectorAll( selector );
	}
}

// Register the custom elements
if ( ! customElements.get( 'edac-highlight-button' ) ) {
	customElements.define( 'edac-highlight-button', EdacHighlightButton );
}

if ( ! customElements.get( 'edac-highlight-panel' ) ) {
	customElements.define( 'edac-highlight-panel', EdacHighlightPanel );
}

export { EdacHighlightButton, EdacHighlightPanel };
