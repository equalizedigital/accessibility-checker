<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * EDAC DOM Class
 */
class EDAC_Dom extends simple_html_dom {

	/** @var array $video_ext video extensions. */
	protected $video_ext = array(
		'.3gp',
		'.asf',
		'.asx',
		'.avi',
		'.flv',
		'.m4p',
		'.mov',
		'.mpeg',
		'.mpeg2',
		'.mpg',
		'.mpv',
		'.ogg',
		'.ogv',
		'.qtl',
		'.smi',
		'.smil',
		'.wax',
		'.webm',
		'.wmv',
		'.wmp',
		'.wmx',
	);

	/** @var array $audio_ext audio extensions. */
	protected $audio_ext = array(
		'.aif',
		'.aiff',
		'.m4a',
		'.mp2',
		'.mp3',
		'.mp4',
		'.mpa',
		'.ra',
		'.ram',
		'.wav',
		'.wma',
	);

	/** @var array $embed_sources embed source urls. */
	protected $embed_sources = array(
		'mixcloud.com',
		'reverbnation.com',
		'screencast.com',
		'soundcloud.com',
		'spotify.com',
		'ted.com',
		'tiktok.com',
		'youtube.com',
		'youtu.be',
		'videopress.com',
		'vimeo.com',
	);

	/**
	 * Convert Tag to Marker
	 *
	 * @param array $tags array of tags.
	 * @return void
	 */
	public function convert_tag_to_marker( $tags ) {
		$elements = array();

		foreach ( $tags as $tag ) {
			$elements = array_merge( $elements, $this->find( $tag ) );
		}

		foreach ( $elements as $element ) {
			$element->innertext = '[' . $element->tag_start . '_ac_element]';
		}
	}

	/**
	 * Text around element contains
	 *
	 * @param obj     $element object for element.
	 * @param string  $contains value contains.
	 * @param integer $distance_after_element number.
	 * @return int
	 */
	public function text_around_element_contains( $element, $contains, $distance_after_element = 25 ) {
		// to account for the start of the search term getting cut off add the length of the search to the distance.
		$total_distance = $distance_after_element + strlen( $contains );
		$marker = $element->plaintext;
		$tag_end = stripos( $this->plaintext, $marker ) + strlen( $marker );
		$next_marker_position = stripos( $this->plaintext, 'ac_element', $tag_end ) ?: strlen( $this->plaintext );
		$found_position = stripos( $this->plaintext, $contains, $tag_end );

		if ( false === $found_position || $found_position > $next_marker_position ) {
			return false;
		}

		$distance = $found_position - $tag_end;

		return $distance < $total_distance;

	}

	/**
	 * Find Media Embeds
	 *
	 * @param boolean $include_audio boolean to include audio.
	 * @return array
	 */
	public function find_media_embeds( $include_audio = true ) {
		// all elements with sources.
		$elements_with_src = $this->find( '[src]' );
		$elements = array();
		$audio = $include_audio ? $this->audio_ext : array();
		$extensions = array_merge( $this->video_ext, $this->embed_sources, $audio );
		if ( $elements_with_src ) {
			$elements = array_filter(
				$elements_with_src,
				function( $element ) use ( $extensions ) {
					$count = 0;
					str_ireplace( $extensions, '', $element->getAttribute( 'src' ), $count );
					return $count > 0;
				}
			);
		}

		return array_merge( $elements, $this->find( '.is-type-video' ) );
	}

	/**
	 * Find Linked Media
	 *
	 * @param boolean $include_audio boolean to include audio.
	 * @return array
	 */
	public function find_linked_media( $include_audio = true ) {
		$elements_with_href = $this->find( '[href]' );
		$elements = array();
		$audio = $include_audio ? $this->audio_ext : array();
		$extensions = array_merge( $this->video_ext, $audio );
		if ( $elements_with_href ) {
			$elements = array_filter(
				$elements_with_href,
				function( $element ) use ( $extensions ) {
					$count = 0;
					str_ireplace( $extensions, '', $element->getAttribute( 'href' ), $count );
					return $count > 0;
				}
			);
		}

		return $elements;
	}

}
