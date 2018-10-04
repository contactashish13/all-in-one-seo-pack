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



}