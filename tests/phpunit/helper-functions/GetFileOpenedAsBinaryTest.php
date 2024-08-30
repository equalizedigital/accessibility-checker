<?php
/**
 * Tests the opening of image files as binary.
 *
 * @package Accessibility_Checker
 */

/**
 * Tests the function that opens a file as binary - used when analyzing images.
 *
 * @group helper-functions
 */
class GetFileOpenedAsBinaryTest extends WP_UnitTestCase {

	/**
	 * Test if file is not opened as binary.
	 *
	 * @group external-http
	 */
	public function test_file_not_opened_as_binary() {
		$file = 'https://postman-echo.com/status/404';

		$fh = edac_get_file_opened_as_binary( $file );

		$this->assertFalse( $fh );
	}
}
