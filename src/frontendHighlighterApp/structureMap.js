import { __, sprintf } from '@wordpress/i18n';
import { getLandmarkType } from './getLandmarkType';

const LANDMARK_SELECTOR = [
	'header:not([role])',
	'nav:not([role])',
	'main:not([role])',
	'aside:not([role])',
	'footer:not([role])',
	'section[aria-label], section[aria-labelledby]',
	'form[aria-label], form[aria-labelledby]',
	'[role="banner"]',
	'[role="navigation"]',
	'[role="main"]',
	'[role="complementary"]',
	'[role="contentinfo"]',
	'[role="search"]',
	'[role="form"]',
	'[role="region"]',
].join( ', ' );

function getLandmarkLabel( el ) {
	const labelledby = el.getAttribute( 'aria-labelledby' );
	if ( labelledby ) {
		const labelEl = document.getElementById( labelledby );
		if ( labelEl ) {
			return labelEl.textContent.trim();
		}
	}
	const ariaLabel = el.getAttribute( 'aria-label' );
	if ( ariaLabel ) {
		return ariaLabel.trim();
	}
	return '';
}

function buildLandmarkNodes( elements ) {
	const sorted = [ ...elements ].sort( ( a, b ) => {
		// eslint-disable-next-line no-bitwise
		return a.compareDocumentPosition( b ) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
	} );

	const root = { el: null, children: [] };
	const stack = [ root ];

	for ( const el of sorted ) {
		const node = { el, children: [] };
		while ( stack.length > 1 && ! stack[ stack.length - 1 ].el.contains( el ) ) {
			stack.pop();
		}
		stack[ stack.length - 1 ].children.push( node );
		stack.push( node );
	}

	return root.children;
}

function makeItemButton( levelText, labelText, isLandmark, hasError ) {
	const btn = document.createElement( 'button' );
	btn.className = 'edac-structure-item-btn';

	const levelSpan = document.createElement( 'span' );
	levelSpan.className = 'edac-structure-item-level' + ( isLandmark ? ' edac-structure-item-level--landmark' : '' );
	levelSpan.textContent = levelText;
	levelSpan.setAttribute( 'aria-hidden', 'true' );
	btn.append( levelSpan );

	if ( labelText ) {
		const textSpan = document.createElement( 'span' );
		textSpan.className = 'edac-structure-item-text';
		textSpan.textContent = labelText;
		btn.append( textSpan );
	}

	if ( hasError ) {
		const errorSpan = document.createElement( 'span' );
		errorSpan.className = 'edac-structure-item-error-icon';
		errorSpan.setAttribute( 'aria-hidden', 'true' );
		btn.append( errorSpan );
	}

	return btn;
}

export function renderHeadingsPanel( panel, issues, onHeadingClick ) {
	const headingEls = Array.from( document.querySelectorAll( 'h1, h2, h3, h4, h5, h6' ) ).filter(
		( el ) => ! el.closest( '#edac-highlight-panel' ) && ! el.closest( '#edac-fixes-modal' )
	);

	if ( headingEls.length === 0 ) {
		const empty = document.createElement( 'p' );
		empty.className = 'edac-structure-empty';
		empty.textContent = __( 'No headings found on this page.', 'accessibility-checker' );
		panel.append( empty );
		return;
	}

	const list = document.createElement( 'ul' );
	list.className = 'edac-structure-list';
	list.setAttribute( 'role', 'list' );

	let prevLevel = 0;

	for ( const el of headingEls ) {
		const level = parseInt( el.tagName[ 1 ] );
		const hasDbIssue = issues?.some( ( i ) => i.element === el ) ?? false;
		const isSkip = prevLevel > 0 && level > prevLevel + 1;
		prevLevel = level;

		const hasError = hasDbIssue || isSkip;
		const text = el.textContent.trim();
		const levelTag = `H${ level }`;

		let srSuffix = '';
		if ( isSkip ) {
			srSuffix = ', ' + __( 'heading level skipped', 'accessibility-checker' );
		} else if ( hasDbIssue ) {
			srSuffix = ', ' + __( 'has accessibility issue', 'accessibility-checker' );
		}

		const li = document.createElement( 'li' );
		li.setAttribute( 'role', 'listitem' );

		const item = document.createElement( 'div' );
		item.className = 'edac-structure-item' + ( hasError ? ' edac-structure-item--error' : '' );
		item.style.setProperty( '--depth', level - 1 );

		const btn = makeItemButton( levelTag, text, false, hasError );
		btn.setAttribute( 'aria-label', sprintf(
			/* translators: %s is the heading level and text, e.g. "H2: Section Title, heading level skipped" */
			__( 'Navigate to %s', 'accessibility-checker' ),
			`${ levelTag }: ${ text }${ srSuffix }`
		) );

		btn.addEventListener( 'click', () => {
			if ( onHeadingClick ) {
				onHeadingClick( el );
			} else {
				el.scrollIntoView( { block: 'center' } );
			}
		} );

		item.append( btn );
		li.append( item );
		list.append( li );
	}

	panel.append( list );
}

function appendLandmarkNodes( nodeList, ulEl, onLandmarkClick ) {
	for ( const node of nodeList ) {
		const type = getLandmarkType( node.el );
		const label = getLandmarkLabel( node.el );

		const li = document.createElement( 'li' );
		li.setAttribute( 'role', 'listitem' );

		const item = document.createElement( 'div' );
		item.className = 'edac-structure-item';

		const btn = makeItemButton( type, label, true, false );
		btn.setAttribute( 'aria-label', sprintf(
			/* translators: %s is the landmark type and label, e.g. "Navigation: Main menu" */
			__( 'Navigate to %s', 'accessibility-checker' ),
			label ? `${ type }: ${ label }` : type
		) );

		btn.addEventListener( 'click', () => {
			if ( onLandmarkClick ) {
				onLandmarkClick( node.el );
			} else {
				node.el.scrollIntoView( { block: 'center' } );
			}
		} );

		item.append( btn );
		li.append( item );

		if ( node.children.length ) {
			const childUl = document.createElement( 'ul' );
			childUl.setAttribute( 'role', 'list' );
			appendLandmarkNodes( node.children, childUl, onLandmarkClick );
			li.append( childUl );
		}

		ulEl.append( li );
	}
}

export function renderLandmarksPanel( panel, onLandmarkClick ) {
	const landmarkEls = Array.from( document.querySelectorAll( LANDMARK_SELECTOR ) ).filter(
		( el ) => ! el.closest( '#edac-highlight-panel' )
	);

	if ( landmarkEls.length === 0 ) {
		const empty = document.createElement( 'p' );
		empty.className = 'edac-structure-empty';
		empty.textContent = __( 'No landmarks found on this page.', 'accessibility-checker' );
		panel.append( empty );
		return;
	}

	const nodes = buildLandmarkNodes( landmarkEls );
	const list = document.createElement( 'ul' );
	list.className = 'edac-structure-list';
	list.setAttribute( 'role', 'list' );
	appendLandmarkNodes( nodes, list, onLandmarkClick );
	panel.append( list );
}
