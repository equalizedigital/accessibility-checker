<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Slider Present Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 *
 * Soliloquy: .wp-block-soliloquy-soliloquywp, .soliloquy-container - https://wordpress.org/plugins/soliloquy-lite/
 * Smart Slider 3: .wp-block-nextend-smartslider3, .n2-section-smartslider - https://wordpress.org/plugins/smart-slider-3/
 * MetaSlider: .metaslider - https://wordpress.org/plugins/ml-slider/
 * Master Slider: .master-slider - https://wordpress.org/plugins/master-slider/
 * LayerSlider: .ls-wp-container, [data-layerslider-uid] - https://layerslider.kreaturamedia.com/
 * Revolution Slider: .rev_slider - https://www.sliderrevolution.com/
 * Royal Slider: .royalSlider - https://codecanyon.net/item/royalslider-touch-content-slider-for-wordpress/700256
 * Wonder Slider Lite: .wonderpluginslider - https://wordpress.org/plugins/wonderplugin-slider-lite/
 * Meter Sliders: .meteor-slides - https://wordpress.org/plugins/meteor-slides/
 * FlexSlider: .flexslider - https://woocommerce.com/flexslider/
 * Owl Carousel: .owl-carousel - https://owlcarousel2.github.io/OwlCarousel2/
 * Slick Slider: .slick-slider - https://kenwheeler.github.io/slick/
 * Swiper: .swiper-container - https://swiperjs.com/
 * Flickity: .flickity-slider - https://flickity.metafizzy.co/
 * SpaceGallery: .spacegallery - https://www.eyecon.ro/spacegallery/
 * blueimp: .blueimp-gallery - https://blueimp.github.io/Gallery/
 * Sequencejs: .seq, .seq-active - https://www.sequencejs.com/
 * Siema: .siema - https://pawelgrzybek.github.io/siema/
 * Keen Slider: .keen-slider - https://keen-slider.io/
 * Jssor: [data-jssor-slider] - https://www.jssor.com/
 * bxSlider: .bxslider, .bx-wrapper - https://bxslider.com/
 * Glidejs: .glide--slider - https://glidejs.com/
 */
function edac_rule_slider_present( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom      = $content['html'];
	$errors   = [];
	$elements = $dom->find( '.slider, .carousel, .owl-carousel, .soliloquy-container, .n2-section-smartslider, .metaslider, .master-slider, [data-layerslider-uid], .rev_slider, .royalSlider, .wonderpluginslider, .meteor-slides, .flexslider, .slick-slider, .uagb-slick-carousel, .swiper-container, .flickity-slider, .spacegallery, .blueimp-gallery, .seq-active, .siema, .keen-slider, [data-jssor-slider], .bxslider, .glide--slider' );

	if ( $elements ) {
		foreach ( $elements as $element ) {
			$errors[] = edac_simple_dom_remove_child( $element );
		}
	}
	return $errors;
}
