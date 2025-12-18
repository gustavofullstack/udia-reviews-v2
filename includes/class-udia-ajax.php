<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UDIA_AJAX {

	public function __construct() {
		add_action( 'wp_ajax_udia_v2_fetch_last_order_products', array( $this, 'fetch_last_order_products' ) );
		add_action( 'wp_ajax_udia_v2_submit_review', array( $this, 'submit_review' ) );
	}

	public function fetch_last_order_products() {
		check_ajax_referer( 'udia_review_v2_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Login requerido' ), 403 );
		}
		
		$user_id = get_current_user_id();
		
		// Usa cache de transient pra evitar consultas repetidas ao banco
		$cache_key = 'udia_last_order_products_' . $user_id;
		$cached_data = get_transient( $cache_key );
		
		if ( $cached_data !== false ) {
			wp_send_json_success( $cached_data );
		}
		
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'limit'       => 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'status'      => array( 'wc-completed', 'wc-processing', 'wc-on-hold' )
		) );
		
		if ( empty( $orders ) ) {
			wp_send_json_error( array( 'message' => 'Nenhum pedido encontrado' ) );
		}
		$order = $orders[0];
		$items = $order->get_items();
		if ( empty( $items ) ) {
			wp_send_json_error( array( 'message' => 'Nenhum item no último pedido' ) );
		}
		$options_html = '<option value="">Escolha o item...</option>';
		foreach ( $items as $item_id => $item ) {
			$options_html .= '<option value="' . intval( $item_id ) . '" data-product="' . intval( $item->get_product_id() ) . '" data-variation="' . intval( $item->get_variation_id() ?: 0 ) . '">' . esc_html( $item->get_name() ) . '</option>';
		}
		
		$response_data = array(
			'options_html' => $options_html,
			'order_id'     => $order->get_id()
		);
		
		set_transient( $cache_key, $response_data, 5 * MINUTE_IN_SECONDS );
		
		wp_send_json_success( $response_data );
	}

	public function submit_review() {
		check_ajax_referer( 'udia_review_v2_nonce', 'nonce' );

		// Honeypot check
		if ( ! empty( $_POST['udia_honeypot'] ) ) {
			UDIA_Security::log_suspicious_activity( 'honeypot_triggered', array( 'value' => sanitize_text_field( $_POST['udia_honeypot'] ) ) );
			wp_send_json_error( array( 'message' => 'Spam detectado.' ), 403 );
		}

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Login requerido' ), 403 );
		}
		$user_id = get_current_user_id();

		// Rate limiting por IP
		$ip = UDIA_Security::get_client_ip();
		if ( ! UDIA_Security::check_ip_rate_limit( $ip, 'review_submit', 5, HOUR_IN_SECONDS ) ) {
			UDIA_Security::log_suspicious_activity( 'ip_rate_limit_exceeded', array( 'ip' => $ip, 'user_id' => $user_id ) );
			wp_send_json_error( array( 'message' => 'Muitas tentativas. Aguarde um pouco.' ), 429 );
		}

		// Rate Limiting: 1 review every 5 minutes
		$last_post = get_user_meta( $user_id, '_udia_last_review_time', true );
		if ( $last_post && ( time() - $last_post ) < 5 * MINUTE_IN_SECONDS ) {
			wp_send_json_error( array( 'message' => 'Você está enviando avaliações muito rápido. Aguarde alguns minutos.' ), 429 );
		}

		$user = wp_get_current_user();
		$name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : $user->display_name;
		$rating = isset( $_POST['rating'] ) ? max( 1, min( 5, intval( $_POST['rating'] ) ) ) : 5;
		$content = isset( $_POST['content'] ) ? wp_kses_post( trim( $_POST['content'] ) ) : '';
		
		// Validação de comprimento
		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => 'Depoimento vazio' ), 400 );
		}
		
		if ( ! UDIA_Security::validate_text_length( $content, 10, 2000 ) ) {
			wp_send_json_error( array( 'message' => 'Depoimento muito curto (mínimo 10 caracteres) ou muito longo (máximo 2000)' ), 400 );
		}
		
		// Detecção de spam
		if ( UDIA_Security::is_likely_spam( $content ) ) {
			UDIA_Security::log_suspicious_activity( 'spam_detected', array( 'content_preview' => mb_substr( $content, 0, 100 ), 'user_id' => $user_id ) );
			wp_send_json_error( array( 'message' => 'Conteúdo não permitido detectado' ), 403 );
		}
		
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'limit'       => 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'status'      => array( 'wc-completed', 'wc-processing', 'wc-on-hold' )
		) );
		$last_order = ! empty( $orders ) ? $orders[0] : null;
		$order_item_id = isset( $_POST['order_item_id'] ) ? intval( $_POST['order_item_id'] ) : 0;
		$manual_product = isset( $_POST['manual_product'] ) ? sanitize_text_field( $_POST['manual_product'] ) : '';
		$valid_product_id = 0;
		$valid_variation_id = 0;
		$product_label = '';
		
		if ( $order_item_id && $last_order ) {
			$found = false;
			foreach ( $last_order->get_items() as $item_id => $item ) {
				if ( intval( $item_id ) === $order_item_id ) {
					$found = true;
					$valid_product_id = $item->get_product_id();
					$valid_variation_id = $item->get_variation_id() ?: 0;
					$product_label = $item->get_name();
					break;
				}
			}
			if ( ! $found ) {
				wp_send_json_error( array( 'message' => 'Item inválido ou não pertence ao seu último pedido' ), 403 );
			}
		} else {
			if ( empty( $manual_product ) ) {
				wp_send_json_error( array( 'message' => 'Escolha um produto do seu último pedido ou escreva o nome do produto' ), 400 );
			}
			$product_label = $manual_product;
		}
		
		$clean_name = preg_replace( '/^([^—]+)(?: — .*)?$/', '$1', $name );
		$post_title = wp_strip_all_tags( $clean_name );
		
		$postarr = array(
			'post_title'   => $post_title,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'reviews',
			'post_author'  => $user_id
		);
		
		$post_id = wp_insert_post( $postarr, true );
		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => 'Erro ao salvar review' ), 500 );
		}
		
		update_post_meta( $post_id, 'rating', $rating );
		update_post_meta( $post_id, 'review_user_id', $user_id );
		if ( $valid_product_id ) {
			update_post_meta( $post_id, 'prod_id', $valid_product_id );
		}
		if ( $valid_variation_id ) {
			update_post_meta( $post_id, 'variation_id', $valid_variation_id );
		}
		if ( $order_item_id ) {
			update_post_meta( $post_id, 'order_item_id', $order_item_id );
		}
		if ( $product_label ) {
			update_post_meta( $post_id, 'product_label', sanitize_text_field( $product_label ) );
		}
		
		// Update rate limit time
		update_user_meta( $user_id, '_udia_last_review_time', time() );
		
		$avatar_data = UDIA_Helpers::generate_initials_avatar( $clean_name, $user_id );
		$avatar = '<div class="udia-review-avatar" style="background:' . $avatar_data['bg_color'] . '; color:' . $avatar_data['text_color'] . ';">' . $avatar_data['initials'] . '</div>';
		
		$prod_info = $product_label ? '<div class="udia-review-product">Produto: ' . esc_html( $product_label ) . '</div>' : '';
		$html = '<div class="udia-single-review" id="udia-review-' . intval( $post_id ) . '">';
		$html .= $avatar;
		$html .= '<div class="udia-review-author-name">' . esc_html( $clean_name ) . '</div>';
		$html .= '<div class="udia-review-rating">' . str_repeat( '★', $rating ) . '</div>';
		if ( $prod_info ) {
			$html .= $prod_info;
		}
		$html .= '<div class="udia-review-content">' . wpautop( esc_html( $content ) ) . '</div>';
		$html .= '</div>';
		
		delete_transient( 'udia_last_order_products_' . $user_id );
		
		wp_send_json_success( array( 'html' => $html, 'post_id' => $post_id ) );
	}
}
