const EXCLUDED_SELECTOR = [
	'script',
	'style',
	'svg',
	'path',
	'code',
	'pre',
	'textarea',
	'input',
	'select',
	'option',
].join( ',' );

const isElementExcluded = ( element ) => element.matches( EXCLUDED_SELECTOR ) || element.closest( EXCLUDED_SELECTOR );

const hasReadableTextNode = ( element ) => {
	const text = Array.from( element.childNodes )
		.filter( ( node ) => node.nodeType === Node.TEXT_NODE )
		.map( ( node ) => node.textContent.trim() )
		.join( ' ' );

	return text.length > 0;
};

const applyMinimumTextSize = ( element, minSize ) => {
	if ( isElementExcluded( element ) ) {
		return;
	}

	if ( ! hasReadableTextNode( element ) ) {
		return;
	}

	const computedStyle = window.getComputedStyle( element );
	const fontSize = parseFloat( computedStyle.fontSize || '0' );
	if ( Number.isNaN( fontSize ) || fontSize >= minSize ) {
		return;
	}

	element.style.fontSize = `${ minSize }px`;
};

const minimumTextSizeFix = () => {
	const edacFrontendFixes = window.edac_frontend_fixes || {};
	const minSize = Math.max( 10, Number.parseInt( edacFrontendFixes.minimum_text_size?.min_size || 10, 10 ) );

	const walker = document.createTreeWalker( document.body, NodeFilter.SHOW_ELEMENT );
	const elements = [];
	while ( walker.nextNode() ) {
		elements.push( walker.currentNode );
	}

	elements.forEach( ( element ) => applyMinimumTextSize( element, minSize ) );
};

export default minimumTextSizeFix;
