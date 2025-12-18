<?php
/**
 * Core Plugin Class
 *
 * Handles plugin initialization, hook registration, and class instantiation.
 * Implements singleton pattern to ensure only one instance exists.
 *
 * @package UDIA_Reviews_V2
 * @since 4.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UDIA Core Class
 *
 * Main plugin controller that initializes all plugin components
 * and registers necessary WordPress hooks.
 *
 * @since 4.0.0
 */
class UDIA_Core {

	/**
	 * Single instance of the class
	 *
	 * @since 4.0.0
	 * @var UDIA_Core|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * Ensures only one instance of the core class exists.
	 *
	 * @since 4.0.0
	 * @return UDIA_Core The singleton instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * Initializes hooks and plugin classes.
	 * Private to prevent direct instantiation.
	 *
	 * @since 4.0.0
	 */
	private function __construct() {
		$this->init_hooks();
		$this->init_classes();
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * Registers all necessary WordPress hooks for cache management
	 * and review lifecycle events.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'wp_insert_post', array( 'UDIA_Helpers', 'clear_caches_on_new_review' ), 10, 3 );
		add_action( 'wp_insert_post', array( 'UDIA_Helpers', 'clear_global_cache_on_review_change' ), 11, 3 );
		add_action(
			'delete_post',
			function( $post_id ) {
				$post = get_post( $post_id );
				if ( $post && $post->post_type === UDIA_Constants::CPT_SLUG ) {
					wp_cache_delete( 'udia_global_review_stats', UDIA_Constants::CACHE_GROUP_REVIEWS );
				}
			},
			11
		);
	}

	/**
	 * Initialize plugin classes
	 *
	 * Instantiates all plugin component classes in the correct order.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	private function init_classes() {
		new UDIA_CPT();
		new UDIA_Assets();
		new UDIA_AJAX();
		new UDIA_Shortcodes();
		new UDIA_Shop_Display();
	}
}
