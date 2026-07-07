<?php


if (! class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Bazara_Products_List extends WP_List_Table
{

	/** Class constructor */
	public function __construct()
	{

		parent::__construct([
			'singular' => __('products', 'sp'), //singular name of the listed records
			'plural'   => __('products', 'sp'), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		]);

		// Add admin notice hook
		add_action('admin_notices', array($this, 'display_admin_notices'));
	}


	/**
	 * Retrieve Products data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_products($per_page = 5, $page_number = 1)
	{

		global $wpdb;
		$search = (isset($_REQUEST['s'])) ? $_REQUEST['s'] : false;
		$do_search = ($search) ? $wpdb->prepare(" WHERE (ProductCode LIKE '%$search%' OR ProductName LIKE '%$search%') ", $search) : '';

		$sql = "SELECT * FROM {$wpdb->prefix}bazara_products {$do_search}";



		if (! empty($_REQUEST['orderby'])) {
			$sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
			$sql .= ! empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ($page_number - 1) * $per_page;


		$result = $wpdb->get_results($sql, 'ARRAY_A');

		return $result;
	}


	/**
	 * sync record.
	 *
	 * @param int $id sync ID
	 */
	public static function sync_products($id, $type = 'all')
	{
		global $wpdb;
		$cond = ($type == 'all' ? ' stockSync = 0,detailSync=0,priceSync=0 ' : "{$type}=0");
		$a = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}bazara_products  SET {$cond} WHERE ProductCode=%d", $id));
	}

	public static function sync_pictures($id)
	{
		global $wpdb;
		$productid = $wpdb->get_var($wpdb->prepare("SELECT productid from {$wpdb->prefix}bazara_products WHERE productCode = %d", $id));
		$pictureid = $wpdb->get_results($wpdb->prepare("SELECT pictureid from {$wpdb->prefix}bazara_photo_gallery WHERE ItemCode=%d", $productid));
		foreach ($pictureid as $pic) {
			$res = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}bazara_pictures SET isSync = 0 WHERE PictureId = %d", $pic->pictureid));
		}
	}

	function get_picture_ids_by_product_codes($sync_ids)
	{
		global $wpdb;
		$productid = $wpdb->get_var($wpdb->prepare("SELECT productid from {$wpdb->prefix}bazara_products WHERE productCode = %d", $sync_ids));
		$pictureid = $wpdb->get_results($wpdb->prepare("SELECT pictureid from {$wpdb->prefix}bazara_photo_gallery WHERE ItemCode=%d", $productid));
		
    	$picture_ids = array();
    	foreach ($pictureid as $result) {
        	$picture_ids[] = $result->pictureid;
    	}
    
    	return $picture_ids;
	}

function delete_product_images_by_sku($sku) {
    // محصول را با استفاده از SKU پیدا کن
    $product_id = wc_get_product_id_by_sku($sku);
    if (!$product_id) {
        echo "محصولی با این کد پیدا نشد.";
        return;
    }

    // تصویر شاخص را حذف کن
    $thumbnail_id = get_post_thumbnail_id($product_id);
    if ($thumbnail_id) {
        wp_delete_attachment($thumbnail_id, true);
        delete_post_thumbnail($product_id);
    }

    // گالری تصاویر را دریافت و حذف کن
    $gallery_image_ids = get_post_meta($product_id, '_product_image_gallery', true);
    if (!empty($gallery_image_ids)) {
        $gallery_ids = explode(',', $gallery_image_ids);
        foreach ($gallery_ids as $image_id) {
            wp_delete_attachment($image_id, true);
        }
        // متای گالری را پاک کن
        delete_post_meta($product_id, '_product_image_gallery');
    }

    echo "تصویر شاخص و تصاویر گالری با موفقیت حذف شدند.";
}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count()
	{
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}bazara_products";

		return $wpdb->get_var($sql);
	}


	/** Text displayed when no products data is available */
	public function no_items()
	{
		_e('No Products avaliable.', 'sp');
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default($item, $column_name)
	{
		switch ($column_name) {
			case 'ProductCode':
			case 'ProductName':
			case 'detailSync':
			case 'stockSync':
			case 'priceSync':
			case 'Post_ID':

				return $item[$column_name];
			default:
				return print_r($item, true); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="bulk-detailSync[]" value="%s" />',
			$item['ProductCode']
		);
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name($item)
	{

		$sync_nonce = wp_create_nonce('bazara_set_syn1c');

		$title = '<strong>' . $item['ProductName'] . '</strong>';
		var_dump($sync_nonce);
		die;
		$actions = [
			'detailSync' => sprintf('<a href="?page=%s&action=%s&products=%s&_wpnonce=%s">detailSync</a>', esc_attr($_REQUEST['page']), 'detailSync', absint($item['ProductCode']), $sync_nonce)

		];

		return $title . $this->row_actions($actions);
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns()
	{
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'ProductCode'    => __('ProductCode', 'sp'),
			'ProductName' => __('ProductName', 'sp'),
			'detailSync'    => __('detailSync', 'sp'),
			'stockSync'    => __('stockSync', 'sp'),
			'priceSync'    => __('priceSync', 'sp'),
			'Post_ID'    => __('PostID', 'sp')

		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns()
	{
		$sortable_columns = array(
			'ProductName' => array('ProductName', true),
			'ProductCode' => array('ProductCode', false),
			'detailSync' => array('detailSync', false),
			'priceSync' => array('priceSync', false),
			'stockSync' => array('stockSync', false)

		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions()
	{
		$actions = [
			'bulk-detailSync' => 'detailSync',
			'bulk-stockSync' => 'stockSync',
			'bulk-priceSync' => 'priceSync',
			'bulk-pictureSync' => 'pictureSync',
			'bulk-all' => 'all',


		];

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items()
	{

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page('product_per_page', 10);
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args([
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		]);

		$this->items = self::get_products($per_page, $current_page);
	}

	public function process_bulk_action()
	{
		$actions = ['detailSync', 'stockSync', 'priceSync', 'all', 'pictureSync'];
		$bulkAction = str_replace('bulk-', '', $this->current_action());
		
		//Detect when a bulk action is being triggered...
		if (in_array($bulkAction, $actions)) {
			// In our file that handles the request, verify the nonce.
			$sync_ids = esc_sql($_POST["bulk-detailSync"]);
			
			// loop over the array of record IDs and delete them
			foreach ($sync_ids as $id) {
				if($bulkAction == 'pictureSync'){
					self::delete_product_images_by_sku($id);
					self::sync_pictures(absint($id));
				}else{
					self::sync_products(absint($id), $bulkAction);
				}
			}

			$bazara = new BazaraApi(true);

			if($bulkAction == 'pictureSync'){
				$result = $bazara->sync_pictures();
			}else{
				// Only sync the selected products
				$result = $bazara->start_sync_new_product(0, 100000, true, $sync_ids);
			}

			if (isset($result['message']) && !empty($result['message'])) {
				// Store the message in a transient
				set_transient('bazara_sync_message', $result['message'], 45);
			}

			// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
			// add_query_arg() return the current url
			wp_redirect(esc_url_raw(add_query_arg()));
			exit;
		}
	}

	/**
	 * Display admin notices
	 */
	public function display_admin_notices() {
		$message = get_transient('bazara_sync_message');
		if ($message) {
			?>
<div class="notice notice-success is-dismissible">
    <p><?php echo wp_kses_post(nl2br($message)); ?></p>
</div>
<?php
			// Delete the transient after displaying
			delete_transient('bazara_sync_message');
		}
	}
}