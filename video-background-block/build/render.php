<?php
$id = wp_unique_id( 'vbbVideoBG-' );

extract( $attributes );

if ( ! function_exists( 'vbb_has_space_value' ) ) {
	function vbb_has_space_value( $space ) {
		if ( empty( $space ) || ! is_array( $space ) ) {
			return false;
		}
		$keys = [ 'vertical', 'horizontal', 'top', 'right', 'bottom', 'left' ];
		foreach ( $keys as $key ) {
			if ( ! empty( $space[ $key ] ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'vbb_resolve_space' ) ) {
	function vbb_resolve_space( $space, $fallback ) {
		return vbb_has_space_value( $space ) ? $space : $fallback;
	}
}

if ( ! function_exists( 'vbb_extract_youtube_id' ) ) {
	function vbb_extract_youtube_id( $url ) {
		if ( empty( $url ) ) {
			return '';
		}
		if ( strpos( $url, 'youtu.be/' ) !== false ) {
			$parts = explode( 'youtu.be/', $url );
			return explode( '?', end( $parts ) )[0];
		}
		$parsed = wp_parse_url( $url );
		if ( ! empty( $parsed['query'] ) ) {
			parse_str( $parsed['query'], $query );
			if ( ! empty( $query['v'] ) ) {
				return $query['v'];
			}
		}
		if ( ! empty( $parsed['path'] ) && strpos( $parsed['path'], '/embed/' ) !== false ) {
			$parts = explode( '/embed/', $parsed['path'] );
			return end( $parts );
		}
		return '';
	}
}

if ( ! function_exists( 'vbb_extract_vimeo_id' ) ) {
	function vbb_extract_vimeo_id( $url ) {
		if ( empty( $url ) ) {
			return '';
		}
		$parsed = wp_parse_url( $url );
		$path = $parsed['path'] ?? '';
		$segments = array_values( array_filter( explode( '/', $path ) ) );
		$segments = array_reverse( $segments );
		foreach ( $segments as $seg ) {
			if ( ctype_digit( $seg ) ) {
				return $seg;
			}
		}
		return '';
	}
}

if ( ! function_exists( 'vbb_build_youtube_embed' ) ) {
	function vbb_build_youtube_embed( $url, $no_cookie = true ) {
		$id = vbb_extract_youtube_id( $url );
		if ( ! $id ) {
			return '';
		}
		$base = $no_cookie ? 'https://www.youtube-nocookie.com/embed/' : 'https://www.youtube.com/embed/';
		$params = 'autoplay=1&mute=1&loop=1&controls=0&playsinline=1&modestbranding=1&rel=0&playlist=' . $id;
		return $base . $id . '?' . $params;
	}
}

if ( ! function_exists( 'vbb_build_vimeo_embed' ) ) {
	function vbb_build_vimeo_embed( $url ) {
		$id = vbb_extract_vimeo_id( $url );
		if ( ! $id ) {
			return '';
		}
		return 'https://player.vimeo.com/video/' . $id . '?autoplay=1&muted=1&loop=1&background=1&dnt=1';
	}
}

$vbb_css = function( $prop, $val ) {
	if ( $val === '' || $val === null ) {
		return '';
	}
	return $prop . ': ' . $val . ';';
};

$videoSources = $videoSources ?? [];
$posterSources = $posterSources ?? [];
$posterOptions = $posterOptions ?? [];

if ( ! is_array( $video ) && is_string( $video ) ) {
	$video = [ 'id' => null, 'url' => $video, 'alt' => '', 'title' => '' ];
}
if ( ! is_array( $poster ) && is_string( $poster ) ) {
	$poster = [ 'id' => null, 'url' => $poster, 'alt' => '', 'title' => '' ];
}

if ( empty( $videoSources['desktop']['mp4']['url'] ) && ! empty( $video['url'] ) ) {
	$videoSources['desktop']['mp4'] = [
		'id' => $video['id'] ?? null,
		'url' => $video['url'] ?? '',
		'alt' => $video['alt'] ?? '',
		'title' => $video['title'] ?? ''
	];
}
if ( empty( $posterSources['desktop']['url'] ) && ! empty( $poster['url'] ) ) {
	$posterSources['desktop'] = [
		'id' => $poster['id'] ?? null,
		'url' => $poster['url'] ?? '',
		'alt' => $poster['alt'] ?? '',
		'title' => $poster['title'] ?? ''
	];
}

if ( ! is_array( $bgOverlay ) ) {
	$bgOverlay = is_string( $bgOverlay ) ? [ 'color' => $bgOverlay ] : [ 'color' => '#000000b3' ];
}
$contentTypography = $contentTypography ?? [];
$headingTypography = $headingTypography ?? [];
$paragraphTypography = $paragraphTypography ?? [];
$buttonTypography = $buttonTypography ?? [];
$buttonStyle = $buttonStyle ?? [];

$posterDesktop = $posterSources['desktop']['url'] ?? ( $poster['url'] ?? '' );
$posterTablet = $posterSources['tablet']['url'] ?? '';
$posterMobile = $posterSources['mobile']['url'] ?? '';

$minHeightTablet = $minHeightTablet ?: $minHeight;
$minHeightMobile = $minHeightMobile ?: $minHeight;
$resolvedPaddingTablet = vbb_resolve_space( $paddingTablet ?? [], $padding ?? [] );
$resolvedPaddingMobile = vbb_resolve_space( $paddingMobile ?? [], $padding ?? [] );

$posterBlur = ! empty( $posterOptions['blur'] ) ? (int) ( $posterOptions['blurAmount'] ?? 12 ) : 0;
$posterDisplay = isset( $posterOptions['show'] ) && ! $posterOptions['show'] ? 'none' : 'block';
$posterColor = $posterOptions['dominantColor'] ?? '#000000';
$posterAuto = ! empty( $posterOptions['dominantAuto'] );
$overlayOpacity = isset( $overlayOpacity ) ? (float) $overlayOpacity : 1;
$overlayBlendMode = $overlayBlendMode ?? 'normal';
$overlayPattern = $overlayPattern ?? 'none';
$overlayPatternOpacity = isset( $overlayPatternOpacity ) ? (float) $overlayPatternOpacity : 0.2;
$overlayAnimate = ! empty( $overlayAnimate );
$overlayNoise = ! empty( $overlayNoise );
$overlayVignette = ! empty( $overlayVignette );
$videoFit = $videoFit ?? 'cover';
$focusPoint = $focusPoint ?? [ 'x' => 50, 'y' => 50 ];
$focusX = isset( $focusPoint['x'] ) ? (int) $focusPoint['x'] : 50;
$focusY = isset( $focusPoint['y'] ) ? (int) $focusPoint['y'] : 50;
$startTime = isset( $startTime ) ? (float) $startTime : 0;
$endTime = isset( $endTime ) ? (float) $endTime : 0;
$playbackRate = isset( $playbackRate ) ? (float) $playbackRate : 1;
$muted = isset( $muted ) ? (bool) $muted : true;
$volume = isset( $volume ) ? (float) $volume : 1;
$audioOnClick = ! empty( $audioOnClick );

$mainSl = "#$id";
$styles = "$mainSl{
	min-height: $minHeight;
	--vbb-video-fit: $videoFit;
	--vbb-overlay-opacity: $overlayOpacity;
	--vbb-overlay-blend: $overlayBlendMode;
	--vbb-overlay-pattern: " . ( 'none' !== $overlayPattern ? ( $overlayPattern === 'dots'
		? 'radial-gradient(rgba(255,255,255,0.35) 1px, transparent 1px)'
		: ( $overlayPattern === 'grid'
			? 'linear-gradient(rgba(255,255,255,0.25) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.25) 1px, transparent 1px)'
			: 'repeating-linear-gradient(45deg, rgba(255,255,255,0.25) 0, rgba(255,255,255,0.25) 1px, transparent 1px, transparent 8px)' ) ) : 'none' ) . ";
	--vbb-overlay-pattern-opacity: $overlayPatternOpacity;
	--vbb-overlay-noise: " . ( $overlayNoise ? '1' : '0' ) . ";
	--vbb-overlay-vignette: " . ( $overlayVignette ? '1' : '0' ) . ";
	--vbb-overlay-float: " . ( $overlayAnimate ? 'vbbOverlayFloat 16s linear infinite' : 'none' ) . ";
	--vbb-overlay-pulse: " . ( $overlayAnimate ? 'vbbOverlayPulse 12s ease-in-out infinite' : 'none' ) . ";
}
$mainSl .vbbPosterLayer{
	display: $posterDisplay;
	background-image: url($posterDesktop);
	background-color: var(--vbb-poster-color, $posterColor);
	background-position: {$focusX}% {$focusY}%;
	filter: blur({$posterBlur}px);
}
$mainSl .vbbVideoPlayer{
	object-fit: $videoFit;
	object-position: {$focusX}% {$focusY}%;
}
$mainSl .vbbVideoContent{
	justify-content: $verticalAlign;
	text-align: $textAlign;
	min-height: $minHeight;
	padding: " . VBB\GetCSS::getSpaceCSS( $padding ) . ";
}
$mainSl .vbbVideoOverlay{
	" . VBB\GetCSS::getBackgroundCSS( $bgOverlay ) . "
}
@media (max-width: 1024px){
	$mainSl{
		min-height: $minHeightTablet;
	}
	$mainSl .vbbVideoContent{
		min-height: $minHeightTablet;
		padding: " . VBB\GetCSS::getSpaceCSS( $resolvedPaddingTablet ) . ";
	}
	$mainSl .vbbPosterLayer{
		background-image: url(" . ( $posterTablet ?: $posterDesktop ) . ");
	}
}
@media (max-width: 767px){
	$mainSl{
		min-height: $minHeightMobile;
	}
	$mainSl .vbbVideoContent{
		min-height: $minHeightMobile;
		padding: " . VBB\GetCSS::getSpaceCSS( $resolvedPaddingMobile ) . ";
	}
	$mainSl .vbbPosterLayer{
		background-image: url(" . ( $posterMobile ?: ( $posterTablet ?: $posterDesktop ) ) . ");
	}
}";

$sourceType = $sourceType ?? 'self';

$overlayPatternCss = 'none';
if ( 'dots' === $overlayPattern ) {
	$overlayPatternCss = 'radial-gradient(rgba(255,255,255,0.35) 1px, transparent 1px)';
} elseif ( 'grid' === $overlayPattern ) {
	$overlayPatternCss = 'linear-gradient(rgba(255,255,255,0.25) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.25) 1px, transparent 1px)';
} elseif ( 'diagonal' === $overlayPattern ) {
	$overlayPatternCss = 'repeating-linear-gradient(45deg, rgba(255,255,255,0.25) 0, rgba(255,255,255,0.25) 1px, transparent 1px, transparent 8px)';
}
$overlayFloat = $overlayAnimate ? 'vbbOverlayFloat 16s linear infinite' : 'none';
$overlayPulse = $overlayAnimate ? 'vbbOverlayPulse 12s ease-in-out infinite' : 'none';
$overlayStyle = sprintf(
	'--vbb-overlay-opacity:%s; --vbb-overlay-blend:%s; --vbb-overlay-pattern:%s; --vbb-overlay-pattern-opacity:%s; --vbb-overlay-vignette:%s; --vbb-overlay-float:%s; --vbb-overlay-pulse:%s;',
	esc_attr( (string) $overlayOpacity ),
	esc_attr( $overlayBlendMode ),
	esc_attr( $overlayPatternCss ),
	esc_attr( (string) $overlayPatternOpacity ),
	esc_attr( $overlayVignette ? '1' : '0' ),
	esc_attr( $overlayFloat ),
	esc_attr( $overlayPulse )
);

$videoDesktopMp4 = $videoSources['desktop']['mp4']['url'] ?? ( $video['url'] ?? '' );
$videoDesktopWebm = $videoSources['desktop']['webm']['url'] ?? '';
$videoDesktopOgg = $videoSources['desktop']['ogg']['url'] ?? '';
$videoTabletMp4 = $videoSources['tablet']['mp4']['url'] ?? '';
$videoTabletWebm = $videoSources['tablet']['webm']['url'] ?? '';
$videoTabletOgg = $videoSources['tablet']['ogg']['url'] ?? '';
$videoMobileMp4 = $videoSources['mobile']['mp4']['url'] ?? '';
$videoMobileWebm = $videoSources['mobile']['webm']['url'] ?? '';
$videoMobileOgg = $videoSources['mobile']['ogg']['url'] ?? '';

$youtubeUrl = $youtubeUrl ?? '';
$youtubeUrlTablet = $youtubeUrlTablet ?? '';
$youtubeUrlMobile = $youtubeUrlMobile ?? '';
$vimeoUrl = $vimeoUrl ?? '';
$vimeoUrlTablet = $vimeoUrlTablet ?? '';
$vimeoUrlMobile = $vimeoUrlMobile ?? '';
$ytNoCookie = ! empty( $ytNoCookie );

?>
<div
	<?php echo get_block_wrapper_attributes( [ 'class' => $overlayNoise ? 'vbbOverlayNoise' : '' ] ); ?>
	id='<?php echo esc_attr( $id ); ?>'
	data-poster-desktop='<?php echo esc_attr( $posterDesktop ); ?>'
	data-poster-tablet='<?php echo esc_attr( $posterTablet ); ?>'
	data-poster-mobile='<?php echo esc_attr( $posterMobile ); ?>'
	data-poster-color='<?php echo esc_attr( $posterColor ); ?>'
	data-poster-auto='<?php echo $posterAuto ? '1' : '0'; ?>'
	data-video-fit='<?php echo esc_attr( $videoFit ); ?>'
	data-focus-x='<?php echo esc_attr( (string) $focusX ); ?>'
	data-focus-y='<?php echo esc_attr( (string) $focusY ); ?>'
	data-start-time='<?php echo esc_attr( (string) $startTime ); ?>'
	data-end-time='<?php echo esc_attr( (string) $endTime ); ?>'
	data-playback-rate='<?php echo esc_attr( (string) $playbackRate ); ?>'
	data-muted='<?php echo $muted ? '1' : '0'; ?>'
	data-volume='<?php echo esc_attr( (string) $volume ); ?>'
	data-audio-on-click='<?php echo $audioOnClick ? '1' : '0'; ?>'
	data-embed-type='<?php echo esc_attr( $sourceType ); ?>'
	data-yt-no-cookie='<?php echo $ytNoCookie ? '1' : '0'; ?>'
	data-youtube-url-desktop='<?php echo esc_attr( $youtubeUrl ); ?>'
	data-youtube-url-tablet='<?php echo esc_attr( $youtubeUrlTablet ); ?>'
	data-youtube-url-mobile='<?php echo esc_attr( $youtubeUrlMobile ); ?>'
	data-vimeo-url-desktop='<?php echo esc_attr( $vimeoUrl ); ?>'
	data-vimeo-url-tablet='<?php echo esc_attr( $vimeoUrlTablet ); ?>'
	data-vimeo-url-mobile='<?php echo esc_attr( $vimeoUrlMobile ); ?>'
>
	<style>
		<?php echo esc_html( $styles ); ?>
	</style>

	<div class='vbbPosterLayer'></div>

	<?php if ( 'self' === $sourceType ) : ?>
		<video
			autoplay
			<?php echo $audioOnClick ? 'muted' : ( $muted ? 'muted' : '' ); ?>
			loop
			playsinline
			class='vbbVideoPlayer'
			poster='<?php echo esc_attr( $posterDesktop ); ?>'
			data-audio-on-click='<?php echo $audioOnClick ? '1' : '0'; ?>'
			data-volume='<?php echo esc_attr( (string) $volume ); ?>'
		>
			<?php if ( $videoDesktopWebm || $videoTabletWebm || $videoMobileWebm ) : ?>
				<source type='video/webm' src='<?php echo esc_attr( current( array_filter( [ $videoDesktopWebm, $videoTabletWebm, $videoMobileWebm ] ) ) ); ?>' data-src-desktop='<?php echo esc_attr( $videoDesktopWebm ); ?>' data-src-tablet='<?php echo esc_attr( $videoTabletWebm ); ?>' data-src-mobile='<?php echo esc_attr( $videoMobileWebm ); ?>' />
			<?php endif; ?>
			<?php if ( $videoDesktopOgg || $videoTabletOgg || $videoMobileOgg ) : ?>
				<source type='video/ogg' src='<?php echo esc_attr( current( array_filter( [ $videoDesktopOgg, $videoTabletOgg, $videoMobileOgg ] ) ) ); ?>' data-src-desktop='<?php echo esc_attr( $videoDesktopOgg ); ?>' data-src-tablet='<?php echo esc_attr( $videoTabletOgg ); ?>' data-src-mobile='<?php echo esc_attr( $videoMobileOgg ); ?>' />
			<?php endif; ?>
			<?php if ( $videoDesktopMp4 || $videoTabletMp4 || $videoMobileMp4 ) : ?>
				<source type='video/mp4' src='<?php echo esc_attr( current( array_filter( [ $videoDesktopMp4, $videoTabletMp4, $videoMobileMp4 ] ) ) ); ?>' data-src-desktop='<?php echo esc_attr( $videoDesktopMp4 ); ?>' data-src-tablet='<?php echo esc_attr( $videoTabletMp4 ); ?>' data-src-mobile='<?php echo esc_attr( $videoMobileMp4 ); ?>' />
			<?php endif; ?>
			Your browser does not support HTML5 video.
		</video>
	<?php endif; ?>

<?php if ( 'youtube' === $sourceType ) : ?>
		<?php $yt_embed = vbb_build_youtube_embed( $youtubeUrl, $ytNoCookie ); ?>
		<?php if ( $yt_embed ) : ?>
			<iframe
				class='vbbVideoEmbed'
				title='YouTube background video'
				src='<?php echo esc_attr( $yt_embed ); ?>'
				frameborder='0'
				allow='autoplay; fullscreen; picture-in-picture'
				allowfullscreen
			></iframe>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( 'vimeo' === $sourceType ) : ?>
		<?php $vimeo_embed = vbb_build_vimeo_embed( $vimeoUrl ); ?>
		<?php if ( $vimeo_embed ) : ?>
			<iframe
				class='vbbVideoEmbed'
				title='Vimeo background video'
				src='<?php echo esc_attr( $vimeo_embed ); ?>'
				frameborder='0'
				allow='autoplay; fullscreen; picture-in-picture'
				allowfullscreen
			></iframe>
		<?php endif; ?>
	<?php endif; ?>

	<div class='vbbVideoOverlay' style='<?php echo $overlayStyle; ?>'></div>

	<div class='vbbVideoContent'>
		<?php echo wp_kses_post( $content ); ?>
	</div>
</div>

