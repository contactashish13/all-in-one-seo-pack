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

	/**
	 * @var array $_urls Stores the external pages that need to be added to the sitemap.
	 */
	private $_urls;

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
	 * Tests posts with and without images with dependency on jetpack gallery.
	 *
	 * @ticket 1230 XML Sitemap - Add support for images in JetPack and NextGen galleries
	 */
	public function test_jetpack_gallery() {
		$this->markTestSkipped( 'Skipping this till actual use case is determined.' );
		
		$jetpack = 'jetpack/jetpack.php';
		$file = dirname( dirname( AIOSEOP_UNIT_TESTING_DIR ) ) . '/';

		if ( ! file_exists( $file . $jetpack ) ) {
			$this->markTestSkipped( 'JetPack not installed. Skipping.' );
		}

		$this->plugin_to_load = $file . $jetpack;
		tests_add_filter( 'muplugins_loaded', array( $this, 'filter_muplugins_loaded' ) );

		activate_plugin( $jetpack );

		if ( ! is_plugin_active( $jetpack ) ) {
			$this->markTestSkipped( 'JetPack not activated. Skipping.' );
		}

		$posts = $this->setup_posts( 1, 1 );

		// create 4 attachments.
		$attachments = array();
		for ( $x = 0; $x < 4; $x++ ) {
			$attachments[] = $this->upload_image_and_maybe_attach( str_replace( '\\', '/', AIOSEOP_UNIT_TESTING_DIR . '/resources/images/footer-logo.png' ) );
		}

		$id = $this->factory->post->create( array( 'post_type' => 'post', 'post_content' => '[gallery size="medium" link="file" columns="5" type="slideshow" ids="' . implode( ',', $attachments ) . '"]', 'post_title' => 'jetpack' ) );
		$posts['with'][] = get_permalink( $id );

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
			)
		);
	}

	/**
	 * @requires PHPUnit 5.7
	 * Tests posts with and without images with dependency on nextgen gallery.
	 *
	 * @ticket 1230 XML Sitemap - Add support for images in JetPack and NextGen galleries
	 */
	public function test_nextgen_gallery() {
		$nextgen = 'nextgen-gallery/nggallery.php';
		$file = dirname( dirname( AIOSEOP_UNIT_TESTING_DIR ) ) . '/';
		
		if ( ! file_exists( $file . $nextgen ) ) {
			$this->markTestSkipped( 'NextGen Gallery not installed. Skipping.' );
		}

		$this->plugin_to_load = $file . $nextgen;
		tests_add_filter( 'muplugins_loaded', array( $this, 'filter_muplugins_loaded' ) );

		activate_plugin( $nextgen );

		if ( ! is_plugin_active( $nextgen ) ) {
			$this->markTestSkipped( 'NextGen Gallery not activated. Skipping.' );
		}

		$posts = $this->setup_posts( 1, 1 );

		// create 4 attachments.
		$attachments = array();
		for ( $x = 0; $x < 4; $x++ ) {
			$attachments[] = $this->upload_image_and_maybe_attach( str_replace( '\\', '/', AIOSEOP_UNIT_TESTING_DIR . '/resources/images/footer-logo.png' ) );
		}

		$shortcode = '[ngg_images image_ids="' . implode( ',', $attachments ) . '"]';
		$content = do_shortcode( $shortcode );

		if ( 'We cannot display this gallery' === $content ) {
			$this->markTestSkipped( 'NextGen Gallery not working properly. Skipping.' );
		}

		$id = $this->factory->post->create( array( 'post_type' => 'post', 'post_content' => '[ngg_images image_ids="' . implode( ',', $attachments ) . '"]', 'post_title' => 'nextgen' ) );
		$posts['with'][] = get_permalink( $id );

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
			)
		);
	}

	/**
	 * Loads the required plugin.
	 */
	public function filter_muplugins_loaded() {
		require $this->plugin_to_load;
	}

	/**
	 * Add external URLs to the sitemap using the filter 'aiosp_sitemap_addl_pages_only'.
	 *
	 * @dataProvider externalPagesProvider
	 */
	public function test_add_external_urls( $url1, $url2 ) {
		$this->_urls = array( $url1, $url2 );

		$posts = $this->setup_posts( 2 );

		add_filter( 'aiosp_sitemap_addl_pages_only', array( $this, 'filter_aiosp_sitemap_addl_pages_only' ) );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		$this->_setup_options( 'sitemap', $custom_options );

		$without = $posts['without'];
		$this->validate_sitemap(
			array(
					$without[0] => true,
					$without[1] => true,
					$url1['loc'] => true,
					$url2['loc'] => true,
			)
		);
	}

	/**
	 * Returns the urls to be added to the sitemap.
	 */
	public function filter_aiosp_sitemap_addl_pages_only() {
		return $this->_urls;
	}

	/**
	 * Provides the external pages that need to be added to the sitemap.
	 */
	public function externalPagesProvider() {
		return array(
			array(
				array(
					'loc'        => 'http://www.one.com',
					'lastmod'    => '2018-01-18T21:46:44Z',
					'changefreq' => 'daily',
					'priority'   => '1.0',
				),
				array(
					'loc'        => 'http://www.two.com',
					'lastmod'    => '2018-01-18T21:46:44Z',
					'changefreq' => 'daily',
					'priority'   => '1.0',
				),
			),
		);
	}
}


