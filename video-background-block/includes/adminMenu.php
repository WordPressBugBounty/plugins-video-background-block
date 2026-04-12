<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class VBB_Admin_Menu {
	private $post_type = 'vbb';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'adminEnqueueScripts' ] );
		add_action( 'init', [ $this, 'onInit' ] );
		add_action( 'wp_ajax_get_license_status', [ $this, 'getLicenseStatus' ] );
		add_action( 'wp_ajax_activate_freemius_license', [ $this, 'activateFreemiusLicense' ] );
		add_action( 'wp_ajax_deactivate_freemius_license', [ $this, 'deactivateFreemiusLicense' ] );
		add_shortcode( 'vbb', [ $this, 'onAddShortcode' ] );
		add_filter( 'manage_vbb_posts_columns', [ $this, 'managePostsColumns' ], 10 );
		add_action( 'manage_vbb_posts_custom_column', [ $this, 'managePostsCustomColumns' ], 10, 2 );
		add_filter( 'use_block_editor_for_post', [ $this, 'useBlockEditorForPost' ], 999, 2 );
	}

	private function getFreemiusInstance() {
		if ( ! function_exists( 'bvbb_fs' ) ) {
			return null;
		}

		$fs = bvbb_fs();
		return is_object( $fs ) ? $fs : null;
	}

	private function getLicenseData( $fs ) {
		$license_key = '';
		if ( method_exists( $fs, '_get_license' ) ) {
			$license = $fs->_get_license();
			if ( is_object( $license ) && ! empty( $license->secret_key ) ) {
				$license_key = (string) $license->secret_key;
			}
		}

		$is_activated = method_exists( $fs, 'can_use_premium_code' ) ? (bool) $fs->can_use_premium_code() : false;

		return [
			'is_activated' => $is_activated,
			'license_key'  => $license_key,
		];
	}

	public function getLicenseStatus() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized request.', 'video-background' ) ], 403 );
		}

		check_ajax_referer( 'vbb_license_nonce', 'nonce' );

		$fs = $this->getFreemiusInstance();
		if ( ! $fs ) {
			wp_send_json_success( [ 'is_activated' => false, 'license_key' => '' ] );
		}

		wp_send_json_success( $this->getLicenseData( $fs ) );
	}

	public function activateFreemiusLicense() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized request.', 'video-background' ) ], 403 );
		}

		check_ajax_referer( 'vbb_license_nonce', 'nonce' );

		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		$product_id  = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

		if ( empty( $license_key ) ) {
			wp_send_json_error( [ 'message' => __( 'Please enter a license key.', 'video-background' ) ] );
		}

		$fs = $this->getFreemiusInstance();
		if ( ! $fs || ! method_exists( $fs, 'activate_migrated_license' ) ) {
			wp_send_json_error( [ 'message' => __( 'License activation is not available.', 'video-background' ) ] );
		}

		$result = $fs->activate_migrated_license( $license_key, null, $product_id ?: null );
		if ( empty( $result['success'] ) ) {
			$message = ! empty( $result['error'] ) ? $result['error'] : __( 'Activation failed.', 'video-background' );
			wp_send_json_error( [ 'message' => $message ] );
		}

		wp_send_json_success( $this->getLicenseData( $fs ) );
	}

	public function deactivateFreemiusLicense() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized request.', 'video-background' ) ], 403 );
		}

		check_ajax_referer( 'vbb_license_nonce', 'nonce' );

		$fs = $this->getFreemiusInstance();
		if ( ! $fs ) {
			wp_send_json_error( [ 'message' => __( 'License deactivation is not available.', 'video-background' ) ] );
		}

		if ( ! method_exists( $fs, '_deactivate_license' ) ) {
			wp_send_json_error( [ 'message' => __( 'License deactivation is not supported.', 'video-background' ) ] );
		}

		$deactivate = \Closure::bind( function() {
			$this->_deactivate_license( false );
		}, $fs, get_class( $fs ) );

		$deactivate();

		wp_send_json_success( [ 'is_activated' => false, 'license_key' => '' ] );
	}

	public function adminMenu() {
		$cap  = 'manage_options';
		$slug = 'video-background-block-dashboard';

		add_menu_page(
			__( 'Video Background Block', 'video-background' ),
			__( 'Video Background', 'video-background' ),
			$cap,
			$slug,
			[ $this, 'renderDashboardPage' ],
			'dashicons-format-video',
			26
		);

		add_submenu_page(
			$slug,
			__( 'Help and Demos', 'video-background' ),
			__( 'Help and Demos', 'video-background' ),
			$cap,
			$slug,
			[ $this, 'renderDashboardPage' ]
		);
	}

	public function renderDashboardPage() { ?>
		<div
			id="video-background-block-dashboard"
			data-info="<?php echo esc_attr( wp_json_encode( [
				'version'   => defined( 'VBB_VERSION' ) ? VBB_VERSION : '1.0.0',
				'isPremium' => function_exists( 'bpvbbIsPremium' ) ? bpvbbIsPremium() : false,
				'hasPro'    => defined( 'VIDEO_BACKGROUND_BLOCK_PRO' ) ? (bool) VIDEO_BACKGROUND_BLOCK_PRO : false,
				'licenseActiveNonce' => wp_create_nonce('vbbLicenseActive'),

			] ) ); ?>"
		></div>
	<?php }

	public function onInit() {
		register_post_type( $this->post_type, [
			'labels' => [
				'name'          => __( 'Shortcodes', 'video-background' ),
				'singular_name' => __( 'Shortcode', 'video-background' ),
				'add_new'       => __( 'Add New', 'video-background' ),
				'add_new_item'  => __( 'Add New Shortcode', 'video-background' ),
				'edit_item'     => __( 'Edit Shortcode', 'video-background' ),
				'new_item'      => __( 'New Shortcode', 'video-background' ),
				'view_item'     => __( 'View Shortcode', 'video-background' ),
				'search_items'  => __( 'Search Shortcodes', 'video-background' ),
				'not_found'     => __( 'No Shortcodes found.', 'video-background' ),
			],
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'show_in_menu'        => 'video-background-block-dashboard',
			'menu_position'       => 14,
			'capability_type'     => 'page',
			'supports'            => [ 'title', 'editor' ],
			'template'            => [ [ 'vbb/video-bg' ] ],
			'template_lock'       => 'all',
		] );
	}

	public function onAddShortcode( $atts ) {
		$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'vbb' );
		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			return '';
		}
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== $this->post_type ) {
			return '';
		}
		if ( post_password_required( $post ) ) {
			return get_the_password_form( $post );
		}
		$status = get_post_status( $post );
		if ( 'publish' === $status ) {
			return $this->displayContent( $post );
		}
		if ( 'private' === $status ) {
			return current_user_can( 'read_post', $post_id ) ? $this->displayContent( $post ) : '';
		}
		if ( in_array( $status, [ 'draft', 'pending', 'future' ], true ) ) {
			return current_user_can( 'edit_post', $post_id ) ? $this->displayContent( $post ) : '';
		}
		return '';
	}

	private function displayContent( $post ) {
		if ( empty( $post->post_content ) ) {
			return '';
		}
		$old_post        = $GLOBALS['post'] ?? null;
		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		$content = apply_filters( 'the_content', $post->post_content );
		wp_reset_postdata();
		$GLOBALS['post'] = $old_post;
		return $content;
	}

	public function managePostsColumns( $defaults ) {
		unset( $defaults['date'] );
		$defaults['shortcode'] = __( 'Shortcode', 'video-background' );
		$defaults['date']      = __( 'Date', 'video-background' );
		return $defaults;
	}

	public function managePostsCustomColumns( $column_name, $post_ID ) {
		if ( 'shortcode' === $column_name ) {
			$sc = sprintf( '[vbb id="%d"]', (int) $post_ID );
			echo '<div class="bPlAdminShortcode" id="bPlAdminShortcode-' . esc_attr( $post_ID ) . '">
				<input readonly value="' . esc_attr( $sc ) . '" onclick="copyBPlAdminShortcode(\'' . esc_attr( $post_ID ) . '\')">
				<span class="tooltip">' . esc_html__( 'Copy To Clipboard', 'video-background' ) . '</span>
			</div>';
		}
	}

	public function useBlockEditorForPost( $use, $post ) {
		if ( is_object( $post ) && isset( $post->post_type ) && $this->post_type === $post->post_type ) {
			return true;
		}
		return $use;
	}

	public function adminEnqueueScripts( $hook ) {
		if ( false !== strpos( $hook, 'video-background-block-dashboard' ) ) {
			if ( file_exists( VBB_DIR_PATH . 'build/admin-dashboard.asset.php' ) ) {
				$asset = include VBB_DIR_PATH . 'build/admin-dashboard.asset.php';
				$deps  = array_unique( array_merge( $asset['dependencies'], [ 'wp-util' ] ) );
				wp_enqueue_style( 'vbb-admin-dashboard', VBB_DIR_URL . 'build/admin-dashboard.css', [], VBB_VERSION );
				wp_enqueue_script( 'vbb-admin-dashboard', VBB_DIR_URL . 'build/admin-dashboard.js', $deps, $asset['version'], true );
				wp_localize_script( 'vbb-admin-dashboard', 'apbAdmin', [
					'nonce' => wp_create_nonce( 'vbb_license_nonce' ),
				] );
				wp_set_script_translations( 'vbb-admin-dashboard', 'video-background', VBB_DIR_PATH . 'languages' );
			}
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->post_type !== $this->post_type ) {
			return;
		}

		if ( in_array( $hook, [ 'edit.php', 'post.php', 'post-new.php' ], true ) ) {
			$asset = file_exists( VBB_DIR_PATH . 'build/admin-post.asset.php' )
				? include VBB_DIR_PATH . 'build/admin-post.asset.php'
				: [ 'dependencies' => [], 'version' => VBB_VERSION ];
			wp_enqueue_style( 'vbb-admin-post', VBB_DIR_URL . 'build/admin-post.css', [], VBB_VERSION );
			wp_enqueue_script( 'vbb-admin-post', VBB_DIR_URL . 'build/admin-post.js', $asset['dependencies'], $asset['version'], true );
			wp_set_script_translations( 'vbb-admin-post', 'video-background', VBB_DIR_PATH . 'languages' );
		}
	}
}

new VBB_Admin_Menu(); 
