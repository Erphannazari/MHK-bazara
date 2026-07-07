<?php

if (!defined('WPINC')) {
    exit;
}

if(!class_exists('Bz_Import_Export_For_Woo_Basic_Product_Import')){
class Bz_Import_Export_For_Woo_Basic_Product_Import {

    public $import_results = array();
    private $item_data ;
    private $is_product_exist ;
    private $parent_module ;
    private $product_id ;
    private $plugin_options;
    private $visitor_options;
    private $visitor_settings;
    private $visitor_soft_settings;
    private $latest_versions;
    private $Properties;
    private $ExtraData;
    private $module_base;


    public function __construct() {

        $this->module_base='import';
        $this->plugin_options = bazara_get_options();
        $this->visitor_options = get_bazara_visitor_options();
        $this->visitor_settings = get_bazara_visitor_settings();
        $this->latest_versions = get_latest_versions();
        $this->visitor_soft_settings = bazara_visitor_soft_settings();
    }

    public function get_default_data(){
        return array(
		'name'               => '',
		'status'             => '',
		'sku'                => '',
		'price'              => '',
		'regular_price'      => '',
		'sale_price'         => '',
		'tax_status'         => 'taxable',
		'tax_class'          => '',
		'manage_stock'       => false,
		'stock_quantity'     => null,
		'stock_status'       => 'instock',
		'backorders'         => 'no',
		'attributes'         => array(),
		'virtual'            => false,
		'category_ids'       => array(),
        'ProductId'       => null,
		'ProductCode'       => null,
		'store_id'       => null,
		'isSync'       => 0,
		'barcode'       => false,
		'queue'       => 0,
		'type'       =>  'simple',
        'parent_id'       =>  '',
        'skip'       =>  false,

        

	);
    }


    public function prepare_data_to_import($batch_offset = 30){  
        $products = get_products(true,0,true);
        $this->Properties = get_properties();
        $this->ExtraData = get_extras();


        Bz_Import_Export_For_Woo_Basic_Logwriter::write_log($this->module_base, 'import', "Preparing for import.");
        $success = 0;
        $failed = 0;
        $row = 0;
        foreach ($products as $product)
        {
            $row ++;

            Bz_Import_Export_For_Woo_Basic_Logwriter::write_log($this->module_base, 'import', "Row :$row - Parsing item.");   

            $parsed_data = $this->bz_parse_data($product);


            if (!is_wp_error($parsed_data)){
               
                Bz_Import_Export_For_Woo_Basic_Logwriter::write_log($this->module_base, 'import', "Row :$row - Processing item.");  

                $result = $this->bz_process_item($parsed_data);

                if(!is_wp_error($result)){                    
                    if($this->is_product_exist){
                        $msg = 'Product updated successfully.';
                    } else {
                        $msg = 'Product imported successfully.';
                    }
                    $this->import_results[$row] = array('row'=>$row, 'message'=>$msg, 'status'=>true, 'status_msg' => __( 'Success' ), 'post_id'=>$result['id'], 'post_link' => ''); 
                    Bz_Import_Export_For_Woo_Basic_Logwriter::write_log($this->module_base, 'import', "Row :$row - ".$msg);                    
                    $success++;
                }else{
                    $this->import_results[$row] = array('row'=>$row, 'message'=>$result->get_error_message(), 'status'=>false, 'status_msg' => __( 'Failed/Skipped' ), 'post_id'=>'', 'post_link' => array( 'title' => __( 'Untitled' ), 'edit_url' => false ) );
                    Bz_Import_Export_For_Woo_Basic_Logwriter::write_log($this->module_base, 'import', "Row :$row - Processing failed. Reason: ".$result->get_error_message());
                   $failed++;
                }                
            }else{
               $this->import_results[$row] = array('row'=>$row, 'message'=>$parsed_data->get_error_message(), 'status'=>false, 'status_msg' => __( 'Failed/Skipped' ), 'post_id'=>'', 'post_link' => array( 'title' => __( 'Untitled' ), 'edit_url' => false ) );
               Bz_Import_Export_For_Woo_Basic_Logwriter::write_log($this->module_base, 'import', "Row :$row - Parsing failed. Reason: ".$parsed_data->get_error_message());
               

                
        }

    }

    }
    public function bz_product_existance_check($data){
        global $wpdb;   
        $product_id = 0;
        $this->product_id = '';
        $this->is_product_exist = false;  
        $sku = isset($data['sku']) && '' != $data['sku'] ? trim($data['sku']) : '';
        $id_found_with_sku = '';
        if(!empty($sku)){            
            $db_query = $wpdb->prepare("SELECT $wpdb->posts.ID,$wpdb->posts.post_type
                                        FROM $wpdb->posts
                                        LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
                                        WHERE $wpdb->posts.post_status IN ( 'publish', 'private', 'draft', 'pending', 'future' )
                                        AND $wpdb->postmeta.meta_key = '_sku' AND $wpdb->postmeta.meta_value = '%s'
                                        ", $sku);
            $id_found_with_sku = $wpdb->get_row($db_query);
            if ($id_found_with_sku && (in_array($id_found_with_sku->post_type, array('product', 'product_variation')))) {
                $id_found_with_sku = $id_found_with_sku->ID;
                $this->is_product_exist = true; 
                $product_id = $id_found_with_sku;                
            }     
        }
        

        return $product_id;
    }

       /**
    * Parse the data.
    *
    *
    * @param array $data value.
    *
    * @return array
    */
   public function bz_parse_data($data) {
    try {            
        
         $this->item_data = array(); // resetting WC default data before parsing new item to avoid merging last parsed item wp_parse_args
         $this->product_id = '';
         $deleted  = true;
         $prop =  [];
         $visitorPCount = $c =  0;

         $default_data = $this->get_default_data();                
         $this->item_data  = wp_parse_args( $this->item_data, $default_data );

        // product sku by barcode
        $optionBarcode = $this->bz_parse_bool_field($this->visitor_settings['barcode']) ;


        $this->item_data['sku'] = ($optionBarcode ? $data->barcode : $data->ProductCode);  
        $this->item_data['name'] = bazara_arabicToPersian($data->ProductName);
        $this->item_data['status'] = $data->Status  ;
        $this->item_data['ProductId'] = $data->ProductId;  
        $this->item_data['ProductCode'] = $data->ProductCode;  
        $this->item_data['Category'] = $data->Category;  
        $this->item_data['TaxPercent'] = $data->TaxPercent  ;
        $this->item_data['ChargePercent'] = $data->ChargePercent  ; 
        $this->item_data['tax_status'] = $this->bz_parse_tax_status_field($data->tax);
        $this->item_data['tax_class'] = isset($data->tax) ? $data->tax : ''  ;
        $this->item_data['new'] = 1; 
        $this->item_data['store_id'] = $data->store_id; 
        $this->item_data['meta_data']['mahak_id'] = $data->ProductId; 
        $this->item_data['meta_data']['mahak_product_store_id'] = $data->store_id; 
        $this->item_data['meta_data']['mahak_product_tax'] = $data->TaxPercent; 
        $this->item_data['meta_data']['mahak_product_charge'] = $data->ChargePercent; 

        $productDetail = get_product_details($data->ProductId,$data->store_id);

        foreach ($productDetail as $detail)
        {

            if (!empty($detail->Properties)) {

                $obj = json_decode($detail->Properties, true);
                $this->item_data['Objects'][] =  $obj;
                foreach ($this->Properties as $property)
                {
                    foreach ($obj as $std)
                    {
                        if ($std['C'] != $property->PropertyDescriptionCode) continue;
                        $v = implode('-',explode(' ',$std['V']));

                        $this->item_data['vars'][$c]['attributes'][("pa_" .sanitize_title($property->Title))] =  sanitize_title($v);

                    }

                }

                $this->item_data['vars'][$c]['stock_quantity'] =  $detail->Count1;
                $this->item_data['vars'][$c]['sku'] =  $data->ProductCode;
                $this->item_data['vars'][$c]['price'] = $detail->Price;
                $this->item_data['vars'][$c]['manage_stock'] = true;


                //set sale_price
                $RegularPrice = $this->item_data['vars'][$c]['regular_price'] == 0 ? '' : $this->item_data['vars'][$c]['regular_price'];
                if ($RegularPrice == '' || ($RegularPrice > 0 && $RegularPrice < $this->item_data['vars'][$c]['price']))
                $this->item_data['vars'][$c]['sale_price'] = $RegularPrice;

                $this->item_data['vars'][$c]['regular_price'] =  $detail->Regular_price;
                $this->item_data['vars'][$c]['Prices'] =  $detail->Prices;
                $this->item_data['vars'][$c]['deleted'] =  $detail->Deleted;
                $this->item_data['vars'][$c]['meta_data']['prop_id'] =  $detail->ProductDetailStoreAssetId;
                $this->item_data['vars'][$c]['meta_data']['mahak_product_store_id'] =  $data->store_id;
                $this->item_data['vars'][$c]['meta_data']['mahak_product_detail_id'] =  $detail->ProductDetailId;
                $this->item_data['vars'][$c]['meta_data']['mahak_product_tax'] =  ($data->TaxPercent == '-1' ? 0 : $data->TaxPercent);
                $this->item_data['vars'][$c]['meta_data']['mahak_product_charge'] =  ($data->ChargePercent == '-1' ? 0 : $data->ChargePercent);


                $c++;
            }else{

                $qty = (float)$detail->Count1;
                $this->item_data['stock_quantity'] = $qty;
                $this->item_data['stock_status'] = ($qty == 0 ? 'instock' : 'outofstock');
            }

                
                $this->item_data['price'] = $this->bz_change_currency_value($detail->Price);
                $this->item_data['regular_price'] =  $this->bz_change_currency_value($detail->Regular_price);

                //set sale_price
                $RegularPrice = $this->item_data['regular_price'] == 0 ? '' : $this->item_data['regular_price'];
                if ($RegularPrice == '' || ($RegularPrice > 0 && $RegularPrice < $this->item_data['price']))
                $this->item_data['sale_price'] = $RegularPrice;
                $this->item_data['Prices'] =  $detail->Prices;
                $this->item_data['detail_id'] = $detail->ProductDetailId;
                $this->item_data['meta_data']['mahak_product_detail_id'] = $detail->ProductDetailId; 

                if (get_visitor_products_count($detail->ProductDetailId,$this->visitor_options['VisitorId']) > 0)
                    $deleted = false;
                
                if (!empty(get_visitor_products($detail->ProductDetailId,$this->visitor_options['VisitorId'])))
                    $visitorPCount ++;

        }

        if (empty($this->item_data['vars']))
        $this->item_data['stock_status'] = 'instock';
        $this->item_data['manage_stock'] = empty($this->item_data['vars']);  


        //set product is deleted
        $this->item_data['deleted'] = $visitorPCount == 0 ? true :(empty($productDetail) ? false : $deleted);
        
        //set category ids
        $categoryIDS = $this->bz_get_product_category_ids($this->item_data);
        if(empty($categoryIDS))
            $this->item_data['category_ids'] = $categoryIDS;

        if(empty($this->item_data['id'])){                                 
            $this->item_data['id'] = $this->bz_parse_id_field($data);
        } 
    
        if($this->is_product_exist){
            $OptionTitle =    $this->bz_parse_bool_field($this->visitor_settings['chkTitle']) ;
            $OptionQuantity = $this->bz_parse_bool_field($this->visitor_settings['chkQuantity']);
            $OptionPrice =    $this->bz_parse_bool_field($this->visitor_settings['chkPrice']);
            
            if (!$OptionTitle)
            unset($this->item_data['name']);

            if (!$OptionQuantity)
            unset($this->item_data['stock_quantity'],$this->item_data['stock_status']);

            if (!$OptionPrice)
            unset($this->item_data['price'],$this->item_data['regular_price'],$this->item_data['sale_price']);
            
            
        }
        //create product attributes
        $pa = $this->create_product_attribute($this->item_data['Objects']);
        $this->item_data['attributes'] = $this->wc_prepare_product_attributes(array($pa));

        


        
         
         return $this->item_data;
     } catch (Exception $e) {            
         return new WP_Error('woocommerce_product_importer_error', $e->getMessage(), array('status' => $e->getCode()));
     }
 }


 public function bz_process_item($data) {

    try {
        do_action('bz_woocommerce_product_import_before_process_item', $data);
        $data = apply_filters('bz_woocommerce_product_import_process_item_data', $data);

        update_product_queue($data['ProductId'],1);


        if ($data['skip'])
        {
                update_schedule_sync($data['ProductId']);
                update_product_queue($data['ProductId'],0);

                return $result =  array(
                    'id' => 0
                );
        }

        // Get product ID from SKU if created during the importation.
        if ( !empty($data['sku'])) {
            $product_id = wc_get_product_id_by_sku($data['sku']);

            if ($product_id) {
                $data['id'] = $product_id;
            }
        }

        
        $object = $this->bz_get_product_object($data); 

        if (is_wp_error($object)) {
            return $object;
        }
        
        
        Bz_Import_Export_For_Woo_Basic_Logwriter::write_log($this->module_base, 'import', "Found ".$object->get_type()." product object. ID:".$object->get_id());            

        if ('variation' === $object->get_type()) {
            if (isset($data['status']) && 'draft' === $data['status']) {
                $data['status'] = 'private'; // Variations cannot be drafts - set to private.
            }
        }
                    
        if ('importing' === $object->get_status()) {
            $object->set_status($data['status']);
        }

        if ($data['deleted']){
            $object->set_status('trash');
            update_schedule_sync($data['ProductId']);
            update_product_queue($data['ProductId'],0);
            return $result =  array(
                'id' => $object->get_id()
            );

        }

        //unset stock on ExcludedProductsByCategory set
        $ExcludedProductsByCategory = $this->visitor_settings['ExcludedProductsByCategory'] ;

        if ($this->bz_check_product_Category_for_unset($data['id'],$ExcludedProductsByCategory))
            unset($this->item_data['stock_quantity'],$this->item_data['stock_status']);
        


        $result = $object->set_props(array_diff_key($data, array_flip(array('meta_data', 'raw_image_id', 'raw_gallery_image_ids','images'))));
     
        

            
        if (is_wp_error($result)) {
            throw new Exception($result->get_error_message());
        }
        
        $this->set_meta_data($object, $data);
        
        $object = apply_filters('bz_woocommerce_product_import_pre_insert_product_object', $object, $data);
                                
        $object->save();

        //set person role price
        $this->set_person_group_prices($data);
        
        if (!empty($data['vars']))
        {

            foreach ($data['vars'] as $attr)
            {
                $this->create_variations($data['id'],$attr);

            }
        }
        

        if (!empty($data['vars']))
        {
            wp_remove_object_terms($data['id'], 'simple', 'product_type' );
            wp_set_object_terms($data['id'], 'variable', 'product_type', true );
        }
        

        update_schedule_sync($data['ProductId']);
        update_product_as_old($data['ProductId']);
        update_product_queue($data['ProductId'],0);

        do_action('bz_woocommerce_product_import_inserted_product_object', $object, $data);

        return $result =  array(
            'id' => $object->get_id()
        );
    } catch (Exception $e) {
        return new WP_Error('woocommerce_product_importer_error', $e->getMessage(), array('status' => $e->getCode()));
    }
}

function set_person_group_prices($args)
{
    if(class_exists('WooCommerce_Role_Based_Price_Product_Pricing')){
        $enable = 0;
        $final_price = array();
        $role_price_levels = !empty($args['Prices']) ? json_decode($args['Prices']) : [];
        $role_price_levels  = (json_decode(json_encode($role_price_levels), true));

        $personGroups = get_person_group();
        if($role_price_levels){
            $allowed_prices = wc_rbp_allowed_price();

            foreach($personGroups as $pg)
            {
                foreach( $allowed_prices as $price_type ) {


                    $p = !empty($pg->SellPriceLevel)&& $pg->SellPriceLevel > 0 ? $role_price_levels[$pg->SellPriceLevel]["Price{$pg->SellPriceLevel}"] : '';

                    if(empty($p))
                        $p = '';
                    else
                        $enable = 1;

                    $final_price[str_replace(" ","_",$pg->Name)][$price_type] = $p;

                }
            }
        }
        update_post_meta($args['id'], '_role_based_price', $final_price );
        update_post_meta($args['id'], '_enable_role_based_price', $enable );

    }
}
function set_meta_data(&$product, $data) {
    if (isset($data['meta_data'])) {
        foreach ($data['meta_data'] as $key => $value) {
            if(''== $key)
                continue;    
            $function	 = 'set_' . $key;
            $has_setter	 = is_callable( array( $product, $function ) );
            if ( $has_setter ) {
                $product->{$function}( $value );
                continue;
            }
            $product->update_meta_data($key, $value);
        }
    }
}
function bz_get_product_object($data) {     
    $id = isset($data['id']) ? absint($data['id']) : 0;

        $product = wc_get_product($id);
        if (!$product) {
            return new WP_Error(
                    'woocommerce_product_csv_importer_invalid_id',
                    /* translators: %d: product ID */ sprintf(__('Invalid product ID %d.', 'woocommerce'), $id), array(
                'id' => $id,
                'status' => 401,
                    )
            );
        }
    

    return apply_filters('bz_woocommerce_product_import_get_product_object', $product, $data);

}


/**
     * Parse relative field and return ID.
     * 
     * Handles `id` and Product SKU.
     *
     * If we're not doing an update, create a prost and return ID
     * for rows following this one.
     *
     * @param array $data  mapped data.
     *
     * @return int|Exception
     */
    public function bz_parse_id_field($data ) {    
        $data = (array)$data ;                        
        if(!empty($this->item_data['id'])){
            return $this->item_data['id'];
        }        
        $found_id = $this->bz_product_existance_check($data);  
        if($found_id){
            return $found_id;
        }
        if ($this->item_data['deleted'])
        {
            $this->item_data['skip'] = true;
            return false;
        }
        
	    $this->item_data['type'] = isset($this->item_data['type']) ? $this->item_data['type'] : 'simple';
        $postdata = array( // if not specifiying id (id is empty) or if not found by given id or Product 
            'post_title'      =>  ($this->item_data['type'] == 'variation' ? 'product variation' : $this->item_data['name'] ),
            'post_status'    => 'importing',
            'post_type'      => 'product'
        );                 
        if(isset($id) && !empty($id)){
            $postdata['import_id'] = $id;
        }    
        
        $post_id = wp_insert_post( $postdata, true );                
        if($post_id && !is_wp_error($post_id)){
            Bz_Import_Export_For_Woo_Basic_Logwriter::write_log($this->module_base, 'import', sprintf('Importing as new '. ($this->module_base).' ID:%d',$post_id ));
            return $post_id;
        }else{
            throw new Exception($post_id->get_error_message());
        }

    }

    /**
     * Parse the tax status field.
     *
     * @param string $value Field value.
     *
     * @return string
     */
    public function bz_parse_tax_status_field( $value ) {
        if ( '' === $value ) {
                return $value;
        }
        $tax_status = strtolower($value);
        $value = ('taxable' == $tax_status || 'shipping' == $tax_status) ? $tax_status : 'none';

        return wc_clean( $value );
        }

        /**
     *
     * @param string $value Field value.
     *
     * @return string
     */
    public function bz_change_currency_value( $value,$order = false ) {
        if ( '' === $value ) {
                return $value;
        }
        $SoftwareCurrency =  $this->visitor_settings['selectCurrencySoftware']  ;
        $PluginCurrency =    $this->visitor_settings['selectCurrencyPlugin']  ;
        if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman'){
            if ($order)
            $value *= 10;
            else
            $value /= 10;
            
        }
        else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial'){
            if ($order)
            $value /= 10;
            else
            $value *= 10;
        }
            return $value;
    }

    	/**
    * Parse a field that is generally '1' or '0' but can be something else.
    *
    * @param string $value Field value.
    *
    * @return bool|string
    */
    private function bz_parse_bool_field( $value ) {
        if ( '0' === $value ) {
                return false;
        }

        if ( '1' === $value ) {
                return true;
        }

        // Don't return explicit true or false for empty fields or values like 'notify'.
        return wc_clean( $value );
    }

    /**
    * Parse a field that is generally '1' or '0' but can be something else.
    *
    * @param string $value Field value.
    *
    * @return bool|string
    */
     function bz_check_product_Category_for_unset($id,$ExcludedProductsByCategory) {
        if ( null === $id || 0 === $id ) 
                return false;
        
        if (empty($ExcludedProductsByCategory)) 
            return false;
    
            $ProductCategoryExist = false;
			$catTerms = get_the_terms($id, 'product_cat' );
            
            if (empty($catTerms))
            return false;

			foreach ($catTerms  as $term  ) {                    

            $product_cat_id = $term->term_id; 
			if ($product_cat_id == $ExcludedProductsByCategory){
			$ProductCategoryExist  = true;
            break;
			}

        }
        return $ProductCategoryExist;
    }


    function bz_get_product_category_ids($productArgs)
    {
        $cat_ids = [];
        $extraDatas = get_extras();
        if (empty($extraDatas)) return '';

        foreach ($extraDatas as $item) {
            $ItemType = empty($item->ItemType) ? $item['ItemType'] : $item->ItemType;
            $Data = empty($item->Data) ? $item['Data'] : $item->Data;
            if ($ItemType != 10202 ) continue;
            $data = json_decode($Data, true);

            if ($data['ProductCode'] == $productArgs['sku']) {

                $term_id = get_bazara_taxonomy_term($data['CategoryCode']);

                $cat_id = $term_id;
                if (!empty($cat_id))
                    array_push($cat_ids, $cat_id);
            }

        }
        if (!empty($productArgs['Category'])){
            $term_id = get_bazara_taxonomy_term($productArgs['Category']);

            $cat_id = $term_id;
            if (!empty($cat_id))
                array_push($cat_ids,$cat_id);
        }
        return $cat_ids;
    }

    function create_product_attribute($object = array())
    {
        if (empty($object)) return [];
        
        $pa = array();
        $forVariation =  $this->bz_parse_bool_field($this->visitor_settings['forVariation']);
        $position = 0;
        foreach ($this->Properties as $property)
        {
            foreach ($object as $prop => $value) {
                foreach ($value as $var) {
                    if ($var['C'] != $property->PropertyDescriptionCode) continue;
                    $pa[$property->Title]['term_names'][] = $var['V'];
                }

            }
            if (empty($pa[$property->Title]['term_names']))
            {
                unset($pa[$property->Title]);
                continue;
            }

            $pa[$property->Title]['is_visible']= true;
            $pa[$property->Title]['for_variation'] = ($position == 0 || !$forVariation);



            $position ++;
        }

        return $pa;
                
    }

    // Utility function that prepare product attributes before saving
 function wc_prepare_product_attributes( $attributes ){
    global $woocommerce;

    $data = $array = $vals = array();
    $position = 0;


    foreach ($attributes as $attr)
    {
        foreach( $attr as $taxonomy => $values ){


            $taxonomy = 'pa_' . $taxonomy;
            if( ! taxonomy_exists( $taxonomy ) ) {

                $a = register_taxonomy(
                    $taxonomy,
                    'product',
                    array(
                        'hierarchical' => false,
                        'label' => ucfirst($taxonomy),
                        'query_var' => true,
                        'rewrite' => array(
                            'slug' => $taxonomy,
                            'with_front' => false
                        ),
                    )
                );
            }



            // Get an instance of the WC_Product_Attribute Object
            $attribute = new WC_Product_Attribute();

            $term_ids = array();


            if (empty($values['term_names'])) continue;
            // Loop through the term names
            foreach( $values['term_names'] as $term_name ){
		if (empty($term_name)) continue;

                $array[$taxonomy][] = $term_name;
                if( term_exists( $term_name, $taxonomy ) )
                    // Get and set the term ID in the array from the term name
                    $term_ids[] = get_term_by( 'name', $term_name, $taxonomy )->term_id;
                else
                    $term_ids[] = wp_insert_term($term_name,$taxonomy)['term_id'];

//                wp_set_object_terms($pid, $term_name, $taxonomy,true);
            }

            $taxonomy_id = wc_attribute_taxonomy_id_by_name( $taxonomy ); // Get taxonomy ID


            $attribute->set_id( $taxonomy_id );
            $attribute->set_name( $taxonomy );
            $attribute->set_options( $values['term_names'] );
            $attribute->set_position( $position );
            $attribute->set_visible( $values['is_visible'] );
            $attribute->set_variation( $values['for_variation']);

            $data[$taxonomy] = $attribute; // Set in an array

            $position++; // Increase position

        }
    }




    return $data;
    }

    function create_variations( $product_id, $args ){


        $variation = get_product_variation($args['meta_data']['mahak_product_detail_id']);
        $variation_id = $args['meta_data']['prop_id'];

        
        $deleted = $args['deleted'];
        if (empty($args['meta_data']['mahak_product_detail_id'])) return false;
        update_product_queue($args['meta_data']['mahak_product_detail_id'],0,false,'bazara_product_details','ProductDetailId');
    
        $args['parent_id'] = $product_id;
        $args  = wp_parse_args( $args, $default_data );

        // Get the Variable product object (parent)
        $product = wc_get_product($product_id);


        if (empty($variation))
        {
            if ($args['deleted'] == 1 || $args['deleted']){
    
                update_schedule_sync($args['meta_data']['mahak_product_detail_id'],1,'bazara_product_details','ProductDetailId');
                update_product_queue($args['meta_data']['mahak_product_detail_id'],1,false,'bazara_product_details','ProductDetailId');
                return false;
            }			
    
            $variation_post = array(
                'post_title'  => $product->get_name(),
                'post_name'   => 'product-'.$product_id.'-variation',
                'post_status' => 'publish',
                'post_parent' => $product_id,
                'post_type'   => 'product_variation',
                'guid'        => $product->get_permalink()
            );
    
    
            // Creating the product variation
            $variation_id = wp_insert_post( $variation_post );
            $variation = new WC_Product_Variation( $variation_id );
            $this->set_meta_data($variation,$args);

    
        }
        if ($deleted == 1 || $deleted){
            $id =  $variation->get_id();
            $a = wh_deleteProduct($id,true);
        }
    
    
    
        $OptionQuantity = $this->bz_parse_bool_field($this->visitor_settings['chkQuantity']);
        $OptionPrice = $this->bz_parse_bool_field($this->visitor_settings['chkPrice']);

        if (!$OptionQuantity)
        unset($args['stock_quantity'],$args['stock_status']);

        if (!$OptionPrice)
        unset($args['price'],$args['regular_price'],$args['sale_price']);


        $variation->set_props(array_diff_key($args, array_flip(array('meta_data', 'raw_image_id', 'raw_gallery_image_ids','images'))));
    
    

        $variation->save();
        update_schedule_sync($args['detail_id'],1,'bazara_product_details','ProductDetailId');
        update_product_queue($args['detail_id'],1,false,'bazara_product_details','ProductDetailId');
    
    
    
    }

  }

  	
}




