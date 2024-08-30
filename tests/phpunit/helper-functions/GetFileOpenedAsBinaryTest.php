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
	 * Test if file gets retrieved and opened as binary.
	 *
	 * @group external-http
	 */
	public function test_file_opened_as_binary() {
		$file = 'https://via.placeholder.com/100/000000/FFFFFF/?text=testimage';

		$fh = edac_get_file_opened_as_binary( $file );

		// since this external service can be flaky we try again if it fails.
		if ( ! $fh ) {
			$fh = edac_get_file_opened_as_binary( $file );
		}

		$this->assertNotFalse( $fh );
		fclose( $fh );
	}

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
