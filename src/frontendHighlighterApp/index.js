/* eslint-disable padded-blocks, no-multiple-empty-lines */
/* global edacFrontendHighlighterApp */

import { computePosition, autoUpdate } from '@floating-ui/dom';
import { createFocusTrap } from 'focus-trap';
import { isFocusable } from 'tabbable';
import { __, _n, sprintf } from '@wordpress/i18n';
import { saveFixSettings } from '../common/saveFixSettingsRest';
import { fillFixesModal, fixSettingsModalInit, openFixesModal } from './fixesModal';
import { getLandmarkType as getLandmarkTypeUtil } from './getLandmarkType';

class AccessibilityCheckerHighlight {
	/**
	 * Constructor
	 * @param {Object} settings
	 */
	constructor( settings = {} ) {
		const defaultSettings = {
			showIgnored: false,
		};

		this.settings = { ...defaultSettings, ...settings };
		this._scanAttempted = false;
		this._isRescanning = false;
		this._pendingRescanAnnouncement = false;
		this._issuesCleared = false;

		this.highlightPanel = this.addHighlightPanel();
		this.nextButton = document.querySelector( '#edac-highlight-next' );
		this.previousButton = document.querySelector( '#edac-highlight-previous' );
		this.panelToggle = document.querySelector( '#edac-highlight-panel-toggle' );
		this.closePanel = document.querySelector( '#edac-highlight-panel-controls-close' );
		this.panelControls = document.querySelector( '#edac-highlight-panel-controls' );
		this.contentArea = document.querySelector( '#edac-highlight-panel-controls-content' );
		this.issues = null;
		this.fixes = null;
		this.currentButtonIndex = null;
		this.urlParameter = this.get_url_parameter( 'edac' );
		this.landmarkParameter = this.get_url_parameter( 'edac_landmark' );
		this.currentIssueStatus = null;
		this.explanationExpanded = false;
		this.codeExpanded = false;
		this.isDragged = false;
		this.tooltips = [];
		this.panelControlsFocusTrap = createFocusTrap( '#' + this.panelControls.id, {
			clickOutsideDeactivates: true,
			escapeDeactivates: () => {
				this.panelClose();
			},
			initialFocus: () => {
				return this.closePanel;
			},
		} );

		this.disableStylesButton = document.querySelector( '#edac-highlight-disable-styles' );
		this.rescanButton = document.querySelector( '#edac-highlight-rescan' );
		this.clearIssuesButton = document.querySelector( '#edac-highlight-clear-issues' );
		this.menuButton = document.querySelector( '#edac-highlight-menu-button' );
		this.menu = document.querySelector( '#edac-highlight-menu' );
		this.moveButton = document.querySelector( '#edac-highlight-move' );
		this.dockButton = document.querySelector( '#edac-highlight-dock' );
		this.srAnnouncer = document.querySelector( '#edac-highlight-announcer' );
		this.isDocked = localStorage.getItem( 'edac-panel-docked' ) === '1';
		this.stylesDisabled = false;
		this.originalCss = [];
		this.originalInlineStyles = [];

		this.init();
	}

	/**
	 * This function initializes the component by setting up event listeners
	 * and managing the initial state of the panel based on the URL parameter.
	 */
	init() {
		// Add event listeners for 'next' and 'previous' buttons
		this.nextButton.addEventListener( 'click', () => {
			this.highlightFocusNext();
		} );
		this.previousButton.addEventListener( 'click', () => {
			this.highlightFocusPrevious();
		} );

		// Manage panel open/close operations
		this.panelToggle.addEventListener( 'click', () => {
			this.panelOpen();
			this.focusTrapControls();
		} );
		this.closePanel.addEventListener( 'click', () => {
			this.closeMenu();
			this.panelClose();
			this.panelControlsFocusTrap.deactivate();
			// Only re-enable styles if they were disabled by the tool.
			if ( this.stylesDisabled ) {
				this.enableStyles();
			}
		} );

		// Handle ellipsis menu toggle
		this.menuButton.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			this.toggleMenu();
		} );

		// Close menu on outside click
		document.addEventListener( 'click', ( e ) => {
			if ( this.menu && ! this.menu.hidden && ! this.menu.contains( e.target ) && e.target !== this.menuButton ) {
				this.closeMenu();
			}
		} );

		// Keyboard navigation within menu
		this.menu.addEventListener( 'keydown', ( e ) => {
			const items = [ ...this.menu.querySelectorAll( '[role="menuitem"]' ) ];
			const focused = document.activeElement;
			const index = items.indexOf( focused );
			if ( e.key === 'ArrowDown' ) {
				e.preventDefault();
				items[ ( index + 1 ) % items.length ]?.focus();
			} else if ( e.key === 'ArrowUp' ) {
				e.preventDefault();
				items[ ( index - 1 + items.length ) % items.length ]?.focus();
			} else if ( e.key === 'Escape' ) {
				this.closeMenu();
				this.menuButton.focus();
			}
		} );

		// Handle move left/right / reset position
		this.moveButton.addEventListener( 'click', () => {
			this.togglePosition();
			this.closeMenu();
		} );

		// Handle disable/enable styles
		this.disableStylesButton.addEventListener( 'click', () => {
			if ( this.stylesDisabled ) {
				this.enableStyles();
			} else {
				this.disableStyles();
			}
			this.closeMenu();
		} );

		if ( this.rescanButton ) {
			this.rescanButton.addEventListener( 'click', () => {
				this.closeMenu();
				this.rescanPage();
			} );
		}

		if ( this.clearIssuesButton ) {
			this.clearIssuesButton.addEventListener( 'click', () => {
				this.closeMenu();
				this.clearIssues();
			} );
		}

		if ( this.dockButton ) {
			this.dockButton.addEventListener( 'click', () => {
				this.closeMenu();
				this.toggleDock();
			} );
		}

		// Reactivate the focus trap when the user clicks back into the panel.
		this.panelControls.addEventListener( 'pointerdown', () => {
			if ( ! this.panelControlsFocusTrap.active ) {
				this.panelControlsFocusTrap.activate( { returnFocusOnDeactivate: false } );
			}
		} );

		// Restore docked state if it was previously set.
		if ( this.isDocked ) {
			this.applyDock();
		}

		// Open panel if a URL parameter exists
		if ( this.urlParameter ) {
			this.panelOpen( this.urlParameter );
		} else if ( this.landmarkParameter ) {
			this.highlightLandmark( this.landmarkParameter );
		} else if ( this.isDocked ) {
			// Docked panel restored on page load — fetch issue data so the panel isn't empty.
			this.panelOpen();
		}
	}

	toggleMenu() {
		const isOpen = ! this.menu.hidden;
		if ( isOpen ) {
			this.closeMenu();
		} else {
			this.menu.hidden = false;
			this.menuButton.setAttribute( 'aria-expanded', 'true' );
			this.menu.querySelector( '[role="menuitem"]' )?.focus();
		}
	}

	closeMenu() {
		this.menu.hidden = true;
		this.menuButton.setAttribute( 'aria-expanded', 'false' );
	}

	/**
	 * Announce a message to screen readers using a live region.
	 *
	 * @param {string} message - The message to announce.
	 */
	announce( message ) {
		if ( ! this.srAnnouncer ) {
			return;
		}
		// Clear first so repeated identical messages are re-announced.
		this.srAnnouncer.textContent = '';
		// Use a timeout to ensure the DOM update is picked up by assistive technologies.
		setTimeout( () => {
			this.srAnnouncer.textContent = message;
		}, 50 );
	}

	togglePosition() {
		// Clear any drag-applied inline position so the panel repositions via CSS classes.
		this.panelControls.style.position = '';
		this.panelControls.style.width = '';
		this.panelControls.style.left = '';
		this.panelControls.style.top = '';
		this.panelControls.style.right = '';
		this.panelControls.style.bottom = '';

		// If the panel was dragged, just reset — don't toggle the side.
		if ( this.isDragged ) {
			this.isDragged = false;
			const isRight = this.highlightPanel.classList.contains( 'edac-highlight-panel--right' );
			this.moveButton.querySelector( 'span' ).textContent = isRight
				? __( 'Move to Left', 'accessibility-checker' )
				: __( 'Move to Right', 'accessibility-checker' );
			this.announce( __( 'Panel position reset.', 'accessibility-checker' ) );
			return;
		}

		const isRight = this.highlightPanel.classList.contains( 'edac-highlight-panel--right' );
		this.highlightPanel.classList.toggle( 'edac-highlight-panel--right', ! isRight );
		this.highlightPanel.classList.toggle( 'edac-highlight-panel--left', isRight );
		this.moveButton.querySelector( 'span' ).textContent = isRight
			? __( 'Move to Right', 'accessibility-checker' )
			: __( 'Move to Left', 'accessibility-checker' );
		this.announce( isRight
			? __( 'Panel moved to the left.', 'accessibility-checker' )
			: __( 'Panel moved to the right.', 'accessibility-checker' )
		);

		// If docked, update body margin to match the new side.
		if ( this.isDocked ) {
			const panelWidth = this.panelControls.offsetWidth + 'px';
			document.body.style.marginRight = '';
			document.body.style.marginLeft = '';
			document.body.style[ isRight ? 'marginLeft' : 'marginRight' ] = panelWidth;
		}
	}

	/**
	 * This function tries to find an element on the page that matches a given HTML snippet.
	 * It tries multiple strategies in order: selector (most stable), ancestry (more specific),
	 * and HTML matching (fallback). If a match is found, it adds a tooltip and returns the element.
	 * If no matching element is found, it returns null.
	 *
	 * @param {Object} value - Object containing the HTML snippet and selectors.
	 * @param {number} index - Index of the element being searched.
	 * @return {HTMLElement|null} - Returns the matching HTML element, or null if no match is found.
	 */
	findElement( value, index ) {
		// Try selector first (most stable - IDs/classes don't change with DOM structure)
		if ( value.selector ) {
			try {
				const element = document.querySelector( value.selector );
				if ( element ) {
					const tooltip = this.addTooltip( element, value, index, this.issues.length );
					this.issues[ index ].tooltip = tooltip.tooltip;
					this.tooltips.push( tooltip );
					return element;
				}
			} catch ( e ) {
				// Selector may be invalid, fall back to ancestry
			}
		}

		// Try ancestry selector (more specific than selector but less stable)
		if ( value.ancestry ) {
			try {
				const element = document.querySelector( value.ancestry );
				if ( element ) {
					const tooltip = this.addTooltip( element, value, index, this.issues.length );
					this.issues[ index ].tooltip = tooltip.tooltip;
					this.tooltips.push( tooltip );
					return element;
				}
			} catch ( e ) {
				// Ancestry selector may be invalid, fall back to HTML matching
			}
		}

		// Fall back to HTML matching
		let htmlToFind = value.object;
		const parser = new DOMParser();
		const parsedHtml = parser.parseFromString( htmlToFind, 'text/html' );
		const firstParsedElement = parsedHtml.body.firstElementChild;

		if ( firstParsedElement ) {
			htmlToFind = firstParsedElement.outerHTML;
		}

		// Compare the outer HTML of the parsed element with all elements on the page
		const allElements = document.body.querySelectorAll( '*' );

		for ( const element of allElements ) {
			if ( element.outerHTML.replace( /\W/g, '' ) === htmlToFind.replace( /\W/g, '' ) ) {
				const tooltip = this.addTooltip( element, value, index, this.issues.length );

				this.issues[ index ].tooltip = tooltip.tooltip;

				this.tooltips.push( tooltip );

				return element;
			}
		}

		// If no matching element is found, return null
		return null;
	}

	/**
	 * This function makes an AJAX call to the server to retrieve the list of issues.
	 *
	 * Note: This function assumes that `edacFrontendHighlighterApp` is a global variable containing necessary data.
	 */
	highlightAjax() {
		const self = this;
		return new Promise( function( resolve, reject ) {
			const xhr = new XMLHttpRequest();
			const url = edacFrontendHighlighterApp.ajaxurl + '?action=edac_frontend_highlight_ajax&post_id=' + edacFrontendHighlighterApp.postID + '&nonce=' + edacFrontendHighlighterApp.nonce;

			self.showWait( true );

			xhr.open( 'GET', url );

			xhr.onload = function() {
				if ( xhr.status === 200 ) {
					self.showWait( false );

					const response = JSON.parse( xhr.responseText );
					if ( true === response.success ) {
						const responseJson = JSON.parse( response.data );
						if ( self.settings.showIgnored ) {
							resolve( {
								issues: responseJson.issues,
								fixes: responseJson.fixes,
							} );
						} else {
							resolve(
								{
									issues: responseJson.issues.filter( ( item ) => {
										// When rules are filtered off from php we can get null values for some properties
										// here. This should be fixed upstream but handling it here as well for robustness.
										if ( item.rule_type === null ) {
											return false;
										}

										return ( item.id === self.urlParameter || item.rule_type !== 'ignored' );
									} ),
									fixes: responseJson.fixes,
								},
							);
						}
					} else if ( ! self._scanAttempted && response.data?.[ 0 ]?.code === -3 ) {
						// Only try kickoffScan once per highlightAjax call
						self._scanAttempted = true;
						self.kickoffScan();
						// After kickoffScan, try highlightAjax again, but only once
						setTimeout( () => {
							self.highlightAjax().then( resolve ).catch( reject );
						}, 5000 ); // Wait 5s for scan to complete.
					} else {
						// Default: resolve with empty issues/fixes
						resolve( { issues: [], fixes: [] } );
					}
				} else {
					self.showWait( false );

					reject( {
						status: xhr.status,
						statusText: xhr.statusText,
					} );
				}
			};

			xhr.onerror = function() {
				self.showWait( false );

				reject( {
					status: xhr.status,
					statusText: xhr.statusText,
				} );
			};

			xhr.send();
		} );
	}

	/**
	 * This function toggles showing Wait
	 * @param {boolean} status
	 */
	showWait( status = true ) {
		if ( status ) {
			document.querySelector( 'body' ).classList.add( 'edac-app-wait' );
		} else {
			document.querySelector( 'body' ).classList.remove( 'edac-app-wait' );
		}
	}

	/**
	 * This function removes the highlight/tooltip buttons and runs cleanups for each.
	 */
	removeHighlightButtons() {
		this.tooltips.forEach( ( item ) => {
			//remove click listener
			item.tooltip.removeEventListener( 'click', item.listeners.onClick );

			//remove position/resize listener: https://floating-ui.com/docs/autoUpdate
			item.listeners.cleanup();
		} );

		const buttons = document.querySelectorAll( '.edac-highlight-btn' );
		buttons.forEach( ( button ) => {
			button.remove();
		} );

		// Clean up any landmark labels
		this.removeLandmarkLabels();
	}

	/**
	 * This function adds a new button element to the DOM, which acts as a tooltip for the highlighted element.
	 *
	 * @param {HTMLElement} element - The DOM element before which the tooltip button will be inserted.
	 * @param {Object}      value   - An object containing properties used to customize the tooltip button.
	 * @param {number}      index   - The index of the element being processed.
	 * @return {Object} - information about the tooltip
	 */
	/* eslint-disable no-unused-vars */
	addTooltip( element, value, index, totalItems ) {
		// Create the tooltip.
		const tooltip = document.createElement( 'button' );
		tooltip.classList = 'edac-highlight-btn edac-highlight-btn-' + value.rule_type;
		tooltip.setAttribute( 'aria-label', sprintf( __( 'Open details for %1$s, %2$s of %3$s', 'accessibility-checker' ), value.rule_title, index + 1, totalItems ) );
		tooltip.setAttribute( 'aria-expanded', 'false' );
		tooltip.setAttribute( 'aria-haspopup', 'dialog' );

		//add data-id to the tooltip/button so we can find it later.
		tooltip.dataset.id = value.id;

		const onClick = ( e ) => {
			const id = e.currentTarget.dataset.id;
			this.showIssue( id );
			this.focusTrapControls();
		};

		tooltip.addEventListener( 'click', onClick );

		// Add the tooltip to the page.
		document.body.append( tooltip );

		// Store a unique identifier for the target element
		// Use a WeakMap-style unique identifier based on the actual element object
		// This ensures that even if multiple elements have identical HTML, they get different identifiers
		if ( ! element.__edacElementId ) {
			element.__edacElementId = 'edac-' + Math.random().toString( 36 ).substr( 2, 9 );
		}
		tooltip.dataset.targetElement = element.__edacElementId;

		// Add creation timestamp to track order of tooltip creation
		tooltip.dataset.creationOrder = Date.now() + Math.random(); // Ensure uniqueness

		const updatePosition = function() {
			// Get the sorted index and element hash for this tooltip
			const sortedIndex = parseInt( tooltip.dataset.sortedIndex || '0', 10 );
			const currentElementHash = tooltip.dataset.targetElement;

			// Calculate offset based on sorted position, not creation order
			// Count how many tooltips for this same element have a LOWER sorted index
			let tooltipOffset = 0;
			const allTooltips = Array.from( document.querySelectorAll( '.edac-highlight-btn' ) );
			for ( const btn of allTooltips ) {
				if ( btn === tooltip ) {
					break; // Stop counting when we reach this tooltip
				}
				const btnSortedIndex = parseInt( btn.dataset.sortedIndex || '0', 10 );
				// Count only tooltips for the same element that come before this one in sorted order
				if ( btn.dataset.targetElement === currentElementHash && btnSortedIndex < sortedIndex ) {
					tooltipOffset++;
				}
			}

			const TOOLTIP_GAP = 5; // Gap between tooltip buttons in pixels

			computePosition( element, tooltip, {
				placement: 'top-start',
				middleware: [],
			} ).then( ( { x, y } ) => {
				const elRect = element.getBoundingClientRect();
				const elHeight = element.offsetHeight === undefined ? 0 : element.offsetHeight;
				const tooltipHeight = tooltip.offsetHeight === undefined ? 0 : tooltip.offsetHeight;
				const tooltipWidth = tooltip.offsetWidth === undefined ? 0 : tooltip.offsetWidth;

				// Calculate the horizontal offset for stacking multiple tooltips on the same element
				const left = tooltipOffset * ( tooltipWidth + TOOLTIP_GAP );

				// Start with the position from computePosition
				const finalLeft = x + left;
				let finalTop = y;

				// Special handling for zero-height elements (like empty <p> tags)
				// When an element has no height, computePosition may not calculate y correctly
				// Use the element's bounding rect top position adjusted for tooltip height
				if ( elHeight === 0 && elRect.height === 0 ) {
					// Element has no visual height
					// Position tooltip above where the element is in the document
					finalTop = elRect.top + document.documentElement.scrollTop - tooltipHeight - 5;
				}

				// Note: We do NOT clamp to viewport boundaries
				// Tooltips should follow their elements even when outside viewport
				// They'll become visible when scrolling to the element

				Object.assign( tooltip.style, {
					left: `${ finalLeft }px`,
					top: `${ finalTop }px`,
				} );
			} );
		};


		// Place the tooltip at the element's position on the page.
		// See: https://floating-ui.com/docs/autoUpdate
		const cleanup = autoUpdate(
			element,
			tooltip,
			updatePosition, {
				ancestorScroll: true,
				ancestorResize: true,
				elementResize: true,
				layoutShift: true,
				animationFrame: true, 	// TODO: Disable styles sometimes causes the toolbar to disappear until a scroll or resize event. This may help - but is expensive.

			}
		);

		return {
			element,
			tooltip,
			listeners: {
				onClick,
				cleanup,
			},
		};
	}

	/**
	 * This function adds a new div element to the DOM, which contains the accessibility checker panel.
	 */
	addHighlightPanel() {
		const widgetPosition = edacFrontendHighlighterApp?.widgetPosition || 'right';

		const userCanEdit = edacFrontendHighlighterApp && edacFrontendHighlighterApp?.userCanEdit && edacFrontendHighlighterApp?.loggedIn;
		const moveLabel = widgetPosition === 'right'
			? __( 'Move to Left', 'accessibility-checker' )
			: __( 'Move to Right', 'accessibility-checker' );
		const scanIcon = `<span class="edac-menu-icon edac-menu-icon--scan" aria-hidden="true"></span>`;
		const refreshIcon = `<span class="edac-menu-icon edac-menu-icon--refresh" aria-hidden="true"></span>`;
		const trashIcon = `<span class="edac-menu-icon edac-menu-icon--trash" aria-hidden="true"></span>`;
		const moveIcon = `<span class="edac-menu-icon edac-menu-icon--move" aria-hidden="true"></span>`;
		const stylesIcon = `<span class="edac-menu-icon edac-menu-icon--styles" aria-hidden="true"></span>`;
		const dockIcon = `<span class="edac-menu-icon edac-menu-icon--dock" aria-hidden="true"></span>`;
		const dockLabel = localStorage.getItem( 'edac-panel-docked' ) === '1'
			? __( 'Undock Panel', 'accessibility-checker' )
			: __( 'Dock Panel', 'accessibility-checker' );

		const clearButtonMarkup = userCanEdit
			? `<li role="none"><button id="edac-highlight-clear-issues" class="edac-highlight-clear-issues" role="menuitem"><span>${ __( 'Clear Issues', 'accessibility-checker' ) }</span>${ trashIcon }</button></li>`
			: '';

		const rescanButton = userCanEdit
			? `<li role="none"><button id="edac-highlight-rescan" class="edac-highlight-rescan" role="menuitem"><span>${ __( 'Rescan This Page', 'accessibility-checker' ) }</span>${ refreshIcon }</button></li>`
			: '';

		const newElement = `
                        <div id="edac-highlight-announcer" class="edac-sr-only" role="status" aria-live="polite" aria-atomic="true"></div>
                        <div id="edac-highlight-panel" class="edac-highlight-panel edac-highlight-panel--${ widgetPosition }">
                                <button id="edac-highlight-panel-toggle" class="edac-highlight-panel-toggle" aria-haspopup="dialog" aria-label="${ __( 'Accessibility Checker Tools', 'accessibility-checker' ) }"></button>
                                <div id="edac-highlight-panel-controls" class="edac-highlight-panel-controls" tabindex="0" role="dialog" aria-labelledby="edac-highlight-panel-controls-title">
                                        <div class="edac-highlight-panel-controls-header">
                                                <div id="edac-highlight-panel-controls-title" class="edac-highlight-panel-controls-title" role="heading" aria-level="2"><span class="edac-highlight-panel-controls-title-icon" aria-hidden="true"></span>${ __( 'Accessibility Checker', 'accessibility-checker' ) }</div>
                                                <div class="edac-highlight-panel-controls-header-actions">
                                                        <div class="edac-highlight-menu-container">
                                                                <button id="edac-highlight-menu-button" class="edac-highlight-menu-button" aria-haspopup="menu" aria-expanded="false" aria-label="${ __( 'More options', 'accessibility-checker' ) }">&#8943;</button>
                                                                <ul id="edac-highlight-menu" class="edac-highlight-menu" role="menu" aria-label="${ __( 'More options', 'accessibility-checker' ) }" hidden>
                                                                        <li role="none"><button id="edac-highlight-move" class="edac-highlight-move" role="menuitem"><span>${ moveLabel }</span>${ moveIcon }</button></li>
                                                                        <li role="none"><button id="edac-highlight-dock" class="edac-highlight-dock" role="menuitem"><span>${ dockLabel }</span>${ dockIcon }</button></li>
                                                                        ${ rescanButton }
                                                                        ${ clearButtonMarkup }
                                                                        <li role="none"><button id="edac-highlight-disable-styles" class="edac-highlight-disable-styles" role="menuitem" aria-live="polite" aria-label="${ __( 'Disable Page Styles', 'accessibility-checker' ) }"><span>${ __( 'Disable Styles', 'accessibility-checker' ) }</span>${ stylesIcon }</button></li>
                                                                </ul>
                                                        </div>
                                                        <button id="edac-highlight-panel-controls-close" class="edac-highlight-panel-controls-close" aria-label="${ __( 'Close', 'accessibility-checker' ) }">×</button>
                                                </div>
                                        </div>
                                        <div id="edac-highlight-panel-controls-content" class="edac-highlight-panel-controls-content">
                                                <div id="edac-highlight-panel-controls-content-empty" class="edac-highlight-panel-controls-content-empty">
                                                        ${ __( 'No issues found on this page.', 'accessibility-checker' ) }
                                                </div>
                                                <div class="edac-highlight-panel-controls-content-issue" style="display:none">
                                                        <div id="edac-highlight-panel-description-title" class="edac-highlight-panel-description-title"></div>
                                                        <div class="edac-highlight-panel-description-content"></div>
                                                        <div id="edac-highlight-panel-description-code" class="edac-highlight-panel-description-code"><code></code></div>
                                                        <div id="edac-highlight-panel-description-fix" class="edac-highlight-panel-description-fix"></div>
                                                </div>
                                        </div>
                                        <div class="edac-highlight-panel-controls-footer">
                                                <div class="edac-highlight-panel-controls-summary">${ __( 'Loading...', 'accessibility-checker' ) }</div>
                                                <div class="edac-highlight-panel-controls-buttons">
                                                        <button id="edac-highlight-previous" disabled="true"><span aria-hidden="true">← </span>${ __( 'Previous', 'accessibility-checker' ) }</button>
                                                        <span class="edac-highlight-panel-controls-pagination" id="edac-highlight-pagination" aria-live="polite"></span>
                                                        <button id="edac-highlight-next" disabled="true">${ __( 'Next', 'accessibility-checker' ) }<span aria-hidden="true"> →</span></button>
                                                </div>
                                        </div>
                                </div>
                        </div>
                `;

		document.body.insertAdjacentHTML( 'afterbegin', newElement );
		const panel = document.getElementById( 'edac-highlight-panel' );

		// Override --wp-admin-theme-color with the correct value from the user's
		// admin color scheme, since WordPress does not update this variable on the frontend.
		if ( edacFrontendHighlighterApp?.adminThemeColor ) {
			panel.style.setProperty( '--wp-admin-theme-color', edacFrontendHighlighterApp.adminThemeColor );
		}

		this.initDrag( panel );
		return panel;
	}

	/**
	 * Makes the panel draggable by its header. Buttons in the header are excluded from triggering a drag.
	 *
	 * @param {HTMLElement} panel
	 */
	initDrag( panel ) {
		const controls = panel.querySelector( '.edac-highlight-panel-controls' );
		const header = panel.querySelector( '.edac-highlight-panel-controls-header' );
		if ( ! header || ! controls ) {
			return;
		}

		header.style.cursor = 'grab';

		let startX, startY, startLeft, startTop, isDragging, hasMoved;

		const resetControlsPosition = () => {
			controls.style.position = '';
			controls.style.width = '';
			controls.style.left = '';
			controls.style.top = '';
			controls.style.right = '';
			controls.style.bottom = '';
		};

		header.addEventListener( 'pointerdown', ( e ) => {
			// Let buttons and links handle their own clicks.
			if ( e.target.closest( 'button, a' ) ) {
				return;
			}

			// Disable drag in docked mode.
			if ( this.isDocked ) {
				return;
			}

			const rect = controls.getBoundingClientRect();
			startLeft = rect.left;
			startTop = rect.top;
			startX = e.clientX;
			startY = e.clientY;
			isDragging = true;
			hasMoved = false;

			// Detach controls from panel flow and position them independently.
			controls.style.width = rect.width + 'px';
			controls.style.position = 'fixed';
			controls.style.left = startLeft + 'px';
			controls.style.top = startTop + 'px';
			controls.style.right = 'auto';
			controls.style.bottom = 'auto';

			// Capture pointer so pointermove/pointerup fire even outside the window.
			header.setPointerCapture( e.pointerId );
			document.body.style.userSelect = 'none';
			header.style.cursor = 'grabbing';

			e.preventDefault();
		} );

		header.addEventListener( 'pointermove', ( e ) => {
			if ( ! isDragging ) {
				return;
			}
			const dx = e.clientX - startX;
			const dy = e.clientY - startY;
			if ( ! hasMoved && Math.abs( dx ) <= 4 && Math.abs( dy ) <= 4 ) {
				return;
			}
			hasMoved = true;
			controls.style.left = ( startLeft + dx ) + 'px';
			controls.style.top = ( startTop + dy ) + 'px';
		} );

		header.addEventListener( 'pointerup', ( e ) => {
			if ( ! isDragging ) {
				return;
			}
			isDragging = false;
			header.releasePointerCapture( e.pointerId );
			document.body.style.userSelect = '';
			header.style.cursor = 'grab';

			// Check final delta too — pointermove may have been skipped on a fast gesture.
			const totalDx = e.clientX - startX;
			const totalDy = e.clientY - startY;
			const wasDrag = hasMoved || Math.abs( totalDx ) > 4 || Math.abs( totalDy ) > 4;

			if ( wasDrag ) {
				// Mark as dragged and update the menu action.
				this.isDragged = true;
				this.moveButton.querySelector( 'span' ).textContent = __( 'Reset Position', 'accessibility-checker' );
			} else {
				// Simple click — undo the fixed positioning applied on pointerdown.
				resetControlsPosition();
			}
		} );

		header.addEventListener( 'pointercancel', ( e ) => {
			if ( ! isDragging ) {
				return;
			}
			isDragging = false;
			header.releasePointerCapture( e.pointerId );
			document.body.style.userSelect = '';
			header.style.cursor = 'grab';
			if ( ! hasMoved ) {
				resetControlsPosition();
			}
		} );
	}

	/**
	 * This function highlights the next element on the page. It uses the 'currentButtonIndex' property to keep track of the current element.
	 */
	highlightFocusNext = () => {
		if ( this.currentButtonIndex === null ) {
			this.currentButtonIndex = 0;
		} else {
			this.currentButtonIndex = ( this.currentButtonIndex + 1 ) % this.issues.length;
		}
		const id = this.issues[ this.currentButtonIndex ].id;
		this.showIssue( id );
	};

	/**
	 * This function highlights the previous element on the page. It uses the 'currentButtonIndex' property to keep track of the current element.
	 */
	highlightFocusPrevious = () => {
		if ( this.currentButtonIndex === null ) {
			this.currentButtonIndex = this.issues.length - 1;
		} else {
			this.currentButtonIndex = ( this.currentButtonIndex - 1 + this.issues.length ) % this.issues.length;
		}
		const id = this.issues[ this.currentButtonIndex ].id;
		this.showIssue( id );
	};

	/**
	 * This function sets a focus trap on the controls panel
	 */
	focusTrapControls = () => {
		this.panelControlsFocusTrap.activate();

		setTimeout( () => {
			this.closePanel?.focus();
		}, 100 ); //give render time to complete.
	};

	/**
	 * This function shows an issue related to an element.
	 * @param {string} id - The ID of the element.
	 */

	showIssue = ( id ) => {
		this.removeSelectedClasses();

		if ( id === undefined ) {
			return;
		}

		const issue = this.issues.find( ( i ) => String( i.id ) === String( id ) );

		if ( ! issue ) {
			return;
		}

		this.currentButtonIndex = this.issues.findIndex( ( i ) => String( i.id ) === String( id ) );

		// Keep the URL in sync so the current issue is bookmarkable / shareable.
		const url = new URL( window.location.href );
		url.searchParams.set( 'edac', id );
		history.replaceState( null, '', url.toString() );

		const pagination = document.getElementById( 'edac-highlight-pagination' );
		if ( pagination ) {
			const visiblePosition = sprintf(
				// translators: %1$d is the current issue number, %2$d is the total number of issues.
				__( '%1$d of %2$d', 'accessibility-checker' ),
				this.currentButtonIndex + 1,
				this.issues.length
			);

			const issueTitle = issue.rule_title || __( 'Untitled issue', 'accessibility-checker' );
			const srAnnouncement = sprintf(
				// translators: %1$d is the current issue number, %2$d is the total number of issues, %3$s is the issue title.
				__( 'Issue %1$d of %2$d: %3$s', 'accessibility-checker' ),
				this.currentButtonIndex + 1,
				this.issues.length,
				issueTitle
			);

			pagination.textContent = '';

			const visiblePositionNode = document.createElement( 'span' );
			visiblePositionNode.setAttribute( 'aria-hidden', 'true' );
			visiblePositionNode.textContent = visiblePosition;

			const srOnlyNode = document.createElement( 'span' );
			srOnlyNode.className = 'edac-sr-only';
			srOnlyNode.textContent = srAnnouncement;

			pagination.append( visiblePositionNode, srOnlyNode );
		}

		const tooltip = issue.tooltip;
		const element = issue.element;

		if ( tooltip && element ) {
			tooltip.classList.add( 'edac-highlight-btn-selected' );
			element.classList.add( 'edac-highlight-element-selected' );

			if ( element.offsetWidth < 20 ) {
				element.classList.add( 'edac-highlight-element-selected-min-width' );
			}

			if ( element.offsetHeight < 5 ) {
				element.classList.add( 'edac-highlight-element-selected-min-height' );
			}

			element.scrollIntoView( { block: 'center' } );

			if ( isFocusable( tooltip ) ) {
				//issueElement.focus();

				if ( ! this.checkVisibility( tooltip ) || ! this.checkVisibility( element ) ) {
					this.currentIssueStatus = __( 'The element is not visible. Try disabling styles.', 'accessibility-checker' );
					//TODO: console.log(`Element with id ${id} is not visible!`);
				} else {
					this.currentIssueStatus = null;
				}
			} else {
				this.currentIssueStatus = __( 'The element is not focusable. Try disabling styles.', 'accessibility-checker' );
				//TODO: console.log(`Element with id ${id} is not focusable!`);
			}
		} else {
			this.currentIssueStatus = __( 'The element was not found on the page.', 'accessibility-checker' );
			//TODO: console.log(`Element with id ${id} not found in the document!`);
		}

		this.descriptionOpen( id );
	};

	/**
	 * This function checks if a given element is visible on the page.
	 *
	 * @param {HTMLElement} el The element to check for visibility
	 * @return {boolean} isVisible
	 */
	checkVisibility = ( el ) => {
		//checkVisibility is still in draft but well supported on many browsers.
		//See: https://drafts.csswg.org/cssom-view-1/#dom-element-checkvisibility
		//See: https://caniuse.com/mdn-api_element_checkvisibility
		if ( typeof ( el.checkVisibility ) !== 'function' ) {
			//See: https://github.com/jquery/jquery/blob/main/src/css/hiddenVisibleSelectors.js
			return !! ( el.offsetWidth || el.offsetHeight || el.getClientRects().length );
		}
		return el.checkVisibility( {
			checkOpacity: true, // Check CSS opacity property too
			checkVisibilityCSS: true, // Check CSS visibility property too
		} );
	};

	/**
	 * This function opens the accessibility checker panel.
	 * @param {number} id of the issue
	 */
	panelOpen( id ) {
		this.highlightPanel.classList.add( 'edac-highlight-panel-visible' );
		this.panelControls.style.display = 'flex';
		this.panelToggle.style.display = 'none';

		// previous and next buttons are disabled until we have issues to show.
		this.nextButton.disabled = true;
		this.previousButton.disabled = true;

		// If issues were cleared, trigger a fresh scan instead of loading stale data.
		if ( this._issuesCleared ) {
			this._issuesCleared = false;
			this.rescanPage();
			return;
		}

		// Get the issues for this page.
		this.highlightAjax().then(
			( json ) => {

				this.issues = json.issues;
				this.fixes = json.fixes;

				json.issues.forEach( function( value, index ) {
					const element = this.findElement( value, index );
					if ( element !== null ) {
						this.issues[ index ].element = element;
					}
				}.bind( this ) );

				// Sort issues by DOM order using native compareDocumentPosition
				this.issues.sort( ( a, b ) => {
					// If elements weren't found, push to end
					if ( ! a.element && b.element ) {
						return 1;
					}
					if ( a.element && ! b.element ) {
						return -1;
					}
					if ( ! a.element && ! b.element ) {
						return 0;
					}

					// Use DOM compareDocumentPosition for accurate ordering
					const position = a.element.compareDocumentPosition( b.element );

					// DOCUMENT_POSITION_FOLLOWING (4) means b comes after a in DOM
					// eslint-disable-next-line no-bitwise
					if ( position & Node.DOCUMENT_POSITION_FOLLOWING ) {
						return -1;
					}
					// DOCUMENT_POSITION_PRECEDING (2) means b comes before a in DOM
					// eslint-disable-next-line no-bitwise
					if ( position & Node.DOCUMENT_POSITION_PRECEDING ) {
						return 1;
					}

					// Elements are the same (or in different documents)
					// When elements are the same, sort by issue ID for consistent ordering
					// This ensures multiple issues on the same element appear in predictable order
					const idA = parseInt( a.id, 10 );
					const idB = parseInt( b.id, 10 );
					return idA - idB;
				} );

				// Update tooltip aria-labels to reflect sorted order
				this.issues.forEach( ( issue, sortedIndex ) => {
					if ( issue.tooltip ) {
						// Store the sorted index on the tooltip for debugging
						issue.tooltip.dataset.sortedIndex = sortedIndex;
						issue.tooltip.setAttribute(
							'aria-label',
							sprintf(
								__( 'Open details for %1$s, %2$s of %3$s', 'accessibility-checker' ),
								issue.rule_title,
								sortedIndex + 1,
								this.issues.length
							)
						);
					}
				} );

				this.showIssueCount();

				if ( id !== undefined ) {
					this.showIssue( id );
				} else if ( this.currentButtonIndex !== null && this.issues[ this.currentButtonIndex ] ) {
					this.showIssue( this.issues[ this.currentButtonIndex ].id );
				} else if ( this.issues.length > 0 ) {
					this.showIssue( this.issues[ 0 ].id );
				}
			}
		).catch( ( err ) => {
			// Output a message that says that there are no issues or that the issues could not be loaded.
			const summary = document.querySelector( '.edac-highlight-panel-controls-summary' );
			// Output result messaging in the panel instead of a popup notice.
			if ( summary ) {
				summary.textContent = __( 'An error occurred when loading the issues.', 'accessibility-checker' );
			}
		} );
	}

	/**
	 * This function closes the accessibility checker panel.
	 */
	panelClose() {
		if ( this.isDocked ) {
			this.removeDock();
		}
		this.highlightPanel.classList.remove( 'edac-highlight-panel-visible' );
		this.panelControls.style.display = 'none';
		this.panelToggle.style.display = 'block';
		this.removeSelectedClasses();
		this.removeHighlightButtons();

		this.closePanel.removeEventListener( 'click', this.panelControlsFocusTrap.deactivate );

		this.panelToggle.focus();
	}

	/**
	 * Toggle between docked and undocked panel modes.
	 */
	toggleDock() {
		if ( this.isDocked ) {
			this.removeDock();
		} else {
			this.applyDock();
			// Open the panel if not already open.
			if ( ! this.highlightPanel.classList.contains( 'edac-highlight-panel-visible' ) ) {
				this.panelOpen();
			}
		}
	}

	/**
	 * Apply docked sidebar mode.
	 */
	applyDock() {
		this.isDocked = true;
		localStorage.setItem( 'edac-panel-docked', '1' );

		const isRight = this.highlightPanel.classList.contains( 'edac-highlight-panel--right' );
		this.highlightPanel.classList.add( 'edac-highlight-panel--docked' );

		// Reset any drag-applied inline position styles.
		this.isDragged = false;
		this.panelControls.style.position = '';
		this.panelControls.style.left = '';
		this.panelControls.style.right = '';
		this.panelControls.style.top = '';
		this.panelControls.style.bottom = '';
		this.panelControls.style.width = '';

		// Offset below the admin bar if present.
		const adminBar = document.getElementById( 'wpadminbar' );
		const adminBarHeight = adminBar ? adminBar.offsetHeight : 0;
		this.highlightPanel.style.setProperty( '--edac-adminbar-height', adminBarHeight + 'px' );

		// Show panel controls, hide the toggle button.
		this.panelControls.style.display = 'flex';
		this.panelToggle.style.display = 'none';
		this.highlightPanel.classList.add( 'edac-highlight-panel-visible' );

		// Push page content to make room for the panel.
		// Use a rAF so the browser has laid out the panel before we read its width.
		requestAnimationFrame( () => {
			const panelWidth = this.panelControls.offsetWidth + 'px';
			document.body.style[ isRight ? 'marginRight' : 'marginLeft' ] = panelWidth;
		} );

		if ( this.dockButton ) {
			this.dockButton.querySelector( 'span' ).textContent = __( 'Undock Panel', 'accessibility-checker' );
		}

		// Reset move button label to reflect the current side (no longer "Reset Position").
		this.moveButton.querySelector( 'span' ).textContent = isRight
			? __( 'Move to Left', 'accessibility-checker' )
			: __( 'Move to Right', 'accessibility-checker' );
		this.announce( __( 'Panel docked.', 'accessibility-checker' ) );
	}

	/**
	 * Remove docked sidebar mode and return to overlay.
	 */
	removeDock() {
		this.isDocked = false;
		localStorage.removeItem( 'edac-panel-docked' );

		this.highlightPanel.classList.remove( 'edac-highlight-panel--docked' );

		// Remove body margin.
		document.body.style.marginRight = '';
		document.body.style.marginLeft = '';

		// Reset any inline position styles set during dock.
		this.panelControls.style.width = '';
		this.panelControls.style.position = '';
		this.panelControls.style.left = '';
		this.panelControls.style.right = '';
		this.panelControls.style.top = '';
		this.panelControls.style.bottom = '';

		if ( this.dockButton ) {
			this.dockButton.querySelector( 'span' ).textContent = __( 'Dock Panel', 'accessibility-checker' );
		}
		this.announce( __( 'Panel undocked.', 'accessibility-checker' ) );
	}

	/**
	 * This function removes the classes that indicates a button or element are selected
	 */
	removeSelectedClasses = () => {
		//remove selected class from previously selected buttons
		const selectedButtons = document.querySelectorAll( '.edac-highlight-btn-selected' );
		selectedButtons.forEach( ( selectedButton ) => {
			selectedButton.classList.remove( 'edac-highlight-btn-selected' );
		} );
		//remove selected class from previously selected elements
		const selectedElements = document.querySelectorAll( '.edac-highlight-element-selected' );
		selectedElements.forEach( ( selectedElement ) => {
			selectedElement.classList.remove(
				'edac-highlight-element-selected',
				'edac-highlight-element-selected-min-width',
				'edac-highlight-element-selected-min-height'
			);

			if ( selectedElement.classList.length === 0 ) {
				selectedElement.removeAttribute( 'class' );
			}
		} );

		// Clean up any landmark labels when highlights are removed
		this.removeLandmarkLabels();
	};

	/**
	 * This function displays the description of the issue.
	 *
	 * @param {string} dataId
	 */
	descriptionOpen( dataId ) {
		// get the value of the property by key
		const searchTerm = dataId;
		const keyToSearch = 'id';
		const matchingObj = this.issues.find( ( obj ) => String( obj[ keyToSearch ] ) === String( searchTerm ) );

		if ( matchingObj ) {
			const descriptionTitle = document.querySelector( '.edac-highlight-panel-description-title' );
			const descriptionContent = document.querySelector( '.edac-highlight-panel-description-content' );
			const descriptionCode = document.querySelector( '.edac-highlight-panel-description-code code' );

			let content = '';

			const newWindowHtml = `<span aria-hidden="true">↗\uFE0E</span><span class="edac-sr-only">${ __( ', opens a new window', 'accessibility-checker' ) }</span>`;

			// WCAG reference + severity inline
			if ( matchingObj.wcag ) {
				const wcagNumber = parseFloat( matchingObj.wcag );
				const showWcagNumber = ! isNaN( wcagNumber ) && wcagNumber >= 1;
				const wcagLinkText = matchingObj.wcag_title
					? `${ showWcagNumber ? matchingObj.wcag + ' ' : '' }${ matchingObj.wcag_title } ${ newWindowHtml }`
					: `${ showWcagNumber ? matchingObj.wcag + ' ' : '' }${ newWindowHtml }`;

				let severityBadgeHtml = '';
				if ( matchingObj.severity ) {
					const severityMap = {
						1: { label: __( 'Critical', 'accessibility-checker' ), slug: 'critical' },
						2: { label: __( 'High', 'accessibility-checker' ), slug: 'high' },
						3: { label: __( 'Medium', 'accessibility-checker' ), slug: 'medium' },
						4: { label: __( 'Low', 'accessibility-checker' ), slug: 'low' },
					};
					const severity = severityMap[ matchingObj.severity ];
					if ( severity ) {
						severityBadgeHtml = `<strong class="edac-highlight-panel-description-wcag-label" role="heading" aria-level="4">${ __( 'Severity:', 'accessibility-checker' ) }</strong> <span class="edac-badge edac-badge--severity-${ severity.slug }"><span class="edac-badge__label">${ severity.label }</span></span>`;
					}
				}

				content += `<div class="edac-highlight-panel-description-wcag"><strong class="edac-highlight-panel-description-wcag-label" role="heading" aria-level="4">${ __( 'WCAG:', 'accessibility-checker' ) }</strong> <a class="edac-highlight-panel-description-reference" href="${ matchingObj.link }" target="_blank" rel="noopener noreferrer">${ wcagLinkText }</a>${ severityBadgeHtml ? ` ${ severityBadgeHtml }` : '' }</div>`;
			}

			// Metadata row: Type
			content += `<div class="edac-highlight-panel-description-meta">`;

			// Type
			const typeIconDataUris = {
				error: 'data:image/svg+xml,' + encodeURIComponent( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="16" height="16" fill="none"><path d="M10 7.5V10.625M17.5 10C17.5 10.9849 17.306 11.9602 16.9291 12.8701C16.5522 13.7801 15.9997 14.6069 15.3033 15.3033C14.6069 15.9997 13.7801 16.5522 12.8701 16.9291C11.9602 17.306 10.9849 17.5 10 17.5C9.01509 17.5 8.03982 17.306 7.12987 16.9291C6.21993 16.5522 5.39314 15.9997 4.6967 15.3033C4.00026 14.6069 3.44781 13.7801 3.0709 12.8701C2.69399 11.9602 2.5 10.9849 2.5 10C2.5 8.01088 3.29018 6.10322 4.6967 4.6967C6.10322 3.29018 8.01088 2.5 10 2.5C11.9891 2.5 13.8968 3.29018 15.3033 4.6967C16.7098 6.10322 17.5 8.01088 17.5 10ZM10 13.125H10.0067V13.1317H10V13.125Z" stroke="#970C0C" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>' ),
				warning: 'data:image/svg+xml,' + encodeURIComponent( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="16" height="16" fill="none"><path d="M9.99997 7.5V10.625M2.24747 13.4383C1.52581 14.6883 2.42831 16.25 3.87081 16.25H16.1291C17.5708 16.25 18.4733 14.6883 17.7525 13.4383L11.6241 2.815C10.9025 1.565 9.09747 1.565 8.37581 2.815L2.24747 13.4383ZM9.99997 13.125H10.0058V13.1317H9.99997V13.125Z" stroke="#CF8402" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>' ),
				ignored: 'data:image/svg+xml,' + encodeURIComponent( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 37 37" width="16" height="16" fill="none"><path d="M13.875 19.6562L17.3437 23.125L23.125 15.0312M32.375 18.5C32.375 20.3221 32.0161 22.1263 31.3188 23.8097C30.6215 25.4931 29.5995 27.0227 28.3111 28.3111C27.0227 29.5995 25.4931 30.6215 23.8097 31.3188C22.1263 32.0161 20.3221 32.375 18.5 32.375C16.6779 32.375 14.8737 32.0161 13.1903 31.3188C11.5069 30.6215 9.97731 29.5995 8.68889 28.3111C7.40048 27.0227 6.37846 25.4931 5.68117 23.8097C4.98389 22.1263 4.625 20.3221 4.625 18.5C4.625 14.8201 6.08683 11.291 8.68889 8.68889C11.291 6.08683 14.8201 4.625 18.5 4.625C22.1799 4.625 25.709 6.08683 28.3111 8.68889C30.9132 11.291 32.375 14.8201 32.375 18.5Z" stroke="#737373" stroke-width="2.775" stroke-linecap="round" stroke-linejoin="round"/></svg>' ),
			};
			const typeIconUri = typeIconDataUris[ matchingObj.rule_type ];
			const typeBadgeHtml = `<span class="edac-badge edac-badge--${ matchingObj.rule_type } edac-badge--large">
				${ typeIconUri ? `<img src="${ typeIconUri }" width="16" height="16" style="display:block;width:16px;height:16px;flex-shrink:0" alt="" />` : '' }
				<span class="edac-badge__label">${ { error: __( 'Problem', 'accessibility-checker' ), warning: __( 'Needs Review', 'accessibility-checker' ), ignored: __( 'Ignored', 'accessibility-checker' ) }[ matchingObj.rule_type ] ?? matchingObj.rule_type }</span>
			</span>`;

			content += `</div>`;


			// Get the summary of the issue
			if ( matchingObj.summary ) {
				content += `<p class="edac-highlight-panel-description-summary">${ matchingObj.summary }</p>`;
			}

			const isPro = window.edacFrontendHighlighterApp?.isPro;
			const hasExplanation = matchingObj.why_it_matters || matchingObj.how_to_fix;

			if ( isPro && hasExplanation ) {
				// Pro: show expandable explanation accordion
				const explanationArrowUri = 'data:image/svg+xml,' + encodeURIComponent( '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z" fill="#2271b1"/></svg>' );
				content += `<button class="edac-highlight-panel-description-explanation-toggle" aria-expanded="${ this.explanationExpanded }" aria-controls="edac-highlight-panel-description-explanation">${ __( 'Show explanation', 'accessibility-checker' ) } <img src="${ explanationArrowUri }" width="16" height="16" class="edac-highlight-panel-description-explanation-toggle-arrow" style="display:inline-block;width:16px;height:16px;vertical-align:middle" alt="" /></button>`;
				content += `<div id="edac-highlight-panel-description-explanation" class="edac-highlight-panel-description-explanation"${ this.explanationExpanded ? '' : ' hidden' }>`;

				if ( matchingObj.why_it_matters ) {
					content += `<div class="edac-highlight-panel-description-how-to-fix">
						<div class="edac-highlight-panel-description-how-to-fix-title" role="heading" aria-level="4">${ __( 'Why It Matters', 'accessibility-checker' ) }</div>
						<div class="edac-highlight-panel-description-how-to-fix-content">${ matchingObj.why_it_matters }</div>
					</div>`;
				}

				if ( matchingObj.how_to_fix ) {
					content += `<div class="edac-highlight-panel-description-how-to-fix">
						<div class="edac-highlight-panel-description-how-to-fix-title" role="heading" aria-level="4">${ __( 'How to Fix', 'accessibility-checker' ) }</div>
						<div class="edac-highlight-panel-description-how-to-fix-content">${ matchingObj.how_to_fix }</div>
					</div>`;
				}

				content += `<div><a class="edac-highlight-panel-description-reference" href="${ matchingObj.link }" target="_blank" rel="noopener noreferrer">${ __( 'More Detailed Documentation', 'accessibility-checker' ) } ${ newWindowHtml }</a></div>`;
				content += `</div>`;
			} else {
				// Free: show a plain "How to Fix" link
				content += `<a class="edac-highlight-panel-description-reference" href="${ matchingObj.link }" target="_blank" rel="noopener noreferrer">${ __( 'How to Fix', 'accessibility-checker' ) } ${ newWindowHtml }</a>`;
			}

			// Get the code button
			const codeArrowUri = 'data:image/svg+xml,' + encodeURIComponent( '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z" fill="#2271b1"/></svg>' );
			content += `<div><button class="edac-highlight-panel-description-code-button" aria-expanded="${ this.codeExpanded }" aria-controls="edac-highlight-panel-description-code">${ __( 'Show Affected Code', 'accessibility-checker' ) } <img src="${ codeArrowUri }" width="16" height="16" class="edac-highlight-panel-description-code-button-arrow" style="display:inline-block;width:16px;height:16px;vertical-align:middle" alt="" /></button></div>`;


			// title and content (notice only rendered when there is a status message)
			const noticeHtml = this.currentIssueStatus
				? `<div class="edac-highlight-panel-description-notice">${ this.currentIssueStatus }</div>`
				: '';
			descriptionTitle.innerHTML = `${ noticeHtml }<span class="edac-highlight-panel-description-title-text" role="heading" aria-level="3">${ matchingObj.rule_title }</span>${ typeBadgeHtml }`;

			// content
			descriptionContent.innerHTML = content;

			// code object
			// remove any non-html from the object
			const htmlSnippet = matchingObj.object;
			const parser = new DOMParser();
			const parsedHtml = parser.parseFromString( htmlSnippet, 'text/html' );
			const firstParsedElement = parsedHtml.body.firstElementChild;

			if ( firstParsedElement ) {
				descriptionCode.innerText = firstParsedElement.outerHTML;
			} else {
				const textNode = document.createTextNode( matchingObj.object );
				descriptionCode.innerText = textNode.nodeValue;
			}

			// inject fix settings below the code box
			const descriptionFix = document.getElementById( 'edac-highlight-panel-description-fix' );
			if ( descriptionFix ) {
				descriptionFix.innerHTML = '';
			}
			if ( this.fixes[ matchingObj.slug ] && window.edacFrontendHighlighterApp?.userCanFix && descriptionFix ) {
				descriptionFix.innerHTML = `
					<div style="display:none;" class="always-hide">
						<div class="edac-fix-settings">
							<div class="edac-fix-settings--fields">
								${ this.fixes[ matchingObj.slug ].fields }
								<div class="edac-fix-settings--action-row">
									<button role="button" class="button button-primary edac-fix-settings--button--save">
										${ __( 'Save', 'accessibility-checker' ) }
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
						aria-label="${ sprintf( __( 'Fix issue: %s', 'accessibility-checker' ), this.fixes[ matchingObj.slug ][ Object.keys( this.fixes[ matchingObj.slug ] )[ 0 ] ].group_name ) }">
						${ __( 'Fix Issue', 'accessibility-checker' ) }
					</button>
				`;

				this.fixSettingsButton = descriptionFix.querySelector( '.edac-fix-settings--button--open' );
				this.fixSettingsButton.addEventListener( 'click', ( event ) => {
					this.showFixSettings( event );
				} );

				this.fixSettingsSaveButton = descriptionFix.querySelector( '.edac-fix-settings--button--save' );
				this.fixSettingsSaveButton.addEventListener( 'click', ( event ) => {
					saveFixSettings( event.target.closest( '.edac-fix-settings' ) );
				} );
			}

			// set explanation toggle listener
			const explanationToggle = document.querySelector( '.edac-highlight-panel-description-explanation-toggle' );
			if ( explanationToggle ) {
				explanationToggle.addEventListener( 'click', () => {
					const explanationPanel = document.querySelector( '#edac-highlight-panel-description-explanation' );
					const isExpanded = explanationToggle.getAttribute( 'aria-expanded' ) === 'true';
					this.explanationExpanded = ! isExpanded;
					explanationToggle.setAttribute( 'aria-expanded', String( this.explanationExpanded ) );
					explanationPanel.hidden = ! this.explanationExpanded;
				} );
			}

			// set code button listener
			this.codeContainer = document.querySelector( '.edac-highlight-panel-description-code' );
			this.codeButton = document.querySelector( '.edac-highlight-panel-description-code-button' );
			this.codeButton.addEventListener( 'click', () => this.codeToggle() );

			// restore persistent code expanded state
			this.codeContainer.style.display = this.codeExpanded ? 'block' : 'none';

			// show the issue content, hide the empty state
			const emptyState = document.querySelector( '.edac-highlight-panel-controls-content-empty' );
			const issueContent = document.querySelector( '.edac-highlight-panel-controls-content-issue' );
			if ( emptyState ) {
				emptyState.style.display = 'none';
			}
			if ( issueContent ) {
				issueContent.style.display = 'block';
			}
		}
	}

	/**
	 * This function closes the description.
	 */
	descriptionClose() {
		const emptyState = document.querySelector( '.edac-highlight-panel-controls-content-empty' );
		const issueContent = document.querySelector( '.edac-highlight-panel-controls-content-issue' );
		if ( emptyState ) {
			emptyState.style.display = 'block';
		}
		if ( issueContent ) {
			issueContent.style.display = 'none';
		}
	}

	/**
	 * This function disables all styles on the page.
	 */
	disableStyles() {
		/*
		If the site compiles css into a combined file, our method for disabling styles will cause out app's css to break.
		This checks if the app's css is loading into #edac-app-css as expected.
		If not, then we assume the css has been combined, so we manually add it to the document.
		*/
		if ( ! document.querySelector( '#edac-app-css' ) ) {
			const link = document.createElement( 'link' );
			link.rel = 'stylesheet';
			link.id = 'edac-app-css';
			link.type = 'text/css';
			link.href = edacFrontendHighlighterApp.appCssUrl;
			link.media = 'all';
			document.head.appendChild( link );
		}

		// Store inline styles with element references for restoration.
		this.originalInlineStyles = [];
		const elementsWithStyle = document.querySelectorAll( '*[style]:not([class^="edac"])' );
		elementsWithStyle.forEach( ( element ) => {
			this.originalInlineStyles.push( {
				element,
				style: element.getAttribute( 'style' ),
			} );
			element.removeAttribute( 'style' );
		} );

		// Find all stylesheets in the entire document (head and body).
		// Include: style elements, link[rel="stylesheet"], and link elements with .css href.
		const styleElements = Array.from( document.querySelectorAll(
			'style[type="text/css"], style, link[rel="stylesheet"], link[href$=".css"], link[href*=".css?"]'
		) );

		// Filter out our app CSS and dashicons, then store with position info.
		this.originalCss = styleElements
			.filter( ( element ) => element.id !== 'edac-app-css' && element.id !== 'dashicons-css' )
			.map( ( element ) => {
				// Store the parent and next sibling for position restoration.
				const parent = element.parentNode;
				let nextSibling = element.nextElementSibling;

				// Find the next sibling that won't be removed (for position restoration).
				while ( nextSibling ) {
					// Check if this sibling will be preserved (not a stylesheet we're removing).
					const isStyleElement = nextSibling.tagName === 'STYLE';
					const isLinkStylesheet = nextSibling.tagName === 'LINK' && (
						nextSibling.matches( '[rel="stylesheet"]' ) ||
						nextSibling.matches( '[href$=".css"]' ) ||
						nextSibling.matches( '[href*=".css?"]' )
					);
					const isPreserved = nextSibling.id === 'edac-app-css' || nextSibling.id === 'dashicons-css';

					// If it's not a stylesheet we'll remove, or it's preserved, use it as reference.
					if ( ( ! isStyleElement && ! isLinkStylesheet ) || isPreserved ) {
						break;
					}
					nextSibling = nextSibling.nextElementSibling;
				}

				return {
					element,
					parent,
					nextSibling,
				};
			} );

		// Remove the stylesheets.
		this.originalCss.forEach( ( item ) => {
			item.element.remove();
		} );

		document.querySelector( 'body' ).classList.add( 'edac-app-disable-styles' );

		this.stylesDisabled = true;
		this.disableStylesButton.querySelector( 'span' ).textContent = __( 'Enable Styles', 'accessibility-checker' );
		this.disableStylesButton.setAttribute( 'aria-label', __( 'Enable Page Styles', 'accessibility-checker' ) );
		this.announce( __( 'Page styles disabled.', 'accessibility-checker' ) );
	}

	/**
	 * This function enables all styles on the page.
	 */
	enableStyles() {
		// Restore stylesheets in their original order.
		// Process in reverse so insertBefore places them correctly.
		const reversedCss = [ ...this.originalCss ].reverse();

		reversedCss.forEach( ( item ) => {
			const parent = item.parent && item.parent.isConnected ? item.parent : document.head;

			if ( item.nextSibling && item.nextSibling.parentNode === parent ) {
				// Insert before the reference sibling to restore original position.
				parent.insertBefore( item.element, item.nextSibling );
			} else {
				// Fallback: append to parent if reference sibling is no longer valid.
				parent.appendChild( item.element );
			}
		} );

		// Restore inline styles to their original elements.
		if ( this.originalInlineStyles ) {
			this.originalInlineStyles.forEach( ( item ) => {
				if ( item.element && item.element.isConnected ) {
					item.element.setAttribute( 'style', item.style );
				}
			} );
		}

		document.querySelector( 'body' ).classList.remove( 'edac-app-disable-styles' );

		this.stylesDisabled = false;
		this.disableStylesButton.querySelector( 'span' ).textContent = __( 'Disable Styles', 'accessibility-checker' );
		this.disableStylesButton.setAttribute( 'aria-label', __( 'Disable Page Styles', 'accessibility-checker' ) );
		this.announce( __( 'Page styles re-enabled.', 'accessibility-checker' ) );

		// Re-render the current issue to restore panel state after styles are re-enabled.
		if ( this.currentButtonIndex !== null && this.issues[ this.currentButtonIndex ] ) {
			this.showIssue( this.issues[ this.currentButtonIndex ].id );
		}
	}

	/**
	 * 	* This function retrieves the value of a given URL parameter.
	 *
	 * @param {string} sParam The name of the URL parameter to be retrieved.
	 * @return {string | boolean} Returns the value of the URL parameter, or false if the parameter is not found.
	 */
	get_url_parameter( sParam ) {
		const sPageURL = window.location.search.substring( 1 );
		const sURLVariables = sPageURL.split( '&' );
		let sParameterName, i;

		for ( i = 0; i < sURLVariables.length; i++ ) {
			sParameterName = sURLVariables[ i ].split( '=' );

			if ( sParameterName[ 0 ] === sParam ) {
				return sParameterName[ 1 ] === undefined ? true : decodeURIComponent( sParameterName[ 1 ] );
			}
		}
		return false;
	}

	/**
	 * This function toggles the code container.
	 */
	codeToggle() {
		this.codeExpanded = ! this.codeExpanded;
		this.codeContainer.style.display = this.codeExpanded ? 'block' : 'none';
		this.codeButton.setAttribute( 'aria-expanded', String( this.codeExpanded ) );
	}

	showFixSettings( event ) {
		const fixSettingsContainer = event.target.closest( '.edac-highlight-panel-controls-content-issue' ).querySelector( '.edac-fix-settings' );
		if ( ! fixSettingsContainer ) {
			// this is a fail, it should do something.
			return;
		}
		const placeholder = document.createElement( 'span' );
		placeholder.classList.add( 'edac-fix-settings--origin-placeholder' );
		// put the placeholder AFTER the fix container.
		fixSettingsContainer.parentNode.insertBefore( placeholder, fixSettingsContainer );
		// renive the fixSettingsContainer from the DOM.
		fixSettingsContainer.remove();

		fillFixesModal(
			`<p class="modal-opening-message">${ __( 'These settings enable global fixes across your entire site. Pages may need to be resaved or a full site scan run to see fixes reflected in reports.', 'accessibility-checker' ) }</p>`,
			fixSettingsContainer
		);

		// pause the highlighter panel focus trap.
		this.panelControlsFocusTrap.pause();
		openFixesModal( event.target );

		// unpause the focus trap when the modal is closed (once only, to avoid handler accumulation).
		document.addEventListener( 'edac-fixes-modal-closed', () => {
			this.panelControlsFocusTrap.unpause();
		}, { once: true } );
	}

	/**
	 * This function counts the number of issues of a given type.
	 *
	 * @param {string} ruleType The type of issue to be counted.
	 * @return {number} The number of issues of a given type.
	 */
	countIssues( ruleType ) {
		let count = 0;
		for ( const issue of this.issues ) {
			if ( issue.rule_type === ruleType ) {
				count++;
			}
		}
		return count;
	}

	/**
	 * This function counts the number of ignored issues.
	 *
	 * @return {number} The number of ignored issues.
	 */
	countIgnored() {
		let count = 0;
		for ( const issue of this.issues ) {
			if ( issue.ignored === '1' ) {
				count++;
			}
		}
		return count;
	}

	/**
	 * This function shows the count of issues in the panel.
	 */
	showIssueCount() {
		const errorCount = this.countIssues( 'error' );
		const warningCount = this.countIssues( 'warning' );
		const ignoredCount = this.countIgnored();
		const div = document.querySelector( '.edac-highlight-panel-controls-summary' );
		const total = errorCount + warningCount;

		if ( total === 0 && ignoredCount === 0 ) {
			div.innerHTML = `<span class="edac-highlight-summary-total" role="heading" aria-level="3">${ __( 'No issues detected.', 'accessibility-checker' ) }</span>`;
			return;
		}

		// Show nav buttons since we have issues.
		this.nextButton.disabled = false;
		this.previousButton.disabled = false;

		const totalLabel = sprintf(
			// translators: %d is the number of issues found.
			_n( '%d issue found', '%d issues found', total, 'accessibility-checker' ),
			total
		);

		const problemsLabel = sprintf(
			// translators: %d is the number of errors/problems.
			_n( '%d Problem', '%d Problems', errorCount, 'accessibility-checker' ),
			errorCount
		);

		const reviewLabel = sprintf(
			// translators: %d is the number of warnings needing review.
			_n( '%d Needs Review', '%d Need Review', warningCount, 'accessibility-checker' ),
			warningCount
		);

		const breakdownParts = [ problemsLabel, reviewLabel ];
		if ( ignoredCount > 0 ) {
			breakdownParts.push( sprintf(
				// translators: %d is the number of ignored issues.
				_n( '%d Ignored', '%d Ignored', ignoredCount, 'accessibility-checker' ),
				ignoredCount
			) );
		}

		div.innerHTML = `<span class="edac-highlight-summary-total" role="heading" aria-level="3">${ totalLabel }</span><span class="edac-highlight-summary-breakdown">${ breakdownParts.join( ' · ' ) }</span>`;
	}

	/**
	 * This function highlights a landmark based on the selector.
	 * @param {string} encodedSelector Base64-encoded CSS selector for the landmark
	 */
	highlightLandmark( encodedSelector ) {
		try {
			// Decode the base64 selector
			const selector = atob( encodedSelector );

			// Find the landmark element using multiple strategies
			let landmarkElement = null;

			try {
				// Try the original selector first
				landmarkElement = document.querySelector( selector );
			} catch ( error ) {
				// Selector might be invalid, try fallbacks
			}

			// If original selector failed, try some fallback strategies
			if ( ! landmarkElement ) {
				// Try common landmark selectors as fallbacks
				const fallbackSelectors = [
					// Remove complex pseudo-selectors and try simpler versions
					selector.replace( /:nth-child\(\d+\)/g, '' ).replace( /\s+>\s+/g, ' ' ),
					// Try just the last part of the selector
					selector.split( ' > ' ).pop(),
					// Try without classes
					selector.replace( /\.[^:\s>]+/g, '' ),
				];

				for ( const fallback of fallbackSelectors ) {
					if ( fallback && fallback.trim() ) {
						try {
							landmarkElement = document.querySelector( fallback.trim() );
							if ( landmarkElement ) {
								break;
							}
						} catch ( e ) {
							// Continue to next fallback
						}
					}
				}
			}

			if ( landmarkElement ) {
				// Clean up any existing landmark labels first
				this.removeLandmarkLabels();

				// Add highlighting styles
				landmarkElement.classList.add( 'edac-highlight-element-selected' );
				landmarkElement.classList.add( 'edac-landmark-highlight' );

				// Create and add landmark type label
				const landmarkType = this.getLandmarkType( landmarkElement );
				const landmarkLabel = document.createElement( 'div' );
				landmarkLabel.classList.add( 'edac-landmark-label' );
				landmarkLabel.textContent = sprintf( __( 'Landmark: %s', 'accessibility-checker' ), landmarkType );
				landmarkLabel.setAttribute( 'aria-hidden', 'true' );
				landmarkLabel.style.cssText = `
					position: absolute;
					background: #072446;
					color: white;
					padding: 4px 8px;
					font-size: 12px;
					font-weight: bold;
					border-radius: 3px;
					z-index: 99998;
					pointer-events: none;
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
					line-height: 1;
					box-shadow: 0 2px 4px rgba(0,0,0,0.2);
				`;

				// Position the label inside the top-left corner of the landmark
				const rect = landmarkElement.getBoundingClientRect();
				landmarkLabel.style.left = ( rect.left + window.scrollX - 0 ) + 'px'; // 15px inside from left edge
				landmarkLabel.style.top = ( rect.top + window.scrollY - 0 ) + 'px'; // 15px inside from top edge

				// Add label to the page
				document.body.appendChild( landmarkLabel );

				// Store reference for cleanup
				landmarkElement.setAttribute( 'data-edac-landmark-label-id', Date.now() );
				landmarkLabel.setAttribute( 'data-edac-landmark-for', landmarkElement.getAttribute( 'data-edac-landmark-label-id' ) );

				// Adjust for small elements
				if ( landmarkElement.offsetWidth < 20 ) {
					landmarkElement.classList.add( 'edac-highlight-element-selected-min-width' );
				}

				if ( landmarkElement.offsetHeight < 5 ) {
					landmarkElement.classList.add( 'edac-highlight-element-selected-min-height' );
				}

				// Scroll to the landmark with 20px offset from start
				const elementRect = landmarkElement.getBoundingClientRect();
				const elementTop = elementRect.top + window.scrollY - 75;
				window.scrollTo( {
					top: elementTop,
					behavior: 'smooth',
				} );

			} else {
				// Landmark element not found - silently fail
			}
		} catch ( error ) {
			// Error highlighting landmark - silently fail
		}
	}

	/**
	 * Determines the landmark type of an element
	 * @param {HTMLElement} element The element to check
	 * @return {string} The landmark type (e.g., "Header", "Navigation", "Main")
	 */
	getLandmarkType( element ) {
		return getLandmarkTypeUtil( element );
	}

	/**
	 * Remove all landmark labels from the page
	 */
	removeLandmarkLabels() {
		const landmarkLabels = document.querySelectorAll( '.edac-landmark-label' );
		landmarkLabels.forEach( ( label ) => {
			label.remove();
		} );

		// Remove landmark highlight classes
		const landmarkHighlights = document.querySelectorAll( '.edac-landmark-highlight' );
		landmarkHighlights.forEach( ( element ) => {
			element.classList.remove( 'edac-landmark-highlight' );
			element.removeAttribute( 'data-edac-landmark-label-id' );
		} );
	}

	/**
	 * Kick off the accessibility scan.
	 */
	kickoffScan() {
		const getPageDensity = () => {
			const elementCount = document.body.getElementsByTagName( '*' ).length;
			const contentLength = document.body.innerText.length;
			return { elementCount, contentLength };
		};
		const densityMetrics = getPageDensity();
		const self = this;
		const scriptId = 'edac-accessibility-checker-scanner-script';

		return new Promise( ( resolve, reject ) => {
			const runScan = () => {
				self._runScanOrShowError( densityMetrics )
					.then( resolve )
					.catch( reject );
			};

			if ( ! document.getElementById( scriptId ) ) {
				const script = document.createElement( 'script' );
				script.src = window.edacFrontendHighlighterApp?.scannerBundleUrl || '/wp-content/plugins/accessibility-checker/build/pageScanner.bundle.js';
				script.id = scriptId;
				script.onload = function() {
					setTimeout( () => {
						runScan();
					}, 100 );
				};
				script.onerror = function() {
					const message = __( 'Failed to load scanner script.', 'accessibility-checker' );
					self.showWait( false );
					self.showScanError( message );
					reject( new Error( message ) );
				};
				document.head.appendChild( script );
			} else {
				runScan();
			}
		} );
	}

	_runScanOrShowError( densityMetrics ) {
		if ( window.runAccessibilityScan ) {
			return this.runAccessibilityScanAndSave( densityMetrics );
		}

		const message = __( 'Scanner function not found.', 'accessibility-checker' );
		this.showWait( false );
		this.showScanError( message );
		const error = new Error( message );
		error.edacHandled = true;
		return Promise.reject( error );
	}

	runAccessibilityScanAndSave( densityMetrics ) {
		const self = this;
		const summary = document.querySelector( '.edac-highlight-panel-controls-summary' );
		if ( summary ) {
			summary.textContent = __( 'Scanning...', 'accessibility-checker' );
			summary.classList.remove( 'edac-error' );
		}
		return window.runAccessibilityScan().then( ( result ) => {
			const postId = window.edacFrontendHighlighterApp && window.edacFrontendHighlighterApp.postID;
			const nonce = window.edacFrontendHighlighterApp && window.edacFrontendHighlighterApp.restNonce;
			if ( ! postId || ! nonce ) {
				const message = __( 'Missing postId or nonce.', 'accessibility-checker' );
				self.showWait( false );
				self.showScanError( message );
				const error = new Error( message );
				error.edacHandled = true;
				throw error;
			}
			if ( ! result || ! result.violations || result.violations.length === 0 ) {
				self.showWait( false );
				if ( self._pendingRescanAnnouncement ) {
					self.announce( __( 'Rescan complete. No violations found.', 'accessibility-checker' ) );
					self._pendingRescanAnnouncement = false;
				}
				self.showScanError( __( 'No violations found, skipping save.', 'accessibility-checker' ) );
				return { status: 'no-violations' };
			}
			return self.saveScanResults( postId, nonce, result.violations, densityMetrics );
		} ).catch( ( error ) => {
			if ( error?.edacHandled ) {
				throw error;
			}
			const message = __( 'Accessibility scan error.', 'accessibility-checker' );
			self.showWait( false );
			self.showScanError( message );
			const handledError = new Error( message );
			handledError.edacHandled = true;
			throw handledError;
		} );
	}

	saveScanResults( postId, nonce, violations, densityMetrics ) {
		const self = this;
		const restUrl = window.edacFrontendHighlighterApp?.restUrl;
		if ( ! restUrl ) {
			return Promise.reject( new Error( 'Missing REST API URL.' ) );
		}
		return fetch( `${ restUrl }/post-scan-results/${ postId }`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': nonce,
			},
			body: JSON.stringify( {
				violations,
				isSkipped: false,
				isFailure: false,
				densityMetrics,
			} ),
		} )
			.then( ( response ) => response.json() )
			.then( ( data ) => {
				self.showWait( false );
				if ( data && data.success ) {
					return { status: 'success' };
				}

				const message = __( 'Saving failed.', 'accessibility-checker' );
				self.showScanError( message );
				const error = new Error( message );
				error.edacHandled = true;
				throw error;
			} )
			.catch( ( error ) => {
				if ( error?.edacHandled ) {
					throw error;
				}
				const message = __( 'Error saving scan results.', 'accessibility-checker' );
				self.showWait( false );
				self.showScanError( message );
				const handledError = new Error( message );
				handledError.edacHandled = true;
				throw handledError;
			} );
	}

	/**
	 * Trigger a full rescan of the current page and reload issues.
	 */
	rescanPage() {
		// Prevent multiple concurrent rescans
		if ( this._isRescanning ) {
			this.announce( __( 'Rescan already in progress.', 'accessibility-checker' ) );
			return;
		}
		// Avoid panelOpen from short-circuiting into an auto-rescan after an explicit rescan.
		this._issuesCleared = false;
		this._isRescanning = true;
		this._pendingRescanAnnouncement = true;
		this.announce( __( 'Rescanning this page.', 'accessibility-checker' ) );

		this.removeHighlightButtons();
		this.kickoffScan().then( () => {
			if ( this._pendingRescanAnnouncement ) {
				this.announce( __( 'Rescan complete.', 'accessibility-checker' ) );
				this._pendingRescanAnnouncement = false;
			}
			this.panelOpen();
		} ).finally( () => {
			this._isRescanning = false;
		} );
	}

	/**
	 * Clear all saved issues for the current post.
	 */
	clearIssues() {
		// eslint-disable-next-line no-alert -- Using an alert here is the best way to inform the user of the action.
		if ( ! confirm( __( 'This will clear all issues for this post. A save will be required to trigger a fresh scan of the post content. Do you want to continue?', 'accessibility-checker' ) ) ) {
			return;
		}

		if ( ! this.clearIssuesButton ) {
			return;
		}

		// Validate required parameters
		if ( ! window.edacFrontendHighlighterApp?.restUrl || ! window.edacFrontendHighlighterApp?.postID ) {
			const summary = document.querySelector( '.edac-highlight-panel-controls-summary' );
			if ( summary ) {
				summary.textContent = __( 'Error: Missing required parameters.', 'accessibility-checker' );
				summary.classList.add( 'edac-error' );
			}
			return;
		}

		this.clearIssuesButton.disabled = true;
		this.clearIssuesButton.textContent = __( 'Clearing...', 'accessibility-checker' );
		const summary = document.querySelector( '.edac-highlight-panel-controls-summary' );

		fetch( `${ window.edacFrontendHighlighterApp.restUrl }/clear-issues/${ window.edacFrontendHighlighterApp.postID }`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': edacFrontendHighlighterApp.restNonce,
			},
			body: JSON.stringify( {
				id: edacFrontendHighlighterApp.postID,
				flush: true,
			} ),
		} ).then( ( response ) => {
			if ( response.ok ) {
				this._issuesCleared = true;
				this.removeHighlightButtons();
				this.removeSelectedClasses();
				this.issues = [];
				this.currentButtonIndex = null;

				// Clear issue text from the panel.
				const descriptionTitle = document.querySelector( '.edac-highlight-panel-description-title' );
				const descriptionContent = document.querySelector( '.edac-highlight-panel-description-content' );
				if ( descriptionTitle ) {
					descriptionTitle.innerHTML = '';
				}
				if ( descriptionContent ) {
					descriptionContent.innerHTML = '';
				}

				// Remove the URL parameter.
				const url = new URL( window.location.href );
				url.searchParams.delete( 'edac' );
				history.replaceState( null, '', url.toString() );

				// Clear the pagination count and hide nav buttons.
				const pagination = document.getElementById( 'edac-highlight-pagination' );
				if ( pagination ) {
					pagination.textContent = '';
				}
				this.nextButton.disabled = true;
				this.previousButton.disabled = true;

				this.descriptionClose();
				this.showIssueCount();
				if ( summary ) {
					summary.textContent = __( 'Issues cleared successfully.', 'accessibility-checker' );
					summary.classList.remove( 'edac-error' );
				}
			} else if ( summary ) {
				summary.textContent = __( 'Failed to clear issues.', 'accessibility-checker' );
				summary.classList.add( 'edac-error' );
			}
		} ).catch( () => {
			if ( summary ) {
				summary.textContent = __( 'An error occurred while clearing issues.', 'accessibility-checker' );
				summary.classList.add( 'edac-error' );
			}
		} ).finally( () => {
			this.clearIssuesButton.disabled = false;
			this.clearIssuesButton.textContent = __( 'Clear Issues', 'accessibility-checker' );
		} );
	}

	/**
	 * Show an error message in the scan panel or as an alert fallback.
	 * @param {string} message
	 */
	showScanError( message ) {
		const summary = document.querySelector( '.edac-highlight-panel-controls-summary' );
		if ( summary ) {
			summary.textContent = message;
			summary.classList.add( 'edac-error' );
		}

		if ( this._pendingRescanAnnouncement ) {
			this.announce( message );
			this._pendingRescanAnnouncement = false;
		}
	}
}

// Some systems (Cloudflare Rocket Loader) defers scripts for performance but that can
// cause some DOMContentLoaded events to be missed. This is flag tracks if it run so we
// can retry at a latter event listener.
let highlighterInitialized = false;
const initHighlighter = () => {
	if ( ! highlighterInitialized ) {
		new AccessibilityCheckerHighlight();
		if ( window.edacFrontendHighlighterApp?.userCanFix ) {
			fixSettingsModalInit();
		}
		highlighterInitialized = true;
	}
};

[ 'DOMContentLoaded', 'load' ].forEach( ( event ) => {
	window.addEventListener( event, initHighlighter );
} );
