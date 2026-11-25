<?php
/** Clasificación del origen de cada lead. El navegador envía los datos en bruto; aquí se decide el canal. */
defined( 'ABSPATH' ) || exit;

const ASA_CHANNELS = array(
	'Google Ads', 'Meta Ads', 'Otros anuncios', 'Buscador orgánico', 'Asistentes de IA',
	'Redes sociales', 'Correo', 'Folletos y ferias', 'Referencia', 'Directo',
);

const ASA_TOUCH_KEYS = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid', 'fbclid', 'msclkid', 'ref', 'landing', 'ts' );

function asa_clean_touch( $raw ) {
	$out = array();
	foreach ( ASA_TOUCH_KEYS as $k ) {
		if ( isset( $raw[ $k ] ) && is_scalar( $raw[ $k ] ) && '' !== (string) $raw[ $k ] ) {
			$out[ $k ] = mb_substr( sanitize_text_field( (string) $raw[ $k ] ), 0, 250 );
		}
	}
	return $out;
}

function asa_channel( array $t ) {
	$src  = strtolower( $t['utm_source'] ?? '' );
	$med  = strtolower( $t['utm_medium'] ?? '' );
	$host = strtolower( $t['ref'] ?? '' );
	$paid = (bool) preg_match( '/^(cpc|ppc|paid|paidsearch|paid_social|paid-social|display|ads?)$/', $med );
	$meta = (bool) preg_match( '/^(facebook|instagram|meta|fb|ig)$/', $src );

	if ( ! empty( $t['gclid'] ) || ! empty( $t['gbraid'] ) || ! empty( $t['wbraid'] ) || ( $paid && str_contains( $src, 'google' ) ) ) {
		return 'Google Ads';
	}
	if ( $paid && $meta ) {
		return 'Meta Ads';
	}
	if ( $paid || ! empty( $t['msclkid'] ) ) {
		return 'Otros anuncios';
	}
	$ai = '/(chatgpt|openai|perplexity|gemini\.google|^gemini$|copilot|claude\.ai|^claude$|deepseek|meta\.ai|you\.com|grok)/';
	if ( preg_match( $ai, $src ) || preg_match( $ai, $host ) ) {
		return 'Asistentes de IA';
	}
	if ( 'email' === $med || 'correo' === $med || preg_match( '/(mail\.google|outlook\.live|mail\.yahoo)/', $host ) ) {
		return 'Correo';
	}
	if ( in_array( $med, array( 'qr', 'offline', 'impreso', 'feria' ), true ) ) {
		return 'Folletos y ferias';
	}
	if ( $meta || ! empty( $t['fbclid'] ) || 'social' === $med || preg_match( '/(facebook|instagram|linkedin|lnkd\.in|^t\.co$|twitter|tiktok|youtube|pinterest)/', $host ) ) {
		return 'Redes sociales';
	}
	if ( 'organic' === $med || preg_match( '/(^|\.)(google\.[a-z.]+|bing\.com|duckduckgo\.com|ecosia\.org|search\.yahoo\.com|search\.brave\.com)$/', $host ) ) {
		return 'Buscador orgánico';
	}
	if ( $host || $src ) {
		return 'Referencia';
	}
	return 'Directo';
}

/** El canal del lead es el último contacto que no fue directo; si no hay, el primero. */
function asa_attribution( array $first, array $last ) {
	$main    = 'Directo' !== asa_channel( $last ) ? $last : $first;
	$channel = asa_channel( $main );
	return array(
		'channel'       => $channel,
		'first_channel' => asa_channel( $first ),
		'source'        => $main['utm_source'] ?? ( $main['ref'] ?? '' ),
		'medium'        => $main['utm_medium'] ?? '',
		'campaign'      => $main['utm_campaign'] ?? '',
		'term'          => $main['utm_term'] ?? '',
		'gclid'         => $main['gclid'] ?? ( $first['gclid'] ?? '' ),
		'fbclid'        => $main['fbclid'] ?? ( $first['fbclid'] ?? '' ),
		'landing'       => $first['landing'] ?? '',
		'referrer'      => $main['ref'] ?? '',
		'first_touch'   => wp_json_encode( $first ),
		'last_touch'    => wp_json_encode( $last ),
	);
}
