<?php

namespace bazara_Log\log\tables;
use bazara_Log\log\tables;
/**
 * Class for displaying registered WordPress Users
 * in a WordPress-like Admin Table with row actions to
 * perform user meta opeations
 *
 *
 * @link       http://nuancedesignstudio.in
 * @since      1.0.0
 *
 * @author     Karan NA Gupta
 */
class bazara_log_Table extends WP_List_Table  {

	/**
	 * The text domain of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_text_domain    The text domain of this plugin.
	 */
	protected $plugin_text_domain;

    /*
	 * Call the parent constructor to override the defaults $args
	 *
	 * @param string $plugin_text_domain	Text domain of the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $plugin_text_domain ) {

		$this->plugin_text_domain = $plugin_text_domain;

		parent::__construct( array(
				'plural'	=>	'logs',	// Plural value used for labels and the objects being listed.
				'singular'	=>	'log',		// Singular label for an object being listed, e.g. 'post'.
				'ajax'		=>	false,		// If true, the parent class will call the _js_vars() method in the footer
			) );
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * Query, filter data, handle sorting, and pagination, and any other data-manipulation required prior to rendering
	 *
	 * @since   1.0.0
	 */
	public function prepare_items() {

		// check if a search was performed.
		$user_search_key = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

		$this->_column_headers = $this->get_column_info();

		// check and process any actions such as bulk actions.
		$this->handle_table_actions();

		// fetch table data
		$table_data = $this->fetch_table_data();
		// filter the data in case of a search.
		if( $user_search_key ) {
			$table_data = $this->filter_table_data( $table_data, $user_search_key );
		}

		// required for pagination
		$users_per_page = $this->get_items_per_page( 'users_per_page' );
		$table_page = $this->get_pagenum();

		// provide the ordered data to the List Table.
		// we need to manually slice the data based on the current pagination.
		$this->items = array_slice( $table_data, ( ( $table_page - 1 ) * $users_per_page ), $users_per_page );

		// set the pagination arguments
		$total_users = count( $table_data );
		$this->set_pagination_args( array (
					'total_items' => $total_users,
					'per_page'    => $users_per_page,
					'total_pages' => ceil( $total_users/$users_per_page )
				) );
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_columns() {

		$table_columns = array(
			'cb'				=> '<input type="checkbox" />', // to display the checkbox.
			'log_date'		=>	__( 'Log Date', $this->plugin_text_domain ),
			'log_title'		=>	__( 'Title', $this->plugin_text_domain ),
			'log_comment'		=>	__( 'Comment', $this->plugin_text_domain ),
			'is_success'		=>	__( 'Is Success', $this->plugin_text_domain )
		);

		return $table_columns;

	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {

		/*
		 * actual sorting still needs to be done by prepare_items.
		 * specify which columns should have the sort icon.
		 *
		 * key => value
		 * column name_in_list_table => columnname in the db
		 */
		$sortable_columns = array (
				'log_date'=>'log_date',
				'log_title'=>'log_title',
				'log_comment'=>'log_comment',
				'is_success'=>'is_success'
			);

		return $sortable_columns;
	}

	/**
	 * Text displayed when no user data is available
	 *
	 * @since   1.0.0
	 *
	 * @return void
	 */
	public function no_items() {
		_e( 'No logs avaliable.', $this->plugin_text_domain );
	}

	/*
	 * Fetch table data from the WordPress database.
	 *
	 * @since 1.0.0
	 *
	 * @return	Array
	 */

	public function fetch_table_data() {

		global $wpdb;

		$wpdb_table = $wpdb->prefix . 'bazara_logs';
		$orderby = ( isset( $_GET['orderby'] ) ) ? esc_sql( $_GET['orderby'] ) : 'log_id';
		$order = ( isset( $_GET['order'] ) ) ? esc_sql( $_GET['order'] ) : 'ASC';

		$user_query = "SELECT * FROM $wpdb_table ORDER BY $orderby $order";

		// query output_type will be an associative array with ARRAY_A.
		$query_results = $wpdb->get_results( $user_query, ARRAY_A  );

		// return result array to prepare_items.
		return $query_results;
	}

	/*
	 * Filter the table data based on the user search key
	 *
	 * @since 1.0.0
	 *
	 * @param array $table_data
	 * @param string $search_key
	 * @returns array
	 */
	public function filter_table_data( $table_data, $search_key ) {
		$filtered_table_data = array_values( array_filter( $table_data, function( $row ) use( $search_key ) {
			foreach( $row as $row_val ) {
				if( stripos( $row_val, $search_key ) !== false ) {
					return true;
				}
			}
		} ) );

		return $filtered_table_data;

	}

	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'log_title':
			case 'log_comment':
				return $item[$column_name];
			default:
			  return $item[$column_name];
		}
	}

	/**
	 * Get value for checkbox column.
	 *
	 * The special 'cb' column
	 *
	 * @param object $item A row's data
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $item ) {
		return sprintf(
				'<label class="screen-reader-text" for="log_' . $item['log_id'] . '">' . sprintf( __( 'Select %s' ), $item['log_id'] ) . '</label>'
				. "<input type='checkbox' name='commissions[]' id='commission_{$item['log_id']}' value='{$item['log_id']}' />"
			);
	}


	/*
	 * Method for rendering the user_login column.
	 *
	 * Adds row action links to the user_login column.
	 *
	 * @param object $item A singular item (one full row's worth of data).
	 * @return string Text to be placed inside the column <td>.
	 *
	 */
	protected function column_log_date( $item ) {
		/*
		 *  Build usermeta row actions.
		 *
		 * e.g. /users.php?page=nds-wp-list-table-demo&action=view_usermeta&user=18&_wpnonce=1984253e5e
		 */

		$admin_page_url =  admin_url( 'admin.php' );

		// row actions to add usermeta.
		$query_args_delete = array(
			'page'		=>  wp_unslash( $_REQUEST['page'] ),
			'action'	=> 'delete',
			'log_id'		=> absint( $item['log_id']),
			'_wpnonce'	=> wp_create_nonce( 'delete_log_nonce' ),
		);
		$delete_link = esc_url( add_query_arg( $query_args_delete, $admin_page_url ) );
		$actions['delete_log'] = '<a href="' . $delete_link . '">' . __( 'Delete', $this->plugin_text_domain ) . '</a>';


		$row_value = '<strong>' . $item['log_date'] . '</strong>';
		return $row_value . $this->row_actions( $actions );
	}

	protected function column_customer_id( $item ) {
		$user = get_userdata($item['customer_id']);
		$name = !empty($user)?$user->first_name.' '.$user->last_name:'';
		$val = $name.'<div style="color:#aaa;font-size:11px;">'.$user->user_login;
		return $val;
	}
	protected function column_affiliate_user_id( $item ) {
		$user = get_userdata($item['affiliate_user_id']);
		$name = !empty($user)?$user->first_name.' '.$user->last_name:'';
		$val = $name.'<div style="color:#aaa;font-size:11px;">'.$user->user_login;
		return $val;
	}
	protected function column_is_success( $item ) {
		return $item['is_success']?__('yes',$this->plugin_text_domain):'<div style="color:red">'.__('no',$this->plugin_text_domain).'</div>';
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @since    1.0.0
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		/*
		 * on hitting apply in bulk actions the url paramas are set as
		 * ?action=bulk-download&paged=1&action2=-1
		 *
		 * action and action2 are set based on the triggers above or below the table
		 *
		 */
		 // $actions = array(
			//  'delete' => __('Delete',$this->$plugin_text_domain)
		 // );

		 // return $actions;
	}

	/**
	 * Process actions triggered by the user
	 *
	 * @since    1.0.0
	 *
	 */
	public function handle_table_actions() {

		/*
		 * Note: Table bulk_actions can be identified by checking $_REQUEST['action'] and $_REQUEST['action2']
		 *
		 * action - is set if checkbox from top-most select-all is set, otherwise returns -1
		 * action2 - is set if checkbox the bottom-most select-all checkbox is set, otherwise returns -1
		 */

		// check for individual row actions
		$the_table_action = $this->current_action();

		if ( 'delete' === $the_table_action ) {
			$nonce = wp_unslash( $_REQUEST['_wpnonce'] );
			// verify the nonce.
			if ( ! wp_verify_nonce( $nonce, 'delete_log_nonce' ) ) {
				$this->invalid_nonce_redirect();
			}
			else {
				global $wpdb;
				$tbl = $wpdb->prefix.'bazara_logs';
				$wpdb->delete( $tbl, array( 'log_id' =>  (int)wp_unslash( $_REQUEST['log_id'] ) ) );
				// $this->graceful_exit();
			}
		}

		if ( 'add_usermeta' === $the_table_action ) {
			$nonce = wp_unslash( $_REQUEST['_wpnonce'] );
			// verify the nonce.
			if ( ! wp_verify_nonce( $nonce, 'add_usermeta_nonce' ) ) {
				$this->invalid_nonce_redirect();
			}
			else {
				$this->page_add_usermeta( absint( $_REQUEST['user_id']) );
				$this->graceful_exit();
			}
		}

		// check for table bulk actions
		if ( ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'bulk-download' ) || ( isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] === 'bulk-download' ) ) {

			$nonce = wp_unslash( $_REQUEST['_wpnonce'] );
			// verify the nonce.
			/*
			 * Note: the nonce field is set by the parent class
			 * wp_nonce_field( 'bulk-' . $this->_args['plural'] );
			 *
			 */
			if ( ! wp_verify_nonce( $nonce, 'bulk-users' ) ) {
				$this->invalid_nonce_redirect();
			}
			else {
				$this->page_bulk_download( $_REQUEST['users']);
				$this->graceful_exit();
			}
		}

	}

	/**
	 * View a user's meta information.
	 *
	 * @since   1.0.0
	 *
	 * @param int $user_id  user's ID
	 */
	public function page_view_usermeta( $user_id ) {

		$user = get_user_by( 'id', $user_id );
		include_once( 'views/partials-wp-list-table-demo-view-usermeta.php' );
	}

	/**
	 * Add a meta information for a user.
	 *
	 * @since   1.0.0
	 *
	 * @param int $user_id  user's ID
	 */

	public function page_add_usermeta( $user_id ) {

		$user = get_user_by( 'id', $user_id );
		include_once( 'views/partials-wp-list-table-demo-add-usermeta.php' );
	}

	/**
	 * Bulk process users.
	 *
	 * @since   1.0.0
	 *
	 * @param array $bulk_user_ids
	 */
	public function page_bulk_download( $bulk_user_ids ) {

		include_once( 'views/partials-wp-list-table-demo-bulk-download.php' );
	}

	/**
	 * Stop execution and exit
	 *
	 * @since    1.0.0
	 *
	 * @return void
	 */
	 public function graceful_exit() {
		 exit;
	 }

	/**
	 * Die when the nonce check fails.
	 *
	 * @since    1.0.0
	 *
	 * @return void
	 */
	 public function invalid_nonce_redirect() {
		wp_die( __( 'Invalid Nonce', $this->plugin_text_domain ),
				__( 'Error', $this->plugin_text_domain ),
				array(
						'response' 	=> 403,
						'back_link' =>  esc_url( add_query_arg( array( 'page' => wp_unslash( $_REQUEST['page'] ) ) , admin_url( 'admin.php' ) ) ),
					)
		);
	 }


}
