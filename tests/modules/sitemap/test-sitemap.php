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
	 * Testing post type archive pages.
	 *
	 * @ticket 155 XML Sitemap - Add support for post type archive pages and support to exclude them as well.
	 *
	 * @access public
	 * @dataProvider post_type_archive_pages_provider
	 */
	public function test_post_type_archive_pages( $post_types, $exclude ) {
		update_option( 'timezone_string', 'GMT' );
		update_option( 'gmt_offset', 0 );

		$links = array();
		$tests = array();

		// create 2 posts, each in a different month.
		for ( $i = 0; $i < 2; $i++ ) {
			$date = sprintf( '2017-0%d-01 09:09:09', ( $i + 1 ) );
			$links['post'][] = get_month_link( 2017, ( $i + 1 ) );
			$id = $this->factory->post->create( array( 'post_type' => 'post', 'post_date' => $date ) );
		
			if ( in_array( 'post', $post_types ) ) {
				$tests[ get_permalink( $id ) ] = true;
			}
		}

		if ( in_array( 'page', $post_types ) ) {
			// create 2 pages, each in a different month.
			for ( $i = 0; $i < 2; $i++ ) {
				$date = sprintf( '2017-0%d-01 09:09:09', ( $i + 5 ) );
				$links['page'][] = get_month_link( 2017, $i + 5 );
				$id = $this->factory->post->create( array( 'post_type' => 'page', 'post_date' => $date ) );
				$tests[ get_permalink( $id ) ] = true;
			}
		}

		if ( $exclude ) {
			add_filter( 'aiosp_sitemap_include_archives', array( $this, 'filter_aiosp_sitemap_include_archives' ) );
		}

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = '';
		$custom_options['aiosp_sitemap_images'] = 'on';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_archive'] = 'on';
		$custom_options['aiosp_sitemap_posttypes'] = $post_types;

		$this->_setup_options( 'sitemap', $custom_options );

		foreach ( $links as $type => $urls ) {
			foreach ( $urls as $url ) {
				// any other post type will have a &post_type= argument.
				if ( 'post' === $type ) {
					// post date archives will always be present.
					$tests[ $url ] = true;
				} else {
					$url = add_query_arg( 'post_type', $type, $url );
					// one can switch off date archives of other post types.
					$tests[ $url ] = ! $exclude;
				}
			}
		}
		$this->validate_sitemap( $tests );
	}

	/**
	 * Implements the filter 'aiosp_sitemap_include_archives'.
	 */
	public function filter_aiosp_sitemap_include_archives( $posttypes ) {
		return array();
	}

	/**
	 * Provide the post types for testing test_post_type_archive_pages.
	 * 
	 * This will enable us to test 3 cases:
	 * 1) When no post type is selected => only post archives in the sitemap.
	 * 2) When only post post type is selected => posts and post archives in the sitemap.
	 * 3) When post and page post types are selected => posts, pages, post archives and page archives in the sitemap.
	 *
	 * @access public
	 */
	public function post_type_archive_pages_provider() {
		return array(
			// include post date archives.
			array( array(), false ),
			array( array( 'post' ), false ),
			array( array( 'post', 'page' ), false ),
			// exclude post date archives (but only for non "post" post types).
			array( array(), true ),
			array( array( 'post' ), true ),
			array( array( 'post', 'page' ), true ),
		);
	}
  

 /**
	 * @requires PHPUnit 5.7
	 * Creates different types of posts, enables indexes and pagination and checks if the posts are being paginated correctly without additional/blank sitemaps.
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
