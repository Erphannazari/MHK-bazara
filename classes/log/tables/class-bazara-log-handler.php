<?php
namespace bazara_Log\log\tables;
use bazara_Log\log\tables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       https://www.nuancedesignstudio.in
 * @since      1.0.0
 *
 * @author    Karan NA Gupta
 */
class BazaraLog {

  /**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object
	 */
	private $log;
	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The text domain of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_text_domain    The text domain of this plugin.
	 */
	private $plugin_text_domain;

	/**
	 * WP_List_Table object
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      logs_list_table    $logs_list_table
	 */
	private $logs_table;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string $plugin_name	The name of this plugin.
	 * @param    string $version	The version of this plugin.
	 * @param	 string $plugin_text_domain	The text domain of this plugin
	 */
	public function __construct( $version, $plugin_text_domain ) {

		$this->version = $version;
		$this->plugin_text_domain = $plugin_text_domain;

	}
  public function init() {
    add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu') );
  }


	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/nds-wp-list-table-demo-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		$params = array ( 'ajaxurl' => admin_url( 'admin-ajax.php' ) );
		wp_enqueue_script( 'nds_ajax_handle', plugin_dir_url( __FILE__ ) . 'js/nds-wp-list-table-demo-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( 'nds_ajax_handle', 'params', $params );

	}

	/**
	 * Callback for the user sub-menu in define_admin_hooks() for class Init.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
    $page_hook = add_submenu_page(
    		'bazara',
    		"جدول عملکرد",
    		"گزارش عملکرد",
    		'manage_options',
    		'bazara_logs',
			array( $this, 'load_bazara_log_table' )
		);
  	/*
		 * The $page_hook_suffix can be combined with the load-($page_hook) action hook
		 * https://codex.wordpress.org/Plugin_API/Action_Reference/load-(page)
		 *
		 * The callback below will be called when the respective page is loaded
		 *
		 */
		add_action( 'load-'.$page_hook, array( $this, 'load_bazara_logs_table_screen_options' ) );

	}

	/**
	* Screen options for the List Table
	*
	* Callback for the load-($page_hook_suffix)
	* Called when the plugin page is loaded
	*
	* @since    1.0.0
	*/
	public function load_bazara_logs_table_screen_options() {

		$arguments	=	array(
						'label'		=>	"تعداد در هر صفحه",
						'default'	=>	25,
						'option'	=>	'users_per_page'
					);

		add_screen_option( 'per_page', $arguments );

		// instantiate the User List Table
		$this->logs_table = new bazara_log_Table( $this->plugin_text_domain );

	}

	/*
	 * Display the User List Table
	 *
	 * Callback for the add_users_page() in the add_plugin_admin_menu() method of this class.
	 *
	 * @since	1.0.0
	 */
	public function load_bazara_log_table(){

		// query, filter, and sort the data
		$this->logs_table->prepare_items();

		// render the List Table
    include_once( plugin_dir_path(dirname(__FILE__) ) .'/tables/partials-wp-bazara-logs-table-display.php' );
	}

  public function show_log_detail_view(){
      include_once( plugin_dir_path(dirname(__FILE__) ) .'/tables/partials-wp-log-detail.php' );
  }

  public function load_log_by_id($id){
      global $wpdb;
  }
  public function get_log(){
      return $this->$log;
  }
}
