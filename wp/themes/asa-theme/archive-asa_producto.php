<?php
get_header();
$term   = is_tax( 'asa_categoria' ) ? get_queried_object() : null;
$search = is_search();
$title  = $search ? 'Resultados para “' . get_search_query( false ) . '”' : ( $term ? $term->name : 'Catálogo de artículos promocionales' );
?>
<div class="wrap page">
	<?php asa_crumbs_html(); ?>
	<h1><?php echo esc_html( $title ); ?></h1>
	<?php if ( $term && $term->description && ! is_paged() ) : ?><p class="lead"><?php echo esc_html( $term->description ); ?></p><?php endif; ?>

	<?php
	if ( ! $search && ! $term && ! is_paged() && empty( $_GET ) ) {
		asa_cat_grid();
		echo '<h2>Todos los productos</h2>';
	}
	if ( $term ) {
		$parent = $term->parent ?: $term->term_id;
		$subs   = get_terms( array( 'taxonomy' => 'asa_categoria', 'parent' => $parent ) );
		if ( $subs ) {
			echo '<div class="chips"><a href="' . esc_url( get_term_link( $parent, 'asa_categoria' ) ) . '"' . ( $parent === $term->term_id ? ' class="is-on"' : '' ) . '>Todo</a>';
			foreach ( $subs as $s ) {
				echo '<a href="' . esc_url( get_term_link( $s ) ) . '"' . ( $s->term_id === $term->term_id ? ' class="is-on"' : '' ) . '>' . esc_html( $s->name ) . '</a>';
			}
			echo '</div>';
		}
	}
	$get = fn( $k ) => sanitize_text_field( wp_unslash( $_GET[ $k ] ?? '' ) );
	?>
	<form class="filters" method="get">
		<?php if ( $search ) : ?><input type="hidden" name="s" value="<?php echo esc_attr( get_search_query( false ) ); ?>"><input type="hidden" name="post_type" value="asa_producto"><?php endif; ?>
		<label>Técnica <select name="tecnica"><option value="">Todas</option>
			<?php foreach ( get_terms( array( 'taxonomy' => 'asa_tecnica' ) ) as $t ) { echo '<option value="' . esc_attr( $t->slug ) . '"' . selected( $get( 'tecnica' ), $t->slug, false ) . '>' . esc_html( $t->name ) . '</option>'; } ?>
		</select></label>
		<label class="check"><input type="checkbox" name="stock" value="1" <?php checked( $get( 'stock' ), '1' ); ?>> Con existencias</label>
		<label class="check"><input type="checkbox" name="mx" value="1" <?php checked( $get( 'mx' ), '1' ); ?>> Hecho en México</label>
		<button class="btn btn--sm">Filtrar</button>
		<span><?php echo (int) $wp_query->found_posts; ?> productos</span>
	</form>

	<?php if ( have_posts() ) : ?>
		<div class="grid-p">
			<?php while ( have_posts() ) { the_post(); echo asa_product_card( get_the_ID() ); } ?>
		</div>
		<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => 'Anterior', 'next_text' => 'Siguiente' ) ); ?>
	<?php else : ?>
		<div class="empty"><p>No encontramos productos con esa búsqueda. Dinos qué necesitas y lo buscamos por ti.</p><a class="btn" href="<?php echo esc_url( home_url( '/cotizar/' ) ); ?>">Pedir cotización</a></div>
	<?php endif; ?>
</div>
<?php get_footer(); ?>
