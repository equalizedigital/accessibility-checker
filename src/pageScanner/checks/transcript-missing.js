/**
 * Rule to detect the presence of media elements or links to media files
 * that may require a transcript for accessibility purposes.
 * This checks for the presence of surrounding text mentioning "transcript"
 * to ensure compliance with accessibility guidelines.
 */

const videoEmbedKeywords = [ 'youtube.com', 'youtu.be', 'vimeo.com' ];
const mediaExtensions = /\.(3gp|asf|asx|avi|flv|m4a|m4p|mov|mp3|mp4|mpeg|mpeg2|mpg|mpv|ogg|oga|ogv|qtl|smi|smil|wav|wax|webm|wmv|wmp|wmx)(\?.*)?$/i;

export default {
	id: 'transcript_missing',
	evaluate: ( node ) => {
		const tag = node.nodeName.toLowerCase();
		const href = node.getAttribute( 'href' ) || '';
		const src = node.getAttribute( 'src' ) || '';

		const isMediaLink = tag === 'a' && mediaExtensions.test( href );
		const isVideoEmbed = tag === 'iframe' && videoEmbedKeywords.some( ( keyword ) => src.includes( keyword ) );

		const isRelevant =
			tag === 'audio' ||
			tag === 'video' ||
			isVideoEmbed ||
			isMediaLink;

		if ( ! isRelevant ) {
			return true;
		}

		const nearbyText = getSurroundingText( node, 250 ); // ~25 words

		if ( ! nearbyText.toLowerCase().includes( 'transcript' ) ) {
			return false; // Fail check → missing transcript
		}

		return true; // Pass check
	},
};

function getSurroundingText( node, radius = 250 ) {
	let text = '';

	// Include immediate next sibling
	if ( node.nextElementSibling ) {
		text += node.nextElementSibling.textContent.trim() + ' ';
	}

	// Walk surrounding parent structure
	const parent = node.closest( 'section, article, div, body' );
	if ( parent ) {
		const walker = document.createTreeWalker( parent, NodeFilter.SHOW_TEXT, null, false );
		while ( walker.nextNode() ) {
			if ( text.length >= radius ) {
				break;
			}
			const current = walker.currentNode;
			if ( ! node.contains( current ) ) {
				text += current.textContent.trim() + ' ';
			}
		}
	}

	return text;
}
