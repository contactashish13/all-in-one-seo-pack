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
	 * Creates a custom field in the post and uses this in the meta description.
	 */
	public function test_custom_field_in_meta_desc_no_content() {
		wp_set_current_user( 1 );

		global $aioseop_options;

		$meta_desc	= 'heyhey';
		// very, very important: post excerpt has to be empty or this will not work.
		$id = $this->factory->post->create( array( 'post_type' => 'post', 'post_title' => 'hey', 'post_content' => '', 'post_excerpt' => '' ) );
		// update the AIOSEOP description.
		update_post_meta( $id, 'custom_description', $meta_desc );

		// update the format.
		$aioseop_options['aiosp_description_format'] = "%cf_custom_description%";
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


}