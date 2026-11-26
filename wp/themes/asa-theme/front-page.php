<?php get_header(); ?>

<section class="hero">
	<img src="<?php echo asa_img( 'bordado-maquina.jpg' ); ?>" alt="" width="1400" height="733" fetchpriority="high">
	<div class="wrap hero__in">
		<p class="eyebrow">Artículos promocionales para empresas</p>
		<h1>Tu marca en todas partes</h1>
		<p>Encontramos y personalizamos los artículos que tu empresa necesita, de catálogo y de fabricación propia, con envíos a todo México.</p>
		<div class="actions">
			<a class="btn btn--light btn--lg" href="<?php echo esc_url( home_url( '/cotizar/' ) ); ?>">Solicitar cotización</a>
			<a class="btn btn--ghost btn--lg" href="<?php echo esc_url( home_url( '/catalogo/' ) ); ?>">Explorar catálogo</a>
		</div>
	</div>
</section>

<section class="proofs"><ul class="wrap">
	<li><b>+35 años</b>de experiencia</li>
	<li><b>Todo México</b>envíos nacionales</li>
	<li><b>Crédito</b>para empresas, sujeto a autorización</li>
	<li><b>Fabricación propia</b>chamarras hechas en México</li>
</ul></section>

<section class="sec"><div class="wrap">
	<p class="eyebrow">Catálogo</p><h2>Encuentra lo que estás buscando</h2>
	<?php asa_cat_grid(); ?>
</div></section>

<section class="sec sec--tint"><div class="wrap">
	<p class="eyebrow">Soluciones</p><h2>Para cada necesidad de tu empresa</h2>
	<div class="tiles">
		<?php
		$solutions = get_page_by_path( 'soluciones' );
		foreach ( $solutions ? get_pages( array( 'parent' => $solutions->ID, 'sort_column' => 'menu_order' ) ) : array() as $p ) {
			echo '<a class="tile" href="' . esc_url( get_permalink( $p ) ) . '"><h3>' . esc_html( $p->post_title ) . '</h3><p>' . esc_html( $p->post_excerpt ) . '</p><span>Ver ideas ' . asa_icon( 'arrow' ) . '</span></a>';
		}
		?>
	</div>
</div></section>

<?php $new = do_shortcode( '[asa_productos n="8"]' ); if ( $new ) : ?>
<section class="sec"><div class="wrap">
	<p class="eyebrow">Novedades</p><h2>Lo más reciente del catálogo</h2>
	<?php echo $new; ?>
</div></section>
<?php endif; ?>

<section class="sec sec--tint"><div class="wrap">
	<p class="eyebrow">Confianza</p><h2>Empresas que trabajan con nosotros</h2>
	<?php asa_clients(); ?>
</div></section>

<section class="sec sec--dark"><div class="wrap split">
	<div>
		<p class="eyebrow">Fabricación propia</p><h2>Chamarras con tu marca, hechas en México</h2>
		<p>Desarrollamos chamarras con atención a materiales, confección y acabados, y las personalizamos con la identidad de tu empresa.</p>
		<a class="btn btn--light" href="<?php echo esc_url( home_url( '/catalogo/textiles/chamarras-y-chalecos/' ) ); ?>">Ver chamarras</a>
	</div>
	<img src="<?php echo asa_img( 'bordado.jpg' ); ?>" alt="Bordado sobre prenda" loading="lazy" width="584" height="900">
</div></section>

<section class="sec"><div class="wrap">
	<p class="eyebrow">Cómo trabajamos</p><h2>Te acompañamos hasta la entrega</h2>
	<ol class="steps">
		<li><h3>Cotización</h3><p>Nos compartes qué necesitas y te proponemos alternativas según tu presupuesto.</p></li>
		<li><h3>Autorización</h3><p>Revisas y apruebas productos, acabados y condiciones.</p></li>
		<li><h3>Producción</h3><p>Fabricamos o personalizamos con la técnica adecuada.</p></li>
		<li><h3>Entrega</h3><p>Enviamos tu pedido a cualquier parte de México.</p></li>
	</ol>
</div></section>

<section class="sec sec--tint"><div class="wrap narrow">
	<p class="eyebrow">Preguntas frecuentes</p><h2>Antes de cotizar</h2>
	<?php asa_faq_html(); ?>
</div></section>

<section class="sec"><div class="wrap narrow">
	<p class="eyebrow">Contacto</p><h2>Cuéntanos qué necesitas</h2>
	<?php echo function_exists( 'asa_form' ) ? asa_form( 'contacto' ) : ''; ?>
</div></section>

<?php get_footer(); ?>
