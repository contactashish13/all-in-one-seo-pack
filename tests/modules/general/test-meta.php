<?php
/**
 * Class Test_Meta
 *
 * @package 
 */

/**
 * Advance Custom Fields test cases.
 */

require_once AIOSEOP_UNIT_TESTING_DIR . '/base/class-aioseop-test-base.php';

class Test_Meta extends AIOSEOP_Test_Base {

	/**
	 * Test whether the meta description contains exactly what is expected.
	 *
	 * @dataProvider metaDescProvider
	 */
	public function test_post_title_in_meta_desc( $title, $meta_desc, $format ) {
		wp_set_current_user( 1 );

		global $aioseop_options;

		$id = $this->factory->post->create( array( 'post_type' => 'post', 'post_title' => $title ) );

		// update the format.
		$aioseop_options['aiosp_description_format'] = $format;
		update_option( 'aioseop_options', $aioseop_options );

		$link = get_permalink( $id );
		$meta = $this->parse_html( $link, array( 'meta' ) );

		// should have atleast one meta tag.
		$this->assertGreaterThan( 1, count( $meta ) );

		$description = null;
		foreach ( $meta as $m ) {
			if ( 'description' === $m['name'] ) {
				$description = $m['content'];
				break;
			}
		}
		$this->assertEquals( $meta_desc, $description );
	}

	/**
	 * The data provider for meta description.
	 */
	public function metaDescProvider() {
		return array(
			array( 'heyhey', 'heyhey', '%post_title%' ),
			array( 'heyhey', 'heyhey' . get_option('blogname'), '%post_title%%blog_title%' ),
		);
	}

}