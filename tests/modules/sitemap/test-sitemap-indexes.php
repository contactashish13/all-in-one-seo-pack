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
	public function test_exclude_taxonomy_with_without_terms() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Only for single site' );
		}

		$prefix	= 'custom_category';

		$term_vs_tax = array(
			// term => taxonomy
			'cat01' => "{$prefix}0",
			'cat02' => "{$prefix}0",
			'cat11' => "{$prefix}1",
			'cat12' => "{$prefix}1",
			'cat21' => "{$prefix}2",
			'cat31' => "{$prefix}3",
		);

		$types = array();
		for( $x = 0; $x < 4; $x++ ) {
			register_taxonomy( "{$prefix}{$x}", 'post' );
			$cat_id = get_cat_ID( "{$prefix}{$x}" );
			// exclude all posts from custom_category0 and custom_category2, only cat1 from custom_category1 and none from custom_category3.
			$types[ "{$prefix}{$x}" ] = $x%2 !== 0;
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
		$custom_options['aiosp_sitemap_taxonomies'] = array( "{$prefix}0", "{$prefix}1", "{$prefix}2", "{$prefix}3" );
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		// exclude all posts from custom_category0 and custom_category2, only cat11 from custom_category1 and none from custom_category3.
		$custom_options['aiosp_sitemap_excl_taxonomies'] = array( "{$prefix}0", "{$prefix}1", "{$prefix}2" );
		$custom_options['aiosp_sitemap_excl_categories'] = array( $terms['cat11'] );

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap_index( $types );
	}

	/**
	 * @requires PHPUnit 5.7
	 * Assign a custom taxonomy's terms to all posts, exclude this taxonomy and check that neither the taxonomy sitemap nor the post sitemap is shown.
	 *
	 * @ticket 240 Exclude custom taxonomy.
	 */
	public function test_exclude_taxonomy_and_post_type_sitemaps() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Only for single site' );
		}

		$prefix	= 'custom_category';

		$term_vs_tax = array(
			// term => taxonomy
			'cat01' => "{$prefix}0",
			'cat02' => "{$prefix}0",
			'cat03' => "{$prefix}0",
			'cat04' => "{$prefix}0",
			'cat05' => "{$prefix}0",
		);

		register_taxonomy( "{$prefix}0", 'post' );

		$types = array();
		$types[ "{$prefix}0" ] = false;
		$types[ "post" ] = false;

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
		$custom_options['aiosp_sitemap_taxonomies'] = array( "{$prefix}0" );
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		$custom_options['aiosp_sitemap_excl_taxonomies'] = array( "{$prefix}0" );
		$custom_options['aiosp_sitemap_excl_categories'] = array();

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap_index( $types, true );
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