<?php
defined( 'ABSPATH' ) || exit;

define( 'ASA_THEME_VERSION', '0.1.1' );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'script', 'style' ) );
	register_nav_menus( array( 'principal' => 'Menú principal' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'asa-font', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400..800&display=swap', array(), null );
	wp_enqueue_style( 'asa-theme', get_stylesheet_uri(), array(), ASA_THEME_VERSION );
	wp_enqueue_script( 'asa-theme', get_theme_file_uri( 'assets/theme.js' ), array(), ASA_THEME_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
} );

function asa_img( $file ) {
	return esc_url( get_theme_file_uri( 'assets/img/' . $file ) );
}

function asa_icon( $name ) {
	$paths = array(
		'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
		'list'   => '<path d="M9 5h11M9 12h11M9 19h11M4 5h.01M4 12h.01M4 19h.01"/>',
		'menu'   => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'chat'   => '<path d="M21 12a8.5 8.5 0 0 1-12.6 7.4L3 21l1.6-5.2A8.5 8.5 0 1 1 21 12Z"/>',
		'arrow'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
	);
	return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

/** Migas: [nombre, url] */
function asa_breadcrumbs() {
	if ( is_front_page() ) {
		return array();
	}
	$c       = array( array( 'Inicio', home_url( '/' ) ) );
	$catalog = array( 'Catálogo', home_url( '/catalogo/' ) );
	$term    = null;
	if ( is_singular( 'asa_producto' ) ) {
		$c[]   = $catalog;
		$terms = get_the_terms( get_queried_object_id(), 'asa_categoria' ) ?: array();
		usort( $terms, fn( $a, $b ) => $b->parent <=> $a->parent );
		$term = $terms[0] ?? null;
	} elseif ( is_tax( 'asa_categoria' ) ) {
		$c[]  = $catalog;
		$term = get_queried_object();
	} elseif ( is_post_type_archive( 'asa_producto' ) ) {
		$c[] = $catalog;
	}
	if ( $term ) {
		foreach ( array_reverse( get_ancestors( $term->term_id, 'asa_categoria' ) ) as $id ) {
			$a   = get_term( $id );
			$c[] = array( $a->name, get_term_link( $a ) );
		}
		$c[] = array( $term->name, get_term_link( $term ) );
	}
	if ( is_singular() ) {
		foreach ( array_reverse( get_post_ancestors( get_queried_object_id() ) ) as $id ) {
			$c[] = array( get_the_title( $id ), get_permalink( $id ) );
		}
		$c[] = array( get_the_title( get_queried_object_id() ), '' );
	}
	return $c;
}

function asa_crumbs_html() {
	$c = asa_breadcrumbs();
	if ( count( $c ) < 2 ) {
		return;
	}
	echo '<nav class="crumbs" aria-label="Migas de pan">';
	foreach ( $c as $i => $x ) {
		echo $i ? '<span>/</span>' : '';
		echo $x[1] && $i < count( $c ) - 1 ? '<a href="' . esc_url( $x[1] ) . '">' . esc_html( $x[0] ) . '</a>' : '<b>' . esc_html( $x[0] ) . '</b>';
	}
	echo '</nav>';
}

/** Categorías principales con una imagen representativa (en caché 12 h). */
function asa_categories() {
	$cats = get_transient( 'asa_cats' );
	if ( false !== $cats ) {
		return $cats;
	}
	$cats  = array();
	$terms = get_terms( array( 'taxonomy' => 'asa_categoria', 'parent' => 0, 'orderby' => 'name' ) );
	foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
		$ids    = get_posts( array( 'post_type' => 'asa_producto', 'posts_per_page' => 1, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'asa_categoria', 'terms' => $t->term_id ) ), 'meta_key' => '_asa_stock_total', 'orderby' => 'meta_value_num', 'order' => 'DESC' ) );
		$cats[] = array( 'name' => $t->name, 'url' => get_term_link( $t ), 'count' => $t->count, 'img' => $ids ? asa_image( $ids[0] ) : '' );
	}
	if ( $cats ) {
		set_transient( 'asa_cats', $cats, 12 * HOUR_IN_SECONDS );
	}
	return $cats;
}

function asa_cat_grid( $limit = 0 ) {
	$cats = asa_categories();
	echo '<div class="cats">';
	foreach ( $limit ? array_slice( $cats, 0, $limit ) : $cats as $c ) {
		echo '<a href="' . esc_url( $c['url'] ) . '">' . ( $c['img'] ? '<img src="' . esc_url( $c['img'] ) . '" alt="" loading="lazy" width="200" height="200">' : '' ) . '<span>' . esc_html( $c['name'] ) . '</span></a>';
	}
	echo '</div>';
}

const ASA_CLIENTS = array(
	'evercast' => 'Evercast', 'universidad-de-guanajuato' => 'Universidad de Guanajuato', 'general-motors' => 'General Motors',
	'grupo-mexico' => 'Grupo México', 'harting' => 'Harting', 'graham-packaging' => 'Graham Packaging', 'hirotec' => 'Hirotec',
	'draxton' => 'Draxton', 'martinrea' => 'Martinrea',
);

function asa_clients() {
	echo '<ul class="clients">';
	foreach ( ASA_CLIENTS as $file => $name ) {
		echo '<li><img src="' . asa_img( "clientes/$file.png" ) . '" alt="' . esc_attr( $name ) . '" loading="lazy" width="180" height="90"></li>';
	}
	echo '</ul>';
}

function asa_faq_html() {
	if ( ! function_exists( 'asa_faqs' ) ) {
		return;
	}
	echo '<div class="faq">';
	foreach ( asa_faqs() as $q => $a ) {
		echo '<details><summary>' . esc_html( $q ) . '</summary><p>' . esc_html( $a ) . '</p></details>';
	}
	echo '</div>';
}

add_shortcode( 'asa_faq', function () {
	ob_start();
	asa_faq_html();
	return ob_get_clean();
} );
add_shortcode( 'asa_categorias', function () {
	ob_start();
	asa_cat_grid();
	return ob_get_clean();
} );
add_shortcode( 'asa_clientes', function () {
	ob_start();
	asa_clients();
	return ob_get_clean();
} );
