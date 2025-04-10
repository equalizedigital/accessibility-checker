/**
 * Check to detect the presence of video elements that may require accessibility features.
 * Identifies various video element types including native video elements, embedded players,
 * and elements with video roles.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} False if the node is a video element (triggering violation), true otherwise (no violation).
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

		const matchesExtension = videoExtensions.some( ( ext ) => {
			const srcLower = src.toLowerCase();
			const dataLower = data.toLowerCase();
			// Check if the extension is at the end of the string or followed by a query parameter
			return (
				( srcLower.endsWith( ext ) || srcLower.includes( ext + '?' ) ) ||
				( dataLower.endsWith( ext ) || dataLower.includes( ext + '?' ) )
			);
		} );

		const matchesKeyword = videoKeywords.some( ( keyword ) =>
			src.toLowerCase().includes( keyword )
		);

		const matchesType = type.toLowerCase().startsWith( 'video/' );

		const matchesRole = videoRoles.includes( role.toLowerCase() );

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
