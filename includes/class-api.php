<?php
namespace SBWP;

defined( 'ABSPATH' ) || exit;

class Api {

	private const BASE = 'https://api.sendbyte.africa';

	public static function health_check( string $api_key ): array {
		$cache_key = 'sbwp_health_' . md5( $api_key );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$result = array(
			'valid'    => false,
			'mode'     => 'unknown',
			'domains'  => array(),
			'plan'     => '',
			'quota'    => 0,
			'used'     => 0,
			'error'    => '',
		);

		$email_resp = self::get( '/v1/emails?limit=1', $api_key );
		if ( is_wp_error( $email_resp ) ) {
			$result['error'] = $email_resp->get_error_message();
			set_transient( $cache_key, $result, 60 );
			return $result;
		}

		$code = wp_remote_retrieve_response_code( $email_resp );
		if ( 200 !== $code ) {
			$body = json_decode( wp_remote_retrieve_body( $email_resp ), true );
			$result['error'] = $body['message'] ?? sprintf(
				/* translators: %d: HTTP response code */
				__( 'API returned HTTP %d', 'smtp-for-sendbyte' ),
				$code
			);
			set_transient( $cache_key, $result, 60 );
			return $result;
		}

		$data              = json_decode( wp_remote_retrieve_body( $email_resp ), true );
		$result['valid']   = true;
		$result['mode']    = ! empty( $data['data'][0]['sandbox'] ) ? 'sandbox' : 'live';

		$billing_resp = self::get( '/v1/billing', $api_key );
		if ( ! is_wp_error( $billing_resp ) && 200 === wp_remote_retrieve_response_code( $billing_resp ) ) {
			$billing           = json_decode( wp_remote_retrieve_body( $billing_resp ), true );
			$result['plan']    = $billing['plan']['name'] ?? '';
			$result['quota']   = $billing['plan']['quota'] ?? 0;
			$result['used']    = $billing['period']['used'] ?? 0;
			$result['reset']   = $billing['period']['resets_at'] ?? '';
		}

		$domains_resp = self::get( '/v1/domains', $api_key );
		if ( ! is_wp_error( $domains_resp ) && 200 === wp_remote_retrieve_response_code( $domains_resp ) ) {
			$body = json_decode( wp_remote_retrieve_body( $domains_resp ), true );
			$result['domains'] = $body['data'] ?? ( isset( $body['domain'] ) ? array( $body ) : array() );
		}

		set_transient( $cache_key, $result, 300 );
		return $result;
	}

	public static function get_email_stats( string $api_key, int $limit = 100 ): array {
		$cache_key = 'sbwp_stats_' . md5( $api_key . $limit );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$default = array(
			'total'     => 0,
			'delivered' => 0,
			'bounced'   => 0,
			'failed'    => 0,
			'pending'   => 0,
			'recent'    => array(),
			'hourly'    => array(),
			'error'     => '',
		);

		$resp = self::get( '/v1/emails?limit=' . intval( $limit ), $api_key );
		if ( is_wp_error( $resp ) ) {
			$default['error'] = $resp->get_error_message();
			return $default;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $resp ) ) {
			return $default;
		}

		$data       = json_decode( wp_remote_retrieve_body( $resp ), true );
		$emails     = $data['data'] ?? array();
		$stats      = array( 'delivered' => 0, 'bounced' => 0, 'failed' => 0, 'sent' => 0, 'queued' => 0 );
		$hourly     = array();

		foreach ( $emails as $email ) {
			$status = $email['status'] ?? 'unknown';
			if ( isset( $stats[ $status ] ) ) {
				$stats[ $status ]++;
			}

			$created = $email['created_at'] ?? '';
			if ( $created ) {
				$hour_key = substr( $created, 0, 13 );
				if ( ! isset( $hourly[ $hour_key ] ) ) {
					$hourly[ $hour_key ] = array( 'ok' => 0, 'fail' => 0 );
				}
				if ( 'delivered' === $status ) {
					$hourly[ $hour_key ]['ok']++;
				} elseif ( in_array( $status, array( 'bounced', 'failed', 'complained' ), true ) ) {
					$hourly[ $hour_key ]['fail']++;
				}
			}
		}

		ksort( $hourly );

		$result = array(
			'total'     => count( $emails ),
			'delivered' => $stats['delivered'],
			'bounced'   => $stats['bounced'],
			'failed'    => $stats['failed'],
			'pending'   => $stats['sent'] + $stats['queued'],
			'recent'    => array_slice( $emails, 0, 10 ),
			'hourly'    => $hourly,
		);

		set_transient( $cache_key, $result, 120 );
		return $result;
	}

	public static function clear_cache( string $api_key = '' ): void {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_sbwp_health_' ) . '%',
				$wpdb->esc_like( '_transient_sbwp_stats_' ) . '%'
			)
		);
	}

	private static function get( string $endpoint, string $api_key ) {
		return wp_remote_get(
			self::BASE . $endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Accept'        => 'application/json',
				),
				'timeout' => 15,
			)
		);
	}
}
