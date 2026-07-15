<?php
/**
 * Plugin Name:       Mansoor SMTP for SendByte
 * Plugin URI:        https://sendbyte.africa
 * Description:       Send all WordPress emails through SendByte's SMTP. Includes logging, test email, and sandbox mode.
 * Version:           1.1.4
 * Requires PHP:      7.4
 * Requires at least: 5.5
 * Requires Plugins:  
 * Author:            Mansoor Saidu
 * Author URI:        https://profiles.wordpress.org/mansoor8080/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mansoor-smtp-for-sendbyte
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'MANSMTP_VERSION', '1.1.4' );
define( 'MANSMTP_FILE',    __FILE__ );
define( 'MANSMTP_PATH',    plugin_dir_path( __FILE__ ) );
define( 'MANSMTP_URL',     plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( $class ) {
	$prefix = 'MANSMTP\\';
	$base   = MANSMTP_PATH . 'includes/';

	if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
		return;
	}

	$relative = substr( $class, strlen( $prefix ) );
	$file     = $base . 'class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

register_activation_hook( __FILE__, 'mansmtp_activate' );
function mansmtp_activate() {
	MANSMTP\Logger::install();
	set_transient( 'mansmtp_activation_notice', true, 86400 );
}

add_action( 'plugins_loaded', 'mansmtp_init' );
function mansmtp_init() {
	new MANSMTP\Admin();
	new MANSMTP\Smtp();
}

add_action( 'admin_notices', 'mansmtp_activation_notice' );
function mansmtp_activation_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$options = get_option( 'mansmtp_settings', array() );
	if ( ! empty( $options['api_key'] ) ) {
		delete_transient( 'mansmtp_activation_notice' );
		return;
	}

	if ( ! get_transient( 'mansmtp_activation_notice' ) ) {
		return;
	}

	$url = admin_url( 'options-general.php?page=mansoor-smtp-for-sendbyte' );
	?>
	<div class="notice notice-info is-dismissible" style="border-left-color:#2563eb">
		<p>
			<strong><?php esc_html_e( 'Mansoor SMTP for SendByte', 'mansoor-smtp-for-sendbyte' ); ?></strong> &mdash;
			<?php esc_html_e( 'Add your API key to start sending emails from WordPress through SendByte.', 'mansoor-smtp-for-sendbyte' ); ?>
			<a href="<?php echo esc_url( $url ); ?>" style="text-decoration:none;font-weight:600;color:#2563eb">
				<?php esc_html_e( 'Go to Settings', 'mansoor-smtp-for-sendbyte' ); ?> &rarr;
			</a>
		</p>
	</div>
	<?php
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mansmtp_action_links' );
function mansmtp_action_links( $links ) {
	$url = admin_url( 'options-general.php?page=mansoor-smtp-for-sendbyte' );
	array_unshift(
		$links,
		sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'mansoor-smtp-for-sendbyte' ) )
	);
	return $links;
}
