<?php
class Bazara_Settings_Reports_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Log', 'sp'),
            'plural'   => __('Logs', 'sp'),
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        $columns = [
            'cb'         => '<input type="checkbox" />',
            'log_id'       => __('شناسه لاگ', 'sp'),
            'log_updated_date'       => __('تاریخ', 'sp'),
            'log_title'        => __('عنوان', 'sp'),
            'log_comment'        => __('توضیجات', 'sp'),
            'is_success'        => __('وضعیت همگام سازی', 'sp')
        ];
        return $columns;
    }
    public function get_sortable_columns() {
        $sortable_columns = array(
            'log_id' => array( 'log_id', true ),
            'log_updated_date' => array( 'log_updated_date', false ),
            'log_title' => array( 'log_title', false ),
            'log_comment' => array( 'log_comment', false ),
            'is_success' => array( 'is_success', false )
        );

        return $sortable_columns;
    }

    public function prepare_items() {

        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'log_per_page', 10 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = self::get_logs( $per_page, $current_page );
    }

    public static function get_logs( $per_page = 5, $page_number = 1 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bazara_logs';
        $query = "SELECT * FROM $table_name";
        $search = isset($_REQUEST['s']) ? $_REQUEST['s'] : false;
        if ($search) {
            $query .= " WHERE log_title LIKE '%" . esc_sql($search) . "%'";
        }

        if ( ! empty( $_REQUEST['orderby'] ) ) {
            $query .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
            $query .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
        }
        else{
            $query .= ' ORDER BY log_id DESC';
        }

        $query .= " LIMIT $per_page";
        $query .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
        $result = $wpdb->get_results($query, 'ARRAY_A');

        return $result;
    }
    public static function record_count() {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}bazara_logs";
        return $wpdb->get_var( $sql );
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'log_id':
            case 'log_updated_date':
            case 'log_title':
            case 'log_comment':
                return $item[$column_name];
            case 'is_success':
                return $item[$column_name] == 0?'سینک نشده':'سینک شده';
            default:
                return print_r($item, true);
        }
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="product[]" value="%s" />',
            $item['ID']
        );
    }

    public function get_bulk_actions() {
        $actions = [
            'bulk-detailSync' => 'detailSync',
            'bulk-stockSync' => 'stockSync',
            'bulk-priceSync' => 'priceSync',
            'bulk-all' => 'all',
        ];

        return $actions;
    }

    public function process_bulk_action() {

        $actions = ['detailSync','stockSync','priceSync','all'];
        $bulkAction = str_replace('bulk-','',$this->current_action());
        //Detect when a bulk action is being triggered...
        if ( in_array($bulkAction,$actions) ) {

            // In our file that handles the request, verify the nonce.

            $sync_ids = esc_sql( $_POST["bulk-detailSync"] );
            // loop over the array of record IDs and delete them
            foreach ( $sync_ids as $id ) {
                self::sync_products( absint( $id ),$bulkAction);

            }


            $bazara = new BazaraApi(true);
            $message = $bazara->start_sync_new_product(0, 100000, true)['message'];
            // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
            // add_query_arg() return the current url
            wp_redirect( esc_url_raw(add_query_arg()) );
            exit;
        }
    }

    public static function sync_products( $id,$type = 'all' ) {
        global $wpdb;
        $cond = ($type == 'all' ? ' stockSync = 0,detailSync=0,priceSync=0 ':"{$type}=0");
        $a = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}bazara_products  SET {$cond} WHERE ProductCode=%d",$id));
    }
}
