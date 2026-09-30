import { __ } from '@wordpress/i18n';

const ambiguousPhrases = [
	__( 'click', 'accessibility-checker' ),
	__( 'click here', 'accessibility-checker' ),
	__( 'here', 'accessibility-checker' ),
	__( 'go here', 'accessibility-checker' ),
	__( 'more', 'accessibility-checker' ),
	__( 'more...', 'accessibility-checker' ),
	__( 'more…', 'accessibility-checker' ),
	__( 'details', 'accessibility-checker' ),
	__( 'more details', 'accessibility-checker' ),
	__( 'link', 'accessibility-checker' ),
	__( 'this page', 'accessibility-checker' ),
	__( 'continue', 'accessibility-checker' ),
	__( 'continue reading', 'accessibility-checker' ),
	__( 'read more', 'accessibility-checker' ),
	__( 'open', 'accessibility-checker' ),
	__( 'download', 'accessibility-checker' ),
	__( 'button', 'accessibility-checker' ),
	__( 'keep reading', 'accessibility-checker' ),
	__( 'learn more', 'accessibility-checker' ),
	__( 'opens a new window', 'accessibility-checker' ),
];

// When testing against translations always run the normalize before the strip.
const normalizePhrase = ( text ) => text
	.normalize( 'NFC' )
	.toLowerCase()
	.replace( /[^\p{L}\p{M}]+/gu, ' ' )
	.trim();

const normalizedPhrases = ambiguousPhrases.map( normalizePhrase );

// Phrases that describe how a link opens rather than where it goes.
// Appended to accessible names by the "Add Label To Links That Open A
// New Tab/Window" fix and by similar theme/plugin features. They add no
// information about the link's purpose, so they are ignored when
// deciding whether a name is ambiguous.
const behavioralPhrases = [
	__( 'opens a new window', 'accessibility-checker' ),
	__( 'opens in a new window', 'accessibility-checker' ),
	__( 'opens a new tab', 'accessibility-checker' ),
	__( 'opens in a new tab', 'accessibility-checker' ),
	__( 'opens new window', 'accessibility-checker' ),
	__( 'opens new tab', 'accessibility-checker' ),
	__( 'opens in new window', 'accessibility-checker' ),
	__( 'opens in new tab', 'accessibility-checker' ),
];

// The exact string the plugin's own new-window fix appends is injected
// into the page it runs on; read it from the same source the fix reads
// so the rule always matches what was actually appended — including
// translations and strings customized via edac_filter_frontend_fixes_data.
const getInjectedPhrases = () => [
	window.edac_frontend_fixes?.new_window_warning?.localizedString,
	window.anww_localized?.localizedString,
].filter( ( phrase ) => typeof phrase === 'string' );

// Strips behavioral phrases from either end of an already-normalized name.
const stripBehavioralPhrases = ( text ) => {
	const phrases = [ ...behavioralPhrases, ...getInjectedPhrases() ]
		.map( normalizePhrase )
		.filter( Boolean );
	let stripped = text;
	let changed = true;
	while ( changed ) {
		changed = false;
		for ( const phrase of phrases ) {
			if ( stripped.endsWith( ' ' + phrase ) ) {
				stripped = stripped.slice( 0, -( phrase.length + 1 ) ).trim();
				changed = true;
			} else if ( stripped.startsWith( phrase + ' ' ) ) {
				stripped = stripped.slice( phrase.length + 1 ).trim();
				changed = true;
			}
		}
	}
	return stripped;
};

const checkAmbiguousPhrase = ( text ) => {
	if ( ! text ) {
		return false;
	}
	text = normalizePhrase( text );
	if ( normalizedPhrases.includes( text ) ) {
		return true;
	}
	// A name like "read more, opens a new window" is still ambiguous: the
	// added text describes behavior, not the link's destination.
	return normalizedPhrases.includes( stripBehavioralPhrases( text ) );
};

export default {
	id: 'has_ambiguous_text',
	evaluate: ( node ) => {
		if ( node.hasAttribute( 'aria-label' ) ) {
			const ariaLabel = node.getAttribute( 'aria-label' );
			return checkAmbiguousPhrase( ariaLabel );
		}

		if ( node.hasAttribute( 'aria-labelledby' ) ) {
			const label = node.getAttribute( 'aria-labelledby' );
			const labelText = document.getElementById( label )?.textContent;
			return checkAmbiguousPhrase( labelText );
		}

		if ( node.textContent && node.textContent !== '' ) {
			return checkAmbiguousPhrase( node.textContent );
		}

		const images = node.querySelectorAll( 'img' );
		for ( const image of images ) {
			const altText = image.getAttribute( 'alt' );
			if ( checkAmbiguousPhrase( altText ) ) {
				return true;
			}
		}

		return false;
	},
};
