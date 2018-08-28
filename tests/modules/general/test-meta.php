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

	public function setUp() {
		$this->init( true );
	}

	/**
	 * Creates a custom field in the post and uses this in the meta description.
	 * NOTE: This does not require the ACF plugin because the code uses the custom field directly if ACF is not installed.
	 *
	 * @dataProvider acfDataProvider
	 */
	public function test_custom_field_in_meta_desc( $format, $custom_field = false ) {
		wp_set_current_user( 1 );

		global $aioseop_options;

		$meta_desc	= 'heyhey';
		$id = $this->factory->post->create( array( 'post_type' => 'post', 'post_title' => 'hey', 'post_content' => $meta_desc ) );
		// update the AIOSEOP description to be the same as the post description.
		update_post_meta( $id, '_aioseop_description', $meta_desc );

		// if custom field is provided, create it and change the format.
		if ( $custom_field ) {
			$meta_desc = 'holahola';
			update_post_meta( $id, $format, $meta_desc );
			$format	= "cf_{$format}";
		}

		// update the format.
		$aioseop_options['aiosp_description_format'] = "%$format%";
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
	 * Test whether the meta description is correctly auto generated given different types of content.
	 * @test
	 * @dataProvider postContentProvider
	 */
	public function test_auto_generate_description( $content, $meta_desc, $excerpt = '' ) {
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
	 * Provides the different contents to test whether auto-generated description is generated correctly.
	 */
	public function postContentProvider() {
		return array(
			array( 'content part 1 content part 2', 'content part 1 content part 2' ),
			array( 'blah part 1 blahhhhhhh', 'blah part 1 content part 2', 'blah part 1 content part 2' ),
			array( 'content blah 1 <img src="http://someurl.com/someimage.jpg" /> content part 2', 'content blah 1 content part 2' ),
			array( '<img src="http://someurl.com/someimage.jpg" /> content part blah <img src="http://someurl.com/someimage.jpg" /> content part 2', 'content part blah content part 2' ),
			array( 'content part 1a content part 2 content part 3', 'content part 1a content part 2', 'content part 1a content part 2' ),
			array( 'content part 10 <img src="http://someurl.com/someimage.jpg" /> content part 2 <img src="http://someurl.com/someimage.jpg" /> content part 3', 'content part 10 content part 2 content part 3' ),
			array( str_repeat( 'blah', 300 ), substr( str_repeat( 'blah', 300 ), 0, 160 ) ),
		);
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

	/**
	 * Provides data to test meta with and without custom fields.
	 */
	public function acfDataProvider() {
		return array(
			array( 'description', false ),
			array( 'custom_description', true ),
		);
	}
}