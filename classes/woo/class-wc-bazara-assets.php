<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'BazaraWooAssets' ) ) :

class BazaraWooAssets {
	
	function __construct()	{
		add_action( 'wp_enqueue_scripts', array( $this, 'bazara_frontend_scripts_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'bazara_backend_scripts_styles' ) );


	}
	
	/**
	 * Load styles & scripts
	 */
	public function bazara_frontend_scripts_styles ( $hook ) {
		# none yet
	}

	/**
	 * Load styles & scripts
	 */

	public function is_order_page()
	{
		$screen = get_current_screen();
		if (!is_null($screen) && in_array($screen->id, array(
			"shop_order",
			"edit-shop_order",
			"woocommerce_page_wc-orders"
		)))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	public function bazara_backend_scripts_styles ( $hook ) {
		global $wp_version;
		if ($this->is_order_page() ) {

			wp_enqueue_style(
				'bazara-woo-order-styles',
				PLUGIN_DIR_URL . '/assets/css/order-styles.min.css',
				array(),
				2.0
			);

			// SCRIPTS
			wp_enqueue_script(
				'bazara-wc-order',
				PLUGIN_DIR_URL . '/assets/js/bazara-order-script.js',
				array( 'jquery', 'jquery-blockui' ),
				2.0
			);

			$bulk_actions = array();
			$bulk_actions["bazara_order"] = "Bazara Order";

			
			wp_localize_script(
				'bazara-wc-order',
				'bazara_wc_order_ajax',
				array(
					'ajaxurl'			 => admin_url( 'admin-ajax.php' ), // URL to WordPress ajax handling page  
					'nonce'				 => wp_create_nonce('generate_bazara_order'),
					'bulk_actions'		 => array_keys( $bulk_actions )
				)
			);
		}

	}

}

endif; // class_exists

return new BazaraWooAssets();