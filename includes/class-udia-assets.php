<?php
/**
 * Assets Management Class
 *
 * Handles conditional loading of CSS and JavaScript files,
 * ensuring assets are only enqueued when needed for optimal performance.
 *
 * @package UDIA_Reviews_V2
 * @since 4.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UDIA Assets Class
 *
 * Manages plugin asset enqueuing with conditional loading based on page context.
 *
 * @since 4.0.0
 */
class UDIA_Assets {

	/**
	 * Constructor
	 *
	 * Hooks into WordPress enqueue scripts action.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 999 );
	}

	/**
	 * Check if assets should be loaded on current page
	 *
	 * Determines whether to load plugin assets based on page type,
	 * shortcode presence, and widget usage.
	 *
	 * @since 4.0.0
	 * @return bool True if assets should be loaded, false otherwise.
	 */
	private function should_load_assets() {
		global $post;

		// Always load in admin.
		if ( is_admin() ) {
			return true;
		}

		// Load on WooCommerce product pages.
		if ( function_exists( 'is_product' ) && is_product() ) {
			return true;
		}

		// Check if page contains any plugin shortcodes.
		if ( $post && is_a( $post, 'WP_Post' ) ) {
			$shortcodes = UDIA_Constants::get_shortcodes();

			foreach ( $shortcodes as $shortcode ) {
				if ( has_shortcode( $post->post_content, $shortcode ) ) {
					return true;
				}
			}
		}

		// Load if text widgets are active (may contain shortcodes).
		if ( is_active_widget( false, false, 'text' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue plugin assets
	 *
	 * Registers and enqueues CSS and JavaScript files with proper dependencies
	 * and localized script data for AJAX functionality.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function enqueue_assets() {
		// Check if assets should be loaded.
		if ( ! $this->should_load_assets() ) {
			return;
		}

		$plugin_url = UDIA_Constants::get_plugin_url();
		$version    = UDIA_Constants::VERSION;

		// Register and enqueue JavaScript.
		wp_register_script(
			'udia-v2-js',
			$plugin_url . 'assets/js/udia-script.js',
			array(),
			$version,
			true
		);

		// Register and enqueue CSS.
		wp_register_style(
			'udia-v2-css',
			$plugin_url . 'assets/css/udia-style.css',
			array(),
			$version
		);

		// Localize script with AJAX data.
		wp_localize_script(
			'udia-v2-js',
			'UDIA_REVIEW_V2',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( UDIA_Constants::get_nonce_action() ),
			)
		);

		wp_enqueue_script( 'udia-v2-js' );
		wp_enqueue_style( 'udia-v2-css' );
	}
}
