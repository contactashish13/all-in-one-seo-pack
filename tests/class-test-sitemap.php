<?php
/**
 * Class Test_Sitemap
 *
 * @package 
 */

/**
 * Sitemap test case.
 */

require_once dirname( __FILE__ ) . '/base/class-sitemap-test-base.php';

class Test_Sitemap extends Sitemap_Test_Base {

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

	/**
	 * Testing post type archive pages.
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
			add_filter( 'aiosp_sitemap_include_archives', function( $posttypes ) {
				return array();
			} );
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
}


