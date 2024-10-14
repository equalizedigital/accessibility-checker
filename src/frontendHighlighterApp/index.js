/* eslint-disable padded-blocks, no-multiple-empty-lines */
/* global edacFrontendHighlighterApp */

import { computePosition, autoUpdate } from '@floating-ui/dom';
import { createFocusTrap } from 'focus-trap';
import { isFocusable } from 'tabbable';
import { __ } from '@wordpress/i18n';
import { saveFixSettings } from '../common/saveFixSettingsRest';
import { fillFixesModal, fixSettingsModalInit, openFixesModal } from './fixesModal';

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

		this.highlightPanel = this.addHighlightPanel();
		this.nextButton = document.querySelector( '#edac-highlight-next' );
		this.previousButton = document.querySelector( '#edac-highlight-previous' );
		this.panelToggle = document.querySelector( '#edac-highlight-panel-toggle' );
		this.closePanel = document.querySelector( '#edac-highlight-panel-controls-close' );
		this.panelDescription = document.querySelector( '#edac-highlight-panel-description' );
		this.panelControls = document.querySelector( '#edac-highlight-panel-controls' );
		this.descriptionCloseButton = document.querySelector( '.edac-highlight-panel-description-close' );
		this.issues = null;
		this.fixes = null;
		this.currentButtonIndex = null;
		this.urlParameter = this.get_url_parameter( 'edac' );
		this.currentIssueStatus = null;
		this.tooltips = [];
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

		this.disableStylesButton = document.querySelector( '#edac-highlight-disable-styles' );
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

		// Handle disable/enable styles
		this.disableStylesButton.addEventListener( 'click', () => {
			if ( this.stylesDisabled ) {
				this.enableStyles();
			} else {
				this.disableStyles();
			}
		} );

		// Open panel if a URL parameter exists
		if ( this.urlParameter ) {
			this.panelOpen( this.urlParameter );
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
					} else {
						resolve( [] );
						//console.log(response);
					}
				} else {
					self.showWait( false );

					//console.log( 'Request failed.  Returned status of ' + xhr.status );

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

		const updatePosition = function() {
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
				const left = 0;

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
		const widgetPosition = edacFrontendHighlighterApp.widgetPosition || 'right';

		const newElement = `
			<div id="edac-highlight-panel" class="edac-highlight-panel edac-highlight-panel--${ widgetPosition }">
			<button id="edac-highlight-panel-toggle" class="edac-highlight-panel-toggle" aria-haspopup="dialog" aria-label="Accessibility Checker Tools"></button>
			<div id="edac-highlight-panel-description" class="edac-highlight-panel-description" role="dialog" aria-labelledby="edac-highlight-panel-description-title" tabindex="0">
			<button class="edac-highlight-panel-description-close edac-highlight-panel-controls-close" aria-label="Close">×</button>
				<div id="edac-highlight-panel-description-title" class="edac-highlight-panel-description-title"></div>
				<div class="edac-highlight-panel-description-content"></div>
				<div id="edac-highlight-panel-description-code" class="edac-highlight-panel-description-code"><code></code></div>
			</div>
			<div id="edac-highlight-panel-controls" class="edac-highlight-panel-controls" tabindex="0">
				<button id="edac-highlight-panel-controls-close" class="edac-highlight-panel-controls-close" aria-label="Close">×</button>
				<div class="edac-highlight-panel-controls-title">Accessibility Checker</div>
				<div class="edac-highlight-panel-controls-summary">Loading...</div>
				<div class="edac-highlight-panel-controls-buttons">
					<div>
						<button id="edac-highlight-previous" disabled="true"><span aria-hidden="true">« </span>Previous</button>
						<button id="edac-highlight-next" disabled="true">Next<span aria-hidden="true"> »</span></button><br />
					</div>
					<div>
						<button id="edac-highlight-disable-styles" class="edac-highlight-disable-styles" aria-live="polite" aria-label="${ __( 'Disable Page Styles', 'accessibility-checker' ) }">${ __( 'Disable Styles', 'text-domain' ) }</button>
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
			//TODO:
		} );
	}

	/**
	 * This function closes the accessibility checker panel.
	 */
	panelClose() {
		this.highlightPanel.classList.remove( 'edac-highlight-panel-visible' );
		this.panelControls.style.display = 'none';
		this.panelDescription.style.display = 'none';
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
			content += matchingObj.summary;

			if ( this.fixes[ matchingObj.slug ] ) {
				// this is the markup to put in the modal.
				content += `
					<div style="display:none;">
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
				content += `
					<div class="edac-fix-settings--action-open">
						<button role="button" class="edac-fix-settings--button--open edac-highlight-panel-description--button" aria-expanded="false" aria-controls="edac-highlight-panel-description-fix">Fix Issue</button>
					</div>
					`;
			}

			// Get the link to the documentation
			content += ` <br /><a class="edac-highlight-panel-description-reference" href="${ matchingObj.link }">Full Documentation</a>`;

			// Get the code button
			content += `<button class="edac-highlight-panel-description-code-button" aria-expanded="false" aria-controls="edac-highlight-panel-description-code">Show Code</button>`;

			// title and content
			descriptionTitle.innerHTML = matchingObj.rule_title + ' <span class="edac-highlight-panel-description-type edac-highlight-panel-description-type-' + matchingObj.rule_type + '" aria-label=" Issue type: ' + matchingObj.rule_type + '"> ' + matchingObj.rule_type + '</span>';

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
			if ( this.fixes[ matchingObj.slug ] ) {
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
		this.disableStylesButton.textContent = 'Enable Styles';
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
		this.disableStylesButton.textContent = 'Disable Styles';
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

		fixSettingsContainer.classList.add( 'edac-fix-settings--open' );
		fillFixesModal( '', fixSettingsContainer.innerHTML );

		// puse the focus trap.
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

		let textContent = 'No issues detected.';
		if ( errorCount > 0 || warningCount > 0 || ignoredCount > 0 ) {
			textContent = '';
			// show buttons since we have issues.
			this.nextButton.disabled = false;
			this.previousButton.disabled = false;

			if ( errorCount >= 0 ) {
				textContent += errorCount + ' error' + ( errorCount === 1 ? '' : 's' ) + ', ';
			}
			if ( warningCount >= 0 ) {
				textContent += warningCount + ' warning' + ( warningCount === 1 ? '' : 's' ) + ', ';
			}
			if ( ignoredCount >= 0 ) {
				textContent += 'and ' + ignoredCount + ' ignored issue' + ( ignoredCount === 1 ? '' : 's' ) + ' detected.';
			} else {
				// Remove the trailing comma and add "detected."
				textContent = textContent.slice( 0, -2 ) + ' detected.';
			}
		}

		div.textContent = textContent;
	}
}

window.addEventListener( 'DOMContentLoaded', () => {
	new AccessibilityCheckerHighlight();
	fixSettingsModalInit();
} );
