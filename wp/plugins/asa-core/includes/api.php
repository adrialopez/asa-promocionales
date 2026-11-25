<?php
/**
 * Cliente de la API de Promo Opción.
 * Credenciales: constantes ASA_PROMO_EMAIL y ASA_PROMO_PASSWORD en wp-config.php.
 * La API admite 3 inicios de sesión por email cada 24 h, así que el token se reutiliza
 * y nunca se hacen más de 2 intentos propios en esa ventana.
 */
defined( 'ABSPATH' ) || exit;

const ASA_API_URL = 'https://www.promocionalesenlinea.net/graphql';

function asa_api_post( $query, $variables = array(), $token = '' ) {
	$headers = array( 'Content-Type' => 'application/json' );
	if ( $token ) {
		$headers['Authorization'] = 'Bearer ' . $token;
	}
	$res = wp_remote_post( ASA_API_URL, array(
		'timeout' => 180,
		'headers' => $headers,
		'body'    => wp_json_encode( array( 'query' => $query, 'variables' => (object) $variables ) ),
	) );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( ! is_array( $body ) ) {
		return new WP_Error( 'asa_api', 'Respuesta no válida de la API (HTTP ' . wp_remote_retrieve_response_code( $res ) . ').' );
	}
	if ( ! empty( $body['errors'] ) ) {
		$e = $body['errors'][0];
		return new WP_Error( 'asa_api', (string) ( $e['message'] ?? 'Error de la API' ), array( 'code' => $e['extensions']['code'] ?? '', 'status' => wp_remote_retrieve_response_code( $res ) ) );
	}
	return $body['data'] ?? array();
}

function asa_api_token( $force = false ) {
	$cache = get_option( 'asa_token' );
	if ( ! $force && is_array( $cache ) && time() - $cache['at'] < 23 * HOUR_IN_SECONDS ) {
		return $cache['token'];
	}
	if ( ! defined( 'ASA_PROMO_EMAIL' ) || ! defined( 'ASA_PROMO_PASSWORD' ) ) {
		return new WP_Error( 'asa_api', 'Faltan las credenciales de Promo Opción en wp-config.php.' );
	}
	$attempts = array_filter( (array) get_option( 'asa_login_attempts', array() ), fn( $t ) => time() - $t < DAY_IN_SECONDS );
	if ( count( $attempts ) >= 2 ) {
		return new WP_Error( 'asa_api', 'Límite propio de inicios de sesión alcanzado; se reintentará en 24 h.' );
	}
	$attempts[] = time();
	update_option( 'asa_login_attempts', $attempts, false );
	$data = asa_api_post(
		'mutation($email:String!,$password:String!){login(email:$email,password:$password){accessToken}}',
		array( 'email' => ASA_PROMO_EMAIL, 'password' => ASA_PROMO_PASSWORD )
	);
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	$token = $data['login']['accessToken'] ?? '';
	if ( ! $token ) {
		return new WP_Error( 'asa_api', 'La API no devolvió token.' );
	}
	update_option( 'asa_token', array( 'token' => $token, 'at' => time() ), false );
	return $token;
}

/** Consulta autenticada; si el token fue rechazado, inicia sesión una sola vez. */
function asa_api_query( $query, $variables = array() ) {
	$token = asa_api_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}
	$data = asa_api_post( $query, $variables, $token );
	if ( is_wp_error( $data ) ) {
		$d = $data->get_error_data();
		if ( 'UNAUTHENTICATED' === ( $d['code'] ?? '' ) || 401 === ( $d['status'] ?? 0 ) ) {
			$token = asa_api_token( true );
			return is_wp_error( $token ) ? $token : asa_api_post( $query, $variables, $token );
		}
	}
	return $data;
}
