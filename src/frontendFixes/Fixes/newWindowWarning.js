import { __ } from '@wordpress/i18n';

const localizedNewWindowWarning = __( 'opens a new window', 'accessibility-checker' );

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
	anwwLinkTooltip.classList.add( 'anww-tooltip' );
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
		whiteSpace: 'nowrap',
	} );
	document.body.appendChild( anwwLinkTooltip );

	// Hide tooltip when clicking outside or pressing Escape
	document.addEventListener( 'click', ( event ) => {
		if ( ! event.target.closest( ".anww-tooltip, a[target='_blank']" ) ) {
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
	// Remove previously appended icons to avoid duplication
	document.querySelectorAll( '.edac-nww-external-link-icon' ).forEach( ( icon ) => icon.remove() );

	document.querySelectorAll( 'a:not([data-nww-processed])' ).forEach( ( link ) => {
		const onclickAttr = link.getAttribute( 'onclick' );

		// Check if the link opens a new window using target="_blank"
		if ( link.getAttribute( 'target' ) === '_blank' ) {
			addExternalLinkIcon( link );
			updateAriaLabel( link );
			addTooltipHandlers( link );
			link.setAttribute( 'data-nww-processed', 'true' ); // Mark link as processed
		}

		// Check if the link uses window.open in the onclick attribute
		if ( onclickAttr && onclickAttr.includes( 'window.open' ) ) {
			const windowOpenMatch = onclickAttr.match( /window\.open\([^,]+,\s*['"]([^'"]+)['"]/ );
			const targetWindow = windowOpenMatch ? windowOpenMatch[ 1 ] : '';

			if ( targetWindow === '_blank' || targetWindow === '' ) {
				addExternalLinkIcon( link );
				updateAriaLabel( link );
				addTooltipHandlers( link );
				link.setAttribute( 'data-nww-processed', 'true' ); // Mark link as processed
			}
		}
	} );
};

/**
 * Adds an external link icon to the specified link.
 * @param {HTMLElement} link - The link element to modify.
 */
const addExternalLinkIcon = ( link ) => {
	// Add icon to link.
	const header = link.querySelector( 'h1, h2, h3, h4, h5, h6' );
	if ( header ) {
		header.insertAdjacentHTML( 'beforeend', '<i class="edac-nww-external-link-icon" aria-hidden="true"></i>' );
		return;
	}

	// If this link is an Elementor button, place the icon inside its content wrapper.
	// Note: This relies on Elementor's specific '.elementor-button-content-wrapper' class, which might change in future Elementor updates.
	const elementorButtonContent = link.querySelector( '.elementor-button-content-wrapper' );
	if ( elementorButtonContent ) {
		elementorButtonContent.insertAdjacentHTML( 'beforeend', '<i class="edac-nww-external-link-icon elementor-button-link-content" aria-hidden="true"></i>' );
		return;
	}

	link.insertAdjacentHTML( 'beforeend', '<i class="edac-nww-external-link-icon" aria-hidden="true"></i>' );
};

/**
 * Updates the aria-label of the specified link.
 * @param {HTMLElement} link - The link element to modify.
 */
const updateAriaLabel = ( link ) => {
	let anwwLabel = '';

	if ( link.hasAttribute( 'aria-label' ) ) {
		anwwLabel = link.getAttribute( 'aria-label' );
	} else if ( link.querySelector( 'img' ) ) {
		const img = link.querySelector( 'img' );
		anwwLabel = img.getAttribute( 'alt' ) || '';
	} else if ( link.textContent ) {
		anwwLabel = link.textContent.trim();
	}

	anwwLabel = anwwLabel ? `${ anwwLabel }, ${ localizedNewWindowWarning }` : localizedNewWindowWarning;
	link.setAttribute( 'aria-label', anwwLabel );
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

	anwwLinkTooltip.textContent = localizedNewWindowWarning;
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
