<?php
/**
 * Classe para exibição automática de reviews nas páginas de loja
 *
 * @package UDIA_Reviews_V2
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UDIA_Shop_Display {

	/**
	 * Construtor
	 */
	public function __construct() {
		// Hook para reviews: DEPOIS da imagem, ANTES do título
		// Editável via Elementor/Builder (fluxo normal do layout)
		add_action( 'woocommerce_shop_loop_item_title', array( $this, 'display_review_summary' ), 5 );
		
		// Adicionar estilos específicos para shop display
		add_action( 'wp_head', array( $this, 'add_shop_styles' ) );
	}

	/**
	 * Exibe o resumo de reviews no loop de produtos
	 * Localização: Entre título/imagem e preço
	 *
	 * @return void
	 */
	public function display_review_summary() {
		global $product;

		if ( ! $product ) {
			return;
		}

		// Obter ID do produto
		$product_id = $product->get_id();
		$variation_id = 0;

		// Se for variação, ajustar IDs
		if ( $product->is_type( 'variation' ) ) {
			$variation_id = $product_id;
			$product_id = $product->get_parent_id();
		}

		// Obter estatísticas de reviews
		$stats = UDIA_Helpers::get_product_review_stats( $product_id, $variation_id );

		// Se não houver reviews, mostrar 5 estrelas vazias com "Seja o primeiro"
		if ( $stats['total_reviews'] === 0 ) {
			$this->render_no_reviews();
			return;
		}

		// Renderizar resumo de reviews
		$this->render_review_summary( $stats['average_rating'], $stats['total_reviews'] );
	}

	/**
	 * Renderiza o resumo quando há reviews
	 *
	 * @param float $average_rating Nota média
	 * @param int   $total_reviews  Total de reviews
	 * 
	 * @return void
	 */
	private function render_review_summary( $average_rating, $total_reviews ) {
		$full_stars = floor( $average_rating );
		$half_star = ( $average_rating - $full_stars ) >= 0.5 ? 1 : 0;
		$empty_stars = 5 - $full_stars - $half_star;

		echo '<div class="udia-shop-review-summary">';
		echo '<div class="udia-shop-stars">';

		// Estrelas cheias
		for ( $i = 0; $i < $full_stars; $i++ ) {
			echo '<span class="udia-shop-star filled">★</span>';
		}

		// Meia estrela (renderizada como cheia para simplicidade)
		if ( $half_star ) {
			echo '<span class="udia-shop-star filled">★</span>';
		}

		// Estrelas vazias
		for ( $i = 0; $i < $empty_stars; $i++ ) {
			echo '<span class="udia-shop-star empty">★</span>';
		}

		echo '</div>';

		// Contador de reviews
		echo '<span class="udia-shop-count">';
		echo '(' . esc_html( $total_reviews ) . ')';
		echo '</span>';

		echo '</div>';
	}

	/**
	 * Renderiza quando não há reviews
	 *
	 * @return void
	 */
	private function render_no_reviews() {
		echo '<div class="udia-shop-review-summary udia-no-reviews">';
		echo '<div class="udia-shop-stars">';

		// 5 estrelas vazias
		for ( $i = 0; $i < 5; $i++ ) {
			echo '<span class="udia-shop-star empty">★</span>';
		}

		echo '</div>';
		echo '<span class="udia-shop-count">(0)</span>';
		echo '</div>';
	}

	/**
	 * Adiciona estilos CSS inline específicos para exibição na loja
	 *
	 * @return void
	 */
	public function add_shop_styles() {
		// Só adicionar se estiver em páginas de loja
		if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) ) {
			return;
		}

		?>
		<style>
		/* Reviews na loja - ABAIXO DA IMAGEM, ACIMA DO TÍTULO */
		/* Fluxo normal do layout (editável via Elementor) */
		.udia-shop-review-summary {
			display: flex;
			align-items: center;
			/* SEM justify-content - herda alinhamento do título */
			gap: 0.4rem;
			margin: 0.5rem 0;
			padding: 0.4rem 0;
			font-size: 0.9rem;
			line-height: 1.2;
		}

		.udia-shop-stars {
			display: flex;
			align-items: center;
			gap: 0.15rem;
		}

		.udia-shop-star {
			color: #ddd;
			font-size: 1rem;
			line-height: 1;
			transition: color 0.2s ease;
		}

		.udia-shop-star.filled {
			color: var(--udia-accent, #39ff14);
			text-shadow: 0 0 10px rgba(57, 255, 20, 0.6);
			filter: drop-shadow(0 0 4px rgba(57, 255, 20, 0.4));
		}

		.udia-shop-star.empty {
			color: rgba(255, 255, 255, 0.15);
		}

		/* CONTADOR VERDE NEON - DESTAQUE MÁXIMO */
		.udia-shop-count {
			color: var(--udia-accent, #39ff14);
			font-size: 0.9rem;
			font-weight: 700;
			text-shadow: 0 0 10px rgba(57, 255, 20, 0.6);
			letter-spacing: 0.5px;
		}

		/* Estado hover - brilho aumentado */
		.udia-shop-review-summary:hover .udia-shop-star.filled {
			color: #4dff29;
			text-shadow: 0 0 14px rgba(77, 255, 41, 0.8);
		}

		.udia-shop-review-summary:hover .udia-shop-count {
			color: #4dff29;
			text-shadow: 0 0 14px rgba(77, 255, 41, 0.8);
		}

		/* Sem reviews - estilo discreto */
		.udia-shop-review-summary.udia-no-reviews {
			opacity: 0.5;
		}

		.udia-shop-review-summary.udia-no-reviews:hover {
			opacity: 0.7;
		}

		.udia-shop-review-summary.udia-no-reviews .udia-shop-count {
			color: rgba(255, 255, 255, 0.5);
			text-shadow: none;
		}

		/* Responsividade */
		@media (max-width: 768px) {
			.udia-shop-review-summary {
				font-size: 0.85rem;
				margin: 0.4rem 0;
			}
			
			.udia-shop-star {
				font-size: 0.95rem;
			}
			
			.udia-shop-count {
				font-size: 0.85rem;
			}
		}

		@media (max-width: 480px) {
			.udia-shop-review-summary {
				font-size: 0.8rem;
				margin: 0.3rem 0;
				gap: 0.3rem;
			}
			
			.udia-shop-star {
				font-size: 0.9rem;
			}
			
			.udia-shop-count {
				font-size: 0.8rem;
			}
		}

		/* Compatibilidade com temas claros */
		@media (prefers-color-scheme: light) {
			.udia-shop-star.empty {
				color: rgba(0, 0, 0, 0.15);
			}
		}
		</style>
		<?php
	}
}
