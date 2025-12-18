<?php
/**
 * Helper Functions Class
 *
 * Provides utility methods for caching, avatar generation, review queries,
 * and database optimization.
 *
 * @package UDIA_Reviews_V2
 * @since 4.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UDIA Helpers Class
 *
 * Collection of static utility methods for review management and caching.
 *
 * @since 4.0.0
 */
class UDIA_Helpers {

	/**
	 * Get cached fragment
	 *
	 * Implements fragment caching for shortcode output to improve performance.
	 *
	 * @since 4.0.0
	 * @param string   $key      Cache key.
	 * @param callable $callback Function that generates the content.
	 * @param int      $ttl      Time to live in seconds.
	 * @return string Cached or generated content.
	 */
	public static function get_cached_fragment( $key, $callback, $ttl = null ) {
		if ( null === $ttl ) {
			$ttl = UDIA_Constants::CACHE_TTL_FRAGMENT;
		}
		
		$output = wp_cache_get( $key, UDIA_Constants::CACHE_GROUP_FRAGMENTS );
		
		if ( $output !== false ) {
			return $output;
		}
		
		$output = call_user_func( $callback );
		wp_cache_set( $key, $output, UDIA_Constants::CACHE_GROUP_FRAGMENTS, $ttl );
		
		return $output;
	}

	/**
	 * Create database indexes
	 *
	 * Creates indexes on postmeta table for optimal query performance.
	 * Should be called on plugin activation hook.
	 *
	 * @since 4.0.0
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @return void
	 */
	public static function create_meta_indexes() {
		global $wpdb;
		
		// Verificar se índices já existem
		$index_exists = $wpdb->get_var(
			"SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'idx_udia_prod_id'"
		);
		
		if ( ! $index_exists ) {
			// Criar índice para prod_id (melhora queries de reviews por produto)
			$wpdb->query(
				"ALTER TABLE {$wpdb->postmeta} 
				 ADD INDEX idx_udia_prod_id (meta_key(20), meta_value(20))"
			);
		}
		
		$index_exists_rating = $wpdb->get_var(
			"SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'idx_udia_rating'"
		);
		
		if ( ! $index_exists_rating ) {
			// Criar índice para rating (melhora agregações)
			$wpdb->query(
				"ALTER TABLE {$wpdb->postmeta} 
				 ADD INDEX idx_udia_rating (meta_key(20), meta_value(5))"
			);
		}
	}

	/**
	 * Generate avatar with initials
	 *
	 * Creates a colored avatar div with user initials based on name hashing.
	 *
	 * @since 4.0.0
	 * @param string   $name    User's full name.
	 * @param int|null $user_id Optional user ID for consistent colors.
	 * @return array {
	 *     Avatar data.
	 *
	 *     @type string $initials   User initials (2 characters).
	 *     @type string $bg_color    HSL background color.
	 *     @type string $text_color  Text color (white).
	 * }
	 */
	public static function generate_initials_avatar( $name, $user_id = null ) {
		$initials = '';
		$name_parts = explode( ' ', trim( $name ) );
		
		if ( ! empty( $name_parts[0] ) ) {
			$initials .= mb_substr( $name_parts[0], 0, 1 );
		}
		
		if ( count( $name_parts ) > 1 && ! empty( $name_parts[ count( $name_parts ) - 1 ] ) ) {
			$initials .= mb_substr( $name_parts[ count( $name_parts ) - 1 ], 0, 1 );
		}
		
		if ( strlen( $initials ) < 2 && ! empty( $name_parts[0] ) ) {
			$initials = mb_substr( $name_parts[0], 0, 2 );
		}
		
		$initials = mb_strtoupper( $initials );
		
		$hash = md5( $name . ( $user_id ?: '' ) );
		$hue = hexdec( substr( $hash, 0, 2 ) ) / 255 * 360;
		$saturation = 65 + ( hexdec( substr( $hash, 2, 2 ) ) % 25 );
		$lightness = 45 + ( hexdec( substr( $hash, 4, 2 ) ) % 15 );
		
		return array(
			'initials'   => $initials,
			'bg_color'   => "hsl({$hue}, {$saturation}%, {$lightness}%)",
			'text_color' => '#ffffff'
		);
	}

	/**
	 * Get product reviews query
	 *
	 * Retrieves reviews for a specific product or product variation.
	 *
	 * @since 4.0.0
	 * @param int $product_id      Product ID.
	 * @param int $variation_id    Optional variation ID.
	 * @param int $posts_per_page  Number of reviews to retrieve (-1 for all).
	 * @return WP_Query Query object containing reviews.
	 */
	public static function get_product_reviews( $product_id, $variation_id = 0, $posts_per_page = -1 ) {
		$meta_queries = array( 'relation' => 'OR' );
		if ( $product_id > 0 ) {
			$meta_queries[] = array(
				'key'     => 'prod_id',
				'value'   => $product_id,
				'compare' => '=',
				'type'    => 'NUMERIC'
			);
		}
		if ( $variation_id > 0 ) {
			$meta_queries[] = array(
				'key'     => 'variation_id',
				'value'   => $variation_id,
				'compare' => '=',
				'type'    => 'NUMERIC'
			);
		}

		$args = array(
			'post_type'      => UDIA_Constants::CPT_SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => $posts_per_page,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => $meta_queries,
		);

		return new WP_Query( $args );
	}

	/**
	 * Get product review stats
	 *
	 * Calculates average rating and total review count for a product.
	 * Results are cached for performance.
	 *
	 * @since 4.0.0
	 * @param int $product_id   Product ID.
	 * @param int $variation_id Optional variation ID.
	 * @return array {
	 *     Review statistics.
	 *
	 *     @type float $average_rating Average rating (0-5).
	 *     @type int   $total_reviews  Total review count.
	 * }
	 */
	public static function get_product_review_stats( $product_id, $variation_id = 0 ) {
		$cache_key = 'udia_product_stats_' . $product_id . '_' . $variation_id;
		$cached_stats = wp_cache_get( $cache_key, 'udia_reviews' );

		if ( $cached_stats !== false ) {
			return $cached_stats;
		}

		// Optimized WP_Query approach to minimize memory usage
		$args = array(
			'post_type'      => 'reviews',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				'relation' => 'OR',
			),
		);
		
		if ( $product_id > 0 ) {
			$args['meta_query'][] = array(
				'key'     => 'prod_id',
				'value'   => $product_id,
				'compare' => '=',
				'type'    => 'NUMERIC'
			);
		}
		if ( $variation_id > 0 ) {
			$args['meta_query'][] = array(
				'key'     => 'variation_id',
				'value'   => $variation_id,
				'compare' => '=',
				'type'    => 'NUMERIC'
			);
		}
		
		$query = new WP_Query( $args );
		$total_reviews = $query->post_count;
		$total_rating = 0;

		if ( $total_reviews > 0 ) {
			foreach ( $query->posts as $review_id ) {
				$rating = intval( get_post_meta( $review_id, 'rating', true ) );
				$total_rating += max( 1, min( 5, $rating ) );
			}
			$average_rating = round( $total_rating / $total_reviews, 1 );
		} else {
			$average_rating = 0;
		}

		$stats = array(
			'average_rating' => $average_rating,
			'total_reviews'  => $total_reviews
		);

		wp_cache_set( $cache_key, $stats, 'udia_reviews', 10 * MINUTE_IN_SECONDS );

		return $stats;
	}

	/**
	 * Get global review stats
	 *
	 * Calculates overall average rating and total review count across all products.
	 * Results are cached for performance.
	 *
	 * @since 4.0.0
	 * @return array {
	 *     Global review statistics.
	 *
	 *     @type float $average_rating Average rating (1-5, defaults to 5.0 if no reviews).
	 *     @type int   $total_reviews  Total review count.
	 * }
	 */
	public static function get_global_review_stats() {
		$cache_key = 'udia_global_review_stats';
		$cached_stats = wp_cache_get( $cache_key, 'udia_reviews' );
		if ( $cached_stats !== false ) {
			return $cached_stats;
		}

		$reviews_query = new WP_Query( array(
			'post_type'      => UDIA_Constants::CPT_SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );

		$total_reviews = $reviews_query->post_count;
		if ( $total_reviews === 0 ) {
			$stats = array(
				'average_rating' => 5.0,
				'total_reviews'  => 0
			);
			wp_cache_set( $cache_key, $stats, 'udia_reviews', 10 * MINUTE_IN_SECONDS );
			return $stats;
		}

		$total_rating = 0;
		$review_ids = $reviews_query->posts;

		foreach ( $review_ids as $review_id ) {
			$rating = get_post_meta( $review_id, 'rating', true );
			$rating = is_numeric( $rating ) ? (int) $rating : 5;
			$rating = max( 1, min( 5, $rating ) );
			$total_rating += $rating;
		}

		$average_rating = $total_reviews > 0 ? round( $total_rating / $total_reviews, 1 ) : 5.0;

		$stats = array(
			'average_rating' => $average_rating,
			'total_reviews'  => $total_reviews
		);

		wp_cache_set( $cache_key, $stats, 'udia_reviews', 10 * MINUTE_IN_SECONDS );
		return $stats;
	}

	/**
	 * Clear caches on new review
	 *
	 * Clears product-specific caches when a new review is published.
	 *
	 * @since 4.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 * @return void
	 */
	public static function clear_caches_on_new_review( $post_id, $post, $update ) {
		if ( $post->post_type !== UDIA_Constants::CPT_SLUG || $update ) {
			return;
		}

		$prod_id = get_post_meta( $post_id, 'prod_id', true );
		$variation_id = get_post_meta( $post_id, 'variation_id', true );

		if ( $prod_id ) {
			wp_cache_delete( 'udia_product_stats_' . $prod_id . '_' . $variation_id, UDIA_Constants::CACHE_GROUP_REVIEWS );
			wp_cache_delete( 'udia_product_stats_' . $prod_id . '_0', UDIA_Constants::CACHE_GROUP_REVIEWS );
		}
	}

	/**
	 * Clear global cache on review change
	 *
	 * Clears global review statistics cache when reviews are published or unpublished.
	 *
	 * @since 4.0.0
	 * @param int     $post_id     Post ID.
	 * @param WP_Post $post_after  Post object after update.
	 * @param WP_Post $post_before Post object before update (optional).
	 * @return void
	 */
	public static function clear_global_cache_on_review_change( $post_id, $post_after, $post_before = null ) {
		if ( $post_after->post_type !== UDIA_Constants::CPT_SLUG ) {
			return;
		}
		if ( in_array( $post_after->post_status, ['publish'], true ) || 
			( $post_before && $post_before->post_status === 'publish' && $post_after->post_status !== 'publish' ) ) {
			wp_cache_delete( 'udia_global_review_stats', UDIA_Constants::CACHE_GROUP_REVIEWS );
			
			// Clear fragment cache as well.
			wp_cache_delete( 'udia_fragments', UDIA_Constants::CACHE_GROUP_REVIEWS );
		}
	}
}
