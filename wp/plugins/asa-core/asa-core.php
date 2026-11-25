<?php
/**
 * Plugin Name: ASA Core
 * Description: Catálogo conectado a Promo Opción, lista de cotización, leads, atribución, reporte y SEO para ASA Promocionales.
 * Version: 0.1.2
 * Author: Adrià López
 * Author URI: https://adria-lopez.com
 * Requires PHP: 8.0
 * Text Domain: asa-core
 */

defined( 'ABSPATH' ) || exit;

define( 'ASA_CORE_VERSION', '0.1.2' );
define( 'ASA_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASA_CORE_URL', plugin_dir_url( __FILE__ ) );

foreach ( array( 'catalog', 'api', 'sync', 'attribution', 'leads', 'front', 'seo' ) as $asa_file ) {
	require ASA_CORE_DIR . "includes/$asa_file.php";
}
if ( is_admin() ) {
	require ASA_CORE_DIR . 'includes/admin.php';
}

register_activation_hook( __FILE__, function () {
	asa_register_catalog();
	asa_leads_install();
	asa_roles_install();
	flush_rewrite_rules();
	if ( ! wp_next_scheduled( 'asa_sync_catalog' ) ) {
		wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'asa_sync_catalog' );
	}
	if ( ! wp_next_scheduled( 'asa_sync_stock' ) ) {
		wp_schedule_event( time() + 12 * HOUR_IN_SECONDS, 'twicedaily', 'asa_sync_stock' );
	}
} );

register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'asa_sync_catalog' );
	wp_clear_scheduled_hook( 'asa_sync_stock' );
} );

/** Ajustes con valores por defecto. */
function asa_opt( $key ) {
	$defaults = array(
		'whatsapp'     => '520000000000',
		'notify_email' => '',
		'phone'        => '',
		'email'        => '',
		'mailchimp_url' => '',
	);
	$saved = (array) get_option( 'asa_settings', array() );
	return $saved[ $key ] ?? ( $defaults[ $key ] ?? '' );
}
