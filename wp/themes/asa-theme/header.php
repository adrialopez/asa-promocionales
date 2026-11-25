<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="icon" href="<?php echo asa_img( 'logo.png' ); ?>">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<a class="skip" href="#main">Saltar al contenido</a>
<div class="topbar"><div class="wrap"><span>Sitio de prueba · Envíos a todo México</span><a href="#" data-wa><?php echo asa_icon( 'chat' ); ?> WhatsApp</a></div></div>
<header class="header">
	<div class="wrap header__in">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo asa_img( 'logo.png' ); ?>" alt="ASA Promocionales" width="145" height="46"></a>
		<nav class="nav" id="nav" aria-label="Principal">
			<a href="<?php echo esc_url( home_url( '/catalogo/' ) ); ?>">Catálogo</a>
			<a href="<?php echo esc_url( home_url( '/soluciones/' ) ); ?>">Soluciones</a>
			<a href="<?php echo esc_url( home_url( '/personalizacion/chamarras/' ) ); ?>">Chamarras</a>
			<a href="<?php echo esc_url( home_url( '/personalizacion/' ) ); ?>">Personalización</a>
			<a href="<?php echo esc_url( home_url( '/empresas/' ) ); ?>">Empresas</a>
			<a href="<?php echo esc_url( home_url( '/nosotros/' ) ); ?>">Nosotros</a>
			<a href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>">Contacto</a>
		</nav>
		<a class="btn btn--sm quote-link" href="<?php echo esc_url( home_url( '/cotizar/' ) ); ?>"><?php echo asa_icon( 'list' ); ?><span>Cotizar</span><b data-count hidden>0</b></a>
		<button class="burger" type="button" aria-controls="nav" aria-expanded="false" aria-label="Abrir menú"><?php echo asa_icon( 'menu' ); ?></button>
	</div>
	<div class="searchbar"><form class="wrap" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<input type="search" name="s" placeholder="Busca por producto, material o código" aria-label="Buscar en el catálogo" value="<?php echo esc_attr( get_search_query() ); ?>">
		<input type="hidden" name="post_type" value="asa_producto">
		<button aria-label="Buscar"><?php echo asa_icon( 'search' ); ?></button>
	</form></div>
</header>
<main id="main">
