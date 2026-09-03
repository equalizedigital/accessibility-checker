/**
 * <edac-landmark-label>
 *
 * A single floating, non-interactive badge naming the landmark region the
 * user just jumped to (e.g. "Landmark: Navigation"). Ported from the label
 * built inline in highlightLandmark() (index.js:1684-1711).
 *
 * Unlike edac-highlight-tooltip-button, this is a one-shot placement, not
 * continuously tracked with floating-ui autoUpdate — the original only
 * ever positioned it once, at the moment the landmark was jumped to, so
 * `left`/`top` are plain properties the controller sets once rather than
 * something this component recomputes on scroll/resize.
 *
 * Only one of these ever exists on the page at a time (the controller
 * removes/replaces it before highlighting a new landmark) — no click
 * handling, no events, nothing to observe.
 */

class HighlightLandmarkLabel extends HTMLElement {
	constructor() {
		super();
		this.attachShadow( { mode: 'open' } );
		this._text = '';
		this._left = 0;
		this._top = 0;
	}

	connectedCallback() {
		if ( ! this.shadowRoot.firstChild ) {
			this.render();
		}
		this._syncPosition();
		this._syncText();
	}

	render() {
		const template = document.createElement( 'template' );
		template.innerHTML = `
			<style>
				:host {
					all: initial;
					position: absolute;
					z-index: 99998;
					pointer-events: none;
				}

				.edac-landmark-label {
					display: block;
					background: #072446;
					color: #fff;
					padding: 4px 8px;
					font-size: 12px;
					font-weight: bold;
					border-radius: 3px;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
					line-height: 1;
					box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
				}
			</style>
			<div class="edac-landmark-label" aria-hidden="true"></div>
		`;

		this.shadowRoot.appendChild( template.content.cloneNode( true ) );
		this.labelEl = this.shadowRoot.querySelector( '.edac-landmark-label' );
	}

	set text( value ) {
		this._text = value || '';
		this._syncText();
	}

	get text() {
		return this._text;
	}

	set left( value ) {
		this._left = value || 0;
		this._syncPosition();
	}

	get left() {
		return this._left;
	}

	set top( value ) {
		this._top = value || 0;
		this._syncPosition();
	}

	get top() {
		return this._top;
	}

	_syncText() {
		if ( this.labelEl ) {
			this.labelEl.textContent = this._text;
		}
	}

	_syncPosition() {
		this.style.left = this._left + 'px';
		this.style.top = this._top + 'px';
	}
}

if ( ! customElements.get( 'edac-landmark-label' ) ) {
	customElements.define( 'edac-landmark-label', HighlightLandmarkLabel );
}

export { HighlightLandmarkLabel };
