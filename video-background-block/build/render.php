<?php
$id = wp_unique_id( 'vbbVideoBG-' );

extract( $attributes );

$mainSl = "#$id";
$styles = "$mainSl{
	min-height: $minHeight;
}
$mainSl .vbbVideoContent{
	justify-content: $verticalAlign;
	text-align: $textAlign;
	min-height: $minHeight;
	padding: ". VBB\GetCSS::getSpaceCSS( $padding ) .";
}
$mainSl .vbbVideoOverlay{
	". VBB\GetCSS::getBackgroundCSS( $bgOverlay ) ."
}";

?>
<div <?php echo get_block_wrapper_attributes(); ?> id='<?php echo esc_attr( $id ); ?>'>
	<style>
		<?php echo esc_html( $styles ); ?>
	</style>

	<video autoplay muted loop playsinline class='vbbVideoPlayer' poster='<?php echo esc_attr( $poster['url'] ) ?>'>
		<source src='<?php echo esc_attr( $video['url'] ) ?>' type='video/mp4' />

		Your browser does not support HTML5 video.
	</video>

	<div class='vbbVideoOverlay'></div>

	<div class='vbbVideoContent'>
		<?php echo wp_kses_post( $content ); ?>
	</div>
</div>