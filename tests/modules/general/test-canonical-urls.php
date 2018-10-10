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
		parent::_setUp();
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
}
