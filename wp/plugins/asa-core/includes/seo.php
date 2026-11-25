<?php
/** Títulos, descripciones, datos estructurados y llms.txt. */
defined( 'ABSPATH' ) || exit;

function asa_seo_title() {
	if ( is_front_page() ) {
		return 'Artículos promocionales personalizados para empresas en México';
	}
	if ( is_tax( 'asa_categoria' ) ) {
		return single_term_title( '', false ) . ' con logo para empresas';
	}
	if ( is_post_type_archive( 'asa_producto' ) && ! is_search() ) {
		return 'Catálogo de artículos promocionales';
	}
	if ( is_singular( 'asa_producto' ) ) {
		return get_the_title() . ' con logo para empresas';
	}
	return is_singular() ? (string) get_post_meta( get_queried_object_id(), '_asa_title', true ) : '';
}

add_filter( 'document_title_parts', function ( $parts ) {
	$t = asa_seo_title();
	if ( $t ) {
		$parts['title'] = $t;
		$parts['site']  = 'ASA Promocionales';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

function asa_seo_description() {
	if ( is_front_page() ) {
		return 'Artículos promocionales, regalos corporativos y chamarras personalizadas para empresas. Más de 35 años de experiencia y envíos a todo México. Cotiza hoy.';
	}
	if ( is_tax( 'asa_categoria' ) ) {
		$term = get_queried_object();
		return $term->description ?: $term->name . ' promocionales con el logotipo de tu empresa. Envíos a todo México. Solicita tu cotización a ASA Promocionales.';
	}
	if ( is_post_type_archive( 'asa_producto' ) ) {
		return 'Explora el catálogo de artículos promocionales de ASA por categoría, arma tu lista y pide cotización. Envíos a todo México.';
	}
	if ( is_singular() ) {
		$custom = get_post_meta( get_queried_object_id(), '_asa_desc', true );
		if ( $custom ) {
			return $custom;
		}
		$text = wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', get_queried_object_id() ) ) );
		return trim( mb_substr( preg_replace( '/\s+/', ' ', $text ), 0, 155 ) );
	}
	return '';
}

/** Preguntas frecuentes: se muestran en la página y se describen como datos estructurados. */
function asa_faqs() {
	return array(
		'¿Qué vende ASA Promocionales?' => 'Artículos promocionales personalizados, regalos corporativos y ejecutivos, productos para eventos, kits de bienvenida y prendas textiles, con productos de catálogo y líneas de fabricación propia como las chamarras.',
		'¿Hacen envíos a todo México?' => 'Sí. Enviamos a cualquier parte de México.',
		'¿Qué técnicas de personalización manejan?' => 'Serigrafía, bordado, grabado láser, sublimación y tampografía. La técnica adecuada depende del producto y del material.',
		'¿Cómo pido una cotización?' => 'Agrega productos a tu lista de cotización o describe lo que necesitas, deja los datos de tu empresa y recibirás un folio. También puedes escribirnos por WhatsApp.',
		'¿Ofrecen crédito a empresas?' => 'Contamos con líneas de crédito para clientes, sujetas a autorización y condiciones comerciales.',
		'¿Fabrican productos propios?' => 'Sí. Desarrollamos productos de fabricación propia en México, como chamarras, cuidando materiales, confección y acabados.',
	);
}

add_action( 'wp_head', function () {
	$desc  = asa_seo_description();
	$title = wp_get_document_title();
	$url   = is_singular() || is_front_page() ? ( is_front_page() ? home_url( '/' ) : get_permalink() ) : ( is_tax() ? get_term_link( get_queried_object() ) : ( is_post_type_archive() ? get_post_type_archive_link( 'asa_producto' ) : '' ) );
	if ( $url && ! is_singular() ) {
		$paged = (int) get_query_var( 'paged' );
		echo '<link rel="canonical" href="' . esc_url( $paged > 1 ? trailingslashit( $url ) . "page/$paged/" : $url ) . "\">\n";
	}
	$img = is_singular( 'asa_producto' ) ? asa_image( get_the_ID() ) : get_theme_file_uri( 'assets/img/bordado-maquina.jpg' );
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . "\">\n";
	}
	foreach ( array( 'og:type' => 'website', 'og:locale' => 'es_MX', 'og:site_name' => 'ASA Promocionales', 'og:title' => $title, 'og:description' => $desc, 'og:url' => $url, 'og:image' => $img ) as $p => $c ) {
		if ( $c ) {
			echo '<meta property="' . esc_attr( $p ) . '" content="' . esc_attr( $c ) . "\">\n";
		}
	}

	$home  = home_url( '/' );
	$graph = array( array(
		'@type' => 'Organization', '@id' => $home . '#org', 'name' => 'ASA Promocionales', 'url' => $home,
		'logo' => get_theme_file_uri( 'assets/img/logo.png' ), 'slogan' => 'Tu marca en todas partes',
		'description' => 'Artículos promocionales personalizados para empresas, con más de 35 años de experiencia y envíos a todo México.',
		'areaServed' => array( '@type' => 'Country', 'name' => 'México' ),
		'sameAs' => array( 'https://www.facebook.com/ASApromocionales', 'https://www.instagram.com/asa.promocionales', 'https://www.linkedin.com/company/asa-promocionales' ),
	) );
	$crumbs = function_exists( 'asa_breadcrumbs' ) ? asa_breadcrumbs() : array();
	if ( count( $crumbs ) > 1 ) {
		$list = array();
		foreach ( $crumbs as $i => $c ) {
			$list[] = array_filter( array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1] ) );
		}
		$graph[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $list );
	}
	if ( is_singular( 'asa_producto' ) ) {
		$d       = asa_data( get_the_ID() );
		$graph[] = array_filter( array(
			'@type' => 'Product', 'name' => get_the_title(), 'sku' => trim( (string) get_post_meta( get_the_ID(), '_asa_sku', true ), '- ' ),
			'description' => wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) ), 'image' => $img,
			'material' => $d['material'] ?? '', 'color' => implode( ', ', array_filter( array_unique( array_column( $d['variants'] ?? array(), 'color' ) ) ) ),
		) );
	}
	if ( is_front_page() || is_page( 'preguntas-frecuentes' ) ) {
		$qa = array();
		foreach ( asa_faqs() as $q => $a ) {
			$qa[] = array( '@type' => 'Question', 'name' => $q, 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $a ) );
		}
		$graph[] = array( '@type' => 'FAQPage', 'mainEntity' => $qa );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . "</script>\n";
}, 5 );

add_action( 'init', function () {
	if ( '/llms.txt' !== strtok( $_SERVER['REQUEST_URI'] ?? '', '?' ) ) {
		return;
	}
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo "# ASA Promocionales\n\n> Artículos promocionales personalizados, regalos corporativos y chamarras de fabricación propia para empresas. Más de 35 años de experiencia. Envíos a todo México.\n\n## Preguntas frecuentes\n";
	foreach ( asa_faqs() as $q => $a ) {
		echo "\n### $q\n$a\n";
	}
	echo "\n## Categorías del catálogo\n";
	foreach ( get_terms( array( 'taxonomy' => 'asa_categoria', 'parent' => 0 ) ) as $t ) {
		echo "- [{$t->name}](" . get_term_link( $t ) . ")\n";
	}
	echo "\n## Páginas\n";
	foreach ( get_pages( array( 'sort_column' => 'menu_order' ) ) as $p ) {
		echo '- [' . $p->post_title . '](' . get_permalink( $p ) . ")\n";
	}
	exit;
}, 20 );

/** El sitemap no debe exponer usuarios. */
add_filter( 'wp_sitemaps_add_provider', fn( $provider, $name ) => 'users' === $name ? false : $provider, 10, 2 );
