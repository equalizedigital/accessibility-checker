import { __ } from '@wordpress/i18n';

const localizedOpenString = __( 'opens a new window', 'accessibility-checker' );

const NewWindowWarning = () => {
	initializeTooltip();
	processLinks();

	// Support for FacetWP: Re-run the processLinks function when FacetWP refreshes the page
	document.addEventListener( 'facetwp-loaded', processLinks );
};

const initializeTooltip = () => {
	const tooltipElement = document.createElement( 'div' );
	Object.assign( tooltipElement.style, {
		position: 'absolute',
		background: 'white',
		color: '#1e1e1e',
		fontSize: '16px',
		border: '1px solid black',
		padding: '5px 10px',
		zIndex: 9999,
		display: 'none',
	} );
	tooltipElement.classList.add( 'edac-nww-tooltip' );
	document.body.appendChild( tooltipElement );
};

const processLinks = () => {
	// Remove previously appended icons to avoid duplication
	document.querySelectorAll( '.edac-nww-external-link-icon' ).forEach( ( icon ) => icon.remove() );

	const allLinks = document.querySelectorAll( 'a' );
	allLinks.forEach( ( link ) => {
		const onclickAttr = link.getAttribute( 'onclick' );

		// Check if the link opens a new window using target="_blank"
		if ( link.getAttribute( 'target' ) === '_blank' ) {
			addExternalLinkIcon( link );
			updateAriaLabel( link );
			addTooltipHandlers( link );
		}

		// Check if the link uses window.open in the onclick attribute
		if ( onclickAttr && onclickAttr.includes( 'window.open' ) ) {
			// Extract window.open arguments
			const windowOpenMatch = onclickAttr.match( /window\.open\([^,]+,\s*['"]([^'"]+)['"]/ );
			const targetWindow = windowOpenMatch ? windowOpenMatch[ 1 ] : '';

			// Ensure window.open is opening a new window (i.e., '_blank')
			if ( targetWindow === '_blank' || targetWindow === '' ) {
				addExternalLinkIcon( link );
				updateAriaLabel( link );
				addTooltipHandlers( link );
			}
		}
	} );
};

const addExternalLinkIcon = ( link ) => {
	// Add icon to link
	const header = link.querySelector( 'h1, h2, h3, h4, h5, h6' );
	if ( header ) {
		header.insertAdjacentHTML( 'beforeend', '<i class="edac-nww-external-link-icon" aria-hidden="true"></i>' );
	} else {
		link.insertAdjacentHTML( 'beforeend', '<i class="edac-nww-external-link-icon" aria-hidden="true"></i>' );
	}
};

const updateAriaLabel = ( link ) => {
	let label = '';
	// Get aria label text
	if ( link.getAttribute( 'aria-label' ) ) {
		label = link.getAttribute( 'aria-label' );
	} else if ( link.querySelector( 'img' ) ) {
		label = link.querySelector( 'img' ).getAttribute( 'alt' );
	} else if ( link.textContent ) {
		label = link.textContent;
	}

	// Add warning label
	if ( label ) {
		label = label.trimEnd();
		label += ', ' + localizedOpenString;
	} else {
		label += localizedOpenString;
	}

	// Add aria-label to link
	link.setAttribute( 'aria-label', label );
};

const addTooltipHandlers = ( link ) => {
	const tooltip = document.querySelector( '.edac-nww-tooltip' );

	// Position and show link_tooltip on hover
	link.addEventListener( 'mousemove', ( e ) => {
		tooltip.style.top = e.pageY + 10 + 'px';
		tooltip.style.left = e.pageX + 10 + 'px';
	} );

	link.addEventListener( 'mouseenter', () => {
		tooltip.style.display = 'block';
		tooltip.innerHTML = localizedOpenString;
	} );

	link.addEventListener( 'mouseleave', () => {
		tooltip.style.display = 'none';
	} );

	// Position and show link_tooltip on focus
	link.addEventListener( 'focusin', () => {
		const position = link.getBoundingClientRect();
		tooltip.style.top = position.top + window.scrollY + link.offsetHeight + 'px';
		tooltip.style.left = position.left + window.scrollX + 'px';

		tooltip.style.display = 'block';
		tooltip.innerHTML = localizedOpenString;
	} );

	link.addEventListener( 'focusout', () => {
		tooltip.style.display = 'none';
	} );
};

export default NewWindowWarning;
