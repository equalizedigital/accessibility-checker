/**
 * Check to detect the presence of video elements that may require accessibility features.
 * Identifies various video element types including native video elements, embedded players,
 * and elements with video roles.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the node is a video element, false otherwise.
 */

export default {
	id: 'video_element_present',
	evaluate: ( node ) => {
		const tagName = node.tagName.toLowerCase();

		// Check for native video element
		if ( tagName === 'video' ) {
			return true;
		}

		// Check for role="video"
		if ( node.getAttribute( 'role' ) === 'video' ) {
			return true;
		}

		// Check for WordPress video blocks
		if ( node.classList.contains( 'is-type-video' ) ||
			node.closest( '.is-type-video' ) !== null ) {
			return true;
		}

		// Check for embedded video players in iframes
		if ( tagName === 'iframe' ) {
			const src = node.getAttribute( 'src' ) || '';
			const videoServices = [
				'youtube.com', 'youtu.be',
				'vimeo.com',
				'dailymotion.com',
				'jwplayer',
				'brightcove',
				'wistia',
				'videopress',
				'player.twitch.tv',
			];

			return videoServices.some( ( service ) => src.toLowerCase().includes( service ) );
		}

		// Check for object elements with video type
		if ( tagName === 'object' ) {
			const type = node.getAttribute( 'type' ) || '';
			return type.includes( 'video' );
		}

		// Check for elements with src attribute containing video file extensions
		const src = node.getAttribute( 'src' ) || '';
		if ( src ) {
			const videoExtensions = [
				'.3gp', '.asf', '.asx', '.avi', '.flv', '.m4p', '.mov',
				'.mp4', '.mpeg', '.mpeg2', '.mpg', '.mpv', '.ogg', '.ogv',
				'.qtl', '.smi', '.smil', '.wax', '.webm', '.wmv', '.wmp', '.wmx',
			];

			for ( const ext of videoExtensions ) {
				if ( src.toLowerCase().endsWith( ext ) ) {
					return true;
				}
			}
		}

		// Check for common video player class names
		const videoClasses = [
			'video-player', 'video-container', 'video-wrapper',
			'plyr', 'mediaelement', 'videojs', 'wp-video',
		];

		return videoClasses.some( ( className ) =>
			node.classList.contains( className ) ||
			( node.className && node.className.includes( className ) )
		);
	},
};
