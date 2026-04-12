<?php

/**
 * Plugin Name: Video Background Block
 * Description: Use video as background in section.
 * Version: 2.0.1
 * Requires at least: 5.8
 * Requires PHP:      7.1
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: video-background
 */
// ABS PATH
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Optional: deactivate free/pro duplicates on activation (your logic kept)
 */
register_activation_hook( __FILE__, function () {
    if ( !function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if ( is_plugin_active( 'video-background-block/index.php' ) ) {
        deactivate_plugins( 'video-background-block/index.php' );
    }
    if ( is_plugin_active( 'video-background-block-pro/index.php' ) ) {
        deactivate_plugins( 'video-background-block-pro/index.php' );
    }
} );
if ( function_exists( 'bvbb_fs' ) ) {
    bvbb_fs()->set_basename( false, __FILE__ );
} else {
    // Constant
    define( 'VBB_VERSION', ( isset( $_SERVER['HTTP_HOST'] ) && ('localhost' === $_SERVER['HTTP_HOST'] || 'plugins.local' === $_SERVER['HTTP_HOST']) ? time() : '2.0.1' ) );
    define( 'VBB_DIR_URL', plugin_dir_url( __FILE__ ) );
    define( 'VBB_DIR_PATH', plugin_dir_path( __FILE__ ) );
    // ✅ FIX: correct vendor path
    define( 'VIDEO_BACKGROUND_BLOCK_PRO', file_exists( VBB_DIR_PATH . 'vendor/freemius/start.php' ) );
    if ( !function_exists( 'bvbb_fs' ) ) {
        function bvbb_fs() {
            global $bvbb_fs;
            if ( !isset( $bvbb_fs ) ) {
                $fs_lite = VBB_DIR_PATH . 'vendor/freemius-lite/start.php';
                $fs_pro = VBB_DIR_PATH . 'vendor/freemius/start.php';
                if ( VIDEO_BACKGROUND_BLOCK_PRO && file_exists( $fs_pro ) ) {
                    require_once $fs_pro;
                } elseif ( file_exists( $fs_lite ) ) {
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
                $bvbb_fs = ( VIDEO_BACKGROUND_BLOCK_PRO && function_exists( 'fs_dynamic_init' ) ? fs_dynamic_init( $bvbbConfig ) : (( function_exists( 'fs_lite_dynamic_init' ) ? fs_lite_dynamic_init( $bvbbConfig ) : null )) );
            }
            return $bvbb_fs;
        }

        // Init Freemius.
        bvbb_fs();
        do_action( 'bvbb_fs_loaded' );
    }
    function bpvbbIsPremium() {
        return ( VIDEO_BACKGROUND_BLOCK_PRO && bvbb_fs() ? bvbb_fs()->can_use_premium_code() : false );
    }

    // Required files
    require_once VBB_DIR_PATH . 'includes/GetCSS.php';
    require_once VBB_DIR_PATH . 'includes/adminMenu.php';
    if ( VIDEO_BACKGROUND_BLOCK_PRO ) {
        require_once VBB_DIR_PATH . 'includes/LicenseActivation.php';
    }
    if ( !class_exists( 'VBBPlugin' ) ) {
        class VBBPlugin {
            public function __construct() {
                add_action( 'init', array($this, 'onInit') );
                add_action( 'enqueue_block_editor_assets', array($this, 'smbEnqueueBlockEditorAssets') );
                add_filter(
                    'plugin_action_links',
                    [$this, 'pluginActionLinks'],
                    10,
                    2
                );
                add_filter(
                    'default_title',
                    [$this, 'defaultTitle'],
                    10,
                    2
                );
                add_filter(
                    'default_content',
                    [$this, 'defaultContent'],
                    10,
                    2
                );
            }

            public function onInit() {
                register_block_type( __DIR__ . '/build' );
            }

            public function smbEnqueueBlockEditorAssets() {
                $block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'vbb/video-bg' );
                if ( !$block_type || empty( $block_type->editor_script_handles ) ) {
                    return;
                }
                $editor_script_handle = $block_type->editor_script_handles[0];
                wp_add_inline_script( $editor_script_handle, 'const vbbpipecheck = ' . wp_json_encode( bpvbbIsPremium() ) . ';', 'before' );
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
                if ( plugin_basename( __FILE__ ) === $file ) {
                    $goProLink = admin_url( 'edit.php?post_type=apb&page=video-background-block-dashboard#/pricing' );
                    $helpDemosLink = admin_url( 'edit.php?post_type=apb&page=video-background-block-dashboard' );
                    if ( !bpvbbIsPremium() ) {
                        $links['go-pro'] = sprintf(
                            '<a href="%s" style="%s">%s</a>',
                            $goProLink,
                            'color:#146ef5;font-weight:bold',
                            __( 'Go Pro', 'video-background-block-dashboard' )
                        );
                    }
                    $links['help-and-demos'] = sprintf(
                        '<a href="%s" style="%s">%s</a>',
                        $helpDemosLink,
                        'color:#FF7A00;font-weight:bold',
                        __( 'Help & Demos', 'video-background-block-dashboard' )
                    );
                }
                return $links;
            }

        }

        new VBBPlugin();
    }
}