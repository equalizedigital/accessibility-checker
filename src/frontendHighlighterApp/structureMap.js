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
			panel.querySelectorAll( '.edac-structure-item-btn--active' ).forEach(
				( b ) => b.classList.remove( 'edac-structure-item-btn--active' )
			);
			btn.classList.add( 'edac-structure-item-btn--active' );
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

function appendLandmarkNodes( nodeList, ulEl, onLandmarkClick, activeElement ) {
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

		if ( node.el === activeElement ) {
			btn.classList.add( 'edac-structure-item-btn--active' );
		}

		btn.addEventListener( 'click', () => {
			ulEl.closest( '[role="tabpanel"]' ).querySelectorAll( '.edac-structure-item-btn--active' ).forEach(
				( b ) => b.classList.remove( 'edac-structure-item-btn--active' )
			);
			btn.classList.add( 'edac-structure-item-btn--active' );
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
			appendLandmarkNodes( node.children, childUl, onLandmarkClick, activeElement );
			li.append( childUl );
		}

		ulEl.append( li );
	}
}

export function renderLandmarksPanel( panel, onLandmarkClick, activeElement ) {
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
	appendLandmarkNodes( nodes, list, onLandmarkClick, activeElement );
	panel.append( list );
}

const FOCUSABLE_SELECTOR = [
	'a[href]',
	'button:not([disabled])',
	'input:not([disabled])',
	'select:not([disabled])',
	'textarea:not([disabled])',
	'details > summary',
	'audio[controls]',
	'video[controls]',
	'[tabindex]:not([tabindex="-1"])',
].join( ', ' );

function getElementType( el ) {
	const role = el.getAttribute( 'role' )?.toLowerCase();
	if ( role ) {
		return role.charAt( 0 ).toUpperCase() + role.slice( 1 );
	}
	const tag = el.tagName.toLowerCase();
	const type = el.getAttribute( 'type' )?.toLowerCase();
	switch ( tag ) {
		case 'a': return 'Link';
		case 'button': return 'Button';
		case 'select': return 'Select';
		case 'textarea': return 'Textarea';
		case 'summary': return 'Summary';
		case 'audio': return 'Audio';
		case 'video': return 'Video';
		case 'input':
			switch ( type ) {
				case 'checkbox': return 'Checkbox';
				case 'radio': return 'Radio';
				case 'submit': return 'Submit';
				case 'reset': return 'Reset';
				case 'button': return 'Button';
				case 'image': return 'Image button';
				case 'file': return 'File';
				case 'search': return 'Search';
				case 'email': return 'Email';
				case 'url': return 'URL';
				case 'tel': return 'Phone';
				case 'number': return 'Number';
				case 'password': return 'Password';
				case 'date': return 'Date';
				case 'time': return 'Time';
				case 'color': return 'Color';
				case 'range': return 'Range';
				default: return 'Input';
			}
		default:
			return el.hasAttribute( 'tabindex' ) ? 'Interactive' : tag;
	}
}

function getAccessibleName( el ) {
	const labelledby = el.getAttribute( 'aria-labelledby' );
	if ( labelledby ) {
		const text = labelledby.split( /\s+/ )
			.map( ( id ) => document.getElementById( id )?.textContent.trim() )
			.filter( Boolean )
			.join( ' ' );
		if ( text ) {
			return text;
		}
	}
	const ariaLabel = el.getAttribute( 'aria-label' )?.trim();
	if ( ariaLabel ) {
		return ariaLabel;
	}
	if ( el.id ) {
		const label = document.querySelector( `label[for="${ el.id }"]` );
		if ( label ) {
			return label.textContent.trim();
		}
	}
	const wrappingLabel = el.closest( 'label' );
	if ( wrappingLabel ) {
		const clone = wrappingLabel.cloneNode( true );
		clone.querySelectorAll( 'input, select, textarea' ).forEach( ( i ) => i.remove() );
		const text = clone.textContent.trim();
		if ( text ) {
			return text;
		}
	}
	const title = el.getAttribute( 'title' )?.trim();
	if ( title ) {
		return title;
	}
	const textContent = el.textContent.trim();
	if ( textContent ) {
		return textContent;
	}
	const imgAlt = el.querySelector( 'img[alt]' )?.getAttribute( 'alt' )?.trim();
	if ( imgAlt ) {
		return imgAlt;
	}
	const placeholder = el.getAttribute( 'placeholder' )?.trim();
	if ( placeholder ) {
		return placeholder;
	}
	const value = el.getAttribute( 'value' )?.trim();
	if ( value ) {
		return value;
	}
	return '';
}

function isVisible( el ) {
	const style = getComputedStyle( el );
	if ( style.display === 'none' || style.visibility === 'hidden' ) {
		return false;
	}
	if ( el.closest( '[hidden]' ) ) {
		return false;
	}
	if ( el.closest( '[aria-hidden="true"]' ) ) {
		return false;
	}
	return true;
}

export function getFocusableElements() {
	const els = Array.from( document.querySelectorAll( FOCUSABLE_SELECTOR ) ).filter(
		( el ) =>
			! el.closest( '#edac-highlight-panel' ) &&
			! el.closest( '#edac-fixes-modal' ) &&
			! el.closest( '#edac-tab-order-overlay' ) &&
			! el.closest( '#wpadminbar' ) &&
			! el.classList.contains( 'edac-highlight-btn' ) &&
			isVisible( el )
	);
	els.sort( ( a, b ) => {
		const tabA = parseInt( a.getAttribute( 'tabindex' ) ?? '0', 10 );
		const tabB = parseInt( b.getAttribute( 'tabindex' ) ?? '0', 10 );
		const posA = tabA > 0 ? tabA : Infinity;
		const posB = tabB > 0 ? tabB : Infinity;
		if ( posA !== posB ) {
			return posA - posB;
		}
		// eslint-disable-next-line no-bitwise
		return a.compareDocumentPosition( b ) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
	} );
	return els;
}

export function renderTabOrderPanel( panel, onTabOrderClick ) {
	const focusableEls = getFocusableElements();

	if ( focusableEls.length === 0 ) {
		const empty = document.createElement( 'p' );
		empty.className = 'edac-structure-empty';
		empty.textContent = __( 'No focusable elements found on this page.', 'accessibility-checker' );
		panel.append( empty );
		return;
	}

	const list = document.createElement( 'ul' );
	list.className = 'edac-structure-list';
	list.setAttribute( 'role', 'list' );

	focusableEls.forEach( ( el, i ) => {
		const stopNumber = i + 1;
		const type = getElementType( el );
		const name = getAccessibleName( el );
		const hasPositiveTabindex = parseInt( el.getAttribute( 'tabindex' ) ?? '0', 10 ) > 0;

		const li = document.createElement( 'li' );
		li.setAttribute( 'role', 'listitem' );

		const item = document.createElement( 'div' );
		item.className = 'edac-structure-item' + ( hasPositiveTabindex ? ' edac-structure-item--warning' : '' );

		const btn = document.createElement( 'button' );
		btn.className = 'edac-structure-item-btn';
		btn.setAttribute( 'aria-label', sprintf(
			/* translators: %1$s is the tab stop number, %2$s is the element type and name */
			__( 'Navigate to tab stop %1$s: %2$s', 'accessibility-checker' ),
			stopNumber,
			name ? `${ type } - ${ name }` : type
		) );

		const numberSpan = document.createElement( 'span' );
		numberSpan.className = 'edac-structure-item-level edac-structure-item-level--tabstop';
		numberSpan.textContent = `#${ stopNumber }`;
		numberSpan.setAttribute( 'aria-hidden', 'true' );
		btn.append( numberSpan );

		const typeSpan = document.createElement( 'span' );
		typeSpan.className = 'edac-structure-item-taborder-type';
		typeSpan.textContent = type;
		btn.append( typeSpan );

		if ( name ) {
			const nameSpan = document.createElement( 'span' );
			nameSpan.className = 'edac-structure-item-text';
			nameSpan.textContent = name;
			btn.append( nameSpan );
		}

		if ( hasPositiveTabindex ) {
			const warnSpan = document.createElement( 'span' );
			warnSpan.className = 'edac-structure-item-error-icon';
			warnSpan.setAttribute( 'aria-hidden', 'true' );
			btn.append( warnSpan );
		}

		btn.addEventListener( 'click', () => {
			panel.querySelectorAll( '.edac-structure-item-btn--active' ).forEach(
				( b ) => b.classList.remove( 'edac-structure-item-btn--active' )
			);
			btn.classList.add( 'edac-structure-item-btn--active' );
			if ( onTabOrderClick ) {
				onTabOrderClick( el, stopNumber, type );
			} else {
				el.scrollIntoView( { block: 'center' } );
			}
		} );

		item.append( btn );
		li.append( item );
		list.append( li );
	} );

	panel.append( list );
}
