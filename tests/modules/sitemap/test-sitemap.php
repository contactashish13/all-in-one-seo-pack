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

	public function setUp() {
		parent::init();
		parent::setUp();
	}

	public function tearDown() {
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
					'image' => true,
				),
				$with[1] => array(
					'image' => true,
				),
				$without[0] => array(
					'image' => false,
				),
				$without[1] => array(
					'image' => false,
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
					'image' => false,
				),
				$with[1] => array(
					'image' => false,
				),
				$without[0] => array(
					'image' => false,
				),
				$without[1] => array(
					'image' => false,
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
		wp_set_current_user( 1 );
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

		do_action( 'init' );

		// nextgen shortcode does not work without creating a gallery or images. So we will have to create a gallery to do this.
		$nggdb		= new nggdb();
		$gallery_id = nggdb::add_gallery();
		$images	= array(
			$nggdb->add_image( $gallery_id, 'x.png', 'x', 'x', 'eyJiYWNrdXAiOnsiZmlsZW5hbWUiOiJzYW1wbGUucG5nIiwid2lkdGgiOjI0OCwiaGVpZ2h0Ijo5OCwiZ2VuZXJhdGVkIjoiMC4wMjM3MzMwMCAxNTA3MDk1MTcwIn0sImFwZXJ0dXJlIjpmYWxzZSwiY3JlZGl0IjpmYWxzZSwiY2FtZXJhIjpmYWxzZSwiY2FwdGlvbiI6ZmFsc2UsImNyZWF0ZWRfdGltZXN0YW1wIjpmYWxzZSwiY29weXJpZ2h0IjpmYWxzZSwiZm9jYWxfbGVuZ3RoIjpmYWxzZSwiaXNvIjpmYWxzZSwic2h1dHRlcl9zcGVlZCI6ZmFsc2UsImZsYXNoIjpmYWxzZSwidGl0bGUiOmZhbHNlLCJrZXl3b3JkcyI6ZmFsc2UsIndpZHRoIjoyNDgsImhlaWdodCI6OTgsInNhdmVkIjp0cnVlLCJtZDUiOiI3ZWUyMjVjOTNkZmNhMTMyYjQzMTc5ZjJiMGYwZTc2NiIsImZ1bGwiOnsid2lkdGgiOjI0OCwiaGVpZ2h0Ijo5OCwibWQ1IjoiN2VlMjI1YzkzZGZjYTEzMmI0MzE3OWYyYjBmMGU3NjYifSwidGh1bWJuYWlsIjp7IndpZHRoIjoyNDAsImhlaWdodCI6OTgsImZpbGVuYW1lIjoidGh1bWJzX3NhbXBsZS5wbmciLCJnZW5lcmF0ZWQiOiIwLjMwNDUzNDAwIDE1MDcwOTUxNzAifSwibmdnMGR5bi0weDB4MTAwLTAwZjB3MDEwYzAxMHIxMTBmMTEwcjAxMHQwMTAiOnsid2lkdGgiOjI0OCwiaGVpZ2h0Ijo5OCwiZmlsZW5hbWUiOiJzYW1wbGUucG5nLW5nZ2lkMDE3LW5nZzBkeW4tMHgweDEwMC0wMGYwdzAxMGMwMTByMTEwZjExMHIwMTB0MDEwLnBuZyIsImdlbmVyYXRlZCI6IjAuMTgwMzI0MDAgMTUyMTAxMTI1NCJ9fQ=='),
			$nggdb->add_image( $gallery_id, 'x.png', 'x', 'x', 'eyJiYWNrdXAiOnsiZmlsZW5hbWUiOiJzYW1wbGUucG5nIiwid2lkdGgiOjI0OCwiaGVpZ2h0Ijo5OCwiZ2VuZXJhdGVkIjoiMC4wMjM3MzMwMCAxNTA3MDk1MTcwIn0sImFwZXJ0dXJlIjpmYWxzZSwiY3JlZGl0IjpmYWxzZSwiY2FtZXJhIjpmYWxzZSwiY2FwdGlvbiI6ZmFsc2UsImNyZWF0ZWRfdGltZXN0YW1wIjpmYWxzZSwiY29weXJpZ2h0IjpmYWxzZSwiZm9jYWxfbGVuZ3RoIjpmYWxzZSwiaXNvIjpmYWxzZSwic2h1dHRlcl9zcGVlZCI6ZmFsc2UsImZsYXNoIjpmYWxzZSwidGl0bGUiOmZhbHNlLCJrZXl3b3JkcyI6ZmFsc2UsIndpZHRoIjoyNDgsImhlaWdodCI6OTgsInNhdmVkIjp0cnVlLCJtZDUiOiI3ZWUyMjVjOTNkZmNhMTMyYjQzMTc5ZjJiMGYwZTc2NiIsImZ1bGwiOnsid2lkdGgiOjI0OCwiaGVpZ2h0Ijo5OCwibWQ1IjoiN2VlMjI1YzkzZGZjYTEzMmI0MzE3OWYyYjBmMGU3NjYifSwidGh1bWJuYWlsIjp7IndpZHRoIjoyNDAsImhlaWdodCI6OTgsImZpbGVuYW1lIjoidGh1bWJzX3NhbXBsZS5wbmciLCJnZW5lcmF0ZWQiOiIwLjMwNDUzNDAwIDE1MDcwOTUxNzAifSwibmdnMGR5bi0weDB4MTAwLTAwZjB3MDEwYzAxMHIxMTBmMTEwcjAxMHQwMTAiOnsid2lkdGgiOjI0OCwiaGVpZ2h0Ijo5OCwiZmlsZW5hbWUiOiJzYW1wbGUucG5nLW5nZ2lkMDE3LW5nZzBkeW4tMHgweDEwMC0wMGYwdzAxMGMwMTByMTEwZjExMHIwMTB0MDEwLnBuZyIsImdlbmVyYXRlZCI6IjAuMTgwMzI0MDAgMTUyMTAxMTI1NCJ9fQ=='),
		);

		$shortcode = '[ngg_images display_type="photocrati-nextgen_basic_thumbnails" image_ids="'. implode( ',', $images ) . '"]';
		$content = do_shortcode( $shortcode );

		if ( 'We cannot display this gallery' === $content ) {
			$this->markTestSkipped( 'NextGen Gallery not working properly. Skipping.' );
		}

		// $content will output div and img tags but the img tags have an empty src.
		$this->markTestIncomplete( 'We cannot add images in such a way that the shortcode displays the "src" attribute in the image tags. Skipping.' );

		$id = $this->factory->post->create( array( 'post_type' => 'post', 'post_content' => $shortcode, 'post_title' => 'nextgen' ) );
		$url = get_permalink( $id );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap(
			array(
					$url => array(
						'image'	=> true,
					)
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
	 * Creates different types of posts, enables indexes and pagination and checks if the posts are being paginated correctly without additional/blank sitemaps.
	 * @requires PHPUnit 5.7
	 *
	 * @dataProvider enabledPostTypes
	 */
	public function test_sitemap_index_pagination( $enabled_post_type, $enabled_post_types_count, $cpt ) {
		// choose numbers which are not multiples of each other.
		$num_posts = 22;
		$per_xml = 7;

		if ( in_array( 'post', $enabled_post_type ) ) {
			$this->factory->post->create_many( $num_posts );
		}

		if ( in_array( 'page', $enabled_post_type ) ) {
			$this->factory->post->create_many( $num_posts, array( 'post_type' => 'page' ) );
		}

		if ( in_array( 'attachment', $enabled_post_type ) ) {
			$this->create_attachments( $num_posts );
		}

		if ( ! is_null( $cpt ) ) {
			register_post_type( $cpt );
			$this->factory->post->create_many( $num_posts, array( 'post_type' => $cpt ) );
		}

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = 'on';
		$custom_options['aiosp_sitemap_max_posts'] = $per_xml;
		$custom_options['aiosp_sitemap_images'] = 'on';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = $enabled_post_type;
		$custom_options['aiosp_sitemap_taxonomies'] = array();

			$this->_setup_options( 'sitemap', $custom_options );

		// calculate the number of sitemaps expected in the index. The +1 is for the sitemap_addl.xml that includes the home page.
		$expected = intval( $enabled_post_types_count * ceil( $num_posts / $per_xml ) + 1 );
		$got = $this->count_sitemap_elements( array( '<sitemap>' ) );

		$this->assertEquals( $expected, $got['<sitemap>'] );
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

	/**
	 * Provides posts types to test test_sitemap_index_pagination against.
	 */
	public function enabledPostTypes() {
		return array(
			array( array( 'post' ), 1, null ),
			array( array( 'post', 'page' ), 2, null ),
			array( array( 'product' ), 1, 'product' ),
			array( array( 'attachment', 'product' ), 2, 'product' ),
			array( array( 'all', 'post', 'page' ), 2, null ),
			array( array( 'all', 'post', 'page', 'attachment', 'product' ), 4, 'product' ),
		);
	}
}
