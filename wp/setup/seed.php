<?php
/**
 * Contenido inicial del sitio. Uso: wp eval-file seed.php
 * Crea las páginas que falten (no sobrescribe las editadas) y las descripciones de categoría.
 * No inventa mínimos, plazos, direcciones ni condiciones: están pendientes de ASA.
 */
defined( 'ABSPATH' ) || exit;

$products = fn( $cat, $n = 8 ) => "[asa_productos categoria=\"$cat\" n=\"$n\"]";
$cta      = "<h2>Cuéntanos tu proyecto</h2>\n[asa_form tipo=\"contacto\"]";

$solution = fn( $intro, array $ideas, $cats ) => "<p>$intro</p>\n<h2>Ideas que funcionan</h2>\n<ul><li>" . implode( '</li><li>', $ideas ) . "</li></ul>\n<h2>Productos sugeridos</h2>\n" . $products( $cats ) . "\n$cta";

$technique = fn( $text, $ideal, $slug ) => "<p>$text</p>\n<h2>Cuándo conviene</h2>\n<p>$ideal</p>\n<h2>Productos disponibles con esta técnica</h2>\n[asa_productos tecnica=\"$slug\" n=\"8\"]\n<h2>Envíanos tu logotipo</h2>\n<p>Compártelo en tu solicitud, de preferencia en formato vectorial (AI, EPS o PDF). Te asesoramos sobre el acabado.</p>\n[asa_form tipo=\"contacto\"]";

$pages = array(
	array( 'inicio', 'Inicio', '', '' ),
	array( 'cotizar', 'Solicita tu cotización', 'Revisa tu lista, indica cantidades y déjanos los datos de tu empresa.', '[asa_cotizador]' ),
	array( 'soluciones', 'Soluciones promocionales para tu empresa', 'Elige la ocasión y te proponemos productos para ella.', '' ),
	array( 'soluciones/regalos-corporativos', 'Regalos corporativos', 'Detalles con tu marca para reconocer a quienes hacen crecer tu negocio.', $solution( 'Un buen regalo corporativo se usa y se recuerda. Te ayudamos a elegir según el destinatario y el presupuesto.', array( 'Termos y tazas con grabado', 'Libretas y bolígrafos coordinados', 'Mochilas y maletas ejecutivas' ), 'bebidas,libretas-y-carpetas' ) ),
	array( 'soluciones/regalos-ejecutivos', 'Regalos ejecutivos', 'Piezas de mayor presencia para dirección, socios y cuentas clave.', $solution( 'Materiales y acabados sobrios, con personalización discreta y permanente.', array( 'Sets de vino y bar', 'Portafolios', 'Sets ejecutivos y escritura metálica' ), 'bar,portafolios,oficina' ) ),
	array( 'soluciones/eventos-y-exposiciones', 'Eventos, exposiciones y convenciones', 'Artículos para entregar en tu stand y para equipar a tu equipo.', $solution( 'Piensa en la cantidad, la fecha del evento y lo fácil que será para el asistente llevarse el artículo.', array( 'Bolsas reutilizables', 'Bolígrafos y llaveros', 'Gafetes y playeras para el equipo' ), 'bolsas,escritura,llaveros' ) ),
	array( 'soluciones/kits-de-bienvenida', 'Kits de bienvenida para colaboradores', 'El primer día con la identidad de tu empresa.', $solution( 'Armamos kits combinando artículos de uso diario con tu marca.', array( 'Mochila o bolsa', 'Termo o taza', 'Libreta, bolígrafo y prenda bordada' ), 'mochilas,bebidas,libretas-y-carpetas' ) ),
	array( 'soluciones/clientes-y-proveedores', 'Regalos para clientes y proveedores', 'Mantén tu marca presente en las relaciones que importan.', $solution( 'Opciones para fin de año, aniversarios y cierres de proyecto.', array( 'Hieleras y loncheras', 'Artículos para el hogar y asador', 'Tecnología de uso diario' ), 'hogar,tecnologia' ) ),
	array( 'soluciones/campanas', 'Artículos para campañas y actividades', 'Productos para activaciones, jornadas internas y campañas de temporada.', $solution( 'Cuando el volumen importa, conviene partir del presupuesto y la fecha.', array( 'Antiestrés', 'Artículos deportivos', 'Gorras y playeras' ), 'antiestres,deportes,textiles' ) ),
	array( 'personalizacion', 'Personalización con tu logotipo', 'Distintas técnicas de impresión y acabados para cada material.', '' ),
	array( 'personalizacion/chamarras', 'Chamarras personalizadas hechas en México', 'Fabricación propia con atención a materiales, confección y acabados.', "<p>Las chamarras son una de nuestras líneas de fabricación propia. Las desarrollamos en México y las personalizamos con tu logotipo, para uniformes, equipos de campo, ventas o regalos.</p>\n<h2>Qué definimos contigo</h2>\n<ul><li>Modelo y material según el uso y el clima</li><li>Colores de la identidad de tu empresa</li><li>Tallas por colaborador</li><li>Ubicación y técnica del logotipo</li></ul>\n<h2>Chamarras y chalecos de catálogo</h2>\n" . $products( 'chamarras-y-chalecos', 8 ) . "\n<h2>Cotiza tus chamarras</h2>\n[asa_form tipo=\"contacto\"]" ),
	array( 'personalizacion/serigrafia', 'Serigrafía', 'Color y visibilidad para pedidos de volumen.', $technique( 'La tinta se aplica a través de una malla, un color a la vez, sobre textiles, bolsas, plásticos, vidrio y otros materiales.', 'Para logotipos de pocos colores y cantidades medias o grandes.', 'serigrafia' ) ),
	array( 'personalizacion/bordado', 'Bordado', 'Textura y presencia en prendas, gorras y mochilas.', $technique( 'El logotipo se cose con hilo directamente sobre la prenda y resiste el uso y el lavado.', 'Para uniformes, chamarras, gorras y mochilas.', 'bordado' ) ),
	array( 'personalizacion/grabado-laser', 'Grabado láser', 'Un acabado permanente y sobrio.', $technique( 'El láser marca la superficie del material sin tintas.', 'Para metal, madera y piezas de regalo ejecutivo.', 'grabado-laser' ) ),
	array( 'personalizacion/sublimacion', 'Sublimación', 'Impresión a todo color integrada en el material.', $technique( 'La tinta pasa a gas con calor y se fija en poliéster o en superficies preparadas.', 'Para diseños con muchos colores o degradados sobre materiales claros.', 'sublimacion' ) ),
	array( 'personalizacion/tampografia', 'Tampografía', 'Precisión en piezas pequeñas y superficies curvas.', $technique( 'Una almohadilla transfiere la tinta desde un grabado hasta el producto.', 'Para bolígrafos, llaveros y artículos pequeños o irregulares.', 'tampografia' ) ),
	array( 'empresas', 'Un proveedor que entiende cómo compran las empresas', 'Procesos de compra, facturación, pagos, crédito y envíos nacionales.', "<h2>Proceso de compra</h2>\n<ol><li>Solicitud y cotización</li><li>Autorización de productos, arte y condiciones</li><li>Producción y personalización</li><li>Entrega en la dirección que indiques</li></ol>\n<h2>Facturación y pagos</h2>\n<p>Trabajamos con los procesos de compra y facturación de empresas. Tu asesor te confirma los requisitos de alta como proveedor y las formas de pago.</p>\n<h2>Envíos a todo México</h2>\n<p>Llevamos tus promocionales a cualquier parte del país.</p>\n<h2>Clientes</h2>\n[asa_clientes]\n<h2>Solicita una línea de crédito</h2>\n<p>Las líneas de crédito están sujetas a autorización y condiciones comerciales.</p>\n[asa_form tipo=\"credito\"]" ),
	array( 'nosotros', 'Más de 35 años poniendo marcas en todas partes', 'La calidad ofrecida en el tiempo esperado.', "<p>En ASA Promocionales ayudamos a las empresas a encontrar los artículos que necesitan y los personalizamos con su logotipo, marca o mensaje.</p>\n<p>Contamos con productos de catálogo y líneas de fabricación propia para ofrecer opciones que se adapten a necesidades, presupuesto y objetivos de compra.</p>\n<h2>Qué hacemos</h2>\n<ul><li>Te ofrecemos opciones de un amplio catálogo</li><li>Fabricamos productos propios, como chamarras</li><li>Personalizamos con distintas técnicas</li><li>Te asesoramos en productos, materiales y acabados</li><li>Gestionamos tu pedido de la cotización a la entrega</li></ul>\n<h2>Empresas que confían en nosotros</h2>\n[asa_clientes]\n$cta" ),
	array( 'preguntas-frecuentes', 'Preguntas frecuentes', 'Lo que conviene saber antes de cotizar.', "[asa_faq]\n$cta" ),
	array( 'contacto', 'Habla con un asesor', 'Escríbenos y te respondemos con opciones para tu proyecto.', "<p><a class=\"btn btn--wa\" href=\"#\" data-wa>Escribir por WhatsApp</a></p>\n[asa_form tipo=\"contacto\"]" ),
	array( 'aviso-de-privacidad', 'Aviso de privacidad', 'Borrador pendiente de revisión legal.', "<p><strong>Este texto es un borrador para el sitio de prueba. ASA Promocionales debe completarlo y validarlo antes de publicarlo.</strong></p>\n<h2>Responsable</h2>\n<p>[Razón social y domicilio pendientes]</p>\n<h2>Datos que recabamos</h2>\n<p>Nombre, empresa, correo, teléfono, estado, mensaje, productos solicitados y archivos que adjuntes. También el origen de tu visita (página de procedencia, parámetros de campaña y página de entrada).</p>\n<h2>Finalidades</h2>\n<p>Atender tu solicitud de cotización, contacto o crédito y medir qué canales generan solicitudes. Si te suscribes, enviarte el boletín a través de Mailchimp; puedes darte de baja en cualquier momento.</p>\n<h2>Conservación en tu navegador</h2>\n<p>Tu lista de cotización se guarda en tu navegador. El origen de la visita se conserva 90 días solo si aceptas; si no, únicamente durante la sesión.</p>\n<h2>Derechos</h2>\n<p>[Medio para ejercer los derechos de acceso, rectificación, cancelación y oposición: pendiente]</p>" ),
);

$order = 0;
foreach ( $pages as [ $path, $title, $excerpt, $content ] ) {
	++$order;
	if ( get_page_by_path( $path ) ) {
		continue;
	}
	$parent = str_contains( $path, '/' ) ? get_page_by_path( dirname( $path ) ) : null;
	wp_insert_post( wp_slash( array(
		'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => basename( $path ),
		'post_excerpt' => $excerpt, 'post_content' => $content, 'post_parent' => $parent->ID ?? 0, 'menu_order' => $order,
	) ), true );
}

foreach ( array( 'serigrafia' => 'Serigrafía', 'bordado' => 'Bordado', 'grabado-laser' => 'Grabado láser', 'sublimacion' => 'Sublimación', 'tampografia' => 'Tampografía' ) as $slug => $name ) {
	if ( ! term_exists( $slug, 'asa_tecnica' ) ) {
		wp_insert_term( $name, 'asa_tecnica', array( 'slug' => $slug ) );
	}
}

$descriptions = array(
	'Agendas' => 'Agendas diarias y semanales con tu logotipo, un regalo de fin de año que acompaña todo el ciclo.',
	'Antiestrés' => 'Figuras antiestrés personalizadas para ferias, campañas de bienestar y activaciones.',
	'Artículos de viaje' => 'Neceseres, portadocumentos, cangureras y accesorios de viaje con la marca de tu empresa.',
	'Bar' => 'Sets de vino, sacacorchos, destapadores y licoreras personalizados para regalos ejecutivos.',
	'Bebidas' => 'Termos, cilindros, tazas y vasos personalizados con serigrafía o grabado para tu empresa.',
	'Belleza' => 'Cosmetiqueras, espejos y sets de cuidado personal para campañas y obsequios.',
	'Bolsas' => 'Bolsas ecológicas, reutilizables y casuales con tu logotipo para eventos y campañas.',
	'Complementos' => 'Accesorios y complementos para redondear tus regalos promocionales.',
	'Deportes' => 'Artículos deportivos y de futbol para torneos, activaciones y programas de bienestar.',
	'Entretenimiento' => 'Juegos y sets de entretenimiento personalizados para regalar y convivir.',
	'Escritura' => 'Plumas y bolígrafos metálicos, de plástico y ecológicos con tu marca.',
	'Herramientas' => 'Sets de herramientas, navajas, lámparas y accesorios para auto con tu logotipo.',
	'Hieleras, loncheras y portaviandas' => 'Hieleras, loncheras y portaviandas personalizadas para colaboradores y clientes.',
	'Hogar' => 'Tablas, sets de cocina, asador y decoración para regalos que se quedan en casa.',
	'Libretas y carpetas' => 'Libretas ejecutivas y ecológicas y carpetas personalizadas para oficina y eventos.',
	'Llaveros' => 'Llaveros metálicos y multifunción personalizados, ideales para entregas de volumen.',
	'Maletas' => 'Maletas de mano y con ruedas con la identidad de tu empresa.',
	'Mochilas' => 'Mochilas ejecutivas, escolares, deportivas y de viaje personalizadas.',
	'Niños' => 'Artículos escolares, juguetes y alcancías para campañas dirigidas a familias.',
	'Oficina' => 'Sets ejecutivos, portagafetes, relojes y artículos de escritorio con tu logotipo.',
	'Paraguas e impermeables' => 'Paraguas e impermeables personalizados para temporada de lluvias.',
	'Portafolios' => 'Portafolios y fundas para laptop para ejecutivos y equipos comerciales.',
	'Salud' => 'Pastilleros, geles y artículos de cuidado personal para campañas de salud.',
	'Tecnología' => 'Bocinas, audífonos, baterías portátiles, memorias USB y accesorios con tu marca.',
	'Textiles' => 'Playeras, gorras, chamarras y chalecos personalizados para uniformes y eventos.',
);
foreach ( $descriptions as $name => $text ) {
	$slug = sanitize_title( $name );
	$term = get_term_by( 'slug', $slug, 'asa_categoria' );
	if ( ! $term ) {
		wp_insert_term( $name, 'asa_categoria', array( 'slug' => $slug, 'description' => $text ) );
	} elseif ( ! $term->description ) {
		wp_update_term( $term->term_id, 'asa_categoria', array( 'description' => $text ) );
	}
}

$home = get_page_by_path( 'inicio' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home->ID );
delete_transient( 'asa_cats' );
flush_rewrite_rules();
echo "Contenido listo.\n";
