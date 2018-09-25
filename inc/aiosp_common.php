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
		if ( 0 !== strpos( $url, 'http' ) && '/' !== $url ) {
			if ( 0 === strpos( $url, '//' ) ) {
				// for //<host>/resource type urls.
				$scheme = parse_url( home_url(), PHP_URL_SCHEME );
				$url    = $scheme . ':' . $url;
			} else {
				// for /resource type urls.
				$url = home_url( $url );
			}
		}
		return $url;
	}

	/**
	 * Check whether a url is relative (does not contain a . before the first /) or absolute and makes it a valid url.
	 *
	 * @param string $url URL to check.
	 *
	 * @return string
	 */
	static function make_url_valid_smartly( $url ) {
		$scheme = parse_url( home_url(), PHP_URL_SCHEME );
		if ( 0 !== strpos( $url, 'http' ) ) {
			if ( 0 === strpos( $url, '//' ) ) {
				// for //<host>/resource type urls.
				$url    = $scheme . ':' . $url;
			} elseif ( strpos( $url, '.' ) !== false && strpos( $url, '/' ) !== false && strpos( $url, '.' ) < strpos( $url, '/' ) ) {
				// if the . comes before the first / then this is absolute.
				$url    = $scheme . '://' . $url;
			} else {
				// for /resource type urls.
				$url = home_url( $url );
			}
		} else if ( strpos( $url, 'http://' ) === false ) {
			if ( 0 === strpos( $url, 'http:/' ) ) {
				$url	= $scheme . '://' .  str_replace( 'http:/', '', $url );
			} else if ( 0 === strpos( $url, 'http:' ) ) {
				$url	= $scheme . '://' . str_replace( 'http:', '', $url );
			}
		}
		return $url;
	}

	/**
	 * Determines if the given image URL is an attachment and, if it is, gets the correct image URL according to the requested size.
	 *
	 * @param	string		$url	The url of the image.
	 * @param	string		$size	The size of the image to return.
	 *
	 * @return string
	 */
	public static function get_image_src_for_url( $url, $size = 'thumbnail' ) {
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
			// if this is a valid attachment, get the correct size.
			$image = wp_get_attachment_image_src( $attachment_id, $size );
			if ( $image ) {
				$url = $image[0];
			}
		}

		return $url;
	}

	/**
	 * Fetch images from WP, Jetpack and WooCommerce galleries.
	 *
	 * @param string $post The post.
	 * @param array  $images the array of images.
	 *
	 * @return void
	 * @since 2.4.2
	 */
	public static function get_gallery_images( $post, &$images ) {
		if ( false === apply_filters( 'aioseo_include_images_in_wp_gallery', true ) ) {
			return;
		}

		// Check images galleries in the content. DO NOT run the_content filter here as it might cause issues with other shortcodes.
		if ( has_shortcode( $post->post_content, 'gallery' ) ) {
			// Get the jetpack gallery images.
			if ( class_exists( 'Jetpack_PostImages' ) ) {
				// the method specifies default width and height so we need to provide these values to override the defaults.
				// since there is no way to determine the original image's dimensions, we will fetch the 'large' size image here.
				$jetpack    = Jetpack_PostImages::get_images( $post->ID, $this->get_dimensions_for_image_size( 'large' ) );
				if ( $jetpack ) {
					foreach ( $jetpack as $jetpack_image ) {
						$images[]   = $jetpack_image['src'];
					}
				}
			}

			// Get the default WP gallery images.
			$galleries = get_post_galleries( $post, false );
			if ( $galleries ) {
				foreach ( $galleries as $gallery ) {
					$images = array_merge( $images, $gallery['src'] );
				}
			}
		}

		$images = array_unique( $images );
	}

	/**
	 * Fetch the width and height for the specified image size.
	 *
	 * @param string $size The image size e.g. 'large', 'medium' etc.
	 *
	 * @since 2.4.3
	 */
	private function get_dimensions_for_image_size( $size ) {
		$sizes  = get_intermediate_image_sizes();
		if ( ! in_array( $size, $sizes, true ) ) {
			// our specified size does not exist in the registered sizes, so let's use the largest one available.
			$size   = end( $sizes );
		}

		if ( $size ) {
			return array(
				'width'     => get_option( "{$size}_size_w" ),
				'height'    => get_option( "{$size}_size_h" ),
			);
		}
		return null;
	}

	/**
	 * Parses the content to find out if specified images galleries exist and if they do, parse them for images.
	 * Supports NextGen.
	 *
	 * @param string $content The post content.
	 *
	 * @since 2.4.2
	 *
	 * @return string
	 */
	public static function get_content_from_galleries( $content ) {
		// Support for NextGen Gallery.
		static $gallery_types   = array( 'ngg_images' );
		$types                  = apply_filters( 'aioseop_gallery_shortcodes', $gallery_types );

		$gallery_content    = '';

		if ( ! $types ) {
			return $gallery_content;
		}

		$found  = array();
		if ( $types ) {
			foreach ( $types as $type ) {
				if ( has_shortcode( $content, $type ) ) {
					$found[] = $type;
				}
			}
		}

		// If none of the shortcodes-of-interest are found, bail.
		if ( empty( $found ) ) {
			return $gallery_content;
		}

		$galleries = array();

		if ( ! preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER ) ) {
			return $gallery_content;
		}

		// Collect the shortcodes and their attributes.
		foreach ( $found as $type ) {
			foreach ( $matches as $shortcode ) {
				if ( $type === $shortcode[2] ) {

					$attributes = shortcode_parse_atts( $shortcode[3] );

					if ( '' === $attributes ) { // Valid shortcode without any attributes.
						$attributes = array();
					}

					$galleries[ $shortcode[2] ] = $attributes;
				}
			}
		}

		// Recreate the shortcodes and then render them to get the HTML content.
		if ( $galleries ) {
			foreach ( $galleries as $shortcode => $attributes ) {
				$code   = '[' . $shortcode;
				foreach ( $attributes as $key => $value ) {
					$code   .= " $key=$value";
				}
				$code .= ']';
				$gallery_content .= do_shortcode( $code );
			}
		}

		return $gallery_content;
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
		
		self::get_gallery_images( $post, $images );

		$content .= self::get_content_from_galleries( $content );

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
		// These tags should be WITHOUT trailing space because some plugins such as the nextgen gallery put newlines immediately after <img.
		$total   = substr_count( $content, '<img' ) + substr_count( $content, '<IMG' );
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
				$this->debug_message( 'DOMDocument not found; using REGEX' );
			}
			preg_match_all( '/<img.*src=([\'"])?(.*?)\\1/', $content, $matches );
			if ( $matches && isset( $matches[2] ) ) {
				$images = array_merge( $images, $matches[2] );
			}
		}
	}

	/**
	 * Check whether a url is valid.
	 *
	 * @param string $url URL to check.
	 *
	 * @return bool
	 */
	public static function is_url_valid( $url ) {
		return filter_var( filter_var( $url, FILTER_SANITIZE_URL ), FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED ) !== false;
	}

}
