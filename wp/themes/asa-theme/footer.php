</main>
<?php if ( function_exists( 'asa_newsletter' ) ) : ?>
<section class="news" aria-labelledby="news-title"><div class="wrap news__in">
	<div><h2 id="news-title">Suscríbete a nuestro boletín</h2><p>Novedades del catálogo, ideas para tus campañas y promociones.</p></div>
	<?php echo asa_newsletter(); ?>
</div></section>
<?php endif; ?>
<footer class="footer">
	<div class="wrap footer__in">
		<div>
			<p class="footer__brand">Tu marca en todas partes</p>
			<p>La calidad ofrecida en el tiempo esperado. Artículos promocionales para empresas con más de 35 años de experiencia.</p>
			<?php if ( function_exists( 'asa_opt' ) ) : ?>
				<?php if ( asa_opt( 'phone' ) ) : ?><p><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', asa_opt( 'phone' ) ) ); ?>"><?php echo esc_html( asa_opt( 'phone' ) ); ?></a></p><?php endif; ?>
				<?php if ( asa_opt( 'email' ) ) : ?><p><a href="mailto:<?php echo esc_attr( asa_opt( 'email' ) ); ?>"><?php echo esc_html( asa_opt( 'email' ) ); ?></a></p><?php endif; ?>
			<?php endif; ?>
		</div>
		<nav aria-label="Catálogo"><h2>Categorías</h2><?php foreach ( array_slice( asa_categories(), 0, 25 ) as $c ) { echo '<a href="' . esc_url( $c['url'] ) . '">' . esc_html( $c['name'] ) . '</a>'; } ?></nav>
		<nav aria-label="Empresa"><h2>ASA</h2>
			<?php foreach ( array( 'nosotros' => 'Nosotros', 'empresas' => 'Compra empresarial', 'soluciones' => 'Soluciones', 'personalizacion' => 'Personalización', 'preguntas-frecuentes' => 'Preguntas frecuentes', 'contacto' => 'Contacto', 'aviso-de-privacidad' => 'Aviso de privacidad' ) as $slug => $label ) { echo '<a href="' . esc_url( home_url( "/$slug/" ) ) . '">' . esc_html( $label ) . '</a>'; } ?>
		</nav>
	</div>
	<div class="wrap footer__legal">© <?php echo esc_html( gmdate( 'Y' ) ); ?> ASA Promocionales</div>
</footer>
<a class="wa-float" href="#" data-wa aria-label="Escríbenos por WhatsApp"><?php echo asa_icon( 'chat' ); ?></a>
<?php wp_footer(); ?>
</body>
</html>
