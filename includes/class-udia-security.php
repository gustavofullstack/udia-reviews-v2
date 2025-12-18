<?php
/**
 * Security Functions Class
 *
 * Provides centralized security features including rate limiting,
 * spam detection, IP tracking, and activity logging.
 *
 * @package UDIA_Reviews_V2
 * @since 4.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UDIA Security Class
 *
 * Collection of static security methods for protecting against spam and abuse.
 *
 * @since 4.0.0
 */
class UDIA_Security {

	/**
	 * Check IP rate limiting
	 *
	 * Verifies if an IP address has exceeded the allowed number of attempts
	 * for a specific action within a time period.
	 *
	 * @since 4.0.0
	 * @param string $ip     IP address to check.
	 * @param string $action Action being rate limited (e.g., 'review_submit').
	 * @param int    $max    Maximum allowed attempts.
	 * @param int    $period Time period in seconds.
	 * @return bool True if within rate limit, false if exceeded.
	 */
	public static function check_ip_rate_limit( $ip, $action = 'review_submit', $max = null, $period = null ) {
		if ( null === $max ) {
			$max = UDIA_Constants::RATE_LIMIT_REVIEWS_MAX;
		}
		if ( null === $period ) {
			$period = UDIA_Constants::RATE_LIMIT_PERIOD;
		}
		$key = 'udia_rate_' . $action . '_' . md5( $ip );
		$attempts = get_transient( $key ) ?: 0;

		if ( $attempts >= $max ) {
			return false;
		}

		set_transient( $key, $attempts + 1, $period );
		return true;
	}

	/**
	 * Loga atividade suspeita
	 *
	 * @param string $type Tipo de atividade
	 * @param array  $data Dados adicionais
	 * 
	 * @return void
	 */
	public static function log_suspicious_activity( $type, $data = array() ) {
		// Só logar se debug mode estiver ativo ou em produção com filtro
		$should_log = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$should_log = apply_filters( 'udia_log_suspicious_activity', $should_log, $type );

		if ( ! $should_log ) {
			return;
		}

		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'type'      => $type,
			'ip'        => self::get_client_ip(),
			'user_id'   => get_current_user_id(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
			'data'      => $data,
		);

		$logs = get_option( 'udia_security_log', array() );
		$logs[] = $log_entry;

		// Keep only last 100 logs.
		$logs = array_slice( $logs, - UDIA_Constants::SECURITY_LOG_MAX );
		update_option( 'udia_security_log', $logs, false );

		// Also log to PHP error log.
		error_log( sprintf(
			'[UDIA Security] %s | IP: %s | User: %d | Data: %s',
			$type,
			$log_entry['ip'],
			$log_entry['user_id'],
			wp_json_encode( $data )
		) );
	}

	/**
	 * Obtém o IP real do cliente
	 *
	 * @return string IP do cliente
	 */
	public static function get_client_ip() {
		$ip = '';

		// Verificar proxies e CDNs
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			// Cloudflare
			$ip = sanitize_text_field( $_SERVER['HTTP_CF_CONNECTING_IP'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
			// Pegar o primeiro IP da lista
			$ip = explode( ',', $ip )[0];
		} elseif ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_X_REAL_IP'] );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
		}

		return trim( $ip );
	}

	/**
	 * Validate text length
	 *
	 * Checks if text falls within minimum and maximum character limits.
	 *
	 * @since 4.0.0
	 * @param string $text Text to validate.
	 * @param int    $min  Minimum length (default from constants).
	 * @param int    $max  Maximum length (default from constants).
	 * @return bool True if valid length, false otherwise.
	 */
	public static function validate_text_length( $text, $min = null, $max = null ) {
		if ( null === $min ) {
			$min = UDIA_Constants::TEXT_LENGTH_MIN;
		}
		if ( null === $max ) {
			$max = UDIA_Constants::TEXT_LENGTH_MAX;
		}
		$length = mb_strlen( $text );
		return $length >= $min && $length <= $max;
	}

	/**
	 * Adiciona headers de Content Security Policy
	 *
	 * @return void
	 */
	public static function add_csp_headers() {
		if ( headers_sent() ) {
			return;
		}

		// CSP básico para prevenir XSS
		header( "Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;" );
		
		// Outros headers de segurança
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-XSS-Protection: 1; mode=block' );
	}

	/**
	 * Verifica se uma string parece ser spam
	 *
	 * @param string $text Texto a verificar
	 * 
	 * @return bool True se parece spam, false caso contrário
	 */
	public static function is_likely_spam( $text ) {
		// Lista de keywords comuns em spam
		$spam_keywords = array(
			'http://', 'https://', 'www.',
			'casino', 'viagra', 'cialis',
			'buy now', 'click here', 'limited time',
			'make money', 'work from home',
		);

		$text_lower = mb_strtolower( $text );

		foreach ( $spam_keywords as $keyword ) {
			if ( strpos( $text_lower, $keyword ) !== false ) {
				return true;
			}
		}

		// Verificar excesso de links
		$link_count = preg_match_all( '/(https?:\/\/|www\.)/i', $text );
		if ( $link_count > 2 ) {
			return true;
		}

		// Verificar caracteres repetidos excessivos
		if ( preg_match( '/(.)\1{10,}/', $text ) ) {
			return true;
		}

		return false;
	}
}
