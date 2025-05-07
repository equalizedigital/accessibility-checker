<?php
/**
 * DOM Wrapper class to provide Simple HTML DOM compatibility
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

/**
 * Wrapper class for DOMDocument to provide Simple HTML DOM interface compatibility
 */
class DOM_Wrapper {
	/**
	 * The wrapped DOMDocument instance
	 *
	 * @var \DOMDocument
	 */
	private $dom;

	/**
	 * Constructor
	 *
	 * @param \DOMDocument $dom The DOMDocument to wrap.
	 */
	public function __construct( \DOMDocument $dom ) {
		$this->dom = $dom;
	}

	/**
	 * Find elements by selector (Simple HTML DOM compatibility method)
	 *
	 * @param string $selector The selector to search for.
	 * @param int    $idx The index to return (optional).
	 * @return array|\DOMElement|null
	 */
	public function find( $selector, $idx = null ) {
		$xpath = new \DOMXPath( $this->dom );
		
		// Convert simple selectors to XPath.
		if ( strpos( $selector, '.' ) === 0 ) {
			// Class selector.
			$query = "//*[contains(@class, '" . substr( $selector, 1 ) . "')]";
		} else {
			// Tag selector.
			$query = "//{$selector}";
		}
		
		$elements = $xpath->query( $query );
		
		if ( null !== $idx ) {
			return $elements->item( $idx );
		}
		
		return iterator_to_array( $elements );
	}

	/**
	 * Get the underlying DOMDocument
	 *
	 * @return \DOMDocument
	 */
	public function get_dom() {
		return $this->dom;
	}
}
