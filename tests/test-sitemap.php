<?php
/**
 * Class Test_Sitemap
 *
 * @package 
 */

/**
 * Sitemap test case.
 */

require_once dirname( __FILE__ ) . '/aioseop-test-base.php';

class Test_Sitemap extends AIOSEOP_Unit_Test_Base {

	public function setUp(){
		parent::init();
		parent::setUp();
	}

	public function tearDown(){
		parent::init();
		parent::tearDown();
	}

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

	public function test_schemeless_images() {
		$posts = $this->setup_posts( 2 );

		$id1 = $this->factory->post->create( array( 'post_type' => 'post', 'post_content' => 'content <img src="http://example.org/image1.jpg">', 'post_title' => 'title with image' ) );
		$id2 = $this->factory->post->create( array( 'post_type' => 'post', 'post_content' => 'content <img src="//example.org/image2.jpg">', 'post_title' => 'title with image' ) );
		$id3 = $this->factory->post->create( array( 'post_type' => 'post', 'post_content' => 'content <img src="/image3.jpg">', 'post_title' => 'title with image' ) );
		$posts['with'] = array( get_permalink( $id1 ), get_permalink( $id2 ), get_permalink( $id3 ) );

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
					$with[2] => array(
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

}


