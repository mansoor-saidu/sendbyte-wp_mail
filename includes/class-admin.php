<?php
namespace SBWP;

defined( 'ABSPATH' ) || exit;

class Admin {

	private const OPTION_KEY  = 'sendbyte_wp_settings';
	private const PAGE_SLUG   = 'smtp-for-sendbyte';
	private const ONBOARD_KEY = 'sbwp_onboard_dismissed';

	public function __construct() {
		add_action( 'admin_menu',                     array( $this, 'add_menu' ) );
		add_action( 'admin_init',                     array( $this, 'register_settings' ) );
		add_action( 'admin_post_sendbyte_test_email',  array( $this, 'handle_test_email' ) );
		add_action( 'admin_post_sbwp_dismiss_onboard', array( $this, 'dismiss_onboard' ) );
		add_action( 'admin_post_sbwp_refresh_health',  array( $this, 'refresh_health' ) );
		add_action( 'admin_enqueue_scripts',          array( $this, 'enqueue_assets' ) );
	}

	public function add_menu(): void {
		add_options_page(
			__( 'SMTP for SendByte', 'smtp-for-sendbyte' ),
			__( 'SendByte', 'smtp-for-sendbyte' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		delete_transient( 'sbwp_activation_notice' );

		wp_enqueue_style(
			'sbwp-font',
			SBWP_URL . 'assets/fonts.css',
			array(),
			SBWP_VERSION
		);

		$css = '
		.sbwp-wrap { max-width: 880px; font-family:"Hanken Grotesk",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
		.sbwp-wrap h1 { display:flex; align-items:center; gap:10px; margin-bottom:24px; }
		.sbwp-card { background:#fff; border:1px solid #e2e4e7; border-radius:8px; padding:24px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
		.sbwp-card h2 { margin-top:0; margin-bottom:20px; padding-bottom:12px; border-bottom:1px solid #f0f0f1; font-size:16px; font-weight:600; display:flex; align-items:center; gap:8px; }
		.sbwp-card h2 .dashicons { width:20px; height:20px; font-size:20px; }
		.sbwp-card h2 .sbwp-head-right { margin-left:auto; font-size:12px; font-weight:400; color:#787c82; display:flex; align-items:center; gap:8px; }
		.sbwp-card h2 .sbwp-head-right a { text-decoration:none; }
		.sbwp-card .form-table { margin:0; }
		.sbwp-card .form-table th { width:180px; padding:15px 10px 15px 0; }
		.sbwp-card .form-table td { padding:15px 0; }
		.sbwp-card .form-table input.regular-text { width:100%; max-width:400px; }
		.sbwp-card .form-table p.description { margin:8px 0 0; font-size:12px; color:#787c82; }
		.sbwp-card .sbwp-links { margin:4px 0 0; font-size:12px; }
		.sbwp-card .sbwp-links a { text-decoration:none; }
		.sbwp-card .sbwp-links a:hover { text-decoration:underline; }
		.sbwp-status-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:500; }
		.sbwp-status-badge.delivered { background:#e6f9e8; color:#1a7d2a; }
		.sbwp-status-badge.failed, .sbwp-status-badge.bounced { background:#fce8e8; color:#b32d2e; }
		.sbwp-status-badge.sent, .sbwp-status-badge.queued { background:#e8f0fe; color:#1967d2; }
		.sbwp-test-result { margin:16px 0 0; padding:12px 16px; border-radius:6px; font-size:13px; }
		.sbwp-test-result.success { background:#e6f9e8; border:1px solid #b7e4c0; color:#1a7d2a; }
		.sbwp-test-result.fail { background:#fce8e8; border:1px solid #f5bdbd; color:#b32d2e; }
		.sbwp-test-row { display:flex; gap:8px; align-items:center; }
		.sbwp-test-row input[type="email"] { flex:1; max-width:320px; }
		.sbwp-log-table { width:100%; border-collapse:collapse; margin-top:4px; }
		.sbwp-log-table th { text-align:left; padding:10px 12px; border-bottom:2px solid #f0f0f1; font-size:12px; font-weight:600; color:#50575e; text-transform:uppercase; letter-spacing:.5px; }
		.sbwp-log-table td { padding:10px 12px; border-bottom:1px solid #f0f0f1; font-size:13px; }
		.sbwp-log-table tr:hover td { background:#fafafa; }
		.sbwp-log-table .sbwp-to { max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
		.sbwp-log-table .sbwp-subj { max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#50575e; }
		.sbwp-log-table .sbwp-date { color:#787c82; font-size:12px; white-space:nowrap; }
		.sbwp-empty-state { text-align:center; padding:32px 0; color:#787c82; font-size:13px; }
		.sbwp-refresh { text-decoration:none; font-size:13px; display:inline-flex; align-items:center; gap:4px; }
		.sbwp-mode-switch { display:flex; align-items:center; gap:12px; }
		.sbwp-mode-switch .sbwp-toggle { position:relative; width:48px; height:26px; flex-shrink:0; }
		.sbwp-mode-switch .sbwp-toggle input { position:absolute; opacity:0; width:100%; height:100%; margin:0; cursor:pointer; z-index:2; }
		.sbwp-mode-switch .sbwp-toggle .sbwp-slider { position:absolute; inset:0; background:#e5e7eb; border-radius:13px; transition:background .2s; }
		.sbwp-mode-switch .sbwp-toggle .sbwp-slider::after { content:""; position:absolute; top:3px; left:3px; width:20px; height:20px; background:#fff; border-radius:50%; transition:transform .2s; box-shadow:0 1px 3px rgba(0,0,0,.15); }
		.sbwp-mode-switch .sbwp-toggle input:checked + .sbwp-slider { background:#2563eb; }
		.sbwp-mode-switch .sbwp-toggle input:checked + .sbwp-slider::after { transform:translateX(22px); }
		.sbwp-mode-switch .sbwp-mode-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 12px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; }
		.sbwp-mode-switch .sbwp-mode-badge.sandbox { background:#fef3c7; color:#b45309; }
		.sbwp-mode-switch .sbwp-mode-badge.live { background:#e6f9e8; color:#1a7d2a; }
		.sbwp-mode-switch .sbwp-mode-badge .dashicons { width:14px; height:14px; font-size:14px; }
		.sbwp-toggle-label { display:flex; align-items:center; gap:8px; cursor:pointer; }
		.sbwp-toggle-label input[type="checkbox"] { margin:0; }
		.sbwp-onboard { background:#f8faff; border:1px solid #dbe7fe; border-radius:8px; padding:20px 24px; margin-bottom:24px; position:relative; }
		.sbwp-onboard h3 { margin:0 0 4px; font-size:15px; font-weight:600; color:#1e40af; }
		.sbwp-onboard p { margin:0 0 16px; font-size:13px; color:#4b5563; }
		.sbwp-onboard .sbwp-steps { display:flex; gap:0; list-style:none; margin:0; padding:0; }
		.sbwp-onboard .sbwp-steps li { flex:1; display:flex; align-items:center; gap:8px; padding:10px 12px; font-size:12px; color:#6b7280; border-right:1px solid #e5e7eb; }
		.sbwp-onboard .sbwp-steps li:last-child { border-right:0; }
		.sbwp-onboard .sbwp-steps .sbwp-step-num { display:flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; font-size:11px; font-weight:700; flex-shrink:0; background:#e5e7eb; color:#6b7280; }
		.sbwp-onboard .sbwp-steps .sbwp-step-num.done { background:#2563eb; color:#fff; }
		.sbwp-onboard .sbwp-steps .sbwp-step-label { font-weight:500; }
		.sbwp-onboard .sbwp-dismiss { position:absolute; top:16px; right:16px; text-decoration:none; color:#9ca3af; font-size:16px; line-height:1; }
		.sbwp-onboard .sbwp-dismiss:hover { color:#4b5563; }

		.sbwp-dash-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:8px; }
		.sbwp-dash-stat { text-align:center; padding:16px; border-radius:6px; background:#f9fafb; }
		.sbwp-dash-stat .sbwp-stat-num { font-size:28px; font-weight:700; line-height:1.2; }
		.sbwp-dash-stat .sbwp-stat-label { font-size:11px; color:#787c82; text-transform:uppercase; letter-spacing:.5px; margin-top:2px; }
		.sbwp-dash-stat.delivered .sbwp-stat-num { color:#1a7d2a; }
		.sbwp-dash-stat.failed .sbwp-stat-num { color:#b32d2e; }
		.sbwp-dash-stat.pending .sbwp-stat-num { color:#1967d2; }
		.sbwp-dash-stat.bounced .sbwp-stat-num { color:#b45309; }
		.sbwp-dash-bar { height:6px; border-radius:3px; background:#f0f0f1; margin:12px 0; overflow:hidden; display:flex; }
		.sbwp-dash-bar span { height:100%; transition:width .4s; }
		.sbwp-dash-bar .bar-delivered { background:#1a7d2a; }
		.sbwp-dash-bar .bar-pending { background:#1967d2; }
		.sbwp-dash-bar .bar-failed { background:#b32d2e; }
		.sbwp-dash-bar .bar-bounced { background:#b45309; }

		.sbwp-hourly { display:flex; align-items:end; gap:3px; height:40px; margin:12px 0 4px; }
		.sbwp-hourly .sbwp-h-bar { flex:1; display:flex; flex-direction:column-reverse; min-height:4px; border-radius:2px 2px 0 0; overflow:hidden; background:#f0f0f1; position:relative; }
		.sbwp-hourly .sbwp-h-bar .ok { background:#1a7d2a; width:100%; }
		.sbwp-hourly .sbwp-h-bar .fail { background:#b32d2e; width:100%; }

		.sbwp-health-row { display:flex; gap:24px; flex-wrap:wrap; }
		.sbwp-health-item { flex:1; min-width:160px; }
		.sbwp-health-item .sbwp-hl-label { font-size:11px; color:#787c82; text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px; }
		.sbwp-health-item .sbwp-hl-value { font-size:14px; font-weight:600; display:flex; align-items:center; gap:6px; }
		.sbwp-health-item .sbwp-hl-value .dashicons { width:16px; height:16px; font-size:16px; }
		.sbwp-health-item .sbwp-hl-value .green { color:#1a7d2a; }
		.sbwp-health-item .sbwp-hl-value .red { color:#b32d2e; }
		.sbwp-health-item .sbwp-hl-value .amber { color:#b45309; }
		.sbwp-quota-bar { height:8px; border-radius:4px; background:#f0f0f1; margin:8px 0; overflow:hidden; }
		.sbwp-quota-bar span { display:block; height:100%; border-radius:4px; background:#2563eb; transition:width .4s; }
		.sbwp-quota-text { font-size:12px; color:#787c82; }

		.sbwp-mini-table { width:100%; border-collapse:collapse; }
		.sbwp-mini-table td { padding:8px 0; border-bottom:1px solid #f9fafb; font-size:13px; }
		.sbwp-mini-table tr:last-child td { border-bottom:0; }
		.sbwp-mini-table .sbwp-mt-status { font-size:11px; font-weight:600; }
		.sbwp-mini-table .sbwp-mt-to { color:#50575e; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
		.sbwp-mini-table .sbwp-mt-subject { color:#787c82; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
		.sbwp-mini-table .sbwp-mt-date { color:#9ca3af; font-size:11px; white-space:nowrap; }
		';

		wp_add_inline_style( 'common', $css );
	}

	public function register_settings(): void {
		register_setting(
			self::OPTION_KEY,
			self::OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(
					'api_key'     => '',
					'from_email'  => '',
					'from_name'   => get_bloginfo( 'name' ),
					'sandbox'     => 1,
					'log_enabled' => 1,
				),
			)
		);

		add_settings_section( 'sbwp_main', '', '__return_empty_string', self::PAGE_SLUG );

		$fields = array(
			'api_key'     => __( 'API Key', 'smtp-for-sendbyte' ),
			'from_email'  => __( 'From Email', 'smtp-for-sendbyte' ),
			'from_name'   => __( 'From Name', 'smtp-for-sendbyte' ),
			'sandbox'     => __( 'Sandbox Mode', 'smtp-for-sendbyte' ),
			'log_enabled' => __( 'Email Logging', 'smtp-for-sendbyte' ),
		);

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				$key,
				$label,
				array( $this, 'render_field' ),
				self::PAGE_SLUG,
				'sbwp_main',
				array( 'key' => $key )
			);
		}
	}

	public function render_field( array $args ): void {
		$options = get_option( self::OPTION_KEY, array() );
		$key     = $args['key'];
		$value   = $options[ $key ] ?? '';

		switch ( $key ) {
			case 'api_key':
				printf(
					'<input type="password" name="%1$s[%2$s]" value="%3$s" class="regular-text" autocomplete="off" placeholder="sk_live_… or sk_test_…" />',
					esc_attr( self::OPTION_KEY ),
					esc_attr( $key ),
					esc_attr( $value )
				);
				echo '<p class="sbwp-links"><a href="https://dashboard.sendbyte.africa" target="_blank">' . esc_html__( 'SendByte Dashboard', 'smtp-for-sendbyte' ) . '</a> &middot; <a href="https://dashboard.sendbyte.africa/api-keys" target="_blank">' . esc_html__( 'Get API Key', 'smtp-for-sendbyte' ) . '</a></p>';
				break;

			case 'from_email':
				printf(
					'<input type="email" name="%1$s[%2$s]" value="%3$s" class="regular-text" placeholder="wordpress@yourdomain.com" />',
					esc_attr( self::OPTION_KEY ),
					esc_attr( $key ),
					esc_attr( $value )
				);
				echo '<p class="description">' . esc_html__( 'The "From" address for all outgoing mail. Must use a verified domain with live keys.', 'smtp-for-sendbyte' ) . '</p>';
				break;

			case 'from_name':
				printf(
					'<input type="text" name="%1$s[%2$s]" value="%3$s" class="regular-text" placeholder="%4$s" />',
					esc_attr( self::OPTION_KEY ),
					esc_attr( $key ),
					esc_attr( $value ),
					esc_attr( get_bloginfo( 'name' ) )
				);
				echo '<p class="description">' . esc_html__( 'The "From" name recipients see (e.g. "My Website").', 'smtp-for-sendbyte' ) . '</p>';
				break;

			case 'sandbox':
				$sandbox = ! empty( $options['sandbox'] );
				?>
				<div class="sbwp-mode-switch">
					<label class="sbwp-toggle">
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[sandbox]" value="1" <?php checked( $sandbox ); ?> />
						<span class="sbwp-slider"></span>
					</label>
					<span class="sbwp-mode-badge <?php echo $sandbox ? 'sandbox' : 'live'; ?>">
						<span class="dashicons dashicons-<?php echo $sandbox ? 'hammer' : 'yes'; ?>"></span>
						<?php echo $sandbox ? esc_html__( 'Sandbox', 'smtp-for-sendbyte' ) : esc_html__( 'Live', 'smtp-for-sendbyte' ); ?>
					</span>
				</div>
				<p class="description"><?php esc_html_e( 'Sandbox simulates sending — no real delivery. Toggle off for live mode.', 'smtp-for-sendbyte' ); ?></p>
				<?php
				break;

			case 'log_enabled':
				$on = ! empty( $options['log_enabled'] );
				?>
				<label class="sbwp-toggle-label">
					<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[log_enabled]" value="1" <?php checked( $on ); ?> />
					<span>
					<?php
					echo $on
						? esc_html__( 'Logging is ON — sent emails are recorded below', 'smtp-for-sendbyte' )
						: esc_html__( 'Logging is OFF — no email history is stored', 'smtp-for-sendbyte' );
					?>
					</span>
				</label>
				<?php
				break;
		}
	}

	public function sanitize( $input ): array {
		$defaults = get_option( self::OPTION_KEY, array() );
		$output   = array();

		$output['api_key']     = isset( $input['api_key'] ) ? self::sanitize_api_key( $input['api_key'] ) : ( $defaults['api_key'] ?? '' );
		$output['from_email']  = isset( $input['from_email'] ) ? sanitize_email( $input['from_email'] ) : ( $defaults['from_email'] ?? '' );
		$output['from_name']   = isset( $input['from_name'] ) ? sanitize_text_field( $input['from_name'] ) : ( $defaults['from_name'] ?? '' );
		$output['sandbox']     = isset( $input['sandbox'] ) ? 1 : 0;
		$output['log_enabled'] = isset( $input['log_enabled'] ) ? 1 : 0;

		return $output;
	}

	private static function sanitize_api_key( string $value ): string {
		$value = sanitize_text_field( $value );
		return preg_replace( '/[^a-zA-Z0-9_\-]/', '', $value );
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$saved = isset( $_GET['settings-updated'] ) && 'true' === sanitize_key( wp_unslash( $_GET['settings-updated'] ) );
		?>
		<div class="wrap sbwp-wrap">
			<h1>
				<img src="<?php echo esc_url( SBWP_URL . 'assets/logo.png' ); ?>" alt="SendByte" width="122" height="40" style="display:block" />
				<?php echo esc_html__( 'SMTP for SendByte', 'smtp-for-sendbyte' ); ?>
			</h1>

			<?php $this->render_onboard(); ?>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'smtp-for-sendbyte' ); ?></p></div>
			<?php endif; ?>

			<?php $this->render_health(); ?>
			<?php $this->render_dashboard(); ?>

			<form action="options.php" method="post">
				<div class="sbwp-card">
					<h2><span class="dashicons dashicons-email"></span> <?php esc_html_e( 'SMTP Configuration', 'smtp-for-sendbyte' ); ?></h2>
					<?php
					settings_fields( self::OPTION_KEY );
					do_settings_sections( self::PAGE_SLUG );
					submit_button( __( 'Save Settings', 'smtp-for-sendbyte' ), 'primary', 'submit', true, array( 'style' => 'margin-top:8px' ) );
					?>
				</div>
			</form>

			<div class="sbwp-card">
				<h2><span class="dashicons dashicons-mail"></span> <?php esc_html_e( 'Send Test Email', 'smtp-for-sendbyte' ); ?></h2>
				<?php $this->render_test_result(); ?>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="sendbyte_test_email" />
					<?php wp_nonce_field( 'sendbyte_test', '_sbwp_nonce' ); ?>
					<div class="sbwp-test-row">
						<input type="email" name="test_to" id="test_to" class="regular-text" required placeholder="you@example.com" />
						<?php submit_button( __( 'Send Test', 'smtp-for-sendbyte' ), 'secondary', 'send_test', false ); ?>
					</div>
					<p class="description" style="margin:4px 0 0"><?php esc_html_e( 'Send a test email to verify your configuration is working.', 'smtp-for-sendbyte' ); ?></p>
				</form>
			</div>

			<div class="sbwp-card">
				<h2>
					<span class="dashicons dashicons-list-view"></span>
					<?php esc_html_e( 'Email Log', 'smtp-for-sendbyte' ); ?>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ); ?>" class="sbwp-refresh dashicons dashicons-update" title="<?php esc_attr_e( 'Refresh', 'smtp-for-sendbyte' ); ?>"></a>
				</h2>
				<?php $this->render_logs(); ?>
			</div>
		</div>
		<?php
	}

	private function render_dashboard(): void {
		$options = get_option( self::OPTION_KEY, array() );

		if ( empty( $options['api_key'] ) ) {
			return;
		}

		$stats = Api::get_email_stats( $options['api_key'], 100 );

		if ( ! empty( $stats['error'] ) ) {
			return;
		}

		$total     = $stats['total'];
		$delivered = $stats['delivered'];
		$bounced   = $stats['bounced'];
		$failed    = $stats['failed'];
		$pending   = $stats['pending'];
		$hourly    = $stats['hourly'];
		$recent    = $stats['recent'];

		$max_h = 0;
		foreach ( $hourly as $h ) {
			$sum = $h['ok'] + $h['fail'];
			if ( $sum > $max_h ) {
				$max_h = $sum;
			}
		}
		$max_h = max( $max_h, 1 );

		$total_shown = $delivered + $bounced + $failed + $pending;
		$d_pct = $total_shown > 0 ? round( $delivered / $total_shown * 100 ) : 0;
		$p_pct = $total_shown > 0 ? round( $pending  / $total_shown * 100 ) : 0;
		$b_pct = $total_shown > 0 ? round( $bounced  / $total_shown * 100 ) : 0;
		$f_pct = $total_shown > 0 ? round( $failed   / $total_shown * 100 ) : 0;
		?>
		<div class="sbwp-card">
			<h2>
				<span class="dashicons dashicons-chart-area"></span>
				<?php esc_html_e( 'Delivery Dashboard', 'smtp-for-sendbyte' ); ?>
				<span class="sbwp-head-right"><?php
					/* translators: %d: number of recent emails fetched from the API */
					echo esc_html( sprintf( __( 'Last %d emails', 'smtp-for-sendbyte' ), $total ) );
				?></span>
			</h2>

			<div class="sbwp-dash-grid">
				<div class="sbwp-dash-stat delivered">
					<div class="sbwp-stat-num"><?php echo esc_html( $delivered ); ?></div>
					<div class="sbwp-stat-label"><?php esc_html_e( 'Delivered', 'smtp-for-sendbyte' ); ?></div>
				</div>
				<div class="sbwp-dash-stat pending">
					<div class="sbwp-stat-num"><?php echo esc_html( $pending ); ?></div>
					<div class="sbwp-stat-label"><?php esc_html_e( 'Pending', 'smtp-for-sendbyte' ); ?></div>
				</div>
				<div class="sbwp-dash-stat bounced">
					<div class="sbwp-stat-num"><?php echo esc_html( $bounced ); ?></div>
					<div class="sbwp-stat-label"><?php esc_html_e( 'Bounced', 'smtp-for-sendbyte' ); ?></div>
				</div>
				<div class="sbwp-dash-stat failed">
					<div class="sbwp-stat-num"><?php echo esc_html( $failed ); ?></div>
					<div class="sbwp-stat-label"><?php esc_html_e( 'Failed', 'smtp-for-sendbyte' ); ?></div>
				</div>
			</div>

			<?php if ( $total_shown > 0 ) : ?>
			<div class="sbwp-dash-bar">
				<span class="bar-delivered" style="width:<?php echo esc_attr( $d_pct ); ?>%"></span>
				<span class="bar-pending" style="width:<?php echo esc_attr( $p_pct ); ?>%"></span>
				<span class="bar-bounced" style="width:<?php echo esc_attr( $b_pct ); ?>%"></span>
				<span class="bar-failed" style="width:<?php echo esc_attr( $f_pct ); ?>%"></span>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $hourly ) ) : ?>
			<div class="sbwp-hourly">
				<?php foreach ( $hourly as $hk => $hv ) : ?>
					<div class="sbwp-h-bar" style="height:<?php echo esc_attr( max( 4, ( $hv['ok'] + $hv['fail'] ) / $max_h * 40 ) ); ?>px" title="<?php echo esc_attr( substr( $hk, 0, 10 ) . ' ' . substr( $hk, 11 ) . ':00' ); ?>">
						<?php if ( $hv['fail'] > 0 ) : ?>
							<div class="fail" style="flex:<?php echo esc_attr( $hv['fail'] ); ?>"></div>
						<?php endif; ?>
						<?php if ( $hv['ok'] > 0 ) : ?>
							<div class="ok" style="flex:<?php echo esc_attr( $hv['ok'] ); ?>"></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="description" style="margin:0;font-size:11px;text-align:center"><?php esc_html_e( 'Hourly activity (green = delivered, red = failed/bounced)', 'smtp-for-sendbyte' ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $recent ) ) : ?>
			<h3 style="font-size:13px;font-weight:600;margin:16px 0 8px"><?php esc_html_e( 'Recent Activity', 'smtp-for-sendbyte' ); ?></h3>
			<table class="sbwp-mini-table">
				<?php foreach ( $recent as $email ) :
					$s = $email['status'] ?? 'sent';
					$s_class = in_array( $s, array( 'delivered', 'failed', 'bounced', 'sent', 'queued' ), true ) ? $s : 'sent';
					$to = is_array( $email['to'] ?? '' ) ? implode( ', ', $email['to'] ) : ( $email['to'] ?? '' );
					$date = isset( $email['created_at'] ) ? wp_date( 'M j, H:i', strtotime( $email['created_at'] ) ) : '';
				?>
				<tr>
					<td><span class="sbwp-status-badge <?php echo esc_attr( $s_class ); ?>"><?php echo esc_html( ucfirst( $s ) ); ?></span></td>
					<td class="sbwp-mt-to" title="<?php echo esc_attr( $to ); ?>"><?php echo esc_html( $to ); ?></td>
					<td class="sbwp-mt-subject" title="<?php echo esc_attr( $email['subject'] ?? '' ); ?>"><?php echo esc_html( $email['subject'] ?? '' ); ?></td>
					<td class="sbwp-mt-date"><?php echo esc_html( $date ); ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_health(): void {
		$options = get_option( self::OPTION_KEY, array() );
		$has_key = ! empty( $options['api_key'] );

		if ( ! $has_key ) {
			echo '<div class="sbwp-card">';
			echo '<h2><span class="dashicons dashicons-shield"></span> ' . esc_html__( 'Connection Health', 'smtp-for-sendbyte' ) . '</h2>';
			echo '<p class="sbwp-empty-state">' . esc_html__( 'Add your API key above to check connection health.', 'smtp-for-sendbyte' ) . '</p>';
			echo '</div>';
			return;
		}

		$health = Api::health_check( $options['api_key'] );

		if ( ! empty( $health['error'] ) && ! $health['valid'] ) {
			printf(
				'<div class="sbwp-card"><p class="sbwp-empty-state">%s %s</p></div>',
				esc_html__( 'Connection failed:', 'smtp-for-sendbyte' ),
				esc_html( $health['error'] )
			);
			return;
		}

		$pct = $health['quota'] > 0 ? round( $health['used'] / $health['quota'] * 100 ) : 0;
		?>
		<div class="sbwp-card">
			<h2>
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Connection Health', 'smtp-for-sendbyte' ); ?>
				<span class="sbwp-head-right">
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sbwp_refresh_health' ), 'sbwp_refresh_health' ) ); ?>" class="sbwp-refresh dashicons dashicons-update" title="<?php esc_attr_e( 'Refresh', 'smtp-for-sendbyte' ); ?>"></a>
				</span>
			</h2>

			<div class="sbwp-health-row">
				<div class="sbwp-health-item">
					<div class="sbwp-hl-label"><?php esc_html_e( 'API Key', 'smtp-for-sendbyte' ); ?></div>
					<div class="sbwp-hl-value">
						<span class="dashicons dashicons-yes-alt green"></span>
						<?php esc_html_e( 'Connected', 'smtp-for-sendbyte' ); ?>
					</div>
				</div>
				<div class="sbwp-health-item">
					<div class="sbwp-hl-label"><?php esc_html_e( 'Mode', 'smtp-for-sendbyte' ); ?></div>
					<div class="sbwp-hl-value">
						<span class="dashicons dashicons-<?php echo 'sandbox' === $health['mode'] ? 'hammer' : 'yes'; ?> <?php echo 'sandbox' === $health['mode'] ? 'amber' : 'green'; ?>"></span>
						<?php echo 'sandbox' === $health['mode'] ? esc_html__( 'Sandbox', 'smtp-for-sendbyte' ) : esc_html__( 'Live', 'smtp-for-sendbyte' ); ?>
					</div>
				</div>
				<div class="sbwp-health-item">
					<div class="sbwp-hl-label"><?php esc_html_e( 'Plan', 'smtp-for-sendbyte' ); ?></div>
					<div class="sbwp-hl-value"><?php echo esc_html( $health['plan'] ?: '—' ); ?></div>
				</div>
				<div class="sbwp-health-item">
					<div class="sbwp-hl-label"><?php esc_html_e( 'Verified Domains', 'smtp-for-sendbyte' ); ?></div>
					<div class="sbwp-hl-value">
						<?php
						$verified = array();
						foreach ( $health['domains'] as $d ) {
							if ( ! empty( $d['domain'] ) && 'verified' === ( $d['status'] ?? '' ) ) {
								$verified[] = $d['domain'];
							}
						}
						if ( ! empty( $verified ) ) {
							echo esc_html( implode( ', ', $verified ) );
						} else {
							$has_domains = ! empty( $health['domains'] );
							if ( $has_domains ) {
								esc_html_e( 'None verified', 'smtp-for-sendbyte' );
							} else {
								echo '—';
							}
						}
						?>
					</div>
				</div>
			</div>

			<?php if ( $health['quota'] > 0 ) : ?>
			<div style="margin-top:16px">
				<div class="sbwp-hl-label"><?php esc_html_e( 'Monthly Usage', 'smtp-for-sendbyte' ); ?></div>
				<div class="sbwp-quota-bar"><span style="width:<?php echo esc_attr( $pct ); ?>%"></span></div>
				<div class="sbwp-quota-text">
					<?php
					printf(
						/* translators: 1: number used, 2: total quota */
						esc_html__( '%1$s of %2$s emails used', 'smtp-for-sendbyte' ),
						esc_html( number_format_i18n( $health['used'] ) ),
						esc_html( number_format_i18n( $health['quota'] ) )
					);
					?>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_onboard(): void {
		$options  = get_option( self::OPTION_KEY, array() );
		$has_key  = ! empty( $options['api_key'] );
		$has_from = ! empty( $options['from_email'] );
		$dismissed = get_user_meta( get_current_user_id(), self::ONBOARD_KEY, true );

		if ( $has_key && $has_from ) {
			return;
		}
		if ( $dismissed ) {
			return;
		}

		$steps = array(
			array(
				'label' => __( 'Add API Key', 'smtp-for-sendbyte' ),
				'done'  => $has_key,
			),
			array(
				'label' => __( 'Set From Address', 'smtp-for-sendbyte' ),
				'done'  => $has_from,
			),
			array(
				'label' => __( 'Send Test Email', 'smtp-for-sendbyte' ),
				'done'  => false,
			),
		);

		$title = $has_key ? __( 'Finish setting up', 'smtp-for-sendbyte' ) : __( 'Welcome to SMTP for SendByte', 'smtp-for-sendbyte' );
		$desc  = $has_key
			? __( 'Almost there! Set your from address and send a test to start.', 'smtp-for-sendbyte' )
			: __( 'Add your API key to start sending emails from WordPress through SendByte.', 'smtp-for-sendbyte' );
		?>
		<div class="sbwp-onboard">
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sbwp_dismiss_onboard' ), 'sbwp_dismiss' ) ); ?>" class="sbwp-dismiss dashicons dashicons-no-alt" title="<?php esc_attr_e( 'Dismiss', 'smtp-for-sendbyte' ); ?>"></a>
			<h3><?php echo esc_html( $title ); ?></h3>
			<p><?php echo esc_html( $desc ); ?></p>
			<ul class="sbwp-steps">
				<?php foreach ( $steps as $step ) : ?>
					<li>
						<span class="sbwp-step-num<?php echo $step['done'] ? ' done' : ''; ?>">
							<?php echo $step['done'] ? '<span class="dashicons dashicons-yes" style="font-size:14px;width:14px;height:14px;"></span>' : '&nbsp;'; ?>
						</span>
						<span class="sbwp-step-label"><?php echo esc_html( $step['label'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	private function render_test_result(): void {
		if ( ! isset( $_GET['sbwp_test'], $_GET['_wpnonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'sbwp_test_result' ) ) {
			return;
		}

		$type = sanitize_key( wp_unslash( $_GET['sbwp_test'] ) );

		if ( 'success' === $type ) {
			echo '<div class="sbwp-test-result success">' . esc_html__( 'Test email sent successfully! Check the recipient inbox.', 'smtp-for-sendbyte' ) . '</div>';
		} else {
			echo '<div class="sbwp-test-result fail">' . esc_html__( 'Test email failed. Check your API key and domain settings.', 'smtp-for-sendbyte' ) . '</div>';
		}
	}

	private function render_logs(): void {
		$options = get_option( self::OPTION_KEY, array() );

		if ( empty( $options['log_enabled'] ) ) {
			echo '<p class="sbwp-empty-state">' . esc_html__( 'Logging is disabled. Enable "Email Logging" above to start recording sent emails.', 'smtp-for-sendbyte' ) . '</p>';
			return;
		}

		$logs = Logger::get_recent( 25 );

		if ( empty( $logs ) ) {
			echo '<p class="sbwp-empty-state">' . esc_html__( 'No emails logged yet. Send a test email above to see results here.', 'smtp-for-sendbyte' ) . '</p>';
			return;
		}

		echo '<table class="sbwp-log-table"><thead><tr>';
		echo '<th>' . esc_html__( 'Date', 'smtp-for-sendbyte' ) . '</th>';
		echo '<th>' . esc_html__( 'To', 'smtp-for-sendbyte' ) . '</th>';
		echo '<th>' . esc_html__( 'Subject', 'smtp-for-sendbyte' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'smtp-for-sendbyte' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $logs as $log ) {
			$valid_statuses = array( 'delivered', 'failed', 'sent' );
			$status_class   = in_array( $log->status, $valid_statuses, true ) ? $log->status : 'sent';

			switch ( $log->status ) {
				case 'delivered':
					$label = __( 'Delivered', 'smtp-for-sendbyte' );
					break;
				case 'failed':
					$label = __( 'Failed', 'smtp-for-sendbyte' );
					break;
				default:
					$label = __( 'Sent', 'smtp-for-sendbyte' );
					break;
			}

			$date = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->created_at ) );

			echo '<tr>';
			echo '<td class="sbwp-date">' . esc_html( $date ) . '</td>';
			echo '<td class="sbwp-to" title="' . esc_attr( $log->to_email ) . '">' . esc_html( $log->to_email ) . '</td>';
			echo '<td class="sbwp-subj" title="' . esc_attr( $log->subject ) . '">' . esc_html( $log->subject ?: '—' ) . '</td>';
			echo '<td><span class="sbwp-status-badge ' . esc_attr( $status_class ) . '">' . esc_html( $label ) . '</span></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	public function handle_test_email(): void {
		if ( ! isset( $_POST['_sbwp_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_sbwp_nonce'] ) ), 'sendbyte_test' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'smtp-for-sendbyte' ) );
		}

		$to = isset( $_POST['test_to'] ) ? sanitize_email( wp_unslash( $_POST['test_to'] ) ) : '';
		if ( ! is_email( $to ) ) {
			wp_die( esc_html__( 'Invalid email address.', 'smtp-for-sendbyte' ) );
		}

		$subject = sprintf(
			/* translators: %s: current date/time */
			__( 'SendByte Test Email — %s', 'smtp-for-sendbyte' ),
			wp_date( 'Y-m-d H:i:s' )
		);

		$message = sprintf(
			/* translators: 1: site URL, 2: current date/time */
			__( "This is a test email sent from %1\$s via SendByte.\n\nSent at: %2\$s", 'smtp-for-sendbyte' ),
			home_url(),
			wp_date( 'Y-m-d H:i:s' )
		);

		$sent = wp_mail( $to, $subject, $message );

		wp_safe_redirect(
			add_query_arg(
				array(
					'sbwp_test' => $sent ? 'success' : 'fail',
					'_wpnonce'  => wp_create_nonce( 'sbwp_test_result' ),
				),
				admin_url( 'options-general.php?page=' . self::PAGE_SLUG )
			)
		);
		exit;
	}

	public function dismiss_onboard(): void {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'sbwp_dismiss' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'smtp-for-sendbyte' ) );
		}

		update_user_meta( get_current_user_id(), self::ONBOARD_KEY, 1 );
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	public function refresh_health(): void {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'sbwp_refresh_health' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'smtp-for-sendbyte' ) );
		}

		$options = get_option( self::OPTION_KEY, array() );
		if ( ! empty( $options['api_key'] ) ) {
			Api::clear_cache( $options['api_key'] );
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) );
		exit;
	}
}
