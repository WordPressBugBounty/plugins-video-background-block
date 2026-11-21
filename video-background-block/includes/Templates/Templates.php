<?php

namespace VBB\Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once VBB_DIR_PATH . 'includes/Templates/Image.php';

class Templates {

	const PLUGIN_SLUG = 'video-background-block';

	const API = 'https://templates.bplugins.com/wp-json/gutenberg-templates/v1';

	const TIMEOUT = 8;

	public function __construct() {
		add_action( 'wp_ajax_vbb_templates_main', [ $this, 'templatesMain' ] );
		add_action( 'wp_ajax_vbb_templates', [ $this, 'templates' ] );
		add_action( 'wp_ajax_vbb_template_import', [ $this, 'templateImport' ] );
		add_action( 'wp_ajax_vbb_template_counts', [ $this, 'templateCounts' ] );
		add_action( 'wp_ajax_vbb_template_favorites', [ $this, 'templateFavorites' ] );
	}

	const MAX_PER_PAGE = 50;

	const MAX_FAVORITES = 200;

	/** Per-category template counts for VBB only, filled by pluginCategories(). */
	private static $catCounts = [];

	private static function guard() {
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );

		if ( ! wp_verify_nonce( $nonce, 'vbb_template' ) ) {
			wp_send_json_error( 'Invalid Request' );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Insufficient Permissions' );
		}
	}

	private static function type() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in guard().
		$type = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'patterns' ) );

		return 'pages' === $type ? 'pages' : 'patterns';
	}

	private static function api() {
		return untrailingslashit( (string) apply_filters( 'vbb_template_library_api', self::API ) );
	}

	private static function getLocalData() {
		static $data = null;
		if ( null === $data ) {
			$file = __DIR__ . '/templates-data.json';
			if ( file_exists( $file ) ) {
				$data = json_decode( file_get_contents( $file ) );
			}
		}
		return $data;
	}

	private static function getLocalTaxonomies() {
		static $tax = null;
		if ( null === $tax ) {
			$file = __DIR__ . '/taxonomies-data.json';
			if ( file_exists( $file ) ) {
				$tax = json_decode( file_get_contents( $file ) );
			}
		}
		return $tax;
	}

	private static function get( $path, $query = [] ) {
		$api  = self::api();
		$rest = untrailingslashit( rest_url() );

		if ( 0 === strpos( $api, $rest ) ) {
			$route   = '/' . trim( substr( $api, strlen( $rest ) ), '/' ) . $path;
			$request = new \WP_REST_Request( 'GET', $route );
			$request->set_query_params( $query );
			$response = rest_do_request( $request );

			if ( $response->is_error() ) {
				return $response->as_error();
			}

			return json_decode( wp_json_encode( rest_get_server()->response_to_data( $response, false ) ) );
		}

		$response = wp_remote_get( add_query_arg( $query, $api . $path ), [ 'timeout' => self::TIMEOUT ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	public function templatesMain() {
		self::guard();

		$type       = self::type();
		$typeFilter = 'patterns' === $type ? 'patterns-category' : 'pages-category';

		// Remote template server first (templates.bplugins.com, same as B Slider).
		$body = self::get( "/taxonomy/taxonomies/plugin,type,{$typeFilter}" );
		$mine = $this->pluginCategories( $type );

		if ( ! is_wp_error( $body ) && $body && isset( $body->{$typeFilter} ) && ! empty( $mine ) ) {
			// Keep only VBB's own categories. 'free'/'pro' are access filters, not categories: the
			// sidebar adds their server-wide count (shared by every bPlugins product) to "All".
			$body->{$typeFilter} = array_values( array_filter( $body->{$typeFilter}, function ( $cat ) use ( $mine ) {
				return in_array( $cat->name, $mine, true ) && 'free' !== $cat->name && 'pro' !== $cat->name;
			} ) );

			// Term counts on the server include other plugins' templates — use VBB's own counts so the
			// first-load "All" total matches the real number before the access counts arrive.
			foreach ( $body->{$typeFilter} as $cat ) {
				if ( isset( self::$catCounts[ $cat->name ] ) ) {
					$cat->count = self::$catCounts[ $cat->name ];
				}
			}

			if ( ! empty( $body->{$typeFilter} ) ) {
				wp_send_json_success( $body );
			}
		}

		// Fallback to bundled local taxonomies (26 categories for video-background-block)
		$localTax = self::getLocalTaxonomies();
		if ( $localTax && isset( $localTax->{$typeFilter} ) ) {
			wp_send_json_success( $localTax );
		}

		wp_send_json_error( 'Failed to load template categories' );
	}

	private function pluginCategories( $type ) {
		$plugin = self::PLUGIN_SLUG;

		$body = self::get( '/blocks', [
			'type'   => $type,
			'start'  => 0,
			'end'    => 10000,
			'plugin' => $plugin,
			'fields' => 'category',
			'limit'  => 1000,
		] );

		if ( ! is_wp_error( $body ) && $body && isset( $body->patterns ) && ! empty( $body->patterns ) ) {
			$categories = [];
			foreach ( $body->patterns as $pattern ) {
				if ( isset( $pattern->category ) && is_array( $pattern->category ) ) {
					$categories = array_merge( $categories, $pattern->category );
				}
			}
			self::$catCounts = array_count_values( $categories );
			return array_unique( $categories );
		}

		$local = self::getLocalData();
		if ( $local && isset( $local->patterns ) ) {
			$categories = [];
			foreach ( $local->patterns as $pattern ) {
				if ( isset( $pattern->category ) && is_array( $pattern->category ) ) {
					$categories = array_merge( $categories, $pattern->category );
				}
			}
			return array_unique( $categories );
		}

		return [];
	}

	public function templates() {
		self::guard();

		$type       = self::type();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in guard().
		$category   = sanitize_text_field( wp_unslash( $_POST['category'] ?? 'all' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in guard().
		$pageNumber = max( 1, absint( wp_unslash( $_POST['pageNumber'] ?? 1 ) ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in guard().
		$perPage    = min( self::MAX_PER_PAGE, max( 1, absint( wp_unslash( $_POST['perPage'] ?? 9 ) ) ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in guard().
		$search     = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$start      = $pageNumber - 1;
		$plugin     = self::PLUGIN_SLUG;

		$body = self::get( '/blocks', [
			'type'     => $type,
			'start'    => $start,
			'end'      => $pageNumber,
			'limit'    => $perPage,
			'plugin'   => $plugin,
			'category' => rawurlencode( $category ),
			'keywords' => rawurlencode( $search ),
			'fields'   => 'ID,category,keywords,original_content,thumbnail,title,type,url,preview_url',
		] );

		if ( ! is_wp_error( $body ) && $body && isset( $body->patterns ) && ! empty( $body->patterns ) ) {
			wp_send_json_success( self::lockPro( $body ) );
		}

		// Fallback to bundled local templates
		$local = self::getLocalData();
		if ( $local && isset( $local->patterns ) ) {
			$items = $local->patterns;

			if ( 'all' !== $category && '' !== $category ) {
				$items = array_values( array_filter( $items, function ( $item ) use ( $category ) {
					return isset( $item->category ) && is_array( $item->category ) && in_array( $category, $item->category, true );
				} ) );
			}

			if ( '' !== $search ) {
				$terms = array_filter( array_map( 'trim', explode( ' ', strtolower( $search ) ) ) );
				$items = array_values( array_filter( $items, function ( $item ) use ( $terms ) {
					$cats = isset( $item->category ) && is_array( $item->category ) ? implode( ' ', $item->category ) : '';
					$keys = isset( $item->keywords ) && is_array( $item->keywords ) ? implode( ' ', $item->keywords ) : '';
					$haystack = strtolower( ( $item->title ?? '' ) . ' ' . $cats . ' ' . $keys );
					foreach ( $terms as $t ) {
						if ( false !== strpos( $haystack, $t ) ) {
							return true;
						}
					}
					return false;
				} ) );
			}

			$total = count( $items );
			$paged = array_slice( $items, $start * $perPage, $perPage );

			$response = (object) [
				'count'    => $total,
				'patterns' => $paged,
			];

			wp_send_json_success( self::lockPro( $response ) );
		}

		wp_send_json_error( 'Failed to load templates' );
	}

	/** Free plugin: Pro templates are always locked (hardcoded, no licence check) — their content is never sent. */
	private static function lockPro( $body ) {
		if ( ! $body || ! isset( $body->patterns ) || ! is_array( $body->patterns ) ) {
			return $body;
		}

		foreach ( $body->patterns as $pattern ) {
			if ( isset( $pattern->category ) && is_array( $pattern->category ) && in_array( 'pro', $pattern->category, true ) ) {
				$pattern->original_content = '';
			}
		}

		return $body;
	}

	public function templateImport() {
		self::guard();

		try {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- serialized block markup: kses strips HTML comments, which are the block delimiters, so sanitizing here would break every template. Gated by the nonce and capability checks above.
			$content = wp_unslash( $_POST['original_content'] ?? '' );

			$data = current_user_can( 'upload_files' ) ? Image::instance()->maybe_import_images( $content ) : $content;

			wp_send_json_success( $data );
		} catch ( \Throwable $th ) {
			wp_send_json_error( $th->getMessage() );
		}
	}

	public function templateCounts() {
		self::guard();

		$type   = self::type();
		$plugin = self::PLUGIN_SLUG;

		$body = self::get( '/blocks', [
			'type'   => $type,
			'plugin' => $plugin,
			'fields' => 'ID,category',
			'limit'  => 10000,
			'start'  => 0,
			'end'    => 10000,
		] );

		if ( ! is_wp_error( $body ) && $body && isset( $body->patterns ) && ! empty( $body->patterns ) ) {
			wp_send_json_success( self::computeCounts( $body->patterns ) );
		}

		// Fallback to bundled local templates
		$local = self::getLocalData();
		if ( $local && isset( $local->patterns ) ) {
			wp_send_json_success( self::computeCounts( $local->patterns ) );
		}

		wp_send_json_error( 'Failed to fetch templates' );
	}

	private static function computeCounts( $patterns ) {
		$counts = [
			'all'        => 0,
			'free'       => 0,
			'pro'        => 0,
			'categories' => [],
		];

		foreach ( $patterns as $template ) {
			$cats  = isset( $template->category ) && is_array( $template->category ) ? $template->category : [];
			$isPro = in_array( 'pro', $cats, true );

			$counts['all']++;
			$counts[ $isPro ? 'pro' : 'free' ]++;

			foreach ( $cats as $cat ) {
				if ( 'free' === $cat || 'pro' === $cat ) {
					continue;
				}

				if ( ! isset( $counts['categories'][ $cat ] ) ) {
					$counts['categories'][ $cat ] = [ 'total' => 0, 'free' => 0, 'pro' => 0 ];
				}

				$counts['categories'][ $cat ]['total']++;
				$counts['categories'][ $cat ][ $isPro ? 'pro' : 'free' ]++;
			}
		}

		return $counts;
	}

	public function templateFavorites() {
		self::guard();

		$optionKey = 'vbbFavoritesTemplates';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in guard().
		if ( isset( $_POST['favorites'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verified in guard(); a JSON string, and every decoded value is hard-cast through absint() below.
			$raw = json_decode( wp_unslash( $_POST['favorites'] ), true );

			$favorites = [
				'patterns' => array_slice( array_values( array_unique( array_map( 'absint', (array) ( $raw['patterns'] ?? [] ) ) ) ), 0, self::MAX_FAVORITES ),
				'pages'    => array_slice( array_values( array_unique( array_map( 'absint', (array) ( $raw['pages'] ?? [] ) ) ) ), 0, self::MAX_FAVORITES ),
			];

			update_option( $optionKey, $favorites, false );
		}

		wp_send_json_success( get_option( $optionKey, [ 'patterns' => [], 'pages' => [] ] ) );
	}
}

new Templates();
