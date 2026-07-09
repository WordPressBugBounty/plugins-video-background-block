<?php
/**
 * Plugin Name: Video Background Block
 * Description: Use video as background in section.
 * Version: 2.0.2
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: video-background
 * @fs_free_only /vendor/freemius-lite
 */

// ABS PATH
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Optional: deactivate free/pro duplicates on activation (your logic kept)
 */
register_activation_hook( __FILE__, function () {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( is_plugin_active( 'video-background-block/index.php' ) ) {
		deactivate_plugins( 'video-background-block/index.php' );
	}
	if ( is_plugin_active( 'video-background-block-pro/index.php' ) ) {
		deactivate_plugins( 'video-background-block-pro/index.php' );
	}
});

if ( function_exists( 'bvbb_fs' ) ) {
	bvbb_fs()->set_basename( true, __FILE__ );
} else {

	// Constant
	define(
		'VBB_VERSION',
		isset( $_SERVER['HTTP_HOST'] ) &&
		(
			'localhost' === $_SERVER['HTTP_HOST'] ||
			'plugins.local' === $_SERVER['HTTP_HOST']
		)
			? time()
			: '2.0.2'
	);

	define( 'VBB_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'VBB_DIR_PATH', plugin_dir_path( __FILE__ ) );



	if ( ! function_exists( 'bvbb_fs' ) ) {
		function bvbb_fs() {
			global $bvbb_fs;

			if ( ! isset( $bvbb_fs ) ) {
				$fs_lite = VBB_DIR_PATH . '/vendor/freemius-lite/start.php';
				if ( file_exists( $fs_lite ) ) {
					require_once $fs_lite;
				} else {
					// Freemius files missing -> avoid fatal
					return null;
				}

				$bvbbConfig = array(
					'id'                  => '20161',
					'slug'                => 'video-background-block',
					'premium_slug'        => 'video-background-block-pro',
					'type'                => 'plugin',
					'public_key'          => 'pk_c450cd26984f6b711540a633d4fa1',
					'is_premium'          => false,
					'premium_suffix'      => 'Pro',
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'menu'                => array(
						'slug'       => 'video-background-block-dashboard',
						'first-path' => 'admin.php?page=video-background-block-dashboard#/welcome',
						'contact'    => false,
						'support'    => false,
					),
				);

				$bvbb_fs =  fs_lite_dynamic_init( $bvbbConfig );
			}

			return $bvbb_fs;
		}

		// Init Freemius.
		bvbb_fs();
		do_action( 'bvbb_fs_loaded' );
	}

	

	// Required files
	require_once VBB_DIR_PATH . 'includes/GetCSS.php';
	 require_once VBB_DIR_PATH . 'includes/adminMenu.php';


	if ( ! class_exists( 'VBBPlugin' ) ) {
		class VBBPlugin {
			public function __construct() {
				add_action( 'init', array( $this, 'onInit' ) );

				add_filter( 'plugin_action_links', [$this, 'pluginActionLinks'], 10, 2 );
			add_filter( 'default_title', [$this, 'defaultTitle'], 10, 2 );
			add_filter( 'default_content', [$this, 'defaultContent'], 10, 2 );
			}


			public function onInit() {
				register_block_type( __DIR__ . '/build' );
			}
		
			function defaultTitle( $title, $post ) {
			if ( 'page' === $post->post_type && isset( $_GET['title'] ) ) {
				return sanitize_text_field( wp_unslash( $_GET['title'] ) );
			}
			return $title;
		}

		function defaultContent( $content, $post ) {
			if ( 'page' === $post->post_type && isset( $_GET['content'] ) ) {
				return wp_unslash( $_GET['content'] );
			}
			return $content;
		}

		function pluginActionLinks( $links, $file ) {
			if( plugin_basename( __FILE__ ) === $file ) {
				$goProLink = admin_url( 'edit.php?post_type=apb&page=video-background-block-dashboard#/pricing' );
				$helpDemosLink = admin_url( 'edit.php?post_type=apb&page=video-background-block-dashboard' );

					$links['go-pro'] = sprintf( '<a href="%s" style="%s">%s</a>', $goProLink, 'color:#146ef5;font-weight:bold', __( 'Go Pro', 'video-background-block-dashboard' ) );
					$links['help-and-demos'] = sprintf( '<a href="%s" style="%s">%s</a>', $helpDemosLink, 'color:#FF7A00;font-weight:bold', __( 'Help & Demos', 'video-background-block-dashboard' ) );
			}
 
			return $links;
		}

		}
		new VBBPlugin;
	}
}
