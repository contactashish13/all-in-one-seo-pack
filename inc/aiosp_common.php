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
	 * @var null|array
	 *
	 * @since 2.9.2
	 */
	public static $attachment_url_postids = null;

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
		} elseif ( strpos( $url, 'http://' ) === false ) {
			if ( 0 === strpos( $url, 'http:/' ) ) {
				$url    = $scheme . '://' . str_replace( 'http:/', '', $url );
			} elseif ( 0 === strpos( $url, 'http:' ) ) {
				$url    = $scheme . '://' . str_replace( 'http:', '', $url );
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
				$jetpack    = Jetpack_PostImages::get_images( $post->ID, self::get_dimensions_for_image_size( 'large' ) );
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

		// Check WooCommerce product gallery.
		if ( class_exists( 'WooCommerce' ) ) {
			$woo_images = get_post_meta( $post->ID, '_product_image_gallery', true );
			if ( ! empty( $woo_images ) ) {
				$woo_images = array_filter( explode( ',', $woo_images ) );
				if ( is_array( $woo_images ) ) {
					foreach ( $woo_images as $id ) {
						$images[] = wp_get_attachment_url( $id );
					}
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

		$shortcodes = apply_filters( 'aioseop_image_shortcodes', '', $post->ID );
		if ( ! empty( $shortcodes ) ) {
			$content    .= do_shortcode( $shortcodes );
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
				aiosp_log( 'DOMDocument not found; using REGEX' );
			}
			preg_match_all( '/<img.*src=([\'"])?(.*?)\\1/', $content, $matches );
			if ( $matches && isset( $matches[2] ) ) {
				$images = array_merge( $images, $matches[2] );
			}
		}
	}

	/**
	 * Fetch image attributes such as title and caption given the image URL.
	 *
	 * @param string $url The image URL.
	 */
	public static function get_image_attributes( $url ) {
		$attributes	= array();
		global $wpdb;
		$attachment = $wpdb->get_col( $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $url ) ); 
		if ( $attachment && is_array( $attachment ) && is_numeric( $attachment[0] ) ) {
			$attributes	= array(
				'image:caption' => self::get_image_caption( $attachment[0] ),
				'image:title' => get_the_title( $attachment[0] ),
			);
		}
		return $attributes;
	}

	/**
	 * Wrapper around `wp_get_attachment_caption` because it was introduced only in WP 4.6.0.
	 *
	 * @param int $attachment_id The attachment ID.
	 */
	public static function get_image_caption( $attachment_id ) {
		global $wp_version;
		if ( version_compare( $wp_version, '4.6.0', '<' ) ) {
			$post_id = (int) $attachment_id;
			if ( ! $post = get_post( $post_id ) ) {
				return false;
			}
		 
			if ( 'attachment' !== $post->post_type ) {
				return false;
			}
		 
			$caption = $post->post_excerpt;
			return apply_filters( 'wp_get_attachment_caption', $caption, $post->ID );
		}

		return wp_get_attachment_caption( $attachment_id );
	}

	/**
	 * Cleans the URL so that its acceptable in the sitemap.
	 *
	 * @param string $url The image url.
	 *
	 * @since 2.4.1
	 *
	 * @return string
	 */
	public static function clean_url( $url ) {
		// remove the query string.
		$url    = strtok( $url, '?' );
		// make the url XML-safe.
		$url    = htmlspecialchars( $url );
		// Make the url absolute, if its relative.
		$url    = aiosp_common::absolutize_url( $url );
		return apply_filters( 'aioseop_clean_url', $url );
	}

	/**
	 * Validate the image.
	 * NOTE: We will use parse_url here instead of wp_parse_url as we will correct the URLs beforehand and 
	 * this saves us the need to check PHP version support.
	 *
	 * @param string $image The image src.
	 *
	 * @since 2.4.1
	 * @since 2.4.3 Compatibility with Pre v4.7 wp_parse_url().
	 *
	 * @return bool
	 */
	public static function is_image_valid( $image ) {
		global $wp_version;

		// Bail if empty image.
		if ( empty( $image ) ) {
			return false;
		}

		global $wp_version;
		if ( version_compare( $wp_version, '4.4', '<' ) ) {
			$p_url = parse_url( $image );
			$url = $p_url['scheme'] . $p_url['host'] . $p_url['path'];
		} elseif ( version_compare( $wp_version, '4.7', '<' ) ) {
			// Compatability for older WP version that don't have 4.7 changes.
			// @link https://core.trac.wordpress.org/changeset/38726
			$p_url = wp_parse_url( $image );
			$url = $p_url['scheme'] . $p_url['host'] . $p_url['path'];
		} else {
			$component = PHP_URL_PATH;
			$url = wp_parse_url( $image, $component );
		}

		// make the url absolute, if its relative.
		$image      = aiosp_common::absolutize_url( $image );

		$extn       = pathinfo( parse_url( $image, PHP_URL_PATH ), PATHINFO_EXTENSION );
		$allowed    = apply_filters( 'aioseop_allowed_image_extensions', self::$image_extensions );
		// Bail if image does not refer to an image file otherwise google webmaster tools might reject the sitemap.
		if ( ! in_array( $extn, $allowed, true ) ) {
			return false;
		}

		$image_host = parse_url( $image, PHP_URL_HOST );
		$host       = parse_url( home_url(), PHP_URL_HOST );

		if ( $image_host !== $host ) {
			// Allowed hosts will be provided in a wildcard format i.e. img.yahoo.* or *.akamai.*.
			// And we will convert that into a regular expression for matching.
			$whitelist  = apply_filters( 'aioseop_images_allowed_from_hosts', array() );
			$allowed    = false;
			if ( $whitelist ) {
				foreach ( $whitelist as $pattern ) {
					if ( preg_match( '/' . str_replace( '*', '.*', $pattern ) . '/', $image_host ) === 1 ) {
						$allowed = true;
						break;
					}
				}
			}
			return $allowed;

		}
		return true;
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

	/**
	 * Renders the value XML safe.
	 */
	public static function make_xml_safe( $tag, $value ) {
		// some tags contain an array of values.
		if ( is_array( $value ) ) {
			return $value;
		}

		// sanitize the other tags.
		if ( in_array( $tag, array( 'guid', 'link', 'loc', 'image:loc' ), true ) ) {
			$value = esc_url( $value );
		} else {
			// some tags contain sanitized to some extent but they do not encode < and >.
			if ( ! in_array( $tag, array( 'image:title' ), true ) ) {
				$value = convert_chars( wptexturize( $value ) );
			}
		}
		return ent2ncr( esc_html( $value ) );
	}

	/**
	 * Attachment URL to Post ID
	 *
	 * Returns the (original) post/attachment ID from the URL param given. The function checks if URL is
	 * within, chacks for original attachment URLs, and then custom attachment URLs. The main intent for this function
	 * is to avoid having to query if possible (if cache was set prior), and if not, there is only 1 query per instance
	 * rather than multiple queries per instance.
	 * NOTE: Attempting to paginate the query actually caused the memory to peak higher.
	 * NOTE: The weakest point in this function is multiple calls to Result_2's SQL query for custom attachment URLs.
	 *
	 * This is intended to work much the same way as WP's `attachment_url_to_postid()`.
	 *
	 * @link https://developer.wordpress.org/reference/functions/attachment_url_to_postid/
	 *
	 * @see aiosp_common::set_transient_url_postids()
	 * @see get_transient()
	 * @link https://developer.wordpress.org/reference/functions/get_transient/
	 * @see wpdb::get_results()
	 * @link https://developer.wordpress.org/reference/classes/wpdb/get_results/
	 * @see wp_list_pluck()
	 * @link https://developer.wordpress.org/reference/functions/wp_list_pluck/
	 * @see wp_upload_dir()
	 * @link https://developer.wordpress.org/reference/functions/wp_upload_dir/
	 *
	 * @since 2.9.2
	 *
	 * @param string $url Full image URL.
	 * @return int
	 */
	public static function attachment_url_to_postid( $url ) {
		global $wpdb;
		static $results_1;
		static $results_2;

		$id = 0;
		$url_md5 = md5( $url );

		// Gets the URL => PostIDs array.
		// If static variable is still empty, load transient data.
		if ( is_null( self::$attachment_url_postids ) ) {
			if ( is_multisite() ) {
				self::$attachment_url_postids = get_site_transient( 'aioseop_multisite_attachment_url_postids' );
			} else {
				self::$attachment_url_postids = get_transient( 'aioseop_attachment_url_postids' );
			}

			// If no transient data, set as (default) empty array.
			if ( false === self::$attachment_url_postids ) {
				self::$attachment_url_postids = array();
			}
		}

		// Search for URL and get ID.
		if ( isset( self::$attachment_url_postids[ $url_md5 ] ) ) {
			// If static is already loaded and has URL, then return the URL's Post ID.
			$id = intval( self::$attachment_url_postids[ $url_md5 ] );
		} else {
			// Check to make sure Image URL is not outside the website.
			$uploads_dir = wp_upload_dir();
			if ( false !== strpos( $url, $uploads_dir['baseurl'] . '/' ) ) {
				// Results_1 query looks for URLs with the original guid that is uncropped and unedited.
				if ( is_null( $results_1 ) ) {
					$results_1 = aiosp_common::attachment_url_to_postid_query_1();
				}

				if ( isset( $results_1[ $url_md5 ] ) ) {
					$id = intval( $results_1[ $url_md5 ] );
				}

				// TODO Add setting to enable; this is TOO MEMORY INTENSE which could result in 1 or more crashes,
				// TODO however some may still need custom image URLs.
				// TODO NOTE: Transient data does prevent continual crashes.
				// else {
				// Results_2 query looks for the URL that is cropped and edited. This searches JSON strings
				// and returns the original attachment ID (there is no custom attachment IDs).
				//
				// if ( is_null( $results_2 ) ) {
				// $results_2 = aiosp_common::attachment_url_to_postid_query_2();
				// }
				//
				// if ( isset( $results_2[ $url_md5 ] ) ) {
				// $id = intval( $results_2[ $url_md5 ] );
				// }
				// }
			}

			self::$attachment_url_postids[ $url_md5 ] = $id;

			/**
			 * Sets the transient data at the last hook instead at every call.
			 *
			 * @see aiosp_common::set_transient_url_postids()
			 */
			add_action( 'shutdown', array( 'aiosp_common', 'set_transient_url_postids' ) );
		}

		return $id;
	}

	/**
	 * Sets the transient data at the last hook instead at every call.
	 *
	 * @see set_transient()
	 * @link https://developer.wordpress.org/reference/functions/set_transient/
	 *
	 * @since 2.9.2
	 */
	public static function set_transient_url_postids() {
		if ( is_multisite() ) {
			set_site_transient( 'aioseop_multisite_attachment_url_postids', self::$attachment_url_postids, 24 * HOUR_IN_SECONDS );
		} else {
			set_transient( 'aioseop_attachment_url_postids', self::$attachment_url_postids, 24 * HOUR_IN_SECONDS );
		}

	}

	/**
	 * Attachment URL to Post ID - Query 1
	 *
	 * This is intended to work solely with `aiosp_common::attachment_url_to_post_id()`. Calling this multiple times
	 * is memory intense.
	 *
	 * @see wpdb::get_results()
	 * @link https://developer.wordpress.org/reference/classes/wpdb/get_results/
	 *
	 * @return array
	 */
	public static function attachment_url_to_postid_query_1() {
		global $wpdb;

		$results_1 = $wpdb->get_results(
			"SELECT ID, MD5(guid) AS guid FROM $wpdb->posts WHERE post_type='attachment' AND post_status='inherit' AND post_mime_type LIKE 'image/%';",
			ARRAY_A
		);

		if ( $results_1 ) {
			$results_1 = array_combine(
				wp_list_pluck( $results_1, 'guid' ),
				wp_list_pluck( $results_1, 'ID' )
			);
		} else {
			$results_1 = array();
		}

		return $results_1;
	}

	/**
	 * Attachment URL to Post ID - Query 2
	 *
	 * Unused/Conceptual function. This is intended to work solely with `aiosp_common::attachment_url_to_post_id()`.
	 * Calling this multiple times is memory intense. It's intended to query for custom images, and data for those types
	 * of images only exists in the postmeta database table
	 *
	 * @todo Investigate unserialize() memory consumption/leak.
	 * @link https://www.evonide.com/breaking-phps-garbage-collection-and-unserialize/
	 *
	 * @see aiosp_common::attachment_url_to_postid()
	 * @see unserialize()
	 * @link http://php.net/manual/en/function.unserialize.php
	 * @see wpdb::get_results()
	 * @link https://developer.wordpress.org/reference/classes/wpdb/get_results/
	 * @see wp_upload_dir()
	 * @link https://developer.wordpress.org/reference/functions/wp_upload_dir/
	 *
	 * @return array
	 */
	public static function attachment_url_to_postid_query_2() {
		global $wpdb;

		$tmp_arr = array();
		// @codingStandardsIgnoreStart WordPress.WP.PreparedSQL.NotPrepared
		$results_2 = $wpdb->get_results(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE `meta_key` = '_wp_attachment_metadata' AND `meta_value` != '" . serialize( array() ) . "';",
			ARRAY_A
		);
		// @codingStandardsIgnoreStop WordPress.WP.PreparedSQL.NotPrepared
		if ( $results_2 ) {
			for ( $i = 0; $i < count( $results_2 ); $i++ ) {
				// TODO Investigate potentual memory leak(s); currently with unserialize.
				$meta_value = maybe_unserialize( $results_2[ $i ]['meta_value'] );

				// TODO Needs Discussion: Should this be added? To handle errors better instead of suspecting aioseop is at fault and lessen support threads.
				/**
				 * This currently handles "warning" notices with unserialize which normally can't be handled with a try/catch.
				 * However, this notice should be identified and corrected; which is seperate from the plugin, but
				 * can also triggered by the plugin.
				 *
				 * @see aiosp_common::error_handle_images()
				 * @see set_error_handler()
				 * @link http://php.net/manual/en/function.set-error-handler.php
				 * @see restore_error_handler()
				 * @link http://php.net/manual/en/function.restore-error-handler.php
				 */
				/*
				set_error_handler( 'aiosp_common::error_handle_images' );
				try {
					$meta_value = unserialize( $results_2[ $i ]['meta_value'] );
				} catch ( Exception $e ) {
					unset( $meta_value );
					restore_error_handler();
					continue;
				}
				restore_error_handler();
				*/

				// Images and Videos use different variable structures.
				if ( false === $meta_value || ! isset( $meta_value['file'] ) && ! isset( $meta_value['sizes'] ) ) {
					continue;
				}

				// Set the URL => PostIDs.
				$uploads_dir = wp_upload_dir();
				$custom_img_base_url = $uploads_dir['baseurl'] . '/' . str_replace( wp_basename( $meta_value['file'] ), '', $meta_value['file'] );
				foreach ( $meta_value['sizes'] as $image_size_arr ) {
					$tmp_arr[ md5( ( $custom_img_base_url . $image_size_arr['file'] ) ) ] = $results_2[ $i ]['post_id'];
				}

				unset( $meta_value );
			}
		}

		$results_2 = $tmp_arr;
		unset( $tmp_arr );

		return $results_2;
	}

	/**
	 * Error Hand Images
	 *
	 * Unused/Conceptual function potentually used in `aiosp_common::attachment_url_to_post_id_query_2()`.
	 * This is to handle errors where a normal try/catch wouldn't have the exception needed to catch.
	 *
	 * @see aiosp_common::attachment_url_to_post_id_query_2()
	 *
	 * @param $errno
	 * @param $errstr
	 * @param $errfile
	 * @param $errline
	 * @return bool
	 * @throws ErrorException
	 */
	public static function error_handle_images( $errno, $errstr, $errfile, $errline ) {
		// Possibly handle known issues differently.
		// Handles unserialize() warning notice.
		if ( 8 === $errno || strpos( $errstr , 'unserialize():' ) ) {
			throw new ErrorException( $errstr, $errno, 0, $errfile, $errline );
		} else {
			throw new ErrorException( $errstr, $errno, 0, $errfile, $errline );
		}

		return false;
	}
}
