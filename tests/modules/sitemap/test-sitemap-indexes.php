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

	/**
	 * Test excluding all terms of a taxonomy.
	 *
	 * The sitemap corresponding to the excluded taxonomy should not appear in the indexed sitemap.
	 * The posts corresponding to the excluded terms should not appear in the post sitemap.
	 *
	 * @since 3.0
	 *
	 * @requires PHPUnit 5.7
	 */
	public function test_exclude_taxonomy_all_terms() {
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
			// exclude all terms from custom_category0
			$types[ "{$prefix}{$x}" ] = $x !== 0;
		}

		$posts = $this->factory->post->create_many( count( $term_vs_tax ) );

		$exclude_terms = array();
		$terms = array();
		$index = 0;
		foreach ( $term_vs_tax as $term => $taxonomy ) {
			$id = $this->factory->term->create( array( 'taxonomy' => $taxonomy, 'name' => $term ) );
			$terms[ $term ] = $id;
			$this->factory->term->add_post_terms( $posts[ $index ], $term, $taxonomy, false );
			$index++;
			// exclude all terms from custom_category0
			if ( "{$prefix}0" === $taxonomy ) {
				$exclude_terms[] = $id;
			}
		}

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = 'on';
		$custom_options['aiosp_sitemap_taxonomies'] = array( "{$prefix}0", "{$prefix}1", "{$prefix}2", "{$prefix}3" );
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		// exclude all terms of custom_category0
		$custom_options['aiosp_sitemap_excl_terms'] = $exclude_terms;

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap_index( $types );

		// lets check if the number of posts in the post sitemap also corresponds to the posts that have been excluded.
		$file = ABSPATH . '/sitemap_post.xml';
		$map  = $this->count_sitemap_elements( array( '<url>' ), $file );
		$this->assertEquals( count( $term_vs_tax ) - count( $exclude_terms ), $map['<url>'] );
	}

	/**
	 * Test excluding some terms of a taxonomy.
	 *
	 * The sitemap corresponding to the excluded taxonomy should appear in the indexed sitemap.
	 * The posts corresponding to the excluded terms should not appear in the post sitemap.
	 *
	 * @since 3.0
	 *
	 * @requires PHPUnit 5.7
	 */
	public function test_exclude_taxonomy_some_terms() {
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
			$types[ "{$prefix}{$x}" ] = true;
		}

		$posts = $this->factory->post->create_many( count( $term_vs_tax ) );

		$exclude_terms = array();
		$terms = array();
		$index = 0;
		foreach ( $term_vs_tax as $term => $taxonomy ) {
			$id = $this->factory->term->create( array( 'taxonomy' => $taxonomy, 'name' => $term ) );
			$terms[ $term ] = $id;
			$this->factory->term->add_post_terms( $posts[ $index ], $term, $taxonomy, false );
			$index++;
			// exclude only one term from custom_category0
			if ( 'cat01' === $term ) {
				$exclude_terms[] = $id;
			}
		}

		$custom_options = array();
		$custom_options['aiosp_sitemap_indexes'] = 'on';
		$custom_options['aiosp_sitemap_taxonomies'] = array( "{$prefix}0", "{$prefix}1", "{$prefix}2", "{$prefix}3" );
		$custom_options['aiosp_sitemap_images'] = '';
		$custom_options['aiosp_sitemap_gzipped'] = '';
		$custom_options['aiosp_sitemap_posttypes'] = array( 'post' );

		// exclude all terms of custom_category0
		$custom_options['aiosp_sitemap_excl_terms'] = $exclude_terms;

		$this->_setup_options( 'sitemap', $custom_options );

		$this->validate_sitemap_index( $types );

		// lets check if the number of posts in the post sitemap also corresponds to the posts that have been excluded.
		$file = ABSPATH . '/sitemap_post.xml';
		$map  = $this->count_sitemap_elements( array( '<url>' ), $file );
		$this->assertEquals( count( $term_vs_tax ) - count( $exclude_terms ), $map['<url>'] );
	}

}