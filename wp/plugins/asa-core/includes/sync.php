<?php
/** Sincronización de catálogo y existencias. */
defined( 'ABSPATH' ) || exit;

add_action( 'asa_sync_catalog', fn() => asa_sync_catalog() );
add_action( 'asa_sync_stock', fn() => asa_sync_stock() );

/**
 * La consulta vive en un archivo con un campo separado por espacios: la API devuelve
 * campos vacíos si el texto de la consulta va compactado, aunque sea GraphQL válido.
 */
function asa_catalog_query() {
	return file_get_contents( ASA_CORE_DIR . 'queries/catalog.graphql' );
}

function asa_sync_log( $key, $info ) {
	$log         = (array) get_option( 'asa_sync_status', array() );
	$log[ $key ] = array( 'at' => current_time( 'mysql' ), 'info' => $info );
	update_option( 'asa_sync_status', $log, false );
}

function asa_term( $name, $taxonomy, $parent = 0, $slug = '' ) {
	static $cache = array();
	$key = "$taxonomy|$parent|$name";
	if ( ! isset( $cache[ $key ] ) ) {
		$found = term_exists( $name, $taxonomy, $parent );
		if ( ! $found ) {
			$found = wp_insert_term( $name, $taxonomy, array_filter( array( 'parent' => $parent, 'slug' => $slug ) ) );
		}
		$cache[ $key ] = is_wp_error( $found ) ? 0 : (int) $found['term_id'];
	}
	return $cache[ $key ];
}

/** Convierte el texto libre de técnicas de la API en términos normalizados. */
function asa_techniques( $raw ) {
	$known = array(
		'serigraf' => 'Serigrafía', 'bordad' => 'Bordado', 'láser' => 'Grabado láser', 'laser' => 'Grabado láser',
		'sublima' => 'Sublimación', 'tampograf' => 'Tampografía', 'dtf' => 'DTF', 'termograb' => 'Termograbado',
	);
	$out = array();
	$low = mb_strtolower( (string) $raw );
	foreach ( $known as $needle => $name ) {
		if ( str_contains( $low, $needle ) ) {
			$out[ $name ] = $name;
		}
	}
	return array_values( $out );
}

function asa_import_product( array $p, array $existing ) {
	$m   = $p['productModel'];
	$sku = trim( (string) $m['sku'] );
	$fam = $m['filters']['familyFilterable'] ?? '';
	if ( ! $sku || ! $fam || empty( $m['isEnabledProductModelMx'] ) || str_contains( (string) $m['nameProductModel'], 'ERRONEO' ) ) {
		return null;
	}
	$variants = array();
	$is_new   = false;
	$is_mx    = false;
	foreach ( (array) $p['variants'] as $v ) {
		if ( empty( $v['availability']['isEnabledVariantMx'] ) ) {
			continue;
		}
		$is_new     = $is_new || ! empty( $v['commercialAttributes']['isBrandNewMx'] );
		$is_mx      = $is_mx || ! empty( $v['commercialAttributes']['isMadeInMexicoMx'] );
		$variants[] = array(
			'sku'    => trim( (string) $v['sku'] ),
			'color'  => $v['color'] ? asa_sentence( $v['color'] ) : '',
			'size'   => (string) $v['size'],
			'images' => array_values( array_filter( (array) ( $v['mediaAssets']['variantImages'] ?? array() ) ) ),
			// Precio de referencia solo para el asesor; nunca se muestra en la web.
			'price'  => (string) ( $v['pricing']['priceMx'][0]['amount'] ?? '' ),
		);
	}
	if ( ! $variants ) {
		return null;
	}
	$main = $m['media']['mainImagesMx'][0] ?? ( $m['media']['mainImages'][0] ?? ( $variants[0]['images'][0] ?? '' ) );
	$data = array(
		'brand'     => (string) $m['brand'],
		'material'  => (string) ( $m['features']['material'] ?? '' ),
		'measure'   => (string) ( $m['features']['measure'] ?: ( $m['features']['sizeMeasures'] ?? '' ) ),
		'capacity'  => (string) $m['capacity'],
		'print'     => (string) ( $m['printing']['printTechnique'] ?? '' ),
		'area'      => (string) ( $m['printing']['printArea'] ?? '' ),
		'box'       => (int) ( $m['package']['units']['piecesPerBox'] ?? 0 ),
		'eco'       => ! empty( $m['features']['isEcological'] ),
		'variants'  => $variants,
	);
	$post = array(
		'post_type'    => 'asa_producto',
		'post_status'  => 'publish',
		'post_title'   => asa_product_name( $m['nameProductModel'] ),
		'post_content' => sanitize_textarea_field( (string) $m['descriptionMx'] ),
		'post_excerpt' => trim( 'Código ' . trim( $sku, '- ' ) . ( $data['material'] ? '. Material: ' . sanitize_text_field( $data['material'] ) : '' ) ),
		'menu_order'   => $is_new ? 0 : 10,
	);
	$hash = md5( wp_json_encode( array( $post, $data, $main, $m['filters'], $is_mx ) ) );
	$old  = $existing[ $sku ] ?? null;
	if ( $old && $old['hash'] === $hash && 'publish' === $old['status'] ) {
		return 'igual';
	}
	if ( $old ) {
		$post['ID'] = $old['id'];
		// Un texto propio de ASA no se sobrescribe.
		if ( get_post_meta( $old['id'], '_asa_texto_propio', true ) ) {
			unset( $post['post_title'], $post['post_content'] );
		}
		$id = wp_update_post( wp_slash( $post ), true );
	} else {
		$post['post_name'] = sanitize_title( $post['post_title'] . '-' . trim( $sku, '- ' ) );
		$id                = wp_insert_post( wp_slash( $post ), true );
	}
	if ( is_wp_error( $id ) ) {
		return 'error';
	}
	update_post_meta( $id, '_asa_sku', wp_slash( $sku ) );
	update_post_meta( $id, '_asa_data', wp_slash( $data ) );
	update_post_meta( $id, '_asa_img', esc_url_raw( $main ) );
	update_post_meta( $id, '_asa_new', $is_new ? '1' : '' );
	update_post_meta( $id, '_asa_mx', $is_mx ? '1' : '' );
	if ( ! $old ) {
		update_post_meta( $id, '_asa_stock_total', 0 );
	}
	$family = asa_family_name( $fam );
	$parent = asa_term( $family, 'asa_categoria', 0, sanitize_title( $family ) );
	$terms  = array( $parent );
	$sub    = $m['filters']['subFamilyFilterable'] ?? '';
	if ( $sub && $parent ) {
		$subname = asa_sentence( str_replace( '_', ' ', $sub ) );
		if ( sanitize_title( $subname ) !== sanitize_title( $family ) ) {
			$terms[] = asa_term( $subname, 'asa_categoria', $parent );
		}
	}
	wp_set_object_terms( $id, array_values( array_filter( $terms ) ), 'asa_categoria' );
	wp_set_object_terms( $id, asa_techniques( $data['print'] ), 'asa_tecnica' );
	update_post_meta( $id, '_asa_hash', $hash );
	return $old ? 'actualizado' : 'nuevo';
}

function asa_existing_products() {
	global $wpdb;
	$rows = $wpdb->get_results(
		"SELECT p.ID id, p.post_status status, s.meta_value sku, h.meta_value hash FROM {$wpdb->posts} p
		JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_asa_sku'
		LEFT JOIN {$wpdb->postmeta} h ON h.post_id=p.ID AND h.meta_key='_asa_hash'
		WHERE p.post_type='asa_producto'", ARRAY_A );
	return array_column( $rows, null, 'sku' );
}

/**
 * La API ha llegado a responder sin código ni descripción aunque se pidan.
 * Una página así se rechaza entera para no vaciar ni retirar productos.
 */
function asa_page_is_degraded( array $items ) {
	$ok = count( array_filter( $items, fn( $p ) => ! empty( $p['productModel']['sku'] ) && null !== ( $p['productModel']['isEnabledProductModelMx'] ?? null ) ) );
	return $ok < count( $items ) * 0.9;
}

/** @param string $file Copia local en JSON (lista de productos) en lugar de la API. */
function asa_sync_catalog( $file = '' ) {
	if ( get_transient( 'asa_sync_lock' ) ) {
		return;
	}
	set_transient( 'asa_sync_lock', 1, HOUR_IN_SECONDS );
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 0 );
	}
	$existing = asa_existing_products();
	$seen     = array();
	$count    = array( 'nuevo' => 0, 'actualizado' => 0, 'igual' => 0, 'error' => 0, 'retirado' => 0 );
	$page     = 1;
	$complete = false;
	wp_defer_term_counting( true );
	$chunks   = array();
	if ( $file ) {
		$all    = json_decode( (string) file_get_contents( $file ), true );
		$chunks = array_chunk( is_array( $all ) ? $all : array(), 100 );
	}
	do {
		if ( $file ) {
			$data = array( 'distribuitorProductCatalog' => array( 'data' => $chunks[ $page - 1 ] ?? array(), 'totalPages' => count( $chunks ), 'hasNextPage' => $page < count( $chunks ) ) );
		} else {
			$data = asa_api_query( asa_catalog_query(), array( 'page' => $page ) );
		}
		if ( is_wp_error( $data ) ) {
			asa_sync_log( 'catalogo', "Interrumpido en la página $page: " . $data->get_error_message() );
			break;
		}
		$res = $data['distribuitorProductCatalog'] ?? null;
		if ( empty( $res['data'] ) ) {
			break;
		}
		if ( asa_page_is_degraded( $res['data'] ) ) {
			asa_sync_log( 'catalogo', "Respuesta incompleta de la API en la página $page (sin código o estado). No se modificó nada a partir de ahí." );
			break;
		}
		foreach ( $res['data'] as $p ) {
			$result = asa_import_product( $p, $existing );
			if ( $result ) {
				++$count[ $result ];
				$seen[ trim( (string) $p['productModel']['sku'] ) ] = true;
			}
		}
		if ( defined( 'WP_CLI' ) ) {
			WP_CLI::log( "Página $page de {$res['totalPages']}" );
			wp_cache_flush_runtime();
		}
		$complete = empty( $res['hasNextPage'] );
		++$page;
	} while ( ! $complete );

	// Solo se retiran productos si la descarga fue completa y razonable.
	if ( $complete && count( $seen ) >= count( $existing ) / 2 ) {
		foreach ( $existing as $sku => $row ) {
			if ( ! isset( $seen[ $sku ] ) && 'publish' === $row['status'] ) {
				wp_update_post( array( 'ID' => $row['id'], 'post_status' => 'draft' ) );
				++$count['retirado'];
			}
		}
	}
	wp_defer_term_counting( false );
	delete_transient( 'asa_sync_lock' );
	if ( $complete && ! ( $count['nuevo'] + $count['actualizado'] + $count['igual'] ) ) {
		asa_sync_log( 'catalogo', 'Descarga sin productos utilizables; revisar la respuesta de la API.' );
	} elseif ( $complete ) {
		asa_sync_log( 'catalogo', ( $file ? 'Desde copia local: ' : '' ) . urldecode( http_build_query( $count, '', ', ' ) ) );
		delete_transient( 'asa_cats' );
	}
	return $count;
}

function asa_sync_stock( $file = '' ) {
	$data = $file
		? array( 'distributorStockCatalog' => json_decode( (string) file_get_contents( $file ), true ) )
		: asa_api_query( "{\n distributorStockCatalog { sku currentStock incomingTransit arrivalDate }\n}" );
	if ( is_wp_error( $data ) ) {
		asa_sync_log( 'existencias', 'Error: ' . $data->get_error_message() );
		return;
	}
	$rows = $data['distributorStockCatalog'] ?? array();
	$usable = count( array_filter( (array) $rows, fn( $r ) => ! empty( $r['sku'] ) && null !== ( $r['currentStock'] ?? null ) ) );
	if ( ! $rows || $usable < count( $rows ) * 0.9 ) {
		asa_sync_log( 'existencias', 'La API no devolvió filas completas; se conservan los datos anteriores.' );
		return;
	}
	$stock = array();
	foreach ( $rows as $r ) {
		$k = trim( (string) $r['sku'] );
		$stock[ $k ]          ??= array( 'qty' => 0, 'transit' => 0, 'date' => '' );
		$stock[ $k ]['qty']     += max( 0, (int) $r['currentStock'] );
		$stock[ $k ]['transit'] += max( 0, (int) $r['incomingTransit'] );
		if ( $r['arrivalDate'] ) {
			$stock[ $k ]['date'] = sanitize_text_field( $r['arrivalDate'] );
		}
	}
	$n = 0;
	foreach ( asa_existing_products() as $row ) {
		$mine  = array();
		$total = 0;
		foreach ( asa_data( $row['id'] )['variants'] ?? array() as $v ) {
			if ( isset( $stock[ $v['sku'] ] ) ) {
				$mine[ $v['sku'] ] = $stock[ $v['sku'] ];
				$total            += $stock[ $v['sku'] ]['qty'];
			}
		}
		update_post_meta( $row['id'], '_asa_stock', $mine );
		update_post_meta( $row['id'], '_asa_stock_total', $total );
		++$n;
	}
	update_option( 'asa_stock_at', current_time( 'mysql' ), false );
	asa_sync_log( 'existencias', count( $rows ) . " filas aplicadas a $n productos" );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	// wp asa import <catalogo.json> [existencias.json]
	WP_CLI::add_command( 'asa import', function ( $args ) {
		asa_sync_catalog( $args[0] );
		if ( ! empty( $args[1] ) ) {
			asa_sync_stock( $args[1] );
		}
		WP_CLI::log( print_r( get_option( 'asa_sync_status' ), true ) );
	} );
	WP_CLI::add_command( 'asa sync', function ( $args ) {
		$what = $args[0] ?? 'all';
		if ( 'stock' !== $what ) {
			asa_sync_catalog();
		}
		if ( 'catalog' !== $what ) {
			asa_sync_stock();
		}
		WP_CLI::log( print_r( get_option( 'asa_sync_status' ), true ) );
	} );
}
