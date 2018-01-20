<?php
/**
 * AIOSEOP testing base class.
 */
class AIOSEOP_Unit_Test_Base extends WP_UnitTestCase {

	/**
	 * Upload an image and, optionally, attach to the post.
	*/
	protected final function upload_image_and_maybe_attach( $image, $id = 0 ) {
		/* this factory method has a bug so we have to be a little clever.
		$this->factory->attachment->create( array( 'file' => $image, 'post_parent' => $id ) );
		*/
		$attachment_id = $this->factory->attachment->create_upload_object( $image, $id );
		if ( 0 !== $id ) {
			update_post_meta( $id, '_thumbnail_id', $attachment_id );
		}
		return $attachment_id;
	}

	protected final function init() {
		$this->clean();
	}

	/**
	 * Clean up the flotsam and jetsam before starting.
	*/
	protected final function clean() {
		$posts	= get_posts(
			array(
				'post_type' => 'any',
				'fields'	=> 'ids',
				'numberposts' => -1,
		) );

		foreach ( $posts as $post ) {
			wp_delete_post( $post, true );
		}
	}

	/**
	 * Set up the options for the particular module.
	*/
	protected final function _setup_options( $module, $custom_options ) {
		// so that is_admin returns true.
		set_current_screen( 'edit-post' );
		wp_set_current_user( 1 );

		// init the general options.
		do_action( 'init' );

		global $aioseop_options;

		// activate the sitemap module.
		$aioseop_options['modules'] = array(
			'aiosp_feature_manager_options'	=> array(
				"aiosp_feature_manager_enable_$module"	=> 'on'
			),
		);
		update_option( 'aioseop_options', $aioseop_options );

		set_current_screen( 'edit-post' );
		do_action( 'init' );

		$nonce		= wp_create_nonce( 'aioseop-nonce' );
		$class		= 'All_in_One_SEO_Pack_' . ucwords( $module );
		$_POST		= array(
			'action'				=> 'aiosp_update_module',
			'Submit_All_Default'	=> 'blah',
			'Submit'				=> 'blah',
			'nonce-aioseop'			=> $nonce,
			'settings'				=> ' ',
			'options'				=> "aiosp_feature_manager_enable_{$module}=true&page=" . trailingslashit( AIOSEOP_PLUGIN_DIRNAME ) . "modules/aioseop_feature_manager.php&Submit=testing!&module={$class}&nonce-aioseop=" . $nonce,
		);

		// so that is_admin returns true.
		set_current_screen( 'edit-post' );

		// this action will also try to regenerate the sitemap, but we will not let that bother us.
		do_action( 'wp_ajax_aioseop_ajax_save_settings' );

		$this->go_to( admin_url( 'admin.php?page=all-in-one-seo-pack%2Fmodules%2Faioseop_sitemap.php' ) );
		do_action( 'admin_menu' );

		$aioseop_options = get_option( 'aioseop_options' );

		$module_options = $aioseop_options['modules']["aiosp_{$module}_options"];
		$module_options = array_merge( $module_options, $custom_options );

		$aioseop_options['modules']["aiosp_{$module}_options"] = $module_options;

		update_option( 'aioseop_options', $aioseop_options );

		$aioseop_options = get_option( 'aioseop_options' );
		//error_log("aioseop_options " . print_r($aioseop_options,true));
	}

	protected final function setup_posts( $without_images = 0, $with_images = 0, $type = 'post' ) {
		if ( $without_images > 0 ) {
			$this->factory->post->create_many( $without_images, array( 'post_type' => $type, 'post_content' => 'content without image', 'post_title' => 'title without image' ) );
		}
		if ( $with_images > 0 ) {
			$ids	= $this->factory->post->create_many( $with_images, array( 'post_type' => $type, 'post_content' => 'content with image', 'post_title' => 'title with image' ) );
			foreach ( $ids as $id ) {
				$this->upload_image_and_maybe_attach( str_replace( '\\', '/', trailingslashit( __DIR__ ) . 'resources/images/footer-logo.png' ), $id );
			}
		}

		$posts	= get_posts(
			array(
				'post_type' => $type,
				'fields'	=> 'ids',
				'numberposts' => -1,
		) );

		// 4 posts created?
		$this->assertEquals( $without_images + $with_images, count( $posts ) );

		foreach ( $posts as $id ) {
			get_permalink( $id );
		}

		$attachments	= get_posts(
			array(
				'post_type' => 'attachment',
				'fields'	=> 'ids',
		) );

		// 2 attachments created?
		$this->assertEquals( $with_images, count( $attachments ) );

		$with = array();
		$without = array();

		$featured	= 0;
		foreach ( $posts as $id ) {
			if ( has_post_thumbnail( $id ) ) {
				$featured++;
				$with[] = get_permalink( $id );
				continue;
			}
			$without[] = get_permalink( $id );
		}

		// 2 posts have featured image?
		$this->assertEquals( $with_images, $featured );

		return array(
			'with'	=> $with,
			'without'	=> $without,
		);
	}

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

		$this->assertFileIsReadable( $file );

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

	public function test_nothing(){
		$this->assertTrue(true);
	}

}


