<?php
class Bazara_Unsynced_Products_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Product', 'textdomain'),
            'plural'   => __('Products', 'textdomain'),
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        $columns = [
            'cb'      => '<input type="checkbox" />',
            'ProductCode'    => __( 'ProductCode', 'sp' ),
            'ProductName' => __( 'ProductName', 'sp' ),
            'detailSync'    => __( 'detailSync', 'sp' ),
            'stockSync'    => __( 'stockSync', 'sp' ),
            'priceSync'    => __( 'priceSync', 'sp' ),
            'Post_ID'    => __( 'PostID', 'sp' )
        ];
        return $columns;
    }


    public function prepare_items() {
        global $wpdb;
        global $Qauntity;

        $this->process_bulk_action();
        $item = $data = [];
        $products = get_products_v2();
        $options = get_bazara_visitor_settings();
        $SoftwareCurrency =  empty($options['selectCurrencySoftware']) ? 0 : $options['selectCurrencySoftware']  ;
        $PluginCurrency = empty($options['selectCurrencyPlugin']) ? 0 : $options['selectCurrencyPlugin'] ;
        foreach ($products as $product)
        {


            $date = date('Y/m/d h:i:s a', time());

            $store = '';
            if (!$store_priority_toggle)
                $store = $product->store_id;


            if (!is_array($store_priority_value))
                $store = $product->store_id;
            else
                $store =implode(',',$store_priority_value);

            $productDetail = get_product_details_unsynced($product->ProductId,$store);
            $conflict = false;


            foreach ($productDetail as $price)
            {

                if (empty($price->ProductDetailId)) continue;
                if ($price->NotVariation == 1 && empty($price->Properties)) continue;

                $prices  = text_to_json($price->Prices);
                $discounts  = text_to_json($price->Discounts);
                $regular_price = $ProductPrice = $discount = 0;
                if ($chkRegularPrice)
                {
                    $ProductPrice = $prices["{$RegularPrice}"]["Price{$RegularPrice}"];
                    if (class_exists("ChequeShipping") && !empty($chequeLevel)){
                        $ChequeProductPrice = $prices["{$chequeLevel}"]["Price{$chequeLevel}"];

                    }
                }else{
                    $level         =    ($price->DefaultSellPriceLevel == -1 ? 1 : $price->DefaultSellPriceLevel);
                    $ProductPrice = $prices["{$level}"]["Price{$level}"];

                }
                if ($chkSalePrice)
                {
                    if ($discountType == BAZARA_PRODUCT_PERCENT_DISCOUNT){
                        if (!empty($discounts["{$DiscountPriceOrPercent}"]))
                            $discount = $discounts["{$DiscountPriceOrPercent}"]["Discount{$DiscountPriceOrPercent}"];

                        if ($discount > 0 ){
                            $regular_price = $ProductPrice - (($ProductPrice * $discount) / 100);

                        }
                    }else if($discountType == BAZARA_PRODUCT_PRICE_DISCOUNT){
                        $regular_price = $prices["{$DiscountPriceOrPercent}"]["Price{$DiscountPriceOrPercent}"];
                        if ($regular_price == $ProductPrice)
                            $regular_price = 0;
                    }
                }else
                    $regular_price = 0;

                if (!empty($price->Properties))
                    $wooProduct = get_product_variation($price->ProductDetailStoreAssetId, $price->ProductDetailId);
                else
                    $wooProduct = bazara_get_product_by_sku($product->ProductCode);

                if (!empty($wooProduct)){
                    $SalePrice = str_replace('.','',$wooProduct->get_sale_price()) ?? 0;
                    $RegPrice = str_replace('.','',$wooProduct->get_regular_price()) ?? 0;
                    $Price = str_replace('.','',$wooProduct->get_price()) ?? 0;
                    if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman'){
                        $SalePrice = $SalePrice > 0 ? $SalePrice * 10 : 0;

                        $RegPrice = $RegPrice > 0 ? $RegPrice * 10 : 0;
                        $Price = $Price > 0 ? $Price * 10 : 0;


                    }
                    else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial'){
                        $SalePrice = $SalePrice > 0 ? $SalePrice / 10 : 0;
                        $RegPrice = $RegPrice > 0 ? $RegPrice / 10 : 0;
                        $Price = $Price > 0 ? $Price / 10 : 0;
                    }
                    $qty =    $price->$Qauntity;
                    $qty -= get_order_item_qty($wooProduct->get_id(),(!empty($price->Properties) ? '_variation_id' : '_product_id'));
                    $qty -= get_bazara_not_converted_qty($price->ProductDetailId);

                    if (($SalePrice <> $regular_price) || ($RegPrice <> $ProductPrice )|| (((float) $wooProduct->get_stock_quantity()) <> $qty))
                        $conflict = true;
                }
            }

            if ($conflict){

                $item['ProductCode'] = $product->ProductCode;
                $item['ProductName'] = $product->ProductName;
                $item['detailSync'] = $product->detailSync;
                $item['stockSync'] = $product->stockSync;
                $item['priceSync'] = $product->priceSync;
                $item['Post_ID'] = $product->Post_ID;
                $data[] = $item;
            }
        }
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $data;
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'ProductCode':
            case 'ProductName':
            case 'detailSync':
            case 'stockSync':
            case 'priceSync':
            case 'Post_ID':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }
/*
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="product[]" value="%s" />',
            $item['ID']
        );
    }
*/
    function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="bulk-detailSync[]" value="%s" />',
			$item['ProductCode']
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
