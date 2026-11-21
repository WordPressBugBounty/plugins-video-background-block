<?php
namespace VBB;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Server side of the layer system (src/Layers): blocks inside a Video Background section can carry
 * a `vbbLayer` attribute with animation, typography, hover/click, position and responsive settings.
 *
 * The settings are written into each block's saved HTML by the editor (classes, data-*, inline
 * custom properties), so PHP only has to (1) register the attribute and (2) load Google fonts.
 */
class Layers {
	/** Mirrors LAYER_INCAPABLE_BLOCKS in src/Layers/allowedLayerBlocks.js — keep in sync. */
	const INCAPABLE_BLOCKS = [ 'vbb/video-bg', 'core/shortcode', 'core/html', 'core/freeform', 'core/missing' ];

	public static function init() {
		add_filter( 'register_block_type_args', [ __CLASS__, 'addAttribute' ], 10, 2 );
	}

	/** Same attribute the editor adds in src/Layers/layerAttributes.js, so REST and dynamic blocks accept it. */
	public static function addAttribute( $args, $name ) {
		if ( in_array( $name, self::INCAPABLE_BLOCKS, true ) ) {
			return $args;
		}

		if ( ! isset( $args['attributes'] ) || ! is_array( $args['attributes'] ) ) {
			$args['attributes'] = [];
		}

		if ( ! isset( $args['attributes']['vbbLayer'] ) ) {
			$args['attributes']['vbbLayer'] = [
				'type'    => 'object',
				'default' => [],
			];
		}

		return $args;
	}

	/**
	 * Inner content of a section, just before render.php passes it through wp_kses_post().
	 * Blocks saved by the first build of the layer system kept hover colours as rgb()/rgba(), which kses
	 * strips from inline styles; they are turned into hex here (only inside --vbb-hover-* declarations), so
	 * those pages look right without being re-saved. Content without such values is returned untouched.
	 */
	public static function prepareContent( $content ) {
		if ( ! is_string( $content ) || false === strpos( $content, '--vbb-hover-' ) || false === stripos( $content, 'rgb' ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/(--vbb-hover-(?:color|bg|bg-image)\s*:\s*)([^;"\']+)/i',
			function ( $m ) {
				return $m[1] . self::hexColors( $m[2] );
			},
			$content
		);
	}

	/** rgb()/rgba() → #rrggbb / #rrggbbaa inside a CSS value. Twin of cssColor() in src/Layers/utils.js. */
	public static function hexColors( $value ) {
		return preg_replace_callback(
			'/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)(%?)\s*)?\)/i',
			function ( $m ) {
				$hex   = function ( $n ) {
					return str_pad( dechex( (int) max( 0, min( 255, round( (float) $n ) ) ) ), 2, '0', STR_PAD_LEFT );
				};
				$alpha = isset( $m[4] ) && '' !== $m[4] ? max( 0, min( 1, (float) $m[4] / ( empty( $m[5] ) ? 1 : 100 ) ) ) : 1;
				return '#' . $hex( $m[1] ) . $hex( $m[2] ) . $hex( $m[3] ) . ( 1.0 === (float) $alpha ? '' : $hex( $alpha * 255 ) );
			},
			$value
		);
	}

	/** Enqueues the Google fonts chosen in the Typography panel of any block inside this section. */
	public static function enqueueFonts( $block ) {
		if ( ! ( $block instanceof \WP_Block ) || empty( $block->parsed_block['innerBlocks'] ) ) {
			return;
		}

		$urls = [];
		self::collectFonts( $block->parsed_block['innerBlocks'], $urls );

		foreach ( array_unique( $urls ) as $url ) {
			// One handle per URL, so a weight the font lacks fails alone and never blocks the regular face.
			$handle = 'vbb-font-' . sanitize_title( preg_replace( '/^.*family=|&display=swap$/', '', $url ) );

			if ( ! wp_style_is( $handle, 'registered' ) ) {
				wp_register_style( $handle, $url, [], null );
			}

			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Google Fonts URLs for one Typography value: the family (regular) plus the chosen weight / italic.
	 * Twin of fontUrls() in src/Layers/utils.js — keep in sync. Nothing when "Load Google Font" is off.
	 */
	public static function fontUrls( $typo ) {
		$family = is_array( $typo ) ? ( $typo['fontFamily'] ?? '' ) : '';

		if ( ! is_string( $family ) || 'Default' === $family || ( isset( $typo['isUploadFont'] ) && false === $typo['isUploadFont'] ) || ! preg_match( '/^[A-Za-z][A-Za-z0-9 \-]{0,50}$/', $family ) ) {
			return [];
		}

		$name    = str_replace( ' ', '+', $family );
		$variant = (string) ( $typo['fontVariant'] ?? ( $typo['fontWeight'] ?? 400 ) );
		$weight  = min( 900, max( 100, (int) $variant ?: 400 ) );
		$italic  = ( '' !== $variant && 'i' === substr( $variant, -1 ) ) || ( isset( $typo['fontStyle'] ) && 'italic' === $typo['fontStyle'] );

		$urls = [ 'https://fonts.googleapis.com/css2?family=' . $name . '&display=swap' ];
		if ( 400 !== $weight ) {
			$urls[] = 'https://fonts.googleapis.com/css2?family=' . $name . ':wght@' . $weight . '&display=swap';
		}
		if ( $italic ) {
			$urls[] = 'https://fonts.googleapis.com/css2?family=' . $name . ':ital,wght@1,' . $weight . '&display=swap';
		}

		return $urls;
	}

	private static function collectFonts( $blocks, &$urls ) {
		foreach ( (array) $blocks as $inner ) {
			$typo = $inner['attrs']['vbbLayer']['typo'] ?? null;

			if ( is_array( $typo ) ) {
				foreach ( self::fontUrls( $typo ) as $url ) {
					$urls[] = $url;
				}
			}

			if ( ! empty( $inner['innerBlocks'] ) ) {
				self::collectFonts( $inner['innerBlocks'], $urls );
			}
		}
	}
}

Layers::init();
