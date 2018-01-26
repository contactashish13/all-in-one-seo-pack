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

	/**
	 * Checks if a non-paginated post specifies the same URL on every page even with "No Pagination for Canonical URLs" unchecked.
	 */
	public function test_ignore_pagination() {
		$this->markTestIncomplete('Pagination does not seem to work (the post is not broken into pages), so skipping this till we figure this out');

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
	}
}
