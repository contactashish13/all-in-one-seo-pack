<?php

/**
 * @package All-in-One-SEO-Pack
 */

/**
 * Class aiosp_common
 *
 * These are commonly used functions that can be pulled from anywhere.
 * (or in some cases they're functions waiting for a home)
 */
// @codingStandardsIgnoreStart
class aiosp_common {
// @codingStandardsIgnoreEnd

	/**
	 * aiosp_common constructor.
	 *
	 */
	function __construct() {

	}

	/**
	 * Clears WP Engine cache.
	 */
	static function clear_wpe_cache() {
		if ( class_exists( 'WpeCommon' ) ) {
			WpeCommon::purge_memcached();
			WpeCommon::clear_maxcdn_cache();
			WpeCommon::purge_varnish_cache();
		}
	}

	/**
	 * @param null $p
	 *
	 * @return array|null|string|WP_Post
	 */
	static function get_blog_page( $p = null ) {
		static $blog_page = '';
		static $page_for_posts = '';
		if ( null === $p ) {
			global $post;
		} else {
			$post = $p;
		}
		if ( '' === $blog_page ) {
			if ( '' === $page_for_posts ) {
				$page_for_posts = get_option( 'page_for_posts' );
			}
			if ( $page_for_posts && is_home() && ( ! is_object( $post ) || ( $page_for_posts !== $post->ID ) ) ) {
				$blog_page = get_post( $page_for_posts );
			}
		}

		return $blog_page;
	}

	/**
	 * @param string $location
	 * @param string $title
	 * @param string $anchor
	 * @param string $target
	 * @param string $class
	 * @param string $id
	 *
	 * @return string
	 */
	static function get_upgrade_hyperlink( $location = '', $title = '', $anchor = '', $target = '', $class = '', $id = 'aio-pro-update' ) {

		$affiliate_id = '';

		// call during plugins_loaded
		$affiliate_id = apply_filters( 'aiosp_aff_id', $affiliate_id );

		// build URL
		$url = 'https://semperplugins.com/all-in-one-seo-pack-pro-version/';
		if ( $location ) {
			$url .= '?loc=' . $location;
		}
		if ( $affiliate_id ) {
			$url .= "?ap_id=$affiliate_id";
		}

		// build hyperlink
		$hyperlink = '<a ';
		if ( $target ) {
			$hyperlink .= "target=\"$target\" ";
		}
		if ( $title ) {
			$hyperlink .= "title=\"$title\" ";
		}
		if ( $id ) {
			$hyperlink .= "id=\"$id\" ";
		}

		$hyperlink .= "href=\"$url\">$anchor</a>";

		return $hyperlink;
	}

	/**
	 * Gets the upgrade to Pro version URL.
	 */
	static function get_upgrade_url() {
		// put build URL stuff in here
	}

	/**
	 * Check whether a url is relative and if it is, make it absolute.
	 *
	 * @param string $url URL to check.
	 *
	 * @return string
	 */
	static function absolutize_url( $url ) {
		if ( strpos( $url, 'http' ) !== 0 && strpos( $url, '//' ) !== 0 && $url != '/' ) {
			$url = home_url( $url );
		}
		return $url;
	}

	/**
	 * Determines if the given image URL is an attachment and, if it is, gets the correct image URL according to the requested size.
	 */
	static function get_image_src_for_url( $url ) {
		// let's check if this image is an attachment.
		$dir = wp_get_upload_dir();
		$path = $url;
	 
		$site_url = parse_url( $dir['url'] );
		$image_path = parse_url( $path );
	 
		//force the protocols to match if needed
		if ( isset( $image_path['scheme'] ) && ( $image_path['scheme'] !== $site_url['scheme'] ) ) {
			$path = str_replace( $image_path['scheme'], $site_url['scheme'], $path );
		}
	 
		if ( 0 === strpos( $path, $dir['baseurl'] . '/' ) ) {
			$path = substr( $path, strlen( $dir['baseurl'] . '/' ) );
		}


		global $wpdb;
		$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s", '%' . basename( $path ) . '%' ) );

		if ( $attachment_id ) {
			$size  = apply_filters( 'aioseop_attachment_size', apply_filters( 'aioseop_thumbnail_size', apply_filters( 'post_thumbnail_size', 'large' ) ) );
			// if this is a valid attachment, get the correct size.
			$image = wp_get_attachment_image_src( $attachment_id, $size );
			if ( $image ) {
				$url = $image[0];
			}
		}

		return $url;
	}

	/**
	 * Parses the content of the post provided for images and galleries.
	 *
	 * @param WP_Post	$post	The post to parse.
	 * @return array
	 */
	public static function parse_content_for_images( WP_Post $post ) {
		$images		= array();
		$content	= $post->post_content;
		// Check images galleries in the content. DO NOT run the_content filter here as it might cause issues with other shortcodes.
		if ( has_shortcode( $content, 'gallery' ) ) {
			$galleries = get_post_galleries( $post, false );
			if ( $galleries ) {
				foreach ( $galleries as $gallery ) {
					$images = array_merge( $images, $gallery['src'] );
				}
			}
		}

		self::parse_dom_for_images( $content, $images );

		return $images;
	}

	/**
	 * Parse the post for images.
	 *
	 * @param string $content the post content.
	 * @param array  $images the array of images.
	 *
	 * @return void
	 */
	public static function parse_dom_for_images( $content, &$images ) {
		$total   = substr_count( $content, '<img ' ) + substr_count( $content, '<IMG ' );
		// no images found.
		if ( 0 === $total ) {
			return;
		}

		if ( class_exists( 'DOMDocument' ) ) {
			$dom = new domDocument();
			// Non-compliant HTML might give errors, so ignore them.
			libxml_use_internal_errors( true );
			$dom->loadHTML( $content );
			libxml_clear_errors();
			$dom->preserveWhiteSpace = false;
			$matches = $dom->getElementsByTagName( 'img' );
			foreach ( $matches as $match ) {
				$images[] = $match->getAttribute( 'src' );
			}
		} else {
			// Fall back to regex, but also report an error.
			global $img_err_msg;
			if ( ! isset( $img_err_msg ) ) {
				// we will log this error message only once, not per post.
				$img_err_msg = true;
				aiosp_log( 'DOMDocument not found; using REGEX' );
			}
			preg_match_all( '/<img.*src=([\'"])?(.*?)\\1/', $content, $matches );
			if ( $matches && isset( $matches[2] ) ) {
				$images = array_merge( $images, $matches[2] );
			}
		}
	}
}
