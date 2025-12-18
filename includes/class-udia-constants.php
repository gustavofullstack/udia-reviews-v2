<?php
/**
 * Plugin Constants Class
 *
 * Centralizes all plugin constants for better maintainability and consistency.
 * This class provides static access to plugin configuration values, paths,
 * cache settings, and security limits.
 *
 * @package UDIA_Reviews_V2
 * @since 4.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UDIA Constants Class
 *
 * Provides centralized access to all plugin constants and configuration values.
 *
 * @since 4.0.0
 */
class UDIA_Constants {

	/**
	 * Plugin version
	 *
	 * @since 4.0.0
	 * @var string
	 */
	const VERSION = '4.0';

	/**
	 * Text domain for internationalization
	 *
	 * @since 4.0.0
	 * @var string
	 */
	const TEXT_DOMAIN = 'udia-reviews-v2';

	/**
	 * Custom Post Type slug
	 *
	 * @since 4.0.0
	 * @var string
	 */
	const CPT_SLUG = 'reviews';

	/**
	 * Plugin path (set dynamically)
	 *
	 * @since 4.0.0
	 * @var string
	 */
	private static $plugin_path;

	/**
	 * Plugin URL (set dynamically)
	 *
	 * @since 4.0.0
	 * @var string
	 */
	private static $plugin_url;

	/**
	 * Cache group for fragments
	 *
	 * @since 4.0.0
	 * @var string
	 */
	const CACHE_GROUP_FRAGMENTS = 'udia_fragments';

	/**
	 * Cache group for reviews
	 *
	 * @since 4.0.0
	 * @var string
	 */
	const CACHE_GROUP_REVIEWS = 'udia_reviews';

	/**
	 * Default cache TTL in seconds (10 minutes)
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const CACHE_TTL_DEFAULT = 600;

	/**
	 * Fragment cache TTL in seconds (10 minutes)
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const CACHE_TTL_FRAGMENT = 600;

	/**
	 * Stats cache TTL in seconds (10 minutes)
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const CACHE_TTL_STATS = 600;

	/**
	 * Transient cache TTL in seconds (5 minutes)
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const CACHE_TTL_TRANSIENT = 300;

	/**
	 * Rate limit: maximum review submissions per user
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const RATE_LIMIT_REVIEWS_MAX = 5;

	/**
	 * Rate limit: period in seconds (1 hour)
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const RATE_LIMIT_PERIOD = 3600; // HOUR_IN_SECONDS

	/**
	 * Rate limit: minimum time between reviews (5 minutes)
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const RATE_LIMIT_REVIEW_INTERVAL = 300; // 5 * MINUTE_IN_SECONDS

	/**
	 * Minimum review text length
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const TEXT_LENGTH_MIN = 10;

	/**
	 * Maximum review text length
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const TEXT_LENGTH_MAX = 2000;

	/**
	 * Default rating value
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const RATING_DEFAULT = 5;

	/**
	 * Minimum rating value
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const RATING_MIN = 1;

	/**
	 * Maximum rating value
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const RATING_MAX = 5;

	/**
	 * Maximum security logs to keep
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const SECURITY_LOG_MAX = 100;

	/**
	 * Accent color (neon green)
	 *
	 * @since 4.0.0
	 * @var string
	 */
	const COLOR_ACCENT = '#39ff14';

	/**
	 * Initialize plugin path and URL
	 *
	 * @since 4.0.0
	 * @param string $plugin_file Main plugin file path.
	 * @return void
	 */
	public static function init( $plugin_file ) {
		self::$plugin_path = plugin_dir_path( $plugin_file );
		self::$plugin_url  = plugin_dir_url( $plugin_file );
	}

	/**
	 * Get plugin path
	 *
	 * @since 4.0.0
	 * @return string Plugin directory path.
	 */
	public static function get_plugin_path() {
		return self::$plugin_path;
	}

	/**
	 * Get plugin URL
	 *
	 * @since 4.0.0
	 * @return string Plugin directory URL.
	 */
	public static function get_plugin_url() {
		return self::$plugin_url;
	}

	/**
	 * Get AJAX nonce action name
	 *
	 * @since 4.0.0
	 * @return string Nonce action name.
	 */
	public static function get_nonce_action() {
		return 'udia_review_v2_nonce';
	}

	/**
	 * Get AJAX nonce key
	 *
	 * @since 4.0.0
	 * @return string Nonce key for AJAX requests.
	 */
	public static function get_nonce_key() {
		return 'nonce';
	}

	/**
	 * Get list of all registered shortcodes
	 *
	 * @since 4.0.0
	 * @return array List of shortcode names.
	 */
	public static function get_shortcodes() {
		return array(
			'udia_review_v2_form',
			'udia_review_v2_list',
			'udia_review_v2_carousel',
			'udia_product_review_summary',
			'udia_product_full_reviews',
			'udia_global_review_summary',
		);
	}
}
