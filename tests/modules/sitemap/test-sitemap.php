<?php
/**
 * Class Test_Sitemap
 *
 * @package 
 */

/**
 * Sitemap test case.
 */

require_once AIOSEOP_UNIT_TESTING_DIR . '/base/class-sitemap-test-base.php';

class Test_Sitemap extends Sitemap_Test_Base {

	public function setUp(){
		parent::init();
		parent::setUp();
	}

	public function tearDown(){
		parent::init();
		parent::tearDown();
	}

	/**
	 * Creates posts and pages and tests whether only pages are being shown in the sitemap.
	 */
	public function test_only_pages() {
		$posts = $this->setup_posts( 2 );
		$pages = $this->setup_posts( 2, 0, 'page' );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'page' );

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap(
			array(
					$pages['without'][0] => true,
					$pages['without'][1] => true,
					$posts['without'][0] => false,
					$posts['without'][1] => false,					
			)
		);
	}

	/**
	 * @requires PHPUnit 5.7
	 * Creates posts with and without featured images and tests whether the sitemap
	 * 1) contains the image tag in the posts that have images attached.
	 * 2) does not contain the image tag in the posts that do not have images attached.
	 */
	public function test_featured_image() {
		$posts = $this->setup_posts( 2, 2 );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		$this->_setup_options( 'sitemap', $custom_options );

		$with = $posts['with'];
		$without = $posts['without'];
		$this->validate_sitemap(
			array(
					$with[0] => array(
						'image'	=> true,
					),
					$with[1] => array(
						'image'	=> true,
					),
					$without[0] => array(
						'image'	=> false,
					),
					$without[1] => array(
						'image'	=> false,
					),
			)
		);
	}

	/**
	 * @requires PHPUnit 5.7
	 * Creates posts with and without featured images and switches OFF the images from the sitemap. Tests that the sitemap does not contain the image tag for any post.
	 */
	public function test_exclude_images() {
		$posts = $this->setup_posts( 2, 2 );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = 'on';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		$this->_setup_options( 'sitemap', $custom_options );

		$with = $posts['with'];
		$without = $posts['without'];
		$this->validate_sitemap(
			array(
					$with[0] => array(
						'image'	=> false,
					),
					$with[1] => array(
						'image'	=> false,
					),
					$without[0] => array(
						'image'	=> false,
					),
					$without[1] => array(
						'image'	=> false,
					),
			)
		);
	}

	/**
	 * @requires PHPUnit 5.7
	 * Creates posts without images and create a shortcode that injects an image into a particlar post. Check if these images are included in the sitemap.
	 *
	 * @dataProvider shortcodeProvider
	 */
	public function test_filter_aioseop_image_shortcodes( $code ) {
		global $shortcode, $post_ids;
		$shortcode = "[$code]";

		// add 2 posts and add the shortcode to the 1st post.
		$post_ids = $this->factory->post->create_many( 2, array( 'post_type' => 'post', 'post_content' => 'content without image', 'post_title' => 'title without image' ) );
		wp_update_post( array( 'ID' => $post_ids[0], 'post_content' => $shortcode ) );

		$urls = array( get_permalink( $post_ids[0] ), get_permalink( $post_ids[1] ) );

		add_shortcode( $code, array( $this, 'aioseop_image_shortcodes_shortcode' ) );

		add_filter( 'aioseop_image_shortcodes', array( $this, 'aioseop_image_shortcodes_filter' ), 10, 2 );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap(
			array(
					$urls[0] => array(
						'image'	=> true,
					),
					$urls[1] => array(
						'image'	=> false,
					),
			)
		);
	}

	/**
	 * Returns the image for the shortcode.
	 */
	public function aioseop_image_shortcodes_shortcode() {
		// inject a dummy image, from the same host.
		return '<img src="' . site_url( '/image.jpg' ) . '"/>';
	}

	/**
	 * Returns the shortcode to use.
	 */
	public function aioseop_image_shortcodes_filter( $dummy, $post_id ) {
		global $shortcode, $post_ids;
		if ( $post_id == $post_ids[0] ) {
			return $shortcode;
		}
		return $dummy;
	}

	/**
	 * Returns the shortcode that injects an image into the content.
	 */
	public function shortcodeProvider() {
		return array(
			array( 'aioseop_image_shortcodes_unittest' ),
		);
	}
}


