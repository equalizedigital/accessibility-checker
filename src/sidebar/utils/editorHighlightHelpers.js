/**
 * Editor Highlight Helpers
 *
 * Utilities to find and highlight accessibility issue elements
 * within the block editor canvas.
 */

const HIGHLIGHT_CLASS = 'edac-editor-highlight';

/**
 * Decode HTML entities in a string.
 *
 * The issue object field is stored with esc_attr() encoding,
 * so we need to decode entities before parsing.
 *
 * @param {string} html - The potentially HTML-encoded string.
 * @return {string} The decoded string.
 */
const decodeHtmlEntities = ( html ) => {
	const textarea = document.createElement( 'textarea' );
	textarea.innerHTML = html;
	return textarea.value;
};

/**
 * Extract identifying properties from the issue's object HTML.
 *
 * Parses the HTML to find all attributes and text content for
 * matching against elements in the editor canvas.
 *
 * @param {string} objectHtml - The issue's raw object HTML (may be entity-encoded).
 * @return {Object|null} Extracted properties or null if parsing fails.
 */
const extractMatchProps = ( objectHtml ) => {
	const decoded = decodeHtmlEntities( objectHtml );
	const parser = new DOMParser();
	const doc = parser.parseFromString( decoded, 'text/html' );
	const el = doc.body.firstElementChild;

	if ( ! el ) {
		return null;
	}

	const tagName = el.tagName.toLowerCase();
	const props = { tagName };

	// Collect all attributes for comprehensive matching.
	const attrs = {};
	for ( const attr of el.attributes ) {
		attrs[ attr.name ] = attr.value;
	}
	props.attrs = attrs;

	// Also check first-level children for src/href (e.g., img inside figure).
	if ( ! attrs.src ) {
		const childSrc = el.querySelector( '[src]' )?.getAttribute( 'src' );
		if ( childSrc ) {
			props.childSrc = childSrc;
		}
	}

	if ( ! attrs.href ) {
		const childHref = el.querySelector( '[href]' )?.getAttribute( 'href' );
		if ( childHref ) {
			props.childHref = childHref;
		}
	}

	// Extract text content for text-based elements.
	const textContent = el.textContent?.trim();
	if ( textContent ) {
		props.textContent = textContent;
	}

	return props;
};

/**
 * Normalize a URL for comparison by stripping protocol and trailing slashes.
 *
 * @param {string} url - The URL to normalize.
 * @return {string} The normalized URL.
 */
const normalizeUrl = ( url ) => {
	return url
		.replace( /^https?:\/\//, '' )
		.replace( /\/+$/, '' )
		.toLowerCase();
};

const GENERIC_HREFS = new Set( [ '#', '', 'javascript:void(0)', 'javascript:;', 'javascript:void(0);' ] );

/**
 * Score how well a candidate element matches the issue properties.
 *
 * Higher scores indicate a better match. Text content is weighted
 * highest since it's the most unique identifier for an element.
 *
 * @param {Element} candidate  - A DOM element from the editor canvas.
 * @param {Object}  matchProps - Properties extracted from the issue object HTML.
 * @return {number} Match score (0 = no match).
 */
const scoreCandidate = ( candidate, matchProps ) => {
	let score = 0;
	const issueAttrs = matchProps.attrs;

	// Check href match (with URL normalization).
	// Generic hrefs like "#" get a very low score since they're not unique.
	const issueHref = issueAttrs.href || matchProps.childHref;
	const candidateHref = candidate.getAttribute( 'href' );
	if ( issueHref && candidateHref ) {
		const isGeneric = GENERIC_HREFS.has( issueHref.trim() );
		if ( candidateHref === issueHref ) {
			score += isGeneric ? 1 : 10;
		} else if ( normalizeUrl( candidateHref ) === normalizeUrl( issueHref ) ) {
			score += isGeneric ? 1 : 8;
		}
	}

	// Check src match (with URL normalization).
	const issueSrc = issueAttrs.src || matchProps.childSrc;
	const candidateSrc = candidate.getAttribute( 'src' );
	if ( issueSrc && candidateSrc ) {
		if ( candidateSrc === issueSrc ) {
			score += 10;
		} else if ( normalizeUrl( candidateSrc ) === normalizeUrl( issueSrc ) ) {
			score += 8;
		}
	}

	// Check text content match — exact match on trimmed text.
	// Text is the strongest signal for identifying the correct element.
	if ( matchProps.textContent ) {
		const candidateText = candidate.textContent?.trim();
		if ( candidateText === matchProps.textContent ) {
			score += 15;
		}
	}

	// Check other identifying attributes (id, title, alt, role, aria-label, name).
	const attrChecks = [ 'id', 'title', 'alt', 'role', 'aria-label', 'name', 'target', 'rel' ];
	for ( const attr of attrChecks ) {
		if ( issueAttrs[ attr ] && candidate.getAttribute( attr ) === issueAttrs[ attr ] ) {
			score += 2;
		}
	}

	// Check class match for additional disambiguation.
	if ( issueAttrs.class && candidate.getAttribute( 'class' ) ) {
		const issueClasses = issueAttrs.class.split( /\s+/ );
		const candidateClasses = new Set( candidate.getAttribute( 'class' ).split( /\s+/ ) );
		let classMatches = 0;
		for ( const cls of issueClasses ) {
			if ( candidateClasses.has( cls ) ) {
				classMatches++;
			}
		}
		if ( classMatches > 0 ) {
			score += classMatches * 2;
		}
	}

	return score;
};

/**
 * Get the block editor canvas document from the editor iframe.
 *
 * @return {Document|null} The editor canvas document or null if not found.
 */
const getEditorCanvasDocument = () => {
	const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
	if ( iframe?.contentDocument ) {
		return iframe.contentDocument;
	}
	return null;
};

/**
 * Inject the highlight stylesheet into the editor canvas if not already present.
 *
 * @param {Document} canvasDoc - The editor canvas document.
 */
const ensureHighlightStyles = ( canvasDoc ) => {
	if ( canvasDoc.getElementById( 'edac-editor-highlight-styles' ) ) {
		return;
	}

	const style = canvasDoc.createElement( 'style' );
	style.id = 'edac-editor-highlight-styles';
	style.textContent = `
		.${ HIGHLIGHT_CLASS } {
			outline: 4px dashed transparent !important;
			outline-color: #f0f !important;
			outline-offset: 5px !important;
		}
	`;
	canvasDoc.head.appendChild( style );
};

/**
 * Remove any existing editor highlights from the canvas.
 *
 * @param {Document} canvasDoc - The editor canvas document.
 */
const clearEditorHighlights = ( canvasDoc ) => {
	canvasDoc.querySelectorAll( `.${ HIGHLIGHT_CLASS }` ).forEach( ( el ) => {
		el.classList.remove( HIGHLIGHT_CLASS );
	} );
};

/**
 * Find the deepest elements in the canvas whose trimmed text content
 * exactly matches the target text.
 *
 * "Deepest" means we exclude any element whose child also has the same
 * textContent, so we get the most specific match (e.g., the <span> inside
 * an <a>, not the <a> itself — unless the <a> has no children with the same text).
 *
 * @param {Document} canvasDoc - The editor canvas document.
 * @param {string}   text      - The text content to search for.
 * @return {Element[]} Matching elements, deepest-first.
 */
const findDeepestElementsByText = ( canvasDoc, text ) => {
	const all = canvasDoc.body.querySelectorAll( '*' );
	const matches = [];

	for ( const el of all ) {
		if ( el.textContent?.trim() !== text ) {
			continue;
		}

		// Skip if a direct child element also has the same text —
		// we want the deepest/most-specific element.
		let childHasSameText = false;
		for ( const child of el.children ) {
			if ( child.textContent?.trim() === text ) {
				childHasSameText = true;
				break;
			}
		}

		if ( ! childHasSameText ) {
			matches.push( el );
		}
	}

	return matches;
};

/**
 * Find the issue element in the editor canvas iframe.
 *
 * Uses a multi-strategy approach:
 * 1. Text content search across ALL elements (tag-agnostic) — primary strategy
 * 2. Attribute-based search (src, href) — for images and other media
 * 3. Scoring to pick the best match when multiple candidates exist
 *
 * @param {Document} canvasDoc  - The editor canvas document.
 * @param {Object}   matchProps - Properties extracted from the issue object HTML.
 * @return {Element|null} The matching DOM element or null.
 */
const findElementInCanvas = ( canvasDoc, matchProps ) => {
	// Strategy 1: Find by exact text content match (tag-agnostic).
	// This is the most reliable since text survives between frontend and editor
	// even when the tag name or attributes change.
	if ( matchProps.textContent ) {
		const textMatches = findDeepestElementsByText( canvasDoc, matchProps.textContent );

		if ( textMatches.length === 1 ) {
			// Unique text match — walk up to find the closest ancestor matching
			// the issue's tag name, or return the match itself.
			return findBestAncestor( textMatches[ 0 ], matchProps ) || textMatches[ 0 ];
		}

		if ( textMatches.length > 1 ) {
			// Multiple elements with the same text — score each (and their
			// relevant ancestors) to find the best match.
			let bestElement = null;
			let bestScore = 0;

			for ( const match of textMatches ) {
				const ancestor = findBestAncestor( match, matchProps );
				const target = ancestor || match;
				const score = scoreCandidate( target, matchProps );
				if ( score > bestScore ) {
					bestScore = score;
					bestElement = target;
				}
			}

			// If scoring didn't differentiate, return the first text match.
			return bestElement || textMatches[ 0 ];
		}
	}

	// Strategy 2: Attribute-based search for elements without useful text
	// (images, iframes, etc.).
	const candidateSet = new Set();
	const issueSrc = matchProps.attrs?.src || matchProps.childSrc;
	const issueHref = matchProps.attrs?.href || matchProps.childHref;

	if ( issueSrc ) {
		canvasDoc.querySelectorAll( '[src]' ).forEach( ( el ) => {
			candidateSet.add( el );
		} );
	}

	if ( issueHref && ! GENERIC_HREFS.has( issueHref.trim() ) ) {
		canvasDoc.querySelectorAll( '[href]' ).forEach( ( el ) => {
			candidateSet.add( el );
		} );
	}

	// Only search by tag name if we have a specific (non-generic) attribute
	// to pair with it. Otherwise we'd match every <a> or <img> on the page.
	if ( matchProps.tagName && ( issueSrc || ( issueHref && ! GENERIC_HREFS.has( issueHref.trim() ) ) ) ) {
		canvasDoc.querySelectorAll( matchProps.tagName ).forEach( ( el ) => {
			candidateSet.add( el );
		} );
	}

	// Require a minimum score to avoid false positives.
	// A score of 5+ means at least one strong signal matched (specific href, src,
	// or multiple attributes). Generic href alone (score 1) is not enough.
	const MIN_SCORE = 5;
	let bestElement = null;
	let bestScore = 0;

	for ( const candidate of candidateSet ) {
		const score = scoreCandidate( candidate, matchProps );
		if ( score > bestScore ) {
			bestScore = score;
			bestElement = candidate;
		}
	}

	return bestScore >= MIN_SCORE ? bestElement : null;
};

/**
 * Given a deepest text-matching element, walk up the DOM to find the
 * closest ancestor that matches the issue's tag name. This handles cases
 * like <a><span>About</span></a> where the text node is in the <span>
 * but we want to highlight the <a>.
 *
 * Only walks up a limited number of levels to avoid matching a distant wrapper.
 *
 * @param {Element} el         - The deepest text-matching element.
 * @param {Object}  matchProps - Properties extracted from the issue object HTML.
 * @return {Element|null} A matching ancestor, or null if none found nearby.
 */
const findBestAncestor = ( el, matchProps ) => {
	const maxLevels = 3;
	let current = el;
	let levels = 0;

	while ( current && levels <= maxLevels ) {
		if ( current.tagName?.toLowerCase() === matchProps.tagName ) {
			return current;
		}
		current = current.parentElement;
		levels++;
	}

	return null;
};

/**
 * Visually highlight an element in the editor canvas iframe.
 *
 * @param {Document} canvasDoc  - The editor canvas document.
 * @param {Object}   matchProps - Properties extracted from the issue object HTML.
 * @return {boolean} True if the element was found and highlighted.
 */
const highlightElementInCanvas = ( canvasDoc, matchProps ) => {
	ensureHighlightStyles( canvasDoc );
	clearEditorHighlights( canvasDoc );

	const element = findElementInCanvas( canvasDoc, matchProps );

	if ( ! element ) {
		return false;
	}

	element.classList.add( HIGHLIGHT_CLASS );
	element.scrollIntoView( { behavior: 'smooth', block: 'center' } );
	return true;
};

/**
 * Highlight an accessibility issue element in the block editor.
 *
 * Finds the specific element in the editor canvas iframe
 * and applies a visual highlight outline.
 *
 * @param {Object} issue - The issue object containing the object (HTML) field.
 * @return {boolean} True if the element was found and highlighted.
 */
export const highlightIssueInEditor = ( issue ) => {
	if ( ! issue?.object ) {
		return false;
	}

	const matchProps = extractMatchProps( issue.object );
	if ( ! matchProps ) {
		return false;
	}

	// Apply visual highlight in the editor canvas.
	const canvasDoc = getEditorCanvasDocument();
	if ( canvasDoc ) {
		return highlightElementInCanvas( canvasDoc, matchProps );
	}

	return false;
};

/**
 * Clear all editor highlights from the canvas.
 */
export const clearEditorHighlight = () => {
	const canvasDoc = getEditorCanvasDocument();
	if ( canvasDoc ) {
		clearEditorHighlights( canvasDoc );
	}
};

/**
 * Check whether an issue's element can be found in the block editor canvas.
 *
 * @param {Object} issue - The issue object containing the object (HTML) field.
 * @return {boolean} True if the element is found in the editor canvas.
 */
export const canHighlightInEditor = ( issue ) => {
	if ( ! issue?.object ) {
		return false;
	}

	const matchProps = extractMatchProps( issue.object );
	if ( ! matchProps ) {
		return false;
	}

	const canvasDoc = getEditorCanvasDocument();
	if ( ! canvasDoc ) {
		return false;
	}

	return findElementInCanvas( canvasDoc, matchProps ) !== null;
};
