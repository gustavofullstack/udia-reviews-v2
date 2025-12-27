<?php
/**
 * Plugin Name: TriqHub: Reviews
 * Plugin URI: https://github.com/gustavofullstack/triqhub-reviews
 * Description: Sistema de reviews com formulário, lista, carrossel e integração direta na página do produto. Layout unificado, centralizado e responsivo.
 * Version: 1.0.0
 * Author: Triq Hub
 * Requires at least: 6.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Text Domain: udia-reviews-v2
 * Domain Path: /languages
 *
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/gustavofullstack/triqhub-reviews
 * Release Asset: true
 *
 * @package UDIA_Reviews_V2
 * @author Triq Hub
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// TriqHub Invisible Connector
if ( ! class_exists( 'TriqHub_Connector' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/core/class-triqhub-connector.php';
    new TriqHub_Connector( 'TRQ-INVISIBLE-KEY', 'triqhub-reviews' );
}

// Prevent duplicate loading.
if ( defined( 'UDIA_REVIEW_V2_LOADED' ) ) {
	return;
}
define( 'UDIA_REVIEW_V2_LOADED', true );

// Load constants class first.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-udia-constants.php';

// Initialize constants with plugin file path.
UDIA_Constants::init( __FILE__ );

// Initialize Plugin Update Checker.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
	$myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/gustavofullstack/triqhub-reviews',
		__FILE__,
		'triqhub-reviews'
	);
	// Optional: Set the branch that contains the stable release.
	$myUpdateChecker->getVcsApi()->enableReleaseAssets();
}

// Define legacy constants for backward compatibility.
define( 'UDIA_REVIEW_V2_VERSION', '1.0.0' );
define( 'UDIA_REVIEW_V2_PATH', UDIA_Constants::get_plugin_path() );
define( 'UDIA_REVIEW_V2_URL', UDIA_Constants::get_plugin_url() );

/**
 * Check if WooCommerce is active
 *
 * Displays an admin notice if WooCommerce is not installed or active.
 *
 * @since 1.0.0
 * @return bool True if WooCommerce is active, false otherwise.
 */
function udia_v2_check_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function() {
				$message = sprintf(
					/* translators: %s: Plugin name */
					__( '%s: Este plugin requer o WooCommerce instalado e ativo para funcionar.', 'udia-reviews-v2' ),
					'<strong>UDIA Reviews V2</strong>'
				);
				echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
			}
		);
		return false;
	}
	return true;
}

// Load plugin classes.
require_once UDIA_Constants::get_plugin_path() . 'includes/class-udia-helpers.php';
require_once UDIA_Constants::get_plugin_path() . 'includes/class-udia-security.php';
require_once UDIA_Constants::get_plugin_path() . 'includes/class-udia-cpt.php';
require_once UDIA_Constants::get_plugin_path() . 'includes/class-udia-assets.php';
require_once UDIA_Constants::get_plugin_path() . 'includes/class-udia-ajax.php';
require_once UDIA_Constants::get_plugin_path() . 'includes/class-udia-shortcodes.php';
require_once UDIA_Constants::get_plugin_path() . 'includes/class-udia-shop-display.php';
require_once UDIA_Constants::get_plugin_path() . 'includes/class-udia-core.php';

/**
 * Initialize the plugin
 *
 * Loads text domain and initializes core functionality if WooCommerce is active.
 *
 * @since 1.0.0
 * @return void
 */
function udia_v2_init() {
	// Load text domain for translations.
	load_plugin_textdomain(
		UDIA_Constants::TEXT_DOMAIN,
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	// Initialize plugin if WooCommerce is active.
	if ( udia_v2_check_woocommerce() ) {
		UDIA_Core::get_instance();
	}
}
add_action( 'plugins_loaded', 'udia_v2_init' );

/**
 * Plugin activation hook
 *
 * Creates database indexes and flushes rewrite rules on plugin activation.
 *
 * @since 1.0.0
 * @return void
 */
function udia_v2_activate() {
	// Create indexes for better performance.
	UDIA_Helpers::create_meta_indexes();
	
	// Flush rewrite rules to register the CPT.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'udia_v2_activate' );


// TriqHub Styling
function triqhub_enqueue_admin_udia_reviews_v2() {
    wp_enqueue_style( 'triqhub-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/triqhub-admin.css' );
}
add_action( 'admin_enqueue_scripts', 'triqhub_enqueue_admin_udia_reviews_v2' );
