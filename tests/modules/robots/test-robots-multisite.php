<?php
/**
 * Class Test_Robots_Multisite
 *
 * @package
 */

require_once AIOSEOP_UNIT_TESTING_DIR . '/modules/robots/test-robots.php';

/**
 * Robots test case for multisite.
 */
class Test_Robots_Multisite extends Test_Robots {

	/**
	 * Test.
	 */
	public function test_test() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Only for multi site' );
		}

		$this->assertTrue(false);
	}

}