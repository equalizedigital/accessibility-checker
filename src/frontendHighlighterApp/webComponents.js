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
