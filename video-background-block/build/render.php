<?php
$id = wp_unique_id( 'vbbVideoBG-' );

if( !function_exists( 'getBackgroundCSS' ) ){
	function getBackgroundCSS( $bg, $isSolid = true, $isGradient = true, $isImage = true ) {
		extract( $bg );
		$type = $type ?? 'solid';
		$color = $color ?? '#000000b3';
		$gradient = $gradient ?? 'linear-gradient(135deg, #4527a4, #8344c5)';
		$image = $image ?? [];
		$position = $position ?? 'center center';
		$attachment = $attachment ?? 'initial';
		$repeat = $repeat ?? 'no-repeat';
		$size = $size ?? 'cover';
		$overlayColor = $overlayColor ?? '#000000b3';

		$gradientCSS = $isGradient ? "background: $gradient;" : '';

		$imgUrl = $image['url'] ?? '';
		$imageCSS = $isImage ? "background: url($imgUrl); background-color: $overlayColor; background-position: $position; background-size: $size; background-repeat: $repeat; background-attachment: $attachment; background-blend-mode: overlay;" : '';

		$solidCSS = $isSolid ? "background: $color;" : '';

		$styles = 'gradient' === $type ? $gradientCSS : ( 'image' === $type ? $imageCSS : $solidCSS );

		return $styles;
	}
}

if( !function_exists( 'getSpaceCSS' ) ){
	function getSpaceCSS( $space ) {
		extract( $space );
		$side = $side ?? 2;
		$vertical = $vertical ?? '0px';
		$horizontal = $horizontal ?? '0px';
		$top = $top ?? '0px';
		$right = $right ?? '0px';
		$bottom = $bottom ?? '0px';
		$left = $left ?? '0px';

		$styles = ( 2 === $side ) ? "$vertical $horizontal" : "$top $right $bottom $left";

		return $styles;
	}
}

extract( $attributes );

$mainSl = "#$id";
$styles = "$mainSl{
	min-height: $minHeight;
}
$mainSl .vbbVideoContent{
	justify-content: $verticalAlign;
	text-align: $textAlign;
	min-height: $minHeight;
	padding: ". getSpaceCSS( $padding ) .";
}
$mainSl .vbbVideoOverlay{
	". getBackgroundCSS( $bgOverlay ) ."
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