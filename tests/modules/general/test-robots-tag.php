<?php
/**
 * Class Test_Robots_Tag
 *
 * @package 
 */

require_once AIOSEOP_UNIT_TESTING_DIR . '/base/class-aioseop-test-base.php';

class Test_Robots_Tag extends AIOSEOP_Test_Base {

	public function setUp() {
		$this->init( true );
	}

	/**
	 * Tests the category archive page for different values of the FOLLOW and INDEX configurations.
	 *
	 * @param string	$noindex				The post type to enable for noindex.
	 * @param string	$nofollow				The post type to enable for nofollow.
	 * @param array		$additional_key_value	Any additional configuration item to set ([0] = item name, [1] = item value).
	 * @param string	$result					The expected result.
	 *
	 * @dataProvider	categoryDataProvider
	 */
	public function test_category_archive( $noindex, $nofollow, array $additional, $result ) {
		wp_set_current_user( 1 );

		$cat_id	= wp_create_category( "wow" );
		if ( $noindex ) {
			$noindex_id = $this->factory->post->create( array( 'post_type' => $noindex, 'post_title' => 'hey1', 'post_content' => 'heyheyhey1' ) );
			wp_set_post_categories( $noindex_id, array( $cat_id ), false );
		}
		if ( $nofollow ) {
			$nofollow_id = $this->factory->post->create( array( 'post_type' => $nofollow, 'post_title' => 'hey2', 'post_content' => 'heyheyhey2' ) );
			wp_set_post_categories( $nofollow_id, array( $cat_id ), false );
		}

		$url	= get_category_link( $cat_id );

		global $aioseop_options;
		$aioseop_options['aiosp_cpostnoindex'] = array( $noindex );
		$aioseop_options['aiosp_cpostnofollow'] = array( $nofollow );
		if ( $additional ) {
			$aioseop_options[ $additional[0] ] = $additional[1];
		}
		update_option( 'aioseop_options', $aioseop_options );

		$meta = $this->parse_html( $url, array( 'meta' ) );

		// should have atleast one meta tag.
		$this->assertGreaterThan( 0, count( $meta ) );

		$names = wp_list_pluck( $meta, 'name' );
		$this->assertContains( 'robots', $names );

		$robots = null;
		foreach ( $meta as $m ) {
			if ( 'robots' === $m['name'] ) {
				$robots = $m['content'];
				break;
			}
		}
		$this->assertEquals( $result, $robots );
	}

	/**
	 * Provides data for testing category archives.
	 */
	public function categoryDataProvider() {
		return array(
			array( '', 'post', array( 'aiosp_category_noindex', 'on' ), 'noindex,nofollow' ),
			array( 'post', 'post', array( 'aiosp_category_noindex', 'on' ), 'noindex,nofollow' ),
			array( '', 'post', array( 'aiosp_category_noindex', '' ), 'index,nofollow' ),
			array( 'post', 'post', array( 'aiosp_category_noindex', '' ), 'noindex,nofollow' ),
		);
	}
}