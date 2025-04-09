/**
 * Rule to detect the presence of slider components that may require accessibility features.
 * This rule identifies various slider elements based on class names or specific attributes
 * that are commonly associated with slider implementations.
 */

const sliderClassKeywords = [
	'slider',
	'carousel',
	'owl-carousel',
	'soliloquy-container',
	'n2-section-smartslider',
	'metaslider',
	'master-slider',
	'rev_slider',
	'royalSlider',
	'wonderpluginslider',
	'meteor-slides',
	'flexslider',
	'slick-slider',
	'uagb-slick-carousel',
	'swiper-container',
	'flickity-slider',
	'spacegallery',
	'blueimp-gallery',
	'seq-active',
	'siema',
	'keen-slider',
	'bxslider',
	'bx-wrapper',
	'glide--slider',
];

export default {
	id: 'slider_detected',
	evaluate: ( node ) => {
		const className = node.getAttribute( 'class' ) || '';
		const classTokens = className.toLowerCase().split( /\s+/ );
		const matchesClass = sliderClassKeywords.some( ( keyword ) => classTokens.includes( keyword ) );

		const hasDataAttr = node.hasAttribute( 'data-jssor-slider' ) || node.hasAttribute( 'data-layerslider-uid' );

		if ( matchesClass || hasDataAttr ) {
			return false; // Fail check → trigger violation
		}

		return true;
	},
};
