<?php
/**
 * Plugin Name: Video Background Block
 * Description: Use video as background in section.
 * Version: 1.0.5
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: video-background
 */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'bvbb_fs' ) ) {
	bvbb_fs()->set_basename( true, __FILE__ );
}else{
	// Constant
	define( 'VBB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.0.3' );
	define( 'VBB_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'VBB_DIR_PATH', plugin_dir_path( __FILE__ ) );

	function bvbb_fs() {
		global $bvbb_fs;

		if ( ! isset( $bvbb_fs ) ) {
			require_once dirname(__FILE__) . '/vendor/freemius-lite/start.php';

			$bvbbConfig = [
				'id'				=> '20161',
				'slug'				=> 'video-background-block',
				'type'				=> 'plugin',
				'public_key'		=> 'pk_c450cd26984f6b711540a633d4fa1',
				'is_premium'		=> false,
				'has_addons'		=> false,
				'has_paid_plans'	=> false,
				'menu'				=> [
					'first-path'	=> 'plugins.php',
					'account'		=> false,
					'contact'		=> false,
					'support'		=> false
				]
			];

			$bvbb_fs = fs_lite_dynamic_init( $bvbbConfig );
		}

		return $bvbb_fs;
	}

	bvbb_fs();
	do_action( 'bvbb_fs_loaded' );

	// Required files
	require_once VBB_DIR_PATH . 'includes/GetCSS.php';

	if( !class_exists( 'VBBPlugin' ) ){
		class VBBPlugin{
			public function __construct(){
				add_action( 'init', [$this, 'onInit'] );
			}

			function onInit(){
				register_block_type( __DIR__ . '/build' );
			}
		}
		new VBBPlugin;
	}
}