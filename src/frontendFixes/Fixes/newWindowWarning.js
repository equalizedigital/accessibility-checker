/**
 * New Window Warning Fix
 *
 * Adds an icon and tooltip to links that open in a new window, improving accessibility by informing users of the behavior.
 */

const classPrefix = window.edac_frontend_fixes?.new_window_warning?.classPrefix || window.anww_localized?.classPrefix || 'edac-nww';
const localizedString = window.edac_frontend_fixes?.new_window_warning?.localizedString || window.anww_localized?.localizedString || 'opens a new window';
const iconClass = `${ classPrefix }-external-link-icon`;

const NewWindowWarning = () => {
	initializeTooltip();
	processLinks();

	// Support for FacetWP: Re-run the processLinks function when FacetWP refreshes the page
	document.addEventListener( 'facetwp-loaded', processLinks );
};

let anwwLinkTooltip;
let tooltipTimeout;

/**
 * Initializes the tooltip element and event listeners.
 */
const initializeTooltip = () => {
	anwwLinkTooltip = document.createElement( 'div' );
	anwwLinkTooltip.setAttribute( 'role', 'tooltip' );
	anwwLinkTooltip.classList.add( `${ classPrefix }-tooltip` );
	Object.assign( anwwLinkTooltip.style, {
		position: 'absolute',
		background: 'white',
		color: '#1e1e1e',
		fontSize: '16px',
		border: '1px solid black',
		padding: '5px 10px',
		zIndex: 9999,
		display: 'none',
		pointerEvents: 'auto',
		boxShadow: '0px 4px 6px rgba(0,0,0,0.1)',
		maxWidth: '200px',
		whiteSpace: 'normal',
	} );
	document.body.appendChild( anwwLinkTooltip );

	// Hide tooltip when clicking outside or pressing Escape
	document.addEventListener( 'click', ( event ) => {
		if ( ! event.target.closest( `.${ classPrefix }-tooltip, a[target='_blank']` ) ) {
			hideTooltip();
		}
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' ) {
			hideTooltip();
		}
	} );

	// Keep tooltip visible when hovered
	anwwLinkTooltip.addEventListener( 'mouseenter', () => {
		clearTimeout( tooltipTimeout );
	} );

	anwwLinkTooltip.addEventListener( 'mouseleave', () => {
		hideTooltip();
	} );
};

/**
 * Processes all anchor links and applies necessary accessibility enhancements.
 */
const processLinks = () => {
	// Only process links that have not yet been enhanced to prevent duplicate icons.
	document.querySelectorAll( 'a:not([data-nww-processed])' ).forEach( ( link ) => {
		const onclickAttr = link.getAttribute( 'onclick' );

		// Check if the link opens a new window using target="_blank"
		if ( link.getAttribute( 'target' ) === '_blank' ) {
			if ( ! link.closest( '.anww-no-icon, .edac-nww-no-icon' ) ) {
				addExternalLinkIcon( link );
			}
			updateAriaLabel( link );
			if ( ! link.closest( '.anww-no-tooltip, .edac-nww-no-tooltip' ) ) {
				addTooltipHandlers( link );
			}
			link.setAttribute( 'data-nww-processed', 'true' );
			return;
		}

		// Check if the link uses window.open in the onclick attribute
		if ( onclickAttr && onclickAttr.includes( 'window.open' ) ) {
			const windowOpenMatch = onclickAttr.match( /window\.open\([^,]+,\s*['"]([^'"]+)['"]/ );
			const targetWindow = windowOpenMatch ? windowOpenMatch[ 1 ] : '';

			if ( targetWindow === '_blank' || targetWindow === '' ) {
				if ( ! link.closest( '.anww-no-icon, .edac-nww-no-icon' ) ) {
					addExternalLinkIcon( link );
				}
				updateAriaLabel( link );
				if ( ! link.closest( '.anww-no-tooltip, .edac-nww-no-tooltip' ) ) {
					addTooltipHandlers( link );
				}
				link.setAttribute( 'data-nww-processed', 'true' );
			}
		}
	} );
};

/**
 * Adds an external link icon to the specified link.
 * @param {HTMLElement} link - The link element to modify.
 */
const addExternalLinkIcon = ( link ) => {
	const header = link.querySelector( 'h1, h2, h3, h4, h5, h6' );
	if ( header ) {
		header.insertAdjacentHTML( 'beforeend', `<i class="${ iconClass }" aria-hidden="true"></i>` );
		return;
	}

	// If this link is an Elementor button, place the icon inside its content wrapper.
	const elementorButtonContent = link.querySelector( '.elementor-button-content-wrapper' );
	if ( elementorButtonContent ) {
		elementorButtonContent.insertAdjacentHTML( 'beforeend', `<i class="${ iconClass } elementor-button-link-content" aria-hidden="true"></i>` );
		return;
	}

	link.insertAdjacentHTML( 'beforeend', `<i class="${ iconClass }" aria-hidden="true"></i>` );
};

/**
 * Computes the accessible name for a link by traversing its child nodes in document order.
 * Collects text content and image alt text (for non-decorative images), skipping aria-hidden elements.
 * @param {HTMLElement} link - The link element.
 * @return {string} The computed accessible name.
 */
const getLinkAccessibleName = ( link ) => {
	const parts = [];

	const traverse = ( node ) => {
		if ( node.nodeType === Node.TEXT_NODE ) {
			const text = node.textContent.trim();
			if ( text ) {
				parts.push( text );
			}
		} else if ( node.nodeType === Node.ELEMENT_NODE ) {
			if ( node.getAttribute( 'aria-hidden' ) === 'true' ) {
				return;
			}
			if ( node.hasAttribute( 'aria-label' ) ) {
				const ariaLabel = node.getAttribute( 'aria-label' ).trim();
				if ( ariaLabel ) {
					parts.push( ariaLabel );
				}
				return;
			}
			if ( node.nodeName === 'IMG' ) {
				const role = node.getAttribute( 'role' );
				const isDecorative = role && ( role.split( /\s+/ ).includes( 'presentation' ) || role.split( /\s+/ ).includes( 'none' ) );
				if ( ! isDecorative ) {
					const alt = ( node.getAttribute( 'alt' ) || '' ).trim();
					if ( alt ) {
						parts.push( alt );
					}
				}
			} else {
				node.childNodes.forEach( traverse );
			}
		}
	};

	link.childNodes.forEach( traverse );

	return parts.join( ' ' ).replace( /\s+/g, ' ' ).trim();
};

/**
 * Updates the aria-label of the specified link.
 * @param {HTMLElement} link - The link element to modify.
 */
const updateAriaLabel = ( link ) => {
	let label = '';

	if ( link.hasAttribute( 'aria-label' ) ) {
		label = link.getAttribute( 'aria-label' );
	} else {
		label = getLinkAccessibleName( link );
	}

	label = label ? `${ label }, ${ localizedString }` : localizedString;
	link.setAttribute( 'aria-label', label );
};

/**
 * Adds tooltip event handlers to the specified link.
 * @param {HTMLElement} link - The link element to modify.
 */
const addTooltipHandlers = ( link ) => {
	link.addEventListener( 'mouseenter', ( e ) => {
		showTooltip( link, e.pageX, e.pageY );
	} );

	link.addEventListener( 'focusin', () => {
		const rect = link.getBoundingClientRect();
		showTooltip( link, rect.left + window.scrollX, rect.top + rect.height + window.scrollY );
	} );

	link.addEventListener( 'mouseleave', hideTooltipWithDelay );
	link.addEventListener( 'focusout', hideTooltipWithDelay );
};

/**
 * Displays the tooltip near the specified coordinates, adjusting if it overflows.
 * @param {HTMLElement} link - The link triggering the tooltip.
 * @param {number}      x    - The x-coordinate.
 * @param {number}      y    - The y-coordinate.
 */
const showTooltip = ( link, x, y ) => {
	clearTimeout( tooltipTimeout );

	anwwLinkTooltip.textContent = localizedString;
	anwwLinkTooltip.style.display = 'block';

	const tooltipWidth = anwwLinkTooltip.offsetWidth;
	const tooltipHeight = anwwLinkTooltip.offsetHeight;
	const windowWidth = window.innerWidth;
	const windowHeight = window.innerHeight;
	const scrollTop = window.scrollY;

	// Adjust X if the tooltip overflows the right edge
	if ( x + tooltipWidth + 10 > windowWidth ) {
		x -= ( tooltipWidth + 20 );
	}

	// Adjust Y if the tooltip overflows the bottom edge
	if ( y + tooltipHeight + 10 > windowHeight + scrollTop ) {
		y -= ( tooltipHeight + 20 );
	}

	anwwLinkTooltip.style.top = `${ y + 10 }px`;
	anwwLinkTooltip.style.left = `${ x + 10 }px`;
};

/**
 * Delays hiding the tooltip to prevent flickering.
 */
const hideTooltipWithDelay = () => {
	tooltipTimeout = setTimeout( hideTooltip, 300 );
};

/**
 * Hides the tooltip.
 */
const hideTooltip = () => {
	anwwLinkTooltip.style.display = 'none';
};

export default NewWindowWarning;
