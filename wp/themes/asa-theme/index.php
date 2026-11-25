<?php get_header(); ?>
<div class="wrap page">
	<h1><?php echo is_404() ? 'No encontramos esa página' : esc_html( wp_get_document_title() ); ?></h1>
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<article><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?></article>
	<?php endwhile; else : ?>
		<p><a class="btn" href="<?php echo esc_url( home_url( '/catalogo/' ) ); ?>">Ir al catálogo</a></p>
	<?php endif; ?>
</div>
<?php get_footer(); ?>
