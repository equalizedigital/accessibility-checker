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

		if ( !isRelevant ) {
			return true;
		}

		const nearbyText = getSurroundingText( node, 350 ); // Increased radius
  
		// Check for common transcript-related terms and patterns
		const transcriptTerms = ['transcript', 'transcription', 'text version', 'written version'];
		const hasTranscriptMention = transcriptTerms.some(term =>
			nearbyText.toLowerCase().includes(term)
		);
		
		// Also check if there's an ARIA reference to a transcript
		const ariaDescribedBy = node.getAttribute('aria-describedby');
		const hasAriaReference = ariaDescribedBy &&
			document.getElementById(ariaDescribedBy)?.textContent.toLowerCase().includes('transcript');
			
		if ( !hasTranscriptMention && !hasAriaReference ) {
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
	
	// Include previous sibling which may contain transcript info
	if ( node.previousElementSibling ) {
		text += node.previousElementSibling.textContent.trim() + ' ';
	}
	
	// Check for figcaption if node is within a figure
	const figure = node.closest('figure');
	if ( figure ) {
		const figcaption = figure.querySelector('figcaption');
		if ( figcaption ) {
			text += figcaption.textContent.trim() + ' ';
		}
	}

	// Try to find more specific containing element first
	const parent = node.closest( 'figure, .media-wrapper, section, article, div, body' );
	if ( parent ) {
		// Skip hidden nodes
		const nodeFilter = function(node) {
			const style = window.getComputedStyle(node);
			if ( style.display === 'none' || style.visibility === 'hidden' ) {
				return NodeFilter.FILTER_REJECT;
			}
			return NodeFilter.FILTER_ACCEPT;
		};
		const walker = document.createTreeWalker( parent, NodeFilter.SHOW_TEXT, { acceptNode: nodeFilter }, false );
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
