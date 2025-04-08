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
const videoClassKeywords = [
	'video', // good general case
	'video-player',
	'wp-block-video',
	'embed-video',
	'responsive-video',
	'html5-video',
	'media-video',
	'vjs-tech', // used by Video.js
];
const videoRoles = [ 'video' ];

export default {
	id: 'video_detected',
	evaluate: ( node ) => {
		const tag = node.nodeName.toLowerCase();
		const src = node.getAttribute( 'src' ) || '';
		const data = node.getAttribute( 'data' ) || '';
		const type = node.getAttribute( 'type' ) || '';
		const role = node.getAttribute( 'role' ) || '';
		const className = node.getAttribute( 'class' ) || '';

		const matchesExtension = videoExtensions.some( ( ext ) => {
			const srcLower = src.toLowerCase();
			const dataLower = data.toLowerCase();
			// Check if the extension is at the end of the string or followed by a query parameter
			return (srcLower.endsWith(ext) || srcLower.includes(ext + '?')) || 
				   (dataLower.endsWith(ext) || dataLower.includes(ext + '?'));
		});

		const matchesKeyword = videoKeywords.some( ( keyword ) =>
			src.toLowerCase().includes( keyword )
		);

		const matchesType = type.toLowerCase().startsWith( 'video/' );

		const matchesClass = videoClassKeywords.some( ( keyword ) =>
			className.toLowerCase().includes( keyword )
		);

		const matchesRole = videoRoles.includes( role.toLowerCase() );

		if (
			tag === 'video' ||
			matchesExtension ||
			matchesKeyword ||
			matchesType ||
			matchesClass ||
			matchesRole
		) {
			return false; // Fail check → trigger violation
		}

		return true; // Pass check → no violation
	},
};
