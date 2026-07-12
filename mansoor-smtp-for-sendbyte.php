<?php
/**
 * Plugin Name:       Mansoor SMTP for SendByte
 * Plugin URI:        https://sendbyte.africa
 * Description:       Send all WordPress emails through SendByte's SMTP. Includes logging, test email, and sandbox mode.
 * Version:           1.1.3
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

define( 'SBWP_VERSION', '1.1.3' );
define( 'SBWP_FILE',    __FILE__ );
define( 'SBWP_PATH',    plugin_dir_path( __FILE__ ) );
define( 'SBWP_URL',     plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( $class ) {
	$prefix = 'SBWP\\';
	$base   = SBWP_PATH . 'includes/';

	if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
		return;
	}

	$relative = substr( $class, strlen( $prefix ) );
	$file     = $base . 'class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

register_activation_hook( __FILE__, 'sbwp_activate' );
function sbwp_activate() {
	SBWP\Logger::install();
	set_transient( 'sbwp_activation_notice', true, 86400 );
}

add_action( 'plugins_loaded', 'sbwp_init' );
function sbwp_init() {
	new SBWP\Admin();
	new SBWP\Smtp();
}

add_action( 'admin_notices', 'sbwp_activation_notice' );
function sbwp_activation_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$options = get_option( 'sendbyte_wp_settings', array() );
	if ( ! empty( $options['api_key'] ) ) {
		delete_transient( 'sbwp_activation_notice' );
		return;
	}

	if ( ! get_transient( 'sbwp_activation_notice' ) ) {
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

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sbwp_action_links' );
function sbwp_action_links( $links ) {
	$url = admin_url( 'options-general.php?page=mansoor-smtp-for-sendbyte' );
	array_unshift(
		$links,
		sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'mansoor-smtp-for-sendbyte' ) )
	);
	return $links;
}
