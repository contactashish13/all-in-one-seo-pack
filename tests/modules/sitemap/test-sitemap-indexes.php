<?php
/**
 * Class Test_Sitemap_Indexes
 *
 * @package
 */

/**
 * Sitemap test case.
 */

require_once AIOSEOP_UNIT_TESTING_DIR . '/modules/sitemap/test-sitemap.php';

class Test_Sitemap_Indexes extends Test_Sitemap {


	/**
	 * @requires PHPUnit 5.7
	 * Exclude custom taxonomy or a particular taxonomy term.
	 *
	 * @ticket 240 Exclude custom taxonomy.
	 */
	public function test_exclude_taxonomy() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Only for single site' );
		}

		$term_vs_tax = array(
			// term => taxonomy
			'cat1' => 'custom_category0',
			'cat2' => 'custom_category0',
			'custcat1' => 'custom_category1',
			'custcat2' => 'custom_category1',
			'custcat3' => 'custom_category2',
			'custcat4' => 'custom_category3',
		);

		$types = array();
		$prefix	= 'custom_category';
		for( $x = 0; $x < 4; $x++ ) {
			register_taxonomy( "custom_category{$x}", 'post' );
			$cat_id = get_cat_ID( "custom_category{$x}" );
			// exclude all posts from custom_category0 and custom_category2, only custcat1 from custom_category1 and none from custom_category3.
			$types[ "custom_category{$x}" ] = $x%2 !== 0;
		}

		$posts = $this->factory->post->create_many( count( $term_vs_tax ) );

		$terms = array();
		$index = 0;
		foreach ( $term_vs_tax as $term => $taxonomy ) {
			$id = $this->factory->term->create( array( 'taxonomy' => $taxonomy, 'name' => $term ) );
			$terms[ $term ] = $id;
			$this->factory->term->add_post_terms( $posts[ $index ], $term, $taxonomy, false );
			$index++;
		}

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = 'on';
		$custom_options['aiosp_sitemap_taxonomies'] = array( 'custom_category0', 'custom_category1', 'custom_category2', 'custom_category3' );
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		// exclude all posts from custom_category0 and custom_category2, only custcat1 from custom_category1 and none from custom_category3.
		$custom_options['aiosp_sitemap_excl_taxonomies'] = array( 'custom_category0', 'custom_category1', 'custom_category2' );
		$custom_options['aiosp_sitemap_excl_categories'] = array( $terms['custcat1'], $terms['custcat3'] );

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap_index( $types );
	}

	/**
	 * @requires PHPUnit 5.7
	 * Enables indexes and tests that the index and individual sitemaps are all valid according to the schema.
	 *
	 * @ticket 1371 Correct tags order according to Sitemap protocol
	 */
	public function test_index() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Only for single site' );
		}

		$posts = $this->setup_posts( 2, 2 );

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = 'on';
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap_index( array( 'post' ) );
	}

	/**
	 * Creates different types of posts, enables indexes and pagination and checks if the posts are being paginated correctly without additional/blank sitemaps.
	 * @requires PHPUnit 5.7
	 * @dataProvider enabledPostTypes
	 */
	public function test_sitemap_index_pagination( $enabled_post_type, $enabled_post_types_count, $cpt ) {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Only for single site' );
		}

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