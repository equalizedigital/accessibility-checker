/**
 * Rule to detect the presence of media elements or links to media files
 * that may require a transcript for accessibility purposes.
 * This checks for the presence of surrounding text mentioning "transcript"
 * to ensure compliance with accessibility guidelines.
 */

const videoEmbedKeywords = [ 'youtube.com', 'youtu.be', 'vimeo.com' ];
const mediaExtensions = /\.(3gp|asf|asx|avi|flv|m4a|m4p|mov|mp3|mp4|mpeg|mpeg2|mpg|mpv|ogg|oga|ogv|qtl|smi|smil|wav|wax|webm|wmv|wmp|wmx)(\?.*)?$/i;

const MAX_SIBLINGS_TO_CHECK = 5;
const PARENT_SIBLING_LIMIT = 3;

export default {
	id: 'has_transcript',
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

		const nearbyText = getSurroundingText( node, 350 ); // Increased radius

		// Check for common transcript-related terms and patterns
		const transcriptTerms = [ 'transcript', 'transcription', 'text version', 'written version' ];
		const hasTranscriptMention = transcriptTerms.some( ( term ) =>
			nearbyText.toLowerCase().includes( term )
		);

		// Also check if there's an ARIA reference to a transcript
		const ariaDescribedBy = node.getAttribute( 'aria-describedby' );
		const hasAriaReference = ariaDescribedBy &&
			document.getElementById( ariaDescribedBy )?.textContent?.toLowerCase().includes( 'transcript' );

		if ( ! hasTranscriptMention && ! hasAriaReference ) {
			return false; // Fail check → missing transcript
		}

		return true; // Pass check
	},
};

/**
 * Helper function to collect text content from a series of sibling elements
 * @param {Element} startSibling - The first sibling element to start from
 * @param {number}  maxCount     - Maximum number of siblings to check
 * @return {string} - Concatenated text content from siblings
 */
function collectSiblingText( startSibling, maxCount ) {
	let text = '';
	let currentSibling = startSibling;
	let siblingCount = 0;

	while ( currentSibling && siblingCount < maxCount ) {
		const siblingText = currentSibling.textContent?.trim();
		if ( siblingText ) {
			text += siblingText + ' ';
		}
		currentSibling = currentSibling.nextElementSibling;
		siblingCount++;
	}

	return text;
}

function getSurroundingText( node, radius = 250 ) {
	let text = '';

	// Include immediate next and previous siblings (but skip noscript elements)
	if ( node.previousElementSibling && node.previousElementSibling.nodeName.toLowerCase() !== 'noscript' ) {
		text += node.previousElementSibling.textContent.trim() + ' ';
	}
	if ( node.nextElementSibling && node.nextElementSibling.nodeName.toLowerCase() !== 'noscript' ) {
		text += node.nextElementSibling.textContent.trim() + ' ';
	}

	// Include figcaption if inside figure
	const figure = node.closest( 'figure' );
	if ( figure ) {
		const figcaption = figure.querySelector( 'figcaption' );
		if ( figcaption ) {
			text += figcaption.textContent.trim() + ' ';
		}

		// Check siblings of the figure element for transcript links
		text += collectSiblingText( figure.nextElementSibling, MAX_SIBLINGS_TO_CHECK );
	}

	// Walk limited DOM subtree (media-wrapper, section, article, etc.)
	const parent = node.closest( '.media-wrapper, figure, section, article' );
	if ( parent ) {
		// Also check parent's siblings for transcript text
		text += collectSiblingText( parent.nextElementSibling, PARENT_SIBLING_LIMIT );

		const nodeFilter = {
			acceptNode( textNode ) {
				const style = window.getComputedStyle( textNode.parentElement );
				if ( ! style || style.display === 'none' || style.visibility === 'hidden' ) {
					return NodeFilter.FILTER_REJECT;
				}
				return NodeFilter.FILTER_ACCEPT;
			},
		};
		const walker = document.createTreeWalker( parent, NodeFilter.SHOW_TEXT, nodeFilter, false );

		while ( walker.nextNode() ) {
			const current = walker.currentNode;
			const content = current.textContent.trim();

			// Skip if it's the media link itself
			if ( ! node.contains( current ) && content.length ) {
				text += content + ' ';
			}

			if ( text.length >= radius ) {
				// Ensure we don't cut off in the middle of a word
				const lastSpaceIndex = text.lastIndexOf( ' ', radius );
				if ( lastSpaceIndex !== -1 ) {
					text = text.substring( 0, lastSpaceIndex );
				}
				break;
			}
		}
	}

	return text.toLowerCase().trim();
}
