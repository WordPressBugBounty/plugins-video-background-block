<?php
namespace VBB;

class GetCSS{
	static function isValidCSS($property, $value) {
		if ( empty( $value ) && $value !== '0' && $value !== 0 ) {
			return '';
		}
		return "$property: $value;";
	}

	// Returns $value unchanged if it can't break out of a CSS declaration, otherwise $default.
	static function sanitizeValue( $value, $default = '' ) {
		if ( '' === $value || null === $value ) {
			return $value;
		}
		if ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) ) {
			return $default;
		}
		$str = (string) $value;
		if (
			! preg_match( '/^[a-z0-9#%.,\s()+\-*\/_!]+$/i', $str ) ||
			preg_match( '/(url|expression)\s*\(/i', $str ) ||
			substr_count( $str, '(' ) !== substr_count( $str, ')' )
		) {
			return $default;
		}
		return $value;
	}

	// Hex, named color or a color/gradient function such as rgba(), hsl(), var(), linear-gradient().
	static function sanitizeColor( $value, $default = '' ) {
		$value = self::sanitizeValue( $value, $default );
		if ( '' === $value || null === $value ) {
			return $value;
		}
		return preg_match( '/^(#[0-9a-f]{3,8}|[a-z]+|[a-z-]+\(.*\))$/is', (string) $value ) ? $value : $default;
	}

	// Number with an allowed unit, a sizing keyword, or calc()/min()/max()/clamp()/var().
	static function sanitizeLength( $value, $default = '' ) {
		$value = self::sanitizeValue( $value, $default );
		if ( '' === $value || null === $value ) {
			return $value;
		}
		return preg_match( '/^(-?(\d+|\d*\.\d+)(px|em|rem|%|vh|vw|vmin|vmax|svh|lvh|dvh|svw|lvw|dvw|ch|ex|pt|pc|cm|mm|in|q)?|auto|none|inherit|initial|unset|revert|fit-content|min-content|max-content|(calc|min|max|clamp|var)\(.*\))$/is', (string) $value ) ? $value : $default;
	}

	// A single CSS keyword such as cover, center, multiply.
	static function sanitizeKeyword( $value, $default = '' ) {
		if ( '' === $value || null === $value ) {
			return $value;
		}
		return is_string( $value ) && preg_match( '/^[a-z-]+$/i', $value ) ? $value : $default;
	}

	// URL for an unquoted CSS url(): allowed protocols only and nothing that can close url().
	static function sanitizeUrl( $url ) {
		if ( '' === $url || null === $url ) {
			return $url;
		}
		if ( ! is_string( $url ) ) {
			return '';
		}
		$url = esc_url_raw( $url );
		return preg_match( '/[\s()\'"\\\\]/', $url ) ? '' : $url;
	}

	static function getBackgroundCSS( $bg, $isSolid = true, $isGradient = true, $isImage = true ) {
		extract( $bg );
		$type = $type ?? 'solid';
		$color = self::sanitizeColor( $color ?? '#000000b3', '#000000b3' );
		$gradient = self::sanitizeColor( $gradient ?? 'linear-gradient(135deg, #0040E3, #18D4FD)', 'linear-gradient(135deg, #0040E3, #18D4FD)' );
		$image = $image ?? [];
		$position = self::sanitizeValue( $position ?? 'center center', 'center center' );
		$attachment = self::sanitizeValue( $attachment ?? '' );
		$repeat = self::sanitizeValue( $repeat ?? '' );
		$size = self::sanitizeValue( $size ?? '' );
		$overlayColor = self::sanitizeColor( $overlayColor ?? '' );

		if ( 'gradient' === $type && $isGradient ) {
			$styles = self::isValidCSS('background', $gradient);
		} elseif ( 'image' === $type && $isImage ) {
			$imgUrl = self::sanitizeUrl( $image['url'] ?? '' );
			$styles = "background: url($imgUrl);"
				. self::isValidCSS('background-color', $overlayColor)
				. self::isValidCSS('background-position', $position)
				. self::isValidCSS('background-size', $size)
				. self::isValidCSS('background-repeat', $repeat)
				. self::isValidCSS('background-attachment', $attachment)
				. "background-blend-mode: overlay;";
		} else {
			$styles = $isSolid ? self::isValidCSS('background', $color) : '';
		}

		return $styles;
	}

	static function getSpaceCSS( $space ) {
		extract( $space );
		$side = $side ?? 2;
		$vertical = self::sanitizeLength( $vertical ?? '0px', '0px' );
		$horizontal = self::sanitizeLength( $horizontal ?? '0px', '0px' );
		$top = self::sanitizeLength( $top ?? '0px', '0px' );
		$right = self::sanitizeLength( $right ?? '0px', '0px' );
		$bottom = self::sanitizeLength( $bottom ?? '0px', '0px' );
		$left = self::sanitizeLength( $left ?? '0px', '0px' );
	
		$styles = ( 2 === $side ) ? "$vertical $horizontal" : "$top $right $bottom $left";

		return $styles;
	}
}