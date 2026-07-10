<?php
namespace SBWP;

defined( 'ABSPATH' ) || exit;

class Smtp {

	private array $settings;
	private static ?int $last_log_id = null;

	public function __construct() {
		$this->settings = get_option( 'sendbyte_wp_settings', array() );

		add_action( 'phpmailer_init',  array( $this, 'configure' ) );
		add_filter( 'wp_mail_from',    array( $this, 'set_from_email' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'set_from_name' ) );

		if ( $this->logging_enabled() ) {
			add_filter( 'wp_mail',       array( $this, 'capture_mail' ), -PHP_INT_MAX );
			add_action( 'wp_mail_failed', array( $this, 'log_failure' ) );
			add_action( 'shutdown',       array( $this, 'flush_pending_log' ) );
		}
	}

	public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
		if ( empty( $this->settings['api_key'] ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = 'smtp.sendbyte.africa';
		$phpmailer->Port       = 587;
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Username   = 'apikey';
		$phpmailer->Password   = $this->settings['api_key'];
		$phpmailer->SMTPSecure = 'tls';
		$phpmailer->SMTPAutoTLS = true;
		$phpmailer->SMTPOptions = array(
			'ssl' => array(
				'verify_peer'       => true,
				'verify_peer_name'  => true,
				'allow_self_signed' => false,
			),
		);
		$phpmailer->XMailer    = 'SMTP for SendByte ' . SBWP_VERSION;
		$phpmailer->Timeout    = 30;
	}

	public function capture_mail( array $atts ): array {
		$to = is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : $atts['to'];

		self::$last_log_id = Logger::add(
			array(
				'to_email' => $to,
				'subject'  => $atts['subject'] ?? '',
				'status'   => 'sent',
			)
		);

		return $atts;
	}

	public function log_failure( \WP_Error $error ): void {
		if ( ! self::$last_log_id ) {
			return;
		}

		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'sendbyte_wp_log',
			array(
				'status'   => 'failed',
				'response' => $error->get_error_message(),
			),
			array( 'id' => self::$last_log_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		self::reset();
	}

	public function flush_pending_log(): void {
		if ( ! self::$last_log_id ) {
			return;
		}

		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'sendbyte_wp_log',
			array( 'status' => 'delivered' ),
			array( 'id' => self::$last_log_id ),
			array( '%s' ),
			array( '%d' )
		);

		self::reset();
	}

	public function set_from_email( string $email ): string {
		if ( ! empty( $this->settings['from_email'] ) ) {
			return $this->settings['from_email'];
		}
		return $email;
	}

	public function set_from_name( string $name ): string {
		if ( ! empty( $this->settings['from_name'] ) ) {
			return $this->settings['from_name'];
		}
		return $name;
	}

	private function logging_enabled(): bool {
		return ! empty( $this->settings['log_enabled'] );
	}

	private static function reset(): void {
		self::$last_log_id = null;
	}
}
