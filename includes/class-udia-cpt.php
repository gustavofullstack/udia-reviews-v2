<?php
/**
 * Custom Post Type Registration Class
 *
 * Handles registration of the 'reviews' custom post type with proper labels,
 * supports, and WordPress integration.
 *
 * @package UDIA_Reviews_V2
 * @since 4.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UDIA CPT Class
 *
 * Registers the reviews custom post type for storing customer reviews.
 *
 * @since 4.0.0
 */
class UDIA_CPT {

	/**
	 * Constructor
	 *
	 * Hooks into WordPress init action to register the custom post type.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_cpt' ) );
	}

	/**
	 * Register reviews custom post type
	 *
	 * Registers the 'reviews' CPT with appropriate labels, supports,
	 * and WordPress REST API integration.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function register_cpt() {
		// Prevent duplicate registration.
		if ( post_type_exists( UDIA_Constants::CPT_SLUG ) ) {
			return;
		}

		$labels = array(
			'name'                  => _x( 'Reviews', 'Post type general name', 'udia-reviews-v2' ),
			'singular_name'         => _x( 'Review', 'Post type singular name', 'udia-reviews-v2' ),
			'menu_name'             => _x( 'Reviews', 'Admin menu name', 'udia-reviews-v2' ),
			'name_admin_bar'        => _x( 'Review', 'Add new on admin bar', 'udia-reviews-v2' ),
			'add_new'               => __( 'Adicionar Novo', 'udia-reviews-v2' ),
			'add_new_item'          => __( 'Adicionar Novo Review', 'udia-reviews-v2' ),
			'new_item'              => __( 'Novo Review', 'udia-reviews-v2' ),
			'edit_item'             => __( 'Editar Review', 'udia-reviews-v2' ),
			'view_item'             => __( 'Ver Review', 'udia-reviews-v2' ),
			'all_items'             => __( 'Todos os Reviews', 'udia-reviews-v2' ),
			'search_items'          => __( 'Buscar Reviews', 'udia-reviews-v2' ),
			'parent_item_colon'     => __( 'Reviews Pai:', 'udia-reviews-v2' ),
			'not_found'             => __( 'Nenhum review encontrado.', 'udia-reviews-v2' ),
			'not_found_in_trash'    => __( 'Nenhum review encontrado na lixeira.', 'udia-reviews-v2' ),
			'archives'              => __( 'Arquivos de Reviews', 'udia-reviews-v2' ),
			'insert_into_item'      => __( 'Inserir no review', 'udia-reviews-v2' ),
			'uploaded_to_this_item' => __( 'Enviado para este review', 'udia-reviews-v2' ),
			'filter_items_list'     => __( 'Filtrar lista de reviews', 'udia-reviews-v2' ),
			'items_list_navigation' => __( 'Navegação da lista de reviews', 'udia-reviews-v2' ),
			'items_list'            => __( 'Lista de reviews', 'udia-reviews-v2' ),
		);

		$args = array(
			'labels'       => $labels,
			'public'       => true,
			'menu_icon'    => 'dashicons-star-filled',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
			'has_archive'  => true,
			'rewrite'      => array( 'slug' => UDIA_Constants::CPT_SLUG ),
			'show_in_rest' => true,
			'description'  => __( 'Avaliações e depoimentos de clientes sobre produtos', 'udia-reviews-v2' ),
		);

		register_post_type( UDIA_Constants::CPT_SLUG, $args );
	}
}
