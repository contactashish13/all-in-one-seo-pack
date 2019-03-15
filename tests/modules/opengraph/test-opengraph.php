<?php
/**
 * Class Test_Opengraph
 *
 * @package
 */

require_once AIOSEOP_UNIT_TESTING_DIR . '/base/class-aioseop-test-base.php';

/**
 * Opengraph Testcase
 */
class Test_Opengraph extends AIOSEOP_Test_Base {

	public function setUp() {
		$this->init( true );
	}

	/**
	 * Checks whether the meta tags are being truncated correctly.
	 */
	public function test_meta_tag_truncation() {
		$this->markTestIncomplete( 'Cannot seem to get any social meta tag when accessing the page... WIP' );

		$tag_limits  = array(
			'og:description'    => 55,
			'twitter:description'   => 200,
			'twitter:title' => 70,
		);

		wp_set_current_user( 1 );
		global $aioseop_options;

		$id = $this->factory->post->create( array( 'post_title' => 'seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo', 'post_content' => 'seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo') );

		$aioseop_options['aiosp_cpostactive'] = array( 'post' );
		update_option( 'aioseop_options', $aioseop_options );

		$custom_options = array();
		$custom_options['aiosp_opengraph_types'] = array( 'post' );
		$this->_setup_options( 'opengraph', $custom_options );

		$meta = $this->parse_html( get_permalink( $id ), array( 'meta' ) );

		print_r( $meta );

		// should have atleast one meta tag.
		$this->assertGreaterThan( 1, count( $meta ) );

		foreach ( $meta as $m ) {
			if ( ! isset( $m['property'] ) ) {
				continue;
			}
			if ( array_key_exists( $m['property'], $tag_limits ) ) {
				error_log( $m['property'] . ' == ' . strlen( $m['content'] ) );
				$this->assertLessThanOrEqual( $tag_limits[ $m['property'] ], strlen( $m['content'] ) );
			}
		}
	}

	/**
	 * Checks the home page's meta tags.
	 *
	 * @dataProvider metaProvider
	 */
	public function test_home_page( $title_meta, $desc_meta ) {
		$this->markTestIncomplete( 'Cannot seem to get any meta tag when accessing the home page. Have set home page as static page as well as showing latest posts. Nothing works.' );

		$id = $this->factory->post->create( array('post_type' => 'page') );
		$home_url = get_site_url();// trailingslashit( get_site_url() ) . 'house';
		// update_option( 'home', $home_url );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $id );

		$custom_options = array();
		$custom_options['aiosp_opengraph_hometitle'] = $title_meta;
		$custom_options['aiosp_opengraph_description'] = $desc_meta;

		$this->_setup_options( 'opengraph', $custom_options );

		do_action( 'init' );
		$meta = $this->parse_html( $home_url, array( 'meta' ) );

		print_r( $meta );

		// should have atleast one meta tag.
		$this->assertGreaterThan( 1, count( $meta ) );

		$title = null;
		$desc = null;
		foreach ( $meta as $m ) {
			if ( ! isset( $m['property'] ) ) {
				continue;
			}
			if ( 'og:title' === $m['property'] ) {
				$title = $m['content'];
				break;
			}
			if ( 'og:description' === $m['property'] ) {
				$desc = $m['content'];
				break;
			}
		}
		$this->assertEquals( $title_meta, $title );
		$this->assertEquals( $desc_meta, $desc );
		$this->assertContains( '&', $desc );
		$this->assertNotContains( '&amp;', $desc );
		$this->assertContains( '&', $title );
		$this->assertNotContains( '&amp;', $title );
	}

	public function metaProvider() {
		return array(
			array( 'Half & Half', 'Two & a Half men' ),
		);
	}
}
