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
	 *
	 * Function: Adds a post with a long content and title and checks whether the meta tags are being truncated correctly
	 * Expected: The meta tags are being truncated according to the limits imposed.
	 * Actual: Currently works as expected.
	 * Reproduce: Insert a post and check the length of the meta tags content.
	 *
	 * @since 3.0
	 */
	public function test_meta_tag_truncation() {
		$tag_limits  = array(
			'og:description'    => 200,
			'twitter:description'   => 200,
			'twitter:title' => 70,
		);

		$id = $this->factory->post->create( array( 'post_title' => 'seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo', 'post_content' => 'seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo') );

		wp_set_current_user( 1 );

		$options = get_option( 'aioseop_options' );
		$options['aiosp_cpostactive'] = array( 'post' );
		update_option( 'aioseop_options', $options );

		$custom_options = array();
		$custom_options['aiosp_opengraph_types'] = array( 'post' );
		$custom_options['aiosp_opengraph_generate_descriptions'] = 'on';
		$this->_setup_options( 'opengraph', $custom_options );

		$meta = $this->parse_html( get_permalink( $id ), array( 'meta' ) );

		// should have atleast one meta tag.
		$this->assertGreaterThan( 1, count( $meta ) );

		foreach ( $meta as $m ) {
			$tag = isset( $m['property'] ) ? $m['property'] : $m['name'];
			if ( empty( $tag ) ) {
				continue;
			}
			if ( array_key_exists( $tag, $tag_limits ) ) {
				$this->assertLessThanOrEqual( $tag_limits[ $tag ], strlen( $m['content'] ) );
			}
		}
	}


	/**
	 * Checks whether the meta tag filter to disable truncation is running correctly.
	 *
	 * Function: Adds a post with a long content and title and checks whether the meta tags are being truncated correctly except for the meta tag that's not being truncated.
	 * Expected: The meta tags are being truncated according to the limits imposed, except for the meta tag that's not being truncated.
	 * Actual: Currently works as expected.
	 * Reproduce: Insert a post and check the length of the meta tags content.
	 *
	 * @since 3.0
	 */
	public function test_meta_tag_truncation_filter() {
		$tag_limits  = array(
			'og:description'    => 200,
			'twitter:description'   => 200,
			'twitter:title' => array( 70 ),
		);

		$id = $this->factory->post->create( array( 'post_title' => 'seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo', 'post_content' => 'seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo seo') );

		wp_set_current_user( 1 );

		$options = get_option( 'aioseop_options' );
		$options['aiosp_cpostactive'] = array( 'post' );
		update_option( 'aioseop_options', $options );

		$custom_options = array();
		$custom_options['aiosp_opengraph_types'] = array( 'post' );
		$custom_options['aiosp_opengraph_generate_descriptions'] = 'on';
		$this->_setup_options( 'opengraph', $custom_options );

		add_filter( 'aiosp_opengraph_disable_meta_tag_truncation', array( $this, 'filter_disable_meta_tag_truncation' ), 10, 4 );

		$meta = $this->parse_html( get_permalink( $id ), array( 'meta' ) );

		// should have atleast one meta tag.
		$this->assertGreaterThan( 1, count( $meta ) );

		foreach ( $meta as $m ) {
			$tag = isset( $m['property'] ) ? $m['property'] : $m['name'];
			if ( empty( $tag ) ) {
				continue;
			}
			if ( array_key_exists( $tag, $tag_limits ) ) {
				if ( is_array( $tag_limits[ $tag ] ) ) {
					$this->assertGreaterThan( $tag_limits[ $tag ][0], strlen( $m['content'] ) );
				} else {
					$this->assertLessThanOrEqual( $tag_limits[ $tag ], strlen( $m['content'] ) );
				}
			}
		}
	}

	/**
	 * Implements the filter to disable truncation of a particular meta tag.
	 */
	function filter_disable_meta_tag_truncation( $disable, $network, $meta_tag, $network_meta_tag ) {
		switch ( $network_meta_tag ) {
			case 'twitter:title':
				$disable = true;
				break;
		}
		return $disable;
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
