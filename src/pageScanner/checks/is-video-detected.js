/**
 * Check to detect the presence of video elements that may require accessibility features.
 * Identifies various video element types including native video elements, embedded players,
 * and elements with video roles.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the node is a video element (triggering violation), false otherwise (no violation).
 */

const videoExtensions = [
	'.3gp', '.asf', '.asx', '.avi', '.flv', '.m4p', '.mov', '.mp4', '.mpeg', '.mpeg2',
	'.mpg', '.mpv', '.ogg', '.ogv', '.qtl', '.smi', '.smil', '.wax', '.webm', '.wmv',
	'.wmp', '.wmx',
];

const videoKeywords = [ 'youtube', 'youtu.be', 'vimeo' ];
const videoRoles = [ 'video' ];

export default {
	id: 'is_video_detected',
	evaluate: ( node ) => {
		const tag = node.nodeName.toLowerCase();
		const src = node.getAttribute( 'src' ) || '';
		const data = node.getAttribute( 'data' ) || '';
		const type = node.getAttribute( 'type' ) || '';
		const role = node.getAttribute( 'role' ) || '';

		// Check for iframe with video source
		if ( tag === 'iframe' && src ) {
			const iframeSrcLower = src.toLowerCase();
			if ( videoKeywords.some( ( keyword ) => iframeSrcLower.includes( keyword ) ) ) {
				return true; // Fail check → trigger violation
			}
		}

		// .ogg is the Ogg Vorbis audio extension, but the Ogg container can also carry
		// Theora video, so it stays in videoExtensions. To avoid flagging ordinary Ogg
		// Vorbis audio (e.g. the Gutenberg Audio block), only count a .ogg match as video
		// when it isn't attached to an <audio> element or one of its <source> children.
		const isInAudioContainer = tag === 'audio' || ( tag === 'source' &&
			node.parentNode &&
			node.parentNode.nodeName.toLowerCase() === 'audio' );

		const srcLower = src.toLowerCase();
		const dataLower = data.toLowerCase();

		const matchesExtension = videoExtensions.some( ( ext ) => {
			// Check if the extension is at the end of the string or followed by a query parameter
			const matches = (
				( srcLower.endsWith( ext ) || srcLower.includes( ext + '?' ) ) ||
				( dataLower.endsWith( ext ) || dataLower.includes( ext + '?' ) )
			);

			if ( matches && ext === '.ogg' && isInAudioContainer ) {
				return false;
			}

			return matches;
		} );

		const matchesKeyword = videoKeywords.some( ( keyword ) =>
			srcLower.includes( keyword )
		);

		const matchesType = type.toLowerCase().startsWith( 'video/' );

		const matchesRole = videoRoles.includes( role.toLowerCase() );

		// Check for source elements with video type
		if ( tag === 'source' ) {
			const parentNode = node.parentNode;
			// Skip detection if this source is already inside a video element
			// to avoid double counting the same video content
			if ( parentNode && parentNode.nodeName.toLowerCase() === 'video' ) {
				return false; // Don't count as a separate violation
			}
			if ( matchesType || matchesExtension ) {
				return true; // Fail check → trigger violation
			}
		}

		if (
			tag === 'video' ||
			matchesExtension ||
			matchesKeyword ||
			matchesType ||
			matchesRole
		) {
			return true; // Fail check → trigger violation
		}

		// No video found by this point.
		return false; // Pass check → no violation
	},
};
