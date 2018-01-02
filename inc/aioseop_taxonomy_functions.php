<?php
/**
 * Functions specific to taxonomies.
 *
 * @package All-in-One-SEO-Pack
 */

if ( ! function_exists( 'aioseop_taxonomy_columns' ) ) {
	function aioseop_taxonomy_columns( $columns ) {
		global $aioseop_options;
		$columns['seotitle'] = __( 'SEO Title', 'all-in-one-seo-pack' );
		$columns['seodesc']  = __( 'SEO Description', 'all-in-one-seo-pack' );
		if ( empty( $aioseop_options['aiosp_togglekeywords'] ) ) {
			$columns['seokeywords'] = __( 'SEO Keywords', 'all-in-one-seo-pack' );
		}
		return $columns;
	}
}


if ( ! function_exists( 'aioseop_taxonomy_manage_columns' ) ) {
	function aioseop_taxonomy_manage_columns( $out, $column_name, $id ) {
		switch ( $column_name ) {
			case 'seotitle':
				echo get_term_meta( $id, '_aioseop_title', true );
				break;
			case 'seodesc':
				echo get_term_meta( $id, '_aioseop_description', true );
				break;
			case 'seokeywords':
				echo get_term_meta( $id, '_aioseop_keywords', true );
				break;
		}
		return $out;
	}
}
