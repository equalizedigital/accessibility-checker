/**
 * Check to detect the presence of video elements that may require accessibility features.
 * Identifies various video element types including native video elements, embedded players,
 * and elements with video roles.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the node is a video element, false otherwise.
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

		const matchesExtension = videoExtensions.some( ( ext ) =>
			src.toLowerCase().includes( ext ) || data.toLowerCase().includes( ext )
		);

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
