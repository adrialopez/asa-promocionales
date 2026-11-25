<?php
get_header();
the_post();
$id    = get_the_ID();
$d     = asa_data( $id );
$sku   = trim( (string) get_post_meta( $id, '_asa_sku', true ), '- ' );
$stock = (array) get_post_meta( $id, '_asa_stock', true );
$vars  = $d['variants'] ?? array();
$main  = asa_image( $id );
$images = array_values( array_unique( array_filter( array_merge( array( $main ), ...array_values( array_map( fn( $v ) => $v['images'], $vars ) ) ) ) ) );
$specs = array_filter( array(
	'Código' => $sku, 'Material' => $d['material'] ?? '', 'Medidas' => $d['measure'] ?? '', 'Capacidad' => $d['capacity'] ?? '',
	'Técnica de impresión' => $d['print'] ?? '', 'Área de impresión' => $d['area'] ?? '', 'Piezas por caja' => ( $d['box'] ?? 0 ) ?: '',
) );
?>
<div class="wrap page" data-product>
	<?php asa_crumbs_html(); ?>
	<div class="product">
		<div class="gallery">
			<img id="photo" src="<?php echo esc_url( $main ); ?>" alt="<?php the_title_attribute(); ?>" width="800" height="800">
			<?php if ( count( $images ) > 1 ) : ?>
				<div class="thumbs"><?php foreach ( array_slice( $images, 0, 12 ) as $n => $src ) { echo '<button type="button" data-photo="' . esc_url( $src ) . '" aria-label="Ver foto ' . (int) ( $n + 1 ) . '"><img src="' . esc_url( $src ) . '" alt="" loading="lazy" width="80" height="80"></button>'; } ?></div>
			<?php endif; ?>
		</div>
		<div class="info">
			<h1><?php the_title(); ?></h1>
			<div class="lead"><?php the_content(); ?></div>
			<?php if ( $vars ) : ?>
				<h2>Opciones y existencias</h2>
				<div class="variants">
					<?php
					foreach ( $vars as $n => $v ) {
						$label = trim( implode( ' ', array_filter( array( $v['color'], $v['size'] ) ) ) ) ?: $v['sku'];
						$s     = $stock[ $v['sku'] ] ?? null;
						$text  = null === $s ? 'Consultar' : ( $s['qty'] > 0 ? number_format( $s['qty'] ) . ' disponibles' : ( $s['transit'] > 0 ? 'En camino' . ( $s['date'] ? ' ' . $s['date'] : '' ) : 'Consultar' ) );
						echo '<button type="button" data-variant data-label="' . esc_attr( $label ) . '" data-photo="' . esc_url( $v['images'][0] ?? $main ) . '"' . ( 0 === $n ? ' class="is-on" aria-pressed="true"' : ' aria-pressed="false"' ) . '><b>' . esc_html( $label ) . '</b><small>' . esc_html( $text ) . '</small></button>';
					}
					?>
				</div>
				<p class="note">Existencias de referencia<?php $at = get_option( 'asa_stock_at' ); echo $at ? esc_html( ' al ' . mysql2date( 'd/m/Y H:i', $at ) ) : ''; ?>. Se confirman en la cotización.</p>
			<?php endif; ?>
			<div class="buy">
				<label>Cantidad<input type="number" min="1" value="100" data-qty></label>
				<button type="button" class="btn btn--lg" data-add="<?php echo (int) $id; ?>" data-name="<?php the_title_attribute(); ?>" data-img="<?php echo esc_url( $main ); ?>">Agregar a mi cotización</button>
				<a class="btn btn--wa btn--lg" href="#" data-wa data-product="<?php echo esc_attr( get_the_title() . " ($sku)" ); ?>" data-product-id="<?php echo (int) $id; ?>"><?php echo asa_icon( 'chat' ); ?> Preguntar por WhatsApp</a>
			</div>
			<table class="specs"><?php foreach ( $specs as $k => $v ) { echo '<tr><th>' . esc_html( $k ) . '</th><td>' . esc_html( $v ) . '</td></tr>'; } ?></table>
		</div>
	</div>
	<?php
	$terms = get_the_terms( $id, 'asa_categoria' ) ?: array();
	usort( $terms, fn( $a, $b ) => $b->parent <=> $a->parent );
	if ( $terms ) {
		$related = get_posts( array( 'post_type' => 'asa_producto', 'posts_per_page' => 4, 'post__not_in' => array( $id ), 'fields' => 'ids', 'orderby' => 'rand', 'tax_query' => array( array( 'taxonomy' => 'asa_categoria', 'terms' => $terms[0]->term_id ) ) ) );
		if ( $related ) {
			echo '<h2>También te puede interesar</h2><div class="grid-p">' . implode( '', array_map( 'asa_product_card', $related ) ) . '</div>';
		}
	}
	?>
</div>
<?php get_footer(); ?>
