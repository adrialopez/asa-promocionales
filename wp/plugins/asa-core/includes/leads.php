<?php
/** Tabla de leads y recepción desde la web. */
defined( 'ABSPATH' ) || exit;

const ASA_STATUSES = array(
	'clic'       => 'Clic en WhatsApp (sin confirmar)',
	'nuevo'      => 'Nuevo',
	'contactado' => 'Contactado',
	'cotizado'   => 'Cotizado',
	'convertido' => 'Convertido',
	'perdido'    => 'Perdido',
);
const ASA_TYPES = array( 'cotizacion' => 'Cotización', 'contacto' => 'Contacto', 'whatsapp' => 'WhatsApp', 'credito' => 'Línea de crédito', 'manual' => 'Alta manual' );
const ASA_HOW   = array( 'Búsqueda en Google', 'Asistente de IA (ChatGPT u otro)', 'Anuncio', 'Redes sociales', 'Recomendación', 'Ya soy cliente', 'Feria o evento', 'Otro' );

function asa_table() {
	global $wpdb;
	return $wpdb->prefix . 'asa_leads';
}

function asa_leads_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t = asa_table();
	dbDelta( "CREATE TABLE $t (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		folio varchar(20) NOT NULL DEFAULT '',
		created datetime NOT NULL,
		updated datetime NOT NULL,
		type varchar(20) NOT NULL DEFAULT 'contacto',
		status varchar(20) NOT NULL DEFAULT 'nuevo',
		name varchar(190) NOT NULL DEFAULT '',
		company varchar(190) NOT NULL DEFAULT '',
		email varchar(190) NOT NULL DEFAULT '',
		phone varchar(60) NOT NULL DEFAULT '',
		state varchar(60) NOT NULL DEFAULT '',
		needed_date varchar(20) NOT NULL DEFAULT '',
		message text,
		items longtext,
		file varchar(190) NOT NULL DEFAULT '',
		how_known varchar(80) NOT NULL DEFAULT '',
		channel varchar(40) NOT NULL DEFAULT 'Directo',
		first_channel varchar(40) NOT NULL DEFAULT 'Directo',
		source varchar(250) NOT NULL DEFAULT '',
		medium varchar(250) NOT NULL DEFAULT '',
		campaign varchar(250) NOT NULL DEFAULT '',
		term varchar(250) NOT NULL DEFAULT '',
		gclid varchar(250) NOT NULL DEFAULT '',
		fbclid varchar(250) NOT NULL DEFAULT '',
		landing varchar(250) NOT NULL DEFAULT '',
		referrer varchar(250) NOT NULL DEFAULT '',
		first_touch text,
		last_touch text,
		page varchar(250) NOT NULL DEFAULT '',
		device varchar(10) NOT NULL DEFAULT '',
		consent tinyint(1) NOT NULL DEFAULT 0,
		amount decimal(12,2) NOT NULL DEFAULT 0,
		lost_reason varchar(190) NOT NULL DEFAULT '',
		advisor varchar(120) NOT NULL DEFAULT '',
		history longtext,
		converted_at datetime DEFAULT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY folio (folio),
		KEY created (created),
		KEY status (status),
		KEY channel (channel)
	) {$wpdb->get_charset_collate()};" );
}

function asa_roles_install() {
	add_role( 'asa_asesor', 'Asesor ASA', array( 'read' => true, 'manage_asa_leads' => true ) );
	get_role( 'administrator' )?->add_cap( 'manage_asa_leads' );
}

function asa_insert_lead( array $row ) {
	global $wpdb;
	$now = current_time( 'mysql' );
	$row = array_merge( array( 'folio' => 'tmp-' . wp_generate_password( 12, false ), 'created' => $now, 'updated' => $now ), $row );
	if ( false === $wpdb->insert( asa_table(), $row ) ) {
		return null;
	}
	$id = $wpdb->insert_id;
	if ( str_starts_with( $row['folio'], 'tmp-' ) ) {
		$row['folio'] = sprintf( 'A-%05d', $id );
		$wpdb->update( asa_table(), array( 'folio' => $row['folio'] ), array( 'id' => $id ) );
	}
	return $row['folio'];
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'asa/v1', '/lead', array( 'methods' => 'POST', 'callback' => 'asa_rest_lead', 'permission_callback' => '__return_true' ) );
	register_rest_route( 'asa/v1', '/wa', array( 'methods' => 'POST', 'callback' => 'asa_rest_wa', 'permission_callback' => '__return_true' ) );
} );

/** Límite por IP (se guarda solo un hash, durante una hora). */
function asa_throttle( $bucket, $max ) {
	$key = 'asa_t_' . $bucket . '_' . substr( wp_hash( $_SERVER['REMOTE_ADDR'] ?? '' ), 0, 20 );
	$n   = (int) get_transient( $key );
	if ( $n >= $max ) {
		return false;
	}
	set_transient( $key, $n + 1, HOUR_IN_SECONDS );
	return true;
}

function asa_request_context( WP_REST_Request $r ) {
	$attr  = json_decode( (string) $r->get_param( 'attribution' ), true );
	$first = asa_clean_touch( (array) ( $attr['first'] ?? array() ) );
	$last  = asa_clean_touch( (array) ( $attr['last'] ?? array() ) );
	return array_merge( asa_attribution( $first, $last ), array(
		'consent' => empty( $attr['consent'] ) ? 0 : 1,
		'page'    => mb_substr( sanitize_text_field( (string) $r->get_param( 'page' ) ), 0, 250 ),
		'device'  => wp_is_mobile() ? 'móvil' : 'equipo',
	) );
}

function asa_clean_items( $json ) {
	$items = array();
	foreach ( array_slice( (array) json_decode( (string) $json, true ), 0, 60 ) as $i ) {
		$id = (int) ( $i['id'] ?? 0 );
		if ( ! $id || 'asa_producto' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
			continue;
		}
		$items[] = array(
			'id'    => $id,
			'sku'   => (string) get_post_meta( $id, '_asa_sku', true ),
			'name'  => get_the_title( $id ),
			'qty'   => max( 1, min( 1000000, (int) ( $i['qty'] ?? 1 ) ) ),
			'color' => mb_substr( sanitize_text_field( (string) ( $i['color'] ?? '' ) ), 0, 80 ),
			'tech'  => mb_substr( sanitize_text_field( (string) ( $i['tech'] ?? '' ) ), 0, 80 ),
		);
	}
	return $items;
}

/** Guarda el logotipo fuera del alcance público, con nombre aleatorio. */
function asa_store_file( array $f ) {
	if ( UPLOAD_ERR_OK !== $f['error'] || $f['size'] > 8 * MB_IN_BYTES || ! is_uploaded_file( $f['tmp_name'] ) ) {
		return new WP_Error( 'asa_file', 'El archivo no se pudo recibir. Máximo 8 MB.' );
	}
	$ext = strtolower( pathinfo( $f['name'], PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, array( 'pdf', 'png', 'jpg', 'jpeg', 'ai', 'eps' ), true ) ) {
		return new WP_Error( 'asa_file', 'Formato no admitido. Usa PDF, PNG, JPG, AI o EPS.' );
	}
	$dir = wp_upload_dir()['basedir'] . '/asa-leads';
	wp_mkdir_p( $dir );
	if ( ! file_exists( "$dir/.htaccess" ) ) {
		file_put_contents( "$dir/.htaccess", "Require all denied\n" );
		file_put_contents( "$dir/index.html", '' );
	}
	$name = wp_generate_password( 32, false ) . '.' . $ext;
	return move_uploaded_file( $f['tmp_name'], "$dir/$name" ) ? $name : new WP_Error( 'asa_file', 'No se pudo guardar el archivo.' );
}

function asa_rest_lead( WP_REST_Request $r ) {
	$fail = fn( $msg, $code = 400 ) => new WP_REST_Response( array( 'ok' => false, 'message' => $msg ), $code );
	// Trampa para robots: campo oculto relleno o envío en menos de 3 segundos.
	if ( $r->get_param( 'website' ) || (int) $r->get_param( 'elapsed' ) < 3 ) {
		return $fail( 'No pudimos validar el envío. Inténtalo de nuevo.' );
	}
	$type  = in_array( $r->get_param( 'type' ), array( 'cotizacion', 'contacto', 'credito' ), true ) ? $r->get_param( 'type' ) : 'contacto';
	$text  = fn( $k, $len = 190 ) => mb_substr( sanitize_text_field( (string) $r->get_param( $k ) ), 0, $len );
	$name  = $text( 'name' );
	$email = sanitize_email( (string) $r->get_param( 'email' ) );
	if ( ! $name || ! is_email( $email ) ) {
		return $fail( 'Escribe tu nombre y un correo válido.' );
	}
	if ( ! $r->get_param( 'privacy' ) ) {
		return $fail( 'Es necesario aceptar el aviso de privacidad.' );
	}
	if ( ! asa_throttle( 'lead', 10 ) ) {
		return $fail( 'Recibimos varias solicitudes desde tu conexión. Inténtalo más tarde.', 429 );
	}
	$file  = '';
	$files = $r->get_file_params();
	if ( ! empty( $files['logo']['name'] ) ) {
		$file = asa_store_file( $files['logo'] );
		if ( is_wp_error( $file ) ) {
			return $fail( $file->get_error_message() );
		}
	}
	$items = asa_clean_items( $r->get_param( 'items' ) );
	$how   = in_array( $r->get_param( 'how_known' ), ASA_HOW, true ) ? $r->get_param( 'how_known' ) : '';
	$row   = array_merge( asa_request_context( $r ), array(
		'type'        => $type,
		'status'      => 'nuevo',
		'name'        => $name,
		'company'     => $text( 'company' ),
		'email'       => $email,
		'phone'       => $text( 'phone', 60 ),
		'state'       => $text( 'state', 60 ),
		'needed_date' => $text( 'needed_date', 20 ),
		'message'     => mb_substr( sanitize_textarea_field( (string) $r->get_param( 'message' ) ), 0, 3000 ),
		'items'       => wp_json_encode( $items ),
		'file'        => $file,
		'how_known'   => $how,
	) );
	$folio = asa_insert_lead( $row );
	if ( ! $folio ) {
		return $fail( 'No pudimos guardar tu solicitud. Escríbenos por WhatsApp.', 500 );
	}
	asa_notify( $folio, $row, $items );
	return new WP_REST_Response( array( 'ok' => true, 'folio' => $folio ) );
}

function asa_rest_wa( WP_REST_Request $r ) {
	$folio = strtoupper( sanitize_text_field( (string) $r->get_param( 'folio' ) ) );
	if ( ! preg_match( '/^W-[A-Z0-9]{6}$/', $folio ) || ! asa_throttle( 'wa', 20 ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 400 );
	}
	asa_insert_lead( array_merge( asa_request_context( $r ), array(
		'folio'  => $folio,
		'type'   => 'whatsapp',
		'status' => 'clic',
		'items'  => wp_json_encode( asa_clean_items( $r->get_param( 'items' ) ) ),
	) ) );
	return new WP_REST_Response( array( 'ok' => true ) );
}

/** Aviso interno; solo si hay un correo configurado. */
function asa_notify( $folio, array $row, array $items ) {
	$to = asa_opt( 'notify_email' );
	if ( ! $to ) {
		return;
	}
	$lines = array(
		"Folio: $folio", 'Tipo: ' . ASA_TYPES[ $row['type'] ], "Nombre: {$row['name']}", "Empresa: {$row['company']}",
		"Correo: {$row['email']}", "Teléfono: {$row['phone']}", "Estado: {$row['state']}", "Origen: {$row['channel']}", '', $row['message'], '',
	);
	foreach ( $items as $i ) {
		$lines[] = "{$i['qty']} x {$i['name']} ({$i['sku']}) {$i['color']} {$i['tech']}";
	}
	$lines[] = '';
	$lines[] = admin_url( 'admin.php?page=asa-leads&s=' . rawurlencode( $folio ) );
	wp_mail( $to, "Nuevo lead $folio - ASA", implode( "\n", $lines ), array( 'Reply-To: ' . $row['email'] ) );
}
