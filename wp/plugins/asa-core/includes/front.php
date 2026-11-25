<?php
/** Formularios, lista de cotización y script de la web. */
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'asa-core', ASA_CORE_URL . 'assets/asa.js', array(), ASA_CORE_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script( 'asa-core', 'ASA', array(
		'rest'     => esc_url_raw( rest_url( 'asa/v1/' ) ),
		'whatsapp' => preg_replace( '/\D/', '', asa_opt( 'whatsapp' ) ),
		'quote'    => home_url( '/cotizar/' ),
		'privacy'  => home_url( '/aviso-de-privacidad/' ),
	) );
} );

const ASA_MX_STATES = array(
	'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango',
	'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro',
	'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas',
);

function asa_form( $type = 'contacto' ) {
	$quote  = 'cotizacion' === $type;
	$labels = array( 'cotizacion' => 'Enviar solicitud de cotización', 'contacto' => 'Enviar mensaje', 'credito' => 'Solicitar línea de crédito' );
	ob_start();
	?>
	<form class="asa-form" data-type="<?php echo esc_attr( $type ); ?>" enctype="multipart/form-data">
		<div class="fields">
			<label>Nombre *<input name="name" required autocomplete="name"></label>
			<label>Empresa *<input name="company" required autocomplete="organization"></label>
			<label>Correo *<input type="email" name="email" required autocomplete="email"></label>
			<label>Teléfono<input type="tel" name="phone" autocomplete="tel"></label>
			<label>Estado
				<select name="state"><option value="">Selecciona</option><?php foreach ( ASA_MX_STATES as $s ) { echo '<option>' . esc_html( $s ) . '</option>'; } ?></select>
			</label>
			<?php if ( $quote ) : ?>
				<label>Fecha en que lo necesitas<input type="date" name="needed_date"></label>
				<label class="full">Logotipo (PDF, PNG, JPG, AI o EPS, hasta 8 MB)<input type="file" name="logo" accept=".pdf,.png,.jpg,.jpeg,.ai,.eps"></label>
			<?php endif; ?>
			<label class="full">¿Cómo nos conociste?
				<select name="how_known"><option value="">Selecciona</option><?php foreach ( ASA_HOW as $h ) { echo '<option>' . esc_html( $h ) . '</option>'; } ?></select>
			</label>
			<label class="full"><?php echo 'credito' === $type ? 'Cuéntanos sobre tu empresa y tu volumen de compra' : 'Mensaje'; ?><textarea name="message" rows="4"></textarea></label>
		</div>
		<label class="hp" aria-hidden="true">Sitio web<input name="website" tabindex="-1" autocomplete="off"></label>
		<label class="check"><input type="checkbox" name="privacy" value="1" required><span>Acepto el <a href="<?php echo esc_url( home_url( '/aviso-de-privacidad/' ) ); ?>">aviso de privacidad</a>.</span></label>
		<button type="submit" class="btn btn--lg"><?php echo esc_html( $labels[ $type ] ?? $labels['contacto'] ); ?></button>
		<p class="form-msg" role="status" aria-live="polite"></p>
	</form>
	<?php
	return ob_get_clean();
}

add_shortcode( 'asa_form', fn( $a ) => asa_form( shortcode_atts( array( 'tipo' => 'contacto' ), $a )['tipo'] ) );

add_shortcode( 'asa_cotizador', function () {
	return '<div class="quote"><section><h2>Tu lista</h2><div id="asa-list" data-catalog="' . esc_url( home_url( '/catalogo/' ) ) . '"></div></section>'
		. '<section><h2>Tus datos</h2>' . asa_form( 'cotizacion' ) . '</section></div>';
} );
