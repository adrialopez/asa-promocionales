<?php
/** Tipos de contenido del catálogo y utilidades de presentación. */
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'asa_register_catalog' );

function asa_register_catalog() {
	register_taxonomy( 'asa_categoria', 'asa_producto', array(
		'labels'            => array( 'name' => 'Categorías del catálogo', 'singular_name' => 'Categoría' ),
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'catalogo', 'hierarchical' => true, 'with_front' => false ),
	) );
	register_taxonomy( 'asa_tecnica', 'asa_producto', array(
		'labels'             => array( 'name' => 'Técnicas', 'singular_name' => 'Técnica' ),
		'public'             => false,
		'show_ui'            => true,
		'publicly_queryable' => true,
		'rewrite'            => false,
		'query_var'          => 'tecnica',
	) );
	register_post_type( 'asa_producto', array(
		'labels'       => array( 'name' => 'Productos', 'singular_name' => 'Producto' ),
		'public'       => true,
		'menu_icon'    => 'dashicons-products',
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
		'has_archive'  => 'catalogo',
		'rewrite'      => array( 'slug' => 'producto', 'with_front' => false ),
		'show_in_rest' => true,
	) );
	// Sin esta regla, /catalogo/page/2/ se interpretaría como una categoría.
	add_rewrite_rule( '^catalogo/page/([0-9]+)/?$', 'index.php?post_type=asa_producto&paged=$matches[1]', 'top' );
}

/** Listados: 24 por página, con filtros por técnica, origen y novedad. La búsqueda también mira el código. */
add_action( 'pre_get_posts', function ( WP_Query $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	// La búsqueda del sitio es la búsqueda del catálogo.
	if ( $q->is_search() ) {
		$q->set( 'post_type', 'asa_producto' );
	}
	if ( ! ( $q->is_post_type_archive( 'asa_producto' ) || $q->is_tax( 'asa_categoria' ) || ( $q->is_search() && 'asa_producto' === $q->get( 'post_type' ) ) ) ) {
		return;
	}
	$q->set( 'posts_per_page', 24 );
	if ( ! $q->is_search() ) {
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
	$meta = array();
	if ( ! empty( $_GET['mx'] ) ) {
		$meta[] = array( 'key' => '_asa_mx', 'value' => '1' );
	}
	if ( ! empty( $_GET['stock'] ) ) {
		$meta[] = array( 'key' => '_asa_stock_total', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC' );
	}
	if ( $meta ) {
		$q->set( 'meta_query', $meta );
	}
} );

/** Nombres oficiales de las familias de la API frente a las categorías del brief. */
function asa_family_name( $key ) {
	$map = array(
		'AGENDAS_ZEGNO' => 'Agendas', 'ANTIESTRES' => 'Antiestrés', 'ARTICULOS_PARA_VIAJE' => 'Artículos de viaje',
		'BAR' => 'Bar', 'BEBIDAS' => 'Bebidas', 'BELLEZA' => 'Belleza', 'BOLSAS' => 'Bolsas',
		'COMPLEMENTOS' => 'Complementos', 'DEPORTES' => 'Deportes', 'ENTRETENIMIENTO' => 'Entretenimiento',
		'ESCRITURA' => 'Escritura', 'HERRAMIENTAS' => 'Herramientas',
		'HIELERAS_LONCHERAS_Y_PORTAVIANDAS' => 'Hieleras, loncheras y portaviandas', 'HOGAR' => 'Hogar',
		'LIBRETAS_Y_CARPETAS' => 'Libretas y carpetas', 'LLAVEROS' => 'Llaveros', 'MALETAS' => 'Maletas',
		'MOCHILAS' => 'Mochilas', 'NINOS' => 'Niños', 'OFICINA' => 'Oficina',
		'PARAGUAS_E_IMPERMEABLES' => 'Paraguas e impermeables', 'PORTAFOLIOS' => 'Portafolios', 'SALUD' => 'Salud',
		'TECNOLOGIA' => 'Tecnología', 'TEXTILES' => 'Textiles',
	);
	return $map[ $key ] ?? asa_sentence( str_replace( '_', ' ', $key ) );
}

/** Frase con mayúscula inicial y acentos habituales a partir de claves en mayúsculas. */
function asa_sentence( $text ) {
	$t     = mb_strtolower( trim( $text ) );
	$fixes = array(
		'articulos' => 'artículos', 'accesorios' => 'accesorios', 'acc' => 'accesorios', 'automovil' => 'automóvil',
		'bateria' => 'batería', 'boligrafos' => 'bolígrafos', 'cafe' => 'café', 'computo' => 'cómputo',
		'decoracion' => 'decoración', 'electronicos' => 'electrónicos', 'joyeria' => 'joyería',
		'jardineria' => 'jardinería', 'lamparas' => 'lámparas', 'metalico' => 'metálico', 'metalicos' => 'metálicos',
		'plastico' => 'plástico', 'portagafete' => 'portagafetes', 'audifonos' => 'audífonos', 'ninos' => 'niños',
	);
	$words = array_map( fn( $w ) => $fixes[ $w ] ?? $w, preg_split( '/\s+/', $t ) );
	$t     = implode( ' ', $words );
	return mb_strtoupper( mb_substr( $t, 0, 1 ) ) . mb_substr( $t, 1 );
}

/** Nombre de producto legible a partir del nombre en mayúsculas de la API. */
function asa_product_name( $name ) {
	$name = preg_replace( '/\s+/', ' ', trim( $name ) );
	if ( mb_strtoupper( $name ) !== $name ) {
		return $name;
	}
	$small = array( 'de', 'del', 'para', 'con', 'y', 'e', 'en', 'la', 'el', 'los', 'las', 'a', 'o', 'sin' );
	$keep  = array( 'USB', 'LED', 'RPET', 'PET', 'BBQ', 'RFID', 'XL', 'TV', 'PD', 'TWS' );
	$out   = array();
	foreach ( explode( ' ', $name ) as $i => $w ) {
		$low = mb_strtolower( $w );
		if ( in_array( $w, $keep, true ) || preg_match( '/\d/', $w ) ) {
			$out[] = $w;
		} elseif ( $i && in_array( $low, $small, true ) ) {
			$out[] = $low;
		} else {
			$out[] = mb_convert_case( $low, MB_CASE_TITLE );
		}
	}
	return implode( ' ', $out );
}

function asa_data( $post_id ) {
	$d = get_post_meta( $post_id, '_asa_data', true );
	return is_array( $d ) ? $d : array();
}

function asa_image( $post_id ) {
	if ( has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail_url( $post_id, 'large' );
	}
	return (string) get_post_meta( $post_id, '_asa_img', true );
}

function asa_product_card( $post_id ) {
	$data  = asa_data( $post_id );
	$url   = get_permalink( $post_id );
	$img   = asa_image( $post_id );
	$title = get_the_title( $post_id );
	$sku   = (string) get_post_meta( $post_id, '_asa_sku', true );
	$n     = count( $data['variants'] ?? array() );
	ob_start();
	?>
	<article class="card-p">
		<a class="card-p__img" href="<?php echo esc_url( $url ); ?>">
			<?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" width="400" height="400"><?php endif; ?>
			<?php if ( get_post_meta( $post_id, '_asa_new', true ) ) : ?><span class="tag">Nuevo</span><?php endif; ?>
		</a>
		<div class="card-p__body">
			<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></h3>
			<p><?php echo esc_html( trim( $sku, '- ' ) ); ?><?php echo $n > 1 ? esc_html( " · $n opciones" ) : ''; ?></p>
			<button type="button" class="btn btn--outline btn--sm" data-add="<?php echo (int) $post_id; ?>" data-name="<?php echo esc_attr( $title ); ?>" data-img="<?php echo esc_url( $img ); ?>">Agregar a cotización</button>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

/** [asa_productos categoria="bebidas" tecnica="bordado" n="8"] */
add_shortcode( 'asa_productos', function ( $atts ) {
	$a    = shortcode_atts( array( 'categoria' => '', 'tecnica' => '', 'n' => 8 ), $atts );
	$args = array(
		'post_type' => 'asa_producto', 'posts_per_page' => min( 24, (int) $a['n'] ), 'fields' => 'ids',
		'orderby' => array( 'menu_order' => 'ASC', 'date' => 'DESC' ), 'no_found_rows' => true,
	);
	foreach ( array( 'categoria' => 'asa_categoria', 'tecnica' => 'asa_tecnica' ) as $att => $tax ) {
		if ( $a[ $att ] ) {
			$args['tax_query'][] = array( 'taxonomy' => $tax, 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $a[ $att ] ) ) );
		}
	}
	$ids = get_posts( $args );
	return $ids ? '<div class="grid-p">' . implode( '', array_map( 'asa_product_card', $ids ) ) . '</div>' : '';
} );
