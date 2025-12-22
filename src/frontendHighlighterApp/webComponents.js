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

		// Create template with actual SVG icons from the original design
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
					background-image: url("data:image/svg+xml,%3Csvg width='46' height='46' viewBox='0 0 46 46' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='23' cy='23' r='23' fill='white'/%3E%3Ccircle cx='23' cy='23' r='20' fill='%23B30F0F'/%3E%3Ccircle cx='23' cy='23' r='18' stroke='black' stroke-opacity='0.4' stroke-width='4'/%3E%3Ccircle cx='23' cy='23' r='13.435' transform='rotate(45 23 23)' fill='white'/%3E%3Crect x='27.0515' y='16.7132' width='3.16118' height='14.818' rx='1.58059' transform='rotate(45 27.0515 16.7132)' fill='%23B30F0F'/%3E%3Crect x='29.3566' y='27.1213' width='3.16118' height='14.818' rx='1.58059' transform='rotate(135 29.3566 27.1213)' fill='%23B30F0F'/%3E%3C/svg%3E");
				}

				button.warning {
					background-image: url("data:image/svg+xml,%3Csvg width='46' height='46' viewBox='0 0 46 46' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='23' cy='23' r='23' fill='white'/%3E%3Ccircle cx='23' cy='23' r='20' fill='%23F3CD1E'/%3E%3Ccircle cx='23' cy='23' r='18' stroke='black' stroke-opacity='0.4' stroke-width='4'/%3E%3Cpath d='M21.4093 10.7551C22.1163 9.53061 23.8837 9.53061 24.5907 10.7551L34.3997 27.7449C35.1067 28.9694 34.223 30.5 32.8091 30.5H13.1909C11.777 30.5 10.8933 28.9694 11.6003 27.7449L21.4093 10.7551Z' fill='%23072446'/%3E%3Crect x='21.7755' y='14.8878' width='2.44898' height='9.18367' rx='1.22449' fill='%23F3CD1E'/%3E%3Crect x='21.7755' y='25.1429' width='2.44898' height='2.44898' rx='1.22449' fill='%23F3CD1E'/%3E%3C/svg%3E");
				}

				button.ignored {
					background-image: url("data:image/svg+xml,%3Csvg width='46' height='46' viewBox='0 0 46 46' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='23' cy='23' r='23' fill='white'/%3E%3Ccircle cx='23' cy='23' r='20' fill='%23072446'/%3E%3Ccircle cx='23' cy='23' r='18' stroke='black' stroke-opacity='0.4' stroke-width='4'/%3E%3Cpath opacity='0.991' fill-rule='evenodd' clip-rule='evenodd' d='M21.3204 10.0131C24.4685 9.85403 26.8164 11.1441 28.3641 13.8833C29.3235 15.7433 29.7363 17.7213 29.6026 19.8175C29.7265 22.1744 30.1738 24.4621 30.9443 26.6806C28.5079 26.3678 26.7534 27.3139 25.6808 29.5187C25.2423 30.7036 25.2337 31.8904 25.655 33.0793C22.7481 33.0879 19.8411 33.0793 16.9343 33.0535C15.5357 33.0185 14.1597 32.8293 12.8061 32.4859C12.4965 32.3655 12.1869 32.245 11.8772 32.1246C11.4521 31.8936 11.1597 31.5496 11 31.0926C11.028 30.9008 11.0882 30.7202 11.1806 30.5508C11.4042 30.2583 11.6622 30.0003 11.9546 29.7767C12.5331 29.0763 12.9976 28.3023 13.3479 27.4546C14.018 25.7536 14.4997 23.9991 14.7928 22.1912C14.9679 20.4219 15.1055 18.6502 15.2056 16.8762C15.642 14.3279 16.9235 12.3412 19.0499 10.9161C19.7329 10.4878 20.4725 10.2126 21.2688 10.0905C21.2975 10.0708 21.3147 10.045 21.3204 10.0131Z' fill='white'/%3E%3Cpath opacity='0.964' fill-rule='evenodd' clip-rule='evenodd' d='M29.6284 27.3514C31.7421 27.2251 33.1955 28.1367 33.9888 30.0864C34.5213 32.1412 33.9107 33.7495 32.1569 34.9112C30.277 35.7907 28.6 35.5069 27.1257 34.0597C26.0698 32.7639 25.8462 31.3362 26.4549 29.7767C27.111 28.4386 28.1688 27.6301 29.6284 27.3514ZM28.8544 29.6735C29.0243 29.6611 29.1792 29.7041 29.3188 29.8025C29.6026 30.0864 29.8864 30.3702 30.1702 30.654C30.4712 30.353 30.7723 30.0519 31.0733 29.7509C31.7128 29.6166 31.945 29.866 31.7699 30.4992C31.4767 30.7838 31.1929 31.0761 30.9185 31.3764C31.1929 31.6767 31.4767 31.9691 31.7699 32.2536C31.9585 32.7021 31.8209 32.9773 31.3571 33.0793C31.2556 33.0771 31.1609 33.0513 31.0733 33.0019C30.7533 32.7334 30.4523 32.4496 30.1702 32.1504C29.8354 32.4508 29.5 32.7518 29.164 33.0535C28.7639 33.1266 28.5231 32.9632 28.4416 32.5633C28.4621 32.4757 28.4879 32.3897 28.519 32.3052C28.8293 32.0035 29.1303 31.6939 29.422 31.3764C29.1332 31.0617 28.8322 30.7521 28.519 30.4476C28.3862 30.0797 28.498 29.8217 28.8544 29.6735Z' fill='white'/%3E%3Cpath opacity='0.931' fill-rule='evenodd' clip-rule='evenodd' d='M19.6692 33.9565C21.5616 33.9393 23.4536 33.9565 25.3454 34.0081C24.6552 35.5603 23.4684 36.2053 21.7849 35.9432C20.719 35.6598 20.0137 34.9975 19.6692 33.9565Z' fill='white'/%3E%3C/svg%3E");
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
					background: transparent url("data:image/png;base64,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") center center no-repeat;
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
