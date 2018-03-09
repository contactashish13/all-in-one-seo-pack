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
	 * Test whether the meta description is correctly auto generated given different types of content.
	 * @test
	 * @dataProvider postContentProvider
	 */
	public function test_auto_generate_description( $content, $meta_desc, $excerpt = '' ) {
		$this->markTestIncomplete( 'The meta description strangely remains the same for each post. These tests run well when run individually (i.e. not calling the dataProvider). There is obviously something wrong, so skipping this till it can be resolved' );

		wp_set_current_user( 1 );

		global $aioseop_options;

		$id = $this->factory->post->create( array( 'post_type' => 'post', 'post_title' => 'hey' . rand(), 'post_excerpt' => $excerpt, 'post_content' => $content ) );

		// update the format.
		$aioseop_options['aiosp_description_format'] = '%description%';
		$aioseop_options['aiosp_generate_descriptions'] = 'on';
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
	 * Provides the different contents to test whether auto-generated description is generated correctly.
	 */
	public function postContentProvider() {
		return array(
			array( 'content part 1 content part 2', 'content part 1 content part 2' ),
			array( 'blah part 1 blahhhhhhh', 'blah part 1 content part 2', 'blah part 1 content part 2' ),
			array( 'content blah 1 <img src="http://someurl.com/someimage.jpg" /> content part 2', 'content blah 1 content part 2' ),
			array( '<img src="http://someurl.com/someimage.jpg" /> content part blah <img src="http://someurl.com/someimage.jpg" /> content part 2', 'content part blah content part 2' ),
			array( 'content part 1a content part 2 content part 3', 'content part 1a content part 2' ),
			array( 'content part 10 <img src="http://someurl.com/someimage.jpg" /> content part 2 <img src="http://someurl.com/someimage.jpg" /> content part 3', 'content part 10 content part 2 content part 3' ),
			array( str_repeat( 'blah', 300 ), substr( str_repeat( 'blah', 300 ), 0, 320 ) ),
		);
	}

}