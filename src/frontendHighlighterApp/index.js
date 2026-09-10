/* eslint-disable padded-blocks, no-multiple-empty-lines */
/* global edacFrontendHighlighterApp */

import { computePosition, autoUpdate } from '@floating-ui/dom';
import { isFocusable } from 'tabbable';
import { __, _n, sprintf } from '@wordpress/i18n';
import { getLandmarkType as getLandmarkTypeUtil } from './getLandmarkType';
import './components/HighlightTooltipButton.js';
import './components/HighlightTrigger.js';
import './components/HighlightPanel.js';
import './components/HighlightLandmarkLabel.js';
import './components/HighlightFixesModal.js';

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

		this.issues = null;
		this.fixes = null;
		this.currentButtonIndex = null;
		this.urlParameter = this.get_url_parameter( 'edac' );
		this.landmarkParameter = this.get_url_parameter( 'edac_landmark' );
		this.currentIssueStatus = null;
		this.tooltips = [];
		this._landmarkLabel = null;
		this.isDocked = localStorage.getItem( 'edac-panel-docked' ) === '1';
		this.stylesDisabled = false;
		this.originalCss = [];
		this.originalInlineStyles = [];

		this._i18n = this._buildI18n();

		this.trigger = this._createTrigger();
		this.panel = this._createPanel();
		this.fixesModal = this._createFixesModal();

		this._bindEvents();
		this._restoreDockedState();
		this._openFromUrlOrDock();
	}

	/**
	 * Translated strings handed down to the shadow-DOM components, which
	 * have no @wordpress/i18n dependency of their own.
	 */
	_buildI18n() {
		return {
			panelTitle: __( 'Accessibility Checker', 'accessibility-checker' ),
			wcagLabel: __( 'WCAG:', 'accessibility-checker' ),
			severityLabel: __( 'Severity:', 'accessibility-checker' ),
			severity_critical: __( 'Critical', 'accessibility-checker' ),
			severity_high: __( 'High', 'accessibility-checker' ),
			severity_medium: __( 'Medium', 'accessibility-checker' ),
			severity_low: __( 'Low', 'accessibility-checker' ),
			type_error: __( 'Problem', 'accessibility-checker' ),
			type_warning: __( 'Needs Review', 'accessibility-checker' ),
			type_ignored: __( 'Dismissed', 'accessibility-checker' ),
			opensNewWindow: __( ', opens a new window', 'accessibility-checker' ),
			showExplanation: __( 'Show explanation', 'accessibility-checker' ),
			whyItMatters: __( 'Why It Matters', 'accessibility-checker' ),
			howToFix: __( 'How to Fix', 'accessibility-checker' ),
			moreDocs: __( 'More Detailed Documentation', 'accessibility-checker' ),
			showAffectedCode: __( 'Show Affected Code', 'accessibility-checker' ),
			save: __( 'Save', 'accessibility-checker' ),
			fixIssue: __( 'Fix Issue', 'accessibility-checker' ),
			loading: __( 'Loading...', 'accessibility-checker' ),
			noIssues: __( 'No issues detected.', 'accessibility-checker' ),
			noIssuesOnPage: __( 'No issues found on this page.', 'accessibility-checker' ),
			previous: __( 'Previous', 'accessibility-checker' ),
			next: __( 'Next', 'accessibility-checker' ),
			moveToLeft: __( 'Move to Left', 'accessibility-checker' ),
			moveToRight: __( 'Move to Right', 'accessibility-checker' ),
			resetPosition: __( 'Reset Position', 'accessibility-checker' ),
			dockPanel: __( 'Dock Panel', 'accessibility-checker' ),
			undockPanel: __( 'Undock Panel', 'accessibility-checker' ),
			disableStyles: __( 'Disable Styles', 'accessibility-checker' ),
			enableStyles: __( 'Enable Styles', 'accessibility-checker' ),
			disablePageStyles: __( 'Disable Page Styles', 'accessibility-checker' ),
			enablePageStyles: __( 'Enable Page Styles', 'accessibility-checker' ),
			rescanPage: __( 'Rescan This Page', 'accessibility-checker' ),
			clearIssues: __( 'Clear Issues', 'accessibility-checker' ),
			positionReset: __( 'Panel position reset.', 'accessibility-checker' ),
			movedLeft: __( 'Panel moved to the left.', 'accessibility-checker' ),
			movedRight: __( 'Panel moved to the right.', 'accessibility-checker' ),
		};
	}

	/**
	 * Locale-correct pluralization for the panel's summary counts, using
	 * WP's real gettext plural rules rather than naive string substitution.
	 *
	 * @param {number} count
	 * @param {string} key   One of 'issueFound' | 'problem' | 'needsReview' | 'dismissed'.
	 */
	_pluralize = ( count, key ) => {
		const forms = {
			issueFound: [ '%d issue found', '%d issues found' ],
			problem: [ '%d Problem', '%d Problems' ],
			needsReview: [ '%d Needs Review', '%d Need Review' ],
			dismissed: [ '%d Dismissed', '%d Dismissed' ],
		};
		const [ single, plural ] = forms[ key ] || [ '%d', '%d' ];
		return sprintf( _n( single, plural, count, 'accessibility-checker' ), count );
	};

	_createTrigger() {
		const widgetPosition = edacFrontendHighlighterApp?.widgetPosition || 'right';
		const trigger = document.createElement( 'edac-highlight-trigger' );
		trigger.setAttribute( 'position', widgetPosition );
		trigger.setAttribute( 'aria-label', __( 'Accessibility Checker Tools', 'accessibility-checker' ) );
		if ( edacFrontendHighlighterApp?.adminThemeColor ) {
			trigger.style.setProperty( '--wp-admin-theme-color', edacFrontendHighlighterApp.adminThemeColor );
		}
		document.body.appendChild( trigger );
		return trigger;
	}

	_createPanel() {
		const widgetPosition = edacFrontendHighlighterApp?.widgetPosition || 'right';
		const panel = document.createElement( 'edac-highlight-panel' );
		panel.setAttribute( 'position', widgetPosition );
		if ( edacFrontendHighlighterApp?.adminThemeColor ) {
			panel.style.setProperty( '--wp-admin-theme-color', edacFrontendHighlighterApp.adminThemeColor );
		}
		document.body.appendChild( panel );

		panel.i18n = this._i18n;
		panel.pluralize = this._pluralize;
		panel.userCanEdit = !! ( edacFrontendHighlighterApp?.userCanEdit && edacFrontendHighlighterApp?.loggedIn );
		panel.isPro = !! edacFrontendHighlighterApp?.isPro;
		panel.userCanFix = !! edacFrontendHighlighterApp?.userCanFix;

		return panel;
	}

	_createFixesModal() {
		if ( ! window.edacFrontendHighlighterApp?.userCanFix ) {
			return null;
		}
		const modal = document.createElement( 'edac-fixes-modal' );
		document.body.appendChild( modal );
		return modal;
	}

	/**
	 * Production only ever constructs one instance per page load (guarded
	 * by initHighlighter()'s highlighterInitialized flag), so these
	 * document-level listeners are never expected to need cleanup in
	 * practice. The AbortController exists so destroy() can cleanly tear
	 * them down for tests that construct multiple instances in the same
	 * jsdom document.
	 */
	_bindEvents() {
		this._abortController = new AbortController();
		const { signal } = this._abortController;

		document.addEventListener( 'edac-toggle-panel', () => this.panelOpen(), { signal } );
		document.addEventListener( 'edac-panel-close', () => this.panelClose(), { signal } );
		document.addEventListener( 'edac-nav-next', () => this.highlightFocusNext(), { signal } );
		document.addEventListener( 'edac-nav-previous', () => this.highlightFocusPrevious(), { signal } );
		document.addEventListener( 'edac-open-issue', ( e ) => this._onOpenIssue( e.detail.issueId ), { signal } );
		document.addEventListener( 'edac-toggle-dock', () => this.toggleDock(), { signal } );
		document.addEventListener( 'edac-rescan', () => this.rescanPage(), { signal } );
		document.addEventListener( 'edac-clear-issues', () => this.clearIssues(), { signal } );
		document.addEventListener( 'edac-toggle-page-styles', () => {
			if ( this.stylesDisabled ) {
				this.enableStyles();
			} else {
				this.disableStyles();
			}
		}, { signal } );
		document.addEventListener( 'edac-panel-position-changed', ( e ) => this._onPanelPositionChanged( e.detail.position ), { signal } );
		document.addEventListener( 'edac-open-fix-settings', ( e ) => this._openFixSettings( e.detail.container, e.detail.openingElement ), { signal } );
	}

	/**
	 * Not called in production — see _bindEvents()'s note. Exists for test
	 * isolation when multiple instances are constructed in one jsdom document.
	 */
	destroy() {
		this._abortController?.abort();
	}

	_restoreDockedState() {
		if ( this.isDocked ) {
			this.applyDock();
		}
	}

	_openFromUrlOrDock() {
		if ( this.urlParameter ) {
			this.panelOpen( this.urlParameter );
		} else if ( this.landmarkParameter ) {
			this.highlightLandmark( this.landmarkParameter );
		} else if ( this.isDocked ) {
			// Docked panel restored on page load — fetch issue data so the panel isn't empty.
			this.panelOpen();
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
			// remove position/resize listener: https://floating-ui.com/docs/autoUpdate
			item.listeners.cleanup();
		} );

		document.querySelectorAll( 'edac-highlight-tooltip-button' ).forEach( ( button ) => {
			button.remove();
		} );

		this._removeLandmarkLabel();
	}

	/**
	 * This function adds a new edac-highlight-tooltip-button element to the DOM for a flagged element.
	 *
	 * @param {HTMLElement} element    - The DOM element before which the tooltip button will be inserted.
	 * @param {Object}      value      - An object containing properties used to customize the tooltip button.
	 * @param {number}      index      - The index of the element being processed.
	 * @param {number}      totalItems
	 * @return {Object} - information about the tooltip
	 */
	addTooltip( element, value, index, totalItems ) {
		const tooltip = document.createElement( 'edac-highlight-tooltip-button' );
		tooltip.setAttribute( 'rule-type', value.rule_type );
		tooltip.setAttribute( 'aria-label', sprintf( __( 'Open details for %1$s, %2$s of %3$s', 'accessibility-checker' ), value.rule_title, index + 1, totalItems ) );
		tooltip.issueId = value.id;

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
			const allTooltips = Array.from( document.querySelectorAll( 'edac-highlight-tooltip-button' ) );
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
			} ).then( ( { x } ) => {
				const elRect = element.getBoundingClientRect();
				const elHeight = element.offsetHeight === undefined ? 0 : element.offsetHeight;
				const tooltipHeight = tooltip.offsetHeight === undefined ? 0 : tooltip.offsetHeight;
				const tooltipWidth = tooltip.offsetWidth === undefined ? 0 : tooltip.offsetWidth;

				// Calculate the horizontal offset for stacking multiple tooltips on the same element
				const left = tooltipOffset * ( tooltipWidth + TOOLTIP_GAP );

				// Start with the position from computePosition
				const finalLeft = x + left;

				// Compute the vertical position directly from the target's viewport
				// rect + scroll rather than trusting floating-ui's `y`. floating-ui's
				// "absolute" strategy math doesn't account for a margin-top on the
				// <html> element itself — which is exactly what WordPress sets
				// (`html { margin-top: 32px !important }`) to make room for the
				// admin bar on the front end for logged-in users — so `y` ends up
				// off by roughly that margin, placing every tooltip too high. Since
				// placement is a fixed 'top-start' with no flip/shift middleware,
				// floating-ui isn't doing anything here this direct calculation
				// doesn't already cover.
				let finalTop = elRect.top + document.documentElement.scrollTop - tooltipHeight;

				// Special handling for zero-height elements (like empty <p> tags):
				// leave a small gap instead of sitting flush against the target.
				if ( elHeight === 0 && elRect.height === 0 ) {
					finalTop -= 5;
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
				cleanup,
			},
		};
	}

	/**
	 * Handles a tooltip button's edac-open-issue click. Tooltip buttons only
	 * ever exist once issues have already been fetched (they're created
	 * inside panelOpen()'s AJAX success handler), so this shows the issue
	 * directly against already-loaded data rather than re-fetching — a
	 * fresh panelOpen() would be a redundant network round-trip on every
	 * click, and isn't what the original tooltip onClick did either.
	 *
	 * @param {string} id
	 */
	_onOpenIssue( id ) {
		if ( ! this.issues ) {
			this.panelOpen( id );
			return;
		}
		this.trigger.open = true;
		this.panel.open = true;
		this.showIssue( id );
		this.panel.refocus();
	}

	/**
	 * This function opens the accessibility checker panel, fetching issue
	 * data if needed and showing a specific issue if `id` is given.
	 * @param {number} [id] of the issue
	 */
	panelOpen( id ) {
		this.trigger.open = true;
		this.panel.open = true;

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

				this.panel.issues = this.issues;

				if ( id !== undefined ) {
					this.showIssue( id );
				} else if ( this.currentButtonIndex !== null && this.issues[ this.currentButtonIndex ] ) {
					this.showIssue( this.issues[ this.currentButtonIndex ].id );
				} else if ( this.issues.length > 0 ) {
					this.showIssue( this.issues[ 0 ].id );
				}
			}
		).catch( () => {
			this.panel.setMessage( __( 'An error occurred when loading the issues.', 'accessibility-checker' ), true );
		} );
	}

	/**
	 * This function closes the accessibility checker panel.
	 */
	panelClose() {
		if ( this.isDocked ) {
			this.removeDock();
		}
		this.panel.open = false;
		this.trigger.open = false;
		this.removeSelectedClasses();
		this.removeHighlightButtons();
		this.trigger.focus();
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
			if ( ! this.panel.open ) {
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

		const isRight = this.panel.position !== 'left';
		this.panel.docked = true;

		// Offset below the admin bar if present.
		const adminBar = document.getElementById( 'wpadminbar' );
		const adminBarHeight = adminBar ? adminBar.offsetHeight : 0;
		this.panel.style.setProperty( '--edac-adminbar-height', adminBarHeight + 'px' );

		this.trigger.open = true;
		this.panel.open = true;

		// Push page content to make room for the panel.
		// Use a rAF so the browser has laid out the panel before we read its width.
		requestAnimationFrame( () => {
			const panelWidth = this.panel.controls.offsetWidth + 'px';
			document.body.style[ isRight ? 'marginRight' : 'marginLeft' ] = panelWidth;
		} );

		this.panel.announce( __( 'Panel docked.', 'accessibility-checker' ) );
	}

	/**
	 * Remove docked sidebar mode and return to overlay.
	 */
	removeDock() {
		this.isDocked = false;
		localStorage.removeItem( 'edac-panel-docked' );

		this.panel.docked = false;

		// Remove body margin.
		document.body.style.marginRight = '';
		document.body.style.marginLeft = '';

		this.panel.announce( __( 'Panel undocked.', 'accessibility-checker' ) );
	}

	/**
	 * When the panel reports its position changed (via the Move menu
	 * action), keep the docked body-margin push in sync.
	 *
	 * @param {string} position 'left' | 'right'
	 */
	_onPanelPositionChanged( position ) {
		if ( ! this.isDocked ) {
			return;
		}
		document.body.style.marginRight = '';
		document.body.style.marginLeft = '';
		const panelWidth = this.panel.controls.offsetWidth + 'px';
		document.body.style[ position === 'left' ? 'marginLeft' : 'marginRight' ] = panelWidth;
	}

	/**
	 * This function removes the classes that indicates a button or element are selected
	 */
	removeSelectedClasses = () => {
		// remove selected class from previously selected buttons
		const selectedButtons = document.querySelectorAll( '.edac-highlight-btn-selected' );
		selectedButtons.forEach( ( selectedButton ) => {
			selectedButton.classList.remove( 'edac-highlight-btn-selected' );
		} );
		// remove selected class from previously selected elements
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

			if ( isFocusable( tooltip.button ?? tooltip ) ) {
				if ( ! this.checkVisibility( tooltip ) || ! this.checkVisibility( element ) ) {
					this.currentIssueStatus = __( 'The element is not visible. Try disabling styles.', 'accessibility-checker' );
				} else {
					this.currentIssueStatus = null;
				}
			} else {
				this.currentIssueStatus = __( 'The element is not focusable. Try disabling styles.', 'accessibility-checker' );
			}
		} else {
			this.currentIssueStatus = __( 'The element was not found on the page.', 'accessibility-checker' );
		}

		this.panel.currentIndex = this.currentButtonIndex;
		this.panel.status = this.currentIssueStatus;
		this.panel.fix = this.fixes ? this.fixes[ issue.slug ] : null;
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
		this.panel.stylesDisabled = true;
		this.panel.announce( __( 'Page styles disabled.', 'accessibility-checker' ) );
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
		this.panel.stylesDisabled = false;
		this.panel.announce( __( 'Page styles re-enabled.', 'accessibility-checker' ) );

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
				this._removeLandmarkLabel();

				// Add highlighting styles
				landmarkElement.classList.add( 'edac-highlight-element-selected' );
				landmarkElement.classList.add( 'edac-landmark-highlight' );

				// Create and add landmark type label
				const landmarkType = getLandmarkTypeUtil( landmarkElement );
				const label = document.createElement( 'edac-landmark-label' );
				label.text = sprintf( __( 'Landmark: %s', 'accessibility-checker' ), landmarkType );

				// Position the label inside the top-left corner of the landmark
				const rect = landmarkElement.getBoundingClientRect();
				label.left = rect.left + window.scrollX;
				label.top = rect.top + window.scrollY;

				document.body.appendChild( label );
				this._landmarkLabel = label;

				// Store reference for cleanup
				landmarkElement.setAttribute( 'data-edac-landmark-label-id', Date.now() );

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
	 * Remove the current landmark label (if any) from the page.
	 */
	_removeLandmarkLabel() {
		this._landmarkLabel?.remove();
		this._landmarkLabel = null;

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
				if ( window.edacFrontendHighlighterApp?.landmarkTypes ) {
					window.scanOptions = window.scanOptions || {};
					const lm = window.edacFrontendHighlighterApp.landmarkTypes;
					window.scanOptions.landmarkTags = lm.tags;
					window.scanOptions.landmarkRoles = lm.roles;
					window.scanOptions.conditionalLandmarkTags = lm.conditionalTags;
					window.scanOptions.conditionalLandmarkRoles = lm.conditionalRoles;
				}
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
		this.panel.setMessage( __( 'Scanning...', 'accessibility-checker' ) );
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
					self.panel.announce( __( 'Rescan complete. No violations found.', 'accessibility-checker' ) );
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
			this.panel.announce( __( 'Rescan already in progress.', 'accessibility-checker' ) );
			return;
		}
		// Avoid panelOpen from short-circuiting into an auto-rescan after an explicit rescan.
		this._issuesCleared = false;
		this._isRescanning = true;
		this._pendingRescanAnnouncement = true;
		this.panel.announce( __( 'Rescanning this page.', 'accessibility-checker' ) );

		this.removeHighlightButtons();
		this.kickoffScan().then( () => {
			if ( this._pendingRescanAnnouncement ) {
				this.panel.announce( __( 'Rescan complete.', 'accessibility-checker' ) );
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

		// Validate required parameters
		if ( ! window.edacFrontendHighlighterApp?.restUrl || ! window.edacFrontendHighlighterApp?.postID ) {
			this.panel.setMessage( __( 'Error: Missing required parameters.', 'accessibility-checker' ), true );
			return;
		}

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

				// Remove the URL parameter.
				const url = new URL( window.location.href );
				url.searchParams.delete( 'edac' );
				history.replaceState( null, '', url.toString() );

				this.panel.currentIndex = null;
				this.panel.issues = [];
				this.panel.setMessage( __( 'Issues cleared successfully.', 'accessibility-checker' ) );
			} else {
				this.panel.setMessage( __( 'Failed to clear issues.', 'accessibility-checker' ), true );
			}
		} ).catch( () => {
			this.panel.setMessage( __( 'An error occurred while clearing issues.', 'accessibility-checker' ), true );
		} );
	}

	/**
	 * Show an error message in the scan panel.
	 * @param {string} message
	 */
	showScanError( message ) {
		this.panel.setMessage( message, true );

		if ( this._pendingRescanAnnouncement ) {
			this.panel.announce( message );
			this._pendingRescanAnnouncement = false;
		}
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
	 * Move a detached .edac-fix-settings container (handed up from
	 * edac-highlight-issue-view via edac-open-fix-settings) into the fixes
	 * modal and open it, pausing the panel's own focus trap while the
	 * modal is in front of it.
	 *
	 * @param {HTMLElement} container      The detached .edac-fix-settings node.
	 * @param {HTMLElement} openingElement Element to restore focus to on close.
	 */
	_openFixSettings( container, openingElement ) {
		if ( ! this.fixesModal || ! container ) {
			return;
		}

		this.fixesModal.fill(
			`<p class="modal-opening-message">${ __( 'These settings enable global fixes across your entire site. Pages may need to be resaved or a full site scan run to see fixes reflected in reports.', 'accessibility-checker' ) }</p>`,
			container
		);

		this.panel.pauseFocusTrap();
		this.fixesModal.open( openingElement );

		// unpause the focus trap when the modal is closed (once only, to avoid handler accumulation).
		document.addEventListener( 'edac-fixes-modal-closed', () => {
			this.panel.resumeFocusTrap();
		}, { once: true } );
	}
}

// Some systems (Cloudflare Rocket Loader) defers scripts for performance but that can
// cause some DOMContentLoaded events to be missed. This is flag tracks if it run so we
// can retry at a latter event listener.
let highlighterInitialized = false;
const initHighlighter = () => {
	if ( ! highlighterInitialized ) {
		new AccessibilityCheckerHighlight();
		highlighterInitialized = true;
	}
};

[ 'DOMContentLoaded', 'load' ].forEach( ( event ) => {
	window.addEventListener( event, initHighlighter );
} );

export { AccessibilityCheckerHighlight };
