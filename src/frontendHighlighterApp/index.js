/* eslint-disable padded-blocks, no-multiple-empty-lines */
/* global edacFrontendHighlighterApp */

import { computePosition, autoUpdate } from '@floating-ui/dom';
import { createFocusTrap } from 'focus-trap';
import { isFocusable } from 'tabbable';
import { __, _n, sprintf } from '@wordpress/i18n';
import { saveFixSettings } from '../common/saveFixSettingsRest';
import { fillFixesModal, fixSettingsModalInit, openFixesModal } from './fixesModal';
import { hashString } from '../common/helpers';

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

		this.highlightPanel = this.addHighlightPanel();
		this.nextButton = document.querySelector( '#edac-highlight-next' );
		this.previousButton = document.querySelector( '#edac-highlight-previous' );
		this.panelToggle = document.querySelector( '#edac-highlight-panel-toggle' );
		this.closePanel = document.querySelector( '#edac-highlight-panel-controls-close' );
		this.panelDescription = document.querySelector( '#edac-highlight-panel-description' );
		this.panelControls = document.querySelector( '#edac-highlight-panel-controls' );
		this.descriptionCloseButton = document.querySelector( '.edac-highlight-panel-description-close' );
		this.headingMapPanel = document.querySelector( '#edac-highlight-panel-heading-map' );
		this.headingMapToggle = document.querySelector( '#edac-highlight-panel-heading-map-toggle' );
		this.headingMapCloseButton = document.querySelector( '#edac-highlight-panel-heading-map-close' );
		this.headingMapSummary = document.querySelector( '.edac-highlight-panel-headings-summary' );
		this.headingMapContent = document.querySelector( '.edac-highlight-panel-headings-content' );
		this.landmarkMapSummary = document.querySelector( '.edac-highlight-panel-landmarks-summary' );
		this.landmarkMapContent = document.querySelector( '.edac-highlight-panel-landmarks-content' );
		this.headingMapTabList = document.querySelector( '.edac-highlight-panel-heading-map-tabs' );
		this.headingMapTabs = this.headingMapTabList ? Array.from( this.headingMapTabList.querySelectorAll( '.edac-highlight-panel-heading-map-tab' ) ) : [];
		this.headingMapTabPanels = this.headingMapPanel ? Array.from( this.headingMapPanel.querySelectorAll( '.edac-highlight-panel-heading-map-tabpanel' ) ) : [];
		this.issues = null;
		this.fixes = null;
		this.currentButtonIndex = null;
		this.urlParameter = this.get_url_parameter( 'edac' );
		this.landmarkParameter = this.get_url_parameter( 'edac_landmark' );
		this.currentIssueStatus = null;
		this.tooltips = [];
		this.headingMapItems = [];
		this.activeHeadingMapIndex = null;
		this.landmarkMapItems = [];
		this.activeLandmarkMapIndex = null;
		this.activeHeadingMapTab = 'headings';
		this.activeLandmarkElement = null;
		this.activeLandmarkLabel = null;
		this.panelControlsFocusTrap = createFocusTrap( '#' + this.panelControls.id, {
			clickOutsideDeactivates: true,
			escapeDeactivates: () => {
				this.panelClose();
			},
		} );
		this.panelDescriptionFocusTrap = createFocusTrap( '#' + this.panelDescription.id, {
			clickOutsideDeactivates: true,
			escapeDeactivates: () => {
				this.descriptionClose();
			},

		} );
		if ( this.headingMapPanel ) {
			this.headingMapFocusTrap = createFocusTrap( '#' + this.headingMapPanel.id, {
				clickOutsideDeactivates: true,
				escapeDeactivates: () => {
					this.closeHeadingMap();
				},
			} );
		} else {
			this.headingMapFocusTrap = null;
		}

		this.disableStylesButton = document.querySelector( '#edac-highlight-disable-styles' );
		this.rescanButton = document.querySelector( '#edac-highlight-rescan' );
		this.clearIssuesButton = document.querySelector( '#edac-highlight-clear-issues' );
		this.stylesDisabled = false;
		this.originalCss = [];

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
			this.focusTrapDescription();
		} );
		this.previousButton.addEventListener( 'click', () => {
			this.highlightFocusPrevious();
			this.focusTrapDescription();
		} );

		// Manage panel open/close operations
		this.panelToggle.addEventListener( 'click', () => {
			this.panelOpen();
			this.focusTrapControls();
		} );
		this.closePanel.addEventListener( 'click', () => {
			this.panelClose();
			this.panelControlsFocusTrap.deactivate();
			this.panelDescriptionFocusTrap.deactivate();
			this.enableStyles();
		} );

		// Close description when close button is clicked
		this.descriptionCloseButton.addEventListener( 'click', () => this.descriptionClose() );

		if ( this.headingMapToggle && this.headingMapPanel ) {
			this.headingMapToggle.addEventListener( 'click', () => {
				if ( this.headingMapPanel.classList.contains( 'edac-highlight-panel-heading-map-open' ) ) {
					this.closeHeadingMap();
				} else {
					this.openHeadingMap();
				}
			} );
		}

		if ( this.headingMapCloseButton ) {
			this.headingMapCloseButton.addEventListener( 'click', () => this.closeHeadingMap() );
		}

		if ( this.headingMapPanel ) {
			this.headingMapPanel.addEventListener( 'click', this.handleHeadingMapInteraction );
		}

		if ( this.headingMapTabs.length ) {
			this.headingMapTabs.forEach( ( tab ) => {
				tab.addEventListener( 'click', ( event ) => {
					event.preventDefault();
					const tabName = tab.dataset.tab || 'headings';
					this.switchHeadingMapTab( tabName );
					tab.focus();
				} );
				tab.addEventListener( 'keydown', this.handleHeadingMapTabKeydown );
			} );
		}

		// Handle disable/enable styles
		this.disableStylesButton.addEventListener( 'click', () => {
			if ( this.stylesDisabled ) {
				this.enableStyles();
			} else {
				this.disableStyles();
			}
		} );

		if ( this.rescanButton ) {
			this.rescanButton.addEventListener( 'click', () => {
				this.rescanPage();
			} );
		}

		if ( this.clearIssuesButton ) {
			this.clearIssuesButton.addEventListener( 'click', () => {
				this.clearIssues();
			} );
		}

		// Open panel if a URL parameter exists
		if ( this.urlParameter ) {
			this.panelOpen( this.urlParameter );
		} else if ( this.landmarkParameter ) {
			this.highlightLandmark( this.landmarkParameter );
		}
	}

	/**
	 * This function tries to find an element on the page that matches a given HTML snippet.
	 * It parses the HTML snippet, and compares the outer HTML of the parsed element
	 * with all elements present on the page. If a match is found, it
	 * adds a tooltip, checks if the element is focusable, and then returns the element.
	 * If no matching element is found, or if the parsed HTML snippet does not contain an element,
	 * it returns null.
	 *
	 * @param {Object} value - Object containing the HTML snippet to be matched.
	 * @param {number} index - Index of the element being searched.
	 * @return {HTMLElement|null} - Returns the matching HTML element, or null if no match is found.
	 */
	findElement( value, index ) {
		// Parse the HTML snippet
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
		tooltip.setAttribute( 'aria-label', `Open details for ${ value.rule_title }, ${ index + 1 } of ${ totalItems }` );
		tooltip.setAttribute( 'aria-expanded', 'false' );
		tooltip.setAttribute( 'aria-haspopup', 'dialog' );

		//add data-id to the tooltip/button so we can find it later.
		tooltip.dataset.id = value.id;

		const onClick = ( e ) => {
			const id = e.currentTarget.dataset.id;
			this.showIssue( id );
		};

		tooltip.addEventListener( 'click', onClick );

		// Add the tooltip to the page.
		document.body.append( tooltip );

		tooltip.dataset.targetElement = hashString( element.outerHTML );

		// Add creation timestamp to track order of tooltip creation
		tooltip.dataset.creationOrder = Date.now() + Math.random(); // Ensure uniqueness

		const updatePosition = function() {
			// Find existing tooltips for the same element that were created BEFORE this one
			const currentElementHash = tooltip.dataset.targetElement;
			const currentCreationOrder = parseFloat( tooltip.dataset.creationOrder );

			const existingTooltips = Array.from( document.querySelectorAll( '.edac-highlight-btn' ) ).filter( ( btn ) => {
				// Check if this tooltip targets the same element and was created before this one
				return btn !== tooltip && btn.dataset.targetElement === currentElementHash && parseFloat( btn.dataset.creationOrder ) < currentCreationOrder;
			} );

			// The offset should be the count of existing tooltips created before this one
			const tooltipOffset = existingTooltips.length;
			const TOOLTIP_GAP = 5; // Gap between tooltip buttons in pixels

			computePosition( element, tooltip, {
				placement: 'top-start',
				middleware: [],
			} ).then( ( { x, y, middlewareData, placement } ) => {
				const elRect = element.getBoundingClientRect();
				const elHeight = element.offsetHeight === undefined ? 0 : element.offsetHeight;
				const elWidth = element.offsetWidth === undefined ? 0 : element.offsetWidth;
				const tooltipHeight = tooltip.offsetHeight === undefined ? 0 : tooltip.offsetHeight;
				const tooltipWidth = tooltip.offsetWidth === undefined ? 0 : tooltip.offsetWidth;

				let top = 0;
				const left = tooltipOffset * ( tooltipWidth + TOOLTIP_GAP );

				if ( tooltipHeight <= ( elHeight * .8 ) ) {
					top = tooltipHeight;
				}

				if ( tooltipWidth >= ( elWidth * .8 ) ) {
					top = 0;
				}

				if ( elRect.left < tooltipWidth ) {
					x = 0;
				}

				if ( elRect.left > window.screen ) {
					x = window.screen.width - tooltipWidth;
				}

				if ( elRect.top < tooltipHeight ) {
					y = 0;
				}

				Object.assign( tooltip.style, {
					left: `${ x + left }px`,
					top: `${ y + top }px`,
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
		const clearButtonMarkup = userCanEdit
			? `<button id="edac-highlight-clear-issues" class="edac-highlight-clear-issues">${ __( 'Clear Issues', 'accessibility-checker' ) }</button>`
			: '';

		const rescanButton = userCanEdit
			? `<button id="edac-highlight-rescan" class="edac-highlight-rescan">${ __( 'Rescan This Page', 'accessibility-checker' ) }</button>`
			: '';

		const newElement = `
                        <div id="edac-highlight-panel" class="edac-highlight-panel edac-highlight-panel--${ widgetPosition }">
                                <button id="edac-highlight-panel-toggle" class="edac-highlight-panel-toggle" aria-haspopup="dialog" aria-label="Accessibility Checker Tools"></button>
                                <div id="edac-highlight-panel-description" class="edac-highlight-panel-description" role="dialog" aria-labelledby="edac-highlight-panel-description-title" tabindex="0">
                                <button class="edac-highlight-panel-description-close edac-highlight-panel-controls-close" aria-label="Close">×</button>
                                        <div id="edac-highlight-panel-description-title" class="edac-highlight-panel-description-title"></div>
                                        <div class="edac-highlight-panel-description-content"></div>
                                        <div id="edac-highlight-panel-description-code" class="edac-highlight-panel-description-code"><code></code></div>
                                </div>
                                <div id="edac-highlight-panel-heading-map" class="edac-highlight-panel-heading-map" role="dialog" aria-labelledby="edac-highlight-panel-heading-map-title" aria-modal="true" aria-hidden="true" tabindex="0">
                                <button id="edac-highlight-panel-heading-map-close" class="edac-highlight-panel-heading-map-close edac-highlight-panel-controls-close" aria-label="Close">×</button>
                                        <div id="edac-highlight-panel-heading-map-title" class="edac-highlight-panel-heading-map-title">${ __( 'Headings & Landmarks', 'accessibility-checker' ) }</div>
                                        <div class="edac-highlight-panel-heading-map-tabs" role="tablist" aria-label="${ __( 'Document structure views', 'accessibility-checker' ) }">
                                                <button type="button" id="edac-highlight-panel-heading-map-tab-headings" class="edac-highlight-panel-heading-map-tab is-active" role="tab" aria-selected="true" aria-controls="edac-highlight-panel-heading-map-panel-headings" data-tab="headings">${ __( 'Headings', 'accessibility-checker' ) }</button>
                                                <button type="button" id="edac-highlight-panel-heading-map-tab-landmarks" class="edac-highlight-panel-heading-map-tab" role="tab" aria-selected="false" aria-controls="edac-highlight-panel-heading-map-panel-landmarks" tabindex="-1" data-tab="landmarks">${ __( 'Landmarks', 'accessibility-checker' ) }</button>
                                        </div>
                                        <div id="edac-highlight-panel-heading-map-panel-headings" class="edac-highlight-panel-heading-map-tabpanel is-active" role="tabpanel" aria-labelledby="edac-highlight-panel-heading-map-tab-headings" data-tab="headings">
                                                <div class="edac-highlight-panel-headings-summary" aria-live="polite"></div>
                                                <div class="edac-highlight-panel-headings-content"></div>
                                        </div>
                                        <div id="edac-highlight-panel-heading-map-panel-landmarks" class="edac-highlight-panel-heading-map-tabpanel" role="tabpanel" aria-labelledby="edac-highlight-panel-heading-map-tab-landmarks" data-tab="landmarks" hidden>
                                                <div class="edac-highlight-panel-landmarks-summary" aria-live="polite"></div>
                                                <div class="edac-highlight-panel-landmarks-content"></div>
                                        </div>
                                </div>
                                <div id="edac-highlight-panel-controls" class="edac-highlight-panel-controls" tabindex="0">
                                <button id="edac-highlight-panel-controls-close" class="edac-highlight-panel-controls-close" aria-label="Close">×</button>
                                <div class="edac-highlight-panel-controls-title">Accessibility Checker</div>
                                <div class="edac-highlight-panel-controls-summary">Loading...</div>
                                <div class="edac-highlight-panel-controls-buttons ${ ! userCanEdit ? ' single_button' : '' }">
                                        <div>
                                                <button id="edac-highlight-previous" disabled="true"><span aria-hidden="true">« </span>Previous</button>
                                                <button id="edac-highlight-next" disabled="true">Next<span aria-hidden="true"> »</span></button><br />
                                        </div>
                                        <div>
                                                <button id="edac-highlight-panel-heading-map-toggle" class="edac-highlight-panel-heading-map-toggle" aria-haspopup="dialog" aria-expanded="false" aria-controls="edac-highlight-panel-heading-map">${ __( 'Headings & Landmarks', 'accessibility-checker' ) }</button>
                                                ${ rescanButton }
                                                ${ clearButtonMarkup }
                                                <button id="edac-highlight-disable-styles" class="edac-highlight-disable-styles" aria-live="polite" aria-label="${ __( 'Disable Page Styles', 'accessibility-checker' ) }">${ __( 'Disable Styles', 'accessibility-checker' ) }</button>
                                        </div>
                                </div>
                        </div>
                        </div>
                `;

		document.body.insertAdjacentHTML( 'afterbegin', newElement );
		return document.getElementById( 'edac-highlight-panel' );
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
		this.panelDescriptionFocusTrap.deactivate();
		this.panelControlsFocusTrap.activate();

		setTimeout( () => {
			this.panelControls.focus();
		}, 100 ); //give render time to complete.
	};

	/**
	 * This function sets a focus trap on the description panel
	 */
	focusTrapDescription = () => {
		this.panelControlsFocusTrap.deactivate();
		this.panelDescriptionFocusTrap.activate();

		setTimeout( () => {
			this.panelDescription.focus();
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

		const issue = this.issues.find( ( i ) => i.id === id );
		this.currentButtonIndex = this.issues.findIndex( ( i ) => i.id === id );

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
					this.currentIssueStatus = 'The element is not visible. Try disabling styles.';
					//TODO: console.log(`Element with id ${id} is not visible!`);
				} else {
					this.currentIssueStatus = null;
				}
			} else {
				this.currentIssueStatus = 'The element is not focusable. Try disabling styles.';
				//TODO: console.log(`Element with id ${id} is not focusable!`);
			}
		} else {
			this.currentIssueStatus = 'The element was not found on the page.';
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
		this.panelControls.style.display = 'block';
		this.panelToggle.style.display = 'none';

		// previous and next buttons are disabled until we have issues to show.
		this.nextButton.disabled = true;
		this.previousButton.disabled = true;

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

				this.showIssueCount();

				if ( id !== undefined ) {
					this.showIssue( id );
					this.focusTrapDescription();
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
		this.highlightPanel.classList.remove( 'edac-highlight-panel-visible' );
		this.panelControls.style.display = 'none';
		this.panelDescription.style.display = 'none';
		if ( this.headingMapPanel ) {
			this.closeHeadingMap( { restoreFocus: false } );
		}
		this.panelToggle.style.display = 'block';
		this.removeSelectedClasses();
		this.removeHighlightButtons();

		this.closePanel.removeEventListener( 'click', this.panelControlsFocusTrap.deactivate );

		this.panelToggle.focus();
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

		this.clearHeadingMapHighlights();
	};

	/**
	 * Open the headings map dialog.
	 */
	openHeadingMap = () => {
		if ( ! this.headingMapPanel || ! this.headingMapToggle ) {
			return;
		}

		this.panelControlsFocusTrap.deactivate();
		this.panelDescriptionFocusTrap.deactivate();

		if ( this.panelDescription ) {
			this.panelDescription.style.display = 'none';
		}

		this.buildHeadingMap();
		this.buildLandmarkMap();
		this.switchHeadingMapTab( this.activeHeadingMapTab || 'headings' );

		this.headingMapPanel.style.display = 'block';
		this.headingMapPanel.setAttribute( 'aria-hidden', 'false' );
		this.headingMapPanel.classList.add( 'edac-highlight-panel-heading-map-open' );
		this.headingMapToggle.setAttribute( 'aria-expanded', 'true' );
		this.headingMapPanel.scrollTop = 0;

		if ( this.headingMapFocusTrap ) {
			this.headingMapFocusTrap.activate();
		}

		setTimeout( () => {
			const activeTab = this.headingMapPanel.querySelector( '.edac-highlight-panel-heading-map-tab[aria-selected="true"]' );

			if ( activeTab ) {
				activeTab.focus();
			} else {
				this.headingMapPanel.focus();
			}
		}, 100 );
	};

	/**
	 * Close the headings map dialog.
	 *
	 * @param {Object}  [options]
	 * @param {boolean} [options.restoreFocus=true] Should focus return to the toggle button?
	 */
	closeHeadingMap = ( { restoreFocus = true } = {} ) => {
		if ( ! this.headingMapPanel || ! this.headingMapToggle ) {
			return;
		}

		this.headingMapPanel.style.display = 'none';
		this.headingMapPanel.setAttribute( 'aria-hidden', 'true' );
		this.headingMapPanel.classList.remove( 'edac-highlight-panel-heading-map-open' );
		this.headingMapToggle.setAttribute( 'aria-expanded', 'false' );

		if ( this.headingMapFocusTrap ) {
			this.headingMapFocusTrap.deactivate();
		}

		if ( this.highlightPanel && this.highlightPanel.classList.contains( 'edac-highlight-panel-visible' ) ) {
			this.panelControlsFocusTrap.activate();
		}

		if ( restoreFocus ) {
			this.headingMapToggle.focus();
		}

		this.clearHeadingMapHighlights();
		this.clearLandmarkMapHighlights();
	};

	/**
	 * Populate the headings map dialog with the headings found on the page.
	 */
	buildHeadingMap = () => {
		if ( ! this.headingMapContent || ! this.headingMapSummary ) {
			return;
		}

		this.clearHeadingMapHighlights();

		this.headingMapContent.innerHTML = '';
		this.headingMapSummary.textContent = '';

		const seenNodes = new Set();
		const headingNodes = [];
		const selector = 'h1, h2, h3, h4, h5, h6, [role="heading"]';
		const nodes = document.querySelectorAll( selector );

		nodes.forEach( ( node ) => {
			if ( seenNodes.has( node ) ) {
				return;
			}

			if ( node.closest( '#edac-highlight-panel' ) ) {
				return;
			}

			seenNodes.add( node );
			headingNodes.push( node );
		} );

		if ( headingNodes.length === 0 ) {
			const message = __( 'No headings were found on this page.', 'accessibility-checker' );
			this.headingMapItems = [];
			this.activeHeadingMapIndex = null;
			this.headingMapSummary.textContent = message;
			this.headingMapContent.textContent = message;
			return;
		}

		const counters = Array( 7 ).fill( 0 );
		let previousLevel = 0;
		let hasH1 = false;
		let h1Count = 0;
		const mapItems = [];

		headingNodes.forEach( ( node, index ) => {
			const level = this.getHeadingLevel( node );
			const text = this.getHeadingText( node );
			const numbering = this.getHeadingNumber( level, counters );
			const issues = [];

			if ( ! text ) {
				issues.push( {
					type: 'error',
					message: __( 'Heading is empty.', 'accessibility-checker' ),
				} );
			}

			if ( index === 0 && level !== 1 ) {
				issues.push( {
					type: 'warning',
					message: sprintf( __( 'Document starts with an H%1$d heading.', 'accessibility-checker' ), level ),
				} );
			}

			if ( previousLevel && level > previousLevel + 1 ) {
				issues.push( {
					type: 'warning',
					message: sprintf(
						__( 'Skipped from heading level %1$d to %2$d.', 'accessibility-checker' ),
						previousLevel,
						level
					),
				} );
			}

			if ( level === 1 ) {
				h1Count++;
				if ( hasH1 ) {
					issues.push( {
						type: 'warning',
						message: __( 'Multiple H1 headings found.', 'accessibility-checker' ),
					} );
				}
				hasH1 = true;
			}

			previousLevel = level;

			mapItems.push( {
				element: node,
				level,
				text: text || __( '(empty heading)', 'accessibility-checker' ),
				numbering,
				issues,
			} );
		} );

		this.headingMapItems = mapItems;
		this.activeHeadingMapIndex = null;

		const totalIssues = mapItems.reduce( ( total, item ) => total + item.issues.length, 0 );
		const summaryParts = [];

		summaryParts.push(
			sprintf(
				_n( '%d heading found.', '%d headings found.', mapItems.length, 'accessibility-checker' ),
				mapItems.length
			)
		);

		if ( totalIssues > 0 ) {
			summaryParts.push(
				sprintf(
					_n( '%d heading issue detected.', '%d heading issues detected.', totalIssues, 'accessibility-checker' ),
					totalIssues
				)
			);
		} else {
			summaryParts.push( __( 'No heading issues detected.', 'accessibility-checker' ) );
		}

		if ( hasH1 ) {
			summaryParts.push(
				sprintf( _n( '%d H1 heading found.', '%d H1 headings found.', h1Count, 'accessibility-checker' ), h1Count )
			);
		} else {
			summaryParts.push( __( 'No H1 heading found.', 'accessibility-checker' ) );
		}

		if ( mapItems[ 0 ] && mapItems[ 0 ].level !== 1 ) {
			summaryParts.push(
				sprintf( __( 'First heading level is H%1$d.', 'accessibility-checker' ), mapItems[ 0 ].level )
			);
		}

		this.headingMapSummary.textContent = summaryParts.join( ' ' );

		const list = document.createElement( 'ol' );
		list.className = 'edac-highlight-panel-heading-map-list';

		mapItems.forEach( ( item, index ) => {
			const listItem = document.createElement( 'li' );
			listItem.className = 'edac-highlight-panel-heading-map-item';
			listItem.style.setProperty( '--edac-heading-map-level', item.level );

			const button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'edac-highlight-panel-heading-map-item-button';
			button.dataset.headingIndex = String( index );

			const orderSpan = document.createElement( 'span' );
			orderSpan.className = 'edac-highlight-panel-heading-map-item-order';
			orderSpan.textContent = item.numbering || String( index + 1 );

			const levelSpan = document.createElement( 'span' );
			levelSpan.className = 'edac-highlight-panel-heading-map-item-level';
			levelSpan.textContent = `H${ item.level }`;

			const textSpan = document.createElement( 'span' );
			textSpan.className = 'edac-highlight-panel-heading-map-item-text';
			textSpan.textContent = item.text;

			button.append( orderSpan, levelSpan, textSpan );
			listItem.appendChild( button );

			if ( item.issues.length ) {
				const issuesList = document.createElement( 'ul' );
				issuesList.className = 'edac-highlight-panel-heading-map-item-issues';

				item.issues.forEach( ( issue ) => {
					const issueItem = document.createElement( 'li' );
					issueItem.className = `edac-highlight-panel-heading-map-item-issue edac-highlight-panel-heading-map-item-issue-${ issue.type }`;
					issueItem.textContent = issue.message;
					issuesList.appendChild( issueItem );
				} );

				listItem.appendChild( issuesList );
				listItem.classList.add( 'edac-highlight-panel-heading-map-item-has-issues' );
			}

			list.appendChild( listItem );
		} );

		this.headingMapContent.appendChild( list );
	};

	/**
	 * Remove any heading map highlights from the page.
	 */
	clearHeadingMapHighlights = () => {
		document.querySelectorAll( '.edac-heading-map-target' ).forEach( ( node ) => {
			node.classList.remove( 'edac-heading-map-target' );
		} );

		if ( this.headingMapPanel ) {
			const activeButton = this.headingMapPanel.querySelector( '.edac-highlight-panel-heading-map-item-button.is-active' );
			if ( activeButton ) {
				activeButton.classList.remove( 'is-active' );
			}
		}

		this.activeHeadingMapIndex = null;
	};

	/**
	 * Switch between the headings and landmarks tabs in the structure dialog.
	 *
	 * @param {string} tabName Identifier for the tab to activate.
	 */
	switchHeadingMapTab = ( tabName ) => {
		if ( ! this.headingMapTabs || ! this.headingMapTabs.length ) {
			return;
		}

		const normalizedTab = tabName === 'landmarks' ? 'landmarks' : 'headings';
		this.activeHeadingMapTab = normalizedTab;

		this.headingMapTabs.forEach( ( tab ) => {
			const isActive = ( tab.dataset.tab || 'headings' ) === normalizedTab;
			tab.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
			tab.classList.toggle( 'is-active', isActive );
			tab.tabIndex = isActive ? 0 : -1;
		} );

		if ( this.headingMapTabPanels && this.headingMapTabPanels.length ) {
			this.headingMapTabPanels.forEach( ( panel ) => {
				const isActive = ( panel.dataset.tab || 'headings' ) === normalizedTab;

				if ( isActive ) {
					panel.classList.add( 'is-active' );
					panel.removeAttribute( 'hidden' );
				} else {
					panel.classList.remove( 'is-active' );
					panel.setAttribute( 'hidden', '' );
				}
			} );
		}
	};

	/**
	 * Handle keyboard navigation for the headings/landmarks tablist.
	 *
	 * @param {KeyboardEvent} event Keyboard event object.
	 */
	handleHeadingMapTabKeydown = ( event ) => {
		if ( ! this.headingMapTabs || ! this.headingMapTabs.length ) {
			return;
		}

		const supportedKeys = [ 'ArrowLeft', 'ArrowRight', 'Left', 'Right', 'Home', 'End' ];

		if ( ! supportedKeys.includes( event.key ) ) {
			return;
		}

		const currentIndex = this.headingMapTabs.indexOf( event.target );

		if ( currentIndex === -1 ) {
			return;
		}

		event.preventDefault();

		let newIndex = currentIndex;

		if ( event.key === 'ArrowRight' || event.key === 'Right' ) {
			newIndex = ( currentIndex + 1 ) % this.headingMapTabs.length;
		} else if ( event.key === 'ArrowLeft' || event.key === 'Left' ) {
			newIndex = ( currentIndex - 1 + this.headingMapTabs.length ) % this.headingMapTabs.length;
		} else if ( event.key === 'Home' ) {
			newIndex = 0;
		} else if ( event.key === 'End' ) {
			newIndex = this.headingMapTabs.length - 1;
		}

		const nextTab = this.headingMapTabs[ newIndex ];

		if ( nextTab ) {
			this.switchHeadingMapTab( nextTab.dataset.tab || 'headings' );
			nextTab.focus();
		}
	};

	/**
	 * Build the landmarks list for the structure dialog.
	 */
	buildLandmarkMap = () => {
		if ( ! this.landmarkMapContent || ! this.landmarkMapSummary ) {
			return;
		}

		this.clearLandmarkMapHighlights();

		this.landmarkMapContent.innerHTML = '';
		this.landmarkMapSummary.textContent = '';

		const nodes = this.getLandmarkNodes();

		if ( nodes.length === 0 ) {
			const message = __( 'No landmarks were found on this page.', 'accessibility-checker' );
			this.landmarkMapItems = [];
			this.activeLandmarkMapIndex = null;
			this.landmarkMapSummary.textContent = message;
			this.landmarkMapContent.textContent = message;
			return;
		}

		const mapItems = [];
		let missingRequiredNames = 0;

		nodes.forEach( ( node, index ) => {
			const role = this.getLandmarkRole( node );
			const type = this.getLandmarkType( node );
			const accessibleName = this.getAccessibleName( node );
			const hasAccessibleName = accessibleName.length > 0;
			const issues = [];

			if ( this.landmarkRequiresName( role, node ) && ! hasAccessibleName ) {
				issues.push( {
					type: 'warning',
					message: __( 'Landmark is missing an accessible name.', 'accessibility-checker' ),
				} );
				missingRequiredNames++;
			}

			mapItems.push( {
				element: node,
				role,
				type,
				name: accessibleName,
				displayName: accessibleName || type,
				issues,
			} );
		} );

		this.landmarkMapItems = mapItems;
		this.activeLandmarkMapIndex = null;

		const summaryParts = [];

		summaryParts.push(
			sprintf(
				_n( '%d landmark found.', '%d landmarks found.', mapItems.length, 'accessibility-checker' ),
				mapItems.length
			)
		);

		if ( missingRequiredNames > 0 ) {
			summaryParts.push(
				sprintf(
					_n(
						'%d landmark is missing an accessible name.',
						'%d landmarks are missing accessible names.',
						missingRequiredNames,
						'accessibility-checker'
					),
					missingRequiredNames
				)
			);
		} else {
			summaryParts.push( __( 'No landmark naming issues detected.', 'accessibility-checker' ) );
		}

		this.landmarkMapSummary.textContent = summaryParts.join( ' ' );

		const list = document.createElement( 'ol' );
		list.className = 'edac-highlight-panel-landmark-list';

		mapItems.forEach( ( item, index ) => {
			const listItem = document.createElement( 'li' );
			listItem.className = 'edac-highlight-panel-landmark-item';

			const button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'edac-highlight-panel-landmark-item-button';
			button.dataset.landmarkIndex = String( index );

			const orderSpan = document.createElement( 'span' );
			orderSpan.className = 'edac-highlight-panel-landmark-item-order';
			orderSpan.textContent = String( index + 1 );

			const nameSpan = document.createElement( 'span' );
			nameSpan.className = 'edac-highlight-panel-landmark-item-name';
			nameSpan.textContent = item.displayName;

			const roleSpan = document.createElement( 'span' );
			roleSpan.className = 'edac-highlight-panel-landmark-item-role';
			roleSpan.textContent = item.role ? `[${ item.role }]` : '';

			button.append( orderSpan, nameSpan, roleSpan );
			listItem.appendChild( button );

			if ( item.issues.length ) {
				const issuesList = document.createElement( 'ul' );
				issuesList.className = 'edac-highlight-panel-landmark-item-issues';

				item.issues.forEach( ( issue ) => {
					const issueItem = document.createElement( 'li' );
					issueItem.className = `edac-highlight-panel-landmark-item-issue edac-highlight-panel-landmark-item-issue-${ issue.type }`;
					issueItem.textContent = issue.message;
					issuesList.appendChild( issueItem );
				} );

				listItem.appendChild( issuesList );
				listItem.classList.add( 'edac-highlight-panel-landmark-item-has-issues' );
			}

			list.appendChild( listItem );
		} );

		this.landmarkMapContent.appendChild( list );
	};

	/**
	 * Remove highlights created by the landmark map.
	 */
	clearLandmarkMapHighlights = () => {
		this.removeLandmarkLabels();

		if ( this.headingMapPanel ) {
			const activeButton = this.headingMapPanel.querySelector( '.edac-highlight-panel-landmark-item-button.is-active' );
			if ( activeButton ) {
				activeButton.classList.remove( 'is-active' );
			}
		}

		this.activeLandmarkMapIndex = null;
	};

	/**
	 * Highlight a landmark entry from the landmarks tab.
	 *
	 * @param {number} index Position of the landmark in the map array.
	 */
	highlightLandmarkMapItem = ( index ) => {
		if ( ! this.landmarkMapItems || ! this.landmarkMapItems.length ) {
			return;
		}

		const item = this.landmarkMapItems[ index ];

		if ( ! item ) {
			return;
		}

		if ( this.activeLandmarkMapIndex !== null ) {
			const previousButton = this.headingMapPanel?.querySelector( `[data-landmark-index="${ this.activeLandmarkMapIndex }"]` );
			if ( previousButton ) {
				previousButton.classList.remove( 'is-active' );
			}
		}

		if ( item.element ) {
			this.applyLandmarkHighlight( item.element, item.displayName );
		}

		const button = this.headingMapPanel?.querySelector( `[data-landmark-index="${ index }"]` );
		if ( button ) {
			button.classList.add( 'is-active' );
		}

		this.activeLandmarkMapIndex = index;
	};

	/**
	 * Collect landmark nodes from the document in DOM order.
	 *
	 * @return {HTMLElement[]} Array of landmark elements.
	 */
	getLandmarkNodes() {
		if ( ! document.body ) {
			return [];
		}

		const nodes = [];
		const walker = document.createTreeWalker( document.body, NodeFilter.SHOW_ELEMENT, {
			acceptNode: ( node ) => {
				if ( ! node || node.closest( '#edac-highlight-panel' ) ) {
					return NodeFilter.FILTER_SKIP;
				}

				if ( this.isLandmarkElement( node ) ) {
					return NodeFilter.FILTER_ACCEPT;
				}

				return NodeFilter.FILTER_SKIP;
			},
		} );

		while ( walker.nextNode() ) {
			const currentNode = walker.currentNode;
			if ( currentNode && ! nodes.includes( currentNode ) ) {
				nodes.push( currentNode );
			}
		}

		return nodes;
	}

	/**
	 * Determine if an element qualifies as a landmark for the map.
	 *
	 * @param {HTMLElement} element Node to evaluate.
	 * @return {boolean} Whether the element should be treated as a landmark.
	 */
	isLandmarkElement( element ) {
		if ( ! element || ! element.tagName ) {
			return false;
		}

		const explicitRole = ( element.getAttribute( 'role' ) || '' ).trim().toLowerCase().split( ' ' )[ 0 ] || '';
		const landmarkRoles = new Set( [ 'banner', 'navigation', 'main', 'complementary', 'contentinfo', 'search', 'form', 'region' ] );

		if ( explicitRole && landmarkRoles.has( explicitRole ) ) {
			return true;
		}

		const tagName = element.tagName.toLowerCase();

		switch ( tagName ) {
			case 'main':
			case 'nav':
			case 'aside':
				return true;
			case 'header':
				return ! this.isWithinSectioningContext( element );
			case 'footer':
				return ! this.isWithinSectioningContext( element );
			case 'section':
				return this.elementHasAccessibleName( element );
			case 'form':
				return explicitRole === 'search' || this.elementHasAccessibleName( element );
			default:
				return false;
		}
	}

	/**
	 * Determine if an element is inside a sectioning context where header/footer shouldn't be landmarks.
	 *
	 * @param {HTMLElement} element Node to evaluate.
	 * @return {boolean} Whether the element is within a sectioning context.
	 */
	isWithinSectioningContext( element ) {
		if ( ! element ) {
			return false;
		}

		return Boolean( element.closest( 'article, aside, main, nav, section' ) );
	}

	/**
	 * Determine if a landmark role requires an accessible name.
	 *
	 * @param {string}  role    Landmark role name.
	 * @param {Element} element Associated element.
	 * @return {boolean} Whether the landmark must expose an accessible name.
	 */
	landmarkRequiresName( role, element ) {
		const normalizedRole = ( role || this.getLandmarkRole( element ) || '' ).toLowerCase();
		const rolesRequiringNames = new Set( [ 'navigation', 'complementary', 'search', 'form', 'region' ] );

		return rolesRequiringNames.has( normalizedRole );
	}

	/**
	 * Check whether an element has an accessible name.
	 *
	 * @param {HTMLElement} element Node to evaluate.
	 * @return {boolean} Whether an accessible name is present.
	 */
	elementHasAccessibleName( element ) {
		return this.getAccessibleName( element ).length > 0;
	}

	/**
	 * Get an accessible name for the supplied element, if one exists.
	 *
	 * @param {HTMLElement} element Node to evaluate.
	 * @return {string} Accessible name text.
	 */
	getAccessibleName( element ) {
		if ( ! element ) {
			return '';
		}

		const ariaLabel = element.getAttribute( 'aria-label' );
		if ( ariaLabel ) {
			return ariaLabel.trim();
		}

		const ariaLabelledby = element.getAttribute( 'aria-labelledby' );
		if ( ariaLabelledby ) {
			const ids = ariaLabelledby.split( /\s+/ ).filter( Boolean );
			const labelText = ids
				.map( ( id ) => {
					const labelElement = document.getElementById( id );
					if ( labelElement ) {
						return labelElement.textContent.replace( /\s+/g, ' ' ).trim();
					}
					return '';
				} )
				.filter( Boolean )
				.join( ' ' );

			if ( labelText ) {
				return labelText;
			}
		}

		const title = element.getAttribute( 'title' );
		if ( title ) {
			const trimmedTitle = title.trim();
			if ( trimmedTitle ) {
				return trimmedTitle;
			}
		}

		const labelledHeading = element.querySelector( 'h1, h2, h3, h4, h5, h6' );
		if ( labelledHeading ) {
			const headingText = this.getHeadingText( labelledHeading );
			if ( headingText ) {
				return headingText;
			}
		}

		if ( element.tagName && element.tagName.toLowerCase() === 'form' ) {
			const legend = element.querySelector( 'legend' );
			if ( legend ) {
				const legendText = legend.textContent.replace( /\s+/g, ' ' ).trim();
				if ( legendText ) {
					return legendText;
				}
			}
		}

		return '';
	}

	/**
	 * Resolve the landmark role for a given element, considering implicit semantics.
	 *
	 * @param {HTMLElement} element Node to evaluate.
	 * @return {string} Landmark role, if one applies.
	 */
	getLandmarkRole( element ) {
		if ( ! element || ! element.tagName ) {
			return '';
		}

		const explicitRole = ( element.getAttribute( 'role' ) || '' ).trim().toLowerCase().split( ' ' )[ 0 ] || '';
		if ( explicitRole ) {
			return explicitRole;
		}

		const tagName = element.tagName.toLowerCase();

		switch ( tagName ) {
			case 'header':
				return this.isWithinSectioningContext( element ) ? '' : 'banner';
			case 'nav':
				return 'navigation';
			case 'main':
				return 'main';
			case 'aside':
				return 'complementary';
			case 'footer':
				return this.isWithinSectioningContext( element ) ? '' : 'contentinfo';
			case 'section':
				return this.elementHasAccessibleName( element ) ? 'region' : '';
			case 'form':
				return this.elementHasAccessibleName( element ) ? 'form' : '';
			default:
				return '';
		}
	}

	/**
	 * Apply highlight styling and labelling to a landmark element.
	 *
	 * @param {HTMLElement} element   Landmark element to highlight.
	 * @param {string}      labelText Accessible name or type to show in the badge.
	 */
	applyLandmarkHighlight( element, labelText ) {
		if ( ! element ) {
			return;
		}

		const label = labelText || this.getLandmarkType( element );

		this.removeLandmarkLabels();

		element.classList.add( 'edac-highlight-element-selected', 'edac-landmark-highlight' );

		if ( element.offsetWidth < 20 ) {
			element.classList.add( 'edac-highlight-element-selected-min-width' );
		}

		if ( element.offsetHeight < 5 ) {
			element.classList.add( 'edac-highlight-element-selected-min-height' );
		}

		const landmarkLabel = document.createElement( 'div' );
		landmarkLabel.className = 'edac-landmark-label';
		landmarkLabel.textContent = sprintf( __( 'Landmark: %s', 'accessibility-checker' ), label );
		landmarkLabel.setAttribute( 'aria-hidden', 'true' );

		document.body.appendChild( landmarkLabel );
		this.positionLandmarkLabel( element, landmarkLabel );

		this.activeLandmarkElement = element;
		this.activeLandmarkLabel = landmarkLabel;

		const elementRect = element.getBoundingClientRect();
		const elementTop = Math.max( elementRect.top + window.scrollY - 75, 0 );
		window.scrollTo( {
			top: elementTop,
			behavior: 'smooth',
		} );
	}

	/**
	 * Position the floating landmark label relative to the highlighted element.
	 *
	 * @param {HTMLElement} element Landmark element.
	 * @param {HTMLElement} label   Label element to position.
	 */
	positionLandmarkLabel( element, label ) {
		if ( ! element || ! label ) {
			return;
		}

		const rect = element.getBoundingClientRect();
		label.style.left = `${ Math.max( rect.left + window.scrollX, 0 ) }px`;
		label.style.top = `${ Math.max( rect.top + window.scrollY, 0 ) }px`;
	}

	/**
	 * Highlight the heading associated with the given index.
	 *
	 * @param {number} index The index of the heading item in the map.
	 */
	highlightHeadingMapItem = ( index ) => {
		if ( ! this.headingMapItems || ! this.headingMapItems.length ) {
			return;
		}

		const item = this.headingMapItems[ index ];

		if ( ! item ) {
			return;
		}

		if ( this.activeHeadingMapIndex !== null && this.headingMapItems[ this.activeHeadingMapIndex ] ) {
			const previousItem = this.headingMapItems[ this.activeHeadingMapIndex ];
			if ( previousItem.element ) {
				previousItem.element.classList.remove( 'edac-heading-map-target' );
			}

			if ( this.headingMapPanel ) {
				const previousButton = this.headingMapPanel.querySelector( `[data-heading-index="${ this.activeHeadingMapIndex }"]` );
				if ( previousButton ) {
					previousButton.classList.remove( 'is-active' );
				}
			}
		}

		if ( item.element ) {
			item.element.classList.add( 'edac-heading-map-target' );
			item.element.scrollIntoView( { behavior: 'smooth', block: 'center' } );
		}

		if ( this.headingMapPanel ) {
			const button = this.headingMapPanel.querySelector( `[data-heading-index="${ index }"]` );
			if ( button ) {
				button.classList.add( 'is-active' );
			}
		}

		this.activeHeadingMapIndex = index;
	};

	/**
	 * Handle click events inside the headings map.
	 *
	 * @param {MouseEvent} event The DOM event.
	 */
	handleHeadingMapInteraction = ( event ) => {
		const headingButton = event.target.closest( '.edac-highlight-panel-heading-map-item-button' );
		if ( headingButton ) {
			event.preventDefault();

			const index = parseInt( headingButton.dataset.headingIndex, 10 );

			if ( Number.isNaN( index ) ) {
				return;
			}

			this.highlightHeadingMapItem( index );
			return;
		}

		const landmarkButton = event.target.closest( '.edac-highlight-panel-landmark-item-button' );

		if ( ! landmarkButton ) {
			return;
		}

		event.preventDefault();

		const landmarkIndex = parseInt( landmarkButton.dataset.landmarkIndex, 10 );

		if ( Number.isNaN( landmarkIndex ) ) {
			return;
		}

		this.highlightLandmarkMapItem( landmarkIndex );
	};

	/**
	 * Determine the heading level for a given element.
	 *
	 * @param {Element} element Heading element from the DOM.
	 * @return {number} Normalized heading level between 1 and 6.
	 */
	getHeadingLevel( element ) {
		if ( ! element || ! element.tagName ) {
			return 6;
		}

		const tagName = element.tagName.toLowerCase();

		if ( /^h[1-6]$/.test( tagName ) ) {
			return parseInt( tagName.replace( 'h', '' ), 10 );
		}

		const role = element.getAttribute( 'role' );

		if ( role && role.toLowerCase() === 'heading' ) {
			const ariaLevel = parseInt( element.getAttribute( 'aria-level' ), 10 );

			if ( ! Number.isNaN( ariaLevel ) ) {
				return Math.min( 6, Math.max( 1, ariaLevel ) );
			}

			return 2;
		}

		return 6;
	}

	/**
	 * Extract trimmed text from a heading element.
	 *
	 * @param {Element} element Heading element from the DOM.
	 * @return {string} Trimmed heading text.
	 */
	getHeadingText( element ) {
		if ( ! element ) {
			return '';
		}

		const text = element.textContent || '';

		return text.replace( /\s+/g, ' ' ).trim();
	}

	/**
	 * Build a hierarchical number for a heading.
	 *
	 * @param {number} level    Heading level.
	 * @param {Array}  counters Counters for each heading depth.
	 * @return {string} The hierarchical number.
	 */
	getHeadingNumber( level, counters ) {
		if ( ! Array.isArray( counters ) ) {
			return '';
		}

		counters[ level ] = ( counters[ level ] || 0 ) + 1;

		for ( let i = level + 1; i < counters.length; i++ ) {
			counters[ i ] = 0;
		}

		const parts = [];

		for ( let i = 1; i <= level; i++ ) {
			if ( counters[ i ] && counters[ i ] > 0 ) {
				parts.push( counters[ i ] );
			}
		}

		return parts.join( '.' );
	}

	/**
	 * This function displays the description of the issue.
	 *
	 * @param {string} dataId
	 */
	descriptionOpen( dataId ) {
		// get the value of the property by key
		const searchTerm = dataId;
		const keyToSearch = 'id';
		const matchingObj = this.issues.find( ( obj ) => obj[ keyToSearch ] === searchTerm );

		if ( matchingObj ) {
			const descriptionTitle = document.querySelector( '.edac-highlight-panel-description-title' );
			const descriptionContent = document.querySelector( '.edac-highlight-panel-description-content' );
			const descriptionCode = document.querySelector( '.edac-highlight-panel-description-code code' );

			let content = '';

			// Get the index and total
			content += ` <div class="edac-highlight-panel-description-index">${ this.currentButtonIndex + 1 } of ${ this.issues.length }</div>`;

			// Get the status of the issue
			if ( this.currentIssueStatus ) {
				content += ` <div class="edac-highlight-panel-description-status">${ this.currentIssueStatus }</div>`;
			}

			// Get the summary of the issue
			if ( matchingObj.summary ) {
				content += `<p class="edac-highlight-panel-description-summary">${ matchingObj.summary }</p>`;
			}

			// Get the how to fix information
			if ( matchingObj.how_to_fix ) {
				content += `<div class="edac-highlight-panel-description-how-to-fix">
					<div class="edac-highlight-panel-description-how-to-fix-title">How to fix it:</div>
					<p class="edac-highlight-panel-description-how-to-fix-content">${ matchingObj.how_to_fix }</p>
				</div>`;
			}

			if ( this.fixes[ matchingObj.slug ] && window.edacFrontendHighlighterApp?.userCanFix ) {
				// this is the markup to put in the modal.
				content += `
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
				`;
				// and the button that will trigger the modal.
				content += ` <br />
 					<button role="button"
 						class="edac-fix-settings--button--open edac-highlight-panel-description--button"
 						aria-haspopup="true"
 						aria-controls="edac-highlight-panel-description-fix"
						aria-label="Fix issue: ${ this.fixes[ matchingObj.slug ][ Object.keys( this.fixes[ matchingObj.slug ] )[ 0 ] ].group_name }"> 						Fix Issue</button>`;
			} else {
				content += ` <br />`;
			}

			// Get the link to the documentation
			content += `<a class="edac-highlight-panel-description-reference" href="${ matchingObj.link }">Full Documentation</a>`;

			// Get the code button
			content += `<button class="edac-highlight-panel-description-code-button" aria-expanded="false" aria-controls="edac-highlight-panel-description-code">${ __( 'Show Code', 'accessibility-checker' ) }</button>`;

			// title and content
			descriptionTitle.innerHTML = matchingObj.rule_title + ' <span class="edac-highlight-panel-description-type edac-highlight-panel-description-type-' + matchingObj.rule_type + '" aria-label="' + __( 'Issue type:', 'accessibility-checker' ) + ' ' + matchingObj.rule_type + '"> ' + matchingObj.rule_type + '</span>';

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

			// show fix settings button if available
			if ( this.fixes[ matchingObj.slug ] && window.edacFrontendHighlighterApp?.userCanFix ) {
				this.fixSettingsButton = document.querySelector( '.edac-fix-settings--button--open' );
				this.fixSettingsButton.addEventListener( 'click', ( event ) => {
					this.showFixSettings( event );
				} );
				this.fixSettingsButton.display = 'block';

				this.fixSettingsSaveButton = document.querySelector( '.edac-fix-settings--button--save' );
				this.fixSettingsSaveButton.addEventListener( 'click', ( event ) => {
					saveFixSettings( event.target.closest( '.edac-fix-settings' ) );
				} );
			}

			// set code button listener
			this.codeContainer = document.querySelector( '.edac-highlight-panel-description-code' );
			this.codeButton = document.querySelector( '.edac-highlight-panel-description-code-button' );
			this.codeButton.addEventListener( 'click', () => this.codeToggle() );

			// close the code container each time the description is opened
			this.codeContainer.style.display = 'none';

			// show the description
			this.panelDescription.style.display = 'block';
		}
	}

	/**
	 * This function closes the description.
	 */
	descriptionClose() {
		this.panelDescription.style.display = 'none';
		this.focusTrapControls();
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
			//console.log( 'css is combined, so adding app.css to page.' );

			const link = document.createElement( 'link' );
			link.rel = 'stylesheet';
			link.id = 'edac-app-css';
			link.type = 'text/css';
			link.href = edacFrontendHighlighterApp.appCssUrl;
			link.media = 'all';
			document.head.appendChild( link );
		}

		this.originalCss = Array.from( document.head.querySelectorAll( 'style[type="text/css"], style, link[rel="stylesheet"]' ) );

		const elementsWithStyle = document.querySelectorAll( '*[style]:not([class^="edac"])' );
		elementsWithStyle.forEach( function( element ) {
			element.removeAttribute( 'style' );
		} );

		this.originalCss = this.originalCss.filter( function( element ) {
			if ( element.id === 'edac-app-css' || element.id === 'dashicons-css' ) {
				return false;
			}
			return true;
		} );

		document.head.dataset.css = this.originalCss;
		this.originalCss.forEach( function( element ) {
			element.remove();
		} );

		document.querySelector( 'body' ).classList.add( 'edac-app-disable-styles' );

		this.stylesDisabled = true;
		this.disableStylesButton.textContent = __( 'Enable Styles', 'accessibility-checker' );
	}

	/**
	 * This function enables all styles on the page.
	 */
	enableStyles() {
		this.originalCss.forEach( function( element ) {
			if ( element.tagName === 'STYLE' ) {
				document.head.appendChild( element.cloneNode( true ) );
			} else {
				const newElement = document.createElement( 'link' );
				newElement.rel = 'stylesheet';
				newElement.href = element.href;
				document.head.appendChild( newElement );
			}
		} );

		document.querySelector( 'body' ).classList.remove( 'edac-app-disable-styles' );

		this.stylesDisabled = false;
		this.disableStylesButton.textContent = __( 'Disable Styles', 'accessibility-checker' );
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
		if ( this.codeContainer.style.display === 'none' || this.codeContainer.style.display === '' ) {
			this.codeContainer.style.display = 'block';
			this.codeButton.setAttribute( 'aria-expanded', 'true' );
		} else {
			this.codeContainer.style.display = 'none';
			this.codeButton.setAttribute( 'aria-expanded', 'false' );
		}
	}

	showFixSettings( event ) {
		const fixSettingsContainer = event.target.closest( '.edac-highlight-panel-description-content' ).querySelector( '.edac-fix-settings' );
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
		this.panelDescriptionFocusTrap.pause();
		openFixesModal( event.target );

		// unpause the focus trap when the modal is closed.
		document.addEventListener( 'edac-fixes-modal-closed', () => {
			this.panelDescriptionFocusTrap.unpause();
		} );
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
			if ( issue.ignored === 1 ) {
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

		let textContent = __( 'No issues detected.', 'accessibility-checker' );
		if ( errorCount > 0 || warningCount > 0 || ignoredCount > 0 ) {
			textContent = '';
			// show buttons since we have issues.
			this.nextButton.disabled = false;
			this.previousButton.disabled = false;

			if ( errorCount >= 0 ) {
				textContent += errorCount + ' ' + _n( 'error', 'errors', errorCount, 'accessibility-checker' ) + ', ';
			}
			if ( warningCount >= 0 ) {
				textContent += warningCount + ' ' + _n( 'warning', 'warnings', warningCount, 'accessibility-checker' ) + ', ';
			}
			if ( ignoredCount >= 0 ) {
				textContent += __( 'and', 'accessibility-checker' ) + ' ' + ignoredCount + ' ' + _n( 'ignored issue', 'ignored issues', ignoredCount, 'accessibility-checker' ) + ' ' + __( 'detected.', 'accessibility-checker' );
			} else {
				// Remove the trailing comma and add "detected."
				textContent = textContent.slice( 0, -2 ) + ' ' + __( 'detected.', 'accessibility-checker' );
			}
		}

		div.textContent = textContent;
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
				const label = this.getAccessibleName( landmarkElement ) || this.getLandmarkType( landmarkElement );
				this.applyLandmarkHighlight( landmarkElement, label );
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
		// Check explicit ARIA role first
		const role = element.getAttribute( 'role' );
		if ( role ) {
			switch ( role.toLowerCase() ) {
				case 'banner':
					return 'Header';
				case 'navigation':
					return 'Navigation';
				case 'main':
					return 'Main';
				case 'complementary':
					return 'Complementary';
				case 'contentinfo':
					return 'Footer';
				case 'search':
					return 'Search';
				case 'form':
					return 'Form';
				case 'region':
					return 'Region';
				default:
					return role.charAt( 0 ).toUpperCase() + role.slice( 1 );
			}
		}

		// Check semantic HTML elements
		const tagName = element.tagName.toLowerCase();
		switch ( tagName ) {
			case 'header':
				return 'Header';
			case 'nav':
				return 'Navigation';
			case 'main':
				return 'Main';
			case 'aside':
				return 'Complementary';
			case 'footer':
				return 'Footer';
			case 'section': {
				// Check if section has accessible name
				const hasAccessibleName = element.getAttribute( 'aria-label' ) ||
					element.getAttribute( 'aria-labelledby' ) ||
					element.querySelector( 'h1, h2, h3, h4, h5, h6' );
				return hasAccessibleName ? 'Region' : 'Section';
			}
			case 'form': {
				// Check if form has accessible name
				const formHasAccessibleName = element.getAttribute( 'aria-label' ) ||
					element.getAttribute( 'aria-labelledby' );
				return formHasAccessibleName ? 'Form' : 'Form (unlabeled)';
			}
			default:
				return 'Landmark';
		}
	}

	/**
	 * Remove all landmark labels from the page
	 */
	removeLandmarkLabels() {
		if ( this.activeLandmarkLabel ) {
			this.activeLandmarkLabel.remove();
			this.activeLandmarkLabel = null;
		}

		if ( this.activeLandmarkElement ) {
			this.activeLandmarkElement.classList.remove(
				'edac-landmark-highlight',
				'edac-highlight-element-selected',
				'edac-highlight-element-selected-min-width',
				'edac-highlight-element-selected-min-height'
			);
			this.activeLandmarkElement = null;
		}

		document.querySelectorAll( '.edac-landmark-label' ).forEach( ( label ) => {
			label.remove();
		} );

		document.querySelectorAll( '.edac-landmark-highlight' ).forEach( ( element ) => {
			element.classList.remove(
				'edac-landmark-highlight',
				'edac-highlight-element-selected',
				'edac-highlight-element-selected-min-width',
				'edac-highlight-element-selected-min-height'
			);
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
		if ( ! document.getElementById( scriptId ) ) {
			const script = document.createElement( 'script' );
			script.src = window.edacFrontendHighlighterApp?.scannerBundleUrl || '/wp-content/plugins/accessibility-checker/build/pageScanner.bundle.js';
			script.id = scriptId;
			script.onload = function() {
				setTimeout( () => {
					self._runScanOrShowError( densityMetrics );
				}, 100 );
			};
			script.onerror = function() {
				self.showWait( false );
				self.showScanError( 'Failed to load scanner script.' );
			};
			document.head.appendChild( script );
		} else {
			self._runScanOrShowError( densityMetrics );
		}
	}

	_runScanOrShowError( densityMetrics ) {
		if ( window.runAccessibilityScan ) {
			this.runAccessibilityScanAndSave( densityMetrics );
		} else {
			this.showWait( false );
			this.showScanError( __( 'Scanner function not found.', 'accessibility-checker' ) );
		}
	}

	runAccessibilityScanAndSave( densityMetrics ) {
		const self = this;
		const summary = document.querySelector( '.edac-highlight-panel-controls-summary' );
		if ( summary ) {
			summary.textContent = __( 'Scanning...', 'accessibility-checker' );
			summary.classList.remove( 'edac-error' );
		}
		window.runAccessibilityScan().then( ( result ) => {
			const postId = window.edacFrontendHighlighterApp && window.edacFrontendHighlighterApp.postID;
			const nonce = window.edacFrontendHighlighterApp && window.edacFrontendHighlighterApp.restNonce;
			if ( ! postId || ! nonce ) {
				self.showWait( false );
				self.showScanError( __( 'Missing postId or nonce.', 'accessibility-checker' ) );
				return;
			}
			if ( ! result || ! result.violations || result.violations.length === 0 ) {
				self.showWait( false );
				self.showScanError( __( 'No violations found, skipping save.', 'accessibility-checker' ) );
				return;
			}
			self.saveScanResults( postId, nonce, result.violations, densityMetrics );
		} ).catch( () => {
			self.showWait( false );
			self.showScanError( __( 'Accessibility scan error.', 'accessibility-checker' ) );
		} );
	}

	saveScanResults( postId, nonce, violations, densityMetrics ) {
		const self = this;
		fetch( '/wp-json/accessibility-checker/v1/post-scan-results/' + postId, {
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
					// Optionally show a success message or update UI
				} else {
					self.showScanError( __( 'Saving failed.', 'accessibility-checker' ) );
				}
			} )
			.catch( () => {
				self.showWait( false );
				self.showScanError( __( 'Error saving scan results.', 'accessibility-checker' ) );
			} );
	}

	/**
	 * Trigger a full rescan of the current page and reload issues.
	 */
	rescanPage() {
		// Prevent multiple concurrent rescans
		if ( this._isRescanning ) {
			return;
		}
		this._isRescanning = true;

		this.removeHighlightButtons();
		this.kickoffScan();
		setTimeout( () => {
			this._isRescanning = false;
			this.panelOpen();
		}, 5000 );
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
		if ( ! edacFrontendHighlighterApp?.edacUrl || ! edacFrontendHighlighterApp?.postID ) {
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

		fetch( `${ edacFrontendHighlighterApp.edacUrl }/wp-json/accessibility-checker/v1/clear-issues/${ edacFrontendHighlighterApp.postID }`, {
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
				this.removeHighlightButtons();
				this.issues = [];
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
