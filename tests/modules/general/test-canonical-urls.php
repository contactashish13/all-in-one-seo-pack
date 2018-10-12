<?php
/**
 * Class Test_Canonical_Urls
 *
 * @package 
 */

/**
 * Canonnical URLs test cases.
 */
require_once AIOSEOP_UNIT_TESTING_DIR . '/base/class-aioseop-test-base.php';
class Test_Canonical_Urls extends AIOSEOP_Test_Base {
	public function setUp() {
		$this->init( true );
		parent::_setUp();
	}
	/**
	 * Checks if a non-paginated post specifies the same URL on every page even with "No Pagination for Canonical URLs" unchecked.
	 * Checks if a paginated taxonomy archive DOES NOT specify the same URL on every page.
	 */
	public function test_ignore_pagination() {
		wp_set_current_user( 1 );
		global $aioseop_options;
		$aioseop_options['aiosp_can'] = 1;
		update_option( 'aioseop_options', $aioseop_options );
		$id = $this->factory->post->create( array( 'post_type' => 'post', 'post_content' => 'one two three' ) );
		$link_page = get_permalink( $id );
		$pages[] = $link_page;
		$pages[] = add_query_arg( 'page', 2, $link_page );
		$pages[] = add_query_arg( 'page', 3, $link_page );
		foreach ( $pages as $page ) {
			$links = $this->parse_html( $page, array( 'link' ) );
			//error_log("getting $page " . print_r($links,true));
			$canonical_url = null;
			foreach ( $links as $link ) {
				if ( 'canonical' === $link['rel'] ) {
					$canonical_url = $link['href'];
					break;
				}
			}
			$this->assertEquals( $link_page, $canonical_url );
		}
		// test taxonomy archive pages.
		$this->factory->post->create_many( 100 );
		$cat_id = get_cat_ID( 'Uncategorized' );
		$link_page = get_category_link( $cat_id );
		$pages = array();
		$pages[] = $link_page;
		$pages[] = add_query_arg( 'page', 2, $link_page );
		$pages[] = add_query_arg( 'page', 3, $link_page );
		$canonical_urls = array();
		foreach ( $pages as $page ) {
			$links = $this->parse_html( $page, array( 'link' ) );
			foreach ( $links as $link ) {
				if ( 'canonical' === $link['rel'] ) {
					$canonical_urls[] = $link['href'];
					break;
				}
			}
		}
		// all canonical urls should be different.
		$this->assertEquals( count( $canonical_urls ), count( array_unique( $canonical_urls ) ) );
	}
	
	/**
		 * Canonical URLs on all post type (except post) archive pages should include the &post_type parameter.
		 *
		 * @ticket 491 Canonical urls on custom post type archive pages don't include the required URL variable.
		 */
		public function test_post_type_archive_pages() {
			global $aioseop_options;
			$aioseop_options['aiosp_can'] = 1;
			update_option( 'aioseop_options', $aioseop_options );
	 		$id = $this->factory->post->create( array( 'post_type' => 'page' ) );
			$link = get_month_link( get_the_time( 'Y', $id ), get_the_time( 'm', $id ) );
			$link_page = add_query_arg( 'post_type', 'page', $link );
			$links = $this->parse_html( $link_page, array( 'link' ) );
		
			$names	 = wp_list_pluck( $links, 'rel' );
			$this->assertContains( 'canonical', $names );
	 		foreach ( $links as $link ) {
				if ( 'canonical' === $link['rel'] ) {
					$this->assertEquals( $link['href'], $link_page );
				}
			}
		}

	/**
	 * Checks if the canonical URL settings are set correctly and then can be toggled with the filter.
	 *
	 * @ticket 374 Remove Canonical URLs setting.
	 */
	public function test_settings() {
		$check_travis = (bool) getenv( 'TRAVIS' );
		if ( $check_travis ) {
			$this->markTestIncomplete('This test fails on Travis with the reason: "Cannot modify header information - headers already sent by (output started at /tmp/wordpress-tests-lib/includes/bootstrap.php:68)". Skipping.');
		}

		global $aioseop_options;

		do_action( 'admin_init' );
		$this->assertEquals( 1, $aioseop_options['aiosp_can'] );
		$this->assertEquals( 0, $aioseop_options['aiosp_no_paged_canonical_links'] );
		$this->assertEquals( 1, $aioseop_options['aiosp_customize_canonical_links'] );

		// now let's check if the filter works.
		add_filter( 'aiosp_canonical_urls', array( $this, 'filter_aiosp_canonical_urls' ), 10, 2 );

		do_action( 'admin_init' );
		$this->assertEquals( 0, $aioseop_options['aiosp_can'] );
		$this->assertEquals( 1, $aioseop_options['aiosp_no_paged_canonical_links'] );
		$this->assertEquals( 0, $aioseop_options['aiosp_customize_canonical_links'] );
	}

	function filter_aiosp_canonical_urls( $behavior, $default ) {
		$behavior['aiosp_can'] = 0;
		$behavior['aiosp_no_paged_canonical_links'] = 1;
		$behavior['aiosp_customize_canonical_links'] = 0;
		return $behavior;
	}
}