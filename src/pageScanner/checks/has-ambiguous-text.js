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
];

// The exact string the plugin's own new-window fix appends is injected
// into the page it runs on; read it from the same source the fix reads
// so the rule always matches what was actually appended — including
// translations and strings customized via edac_filter_frontend_fixes_data.
const getInjectedPhrases = () => [
	window.edac_frontend_fixes?.new_window_warning?.localizedString,
	window.anww_localized?.localizedString,
].filter( Boolean );

const normalizeText = ( text ) =>
	text.toLowerCase().replace( /[^a-z]+/g, ' ' ).trim();

const stripBehavioralSuffixes = ( text ) => {
	const suffixes = [ ...behavioralPhrases, ...getInjectedPhrases() ].map( normalizeText );
	let stripped = text;
	let changed = true;
	while ( changed ) {
		changed = false;
		for ( const suffix of suffixes ) {
			if ( stripped !== suffix && stripped.endsWith( ' ' + suffix ) ) {
				stripped = stripped.slice( 0, -( suffix.length + 1 ) ).trim();
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
	text = normalizeText( text );
	if ( ambiguousPhrases.includes( text ) ) {
		return true;
	}
	// A name like "read more, opens a new window" is still ambiguous: the
	// appended text describes behavior, not the link's destination.
	return ambiguousPhrases.includes( stripBehavioralSuffixes( text ) );
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
