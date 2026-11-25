<?php get_header(); the_post(); ?>
<div class="wrap page">
	<?php asa_crumbs_html(); ?>
	<h1><?php the_title(); ?></h1>
	<?php if ( has_excerpt() ) : ?><p class="lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
	<div class="content"><?php the_content(); ?></div>
	<?php
	$children = get_pages( array( 'parent' => get_the_ID(), 'sort_column' => 'menu_order' ) );
	if ( $children ) {
		echo '<div class="tiles">';
		foreach ( $children as $p ) {
			echo '<a class="tile" href="' . esc_url( get_permalink( $p ) ) . '"><h3>' . esc_html( $p->post_title ) . '</h3><p>' . esc_html( $p->post_excerpt ) . '</p><span>Ver más ' . asa_icon( 'arrow' ) . '</span></a>';
		}
		echo '</div>';
	}
	?>
</div>
<?php get_footer(); ?>
