<?php
/**
 * Class Test_Robots
 *
 * @package
 */

/**
 * Robots test case.
 */

require_once AIOSEOP_UNIT_TESTING_DIR . '/base/class-aioseop-test-base.php';

class Test_Robots extends AIOSEOP_Test_Base {

	public function setUp() {
		parent::ajaxSetUp();
	}

	public function ajaxTearDown() {
		parent::ajaxTetUp();
	}

	private function create_file() {
		if ( $this->check_file_exists() ) {
			$this->delete_file();
		}

		// create a file.
		$rule       = "User-agent: Googlebot\r\nDisallow: /wow-test-folder/";

		$file   = fopen( ABSPATH . '/robots.txt', 'w' );
		fwrite( $file, $rule );
		fclose( $file );
	}

	private function check_file_exists() {
		return file_exists( ABSPATH . '/robots.txt' );
	}

	private function delete_file() {
		@unlink( ABSPATH . '/robots.txt' );
	}

	/**
	 * Importing a physical robots.txt file.
	 */
	public function test_import_physical_file() {
		$this->_setRole( 'administrator' );

		$this->create_file();

		$this->_setup_options( 'robots', array() );

		$_POST = array(
			'nonce-aioseop' => wp_create_nonce( 'aioseop-nonce' ),
			'settings'      => ' ',
			'options'       => 'import',
		);

		try {
			$this->_handleAjax( 'aioseop_ajax_robots_physical' );
		} catch ( WPAjaxDieContinueException  $e ) {
			// We expected this, do nothing.
		} catch ( WPAjaxDieStopException $ee ) {
			// We expected this, do nothing.
		}

		// now the file should not exist.
		$this->assertFalse( $this->check_file_exists(), 'Physical robots.txt not deleted' );

		$aioseop_options = get_option( 'aioseop_options' );
		$rules = $aioseop_options['modules']['aiosp_robots_options']['aiosp_robots_rules'];

		$this->assertEquals( 1, count( $rules ) );
		$this->assertArrayHasKey( 'path', $rules[0], 'Rules not imported from physical robots.txt' );
		$this->assertEquals( '/wow-test-folder/', $rules[0]['path'], 'Rules not imported from physical robots.txt' );
	}

	/**
	 * Importing a physical robots.txt file.
	 */
	public function test_delete_physical_file() {
		$this->_setRole( 'administrator' );

		$this->create_file();

		$this->_setup_options( 'robots', array() );

		$_POST = array(
			'nonce-aioseop' => wp_create_nonce( 'aioseop-nonce' ),
			'settings'      => ' ',
			'options'       => 'delete',
		);

		try {
			$this->_handleAjax( 'aioseop_ajax_robots_physical' );
		} catch ( WPAjaxDieContinueException  $e ) {
			// We expected this, do nothing.
		} catch ( WPAjaxDieStopException $ee ) {
			// We expected this, do nothing.
		}

		// now the file should not exist.
		$this->assertFalse( $this->check_file_exists(), 'Physical robots.txt not deleted' );

		$aioseop_options = get_option( 'aioseop_options' );
		$rules = $aioseop_options['modules']['aiosp_robots_options']['aiosp_robots_rules'];

		$this->assertEquals( 0, count( $rules ) );
	}
}
