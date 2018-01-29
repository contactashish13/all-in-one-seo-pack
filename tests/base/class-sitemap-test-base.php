<?php
/**
 * Class Test_Sitemap
 *
 * @package 
 */

/**
 * Sitemap test case.
 */

require_once AIOSEOP_UNIT_TESTING_DIR . '/base/class-aioseop-test-base.php';

class Sitemap_Test_Base extends AIOSEOP_Test_Base {

	/**
	 * Check whether the sitemap is valid on the basis of given conditions.
	 *
	 * @param array $elements this is the array that is used to compare with the sitemap. It can have a variety of structures but the key is always the URL:
	 * The values can be
	 * 1) a null - this will check for existence of the url
	 * 2) a boolean - true will check if the url exists, false if the url does not exist.
	 * 3) an array - each element of the array will be the name of the XML node. The value, again, can be 
	 *		i) a boolean - true will check if the node exists, false if the node does not exist.
	 *		ii) a string - the value of the node should be the same as this value.
	 * 
	*/
	protected final function validate_sitemap( $elements, $debug = false ) {
		add_filter( 'aioseo_sitemap_ping', '__return_false' );
		update_option( 'blog_public', 0 );

		// sitemap will be created in the root of the folder.
		do_action( 'aiosp_sitemap_settings_update' );

		$file = ABSPATH . '/sitemap.xml';

		$this->assertFileExists( $file );

		if ( $debug ) {
			echo file_get_contents($file);
		}
		$xml = simplexml_load_file( $file );
		$ns = $xml->getNamespaces(true);

		$sitemap = array();
		foreach ( $xml->url as $url ) {
			$element = array();
			if ( array_key_exists( 'image', $ns ) && count( $url->children( $ns['image'] ) ) > 0 ) {
				$images = array();
				foreach ( $url->children( $ns['image'] ) as $image ) {
					$images[] = (string) $image->loc;
				}
				$element['image'] = $images;
			}
			$sitemap[ (string) $url->loc ] = $element;
		}

		if ( $debug ) {
			error_log( print_r( $sitemap, true ) );
		}

		foreach ( $elements as $url => $attributes ) {
			if ( is_null( $attributes ) ) {
				// no attributes? then just test if the url exists.
				$this->assertArrayHasKey( $url, $sitemap );
				continue;
			}
			if ( is_bool( $attributes ) ) {
				if ( true === $attributes ) {
					// just test if the url exists.
					$this->assertArrayHasKey( $url, $sitemap );
					continue;
				}
				// just test if the url NOT exists.
				$this->assertArrayNotHasKey( $url, $sitemap );
				continue;
			}

			foreach ( $attributes as $name => $value ) {
				if ( $debug ) {
					error_log( "Testing for $url " . print_r( $sitemap[ $url ], true ) );
				}
				if ( is_bool( $value ) ) {
					// just check if this element exists/not-exists.
					if ( true === $value ) {
						$this->assertArrayHasKey( $name, $sitemap[ $url ] );
						continue;
					}
					$this->assertArrayNotHasKey( $name, $sitemap[ $url ] );
					continue;
				}
				$this->assertEquals( $value, $sitemap[ $url ][ $name ] );
			}
		}

		@unlink( $file );
	}
}