<?php
/**
 * Plugin Name: Video Background Block
 * Description: Use video as background in section.
 * Version: 1.0.4
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: video-background
 */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

// Constant
define( 'VBB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.0.3' );
define( 'VBB_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'VBB_DIR_PATH', plugin_dir_path( __FILE__ ) );

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