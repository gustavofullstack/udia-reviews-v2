<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UDIA_Shortcodes {

	public function __construct() {
		add_shortcode( 'udia_review_v2_form', array( $this, 'form_shortcode' ) );
		add_shortcode( 'udia_review_v2_list', array( $this, 'list_shortcode' ) );
		add_shortcode( 'udia_review_v2_carousel', array( $this, 'carousel_shortcode' ) );
		add_shortcode( 'udia_product_review_summary', array( $this, 'product_review_summary_shortcode' ) );
		add_shortcode( 'udia_product_full_reviews', array( $this, 'product_full_reviews_shortcode' ) );
		add_shortcode( 'udia_global_review_summary', array( $this, 'global_review_summary_shortcode' ) );
	}

	public function form_shortcode( $atts ) {
		$login_url = wp_login_url( get_permalink() );
		if ( ! is_user_logged_in() ) {
			return '<div class="udia-review-wrap"><p style="color:#fff">Você precisa <a href="' . esc_url( $login_url ) . '" style="color:var(--udia-accent);font-weight:700">entrar</a> pra enviar um depoimento.</p></div>';
		}

		$user = wp_get_current_user();
		$name_val = esc_attr( $user->display_name ?: $user->user_login );

		$html = '<div class="udia-review-wrap">';
		$html .= '<form class="udia-review-form" method="post" aria-label="Formulário de depoimento">';
		$html .= '<label for="udia-name">Nome</label>';
		$html .= '<input class="udia-input" id="udia-name" type="text" name="name" value="' . $name_val . '" maxlength="60" required readonly />';
		$html .= '<label for="udia-product">Produto (último pedido)</label>';
		$html .= '<select class="udia-select" name="order_item_id" id="udia-product" disabled>';
		$html .= '<option>Carregando itens do seu último pedido...</option>';
		$html .= '</select>';
		$html .= '<div class="udia-manual-product-wrap"><label>Produto (se não tiver pedido)</label><input class="udia-input" type="text" name="manual_product" placeholder="Nome do produto/variação" /></div>';
		// Honeypot field - hidden from real users
		$html .= '<div style="display:none !important; visibility:hidden; opacity:0; height:0; width:0; overflow:hidden;"><label>Não preencha este campo se for humano <input type="text" name="udia_honeypot" value="" tabindex="-1" autocomplete="off"></label></div>';
		$html .= '<label>Avaliação</label><div class="udia-rating"><div class="udia-stars">';
		for ( $i = 5; $i >= 1; $i-- ) {
			$id = 'udia-v2-star-' . $i;
			$html .= '<span class="udia-star"><input type="radio" name="rating" id="' . $id . '" value="' . $i . '"><label for="' . $id . '">' . $i . '★</label></span>';
		}
		$html .= '</div></div>';
		$html .= '<label for="udia-content">Depoimento</label><textarea class="udia-textarea" id="udia-content" name="content" rows="4" maxlength="2000" required placeholder="Conta como foi a experiência..."></textarea>';
		$html .= '<button class="udia-btn" type="submit">Enviar depoimento</button>';
		$html .= '</form></div>';
		return $html;
	}

	public function list_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'posts_per_page' => 20 ), $atts, 'udia_review_v2_list' );
		
		$cache_key = 'udia_reviews_list_' . md5( serialize( $atts ) );
		$cached_output = wp_cache_get( $cache_key, 'udia_reviews' );
		
		if ( $cached_output !== false ) {
			return $cached_output;
		}
		
		$q = new WP_Query( array(
			'post_type'      => 'reviews',
			'post_status'    => 'publish',
			'posts_per_page' => intval( $atts['posts_per_page'] ),
			'orderby'        => 'date',
			'order'          => 'DESC'
		) );
		$out = '<div id="udia-reviews-list" class="udia-reviews-list">';
		while ( $q->have_posts() ) {
			$q->the_post();
			$pid = get_the_ID();
			$title = preg_replace( '/^([^—]+)(?: — .*)?$/', '$1', get_the_title() );
			$content = get_the_content();
			$rating = intval( get_post_meta( $pid, 'rating', true ) ?: 5 );
			$author_id = get_post_field( 'post_author', $pid );
			$prod_label = get_post_meta( $pid, 'product_label', true );
			
			$avatar_data = UDIA_Helpers::generate_initials_avatar( $title, $author_id );
			$avatar = '<div class="udia-review-avatar" style="background:' . $avatar_data['bg_color'] . '; color:' . $avatar_data['text_color'] . ';">' . $avatar_data['initials'] . '</div>';
			
			$out .= '<div class="udia-single-review" id="udia-review-' . $pid . '">';
			$out .= $avatar;
			$out .= '<div class="udia-review-author-name">' . esc_html( $title ) . '</div>';
			$out .= '<div class="udia-review-rating">' . esc_html( str_repeat( '★', $rating ) ) . '</div>';
			if ( $prod_label ) {
				$out .= '<div class="udia-review-product">Produto: ' . esc_html( $prod_label ) . '</div>';
			}
			$out .= '<div class="udia-review-content">' . wpautop( esc_html( $content ) ) . '</div>';
			$out .= '</div>';
		}
		wp_reset_postdata();
		$out .= '</div>';
		
		wp_cache_set( $cache_key, $out, 'udia_reviews', 10 * MINUTE_IN_SECONDS );
		
		return $out;
	}

	public function carousel_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'posts_per_page' => 10,
		), $atts, 'udia_review_v2_carousel' );
		
		$cache_key = 'udia_reviews_carousel_' . md5( serialize( $atts ) );
		$cached_output = wp_cache_get( $cache_key, 'udia_reviews' );
		
		if ( $cached_output !== false ) {
			return $cached_output;
		}

		$q = new WP_Query( array(
			'post_type'      => 'reviews',
			'post_status'    => 'publish',
			'posts_per_page' => intval( $atts['posts_per_page'] ),
			'orderby'        => 'date',
			'order'          => 'DESC'
		) );

		if ( ! $q->have_posts() ) {
			return '';
		}

		$out = '<div class="udia-review-carousel-wrap">';
		$out .= '<div class="udia-carousel-viewport">';
		$out .= '<div class="udia-carousel-track">';

		while ( $q->have_posts() ) {
			$q->the_post();
			$pid = get_the_ID();
			$title = preg_replace( '/^([^—]+)(?: — .*)?$/', '$1', get_the_title() );
			$content = get_the_content();
			$rating = intval( get_post_meta( $pid, 'rating', true ) ?: 5 );
			$author_id = get_post_field( 'post_author', $pid );
			$prod_label = get_post_meta( $pid, 'product_label', true );

			$avatar_data = UDIA_Helpers::generate_initials_avatar( $title, $author_id );
			$avatar = '<div class="udia-review-avatar" style="background:' . $avatar_data['bg_color'] . '; color:' . $avatar_data['text_color'] . ';">' . $avatar_data['initials'] . '</div>';

			$out .= '<div class="udia-carousel-slide">';
			$out .= '<div class="udia-single-review">';
			$out .= $avatar;
			$out .= '<div class="udia-review-author-name">' . esc_html( $title ) . '</div>';
			$out .= '<div class="udia-review-rating">' . esc_html( str_repeat( '★', $rating ) ) . '</div>';
			if ( $prod_label ) {
				$out .= '<div class="udia-review-product">Produto: ' . esc_html( $prod_label ) . '</div>';
			}
			$trimmed_content = wp_trim_words( $content, 20, '...' );
			$out .= '<div class="udia-review-content">' . wpautop( esc_html( $trimmed_content ) ) . '</div>';
			$out .= '</div></div>';
		}
		wp_reset_postdata();

		$out .= '</div>'; // .udia-carousel-track
		$out .= '</div>'; // .udia-carousel-viewport
		
		// Contêiner de navegação embaixo dos slides
		$out .= '<div class="udia-carousel-nav">';
		$out .= '<button class="udia-carousel-btn udia-carousel-btn--prev" aria-label="Anterior"><</button>';
		
		// Dots de navegação
		$out .= '<div class="udia-carousel-dots">';
		for ( $i = 0; $i < min( 5, $q->post_count ); $i++ ) {
			$out .= '<button class="udia-carousel-dot" aria-label="Ir para slide ' . ( $i + 1 ) . '"></button>';
		}
		$out .= '</div>';
		
		$out .= '<button class="udia-carousel-btn udia-carousel-btn--next" aria-label="Próximo">></button>';
		$out .= '</div>'; // .udia-carousel-nav
		
		$out .= '</div>'; // .udia-review-carousel-wrap
		
		wp_cache_set( $cache_key, $out, 'udia_reviews', 10 * MINUTE_IN_SECONDS );
		
		return $out;
	}

	public function product_review_summary_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'product_id'   => 0,
			'variation_id' => 0,
		), $atts, 'udia_product_review_summary' );

		if ( empty( $atts['product_id'] ) && function_exists( 'is_product' ) && is_product() ) {
			global $post;
			$product = wc_get_product( $post->ID );
			if ( $product ) {
				$atts['product_id'] = $product->get_id();
				if ( $product->is_type( 'variation' ) ) {
					$atts['variation_id'] = $product->get_id();
					$atts['product_id'] = $product->get_parent_id();
				}
			}
		}

		if ( empty( $atts['product_id'] ) ) {
			return '';
		}

		$stats = UDIA_Helpers::get_product_review_stats( $atts['product_id'], $atts['variation_id'] );

		$display_rating = $stats['total_reviews'] > 0 ? $stats['average_rating'] : 5.0;
		$total_reviews = $stats['total_reviews'];

		$full_stars = floor( $display_rating );
		$half_star = ( $display_rating - $full_stars ) >= 0.5 ? 1 : 0;
		$empty_stars = 5 - $full_stars - $half_star;

		$stars_html = '';
		for ( $i = 0; $i < $full_stars; $i++ ) {
			$stars_html .= '<span class="udia-product-star filled">★</span>';
		}
		if ( $half_star ) {
			// Conta como cheia para o visual
			$stars_html .= '<span class="udia-product-star filled">★</span>';
		}
		for ( $i = 0; $i < $empty_stars; $i++ ) {
			$stars_html .= '<span class="udia-product-star">★</span>';
		}

		$html = '<div class="udia-product-review-summary">';
		$html .= '<div class="udia-product-stars">' . $stars_html . '</div>';
		if ( $total_reviews > 0 ) {
			$html .= '<div class="udia-product-review-count">' . $total_reviews . '</div>';
		}
		$html .= '</div>';

		return $html;
	}

	public function product_full_reviews_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'product_id'     => 0,
			'variation_id'   => 0,
			'posts_per_page' => 10,
		), $atts, 'udia_product_full_reviews' );

		if ( empty( $atts['product_id'] ) && function_exists( 'is_product' ) && is_product() ) {
			global $post;
			$product = wc_get_product( $post->ID );
			if ( $product ) {
				$atts['product_id'] = $product->get_id();
				if ( $product->is_type( 'variation' ) ) {
					$atts['variation_id'] = $product->get_id();
					$atts['product_id'] = $product->get_parent_id();
				}
			}
		}

		if ( empty( $atts['product_id'] ) ) {
			return '';
		}

		$cache_key = 'udia_product_full_reviews_' . md5( serialize( $atts ) );
		$cached_output = wp_cache_get( $cache_key, 'udia_reviews' );

		if ( $cached_output !== false ) {
			return $cached_output;
		}

		$reviews_query = UDIA_Helpers::get_product_reviews( $atts['product_id'], $atts['variation_id'], intval( $atts['posts_per_page'] ) );

		if ( ! $reviews_query->have_posts() ) {
			$out = '<div class="udia-product-full-reviews">';
			$out .= '<p style="text-align: center; color: var(--udia-text-muted); padding: 2rem 1rem;">Este produto ainda não possui avaliações.</p>';
			$out .= '</div>';
			wp_cache_set( $cache_key, $out, 'udia_reviews', 10 * MINUTE_IN_SECONDS );
			return $out;
		}

		$out = '<div class="udia-product-full-reviews">';
		$out .= '<h3>Avaliações de Clientes</h3>';
		$out .= '<div class="udia-reviews-list">';

		while ( $reviews_query->have_posts() ) {
			$reviews_query->the_post();
			$pid = get_the_ID();
			$title = preg_replace( '/^([^—]+)(?: — .*)?$/', '$1', get_the_title() );
			$content = get_the_content();
			$rating = intval( get_post_meta( $pid, 'rating', true ) ?: 5 );
			$author_id = get_post_field( 'post_author', $pid );
			$prod_label = get_post_meta( $pid, 'product_label', true );

			$avatar_data = UDIA_Helpers::generate_initials_avatar( $title, $author_id );
			$avatar = '<div class="udia-review-avatar" style="background:' . $avatar_data['bg_color'] . '; color:' . $avatar_data['text_color'] . ';">' . $avatar_data['initials'] . '</div>';

			$out .= '<div class="udia-single-review" id="udia-review-' . intval( $pid ) . '">';
			$out .= $avatar;
			$out .= '<div class="udia-review-author-name">' . esc_html( $title ) . '</div>';
			$out .= '<div class="udia-review-rating">' . str_repeat( '★', $rating ) . '</div>';
			if ( $prod_label ) {
				$out .= '<div class="udia-review-product">Produto: ' . esc_html( $prod_label ) . '</div>';
			}
			$out .= '<div class="udia-review-content">' . wpautop( esc_html( $content ) ) . '</div>';
			$out .= '</div>';
		}
		wp_reset_postdata();

		$out .= '</div></div>';

		wp_cache_set( $cache_key, $out, 'udia_reviews', 10 * MINUTE_IN_SECONDS );

		return $out;
	}

	public function global_review_summary_shortcode( $atts ) {
		$atts = shortcode_atts( array(), $atts, 'udia_global_review_summary' );

		$stats = UDIA_Helpers::get_global_review_stats();
		$average_rating = $stats['average_rating'];
		$total_reviews = $stats['total_reviews'];

		$display_rating = $total_reviews > 0 ? $average_rating : 5.0;
		$full_stars = floor( $display_rating );
		$has_half = ( $display_rating - $full_stars ) >= 0.5;
		$empty_stars = 5 - $full_stars - ( $has_half ? 1 : 0 );

		$stars_html = '';
		for ( $i = 0; $i < $full_stars; $i++ ) {
			$stars_html .= '<span class="udia-product-star filled">★</span>';
		}
		if ( $has_half ) {
			$stars_html .= '<span class="udia-product-star filled">★</span>';
		}
		for ( $i = 0; $i < $empty_stars; $i++ ) {
			$stars_html .= '<span class="udia-product-star">★</span>';
		}

		$html = '<a href="https://udiapods.com/feedback/" style="text-decoration:none; color:inherit; display:inline-block;">';
		$html .= '<div class="udia-product-review-summary">';
		$html .= '<div class="udia-product-stars">' . $stars_html . '</div>';
		if ( $total_reviews > 0 ) {
			$html .= '<div class="udia-product-review-count">' . $total_reviews . '</div>';
		} else {
			$html .= '<div class="udia-product-review-count">0</div>';
		}
		$html .= '</div>';
		$html .= '</a>';

		return $html;
	}
}
