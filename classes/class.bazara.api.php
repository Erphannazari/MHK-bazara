<?php

if (! defined('ABSPATH')) {
    exit;
}

class BazaraApi
{
    private $entities;
    private $plugin_options;
    private $visitor_options;
    private $visitor_settings;
    private $visitor_soft_settings;
    private $latest_versions;
    private $enable_versioning;
    private $base_url = "https://mahakacc.mahaksoft.com/api/v3/sync";
    private $img_url = "https://mahakacc.mahaksoft.com/api/v3/Content/Images/all/";
    private $login_url = "";
    private $getAll = "";
    private $setAll = "";
    private $repair = "";
    private $repairOrders = "";


    public function __construct($quick)
    {
        $this->plugin_options = bazara_get_options();
        $this->visitor_options = get_bazara_visitor_options();
        $this->visitor_settings = get_bazara_visitor_settings();
        $this->latest_versions = get_latest_versions();
        $this->visitor_soft_settings = bazara_visitor_soft_settings();
        $this->enable_versioning = $quick;
        $this->login_url = $this->base_url . "/Login";
        $this->getAll = $this->base_url . "/GetAllData";
        $this->setAll = $this->base_url . "/SaveAllData";
        $this->repair = $this->base_url . "/SolveDispute";
        $this->repairOrders = $this->base_url . "/SolveOrderDispute";

        $this->entities = array(
            'PersonGroups' => array('entity' => 'fromPersonGroupVersion', 'alias' => 'PersonGroup'),
            'PropertyDescriptions' => array('entity' => 'fromPropertyDescriptionVersion', 'alias' => 'PropertyDescriptions'),
            'Stores' => array('entity' => 'fromStoreVersion', 'alias' => 'Stores'),
            'ExtraDatas' => array('entity' => 'fromExtraDataVersion', 'alias' => 'ExtraData'),
            'Regions' => array('entity' => 'fromRegionVersion', 'alias' => 'Regions'),
            'Products' => array('entity' => 'fromProductVersion', 'alias' => 'product'),
            'Settings' => array('entity' => 'fromSettingVersion', 'alias' => 'Settings'),
            'ProductDetails' => array('entity' => 'fromProductDetailVersion', 'alias' => 'productDetail'),
            'ProductDetailStoreAssets' => array('entity' => 'fromProductDetailStoreAssetVersion', 'alias' => 'ProductAsset'),
            'VisitorProducts' => array('entity' => 'fromVisitorProductVersion', 'alias' => 'VisitorProducts'),
            'Pictures' => array('entity' => 'fromPictureVersion', 'alias' => 'Pictures'),
            'PhotoGalleries' => array('entity' => 'fromPhotoGalleryVersion', 'alias' => 'PhotoGalleries'),
            'Banks' => array('entity' => 'fromBankVersion', 'alias' => 'Banks'),
            'Persons' => array('entity' => 'fromPersonVersion', 'alias' => 'Persons'),
            'VisitorPersons' => array('entity' => 'fromVisitorPersonVersion', 'alias' => 'VisitorPersons'),
            'SubCategory' => array('entity' => 'fromProductCategoryVersion', 'alias' => 'SubCategory'),
            'Transactions' => array('entity' => 'fromtransactionversion', 'alias' => 'Transactions'),
            'Orders' => array('entity' => 'fromOrderVersion', 'alias' => 'Orders'),
            'OrderDetails' => array('entity' => 'fromOrderDetailVersion', 'alias' => 'OrderDetails'),

        );
    }
    public function get_all_data($token = '', $input = array())
    {
        // 1) دریافت توکن در صورت خالی بودن
        if (empty($token)) {
            $token_result = $this->login_token();
            if (empty($token_result['success']) || !$token_result['success']) {
                return array(
                    'success' => false,
                    'message' => $token_result['message'] ?? 'Token error'
                );
            }
            $token = $token_result['message'];
        }
    
        // 2) درخواست از API
        $response = $this->http_post($this->getAll, $input, $token);
        $decoded  = json_decode($response, true);
    
        // 3) بررسی معتبر بودن JSON
        if (!is_array($decoded)) {
            Bz_Import_Export_For_Woo_Basic_Logwriter::write_log(
                'پاسخ نامعتبر از API (JSON decode failed)',
                'Error',
                $response
            );
            return array(
                'success' => false,
                'message' => 'Invalid API response format'
            );
        }
    
        // 4) وجود نداشتن کلید Result
        if (!array_key_exists('Result', $decoded)) {
            Bz_Import_Export_For_Woo_Basic_Logwriter::write_log(
                'کلید Result در پاسخ API وجود ندارد',
                'Error',
                json_encode($decoded)
            );
            return array(
                'success' => false,
                'message' => 'Missing Result key in API response'
            );
        }
    
        // 5) اگر API خطا برگرداند
        if (!$decoded['Result']) {
            Bz_Import_Export_For_Woo_Basic_Logwriter::write_log(
                'خطا در دریافت اطلاعات از API',
                'Error',
                json_encode($decoded)
            );
            return array(
                'success' => false,
                'message' => json_encode($decoded)
            );
        }
    
        // 6) بررسی وجود Data → Objects
        if (!isset($decoded['Data']['Objects'])) {
            Bz_Import_Export_For_Woo_Basic_Logwriter::write_log(
                'Objects در پاسخ API یافت نشد',
                'Error',
                json_encode($decoded)
            );
            return array(
                'success' => false,
                'message' => 'Objects not found in API response'
            );
        }
    
        // 7) موفقیت کامل
        return array(
            'success' => true,
            'message' => $decoded['Data']['Objects']
        );
    }    
    public function repair_entities($token = '', $url = '', $input = array())
    {
        if (empty($token)) {
            $token_result = $this->login_token();
            if (!$token_result['success'])
                return array('success' => false, 'message' => $token_result['message']);
            $token = $token_result['message'];
        }
        $result = $this->http_post($url, $input, $token);
        $result = json_decode($result, true);

        if (!$result['Result']) {
            Bz_Import_Export_For_Woo_Basic_Logwriter::write_log('خطا در ارسال اطلاعات', 'Error ', json_encode($result));
            return array('success' => false, 'message' => json_encode($result));
        }

        return json_encode(array('success' => true, 'message' => $result['Result'], 'data' => $result));
    }
    public function repair_database()
    {

        global $table_prefix, $wpdb;
        $bazaraOption = get_option('bazara_options');

        $tblname = array('table' => 'bazara_visitor_products', 'id' => "VisitorProductId", 'entity' => 'visitorProduct');
        $sq[] = $tblname;
        $tblname = 'bazara_products';
        $tblname = array('table' => 'bazara_products', 'id' => "ProductId", 'entity' => 'product');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_stores', 'id' => "StoreId", 'entity' => 'store');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_product_properties', 'id' => "PropertyDescriptionId", 'entity' => 'PropertyDescription');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_product_details', 'id' => "ProductDetailId", 'entity' => 'ProductDetail');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_product_assets', 'id' => "ProductDetailStoreAssetId", 'entity' => 'ProductDetailStoreAsset');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_pictures', 'id' => "PictureId", 'entity' => 'picture');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_photo_gallery', 'id' => "PhotoGalleryId", 'entity' => 'PhotoGallery');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_persons', 'id' => "PersonId", 'entity' => 'person');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_visitor_persons', 'id' => "VisitorPersonId", 'entity' => 'visitorPerson');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_person_groups', 'id' => "PersonGroupId", 'entity' => 'personGroup');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_banks', 'id' => "BankId", 'entity' => 'bank');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_extra_data', 'id' => "ExtraDataId", 'entity' => 'extraData');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_orders', 'id' => "OrderId", 'entity' => 'order');
        $sq[] = $tblname;
        $tblname = array('table' => 'bazara_order_details', 'id' => "OrderDetailId", 'entity' => 'orderDetail');
        $sq[] = $tblname;

        $id = "";
        $data = array();

        foreach ($sq as $s) {

            $result = get_entity($s['table']);
            $dispuItems  = array();

            foreach ($result as $item) {
                $dispuItems[] = array('id' => $item[$s['id']], 'rw' => $item['RowVersion']);
            }

            $data = array('entityName' => $s['entity'], 'databaseId' => $bazaraOption['DatabaseId'], 'disputeItems' => $dispuItems);
            self::repair_entities(null, $this->repair, $data);
        }
    }
    public function set_all_data($token = '', $input = array())
    {
        if (empty($token)) {
            $token_result = $this->login_token();
            if (!$token_result['success'])
                return array('success' => false, 'message' => $token_result['message']);
            $token = $token_result['message'];
        }
        $result = $this->http_post($this->setAll, $input, $token);
        $result = json_decode($result, true);

        if (!$result['Result']) {
            Bz_Import_Export_For_Woo_Basic_Logwriter::write_log('خطا در ارسال اطلاعات', 'Error ', json_encode($result));
            return array('success' => false, 'message' => json_encode($result));
        }

        return json_encode(array('success' => true, 'message' => $result['Result'], 'data' => $result));
    }
    public function login_token()
    {
        $this->plugin_options = bazara_get_options();
        $loginOption = get_option('bazara_login');
        if (!empty($loginOption) && $loginOption['expireAt'] >= strtotime('now')) {
            return array('success' => true, 'message' => $loginOption['token'], 'VisitorID' => $loginOption['VisitorID'], 'extra' => $loginOption['extra'], 'object' => $loginOption['object']);
        }
        $validate = bazara_validate_plugin_options($this->plugin_options);
        if ($validate != "")
            return array('success' => false, 'message' => $validate);

        $data = array(
            'userName' => $this->plugin_options['username'],
            'password' => md5($this->plugin_options['password']),
            //            'databaseId'=>(int)$this->plugin_options['systemSyncID'],
            //            'packageNo'=>(int)$this->plugin_options['packageNumber'],
            'description' => $_SERVER['HTTP_HOST'],
            'AppId' => BAZARA_APP_ID
        );


        $result = $this->http_post($this->login_url, $data);

        if ($result === FALSE) {
            return array('success' => false, 'message' => __('HTTP Error In Login. Please check your internet connection or may be mahak service is not available now.', 'bazara-mahak'));
        }
        $jObj = json_decode($result, true);

        $active_sync = toggle_to_boolean(sanitize_text_field($_REQUEST['active_auto_sync']));

        if ($jObj['Result'] == "True") {
            $options = array(
                'token' => $jObj['Data']['UserToken'],
                'VisitorID' => $jObj['Data']['VisitorId'],
                'extra' => $data,
                'object' => $jObj['Data'],
                'expireAt' => strtotime('+10 Hour'),
            );
            if (!$active_sync)
                $options['refresh_interval'] = 0;
            update_option('bazara_login', $options);
            return array('success' => true, 'message' => $jObj['Data']['UserToken'], 'VisitorID' => $jObj['Data']['VisitorId'], 'extra' => $data, 'object' => $jObj['Data']);
        } else {
            Bz_Import_Export_For_Woo_Basic_Logwriter::write_log('ارسال درخواست لاگین', 'Error ', json_encode($result));
            return array('success' => false, 'message' => $jObj['Message']);
        }
    }

    private function http_post($url, $data, $bearerToken = '')
    {

        $headers  = array('Content-Type' => 'application/json', 'Authorization' => 'bearer : ' . $bearerToken);

        $response = wp_remote_request(
            $url,
            array(
                'method'  => 'POST',
                'body'    => json_encode($data),
                'timeout'     => 120,
                'headers' => $headers,
            )
        );

        if (is_wp_error($response)) {
            //   Bz_Import_Export_For_Woo_Basic_Logwriter::write_log('ارسال درخواست ', 'Error ', json_encode($result->get_error_message()));      

            return null;
        }
        return $result =  $response['body'];
    }

    private function create_woo_tax($taxPercent = 0)
    {
        $tax_class_name = 'عوارض و مالیات محک ' . $taxPercent . '%';
        $tax_rate_name = 'عوارض و مالیات ' . $taxPercent . '%';

        $tax_rate = $taxPercent;

        $tax_classes = WC_Tax::get_tax_classes();
        if (!in_array($tax_class_name, $tax_classes)) {
            WC_Tax::create_tax_class($tax_class_name);
            WC_Cache_Helper::invalidate_cache_group('taxes');
            WC_Cache_Helper::get_transient_version('shipping', true);
            $tax_rate_data = array(
                'tax_rate_country' => '*',
                'tax_rate_state' => '*',
                'tax_rate' => $tax_rate,
                'tax_rate_name' => $tax_rate_name,
                'tax_rate_priority' => 1,
                'tax_rate_compound' => 0,
                'tax_rate_shipping' => 1,
                'tax_rate_order' => 0,
                'tax_rate_class' => $tax_class_name
            );
            WC_Tax::_insert_tax_rate($tax_rate_data);
        }

        return $tax_class_name;
    }
    public function start_sync_category($token = null, $catGroup = 2)
    {

        if (empty($token)) {
            $token_result = $this->login_token();
            if (!$token_result['success'])
                return array('success' => false, 'message' => $token_result['message']);
            $token = $token_result['message'];
        }
        switch ($catGroup) {
            case 1:

                // $data = array('fromProductCategoryVersion' => 0); //fetch all Categories
                // $product_result = $this->get_all_data($token,$data);
                // if(!$product_result['success'])
                //     return array('success' => false, 'message' =>$product_result['success']);
                $categories = bazara_get_categories();
                foreach ($categories as $cat) {

                    if ($cat['Deleted'] == true) continue;

                    $term_ids = get_bazara_taxonomy_term($cat['ProductCategoryId']);
                    $category_exist = get_metadata('term', $term_ids, 'CategoryID', true);

                    if (!$category_exist) {
                        $term_id = wp_insert_term(
                            $cat['Name'], // the term
                            'product_cat', // the taxonomy
                            array(
                                'parent' => 0,
                                'slug' => $cat['Name']
                            )
                        );
                        if (is_wp_error($term_id)) {
                            $insert_error = "خطا در اضافه شدن دسته بندی " . $cat['Name'] . '<br/>';
                            if (!empty($term_id->error_data))
                                $term_id = $term_id->error_data['term_exists'];
                        } else
                            $term_id = $term_id['term_id'];

                        add_metadata('term', $term_id, 'CategoryID', $cat['ProductCategoryId'], true);

                        bazara_update_taxonomy_term($cat['ProductCategoryId'], $term_id);
                    } else {
                        wp_update_term(
                            $term_ids,
                            'product_cat',
                            array(
                                'name' => $cat['Name'],
                            )
                        );
                    }
                    update_sub_category_isSync($cat['ProductCategoryId']);
                }
                break;
            case 3:

                $extras = get_extra_datas();
                $this->add_sub_cat($extras);

                break;


            default:

                break;
        }
    }
    private function add_sub_cat($ExtraDatas = array(), $pCode = 0)
    {
        if (!is_array($ExtraDatas)) return false;


        foreach ($ExtraDatas as $item) {
            $data = (array)$item;


            $parent_term_a_id = $pCode;
            //            $data = json_decode($item['Data'],true);

            if ((int)$data['ParentID'] > 0) {
                $parent_term_a_id = get_bazara_taxonomy_term($data['ParentID']);
                //                $parent_term_a_id = $category->term_id;
            }

            try {
                $term_ids = get_bazara_taxonomy_term($data['CategoryID']);
                $category_exist = get_metadata('term', $term_ids, 'CategoryCode', true);
                $term = get_term_by('name', $data['CategoryName'], 'product_cat');
                $insert_error = '';
                if (!$category_exist) {
                    //if (!$term){

                    $term_id = wp_insert_term(
                        $data['CategoryName'], // the term
                        'product_cat', // the taxonomy
                        array(
                            'parent' => $parent_term_a_id,
                        )
                    );

                    if (is_wp_error($term_id)) {
                        $insert_error .= "خطا در اضافه شدن دسته بندی " . $data['CategoryName'] . '<br/>';
                        if (!empty($term_id->error_data))
                            $term_id = $term_id->error_data['term_exists'];
                    } else
                        $term_id = $term_id['term_id'];


                    add_metadata('term', $term_id, 'CategoryCode', $data['CategoryID'], true);

                    bazara_update_taxonomy_term($data['CategoryID'], $term_id);
                } else {
                    $parent_term_a_id = get_bazara_taxonomy_term($data['ParentID']);

                    wp_update_term(
                        $term_ids,
                        'product_cat',
                        array(
                            'name' => $data['CategoryName'],
                            'parent' => $parent_term_a_id,

                        )
                    );
                }
                update_category_isSync($data['ExtraDataId']);
            } catch (Exception $ex) {

                return false;
            }
        }
    }
    //     public function start_sync_category($token=null,$catGroup = 2){

    //         if(empty($token)){
    //             $token_result = $this->login_token();
    //             if(!$token_result['success'])
    //                 return array('success' => false, 'message' => $token_result['message']);
    //             $token = $token_result['message'];
    //         }
    //         switch ($catGroup)
    //         {
    //             case 1:


    //                 $categories = bazara_get_categories();

    //                 foreach ($categories as $cat) {

    //                     if ($cat['Deleted'] == true) continue;

    //                     $term_ids = get_bazara_taxonomy_term($cat['ProductCategoryId']);
    //                     $category_exist = get_metadata('term', $term_ids, 'CategoryID', true);

    //                     if (!$category_exist){
    //                         $term_id = wp_insert_term(
    //                             $cat['Name'], // the term
    //                             'product_cat', // the taxonomy
    //                             array(
    //                                 'parent'=> 0,
    //                                 'slug' => $cat['ProductCategoryId']
    //                             )
    //                         );
    //                         add_metadata('term', $term_id['term_id'], 'CategoryID', $cat['ProductCategoryId'], true);
    //                         bazara_update_taxonomy_term($cat['ProductCategoryId'],$term_id['term_id']);
    //                     }else{
    //                         wp_update_term(
    //                             $term_ids,
    //                             'product_cat',
    //                             array(
    //                                 'name' => $cat['Name'],
    //                             )
    //                         );
    //                     }
    //                     update_sub_category_isSync($cat['ProductCategoryId']);
    //                 }
    //                 break;
    //             case 3:

    //                 $extras = get_extra_datas();
    //                 $this->add_sub_cat($extras);

    //                 break;


    //             default:

    //                 break;
    //         }

    //     }
    //     private function add_sub_cat($ExtraDatas = array(),$pCode = 0)
    //     {
    //         if (!is_array($ExtraDatas)) return false;


    //         foreach ($ExtraDatas as $item)
    //         {
    //             $data = (array)$item;


    //             $parent_term_a_id = $pCode;
    // //            $data = json_decode($item['Data'],true);

    //             if ((int)$data['ParentID'] > 0)
    //             {
    //                 $parent_term_a_id = get_bazara_taxonomy_term($data['ParentID']);
    // //                $parent_term_a_id = $category->term_id;
    //             }

    //             try {
    //                 $term_ids = get_bazara_taxonomy_term($data['CategoryID']);
    //                 $category_exist = get_metadata('term', $term_ids, 'CategoryCode', true);
    //                 $term = get_term_by('name', $data['CategoryName'], 'product_cat');
    //                 $insert_error = '';
    //                 if (!$category_exist) {

    //                     $term_id = wp_insert_term(
    //                         $data['CategoryName'], // the term
    //                         'product_cat', // the taxonomy
    //                         array(
    //                             'parent' => $parent_term_a_id,
    //                         )
    //                     );

    //                     if ( is_wp_error( $term_id ) ){
    //                         $insert_error .= "خطا در اضافه شدن دسته بندی ".$data['CategoryName'].'<br/>';
    //                         if (!empty($term_id->error_data))
    //                         $term_id = $term_id->error_data['term_exists'];

    //                     }else
    //                         $term_id = $term_id['term_id'];

    //                     add_metadata('term', $term_id, 'CategoryCode', $data['CategoryID'], true);

    //                     bazara_update_taxonomy_term($data['CategoryID'],$term_id);

    //                 }else{
    //                     $parent_term_a_id = get_bazara_taxonomy_term($data['ParentID']);

    //                     wp_update_term(
    //                         $term_ids,
    //                         'product_cat',
    //                         array(
    //                             'name' => $data['CategoryName'],
    //                             'parent' => $parent_term_a_id,

    //                         )
    //                     );
    //                 }
    //                 update_category_isSync($data['ExtraDataId']);
    //             }catch (Exception $ex){

    //                 return false;
    //                 Bz_Import_Export_For_Woo_Basic_Logwriter::write_log('خطا در اضافه شدن دسه بندی', 'Error ', json_encode($ex));  

    //             }

    //         }

    //     }

    public function set_visitor_options($token = null, $selectedVisitor = null)
    {
        try {
            if (empty($token)) {
                $token_result = $this->login_token();
                if (!$token_result['success'])
                    return array('success' => false, 'message' => $token_result['message']);
                $token = $token_result['message'];
            }
            $latest_rowVersion =  empty(get_last_row_version("Banks")) ? 0 : (get_last_row_version("Banks") + 1);
            $latest_PersonRowVersion =  empty(get_last_row_version("Persons")) ? 0 : (get_last_row_version("Persons") + 1);

            $data = array(
                'fromVisitorVersion' => 0,
                'fromBankVersion' => $latest_rowVersion,
                'fromPersonVersion' => $latest_PersonRowVersion,

            );
            $product_result = $this->get_all_data($token, $data);
            if (!$product_result['success'])
                return array('success' => false, 'message' => $product_result['success']);



            if (empty($selectedVisitor))
                $selectedVisitor = $this->visitor_options['VisitorId'];

            $visit = null;
            foreach ($product_result['message']['Visitors'] as $visitor) {
                if ($visitor['IsActive'] == false) continue;
                if ($selectedVisitor != $visitor['VisitorId']) continue;
                $Store = (int)$this->get_store_id($token, $visitor['StoreCode']);
                $visitor['StoreID'] = $Store;
                $visit = $visitor;
            }

            $Banks =   $product_result['message']['Banks'];
            if (!empty($Banks)) {
                usort($Banks, function ($item1, $item2) {
                    if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                    return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                });
                foreach ($Banks as $bank) {

                    $product_items = array(
                        'BankId' => $bank['BankId'],
                        'BankClientId' => ($bank['BankClientId']),
                        'BankCode' => $bank['BankCode'],
                        'Name' => $bank['Name'],
                        'Description' => $bank['Description'],
                        'Deleted' => ($bank['Deleted'] == 'true' ? 1 : 0),
                        'RowVersion' => $bank['RowVersion'],
                    );

                    insert('bazara_banks', $product_items, 'BankId', $bank['BankId']);
                    bazara_update_latest_versions('banks', $bank['RowVersion']);
                }
            }

            $Peoples =   $product_result['message']['People'];
            if (!empty($Peoples)) {
                usort($Peoples, function ($item1, $item2) {
                    if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                    return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                });
                foreach ($Peoples as $People) {

                    $product_items = array(
                        'PersonId' => $People['PersonId'],
                        'PersonClientId' => ($People['PersonClientId']),
                        'PersonGroupId' => ($People['PersonGroupId']),
                        'PersonCode' => ($People['PersonCode']),
                        'FirstName' => $People['FirstName'],
                        'LastName' => $People['LastName'],
                        'Email' => $People['Email'],
                        'Deleted' => ($People['Deleted'] == 'true' ? 1 : 0),
                        'RowVersion' => $People['RowVersion'],
                        'Mobile' => $People['Mobile'],
                        'Address' => $People['Address'],
                    );

                    insert('bazara_persons', $product_items, 'PersonId', $People['PersonId']);
                    bazara_update_latest_versions('persons', $People['RowVersion']);
                }
            }


            if (!empty($visit))
                update_option('bazara_visitor_options', $visit);
        } catch (\Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
    private function get_store_id($token, $StoreCode = 0)
    {
        $data = array(
            'fromStoreVersion' => 0
        ); //fetch all products
        $product_result = $this->get_all_data($token, $data);
        $StoreID = 0;
        if (!$product_result['success'])
            return array('success' => false, 'message' => $product_result['success']);
        foreach ($product_result['message']['Stores'] as $store) {
            if ($store['StoreCode'] == $StoreCode) $StoreID = $store['StoreId'];
        }

        return $StoreID;
    }
    public function start_sync_new_product($min = 0, $max = 20, $schd = false, $selected_ids = null)
    {
        try {
            global $Qauntity;
            $error_message = "";
            $message = "";
            $success = 0;
            $errors = 0;
            $ProductArray = [];
            
            // If selected_ids is provided, only get those products
            if ($selected_ids !== null) {
                $products = get_products_v3(false, $min, $max, $schd, $selected_ids);
            } else {
                $products = get_products_v3(false, $min, $max, $schd);
            }
            
            $properties = get_properties();
            $extraDatas = get_extras();
            $Qauntity = class_exists('bazara_ratio_calculator') ? 'Count2' : 'Count1';
            $wp_attrs = $attr_item = $wp_attributes = array();
            $attributes = wc_get_attribute_taxonomies();
            $data = array();
            if (!empty($attributes)) {
                foreach ($attributes as $key => $value) {
                    $attr_item['slug'] = $attributes[$key]->attribute_name;
                    $attr_item['label'] = $attributes[$key]->attribute_label;

                    $wp_attributes[] = $attr_item;
                }
            }


            $Voptions = get_bazara_visitor_settings();
            $VoptionBarcode = $Voptions['barcode'] == 1 || $Voptions['barcode'];
            $options = $this->visitor_settings;
            $forVariation = empty($options['forVariation']) ? 0 : ($options['forVariation'] == 1 || $options['forVariation']);
            $SelectedVariation = !empty($options['variationVisibilityType']) ? ($options['variationVisibilityType']) : '';
            $chkVariationVisibility = !empty($options['chkVariationVisibility']) ? $options['chkVariationVisibility'] : '';
            $visibleVariation = toggle_to_boolean(!empty($options['visibleVariation']) ? $options['visibleVariation'] : '');
            $variation_date_condition = !empty($options['variation_date_condition']) ? $options['variation_date_condition'] : '';
            $store_priority_value = !empty($options['StoresSortOrder']) ? explode(',', $options['StoresSortOrder']) : '';
            $store_priority_toggle = !empty($options['StorePriorityToggle']) && $options['StorePriorityToggle'];

            $chkRegularPrice = !empty($options['chkRegularPrice']) && $options['chkRegularPrice'];
            $chkSalePrice = !empty($options['chkSalePrice']) && $options['chkSalePrice'];
            $discountType = !empty($options['discount']) ? $options['discount'] : BAZARA_PRODUCT_PERCENT_DISCOUNT;

            if (class_exists("sell_simple_with_date_variants")) {
                $expireDateArray = [];
                $expireDateArray[0]['month'] = !empty($options['dateFirstCond']) ? sanitize_text_field($options['dateFirstCond']) : '';
                $expireDateArray[0]['price'] = !empty($options['dateFirstCondPrice']) ? sanitize_text_field($options['dateFirstCondPrice']) : '';
                $expireDateArray[0]['discount'] = !empty($options['dateFirstCondDiscount']) ? sanitize_text_field($options['dateFirstCondDiscount']) : '';
                $expireDateArray[1]['month'] = !empty($options['dateSecondCond']) ? sanitize_text_field($options['dateSecondCond']) : '';
                $expireDateArray[1]['price'] = !empty($options['dateSecondCondPrice']) ? sanitize_text_field($options['dateSecondCondPrice']) : '';
                $expireDateArray[1]['discount'] = !empty($options['dateSecondCondDiscount']) ? sanitize_text_field($options['dateSecondCondDiscount']) : '';
                $expireDateArray[2]['month'] = !empty($options['dateThirdCond']) ? sanitize_text_field($options['dateThirdCond']) : '';
                $expireDateArray[2]['price'] = !empty($options['dateThirdCondPrice']) ? sanitize_text_field($options['dateThirdCondPrice']) : '';
                $expireDateArray[2]['discount'] = !empty($options['dateThirdCondDiscount']) ? sanitize_text_field($options['dateThirdCondDiscount']) : '';
                usort($expireDateArray, fn($a, $b) => $b['month'] <=> $a['month']);
            }
            if (class_exists("ChequeShipping")) {
                $chequeLevel = !empty($options['bazara_regular_multiprice_cheque_select']) && $options['bazara_regular_multiprice_cheque_select'];
            }
            $Discount = $RegularPrice = 0;
            if ($chkRegularPrice) {
                $DiscountPriceOrPercent = $options['DiscountPriceOrPercent'];
                $RegularPrice = $options['RegularPrice'];
            }
            
            

            foreach ($products as $product) {

                $barcode = is_object($product)
                    ? ($product->barcode ?? null)
                    : ($product['barcode'] ?? null);

                if ($VoptionBarcode && empty($barcode)) {
                    continue;
                }

                // bazara_sync_log(
                //     'START | ProductId=' . $product->ProductId .
                //     ' | ProductCode=' . $product->ProductCode .
                //     ' | Name=' . $product->ProductName
                // );                

                $date = date('Y/m/d h:i:s a', time());

                $store = '';
                if (!$store_priority_toggle)
                    $store = $product->store_id;


                if (!is_array($store_priority_value))
                    $store = $product->store_id;
                else
                    $store = implode(',', $store_priority_value);



                $productDetail = get_product_details($product->ProductId, $store);
                
 

                $product_items = array(
                    'ProductId' => $product->ProductId,
                    'ProductCode' => $product->ProductCode,
                    'ProductName' => bazara_arabicToPersian($product->ProductName),
                    'Status' => $product->Status,
                    'Category' => $product->Category,
                    'catalog_visibility' => 'visible',
                    'TaxPercent' => $product->TaxPercent,
                    'ChargePercent' => $product->ChargePercent,
                    'new' => $product->new,
                    'tax' =>  $product->tax,
                    'manage_stock' => true,
                    'store_id' => $product->store_id,
                    'barcode' => $product->barcode,
                    'detailSync' => $product->detailSync,
                    'stockSync' => $product->stockSync,
                    'priceSync' => $product->priceSync,
                    'qty' => $product->qty,
                    'width' => $product->width,
                    'height' => $product->height,
                    'weight' => $product->weight,
                    'length' => $product->length,
                    'description' => $product->description,
                    'unitRatio' => $product->unitRatio,
                    'inProcess' => 0,
                    'unitName1' => $product->unitName1,
                    'unitName2' => $product->unitName2,
                    'sku' => $product->ProductCode
                );

                $deleted  = true;
                $productDetails = $prop =  [];
                $visitorPCount = 0;
                $c = 0;
                $position = 0;
                $SellBaseOnDate = false;
                $quantities = [];
                $not = null;
                
                
                
                foreach ($productDetail as $price) {
                    
                    if ((int)$price->Deleted === 1) {

                        $variation = get_product_variation($not, $price->ProductDetailId);
                    
                        if (!$variation || !is_object($variation)) {
                            continue;
                        }
                    
                        $id = $variation->get_id();
                    
                        if (empty($id)) {
                            continue;
                        }
                    
                        wh_deleteProduct($id, true);
                    }
                    
                    
                    
                    bz_cleanup_duplicate_variations_from_report_v2($price->ProductDetailId,100);

                    
                    if (class_exists("bazara_second_count"))
                        $price->Count1 = $price->Count2;

                    if (empty($price->ProductDetailId)) continue;
                    if ($price->NotVariation == 1 && !empty($price->Properties)) continue;

                    $productDetails[] = $price->ProductDetailId;

                    $variable = $variant_items = [];
                    $prices  = text_to_json($price->Prices);
                    $discounts  = text_to_json($price->Discounts);
                    $regular_price = $ProductPrice = $discount = 0;
                    if ($chkRegularPrice) {
                        $ProductPrice = $prices["{$RegularPrice}"]["Price{$RegularPrice}"];
                        if (class_exists("ChequeShipping") && !empty($chequeLevel)) {
                            $ChequeProductPrice = $prices["{$chequeLevel}"]["Price{$chequeLevel}"];
                        }
                    } else {
                        $level         =    ($price->DefaultSellPriceLevel == -1 ? 1 : $price->DefaultSellPriceLevel);
                        $ProductPrice = $prices["{$level}"]["Price{$level}"];
                    }
                    if ($chkSalePrice) {
                        if ($discountType == BAZARA_PRODUCT_PERCENT_DISCOUNT) {
                            if (!empty($discounts["{$DiscountPriceOrPercent}"]))
                                $discount = $discounts["{$DiscountPriceOrPercent}"]["Discount{$DiscountPriceOrPercent}"];

                            if ($discount > 0) {
                                $regular_price = $ProductPrice - (($ProductPrice * $discount) / 100);
                            }
                        } else if ($discountType == BAZARA_PRODUCT_PRICE_DISCOUNT) {
                            $regular_price = $prices["{$DiscountPriceOrPercent}"]["Price{$DiscountPriceOrPercent}"];
                            if ($regular_price == $ProductPrice)
                                $regular_price = 0;
                        }
                    } else
                        $regular_price = -1;


                    if (!empty($price->Properties)) {

                        $obj = json_decode($price->Properties, true);
                        $product_items['Objects'][] =  $obj;

                        foreach ($properties as $property) {
                            $emptyCounter = 0;
                            $slug = ((in_array(convert_non_persian_chars_to_persian($property->Title), array_column($wp_attributes, 'label')) ? $wp_attributes[array_search(convert_non_persian_chars_to_persian($property->Title), array_column($wp_attributes, 'label'))]['slug'] : convert_non_persian_chars_to_persian($property->Title)));
                            $pTitle = $slug; // class_exists("bazaraSlug") ? $slug : convert_non_persian_chars_to_persian($property->Title);
                            foreach ($obj as $std) {
                                if ($std['C'] != $property->PropertyDescriptionCode) continue;
                                $v = implode('-', explode(' ', $std['V']));
                                if (class_exists("bazara_default_properties")) {
                                    if (empty($v)) {
                                        $v = "پیش فرض";
                                    }
                                }

                                $product_items['vars'][$c]['attr'][("pa_" . sanitize_title($pTitle))] =  sanitize_title(bazara_arabicToPersian($v));
                                if ($property->DataType == BAZARA_PROPERTY_DATE_TYPE) {
                                    $d = $std['V'];
                                    $result = substr($d, 0, 4);
                                    if ($result < 2000) {
                                        if (!function_exists('jdate'))
                                            require_once plugin_dir_path(__FILE__) . '../libs/jdf.php';
                                        $std['V'] = jalali_to_timestamp($d);
                                    }
                                    $product_items['vars'][$c]['expireDate'] =  strtotime(($std['V']));
                                    $SellBaseOnDate = strtotime(($std['V']));
                                }
                            }
                        }

                        if (class_exists("sell_simple_with_date_variants") && !empty($SellBaseOnDate) && is_array($expireDateArray) && $chkRegularPrice) {

                            foreach ($expireDateArray as $exp) {
                                $month = $exp['month'];
                                if ($month == 13) {
                                    $selectedExpireDate = strtotime("+1 year");
                                    if ($SellBaseOnDate > $selectedExpireDate) {
                                        $Sellprice = $exp['price'];
                                        $Discount = $exp['discount'];
                                        $ProductPrice = $prices["{$Sellprice}"]["Price{$Sellprice}"];
                                        $regular_price = $prices["{$Discount}"]["Price{$Discount}"];
                                    }
                                }
                                $selectedExpireDate = strtotime("+$month month");
                                if ($SellBaseOnDate < $selectedExpireDate) {
                                    $Sellprice = $exp['price'];
                                    $Discount = $exp['discount'];
                                    $ProductPrice = $prices["{$Sellprice}"]["Price{$Sellprice}"];
                                    $regular_price = $prices["{$Discount}"]["Price{$Discount}"];
                                }
                            }
                        }
                        if (class_exists('bazara_ratio_calculator'))
                            $product_items['vars'][$c]['attr'][("pa_" . sanitize_title($product->unitName1))] = bazara_arabicToPersian(str_replace('.', '-', number_format($price->Count1, 2)));
                        if (((int)$price->Count1) > 0)
                            $quantities[] = $price->Count1;
                        $product_items['vars'][$c]['detail_id'] =  $price->ProductDetailId;
                        $product_items['vars'][$c]['qty'] =  $price->$Qauntity;
                        $product_items['vars'][$c]['qty2'] = number_format($price->Count1, 2);
                        $product_items['vars'][$c]['sku'] =  $product->ProductCode;
                        $product_items['vars'][$c]['Price'] = $ProductPrice;
                        $product_items['vars'][$c]['Regular_price'] =  $regular_price;
                        if (!empty($ChequeProductPrice))
                            $product_items['vars'][$c]['Cheque_price'] =  $ChequeProductPrice;

                        $product_items['vars'][$c]['Prices'] =  $price->Prices;
                        $product_items['vars'][$c]['prop_id'] =  $price->ProductDetailStoreAssetId;
                        $product_items['vars'][$c]['store_id'] =  $product->store_id;
                        $product_items['vars'][$c]['deleted'] =  (class_exists('bazara_ratio_calculator') && ((int)$price->Count1) == 0) ? 1 : $price->Deleted;
                        $product_items['vars'][$c]['tax'] =  ($product->TaxPercent == '-1' ? 0 : $product->TaxPercent);
                        $product_items['vars'][$c]['charge'] =  ($product->ChargePercent == '-1' ? 0 : $product->ChargePercent);

                        $c++;
                    } else {

                        $qty = (float)$price->$Qauntity;
                        $product_items['qty'] = $qty;
                        $product_items['qty2'] = number_format($price->Count1, 2);
                        $product_items['detail_id'] = $price->ProductDetailId;
                    }

                    if (class_exists("sell_simple_with_date_variants") && !empty($SellBaseOnDate) && is_array($expireDateArray)) {

                        foreach ($expireDateArray as $exp) {
                            $month = $exp['month'];
                            if ($month == 13) {
                                $selectedExpireDate = strtotime("+1 year");
                                if ($SellBaseOnDate > $selectedExpireDate) {
                                    $Sellprice = $exp['price'];
                                    $Discount = $exp['discount'];
                                    $ProductPrice = $prices["{$Sellprice}"]["Price{$Sellprice}"];
                                    $regular_price = $prices["{$Discount}"]["Price{$Discount}"];
                                }
                            }
                            $selectedExpireDate = strtotime("+$month month");
                            if ($SellBaseOnDate < $selectedExpireDate) {
                                $Sellprice = $exp['price'];
                                $Discount = $exp['discount'];
                                $ProductPrice = $prices["{$Sellprice}"]["Price{$Sellprice}"];
                                $regular_price = $prices["{$Discount}"]["Price{$Discount}"];
                            }
                        }
                    }
                    $product_items['Price'] = $ProductPrice;
                    $product_items['Regular_price'] =  $regular_price;
                    if (!empty($ChequeProductPrice))
                        $product_items['Cheque_price']  =  $ChequeProductPrice;
                    $product_items['Prices'] =  $price->Prices;
                    if (get_visitor_products_count($price->ProductDetailId, $this->visitor_options['VisitorId']) > 0) {
                        $deleted = false;
                    }

                    if (!empty(get_visitor_products($price->ProductDetailId, $this->visitor_options['VisitorId'])))
                        $visitorPCount++;
                }
                $pa = array();
                if (!empty($product_items['Objects'])) {
                    foreach ($properties as $property) {
                        $slug = ((in_array(convert_non_persian_chars_to_persian($property->Title), array_column($wp_attributes, 'label')) ? $wp_attributes[array_search(convert_non_persian_chars_to_persian($property->Title), array_column($wp_attributes, 'label'))]['slug'] : convert_non_persian_chars_to_persian($property->Title)));
                        $pTitle = $slug; // class_exists("bazaraSlug") ? $slug : convert_non_persian_chars_to_persian($property->Title);

                        foreach ($product_items['Objects'] as $prop => $value) {
                            foreach ($value as $var) {
                                if ($var['C'] != $property->PropertyDescriptionCode) continue;
                                if (class_exists("bazara_default_properties")) {
                                    if (empty($var['V']))
                                        $var['V'] = "پیش فرض";
                                }
                                $pa[$pTitle]['term_names'][] = bazara_arabicToPersian($var['V']);
                            }
                        }
                        if (empty($pa[$pTitle]['term_names'])) {
                            unset($pa[$pTitle]);
                            continue;
                        }
                        if (!empty($SelectedVariation)) {
                            $checkPropertyTypeExist = array_column($SelectedVariation, $property->PropertyDescriptionId);
                            $foundCols = array_values($checkPropertyTypeExist);
                            if (($variation_date_condition == 2 || $variation_date_condition == 3) && $property->DataType == BAZARA_PROPERTY_DATE_TYPE) {
                                usort($pa[$pTitle]['term_names'], 'bazara_sortByEarliest');
                            }
                            $pa[$pTitle]['is_visible'] = !bazara_in_array("invisible", $foundCols) ? false : true;
                            $pa[$pTitle]['for_variation'] =  !bazara_in_array("for_variation", $foundCols) ? false : true;
                            if (class_exists("bazara_default_properties")) {
                                $result = self::find_attr_without_var($pa[$pTitle]['term_names']);
                                $pds = get_product_detail_with_var($product->ProductId);

                                if (count($result) == count($pds)) {
                                    $pa[$pTitle]['for_variation'] = false;
                                }
                            }
                        }

                        $position++;
                    }
                    if (class_exists('bazara_ratio_calculator')) {
                        foreach ($quantities as $quantity) {
                            $pa[$product->unitName1]['term_names'][] = bazara_arabicToPersian(number_format($quantity, 2));
                        }

                        $pa[$product->unitName1]['is_visible'] = !bazara_in_array("invisible", $foundCols) ? false : true;
                        $pa[$product->unitName1]['for_variation'] =  ($position == 0 || !$forVariation);
                    }
                }
                $product_items['Properties'] = $prop;
                $product_items['attributes'] =  $pa;
                $product_items['deleted'] = $visitorPCount == 0 ? true : (empty($productDetail) ? false : $deleted);
                $product_items['ProductDetails'] = $productDetails;
                $product_items['store_id'] = $product->store_id;
                $ProductArray[] = $product_items;
                if ((($visitorPCount == 0) || (check_product_is_deleted($product->ProductId) == 0) ? true : (empty($productDetail) ? false : $deleted)))
                    $product_items['deleted'] = true;

                //                prepare_product_for_creation
                $r = $this->prepare_product_for_creation($product_items, $extraDatas, $schd);

                // if ($r['success']) {
                //     bazara_sync_log(
                //         'SUCCESS | ProductId=' . $product->ProductId .
                //         ' | ProductCode=' . $product->ProductCode
                //     );
                // } else {
                //     bazara_sync_log(
                //         'FAILED | ProductId=' . $product->ProductId .
                //         ' | ProductCode=' . $product->ProductCode .
                //         ' | Message=' . ($r['message'] ?? 'unknown error')
                //     );
                // }
                

                if ($r['success']) {
                    $success++;
                } else {
                    $errors++;
                    $error_message .= '[' . $product->ProductName . '] ' . $r['message'] . '<br/>';
                }
            }
            $message .= 'تعداد ' . $success . ' محصول با موفقیت در سیستم ثبت شد.' . '<br/>';
            if (!empty($error_message)) {
                $message .= 'تعداد ' . $errors . ' محصول با خطا مواجه شد. شامل:' . '<br/>';
                $message .= $error_message;
            }

            // bazara_sync_log(
            //     'END SYNC | success=' . $success . ' | errors=' . $errors
            // );           

            return array('success' => true, 'message' => $message, 'add' => $success, 'failed', $errors);
        } catch (\Exception $e) {
            return array('success' => false, 'message' => json_encode($e->getMessage()));
        }
    }
    function find_attr_without_var($data = array())
    {
        return array_filter(
            $data,
            function ($v) {
                return strpos($v, 'پیش فرض') !== false;
            }
        );
    }
    public function bazara_copy_entities($type = 'products', $min = 0, $max = 20)
    {
        if (empty($token)) {
            $token_result = $this->login_token();
            if (!$token_result['success'])
                return array('success' => false, 'message' => $token_result['message']);
            $token = $token_result['message'];
        }

        // Handle combined product sync
        if ($type === 'ProductSync') {
            return $this->sync_combined_products($token, $min, $max);
        }
        
        if ($type === 'VisitorPersonSync') {
            return $this->sync_visitor_persons($token, $min, $max);
        }
        
        if ($type === 'PersonSync') {
            return $this->sync_combined_persons($token, $min, $max);
        }

        if ($this->entities[$type]['alias'] == 'Settings')
            $latest_rowVersion =  empty(get_last_row_version("Settings")) ? 0 : (get_last_row_version("Settings"));
        else {
            $latest_rowVersion =  empty(get_last_row_version($this->entities[$type]['alias'])) ? 0 : (get_last_row_version($this->entities[$type]['alias']));
            $VisitorProducts_latest_rowVersion = get_last_row_version("VisitorProducts");
            $Prodcutlatest_rowVersion = get_last_row_version("product");
        }


        $data = array(
            "{$this->entities[$type]['entity']}" => empty($latest_rowVersion) ? 0 : ($latest_rowVersion)
        ); //fetch all products



        if ($this->entities[$type]['alias'] == 'product' || $this->entities[$type]['alias'] == 'productDetail') {
            $visitor_latest_rv  = $VisitorProducts_latest_rowVersion;
            $v = array(
                'fromVisitorProductVersion' => $visitor_latest_rv
            );
            $data = array_merge($data, $v);
        }

        if ($this->entities[$type]['alias'] == 'VisitorProducts') {
            $v = array(
                'fromproductversion' => empty($Prodcutlatest_rowVersion) ? 0 : ($Prodcutlatest_rowVersion)
            );
            $data = array_merge($data, $v);
        }
        $product_result = $this->get_all_data($token, $data);
        if (!$product_result['success'])
            return array('count' => 0, 'error' => $product_result['message'], 'success' => false);

        $this->visitor_options = get_bazara_visitor_options();
        $this->visitor_settings = get_bazara_visitor_settings();
        $this->plugin_options = bazara_get_options();
        if (empty($this->visitor_options['StoreID'])) {
            return false;
        }



        switch ($type) {
            case 'Settings':
                $Settings =  $product_result['message']['Settings'];
                if (count($Settings) > 0)
                    update_option('bazara_visitor_soft_settings', $Settings, true);
                break;
            case 'Products':
                $Products =  $product_result['message']['Products'];
                $ChargePercent = 0;
                $TaxPercent = 0;
                $visitorSettings = get_option('bazara_visitor_soft_settings', true);
                if (!empty($visitorSettings) && is_array($visitorSettings)) {

                    foreach ($visitorSettings as $setting) {
                        if (intval($setting['SettingCode']) === 14000)
                            $ChargePercent = intval($setting['Value']);
                        if (intval($setting['SettingCode']) === 14001)
                            $TaxPercent = intval($setting['Value']);
                    }
                }
                if (!empty($Products)) {
                    usort($Products, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });

                    $index = 0;
                    foreach ($Products as $product) {
                        $latesVersion = (empty($latest_rowVersion) ? 0 : ($latest_rowVersion));
                        if ($product['RowVersion'] == $latesVersion) continue;

                        $index++;
                        if ($index <= $min)
                            continue;
                        if ($index > $max)
                            break;

                        if (intval($product['TaxPercent']) != -1) {
                            if (intval($product['TaxPercent']) === 0) {
                                $product['ChargePercent'] = $ChargePercent;
                                $product['TaxPercent'] = $TaxPercent;
                            }
                            $tax = intval($product['TaxPercent']) + intval($product['ChargePercent']);

                            $taxClass = $this->create_woo_tax($tax);
                        }

                        $product_items = array(
                            'ProductId' => $product['ProductId'],
                            'ProductCode' => $product['ProductCode'],
                            'ProductName' => ($product['Name']),
                            'Status' => isset($this->visitor_settings['publishStatus']) ? $this->visitor_settings['publishStatus'] : 'publish',
                            'Category' => $product['ProductCategoryId'],
                            'TaxPercent' => ($product['TaxPercent'] == '-1' ? 0 : $product['TaxPercent']),
                            'ChargePercent' => ($product['ChargePercent'] == '-1' ? 0 : $product['ChargePercent']),
                            'tax' => (empty($taxClass) ? '' : $taxClass),
                            'store_id' => $this->visitor_options['StoreID'],
                            'qty' => 0,
                            'sku' => $product['ProductCode'],
                            'width' => $product['Width'],
                            'weight' => $product['Weight'],
                            'height' => $product['Height'],
                            'length' => $product['Length'],
                            'description' => $product['Description'],
                            'RowVersion' => $product['RowVersion'],
                            'unitName1' => $product['UnitName'],
                            'unitName2' => $product['UnitName2'],
                            'unitRatio' => $product['UnitRatio'],
                            'Deleted' => $product['Deleted'] ? 1 : 0,

                        );
                        $res = insert('bazara_products', $product_items, 'ProductId', $product['ProductId']);
                        bazara_update_latest_versions('product', $product['RowVersion']);
                        update_schedule_sync($product['ProductId'], 'detailSync', 0);
                    }
                    $VisitorProducts_latest_rowVersion = empty(get_last_row_version("VisitorProducts")) ? 0 : get_last_row_version("VisitorProducts");
                    bazara_update_latest_versions('VisitorProducts', $VisitorProducts_latest_rowVersion);
                }

                break;
            case 'ProductDetails':

                $ProductDetails =    $product_result['message']['ProductDetails'];

                if (!empty($ProductDetails)) {
                    usort($ProductDetails, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    $index = 0;
                    $options = $this->visitor_settings;
                    $DefaultRolePrice = empty($options['chkProductsRolePrice']) ? 0 : $options['chkProductsRolePrice'];
                    $RoleRegularPrice = $RolePrice = 0;
                    if ($DefaultRolePrice) {
                        $RoleRegularPrice = $options['selectRegularPrice'];
                        $RolePrice = $options['selectPrice'];
                    }

                    foreach ($ProductDetails as $productdetail) {
                        $latesVersion = (empty($latest_rowVersion) ? 0 : ($latest_rowVersion));
                        if ($productdetail['RowVersion'] == $latesVersion) continue;

                        $index++;

                        if ($index <= $min)
                            continue;
                        if ($index > $max)
                            break;



                        $pricesList = $Discounts = [];
                        for ($i = 1; $i <= 10; $i++) {
                            if (!empty($productdetail["Price{$i}"]));
                            $pricesList[$i]["Price{$i}"] = $productdetail["Price{$i}"];
                            if (isset($productdetail["Discount{$i}"]) && !empty($productdetail["Discount{$i}"]));
                            $Discounts[$i]["Discount{$i}"] = $productdetail["Discount{$i}"];
                        }
                        $product_items = array(
                            'ProductId' => $productdetail['ProductId'],
                            'ProductDetailId' => $productdetail['ProductDetailId'],
                            'Properties' => ($productdetail['Properties']),
                            // 'Price' => $productdetail[$price],
                            // 'Regular_price' => !$hasDiscount ? $productdetail[$regular_price] : $regular_price,
                            'Prices' => json_encode($pricesList),
                            'Discounts' => json_encode($Discounts),
                            'DefaultDiscountLevel' => $productdetail['DefaultDiscountLevel'],
                            'DefaultSellPriceLevel' => $productdetail['DefaultSellPriceLevel'],
                            'RowVersion' => $productdetail['RowVersion'],
                            'Deleted' => $productdetail['Deleted'] ? 1 : 0,
                        );



                        insert('bazara_product_details', $product_items, 'ProductDetailId', $productdetail['ProductDetailId']);
                        bazara_update_latest_versions('productDetail', $productdetail['RowVersion']);
                        $ProductID = get_product_id($productdetail['ProductDetailId']);
                        update_schedule_sync($ProductID, 'priceSync', 0);
                        update_schedule_sync($ProductID, 'detailSync', 0);
                        update_schedule_sync($productdetail['ProductDetailId'], 'isSync', 0, 'bazara_product_details', 'ProductDetailId');
                        if (!empty($productdetail['Barcode']))
                            update_barcode($ProductID, $productdetail['Barcode']);
                    }
                }

                break;

            case 'VisitorProducts':
                $VisitorProducts =   $product_result['message']['VisitorProducts'];
                if (!empty($VisitorProducts)) {
                    usort($VisitorProducts, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    $index = 0;
                    foreach ($VisitorProducts as $visitorProduct) {
                        $index++;
                        //if($visitorProduct['Deleted'] == true)
                        //  continue;
                        if ($index <= $min)
                            continue;
                        if ($index > $max)
                            break;
                        $product_items = array(
                            'VisitorProductId' => $visitorProduct['VisitorProductId'],
                            'ProductDetailId' => ($visitorProduct['ProductDetailId']),
                            'VisitorId' => $visitorProduct['VisitorId'],
                            'Deleted' => ($visitorProduct['Deleted'] == 'true' ? 1 : 0),
                            'RowVersion' => $visitorProduct['RowVersion'],
                        );

                        insert('bazara_visitor_products', $product_items, 'VisitorProductId', $visitorProduct['VisitorProductId']);
                        $ProductID = get_product_id($visitorProduct['ProductDetailId']);
                        update_schedule_sync($ProductID, 'stockSync', 0);
                    }
                }
                break;

            case 'ProductDetailStoreAssets':

                $ProductAssets =     $product_result['message']['ProductDetailStoreAssets'];

                if (!empty($ProductAssets)) {
                    $index = 0;
                    usort($ProductAssets, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    foreach ($ProductAssets as $productAsset) {
                        $index++;
                        //                         if($productAsset['Deleted'] == true)
                        //                            continue;
                        if ($index <= $min)
                            continue;
                        if ($index > $max)
                            break;
                        //if ($this->visitor_options['StoreID'] == $productAsset['StoreId'] && $this->visitor_options['StoreID'] != 0) {
                        $product_items = array(
                            'ProductDetailStoreAssetId' => $productAsset['ProductDetailStoreAssetId'],
                            'ProductDetailId' => ($productAsset['ProductDetailId']),
                            'Count1' => $productAsset['Count1'],
                            'Count2' => $productAsset['Count2'],
                            'StoreId' => $productAsset['StoreId'],
                            'RowVersion' => $productAsset['RowVersion'],
                            'Deleted' => $productAsset['Deleted'] ? 1 : 0,

                        );
                        insert('bazara_product_assets', $product_items, 'ProductDetailStoreAssetId', $productAsset['ProductDetailStoreAssetId']);
                        bazara_update_latest_versions('ProductAsset', $productAsset['RowVersion']);
                        $ProductID = get_product_id($productAsset['ProductDetailId']);
                        update_schedule_sync($ProductID, 'stockSync', 0);
                        update_schedule_sync($productAsset['ProductDetailId'], 'isSync', 0, 'bazara_product_details', 'ProductDetailId');
                        
                        if (class_exists("sell_simple_with_date_variants")){
                            update_schedule_sync($ProductID,'detailSync',0);
                        }
                        // }
                    }
                }
                break;

            case 'PropertyDescriptions':

                $ProductProperties = $product_result['message']['PropertyDescriptions'];

                $attrs = array();

                $attributes = wc_get_attribute_taxonomies();
                if (!empty($attributes)) {
                    foreach ($attributes as $key => $value) {
                        $attrs[$value->attribute_name] = get_terms(wc_attribute_taxonomy_name($value->attribute_name), 'orderby=name&hide_empty=0');
                    }
                }


                if (!empty($ProductProperties)) {

                    foreach ($ProductProperties as $property) {

                        $title = implode('-', explode(' ', $property['Title']));
                        $product_items = array(
                            'PropertyDescriptionId' => $property['PropertyDescriptionId'],
                            'PropertyDescriptionCode' => $property['PropertyDescriptionCode'],
                            'DisplayType' => $property['DisplayType'],
                            'DataType' => $property['DataType'],
                            'Title' => $title,
                            'RowVersion' => $property['RowVersion'],
                            'Deleted' => $property['Deleted'] ? 1 : 0,
                        );

                        if (!in_array($title, $attrs)) {
                            $args = array(
                                'slug' => sanitize_title($title),
                                'name' => $title,
                                'type' => 'select',
                                'orderby' => 'menu_order',
                                'has_archives' => false,
                                'limit' => 1,
                                'is_in_stock' => 1
                            );

                            wc_create_attribute($args);
                            WC_Post_Types::register_taxonomies();
                        }


                        insert('bazara_product_properties', $product_items, 'PropertyDescriptionId', $property['PropertyDescriptionId']);
                        bazara_update_latest_versions('PropertyDescriptions', $property['RowVersion']);
                    }
                }
                break;

            case 'Regions':
                $Regions =           $product_result['message']['Regions'];
                if (!empty($Regions)) {
                    usort($Regions, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    $index = 0;
                    foreach ($Regions as $region) {
                        $index++;

                        if ($index <= $min)
                            continue;
                        if ($index > $max)
                            break;
                        $product_items = array(
                            'CityID' => $region['CityID'],
                            'CityName' => ($region['CityName']),
                            'ProvinceID' => $region['ProvinceID'],
                            'ProvinceName' => $region['ProvinceName'],
                            'MapCode' => $region['MapCode'],
                            'RowVersion' => $region['RowVersion'],
                        );


                        insert('bazara_regions', $product_items, 'CityID', $region['CityID']);
                        bazara_update_latest_versions('Regions', $region['RowVersion']);
                    }
                }
                break;



            case 'PersonGroups':
                $PersonGroups =           $product_result['message']['PersonGroups'];
                if (!empty($PersonGroups)) {
                    usort($PersonGroups, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    $index = 0;

                    foreach ($PersonGroups as $prg) {
                        $index++;

                        if ($index <= $min)
                            continue;
                        if ($index > $max)
                            break;
                        $product_items = array(
                            'PersonGroupId' => $prg['PersonGroupId'],
                            'Name' => ($prg['Name']),
                            'DiscountPercent' => $prg['DiscountPercent'],
                            'SellPriceLevel' => $prg['SellPriceLevel'],
                            'RowVersion' => $prg['RowVersion'],
                        );


                        insert('bazara_person_groups', $product_items, 'PersonGroupId', $prg['PersonGroupId']);
                        bazara_update_latest_versions('PersonGroup', $prg['RowVersion']);
                    }
                    update_wp_roles($PersonGroups);
                }
                break;

            case 'ExtraDatas':
                $ExtraDatas =           $product_result['message']['ExtraDatas'];

                if (!empty($ExtraDatas)) {
                    usort($ExtraDatas, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    $exType = array(130, 10202);
                    $index = 0;

                    foreach ($ExtraDatas as $extraData) {
                        $index++;

                        if ($index <= $min)
                            continue;
                        if ($index > $max)
                            break;

                        $product_items = array(
                            'ExtraDataId' => $extraData['ExtraDataId'],
                            'ItemType' => ($extraData['ItemType']),
                            'ItemId' => $extraData['ItemId'],
                            'Data' => $extraData['Data'],
                            'RowVersion' => $extraData['RowVersion'],
                            'Deleted' => $extraData['Deleted'] ? 1 : 0,
                        );


                        insert('bazara_extra_data', $product_items, 'ExtraDataId', $extraData['ExtraDataId']);
                        bazara_update_latest_versions('ExtraData', $extraData['RowVersion']);
                        if (in_array($extraData['ItemType'], $exType)) {

                            $data = json_decode($extraData['Data'], true);

                            $product_items = array(
                                'ExtraDataId' => $extraData['ExtraDataId'],
                                'CategoryID' => $data['CategoryCode'],
                                'CategoryName' => $data['CategoryName'],
                                'ItemType' => $extraData['ItemType'],
                                'ParentID' => ($extraData['ItemType'] == 130  ? $data['ParentCode'] : $data['ProductCode']),
                                'isSync'   => 0
                            );
                            insert('bazara_category', $product_items, 'ExtraDataId', $extraData['ExtraDataId']);
                            if ($extraData['ItemType'] == 10202) {
                                change_product_issync_value($data['ProductCode']);
                            }
                        }
                    }
                }
                break;
            case 'SubCategory':
                $Categories =           $product_result['message']['ProductCategories'];

                if (!empty($Categories)) {
                    usort($Categories, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    $index = 0;

                    foreach ($Categories as $catgory) {
                        $index++;

                        if ($index <= $min)
                            continue;
                        if ($index > $max)
                            break;
                        // if (in_array($extraData['ItemType'],$exType)) {

                        $product_items = array(
                            'ProductCategoryId' => $catgory['ProductCategoryId'],
                            'Name' => ($catgory['Name']),
                            'RowVersion' => $catgory['RowVersion'],
                            'Deleted' => $catgory['Deleted'] ? 1 : 0,
                            'isSync'   => 0

                        );


                        insert('bazara_sub_category', $product_items, 'ProductCategoryId', $catgory['ProductCategoryId']);
                        bazara_update_latest_versions('Categories', $catgory['RowVersion']);


                        // }
                    }
                }
                break;
            case 'Pictures':
                $Pictures =           $product_result['message']['Pictures'];
                if (!empty($Pictures)) {
                    usort($Pictures, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    $index = 0;

                    foreach ($Pictures as $pic) {
                        $index++;

                        if ($index <= $min)
                            continue;
                        if ($index > $max)
                            break;
                        $product_items = array(
                            'PictureId' => $pic['PictureId'],
                            'FileName' => ($pic['FileName']),
                            'Url' => $pic['Url'],
                            'RowVersion' => $pic['RowVersion'],
                            'queue' => 0,
                            'isSync' => 0,
                            'Deleted' => ($pic['Deleted'] ? 1 : 0),

                        );


                        insert('bazara_pictures', $product_items, 'PictureId', $pic['PictureId']);
                        bazara_update_latest_versions('picture', $pic['RowVersion']);
                        update_picture_status($pic['PictureId'], 0);
                    }
                }
                break;

            case 'PhotoGalleries':
                $PhotoGalleries =           $product_result['message']['PhotoGalleries'];
                if (!empty($PhotoGalleries)) {
                    usort($PhotoGalleries, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });

                    $index = 0;

                    foreach ($PhotoGalleries as $gallery) {
                        $index++;
                        if ($gallery['Deleted'] == true)
                            continue;
                        if ($index <= $min)
                            continue;
                        if ($index > $max)
                            break;
                        $product_items = array(
                            'PhotoGalleryId' => $gallery['PhotoGalleryId'],
                            'PictureId' => $gallery['PictureId'],
                            'ItemCode' => ($gallery['ItemCode']),
                            'RowVersion' => $gallery['RowVersion'],
                            'Deleted' => ($gallery['Deleted'] == 'true' ? 1 : 0),

                        );


                        insert('bazara_photo_gallery', $product_items, 'PhotoGalleryId', $gallery['PhotoGalleryId']);
                        bazara_update_latest_versions('photo_gallery', $gallery['RowVersion']);
                    }
                }
                break;
            case 'Banks':
                $Banks =   $product_result['message']['Banks'];
                if (!empty($Banks)) {
                    usort($Banks, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    $index = 0;

                    foreach ($Banks as $bank) {

                        $product_items = array(
                            'BankId' => $bank['BankId'],
                            'BankClientId' => ($bank['BankClientId']),
                            'BankCode' => $bank['BankCode'],
                            'Name' => $bank['Name'],
                            'Description' => $bank['Description'],
                            'Deleted' => ($bank['Deleted'] == 'true' ? 1 : 0),
                            'RowVersion' => $bank['RowVersion'],
                        );

                        insert('bazara_banks', $product_items, 'BankId', $bank['BankId']);
                        bazara_update_latest_versions('banks', $bank['RowVersion']);
                    }
                }
                break;
            case 'Stores':
                $Stores =   $product_result['message']['Stores'];
                if (!empty($Stores)) {
                    usort($Stores, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    $index = 0;

                    foreach ($Stores as $store) {

                        $product_items = array(
                            'StoreId' => $store['StoreId'],
                            'StoreCode' => ($store['StoreCode']),
                            'Name' => $store['Name'],
                            'Comment' => $store['Comment'],
                            'Deleted' => ($store['Deleted'] == 'true' ? 1 : 0),
                            'RowVersion' => $store['RowVersion'],
                        );

                        insert('bazara_stores', $product_items, 'StoreId', $store['StoreId']);
                        bazara_update_latest_versions('stores', $store['RowVersion']);
                    }
                }
                break;
            case 'Persons':
                $Peoples =   $product_result['message']['People'];
                if (!empty($Peoples)) {
                    usort($Peoples, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    $index = 0;
                    foreach ($Peoples as $People) {
                        $latesVersion = (empty($latest_rowVersion) ? 0 : ($latest_rowVersion));
                        if ($People['RowVersion'] == $latesVersion) continue;

                        $index++;
                        if ($index <= $min)
                            continue;
                        if ($index > $max)
                            break;

                        $product_items = array(
                            'PersonId' => $People['PersonId'],
                            'PersonClientId' => ($People['PersonClientId']),
                            'PersonGroupId' => ($People['PersonGroupId']),
                            'PersonCode' => ($People['PersonCode']),
                            'FirstName' => $People['FirstName'],
                            'LastName' => $People['LastName'],
                            'Email' => $People['Email'],
                            'Deleted' => ($People['Deleted'] == 'true' ? 1 : 0),
                            'RowVersion' => $People['RowVersion'],
                            'isSync' => 0,
                            'Mobile' => $People['Mobile'],
                            'Address' => $People['Address'],
                        );

                        insert('bazara_persons', $product_items, 'PersonId', $People['PersonId']);
                        bazara_update_latest_versions('persons', $People['RowVersion']);
                    }
                }
                break;

                case 'VisitorPersons':
                    $VisitorPersons = $product_result['message']['VisitorPersons'];
                    if (!empty($VisitorPersons)) {
                        usort($VisitorPersons, function ($item1, $item2) {
                            if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                            return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                        });
                        $index = 0;
                        foreach ($VisitorPersons as $visitorPerson) {
                            $index++;
                            if ($index <= $min)
                                continue;
                            if ($index > $max)
                                break;
                            $product_items = array(
                                'VisitorPersonId' => $visitorPerson['VisitorPersonId'],
                                'PersonId' => ($visitorPerson['PersonId']),
                                'VisitorId' => $visitorPerson['VisitorId'],
                                'Deleted' => ($visitorPerson['Deleted'] == 'true' ? 1 : 0),
                                'RowVersion' => $visitorPerson['RowVersion'],
                            );
    
                            insert('bazara_visitor_persons', $product_items, 'VisitorPersonId', $visitorPerson['VisitorPersonId']);
                        }
                    }
                    break;

            case 'Transactions':
                $Transactions =   $product_result['message']['Transactions'];
                if (!empty($Transactions)) {
                    usort($Transactions, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    foreach ($Transactions as $Transaction) {

                        $product_items = array(
                            'TransactionId' => $Transaction['TransactionId'],
                            'Row' => ($Transaction['Row']),
                            'PersonId' => ($Transaction['PersonId']),
                            'Type' => ($Transaction['Type']),
                            'DebtAmount' => $Transaction['DebtAmount'],
                            'CreditAmount' => $Transaction['CreditAmount'],
                            'Balance' => $Transaction['Balance'],
                            'Status' => $Transaction['Status'],
                            'Description' => $Transaction['Description'],
                            'CreateDate' => $Transaction['CreateDate'],
                            'UpdateDate' => $Transaction['UpdateDate'],
                            'Deleted' => ($Transaction['Deleted'] == 'true' ? 1 : 0),
                            'RowVersion' => $Transaction['RowVersion'],
                            'Date' => $Transaction['Date'],

                        );

                        insert('bazara_transactions', $product_items, 'TransactionId', $Transaction['TransactionId']);
                        bazara_update_latest_versions('transactions', $Transaction['RowVersion']);
                    }
                }
                break;

            case 'Orders':
                $Orders =   $product_result['message']['Orders'];
                if (!empty($Orders)) {
                    usort($Orders, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    foreach ($Orders as $order) {

                        $product_items = array(
                            'OrderId' => $order['OrderId'],
                            'OrderClientId' => ($order['OrderClientId']),
                            'OrderCode' => ($order['OrderCode']),
                            'PersonId' => ($order['PersonId']),
                            'OrderDate' => $order['OrderDate'],
                            'Deleted' => $order['Deleted'],
                            'RowVersion' => $order['RowVersion']

                        );

                        insert('bazara_orders', $product_items, 'OrderId', $order['OrderId']);
                        bazara_update_latest_versions('orders', $order['RowVersion']);
                    }
                }
                break;
            case 'OrderDetails':
                $OrderDetails =   $product_result['message']['OrderDetails'];
                if (!empty($OrderDetails)) {
                    usort($OrderDetails, function ($item1, $item2) {
                        if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                        return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
                    });
                    foreach ($OrderDetails as $order) {

                        $product_items = array(
                            'OrderId' => $order['OrderId'],
                            'OrderDetailId' => ($order['OrderDetailId']),
                            'OrderDetailClientId' => ($order['OrderDetailClientId']),
                            'ProductDetailId' => ($order['ProductDetailId']),
                            'Count1' => $order['Count1'],
                            'Count2' => $order['Count2'],
                            'Price' => $order['Price'],
                            'Deleted' => $order['Deleted'],
                            'RowVersion' => $order['RowVersion']

                        );

                        insert('bazara_order_details', $product_items, 'OrderDetailId', $order['OrderDetailId']);
                        bazara_update_latest_versions('OrderDetails', $order['RowVersion']);
                    }
                }
                break;
        }
        return array('success' => true, 'message' => '');
    }


    public function sync_pictures()
{
    $error_message = "";
    $message = "";
    $success = 0;
    $errors = 0;

    // دریافت تصاویر حذف شده
    global $wpdb;
    $deleted_pictures_query = "SELECT {$wpdb->prefix}bazara_pictures.PictureId as PictureId,
                                     {$wpdb->prefix}bazara_photo_gallery.ItemCode as ItemCode
                              FROM {$wpdb->prefix}bazara_pictures 
                              JOIN {$wpdb->prefix}bazara_photo_gallery 
                              ON {$wpdb->prefix}bazara_pictures.PictureId = {$wpdb->prefix}bazara_photo_gallery.PictureId 
                              WHERE {$wpdb->prefix}bazara_pictures.Deleted = 1";
    
    $deleted_pictures = $wpdb->get_results($deleted_pictures_query);
    
    // حذف تصاویر حذف شده از محصولات
    foreach ($deleted_pictures as $deleted_pic) {
        $objProduct = get_product_by_mahakID($deleted_pic->ItemCode);
        if (!empty($objProduct)) {
            $product_id = $objProduct->get_id();
            
            // جستجوی تصویر با شناسه محک
            $args = array(
                'post_status' => 'inherit',
                'post_type' => 'attachment',
                'meta_query' => array(
                    array(
                        'key' => 'mahak_picture_id',
                        'value' => $deleted_pic->PictureId
                    )
                )
            );
            
            $posts = get_posts($args);
            if (!empty($posts)) {
                $attachment_id = $posts[0]->ID;
                
                // حذف از تصویر شاخص اگر باشد
                if (get_post_thumbnail_id($product_id) == $attachment_id) {
                    $objProduct->set_image_id('');
                }
                
                // حذف از گالری تصاویر
                $gallery_ids = $objProduct->get_gallery_image_ids();
                $gallery_ids = array_diff($gallery_ids, array($attachment_id));
                $objProduct->set_gallery_image_ids($gallery_ids);
                
                $objProduct->save();
                $success++;
            }
        }
    }

    // دریافت و پردازش تصاویر جدید
    $pictures = get_pictures();
    $processed_products = []; // برای ردیابی محصولاتی که پردازش شده‌اند

    // گروه‌بندی تصاویر بر اساس محصول
    $product_pictures = [];
    foreach ($pictures as $pic) {
        $pic = (array)$pic;
        $product_pictures[$pic['ItemCode']][] = $pic;
    }

    foreach ($product_pictures as $item_code => $pics) {
        $objProduct = get_product_by_mahakID($item_code);
        if (empty($objProduct)) continue;

        $product_id = $objProduct->get_id();

        // حذف کامل تصویر شاخص و گالری برای محصول
        if (!isset($processed_products[$product_id])) {
            // حذف تصویر شاخص و گالری با استفاده از متدهای ووکامرس
            $objProduct->set_image_id(''); // حذف تصویر شاخص
            $objProduct->set_gallery_image_ids(array()); // حذف گالری تصاویر
            $objProduct->save(); // ذخیره تغییرات
            $processed_products[$product_id] = true;
        }

        // پردازش تصاویر محصول
        $first_image = true; // برای تنظیم اولین تصویر به‌عنوان شاخص
        foreach ($pics as $pic) {
            update_picture_queue($pic['PictureId'], 1);

            try {
                $attach_id = null;
                $FileName = basename($pic['Url']);
                $expected_url = $this->img_url . $FileName;

                // جستجوی تصویر با شناسه محک
                $args = array(
                    'post_status' => 'inherit',
                    'post_type' => 'attachment',
                    'meta_query' => array(
                        array(
                            'key' => 'mahak_picture_id',
                            'value' => $pic['PictureId']
                        )
                    )
                );

                $posts = get_posts($args);

                // اگر تصویر وجود داشت اما نام فایل متفاوت بود، تصویر جدید آپلود شود
                if (!empty($posts)) {
                    $existing_id = $posts[0]->ID;
                    $attachment_url = wp_get_attachment_url($existing_id);

                    if (strpos($attachment_url, $FileName) === false) {
                        // فایل متفاوت است → آپلود جدید
                        $attach_id = uploadMedia($expected_url, $FileName);
                    } else {
                        $attach_id = $existing_id;
                    }
                } else {
                    // تصویر وجود نداشت → آپلود جدید
                    $attach_id = uploadMedia($expected_url, $FileName);
                }

                // بررسی موفقیت آپلود
                if (!is_wp_error($attach_id) && $attach_id) {
                    // ثبت متاهای اضافی
                    update_post_meta($attach_id, 'mahak_picture_id', $pic['PictureId']);
                    update_post_meta($attach_id, 'mahak_row_version', $pic['RowVersion']);

                    // تنظیم تصویر شاخص یا اضافه کردن به گالری
                    if ($first_image) {
                        $objProduct->set_image_id($attach_id);
                        $first_image = false;
                    } else {
                        $productImagesIDs = $objProduct->get_gallery_image_ids();
                        $productImagesIDs[] = $attach_id;
                        $productImagesIDs = array_unique($productImagesIDs);
                        $objProduct->set_gallery_image_ids($productImagesIDs);
                    }

                    $objProduct->save();

                    $success++;
                    update_picture_status($pic['PictureId']);
                    update_picture_queue($pic['PictureId'], 0);
                    bazara_update_latest_versions('picture', $pic['RowVersion']);
                } else {
                    $errors++;
                    $error_message .= '[' . $objProduct->get_name() . '] ' . $attach_id->get_error_message() . '<br/>';
                }

            } catch (\Exception $e) {
                $errors++;
                $error_message .= '[' . $objProduct->get_name() . '] ' . $e->getMessage() . '<br/>';
            }
        }
    }

    $message .= 'تعداد ' . $success . ' تصویر با موفقیت در سیستم ثبت شد.<br/>';
    Bz_Import_Export_For_Woo_Basic_Logwriter::write_log('دریافت عکس', 'Success ', ($message));

    if (!empty($errors)) {
        $message .= 'تعداد ' . $errors . ' تصویر با خطا مواجه شد:<br/>' . $error_message;
        Bz_Import_Export_For_Woo_Basic_Logwriter::write_log('خطا در دریافت عکس', 'Error ', ($message));
    }

    return array('success' => true, 'message' => $message);
}

    private function prepare_product_for_creation(&$data, $extraData = null, $sched = false)
    {

        $options = $this->visitor_settings;

        $SoftwareCurrency =  empty($options['selectCurrencySoftware']) ? 0 : $options['selectCurrencySoftware'];
        $PluginCurrency = empty($options['selectCurrencyPlugin']) ? 0 : $options['selectCurrencyPlugin'];
        $OptionTitle = $options['chkTitle'] == 1 || $options['chkTitle'];
        $syncCategory = (!empty($options['chkCategory']) && $options['chkCategory']) ? ($options['chkCategory'] == "cat" ? BAZARA_PRODUCT_CATEGORY : BAZARA_PRODUCT_SUB_CATEGORY) : false;

        $SimpleProduct = empty($data['vars']);
        $productArgs = $data;
        $productArgs['type'] = $SimpleProduct ? '' : 'variable';
        $productArgs['sku'] = $data['sku'];
        $productArgs['backorders'] = 'no';
        if ($data['new'] == 0)
            $productArgs['Status'] = '';
        $productArgs['qty'] = $data['qty'];
        $productArgs['manage_stock'] = $SimpleProduct;
        $productArgs['virtual'] = true;
        if ($data['qty'] > 0 || !$SimpleProduct)
            $productArgs['stock_qty'] = 'instock';
        else
            $productArgs['stock_qty'] = 'outofstock';



        $productArgs['name'] = $data['ProductName'];

        $prices = $data['Prices'];
        $price = $data['Price'];
        $RegularPrice = $data['Regular_price'];
        if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
            $price /= 10;
            $RegularPrice /= 10;
        } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
            $price *= 10;
            $RegularPrice *= 10;
        } else {
            $price = $data['Price'];
            $RegularPrice = $data['Regular_price'];
        }

        if ($RegularPrice == 0)
            $RegularPrice = '';
        if ($RegularPrice == 0 || ($RegularPrice > 0 && $RegularPrice < $price))
            $productArgs['sale_price'] = $price;

        $productArgs['price'] = $price;
        $productArgs['regular_price'] = $RegularPrice;
        $productArgs['prices_list'] = $data['Prices'];


        if ($data['tax'] == 'none')
            $productArgs['tax_status'] = $data['tax'];

        else {
            $productArgs['tax_status'] = 'taxable';
            $productArgs['tax_class'] = $data['tax'];
        }
        $productArgs['virtual'] = false;

        if (!empty($data['attributes'])) {
            $productArgs['attributes'] = array($data['attributes']);
            $productArgs['vars'] = $data['vars'];
        }

        $cat_ids = [];

        if ($syncCategory == BAZARA_PRODUCT_SUB_CATEGORY) {

            foreach ($extraData as $item) {
                $ItemType =  $item->ItemType;
                $Data =  $item->Data;

                if ($ItemType != 10202) continue;
                $data = json_decode($Data, true);

                if ($data['ProductCode'] == $productArgs['ProductCode']) {

                    $term_id = get_bazara_taxonomy_term($data['CategoryCode']);


                    $cat_id = $term_id;
                    if (!empty($cat_id))
                        array_push($cat_ids, $cat_id);
                }
            }
        }
        if ($syncCategory == BAZARA_PRODUCT_CATEGORY) {

            if (!empty($productArgs['Category'])) {
                $term_id = get_bazara_taxonomy_term($productArgs['Category']);

                $cat_id = $term_id;
                if (!empty($cat_id))
                    array_push($cat_ids, $cat_id);
            }
        }

        if (!empty($cat_ids))
            $productArgs['category_ids'] = $cat_ids;


        $res = create_product($productArgs);

        return array('success' => $res['success'], 'message' => $res['message']);
    }




    public function register_person($token = null, $person = null)
    {
        if (empty($token)) {
            $token_result = $this->login_token();
            if (!$token_result['success'])
                return array('success' => false, 'message' => $token_result['message']);
            $token = $token_result['message'];
        }

        $data = array('fromPersonGroupVersion' => 0); //fetch all products
        $groups_result = $this->get_all_data($token, $data);

        $role_groups = json_decode(json_encode($groups_result['message']), true)['PersonGroups'];

        $this->send_persons($token, $role_groups[0]['PersonGroupId'], $person);

        return true;
    }
    public function start_sync_persons()
    {


        $persons =  get_all_persons();


        $error_message = "";
        $success = 0;
        $errors = 0;

        foreach ($persons as $person) {
            if ($person['Deleted'] == true)
                continue;

            $r = $this->save_update_person($person);


            if ($r['success']) {
                $success++;
            } else {
                $errors++;
                $error_message .= '[' . $person['FirstName'] . ' ' . $person['LastName'] . '] ' . $r['message'] . '<br/>';
            }
        }
        $message = '<div style="color:blue;">همگام سازی اشخاص شروع شد</div><br/>';
        $message .= '<strong>دریافت اطلاعات از سرور:</strong>' . '<br/>';
        $message .= 'تعداد ' . $success . ' شخص با موفقیت در سیستم ثبت/بروز رسانی شد.' . '<br/>';
        Bz_Import_Export_For_Woo_Basic_Logwriter::write_log('دریافت مشتری', 'Success ', ($message));

        if (!empty($error_message)) {
            $message .= 'تعداد ' . $errors . ' شخص با خطا مواجه شد. شامل:' . '<br/>';
            $message .= $error_message;
            Bz_Import_Export_For_Woo_Basic_Logwriter::write_log('خطا در دریافت مشتری', 'Error ', ($message));
        }


        return array('success' => true, 'message' => $message);
    }
    /**
 * ایجاد یا به‌روزرسانی کاربر وردپرسی بر اساس داده‌های شخص ارائه‌شده.
 *
 * @param array $service_person
 * @return array
 */
private function save_update_person($service_person) {
    try {

        // اعتبارسنجی اولیه
        if (empty($service_person['PersonId']) || empty($service_person['FirstName']) || empty($service_person['LastName'])) {
            return ['success' => false, 'message' => __('فیلدهای ضروری وجود ندارند.', 'mahak-bazara')];
        }

        $person_id = $service_person['PersonId'];
        $objPerson = $this->get_person_by_mahakID($person_id);

        if (empty($objPerson) && !empty($service_person['Email'])) {
            $objPerson = get_user_by('email', $service_person['Email']);
        }

        // تعیین نام کاربری
        if (!empty($service_person['Email'])) {
            $username = $service_person['Email'];
        } elseif (!empty($service_person['Mobile'])) {
            $username = $service_person['Mobile'];
        } else {
            return ['success' => false, 'message' => __('نام کاربری قابل ایجاد نیست.', 'mahak-bazara')];
        }

        // بررسی administrator بودن
        $is_administrator = false;
        if (!empty($objPerson)) {
            $is_administrator = in_array('administrator', (array) $objPerson->roles, true);
        }

        // داده‌های کاربر
        $userdata = [
            'user_login'   => empty($objPerson) ? $username : $objPerson->user_login,
            'display_name' => $service_person['FirstName'] . ' ' . $service_person['LastName'],
            'user_email'   => $service_person['Email'] ?? '',
            'first_name'   => $service_person['FirstName'],
            'last_name'    => $service_person['LastName'],
        ];

        if (!empty($objPerson)) {
            $userdata['ID'] = $objPerson->ID;
        } else {
            $userdata['user_pass'] = wp_generate_password(12, true);
        }

        // ایجاد یا بروزرسانی
        $user_id = empty($objPerson) ? wp_insert_user($userdata) : wp_update_user($userdata);

        if (is_wp_error($user_id)) {
            return ['success' => false, 'message' => $user_id->get_error_message()];
        }

        // متادیتا
        update_user_meta($user_id, 'mahak_id', $person_id);
        update_user_meta($user_id, 'mahak_PersonCode', $service_person['PersonCode'] ?? '');
        update_user_meta($user_id, 'mreeir_phone', $service_person['Mobile'] ?? '');

        // موبایل بین‌المللی
        if (!empty($service_person['Mobile']) && preg_match('/^0[0-9]{10}$/', $service_person['Mobile'])) {
            $intl_mobile = substr_replace($service_person['Mobile'], '+98', 0, 1);
            update_user_meta($user_id, 'digits_phone_no', $intl_mobile);
            update_user_meta($user_id, 'digits_phone', $intl_mobile);
        }

        // اطلاعات صورتحساب
        $billing_meta = [
            'billing_first_name' => $service_person['FirstName'],
            'billing_last_name'  => $service_person['LastName'],
            'billing_address_1'  => $service_person['Address'] ?? '',
            'billing_email'      => $service_person['Email'] ?? '',
        ];
        foreach ($billing_meta as $key => $val) {
            if ($val !== null) {
                update_user_meta($user_id, $key, $val);
            }
        }

        // همگام‌سازی
        update_person_sync($person_id);

        /**
         * مدیریت نقش‌ها (غیر administrator)
         */
        if (!$is_administrator) {
            $user = new \WP_User($user_id);

            // نقش دریافتی از سرویس
            $desired_role_normalized = !empty($service_person['gName'])
                ? $this->normalize_role_name($service_person['gName'])
                : 'customer';

            // نقش‌های وردپرس + نرمال‌سازی
            $all_roles = $this->get_all_roles();
            $normalized_roles = [];

            foreach ($all_roles as $role) {
                $normalized_roles[$this->normalize_role_name($role)] = $role;
            }

            // تعیین نقش نهایی
            if (isset($normalized_roles[$desired_role_normalized])) {
                $role_to_assign = $normalized_roles[$desired_role_normalized];
            } else {
                $role_to_assign = 'customer';
            }

            // حذف نقش‌های قبلی
            foreach ($user->roles as $current_role) {
                if ($current_role !== $role_to_assign) {
                    $user->remove_role($current_role);
                }
            }

            // افزودن نقش جدید
            if (!in_array($role_to_assign, $user->roles, true)) {
                $user->add_role($role_to_assign);
            }
        }

        // ارسال ایمیل کاربر جدید
        if (empty($objPerson) && !empty($service_person['Email'])) {
            wp_new_user_notification($user_id, null, 'both');
        }

        return ['success' => true, 'message' => ''];

    } catch (\Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * نرمال‌سازی نام نقش فارسی برای تطبیق با slug نقش در وردپرس
 *
 * @param string $role_name نام نقش به صورت نمایشی (مثل "مشتریان سایت تک")
 * @return string slug نرمال‌شده نقش
 */
private function normalize_role_name($role_name) {
    if (empty($role_name)) {
        return '';
    }

    // تبدیل حروف عربی به فارسی
    $role_name = str_replace(
        ['ي', 'ك', 'ة', 'ۀ'],
        ['ی', 'ک', 'ه', 'ه'],
        $role_name
    );

    // تبدیل فاصله و نیم‌فاصله به _
    $role_name = str_replace([' ', '‌'], '_', $role_name);

    // حذف کاراکترهای غیرمجاز
    $role_name = preg_replace('/[^ا-یآ-ی0-9_a-zA-Z\-]/u', '_', $role_name);

    // حذف _ اضافی
    $role_name = preg_replace('/_+/', '_', $role_name);
    $role_name = trim($role_name, '_');

    return $role_name;
}

/**
 * تابع کمکی برای دریافت تمام نقش‌های معتبر وردپرس.
 *
 * @return array لیست نام‌های نقش‌ها.
 */
private function get_all_roles() {
    $roles = wp_roles()->get_names();
    return array_keys($roles);
}
    private function get_person_by_mahakID($mahak_id)
    {
        $users = get_users(array(
            'meta_key' => 'mahak_id',
            'role__not_in' => ['administrator'],
            'meta_value' => $mahak_id
        ));
        $user = null;
        if (!empty($users))
            $user = get_userdata($users[0]->ID);
        return $user;
    }
    private function find_role($role_groups, $personGroupID)
    {
        if (empty($personGroupID) || empty($role_groups)) return null;
        foreach ($role_groups as $group) {
            if ($group['PersonGroupId'] == $personGroupID && !$group['Deleted'])
                return $group;
        }
        return null;
    }
    private function send_persons($token, $personGroup, $person = array())
    {


        $this->set_visitor_options($token);
        $this->visitor_options = get_bazara_visitor_options();

        if (empty($person)) {
            $new_users = get_users(
                array(
                    'role__not_in' => ['administrator'],
                    'meta_query' => array(
                        'relation' => 'or',
                        array('key' => 'mahak_id', 'value' => '', 'compare' => '=='),
                        array('key' => 'mahak_id', 'compare' => 'NOT EXISTS')
                    )
                )
            );
        } else
            $new_users = $person;

        $datas = array();
        $addresses = $visitor =  array();


        foreach ($new_users as $user) {
            $last_name = get_user_meta($user->ID, 'last_name', true);
            $first_name = get_user_meta($user->ID, 'first_name', true);
            if (empty($last_name) && empty($first_name)) continue;

            $peoples[] = $this->convert_user_to_people($user, 0, $personGroup)['people'];
            $addresses[] = $this->convert_user_to_people($user, 0, $personGroup)['address'];
            $visitor[] = $this->convert_user_to_people($user, 0, $personGroup)['visitor'];
        }

        $result = $this->set_all_data($token, array('people' => $peoples, 'visitorPeople' => $visitor));
        $result_ids = json_decode($result, true)['data']['Data']['Objects']['People']['Results'];
        $count = 0;
        $visitors = [];
        $PersonAddresses = [];
        $visitorId =  (int)$this->visitor_options['VisitorId'];

        foreach ($new_users as $user) {
            $mahak_id = $result_ids[$count]['EntityId'];
            if ($mahak_id) {
                $PersonAddresses[] = array(
                    "personId" => (int)$mahak_id,
                    "Title" =>  $addresses[$count]['title'],
                    "Address" =>  $addresses[$count]['title'],
                    "tel" =>  !isset($addresses[$count]['tel1']) ? $addresses[$count]['mobile'] : $addresses[$count]['tel1'],
                    "latitude" =>  0,
                    "longitude" =>  0,
                    "isDefault" =>  false,
                    "deleted" =>  false
                );
                update_user_meta($user->ID, 'mahak_id', $mahak_id);
                update_user_meta($user->ID, 'role', 'customer');
            }


            $count++;
        }

        $t = $this->set_all_data($token, array('personAddresses' => $PersonAddresses));
    }
    function register_user_in_order($token, $user)
    {

        $options = $this->visitor_settings;
        $CustomerGroupID = (int)$options['customerGroupID'];
        if (empty($CustomerGroupID)) {
            $CustomerGroupID = get_person_group(True)[0]['PersonGroupId'];
        }
        $this->register_users($token, $CustomerGroupID, $user);

        return true;
    }
    public function register_users($token, $personGroup, $user)
    {
        $datas = array();
        // Normalize $user: accept WP_User or array with first element
        if (is_array($user)) {
            $user = reset($user);
        }
        if (empty($user) || !is_object($user) || empty($user->ID)) {
            return false;
        }
        $last_name = get_user_meta($user->ID, 'last_name', true);
        $first_name = get_user_meta($user->ID, 'first_name', true);
        if (empty($last_name) && empty($first_name)) return false;

        $datas = $this->convert_user_to_people($user, 0, $personGroup);

        $result = $this->set_all_data($token, array('people' => array($datas['people']), 'visitorPeople' => array($datas['visitor'])));
        
        if(is_string($result)){
            $result_ids = json_decode($result, true)['data']['Data']['Objects']['People']['Results'];
        }else{
            error_log('result is not valid');
            return false;
        }
        
        update_user_meta($user->ID, 'mahak_id', $result_ids[0]['EntityId']);
        update_user_meta($user->ID, 'role', 'customer');
    }
    private function convert_user_to_people($user, $person_id = 0, $personGroup = 0)
    {
        $billing_address = get_user_meta($user->ID, 'billing_address_1', true);
        $billing_phone = get_user_meta($user->ID, 'billing_phone', true);
        $billing_postcode = get_user_meta($user->ID, 'billing_postcode', true);
        $last_name = get_user_meta($user->ID, 'last_name', true);
        $first_name = get_user_meta($user->ID, 'first_name', true);
        $digiPhone = '+98' . ltrim(get_user_meta($user->ID, 'digits_phone_no', true), '0');


        //        if ($personGroup > 0){
        $person_clinet_id = $user->ID;
        bazara_update_client_id('person', $person_clinet_id);
        //        }



        $people = array(
            "firstname" => $first_name,
            "lastname" => $last_name,
            "personType" => 0,
            "personClientId" => $person_clinet_id,
            "personCode" => 0,
            "personGroupId" => $personGroup,
            "gender" => 0,
            "nationalCode" => "",
            "mobile" => empty($billing_phone) ? $digiPhone : $billing_phone,
            "phone" => empty($billing_phone) ? $digiPhone : $billing_phone,
            "email" => empty($user->user_email) ? "" : $user->user_email,
            "userName" => "",
            "password" => "",
            "priceLevel" => 1,
            "cityCode" => 0,
            "credit" => 0,
            "balance" => 0,
            "comment" => "",
            "userID" => $user->ID
        );
        $data['people'] = $people;
        $data['address'] = array(
            "personClientId" => $person_clinet_id,
            "title" =>  BAZARA_PERSON_ADDRESS_TITLE,
            "cityCode" => 0,
            "tel1" =>  empty($billing_phone) ? '' : $billing_phone,
            "longitude" => 0,
            "latitude" => 0,
            "postalCode" => empty($billing_postcode) ? '0' : $billing_postcode
        );

        $data['visitor'] = array(
            "personClientId" => $person_clinet_id,
            "visitorId" =>  (int)$this->visitor_options['VisitorId']
        );
        return $data;
    }
    public function start_sync_orders($token = null)
    {
        $hpos_enable = get_option('woocommerce_custom_orders_table_enabled') === 'yes';
        
        $options = $this->visitor_settings;
        $Order_Max_ID = (int) $options['order_id_greater_than'];
        $max_id = (!empty($Order_Max_ID) ? $Order_Max_ID : null);

        // تنظیم لیمیت ثابت برای سفارش‌های جا‌مانده (یکبار تنظیم می‌شود و دیگر تغییر نمی‌کند)
        $left_behind_sync_limit = isset($options['left_behind_sync_limit']) ? (int) $options['left_behind_sync_limit'] : null;
        if ($left_behind_sync_limit === null && $max_id > 0) {
            // اولین بار: لیمیت را از max_id فعلی تنظیم می‌کنیم
            $left_behind_sync_limit = $max_id;
            $options['left_behind_sync_limit'] = $left_behind_sync_limit;
            update_option('bazara_visitor_settings', $options);
            $this->visitor_settings = $options;
        }

        // دریافت سفارش‌های جدید (ID >= max_id)
        if (!$hpos_enable) {
            $orders = get_orders($max_id);
            // if (count($orders) == 0) {
            //     $orders = get_orders_hpos($max_id);
            // }
        } else {
            $orders = get_orders_hpos($max_id);
            // if (count($orders) == 0) {
            //     $orders = get_orders($max_id);
            // }
        }

        // دریافت سفارش‌های جا‌مانده (ID >= left_behind_sync_limit و ID < max_id که هنوز سینک نشده‌اند)
        $left_behind_orders = array();
        if ($left_behind_sync_limit !== null && $left_behind_sync_limit > 0 && $max_id > 0) {
            if (!$hpos_enable) {
                $left_behind_orders = get_left_behind_orders_from_limit($left_behind_sync_limit, $max_id);
                if (count($left_behind_orders) == 0) {
                    $left_behind_orders = get_left_behind_orders_from_limit_hpos($left_behind_sync_limit, $max_id);
                }
            } else {
                $left_behind_orders = get_left_behind_orders_from_limit_hpos($left_behind_sync_limit, $max_id);
                if (count($left_behind_orders) == 0) {
                    $left_behind_orders = get_left_behind_orders_from_limit($left_behind_sync_limit, $max_id);
                }
            }
        }

        // ترکیب تمام سفارش‌ها (ابتدا جا‌مانده‌ها، سپس جدیدها)
        $all_orders = array_merge($left_behind_orders, $orders);
        
        // echo "<pre>";
        // var_dump($all_orders);
        // die;

        $message = "";
        $success = 0;
        $errors = 0;
        $error_message = '';
        $left_behind_success = 0;
        $left_behind_errors = 0;

        if (!empty($all_orders)) {
            if (empty($token)) {
                $token_result = $this->login_token();
                if (!$token_result['success']) {
                    return array('success' => false, 'message' => $token_result['message']);
                }
                $token = $token_result['message'];
            }

            $this->visitor_settings = get_option('bazara_visitor_settings', []);

            // سینک سفارش‌های جا‌مانده
            if (!empty($left_behind_orders)) {
                for ($i = 0; $i < count($left_behind_orders); $i++) {
                    $order_id = $left_behind_orders[$i];
                    $result = $this->bazara_save_order($order_id, $token);

                    if ($result['success']) {
                        $left_behind_success++;
                        $success++;
                        // برای سفارش‌های جا‌مانده، max_id را آپدیت نمی‌کنیم
                    } else {
                        $left_behind_errors++;
                        $errors++;
                        $error_message .= '[شناسه سفارش جا‌مانده:' . $order_id . '] ' . $result['message'] . '<br/>';
                    }
                }
            }

            // سینک سفارش‌های جدید
            if (!empty($orders)) {
                for ($i = 0; $i < count($orders); $i++) {
                    $order_id = $orders[$i];
                    $result = $this->bazara_save_order($order_id, $token);

                    if ($result['success']) {
                        $success++;
                        // آپدیت order_id_greater_than به سفارش سینک‌شده
                        $this->visitor_settings['order_id_greater_than'] = (int) $order_id;
                        update_option('bazara_visitor_settings', $this->visitor_settings);
                    } else {
                        $errors++;
                        $error_message .= '[شناسه سفارش:' . $order_id . '] ' . $result['message'] . '<br/>';
                    }
                }
            }

            $message = '<div style="color:blue;">همگام‌سازی سفارش‌ها شروع شد</div><br/>';
            
            if ($left_behind_success > 0) {
                $message .= '<div style="color:green;">تعداد ' . $left_behind_success . ' سفارش جا‌مانده با موفقیت سینک شد.</div><br/>';
            }
            
            if ($success == 0) {
                $message .= 'هیچ سفارش جدیدی جهت ارسال یافت نشد.' . '<br/>';
            } else {
                $message .= 'تعداد ' . $success . ' سفارش با موفقیت به سرور ارسال و ثبت شد.' . '<br/>';
            }
            if (!empty($error_message)) {
                $message .= 'تعداد ' . $errors . ' سفارش با خطا مواجه شد. شامل:' . '<br/>';
                $message .= $error_message;
            }
            $message .= '*************************************************' . '<br/>';
            return array('success' => true, 'message' => $message);
        } else {
            return array('success' => false, 'message' => "سفارشی جهت ارسال یافت نشد");
        }
    }
    private function get_bank_id($token, $bankCode = 0)
    {
        if (empty($token)) {
            $token_result = $this->login_token();
            if (!$token_result['success'])
                return array('success' => false, 'message' => $token_result['message']);
            $token = $token_result['message'];
        }

        $result = $this->get_all_data($token, array('fromBankVersion' => 0));
        if (!$result['success'])
            return array('success' => false, 'message' => $result['message']);

        foreach ($result['message']['Banks'] as $bank) {

            if ($bank['BankCode'] == $bankCode)
                return $bank['BankId'];
        }

        return null;
    }

    public function bazara_save_order($order_id, $token)
    {
        set_time_limit(0);

        if (class_exists("OrderStatus")) {
            // بررسی وضعیت سفارش
            if (!is_order_status_valid($order_id)) {
                return array(
                    'success' => false,
                    'message' => 'سفارش با شناسه ' . $order_id . ' وضعیت مجاز برای همگام‌سازی ندارد.'
                );
            }
        }

        if (empty($token)) {
            $token_result = $this->login_token();
            if (!$token_result['success'])
                return array('success' => false, 'message' => $token_result['message']);
            $token = $token_result['message'];
        }

        if (empty($order_id)) {
            return [
                'success' => true,
                'message' => 'سفارشی برای ارسال اطلاعات وجود ندارد.'
            ];
        }

        $hpos_enable = false;

        // $this->set_visitor_options($token);
        $this->visitor_options = get_bazara_visitor_options();
        $visitorId = (int)$this->visitor_options['VisitorId'];
        $cashCode = (int)$this->visitor_options['CashCode'];
        $guestPerson = (int)$this->visitor_settings['guestPersonID'];
        $generalPerson = (int)$this->visitor_settings['generalCustomerID'];
        $customerType = $this->visitor_settings['radioCustomer'];
        $sendGuestPerson = !empty($this->visitor_settings['chkGuestCustomer']) && $this->visitor_settings['chkGuestCustomer'];
        $sendShipping = !empty($this->visitor_settings['chkShippingOrder']) && $this->visitor_settings['chkShippingOrder'];
        $sendBank = !empty($this->visitor_settings['chkBankOrder']) && $this->visitor_settings['chkBankOrder'];
        $store_priority_value = !empty($this->visitor_settings['StoresSortOrder']) ? explode(',', $this->visitor_settings['StoresSortOrder']) : '';
        $store_priority_toggle = !empty($this->visitor_settings['StorePriorityToggle']) && $this->visitor_settings['StorePriorityToggle'];

        $options = $this->visitor_settings;
        $count2 = 0;
        $hpos_enable = false;


        $order = wc_get_order($order_id);

        if ( $order instanceof WC_Order_Refund ) {
            return [
                'success' => true,
                'message' => 'عدم پردازش ریفاند'
            ];
        }

        if ($order->has_status('pending')) {
            return [
                'success' => true,
                'message' => 'عدم ارسال سفارش در انتظار پرداخت'
            ];
        }

        $ShippingMethod = get_order_item_meta_shipping($order_id);


        $order_data = $order->get_data();
        $order_customer_id = $order_data['customer_id'];

        if ($sendBank)
            $bankCode = $this->get_selected_bank_id($order_data['payment_method'], $order_id);
        else
            $bankCode = $this->get_bank_id($token, $this->visitor_options['BankCode']);

        if ($bankCode == null || $bankCode == 0)
            $bankCode = bazara_banks()[0]->BankId;



        $order_customer = get_userdata($order_customer_id);
        // Initialize with safe defaults
        $user_person = array('mobile' => '', 'personId' => 0);
        $mahakID = 0;
        if (!empty($order_customer) && is_object($order_customer) && !empty($order_customer->ID)) {
            $user_person = $this->convert_user_to_people($order_customer, get_user_meta($order_customer->ID, 'mahak_id', true))['people'];
            $mahakID = get_user_meta($order_customer->ID, 'mahak_id', true);
        }
        if ($customerType == BAZARA_PERSON_REGISTER) {
            if (empty($mahakID) && !empty($order_customer) && is_object($order_customer) && !empty($order_customer->ID)) {
                $user = get_user_by('id', $order_customer->ID);
                if (!empty($user) && is_object($user) && !empty($user->ID)) {
                    $this->register_user_in_order($token, array($user));
                    $mahakID = get_user_meta($order_customer->ID, 'mahak_id', true);
                }
            }
        } else if ($customerType == BAZARA_PERSON_GENERAL) {
            $mahakID = $generalPerson;
        }
        if (empty($order_customer_id) && $sendGuestPerson) {
            $mahakID = $guestPerson;
        }

        $user_person['personId'] =  $mahakID;
        $order = wc_get_order($order_id);
        $ps = get_post($order_id);

        if (!$hpos_enable) {
            $CodPaymentMethod = bazara_payment_method_is_cod(get_post_meta($order_id, '_payment_method', true));
            $total_amount = get_post_meta($order_id, '_order_total', true);
            $wallet = get_post_meta($order_id, '_payment_method', true) == 'wallet';
            $order_shipping_cost = get_post_meta($order_id, '_order_shipping', true);
		} else {
			//HPOS
			$payment_meta = get_order_item_meta_payment_hpos($order_id);
			$shipping_meta = get_order_item_shipping_amount_hpos($order_id);
			$payment_method_value = (!empty($payment_meta) && isset($payment_meta->payment_method)) ? $payment_meta->payment_method : '';
			$CodPaymentMethod = bazara_payment_method_is_cod($payment_method_value);
			$total_amount = (!empty($payment_meta) && isset($payment_meta->total_amount)) ? $payment_meta->total_amount : 0;
			$wallet = ($payment_method_value === 'wallet');
			$order_shipping_cost = (!empty($shipping_meta) && isset($shipping_meta->cost)) ? $shipping_meta->cost : 0;
		}
 
        $completed_date = get_post_meta($order_id, '_paid_date', true);
		if (empty($completed_date)) {
			$completed_date = !empty($ps) && isset($ps->post_date) ? $ps->post_date : current_time('mysql');
		}

        $completed_date = normalize_datetime_to_gregorian($completed_date);
        bazara_save_log(date_i18n('Y-m-j'), 'completed date', $completed_date, 'test');

        if (!$hpos_enable)
            $order_number = get_post_meta($order_id, '_order_number', true);
        else {
            //HPOS
            $order_number = $order->get_meta('_order_number', true);
        }
        $m = get_post_meta($order_id, '_payment_method', true);
        if (!$hpos_enable) {
            $CodPaymentMethod = bazara_payment_method_is_cod($m);

            $total_amount = get_post_meta($order_id, '_order_total', true);
            $wallet = get_post_meta($order_id, '_payment_method', true) == 'wallet';
        } else {
            //HPOS
            $CodPaymentMethod = bazara_payment_method_is_cod(get_order_item_meta_payment_hpos($order_id)->payment_method);
            $total_amount = get_order_item_meta_payment_hpos($order_id)->total_amount;
            $wallet = get_order_item_meta_payment_hpos($order_id)->payment_method == 'wallet';
        }
        if (empty($m)) {
            $m = get_order_item_meta_payment_hpos($order_id)->payment_method;
            $CodPaymentMethod = bazara_payment_method_is_cod($m);
            $wallet = $m == 'wallet';
            $total_amount = get_order_item_meta_payment_hpos($order_id)->total_amount;
        }
        if (empty($total_amount) || $total_amount == 0)
            $total_amount = get_order_item_meta_payment_hpos($order_id)->total_amount;

        $payment_meta = get_order_item_meta_payment_hpos($order_id);
        $wallet = (!empty($payment_meta) && !empty($payment_meta->payment_method) && $payment_meta->payment_method === 'wallet');
            
        $shipping_meta = get_order_item_shipping_amount_hpos($order_id);
        $order_shipping_cost = (!empty($shipping_meta) && !empty($shipping_meta->cost)) ? $shipping_meta->cost : 0;            
        if (!$hpos_enable)
            $order_number = get_post_meta($order_id, '_order_number', true);
        else {
            //HPOS
            $order_number = $order->get_meta('_order_number', true);
        }
        $receipt_id = bazara_get_last_client_id() + 1;
        $orderClientID = $order_id;
        if ($order_number > 0)
            $orderClientID = $order_number;
        $cheqid = bazara_get_last_client_id('cheque') + 1;
        $db_item_discount = get_order_item_discount($order_id);
        $total_discount = isset($db_item_discount->discount) ? (float)$db_item_discount->discount : 0;
        $orders = array();

        //HPOS
        // if (!$hpos_enable) {

        $State   = $order->get_shipping_state();
        $country = $order->get_shipping_country();
        $first_name = $order->get_billing_first_name();
        $last_name  = $order->get_billing_last_name();
        $address1   = $order->get_billing_address_1();
        $address2   = $order->get_billing_address_2();
        $postCode   = $order->get_billing_postcode();
        $phone      = $order->get_billing_phone();
        $cityName    = $order->get_shipping_city();

        $all_states = WC()->countries->get_states( $country );

        if ($address1 == '')
            $address = $address2;
        else
            $address = $address1;
        // } else {

        //     $shipping_address = get_orders_address_hpos($order_id, 'shipping');

        //     if ($shipping_address->address_1 == '')
        //         $address = $shipping_address->address_2;
        //     else
        //         $address = $shipping_address->address_1;

        //     $State = $shipping_address->state;
        //     $country = $shipping_address->country;
        //     $first_name = $shipping_address->first_name;
        //     $last_name = $shipping_address->last_name;
        //     $address1 = $shipping_address->address_1;
        //     $address2 = $shipping_address->address_2;
        //     $postCode = $shipping_address->postcode;
        //     //$phone =    $shipping_address->phone();
        //     $cityName = $shipping_address->city;

        // }

        if (!bazara_is_rtl($State)){
            $State = WC()->countries->get_states($country)[$State];
        }

        $State = normalize_persian_string($State);

        $city = get_cities($State, $cityName, 'exact');

        // error_log('$State: ' . $State);
        // error_log('$cityName: ' . $cityName);
        // error_log('[BAZARA] $city = ' . print_r($city, true));

		// Derive CityId from $city result
		$cityId = 0;
		if (is_array($city) && !empty($city)) {
			$firstCity = reset($city);
			if (is_object($firstCity) && isset($firstCity->CityID)) {
				$cityId = (int) $firstCity->CityID;
			} elseif (is_array($firstCity) && isset($firstCity['CityID'])) {
				$cityId = (int) $firstCity['CityID'];
			}
		}

		$shippingAddress = array(
            'Title' => BAZARA_PERSON_ADDRESS_TITLE . ' - ' . $first_name . ' ' . $last_name,
            'Address' => $State . ' - ' . $cityName . ' - ' . $address,
            'PostalCode' => $postCode,
            'Tel' => $phone,
            'Mobile' => $user_person['mobile'],
			'CityId' => $cityId,
            'Latitude' => 0,
            'Longitude' => 0
        );

		// error_log('[BAZARA] $shippingAddress = ' . print_r($shippingAddress, true));

        $SoftwareCurrency = $options['selectCurrencySoftware'];
        $PluginCurrency = $options['selectCurrencyPlugin'];
        $serial = 0;
        $serialUsed = false;

		foreach ($order->get_items() as $item_key => $item) {
			$item_data = is_object($item) && method_exists($item, 'get_data') ? (array)$item->get_data() : array();
			$item_name = isset($item_data['name']) ? $item_data['name'] : '';
			if ($item_name == "Wallet Topup" && class_exists("bazaraTeraWallet")) {
                if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
                    $total_amount *= 10;
                } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
                    $total_amount /= 10;
                }
                $product_orders['receipts'] =
                    array(
                        'personId' => (int)$user_person['personId'],
                        'cashAmount' => 0,
                        'cashCode' => (int)$cashCode,
                        'BankId' => (int)$bankCode,
                        'visitorId' =>   $visitorId,
                        'deleted' => false,
                        'receiptClientId' =>  (int)$orderClientID,
                        'date' => $completed_date,
                        'description' => 'بابت افزایش اعتبار'

                    );

                $product_orders['cheques'] =
                    array(
                        'amount' => $total_amount,
                        'cashCode' => (int)$cashCode,
                        'bankId' => (int)$bankCode,
                        'type' => 2,
                        'deleted' => false,
                        'Number' => (int)$cheqid,
                        'visitorId' => $visitorId,
                        'receiptClientId' => (int)$orderClientID,
                        'chequeClientId' => (int)$cheqid,
                        'date' => $completed_date,
                        'description' => 'بابت افزایش اعتبار'

                    );
                $data = array(
                    'cheques' => array($product_orders['cheques']),
                    'receipts' => array($product_orders['receipts'])
                );
                $result = $this->set_all_data($token, $data);

                $o_id = json_decode($result, true)['data'];
                if (!empty($o_id))
                    $o_id = $o_id['Data']['Objects']['Receipts']['Results'][0]['EntityId'];

                if (!$hpos_enable)
                    update_post_meta($order_id, 'mahak_id', $o_id);
                else {
                    //HPOS
                    $order_hpos = wc_get_order($order_id);
                    $order_hpos->update_meta_data('mahak_id', $o_id);
                    $order_hpos->save();
                }


                $clientIds = [
                    'receipt' => $receipt_id,
                    'cheque' => $cheqid
                ];

                foreach ($clientIds as $key => $value) {
                    bazara_update_client_id($key, $value);
                }
                return array('success' => true, 'message' => '');
            }
            $db_item = get_order_item_meta($item_key);
            $atrr_meta = get_order_item_pa_meta($item_key);

            if (empty($db_item))
                continue;
            $product_id       = $db_item->variantID > 0 ? $db_item->variantID : $db_item->productID;
            $product = wc_get_product($product_id);
            if (empty($product))
                continue;

            if (!empty($ShippingMethod->shipping_method))
                $ShippingMethod = $ShippingMethod->shipping_method;


            $getProductSerials = get_post_meta($product_id, 'mahak_product_serials', true);
            $getProductAttributes = get_post_meta($db_item->productID, 'mahak_product_default_attributes', true);


            $orderDetailPID = empty($db_item->variantID) ? $product->get_id() : $db_item->variantID;
            $measurment = $item->get_meta('_measurement_data');
            $quantity      = !empty($measurment) ? $measurment['weight']['value'] * ((int)$db_item->Qty) : ((int)$db_item->Qty);
            if (!empty($getProductSerials) && is_array($getProductSerials) && isset($getProductSerials[0])) {
                $p_detail_id = $getProductSerials[0]['detail_id'];
                $serialUsed = true;
            } else
                $p_detail_id    = get_post_meta($orderDetailPID, 'mahak_product_detail_id', true);

            if (!empty($getProductAttributes) && !empty($atrr_meta)) {

                $col = null;
                $i = 0;
                $visibleAttrs = array_column($getProductAttributes, 'visibleAttrs');
                $count = 0;
                $vals = array();

                foreach ($visibleAttrs as $visible) {

                    foreach ($visible as $key => $value) {
                        if (is_object($atrr_meta)) {
                            //  var_dump([$atrr_meta->attributes,$key,$atrr_meta->attr_values,$value]);
                            if (sanitize_title($atrr_meta->attributes) == sanitize_title($key) && sanitize_title($value) == sanitize_title($atrr_meta->attr_values)) {
                                $col = $i;
                                break;
                            }
                        } else {
                            if (!is_countable($atrr_meta)) break;
                            foreach ($atrr_meta as $at) {

                                if (sanitize_title($at->attributes) == sanitize_title($key) && sanitize_title($value) == sanitize_title($at->attr_values) && !in_array($key, array_keys($vals))) {
                                    $vals[$key] = $value;
                                    $count++;
                                    break;
                                }
                            }
                            if ($count == count($atrr_meta)) break;
                        }
                        if (is_countable($atrr_meta) && $count == count($atrr_meta)) {
                            $col = $i;
                            break;
                        }
                    }
                    $i++;
                }

                $foundedAttrCol = $getProductAttributes[$col];

                $p_detail_id = $foundedAttrCol['detail_id'];
            }
            if (empty($p_detail_id) || $p_detail_id == 0)
                $p_detail_id = get_post_meta($orderDetailPID, 'mahak_product_detail_id', true);

            $p_tax    = get_post_meta($orderDetailPID, 'mahak_product_tax', true);
            $p_charge    = get_post_meta($orderDetailPID, 'mahak_product_charge', true);
            // $total_row_discount = $item->get_subtotal() - $item->get_total();
            $role_base = get_post_meta($orderDetailPID, '_enable_role_based_price', false);

            $total_row_discount = 0;

            $unit_price = $item->get_subtotal() / $quantity;

            if ($product->get_sale_price() > 0 && !$role_base && !class_exists('wholeSalePermium')) {
                $unit_price = $product->get_regular_price();
                $total_row_discount = ($product->get_regular_price() - $product->get_sale_price()) * $quantity;
                $total_discount += $total_row_discount;
            }

            if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
                $unit_price *= 10;
                $total_row_discount *= 10;
            } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
                $unit_price /= 10;
                $total_row_discount /= 10;
            }
            $orders[] = array();
            $orderDetailClientID = bazara_get_last_client_id('order_detail') + 1;


            if (class_exists('bazara_ratio_calculator')) {
                $count2 = $quantity;
                $quantity = get_product_asset_quantity($p_detail_id, $Store_Id) * $count2;
            }
            if (class_exists('bazara_sale_by_ratio')) {
                $count2 = $quantity;
                $p = $db_item->variantID > 0 ? $product->get_parent_id() : $product->get_id();
                $quantity = get_post_meta($p, 'mahak_product_unit_ratio', true) * $count2;
            }

            if ($store_priority_toggle && is_array($store_priority_value)) {
                //var_dump('1');
            
                $among = $quantity;
                
                // Initialize orderDetails array if class exists (though this condition is redundant due to outer check)
                if (class_exists("sell_simple_with_date_variants")) {
                    $product_orders['orderDetails'] = array();
                }
            
                foreach ($store_priority_value as $store) {
                    $storeAsset = get_product_assets($p_detail_id, $store)[0];
            
                    if (!empty($storeAsset)) {
                        $Store_Id = $store;
            
                        // Case 1: Store has enough stock to fulfill the entire quantity
                        if ($storeAsset->Count1 >= $among) {
                            $product_orders['orderDetails'][] = array(
                                'orderClientId' => $orderClientID,
                                'orderDetailClientId' => (int)$orderDetailClientID,
                                'itemType' => 1,
                                'productDetailId' => (int)$p_detail_id,
                                'price' => $unit_price,
                                'count1' => ($serialUsed ? 1 : $among),
                                'count2' => $count2,
                                'storeId' => (int)$Store_Id,
                                'discount' => $total_row_discount,
                                'discountType' => 0,
                                'taxPercent' => ($p_tax == '-1' ? 0 : (!empty($p_tax) ? $p_tax : 0)),
                                'chargePercent' => ($p_charge == '-1' ? 0 : (!empty($p_charge) ? $p_charge : 0)),
                                'promotionCode' => 0,
                                'description' => '',
                                'orderCode' => 0,
                                'deleted' => false,
                                'gift' => 0
                            );
                            break;
                        } 
                        // Case 2: Store has some stock but less than required
                        elseif ($storeAsset->Count1 > 0 && $storeAsset->Count1 < $among) {
                            $product_orders['orderDetails'][] = array(
                                'orderClientId' => $orderClientID,
                                'orderDetailClientId' => (int)$orderDetailClientID,
                                'itemType' => 1,
                                'productDetailId' => (int)$p_detail_id,
                                'price' => $unit_price,
                                'count1' => $storeAsset->Count1,
                                'count2' => 0,
                                'storeId' => (int)$Store_Id,
                                'discount' => $total_row_discount,
                                'discountType' => 0,
                                'taxPercent' => ($p_tax == '-1' ? 0 : (!empty($p_tax) ? $p_tax : 0)),
                                'chargePercent' => ($p_charge == '-1' ? 0 : (!empty($p_charge) ? $p_charge : 0)),
                                'promotionCode' => 0,
                                'description' => '',
                                'orderCode' => 0,
                                'deleted' => false,
                                'gift' => 0
                            );
                            $among -= $storeAsset->Count1;
                            continue;
                        } 
                        // Case 3: Store has no stock
                        elseif ($storeAsset->Count1 == 0) {
                            continue;
                        }
                    }
                }
            } elseif (!class_exists("sell_simple_with_date_variants")) {
                //var_dump('2');
            
                $Store_Id = $this->visitor_options['StoreID'];
                $product_orders['orderDetails'][] = array(
                    'orderClientId' => $orderClientID,
                    'orderDetailClientId' => (int)$orderDetailClientID,
                    'itemType' => 1,
                    'productDetailId' => (int)$p_detail_id,
                    'price' => $unit_price,
                    'count1' => ($serialUsed ? 1 : $quantity),
                    'count2' => $count2,
                    'storeId' => (int)$Store_Id,
                    'discount' => $total_row_discount,
                    'discountType' => 0,
                    'taxPercent' => ($p_tax == '-1' ? 0 : (!empty($p_tax) ? $p_tax : 0)),
                    'chargePercent' => ($p_charge == '-1' ? 0 : (!empty($p_charge) ? $p_charge : 0)),
                    'promotionCode' => 0,
                    'description' => '',
                    'orderCode' => 0,
                    'deleted' => false,
                    'gift' => 0
                );
            }
            
            // Handle product serials
            if (!empty($getProductSerials) && !class_exists("sell_simple_with_date_variants") && is_array($getProductSerials) && ($quantity - 1) > 0) {
                //var_dump('3');
            
                $changedQuantity = ($quantity - 1);
                for ($i = 0; $i < $changedQuantity; $i++) {
                    bazara_update_client_id('order_detail', $orderDetailClientID);
                    $orderDetailClientID = bazara_get_last_client_id('order_detail') + 1;
                    $p_detail_id = $getProductSerials[$i + 1]['detail_id'];
                    
                    $product_orders['orderDetails'][] = array(
                        'orderClientId' => $orderClientID,
                        'orderDetailClientId' => (int)$orderDetailClientID,
                        'itemType' => 1,
                        'productDetailId' => (int)$p_detail_id,
                        'price' => $unit_price,
                        'count1' => 1,
                        'count2' => 0,
                        'storeId' => (int)$Store_Id,
                        'discount' => $total_row_discount,
                        'discountType' => 0,
                        'taxPercent' => ($p_tax == '-1' ? 0 : (!empty($p_tax) ? $p_tax : 0)),
                        'chargePercent' => ($p_charge == '-1' ? 0 : (!empty($p_charge) ? $p_charge : 0)),
                        'promotionCode' => 0,
                        'description' => '',
                        'orderCode' => 0,
                        'deleted' => false,
                        'gift' => 0
                    );
                }
            } else {
                //var_dump('4');
                bazara_update_client_id('order_detail', $orderDetailClientID);
            }
            
            // Handle case with sell_simple_with_date_variants and product serials
            if (class_exists("sell_simple_with_date_variants") && is_array($getProductSerials)) {
                //var_dump('5');
            
                $among = $quantity;
                $Store_Id = $this->visitor_options['StoreID'];
            
                foreach ($getProductSerials as $pdt) {
                    $storeAsset = get_product_assets($pdt['detail_id'], $Store_Id)[0];
            
                    if (!empty($storeAsset)) {
                        // Case 1: Store has enough stock
                        if ($storeAsset->Count1 >= $among) {
                            $product_orders['orderDetails'][] = array(
                                'orderClientId' => $orderClientID,
                                'orderDetailClientId' => (int)$orderDetailClientID,
                                'itemType' => 1,
                                'productDetailId' => (int)$pdt['detail_id'],
                                'price' => $unit_price,
                                'count1' => $among,
                                'count2' => $count2,
                                'storeId' => (int)$Store_Id,
                                'discount' => $total_row_discount,
                                'discountType' => 0,
                                'taxPercent' => ($p_tax == '-1' ? 0 : (!empty($p_tax) ? $p_tax : 0)),
                                'chargePercent' => ($p_charge == '-1' ? 0 : (!empty($p_charge) ? $p_charge : 0)),
                                'promotionCode' => 0,
                                'description' => '',
                                'orderCode' => 0,
                                'deleted' => false,
                                'gift' => 0
                            );
                            break;
                        } 
                        // Case 2: Store has some stock but less than required
                        elseif ($storeAsset->Count1 > 0 && $storeAsset->Count1 < $among) {
                            $product_orders['orderDetails'][] = array(
                                'orderClientId' => $orderClientID,
                                'orderDetailClientId' => (int)$orderDetailClientID,
                                'itemType' => 1,
                                'productDetailId' => (int)$pdt['detail_id'],
                                'price' => $unit_price,
                                'count1' => $storeAsset->Count1,
                                'count2' => 0,
                                'storeId' => (int)$Store_Id,
                                'discount' => $total_row_discount,
                                'discountType' => 0,
                                'taxPercent' => ($p_tax == '-1' ? 0 : (!empty($p_tax) ? $p_tax : 0)),
                                'chargePercent' => ($p_charge == '-1' ? 0 : (!empty($p_charge) ? $p_charge : 0)),
                                'promotionCode' => 0,
                                'description' => '',
                                'orderCode' => 0,
                                'deleted' => false,
                                'gift' => 0
                            );
                            $among -= $storeAsset->Count1;
                            continue;
                        }
                    }
                }
            }

            // منطق جدید مجزا برای شرایط خاص: هر دو کلاس موجود + اولویت انبار فعال
            if (
                class_exists("sell_simple_with_date_variants") &&
                class_exists("sell_simple_with_date_variants_without_date") &&
                $store_priority_toggle &&
                is_array($store_priority_value)
            ) {
                // پاک کردن orderDetails قبلی برای شروع مجدد
                $product_orders['orderDetails'] = array();

                // متغیرهای کنترل
                $remaining_quantity = $quantity;
                $processed_quantity = 0;
                $order_detail_counter = 0;

                // پردازش بر اساس اولویت انبار و سریال‌های محصول
                foreach ($store_priority_value as $store) {
                    if ($processed_quantity >= $quantity) break;

                    // اگر سریال‌های محصول موجود هستند
                    if (is_array($getProductSerials) && !empty($getProductSerials)) {
                        foreach ($getProductSerials as $pdt) {
                            if ($processed_quantity >= $quantity) break;

                            $storeAsset = get_product_assets($pdt['detail_id'], $store)[0];

                            if (!empty($storeAsset) && $storeAsset->Count1 > 0) {
                                $needed_quantity = min($remaining_quantity, $storeAsset->Count1);

                                // تولید orderDetailClientId جدید
                                if ($order_detail_counter > 0) {
                                    bazara_update_client_id('order_detail', $orderDetailClientID);
                                    $orderDetailClientID = bazara_get_last_client_id('order_detail') + 1;
                                }

                                $product_orders['orderDetails'][] = array(
                                    'orderClientId'       => $orderClientID,
                                    'orderDetailClientId' => (int)$orderDetailClientID,
                                    'itemType'            => 1,
                                    'productDetailId'     => (int)$pdt['detail_id'],
                                    'price'               => $unit_price,
                                    'count1'              => $needed_quantity,
                                    'count2'              => $count2,
                                    'storeId'             => (int)$store,
                                    'discount'            => $total_row_discount,
                                    'discountType'        => 0,
                                    'taxPercent'          => ($p_tax == '-1' ? 0 : (!empty($p_tax) ? $p_tax : 0)),
                                    'chargePercent'       => ($p_charge == '-1' ? 0 : (!empty($p_charge) ? $p_charge : 0)),
                                    'promotionCode'       => 0,
                                    'description'         => '',
                                    'orderCode'           => 0,
                                    'deleted'             => false,
                                    'gift'                => 0
                                );

                                $remaining_quantity -= $needed_quantity;
                                $processed_quantity += $needed_quantity;
                                $order_detail_counter++;

                                // اگر مقدار مورد نیاز تأمین شد، از حلقه خارج شو
                                if ($remaining_quantity <= 0) break 2;
                            }
                        }
                    } else {
                        // اگر سریال محصول موجود نیست، از انبار اصلی استفاده کن
                        $storeAsset = get_product_assets($p_detail_id, $store)[0];

                        if (!empty($storeAsset) && $storeAsset->Count1 > 0) {
                            $needed_quantity = min($remaining_quantity, $storeAsset->Count1);

                            // تولید orderDetailClientId جدید
                            if ($order_detail_counter > 0) {
                                bazara_update_client_id('order_detail', $orderDetailClientID);
                                $orderDetailClientID = bazara_get_last_client_id('order_detail') + 1;
                            }

                            $product_orders['orderDetails'][] = array(
                                'orderClientId'       => $orderClientID,
                                'orderDetailClientId' => (int)$orderDetailClientID,
                                'itemType'            => 1,
                                'productDetailId'     => (int)$p_detail_id,
                                'price'               => $unit_price,
                                'count1'              => ($serialUsed ? 1 : $needed_quantity),
                                'count2'              => $count2,
                                'storeId'             => (int)$store,
                                'discount'            => $total_row_discount,
                                'discountType'        => 0,
                                'taxPercent'          => ($p_tax == '-1' ? 0 : (!empty($p_tax) ? $p_tax : 0)),
                                'chargePercent'       => ($p_charge == '-1' ? 0 : (!empty($p_charge) ? $p_charge : 0)),
                                'promotionCode'       => 0,
                                'description'         => '',
                                'orderCode'           => 0,
                                'deleted'             => false,
                                'gift'                => 0
                            );

                            $remaining_quantity -= $needed_quantity;
                            $processed_quantity += $needed_quantity;
                            $order_detail_counter++;

                            // اگر مقدار مورد نیاز تأمین شد، از حلقه خارج شو
                            if ($remaining_quantity <= 0) break;
                        }
                    }
                }

                // بررسی اینکه آیا تمام مقدار مورد نیاز تأمین شده است
                if ($remaining_quantity > 0) {
                    // لاگ کردن مشکل کمبود موجودی
                    error_log("Bazara Order Processing: Insufficient stock. Required: {$quantity}, Processed: {$processed_quantity}, Remaining: {$remaining_quantity}");

                    // می‌توانید تصمیم بگیرید که آیا سفارش را رد کنید یا با موجودی موجود ادامه دهید
                    echo 'موجودی کافی برای تأمین سفارش موجود نیست';
                    return array('success' => false, 'message' => 'موجودی کافی برای تأمین سفارش موجود نیست');
                }

                // به‌روزرسانی orderDetailClientID برای استفاده در بخش‌های بعدی
                if ($order_detail_counter > 0) {
                    bazara_update_client_id('order_detail', $orderDetailClientID);
                }

                // علامت‌گذاری که این منطق جدید اجرا شده است
                $product_orders['processed_with_new_logic'] = true;
            }

        }

        // echo "<pre>";
		// var_dump($product_orders['orderDetails']);
		// var_dump($order->get_items());
		// var_dump(count($order->get_items()));
		// var_dump(count($product_orders['orderDetails']));
		// die;

        if (empty($product_orders['orderDetails'])) {
            echo 'سفارش دارای اقلام نمی باشد. احتمالا محصولات از سایت حذف شده اند.';
            return array('success' => false, 'message' => 'سفارش دارای اقلام نمی باشد. احتمالا محصولات از سایت حذف شده اند.');
        }

        if (count($product_orders['orderDetails']) <> count($order->get_items())) {
            echo 'تعداد اقلام سفارش بیشتر از موجودی می باشد';
            return array('success' => false, 'message' => 'تعداد اقلام سفارش بیشتر از موجودی می باشد');
        }

        $order_items_quantities = array();
        foreach ($order->get_items() as $item_key => $item) {
            $db_item = get_order_item_meta($item_key);
            if (empty($db_item)) continue;

            $product_id = $db_item->variantID > 0 ? $db_item->variantID : $db_item->productID;
            $measurment = $item->get_meta('_measurement_data');
            $quantity = !empty($measurment) ? $measurment['weight']['value'] * ((int)$db_item->Qty) : ((int)$db_item->Qty);

            if (!isset($order_items_quantities[$product_id])) {
                $order_items_quantities[$product_id] = 0;
            }
            $order_items_quantities[$product_id] += $quantity;
        }

        $order_details_quantities = array();
        foreach ($product_orders['orderDetails'] as $order_detail) {
            $product_detail_id = $order_detail['productDetailId'];
            if (!isset($order_details_quantities[$product_detail_id])) {
                $order_details_quantities[$product_detail_id] = 0;
            }
            $order_details_quantities[$product_detail_id] += $order_detail['count1'];
        }
        
        // بررسی تطابق تعدادها
        foreach ($order_items_quantities as $product_id => $original_quantity) {
            $product_detail_id = get_post_meta($product_id, 'mahak_product_detail_id', true);
            $diff_products = [];

			foreach ($order_items_quantities as $product_id => $original_quantity) {

				$product_detail_id = get_post_meta($product_id, 'mahak_product_detail_id', true);
				$system_quantity = $order_details_quantities[$product_detail_id] ?? 0;

				if ($system_quantity != $original_quantity) {

					$product = wc_get_product($product_id);

					$diff_products[] = [
						'product_id'        => $product_id,
						'product_name'      => $product ? $product->get_name() : 'نامشخص',
						'expected_quantity' => $original_quantity,
						'system_quantity'   => $system_quantity,
						'difference'        => $original_quantity - $system_quantity,
						'product_detail_id' => $product_detail_id,
					];
				}
			}

        }

		if (!empty($diff_products)) {
			error_log('Bazara Quantity Mismatch: ' . print_r($diff_products, true));
		}

		if (!empty($diff_products)) {
			echo 'تعداد خریداری شده محصول با تعداد ثبت شده در سیستم مغایرت دارد';
			return [
				'success' => false,
				'message' => 'مغایرت تعداد در محصولات زیر وجود دارد',
				'details' => $diff_products
			];
		}

        if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {

            if (!empty($order_shipping_cost) && $order_shipping_cost  > 0)
                $order_shipping_cost *= 10;
            if (!empty($total_discount) && $total_discount  > 0)
                $total_discount *= 10;
            $total_amount *= 10;
        } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {

            if (!empty($order_shipping_cost) && $order_shipping_cost  > 0)
                $order_shipping_cost /= 10;
            if (!empty($total_discount) && $total_discount  > 0)
                $total_discount /= 10;
            $total_amount /= 10;
        }

        $description = $order->get_customer_note();
        if (empty($description)) {
            $description = $order->get_meta( 'info' );
        }

        $product_orders['orders'] =
            array(
                'latitude' => 0,
                'longitude' => 0,
                'orderType' => 201,
                'deliveryDate' => $completed_date,
                'personId' => (int)$user_person['personId'],
                'orderDate' => $completed_date,
                'description' => $description,
                'discount' =>  $total_discount,
                'discountType' =>  0,
                'visitorId' =>   $visitorId,
                'orderClientId' =>   (int)$orderClientID,
                'receiptClientId' =>   (int)$orderClientID,
                'deleted' => false,
                'shippingAddress' => json_encode($shippingAddress)
            );
        if ($sendShipping) {
            $shippingPerson = $this->get_selected_shipping_person_id($ShippingMethod);
            if (!empty($shippingPerson)) {

                $product_orders['orders']['CarrierType'] = BAZARA_CARRIER_PERSON_TYPE;
                $product_orders['orders']['DriverCurrencyType'] = BAZARA_CARRIER_CURRENCY_TYPE;
                $product_orders['orders']['CarryingAsExpense'] = false;
                $product_orders['orders']['CarrierID'] = $shippingPerson;
                $product_orders['orders']['sendCost'] = empty($order_shipping_cost) ? 0 : $order_shipping_cost;
            }
        }
        $product_orders['receipts'] =
            array(
                'personId' => (int)$user_person['personId'],
                'cashAmount' => 0,
                'cashCode' => (int)$cashCode,
                'BankId' => (int)$bankCode,
                'visitorId' =>   $visitorId,
                'deleted' => false,
                'receiptClientId' =>  (int)$orderClientID,
                'orderClientId' =>   (int)$orderClientID,
                'date' => $completed_date,

            );

        $product_orders['cheques'] =
            array(
                'amount' => $total_amount,
                'cashCode' => (int)$cashCode,
                'bankId' => (int)$bankCode,
                'type' => 2,
                'deleted' => false,
                'Number' => (int)$cheqid,
                'visitorId' => $visitorId,
                'receiptClientId' => (int)$orderClientID,
                'chequeClientId' => (int)$cheqid,
                'orderClientId' => (int)$orderClientID,
                'date' => $completed_date,

            );

        if (($CodPaymentMethod || $total_amount == 0) && !$wallet) {
            unset($product_orders['receipts']);
            unset($product_orders['cheques']);
        }
        $data = array(
            'orders' => array($product_orders['orders']),
            'orderDetails' => $product_orders['orderDetails']
        );
        if (!$CodPaymentMethod && $total_amount > 0 && !$wallet) {
            $data['receipts'] = array($product_orders['receipts']);
            $data['cheques'] = array($product_orders['cheques']);
        }
        $result = $this->set_all_data($token, $data);
        if (is_string($result)) {
            $o_id = json_decode($result, true)['data'];
            if (!empty($o_id))
                $o_id = $o_id['Data']['Objects']['Orders']['Results'][0]['EntityId'];
            else {
                if (!$hpos_enable)
                    update_post_meta($order_id, 'mahak_error', json_encode($result));
                else {
                    //HPOS
                    $order_hpos = wc_get_order($order_id);
                    $order_hpos->update_meta_data('mahak_error', json_encode($result));
                    $order_hpos->save();
                }
            }

            if (!$hpos_enable)
                update_post_meta($order_id, 'mahak_id', $o_id);
            else {
                //HPOS
                $order_hpos = wc_get_order($order_id);
                $order_hpos->update_meta_data('mahak_id', $o_id);
                $order_hpos->save();
            }
            if ($o_id > 0 && $serialUsed) {
                for ($i = 0; $i < $quantity; $i++) {
                    unset($getProductSerials[$i]);
                    update_post_meta($product_id, 'mahak_product_serials', $getProductSerials);
                }
            }

            $clientIds = [
                'receipt' => $receipt_id,
                'order' => $orderClientID,
                'cheque' => $cheqid,
                'order_detail' => $orderDetailClientID
            ];
            if ($CodPaymentMethod) {
                unset($clientIds['receipts']);
                unset($clientIds['cheque']);
            }
            foreach ($clientIds as $key => $value) {
                bazara_update_client_id($key, $value);
            }
        } else {
            if (!$hpos_enable) {
                update_post_meta($order_id, 'mahak_error', json_encode($result));
                delete_post_meta($order_id, 'mahak_id');
            } else {
                //HPOS
                $order_hpos = wc_get_order($order_id);
                $order_hpos->update_meta_data('mahak_error', json_encode($result));
                $order_hpos->delete_meta_data('mahak_id');

                $order_hpos->save();
            }
            $this->handle_order_errors($result, $order_id);
            return array('success' => false, 'message' => '');
        }



        return array('success' => true, 'message' => json_encode($result));
    }
    private function get_selected_bank_id($payment_method, $orderid)
    {
        $hpos_enable = false;

        $options = $this->visitor_settings;

        if (!$hpos_enable)
            $metaMethod = get_post_meta($orderid, '_payment_method', true);
        else {
            //HPOS
            $metaMethod = get_order_item_meta_payment_hpos($orderid)->payment_method;
        }

		$banks = json_decode(stripslashes(isset($options['banksMethods']) ? $options['banksMethods'] : ''), true);
        if (empty($banks))
            return false;
        foreach ($banks as $bank) {

			$bank_method = isset($bank['method']) ? $bank['method'] : null;
			$bank_name = isset($bank['name']) ? $bank['name'] : null;
			if ($bank_method !== null && in_array($bank_method, [$payment_method, $metaMethod]))
				return $bank_name;
        }
        return false;
    }
    private function handle_order_errors($error, $oid = '')
    {
        $checkError = true;
        $orderDetailError = $orderError = true;
        $hpos_enable = false;
    
        if (isset($error['message'])) {
            $error = $error['message'];
            $error = json_decode($error, true);
        }
    
        // Check cheque result
        if (
            isset($error['Data']['Objects']['Cheques']['Results'][0])
        ) {
            $checkError = $error['Data']['Objects']['Cheques']['Results'][0]['Result'];
        }
    
        if (!$checkError) {
            $cheqid = bazara_get_last_client_id('cheque') + 500;
            bazara_update_client_id('cheque', $cheqid);
        }
    
        // Check order detail result
        if (
            isset($error['Data']['Objects']['OrderDetails']['Results'][0])
        ) {
            $orderDetailError = $error['Data']['Objects']['OrderDetails']['Results'][0]['Result'];
        }
    
        if (!$orderDetailError) {
            $orderDetailID = bazara_get_last_client_id('order_detail') + 500;
            bazara_update_client_id('order_detail', $orderDetailID);
        }
    
        // Check order result
        if (
            isset($error['Data']['Objects']['Orders']['Results'][0])
        ) {
            $orderError = $error['Data']['Objects']['Orders']['Results'][0]['Result'];
        }
    
        if (!$orderError) {
            if (!$hpos_enable) {
                update_post_meta($oid, 'mahak_id', '54323444');
            } else {
                // HPOS
                $order_hpos = wc_get_order($oid);
                $order_hpos->update_meta_data('mahak_id', '54323444');
                $order_hpos->save();
            }
        }
    }
    
    private function get_selected_shipping_person_id($shipping_method)
{
    $options = $this->visitor_settings;
    $shippings = json_decode(stripslashes(isset($options['carrierMethods']) ? $options['carrierMethods'] : ''), true);

    if (empty($shippings)) {
        return null;
    }

    // تبدیل stdClass به string
    if (is_object($shipping_method) && isset($shipping_method->shipping_method)) {
        $shipping_method = (string) $shipping_method->shipping_method;
    } else if (!is_string($shipping_method)) {
        $shipping_method = '';
    }

    if (strpos($shipping_method, ":") !== false) {
        $shipping_method = explode(":", $shipping_method)[0];
    }

    foreach ($shippings as $shipping) {
        $shipping_method_key = isset($shipping['method']) ? $shipping['method'] : null;
        $shipping_name = isset($shipping['name']) ? $shipping['name'] : null;
        if ($shipping_method_key !== null && $shipping_method_key == $shipping_method) {
            return $shipping_name;
        }
    }

    return null;
}

    private function sync_combined_products($token, $min = 0, $max = 20)
    {
        // Get latest versions for all related entities
        $product_latest_rv = empty(get_last_row_version("product")) ? 0 : (get_last_row_version("product"));
        $productDetail_latest_rv = empty(get_last_row_version("productDetail")) ? 0 : (get_last_row_version("productDetail"));
        $visitorProducts_latest_rv = empty(get_last_row_version("VisitorProducts")) ? 0 : (get_last_row_version("VisitorProducts"));

        // Prepare combined data
        $data = array(
            "fromProductVersion" => $product_latest_rv,
            "fromProductDetailVersion" => $productDetail_latest_rv,
            "fromVisitorProductVersion" => $visitorProducts_latest_rv
        );

        $product_result = $this->get_all_data($token, $data);
        if (!$product_result['success'])
            return array('count' => 0, 'error' => $product_result['message'], 'success' => false);

        $this->visitor_options = get_bazara_visitor_options();
        $this->visitor_settings = get_bazara_visitor_settings();
        $this->plugin_options = bazara_get_options();
        if (empty($this->visitor_options['StoreID'])) {
            return false;
        }

        // Get tax and charge percent from settings
        $ChargePercent = 0;
        $TaxPercent = 0;
        $visitorSettings = get_option('bazara_visitor_soft_settings', true);
        if($this->visitor_settings['chkPrice']) {
            if (!empty($visitorSettings) && is_array($visitorSettings)) {
                foreach ($visitorSettings as $setting) {
                    if (intval($setting['SettingCode']) === 14000)
                        $ChargePercent = intval($setting['Value']);
                    if (intval($setting['SettingCode']) === 14001)
                        $TaxPercent = intval($setting['Value']);
                }
            }
        }

        // Process Products
        if (!empty($product_result['message']['Products'])) {

            $Products = $product_result['message']['Products'];

            // مرتب‌سازی بر اساس RowVersion
            usort($Products, function ($a, $b) {
                return $a['RowVersion'] <=> $b['RowVersion'];
            });

            $insertCount = 0;

            foreach ($Products as $product) {

                // اگر به max رسیدیم → حلقه تمام
                if ($insertCount >= $max) {
                    break;
                }

                $insertCount++;

                // محاسبه مالیات
                $taxClass = '';
                if (intval($product['TaxPercent']) != -1) {
                    if (intval($product['TaxPercent']) === 0) {
                        $product['ChargePercent'] = $ChargePercent;
                        $product['TaxPercent']    = $TaxPercent;
                    }
                    $tax = intval($product['TaxPercent']) + intval($product['ChargePercent']);
                    $taxClass = $this->create_woo_tax($tax);
                }

                // داده محصول
                $product_items = array(
                    'ProductId'      => $product['ProductId'],
                    'ProductCode'    => $product['ProductCode'],
                    'ProductName'    => $product['Name'],
                    'Status'         => $this->visitor_settings['publishStatus'] ?? 'publish',
                    'Category'       => $product['ProductCategoryId'],
                    'TaxPercent'     => ($product['TaxPercent'] == '-1' ? 0 : $product['TaxPercent']),
                    'ChargePercent'  => ($product['ChargePercent'] == '-1' ? 0 : $product['ChargePercent']),
                    'tax'            => ($taxClass ?: ''),
                    'store_id'       => $this->visitor_options['StoreID'],
                    'qty'            => 0,
                    'sku'            => $product['ProductCode'],
                    'width'          => $product['Width'],
                    'weight'         => $product['Weight'],
                    'height'         => $product['Height'],
                    'length'         => $product['Length'],
                    'description'    => $product['Description'],
                    'RowVersion'     => $product['RowVersion'],
                    'unitName1'      => $product['UnitName'],
                    'unitName2'      => $product['UnitName2'],
                    'unitRatio'      => $product['UnitRatio'],
                    'Deleted'        => $product['Deleted'] ? 1 : 0,
                );

                // درج
                insert('bazara_products', $product_items, 'ProductId', $product['ProductId']);

                // آپدیت نسخه
                bazara_update_latest_versions('product', $product['RowVersion']);

                // سنک جزئیات
                update_schedule_sync($product['ProductId'], 'detailSync', 0);
            }
        }


        // Process ProductDetails
        if (!empty($product_result['message']['ProductDetails'])) {
            $ProductDetails = $product_result['message']['ProductDetails'];
            usort($ProductDetails, function ($item1, $item2) {
                if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
            });

            $index = 0;
            foreach ($ProductDetails as $productdetail) {
                $index++;
                if ($index <= $min) continue;
                if ($index > $max) break;

                $pricesList = $Discounts = [];
                for ($i = 1; $i <= 10; $i++) {
                    if (!empty($productdetail["Price{$i}"]));
                    $pricesList[$i]["Price{$i}"] = $productdetail["Price{$i}"];
                    if (isset($productdetail["Discount{$i}"]) && !empty($productdetail["Discount{$i}"]));
                    $Discounts[$i]["Discount{$i}"] = $productdetail["Discount{$i}"];
                }

                $product_items = array(
                    'ProductId' => $productdetail['ProductId'],
                    'ProductDetailId' => $productdetail['ProductDetailId'],
                    'Properties' => ($productdetail['Properties']),
                    'Prices' => json_encode($pricesList),
                    'Discounts' => json_encode($Discounts),
                    'DefaultDiscountLevel' => $productdetail['DefaultDiscountLevel'],
                    'DefaultSellPriceLevel' => $productdetail['DefaultSellPriceLevel'],
                    'RowVersion' => $productdetail['RowVersion'],
                    'Deleted' => $productdetail['Deleted'] ? 1 : 0,
                );

                insert('bazara_product_details', $product_items, 'ProductDetailId', $productdetail['ProductDetailId']);
                bazara_update_latest_versions('productDetail', $productdetail['RowVersion']);
                $ProductID = get_product_id($productdetail['ProductDetailId']);
                update_schedule_sync($ProductID, 'priceSync', 0);
                update_schedule_sync($ProductID, 'detailSync', 0);
                update_schedule_sync($productdetail['ProductDetailId'], 'isSync', 0, 'bazara_product_details', 'ProductDetailId');
                if (!empty($productdetail['Barcode']))
                    update_barcode($ProductID, $productdetail['Barcode']);
            }
        }

        // Process VisitorProducts
        if (!empty($product_result['message']['VisitorProducts'])) {
            $VisitorProducts = $product_result['message']['VisitorProducts'];
            usort($VisitorProducts, function ($item1, $item2) {
                if ($item1['RowVersion'] == $item2['RowVersion']) return 0;
                return $item1['RowVersion'] < $item2['RowVersion'] ? -1 : 1;
            });

            $index = 0;
            foreach ($VisitorProducts as $visitorProduct) {
                $index++;
                if ($index <= $min) continue;
                if ($index > $max) break;

                $product_items = array(
                    'VisitorProductId' => $visitorProduct['VisitorProductId'],
                    'ProductDetailId' => ($visitorProduct['ProductDetailId']),
                    'VisitorId' => $visitorProduct['VisitorId'],
                    'Deleted' => ($visitorProduct['Deleted'] == 'true' ? 1 : 0),
                    'RowVersion' => $visitorProduct['RowVersion'],
                );

                insert('bazara_visitor_products', $product_items, 'VisitorProductId', $visitorProduct['VisitorProductId']);
                $ProductID = get_product_id($visitorProduct['ProductDetailId']);
                update_schedule_sync($ProductID, 'stockSync', 0);
            }
        }

        return array('success' => true, 'message' => '');
    }
    
/**
 * سینک جداگانه Persons 
 * (VisitorPersons پردازش نمی‌شود - فقط نسخه آن ارسال می‌شود)
 */
private function sync_combined_persons($token, $min = 0, $max = 20)
{
    // دریافت آخرین نسخه‌ها
    $person_latest_rv        = get_last_row_version("Persons") ?: 0;
    $visitorPerson_latest_rv = get_last_row_version("VisitorPersons") ?: 0;

    // آماده‌سازی داده برای API - هر دو نسخه ارسال می‌شود
    $data = array(
        "fromPersonVersion"       => $person_latest_rv,
        "fromVisitorPersonVersion"=> $visitorPerson_latest_rv,
    );

    // دریافت داده از سرور
    $result = $this->get_all_data($token, $data);

    if (!$result['success']) {
        return array('count' => 0, 'error' => $result['message'], 'success' => false);
    }

    $processedPersons = 0;
    $batchSize = $max;

    // ==================== Process Persons فقط ====================
    if (!empty($result['message']['People'])) {
        $Peoples = $result['message']['People'];

        // مرتب‌سازی بر اساس RowVersion
        usort($Peoples, function ($a, $b) {
            return $a['RowVersion'] <=> $b['RowVersion'];
        });

        foreach ($Peoples as $People) {
            if ($processedPersons >= $batchSize) {
                break;
            }

            $processedPersons++;

            $person_items = array(
                'PersonId'       => $People['PersonId'],
                'PersonClientId' => $People['PersonClientId'] ?? null,
                'PersonGroupId'  => $People['PersonGroupId'] ?? null,
                'PersonCode'     => $People['PersonCode'] ?? null,
                'FirstName'      => $People['FirstName'] ?? '',
                'LastName'       => $People['LastName'] ?? '',
                'Email'          => $People['Email'] ?? null,
                'Deleted'        => ($People['Deleted'] == 'true' ? 1 : 0),
                'RowVersion'     => $People['RowVersion'],
                'isSync'         => 0,
                'Mobile'         => $People['Mobile'] ?? null,
                'Address'        => $People['Address'] ?? null,
            );

            // درج رکورد
            insert('bazara_persons', $person_items, 'PersonId', $People['PersonId']);

            // به‌روزرسانی نسخه
            bazara_update_latest_versions('persons', $People['RowVersion']);
        }
    }

    // لاگ برای دیباگ
    if ($processedPersons > 0) {
        error_log("Synced {$processedPersons} Persons successfully. Last RowVersion updated.");
    }

    return array(
        'success' => true,
        'count'   => $processedPersons,
        'message' => "{$processedPersons} person(s) processed successfully."
    );
}
    
    /**
 * سینک جداگانه VisitorPersons
 */
private function sync_visitor_persons($token, $min = 0, $max = 20)
{
    // دریافت آخرین نسخه‌ها
    $visitorPerson_latest_rv = get_last_row_version("VisitorPersons") ?: 0;

    // آماده‌سازی داده برای API
    $data = array(
        "fromVisitorPersonVersion" => $visitorPerson_latest_rv,
        // اگر نیاز به ارسال fromPersonVersion هم هست، می‌توانی اضافه کنی
        // "fromPersonVersion" => get_last_row_version("Persons") ?: 0,
    );

    // دریافت داده از سرور
    $result = $this->get_all_data($token, $data);

    if (!$result['success']) {
        return array('count' => 0, 'error' => $result['message'], 'success' => false);
    }

    $processedCount = 0;
    $batchSize = $max;

    // ==================== Process VisitorPersons ====================
    if (!empty($result['message']['VisitorPeople'])) {   // <<< مهم: نام کلید را چک کن
        $VisitorPersons = $result['message']['VisitorPeople'];

        // مرتب‌سازی بر اساس RowVersion
        usort($VisitorPersons, function ($a, $b) {
            return $a['RowVersion'] <=> $b['RowVersion'];
        });

        foreach ($VisitorPersons as $visitorPerson) {
            if ($processedCount >= $batchSize) {
                break;
            }

            $processedCount++;

            $visitor_items = array(
                'VisitorPersonId' => $visitorPerson['VisitorPersonId'],
                'PersonId'        => $visitorPerson['PersonId'] ?? null,
                'VisitorId'       => $visitorPerson['VisitorId'] ?? null,
                'Deleted'         => ($visitorPerson['Deleted'] == 'true' ? 1 : 0),
                'RowVersion'      => $visitorPerson['RowVersion'],
                // سایر فیلدهای مورد نیاز را اینجا اضافه کنید
            );

            // درج در جدول
            insert('bazara_visitor_persons', $visitor_items, 'VisitorPersonId', $visitorPerson['VisitorPersonId']);

            // به‌روزرسانی آخرین نسخه
            bazara_update_latest_versions('VisitorPersons', $visitorPerson['RowVersion']);
        }
    }

    // لاگ برای دیباگ
    if ($processedCount > 0) {
        error_log("Synced {$processedCount} VisitorPersons successfully.");
    }

    return array(
        'success' => true,
        'count'   => $processedCount,
        'message' => "{$processedCount} visitor person(s) processed successfully."
    );
}
}