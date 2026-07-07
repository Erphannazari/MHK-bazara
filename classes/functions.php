<?php

use Morilog\Jalali\CalendarUtils;

if (! defined('ABSPATH')) {
    exit;
}

function bazara_options_default()
{

    return array(
        'username' => '',
        'password'   => '',
        'systemSyncID'   => '',
        'packageNumber'   => '',
        'banks' => '',
        'refresh_interval' => 5,
        'publishStatus' => 'publish',
        'databaseVersion' => 0
    );
}
function bazara_options_visitor()
{

    return array(
        'StoreCode' => 0,
        'CashCode'   => 0,
        'BankCode'   => 0,
        'VisitorId'   => 0
    );
}
function bazara_taxonomy_term()
{


    return array(
        'id'         => ''
    );
}
function bazara_settings_visitor()
{


    return array(
        'chkProduct'         => true,
        'chkCustomer'        => true,
        'chkPicture'         => true,
        'chkTitle'           => true,
        'chkQuantity'        => true,
        'chkPrice'           => true,
        'chkOrder'           => true,
        'chkRadioGroup'      => true,
        'chkRadioCat'        => true,
        'chkRadioCatGroup'   => true,
        'chkCustomerMahak'   => true,
        'chkProductsRolePrice'   => true,
        'selectCurrencySoftware'   => 'rial',
        'selectCurrencyPlugin'   => 'toman',
        'selectRegularPrice'   => 1,
        'selectPrice'        => 1,
    );
}
function bazara_visitor_soft_settings()
{


    return array(
        'SettingCode'         => ''

    );
}
function bazara_latest_versions()
{

    return array(
        'product' => 0,
        'price'   => 0,
        'picture'   => 0,
        'productDetail'   => 0,
        'Settings'   => 0,
        'ProductAsset'   => 0,
        'ExtraData'   => 0,
        'PersonGroup'   => 0,
        'PropertyDescriptions'   => 0,
        'Stores'   => 0,
        'Regions'   => 0,
        'VisitorProducts'   => 0,
        'CronProcessing'   => 0,
    );
}
add_action('init', 'delete_action_scheduler', 10);
function delete_action_scheduler()
{
    global  $wpdb;
    $table_name = $wpdb->prefix . "actionscheduler_actions";

    $wpdb->query("DELETE FROM $table_name where DATE_ADD(last_attempt_local,INTERVAL 2 MINUTE) < CURRENT_TIMESTAMP AND status = 'in-progress'");
}
function insert($table = 'bazara_products', $arr = array(), $field = '', $value = '')
{
    global  $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}$table WHERE $field= '$value' ";
    $query_results = $wpdb->get_results($query);
    if (count($query_results) == 0) {
        if ($table == 'bazara_products')
            $arr['new'] = 1;
        return $wpdb->insert($wpdb->prefix . $table, $arr);
    } else {
        //if ($table == 'bazara_products')
        // $arr['new'] = 0;

        return $wpdb->update($wpdb->prefix . $table, $arr, array($field => $value));
    }
}
function get_product_categories($CatID = 0, $itemType = 130)
{
    global  $wpdb;
    $cond = $wpdb->prepare(" AND CategoryID = %d", $CatID);
    $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}bazara_category where  ItemType = %s {$cond} ", $itemType);
    return $wpdb->get_results($query);
}
function get_stores()
{
    global  $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bazara_stores where  Deleted = 0  ", ARRAY_A);
}
function get_entity($table)
{
    global  $wpdb;

    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}{$table}  ", ARRAY_A);
}
function get_properties($id = 0, $name = '')
{
    global  $wpdb;
    $cond = "";
    if ($id > 0)
        $cond = $wpdb->prepare(" where PropertyDescriptionId = %d ", $id);
    if (!empty($name))
        $cond = $wpdb->prepare(" where Title = %s ", $name);

    $query = "SELECT * FROM {$wpdb->prefix}bazara_product_properties $cond";
    return $wpdb->get_results($query);
}
function update_schedule_sync($pid, $SyncType = 'detailSync', $value = 1, $tbl = 'bazara_products', $field = 'ProductId')
{
    global  $wpdb;
    $table_name = $wpdb->prefix . $tbl;
    if (empty($pid)) return false;
    $wpdb->query("UPDATE $table_name SET {$SyncType}={$value} WHERE {$field}=$pid");
}

function update_product_post_id($pid, $post_id)
{
    global  $wpdb;
    $table_name = $wpdb->prefix . 'bazara_products';
    if (empty($pid)) return false;
    $wpdb->query("UPDATE $table_name SET Post_ID= {$post_id} WHERE ProductCode=$pid");
}
function update_barcode($pid, $barcode = '')
{
    global  $wpdb;
    $table_name = $wpdb->prefix . 'bazara_products';
    if (empty($pid)) return false;
    $wpdb->query($wpdb->prepare("UPDATE $table_name SET barcode=%s WHERE ProductId=$pid", $barcode));
}
function update_product_as_old($pid, $flag = 0)
{
    global  $wpdb;
    $table_name = $wpdb->prefix . 'bazara_products';
    if (empty($pid)) return false;
    $wpdb->query("UPDATE $table_name SET new={$flag} WHERE ProductId=$pid");
}
function update_person_sync($pid)
{
    global  $wpdb;
    $table_name = $wpdb->prefix . 'bazara_persons';
    if (empty($pid)) return false;
    $wpdb->query("UPDATE $table_name SET isSync = 1 WHERE PersonId=$pid");
}
function update_picture_flag_for_sync($pid, $flag = 0)
{
    global  $wpdb;
    $table_nameGallery  = $wpdb->prefix . 'bazara_photo_gallery';
    $table_namePic = $wpdb->prefix . 'bazara_pictures';
    if (empty($pid)) return false;
    $wpdb->query("update {$table_nameGallery} ga,{$table_namePic} pic set pic.isSync = 0 WHERE pic.PictureID = ga.PictureID AND ga.ItemCode = {$pid}");
}
function change_product_issync_value($pid)
{
    global  $wpdb;
    $field = "ProductId";
    $table_name = $wpdb->prefix . 'bazara_products';
    if (empty($pid)) return false;
    $wpdb->query("UPDATE $table_name SET detailSync = 0,stockSync=0,priceSync = 0 WHERE ProductCode=$pid");
}
function get_product_id($pdid)
{
    global  $wpdb;

    return $wpdb->get_var("SELECT ProductID FROM {$wpdb->prefix}bazara_product_details where ProductDetailId = {$pdid} ");
}
function get_extras($type = 10202)
{
    global  $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}bazara_extra_data where ItemType = {$type} AND Deleted = 0";
    return $wpdb->get_results($query);
}
// --Old--
// function get_extra_datas($type = 130)
// {
//     global  $wpdb;
//     $query = "SELECT * FROM `{$wpdb->prefix}bazara_category` where ItemType = {$type} AND isSync = 0  ORDER BY `ParentID` ASC";
//     return $wpdb->get_results($query);
// }
// --New--
function get_extra_datas($type = 130)
{
    global $wpdb;

    $query = "
        SELECT c.*
        FROM {$wpdb->prefix}bazara_category AS c
        INNER JOIN {$wpdb->prefix}bazara_extra_data AS e
            ON c.ExtraDataId = e.ExtraDataId
        WHERE c.ItemType = %d
        AND c.isSync = 0
        AND e.Deleted = 0
        ORDER BY c.ParentID ASC
    ";

    return $wpdb->get_results($wpdb->prepare($query, $type));
}
function bazara_get_categories()
{
    global  $wpdb;
    $query = "SELECT * FROM `{$wpdb->prefix}bazara_sub_category` where isSync = 0  ";
    return $wpdb->get_results($query, ARRAY_A);
}
function bazara_banks()
{
    global  $wpdb;
    $query = "SELECT * FROM `{$wpdb->prefix}bazara_banks`";
    return $wpdb->get_results($query);
}
function update_sub_category_isSync($pid)
{
    global  $wpdb;
    $table_name = $wpdb->prefix . 'bazara_sub_category';
    if (empty($pid)) return false;
    $wpdb->query("UPDATE $table_name SET isSync=1 WHERE ProductCategoryId=$pid");
}
function update_category_isSync($pid)
{
    global  $wpdb;
    $table_name = $wpdb->prefix . 'bazara_category';
    if (empty($pid)) return false;
    $wpdb->query("UPDATE $table_name SET isSync=1 WHERE ExtraDataId=$pid");
}
function get_last_row_version($tbl = 'product')
{
    global  $wpdb;

    $table = 'bazara_products';
    $id = 'p_id';
    switch ($tbl) {
        case 'productDetail':
            $table = 'bazara_product_details';
            $id = 'p_d_id';
            break;
        case 'ProductAsset':
            $table = 'bazara_product_assets';
            $id = 'p_a_id';

            break;
        case 'VisitorProducts':
            $table = 'bazara_visitor_products';
            $id = 'v_id';

            break;
        case 'ExtraData':
            $table = 'bazara_extra_data';
            $id = 'ExtraDataId';

            break;
        case 'PropertyDescriptions':
            $table = 'bazara_product_properties';
            $id = 'p_p_id';
            break;
        case 'Regions':
            $table = 'bazara_regions';
            $id = 'RegionID';
            break;
        case 'PersonGroup':
            $table = 'bazara_person_groups';
            $id = 'PersonGroupId';
            break;
        case 'PhotoGalleries':
            $table = 'bazara_photo_gallery';
            $id = 'PhotoGalleryId';
            break;
        case 'Pictures':
            $table = 'bazara_pictures';
            $id = 'PictureId';
            break;
        case 'Banks':
            $table = 'bazara_banks';
            $id = 'BankId';
            break;
        case 'Persons':
            $table = 'bazara_persons';
            $id = 'PersonId';
            break;
        case 'VisitorPersons':
            $table = 'bazara_visitor_persons';
            $id = 'v_p_id';
            break;
        case 'SubCategory':
            $table = 'bazara_sub_category';
            $id = 'ProductCategoryId';
            break;
        case 'Transactions':
            $table = 'bazara_transactions';
            $id = 'TransactionId';
            break;
        case 'Orders':
            $table = 'bazara_orders';
            $id = 'OrderId';
            break;
        case 'OrderDetails':
            $table = 'bazara_order_details';
            $id = 'OrderDetailId';
            break;
        case 'Stores':
            $table = 'bazara_stores';
            $id = 'StoreId';
            break;
    }

    $query = "select case when not exists ( SELECT 1 FROM {$wpdb->prefix}{$table} LIMIT 1) then 0 else (SELECT IFNULL(MAX(RowVersion),0) as RowVersion FROM {$wpdb->prefix}{$table}) END;";
    $query = $wpdb->get_var($query);
    return empty($query) ? 0 : $query;
}

function get_products($all = false, $min = 0, $schedule = false)
{
    global  $wpdb;
    global $offset;

    $cond = '';
    $cond2 = '';
    $setting_Options = get_bazara_visitor_settings();


    if (!$all)
        $cond = ' limit ' . $min . ',' . $offset;
    if ($schedule) {
        $cond2 = ' AND ((detailSync = 0 OR detailSync IS NULL) ';

        $cond = '';
    }
    if (!class_exists('bazara_ratio_calculator') && $setting_Options['chkPrice'])
        $cond2 .= ' OR (priceSync = 0 OR priceSync IS NULL) ';

    if ($setting_Options['chkQuantity'])
        $cond2 .= ' OR (stockSync = 0 OR stockSync IS NULL) ';

    $cond2 .= ' )';

    $query = "SELECT * FROM {$wpdb->prefix}bazara_products where Deleted = 0 AND (queue = 0 or queue IS NULL)  {$cond2} order by ProductID {$cond} ";
    return $wpdb->get_results($query);
}
function get_products_v3($all = false, $min = 0, $max = 10000, $schedule = false, $selected_ids = null)
{
    global  $wpdb;

    $cond = '';
    $cond2 = '';
    $setting_Options = get_bazara_visitor_settings();

    if (!$all)
        $cond = ' limit ' . (int)$min . ',' . (int)$max;
    if ($schedule) {
        $cond2 = ' AND ((detailSync = 0 OR detailSync IS NULL) ';
    }
    if (!class_exists('bazara_ratio_calculator') && $setting_Options['chkPrice'])
        $cond2 .= ' OR (priceSync = 0 OR priceSync IS NULL) ';

    if ($setting_Options['chkQuantity'])
        $cond2 .= ' OR (stockSync = 0 OR stockSync IS NULL) ';

    $cond2 .= ' )';

    // If selected_ids is provided, only get those products
    if ($selected_ids !== null) {
        $ids = implode(',', array_map('intval', $selected_ids));
        $query = "SELECT * FROM {$wpdb->prefix}bazara_products where Deleted = 0 AND (queue = 0 or queue IS NULL) AND ProductCode IN ({$ids}) {$cond2} order by ProductID {$cond} ";
    } else {
        $query = "SELECT * FROM {$wpdb->prefix}bazara_products where Deleted = 0 AND (queue = 0 or queue IS NULL) {$cond2} order by ProductID {$cond} ";
    }
    
    return $wpdb->get_results($query);
}
function get_banks()
{
    global  $wpdb;

    $query = "SELECT * FROM {$wpdb->prefix}bazara_banks where Deleted = 0  ";
    return $wpdb->get_results($query, ARRAY_A);
}
function get_persons()
{
    global  $wpdb;

    $query = "SELECT * FROM {$wpdb->prefix}bazara_persons where Deleted = 0 AND PersonCode > 0 ";
    return $wpdb->get_results($query, ARRAY_A);
}
function get_all_persons()
{
    global  $wpdb;

    $query = "SELECT p1.*,g1.Name as gName FROM {$wpdb->prefix}bazara_persons p1 JOIN {$wpdb->prefix}bazara_person_groups g1 ON p1.PersonGroupId = g1.PersonGroupId  where p1.Deleted = 0 AND p1.isSync = 0 ";
    return $wpdb->get_results($query, ARRAY_A);
}
function get_person_group($isArray = false, $title = '')
{
    global  $wpdb;

    $cond = '';
    if (!empty($title))
        $cond = " where Name = '{$title}' ";
    $query = "SELECT * FROM {$wpdb->prefix}bazara_person_groups {$cond}";
    if ($isArray)
        return $wpdb->get_results($query, ARRAY_A);

    return $wpdb->get_results($query);
}
function update_picture_status($pid, $value = 1)
{
    global  $wpdb;
    $table_name = $wpdb->prefix . 'bazara_pictures';
    if (empty($pid)) return false;
    $wpdb->query("UPDATE $table_name SET isSync= {$value} WHERE PictureId=$pid");
    // SELECT * FROM wp_bazara_product_details s1 where ProductID = 1775028 and (select count(1) from wp_bazara_product_details where ProductId = s1.ProductId and Properties IS NULL) = 1 AND (select count(1) from wp_bazara_product_details where ProductId = s1.ProductId and Properties <> '' and Deleted = 1) > 0 

}
function check_product_is_deleted($pid)
{
    global  $wpdb;

    $query = "SELECT count(1) as cnt FROM {$wpdb->prefix}bazara_product_details where ProductId = {$pid} AND Deleted = 0";
    $query = $wpdb->get_var($query);
    return empty($query) ? 0 : $query;
}
function change_visitor()
{
    global  $wpdb;
    $table_name_post_meta = $wpdb->prefix . 'postmeta';
    $table_name_post = $wpdb->prefix . 'posts';
    $table_name_bazara_products = $wpdb->prefix . 'bazara_products';
    $table_name_user_meta = $wpdb->prefix . 'usermeta';
    $wpdb->query("update $table_name_post pm,(select $table_name_post.ID as ID from $table_name_post 
                JOIN $table_name_post_meta ON $table_name_post_meta.post_id = $table_name_post.ID 
                WHERE $table_name_post_meta.meta_key ='mahak_id') sp set pm.post_status = 'trash' WHERE pm.ID = sp.ID;");
    $wpdb->query("DELETE FROM $table_name_user_meta where meta_key LIKE 'mahak_%'");
    $wpdb->query("DELETE FROM $table_name_post_meta where meta_key LIKE 'mahak_%'");


    $entities = [
        'bazara_products',
        'bazara_product_assets',
        'bazara_visitor_products',
        'bazara_product_details',
        'bazara_extra_data',
        'bazara_product_properties',
        'bazara_person_groups',
        'bazara_photo_gallery',
        'bazara_category',
        'bazara_pictures',
        'bazara_persons',
        'bazara_visitor_persons',
        'bazara_banks',
        'bazara_sub_category',
        'bazara_orders',
        'bazara_order_details',

    ];



    for ($i = 0; $i < count($entities); $i++) {
        $table = $wpdb->prefix . $entities[$i];

        $wpdb->query("DELETE FROM $table");
    }
}
function get_product_assets($detail, $StoreID = "")
{
    global  $wpdb;

    $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}bazara_product_assets where ProductDetailId = %d AND StoreId = %d", $detail, $StoreID);
    return $wpdb->get_results($query);
}
function get_product_detail_with_var($ProductID)
{
    global  $wpdb;

    $query = "SELECT *
    FROM {$wpdb->prefix}bazara_product_details as detail
    WHERE
    detail.ProductId = {$ProductID} AND Deleted = 0 AND NOT Properties IS NULL   ORDER BY detail.p_d_id ASC";
    return $wpdb->get_results($query);
}
function get_products_v2()
{
    global  $wpdb;
    global $offset;


    $query = "SELECT * FROM {$wpdb->prefix}bazara_products where Deleted = 0  order by ProductID  ";
    return $wpdb->get_results($query);
}
function get_product_details($ProductID, $StoreID = "")
{
    global  $wpdb;
    $cond = "";
    if (!empty($StoreID))
        $cond .= " where StoreId in({$StoreID}) ";

    $query = "SELECT 
    detail.ProductDetailId as ProductDetailId,
    detail.Properties as Properties,
    detail.Prices as Prices,
    detail.Deleted as Deleted,
    detail.Discounts as Discounts,
    detail.DefaultSellPriceLevel as DefaultSellPriceLevel,
    detail.DefaultDiscountLevel as DefaultDiscountLevel,
    detailAsset.Count1 as Count1,
    detailAsset.Count2 as Count2,
    detail.Prices as Prices,
    (CASE WHEN((select count(1) as cnt from {$wpdb->prefix}bazara_product_details  
    where NOT Properties IS NULL and Deleted = 1 
    AND  ProductId = {$ProductID}
    group by ProductId having count(*) >= 1 ) = (select count(1) as cnt from {$wpdb->prefix}bazara_product_details  
    where NOT Properties IS NULL  
    AND  ProductId = {$ProductID}
    group by ProductId having count(*) >= 1 ) )THEN
    1
    ELSE
    0
    END) as NotVariation ,
    detailAsset.ProductDetailStoreAssetId as ProductDetailStoreAssetId
    FROM {$wpdb->prefix}bazara_product_details as detail
    LEFT JOIN (select StoreId,ProductDetailId,sum(Count1) as Count1,sum(Count2) as Count2,ProductDetailStoreAssetId from {$wpdb->prefix}bazara_product_assets {$cond}  GROUP by ProductDetailId) as detailAsset 
    ON detail.ProductDetailId = detailAsset.ProductDetailId 
    
    WHERE
    detail.ProductId = {$ProductID} ORDER BY detail.p_d_id ASC";
    return $wpdb->get_results($query);
}
function get_product_details_unsynced($ProductID, $StoreID = "")
{
    global  $wpdb;
    $cond = "";
    if (!empty($StoreID))
        $cond .= " where StoreId in({$StoreID}) ";

    $query = "SELECT 
    detail.ProductDetailId as ProductDetailId,
    detail.Properties as Properties,
    detail.Prices as Prices,
    detail.Deleted as Deleted,
    detail.Discounts as Discounts,
    detail.DefaultSellPriceLevel as DefaultSellPriceLevel,
    detail.DefaultDiscountLevel as DefaultDiscountLevel,
    detailAsset.Count1 as Count1,
    detailAsset.Count2 as Count2,
    detail.Prices as Prices,
    (
        CASE 
            WHEN detail.Properties IS NOT NULL THEN 0 ELSE 1 END) as NotVariation ,
    detailAsset.ProductDetailStoreAssetId as ProductDetailStoreAssetId
    FROM {$wpdb->prefix}bazara_product_details as detail
    LEFT JOIN (select StoreId,ProductDetailId,sum(Count1) as Count1,sum(Count2) as Count2,ProductDetailStoreAssetId from {$wpdb->prefix}bazara_product_assets {$cond}  GROUP by ProductDetailId) as detailAsset 
    ON detail.ProductDetailId = detailAsset.ProductDetailId 
    
    WHERE
    detail.ProductId = {$ProductID} AND detail.Deleted = 0 ORDER BY detail.p_d_id ASC";

    return $wpdb->get_results($query);
}
function get_product_asset_quantity($productDetailID = 0, $StoreID = 0)
{
    global  $wpdb;
    $count = $wpdb->get_var("SELECT IFNULL(Count1,0) as Count1 FROM {$wpdb->prefix}bazara_product_assets where ProductDetailId = {$productDetailID} AND Deleted = 0 AND StoreId = {$StoreID}");
    return (@$count ?? 0);
}
function get_provinces($name = '')
{
    global  $wpdb;
    $query = !empty($name) ? " WHERE ProvinceName LIKE '%{$name}%'" : "";
    $query = "SELECT * FROM {$wpdb->prefix}bazara_regions {$query} Group BY ProvinceID";
    return $wpdb->get_results($query);
}
function get_cities($province = '', $name = '', $type = 'exact')
{
    global  $wpdb;
    $cityLen = strlen($name);

    if ($type == 'exact')
        $query = !empty($name) ? " AND CityName ='{$name}'" : "";
    else
        $query = !empty($name) ? " AND (CityName LIKE '%{$name}%')" : "";
    $provinceLen = strlen($province);
    $query = "SELECT * FROM {$wpdb->prefix}bazara_regions where (ProvinceName LIKE '%{$province}%') {$query}";
    return $wpdb->get_results($query);
}
function normalize_persian_string($string) {
    // تبدیل "ی" فارسی (U+06CC) به "ي" عربی (U+064A)
    $string = str_replace('ی', 'ي', $string);
    return $string;
}
function get_visitor_products_count($productDetailID = '', $VisitorID = '')
{
    global  $wpdb;
    $rowcount = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bazara_visitor_products where ProductDetailId = {$productDetailID} AND VisitorID = {$VisitorID} AND Deleted = 0 ");
    return $rowcount;
}
function get_product_cnt()
{
    global  $wpdb;
    $rowcount = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bazara_products where detailSync = 0 OR stockSync = 0 OR priceSync = 0 ");
    return $rowcount;
}
function get_visitor_products($productDetailID = '', $VisitorID = '')
{
    global  $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}bazara_visitor_products where ProductDetailId = {$productDetailID} AND VisitorID = {$VisitorID} AND Deleted = 0 ";
    return $wpdb->get_results($query);
}
/*function get_roles()
{
    global  $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}bazara_person_groups ";
    return $wpdb->get_results($wpdb->prepare($query));
}*/
function bazara_save_log($date, $title, $comment, $is_success)
{
    global $wpdb;
    $wpdb->insert(
        "{$wpdb->prefix}bazara_logs",
        array(
            'log_date' => $date,
            'log_title' => $title,
            'log_comment' => $comment,
            'is_success' => $is_success,
        ),
        array(
            '%s',
            '%s',
            '%s',
            '%d'
        )
    );
}

function get_income_commission_from_customer($customer_id)
{
    global $wpdb;
    return $wpdb->get_var("SELECT SUM(commission_price) FROM `{$wpdb->prefix}mahak_networkaffiliate_commissions` WHERE `customer_id` = " . $customer_id);
}
add_action('wp_ajax_nopriv_bazara_get_sync_percentage', 'bazara_get_sync_percentage');
add_action('wp_ajax_bazara_get_sync_percentage', 'bazara_get_sync_percentage');
function bazara_get_sync_percentage()
{
    global  $wpdb;
    $all =  $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}bazara_products where NOT (IsSync = 1 and queue = 1)"));
    $Sync =  $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}bazara_products where NOT (IsSync = 1 and queue = 1) and IsSync = 1"));
    wp_send_json(array('all' => $all, 'sync' => $Sync));
    die;
}
add_action('wp_ajax_bazara_check_user_access', 'bazara_check_user_access_ajax');
add_action('wp_ajax_bazara_check_support_login', 'bazara_check_support_login_ajax');
add_action('wp_ajax_nopriv_bazara_save_settings', 'bazara_save_settings');
add_action('wp_ajax_bazara_save_settings', 'bazara_save_settings');

// start synchoronize options DB
const bazaraOptionsNeedSupportAccess = [
    'chkProduct',
    'chkPicture',
    'chkTitle',
    'chkQuantity',
    'chkPrice',
    'chkExcludedProductsByCategory',
    'ExcludedProductsByCategory',
    'chkCategory',
    'chkDontRemoveAttributes',
    'barcode',
    'description',
    'chkCustomer',
    'chkCustomersMahak',
    'radioCustomer',
    'generalCustomerID',
    'guestPersonID',
    'chkGuestCustomer',
    'banksMethods',
    'carrierMethods',
    // 'chkRegularPrice',
    // 'chkSalePrice',
    // 'DiscountPriceOrPercent',
    // 'RegularPrice',
    'publishStatus',
    // 'discount',
    'selectCurrencySoftware',
    'selectCurrencyPlugin',
    'chkBankOrder',
    'chkShippingOrder',
    'order_id_greater_than',
    'chkLastOrderID',
    'customerGroupID',
    'variation_date_condition',
    'variationVisibilityType',
    'StoresSortOrder',
    'StorePriorityToggle',
    // 'dateFirstCond',
    // 'dateFirstCondPrice',
    // 'dateFirstCondDiscount',
    // 'dateSecondCond',
    // 'dateSecondCondPrice',
    // 'dateSecondCondDiscount',
    // 'dateThirdCond',
    // 'dateThirdCondPrice',
    // 'dateThirdCondDiscount',
    // 'bazara_regular_multiprice_price_select',
    // 'bazara_regular_multiprice_role_select',
    // 'bazara_regular_multiprice_cheque_select',
    // 'bazara_regular_multiprice_role_cheque',
    // 'bazara_regular_multiprice_discount_price_select',
    // 'bazara_regular_multiprice_role_discount',
];
const bazaraOptionsForSEO = [
    'description' => 'همگام سازی توضیحات کالا',
    'chkTitle' => 'نام محصول',
    'chkDontRemoveAttributes' => 'عدم حذف ویژگی'
];
function bazara_check_support_login_ajax()
{
    try {
        $syncPasswordInput = $_REQUEST['syncPasswordInput'];
        $url = "https://utils.mahaksoft.com/bazara/assistant_verification";
        $data = [
            'password' => $syncPasswordInput
        ];
        $response = wp_remote_request(
            $url,
            array(
                'method' => 'POST',
                'body' => $data,
                'timeout' => 120
            )
        );
        $res = $response['body'] ? json_decode($response['body']) : false;

        $result = [
            'access' => $res ? $res->status : false,
            'message' => $res ? $res->message : "خطا در اتصال به وب سرویس"
        ];
    } catch (Exception $e) {
        $result = [
            'access' => false,
            'message' => "خطا در لاگین",
            'messageDetail' => $e->getMessage()
        ];
    }
    wp_send_json($result);
}

function recursive_array_diff_keys($array1, $array2, $parentKey = '')
{
    $result = [];

    foreach ($array1 as $key => $value) {
        $currentKey = $parentKey ? $parentKey . '.' . $key : $key;

        if (!array_key_exists($key, $array2)) {
            $result[] = $currentKey;
        } elseif (is_array($value) && is_array($array2[$key])) {
            $nestedDiff = recursive_array_diff_keys($value, $array2[$key], $currentKey);
            if (!empty($nestedDiff)) {
                $result = array_merge($result, $nestedDiff);
            }
        } elseif ($value !== $array2[$key]) {
            $result[] = $currentKey;
        }
    }

    foreach ($array2 as $key => $value) {
        $currentKey = $parentKey ? $parentKey . '.' . $key : $key;

        if (!array_key_exists($key, $array1)) {
            $result[] = $currentKey;
        }
    }

    return $result;
}

function bazara_check_user_access_ajax()
{
    $setting_Options = get_bazara_visitor_settings();
    $form_Options = bazara_convert_request_to_options(true);

    $diffOptionsKeys = recursive_array_diff_keys($setting_Options, $form_Options);

    //$checkShouldLogin = !empty(array_intersect($diffOptionsKeys, bazaraOptionsNeedSupportAccess));

    $checkShouldLogin = false;
    foreach ($diffOptionsKeys as $diffKey) {
        foreach (bazaraOptionsNeedSupportAccess as $supportKey) {
            if (strpos($diffKey, $supportKey) === 0) {
                $checkShouldLogin = true;
                break 2; // چون یکی کافیست
            }
        }
    }

    $checkShouldSync = !empty($diffOptionsKeys);

    $seoData = bazara_get_seo_message($diffOptionsKeys);
    if ($checkShouldSync) {
        if ($checkShouldLogin) {
            $result = [
                'checkShouldLogin' => true,
                'checkShouldSync' => true,
                'seoMessage' => $seoData['status'] ? $seoData['message'] : false,
            ];
        } else {
            $result = [
                'checkShouldLogin' => false,
                'checkShouldSync' => true,
                'seoMessage' => $seoData['status'] ? $seoData['message'] : false,
            ];
        }
    } else {
        $result = [
            'checkShouldLogin' => false,
            'checkShouldSync' => false,
            'seoMessage' => $seoData['status'] ? $seoData['message'] : false,
        ];
    }

    wp_send_json($result);
}
function bazara_save_settings()
{

    check_ajax_referer('bazara_security', 'security');
    $setting_Options = get_bazara_visitor_settings();
    $options = bazara_convert_request_to_options(true);
    global  $wpdb;

    if ($setting_Options['chkSalePrice'] &&  !$options['chkSalePrice']) {

        $wpdb->query("UPDATE {$wpdb->prefix}postmeta as p1 SET p1.meta_value=IFNULL((select meta_value from {$wpdb->prefix}postmeta where meta_key='_regular_price' and post_id=p1.post_id),0) WHERE meta_key='_price' AND p1.post_id IN (select * from (select post_id from {$wpdb->prefix}postmeta where meta_key='mahak_id' or mahak_key='mahak_product_detail_id') as t);");
    }

    if (
        $setting_Options['chkExcludedProductsByCategory'] <> $options['chkExcludedProductsByCategory'] ||
        $setting_Options['ExcludedProductsByCategory'] <> $options['ExcludedProductsByCategory'] ||
        $setting_Options['StoresSortOrder'] <> $options['StoresSortOrder'] ||
        $setting_Options['StorePriorityToggle'] <> $options['StorePriorityToggle']

    ) {
        $wpdb->query("UPDATE {$wpdb->prefix}bazara_products SET stockSync = 0");
    }
    if (
        $setting_Options['chkCategory'] <> $options['chkCategory'] ||
        $setting_Options['variationVisibilityType'] <> $options['variationVisibilityType'] ||
        $setting_Options['variation_date_condition'] <> $options['variation_date_condition']
    ) {
        $wpdb->query("UPDATE {$wpdb->prefix}bazara_products SET detailSync = 0");
    }
    if (
        $setting_Options['DiscountPriceOrPercent'] <> $options['DiscountPriceOrPercent'] ||
        $setting_Options['chkRegularPrice'] <> $options['chkRegularPrice'] ||
        $setting_Options['chkSalePrice'] <> $options['chkSalePrice'] ||
        $setting_Options['selectCurrencySoftware'] <> $options['selectCurrencySoftware'] ||
        $setting_Options['selectCurrencyPlugin'] <> $options['selectCurrencyPlugin'] ||
        $setting_Options['discount'] <> $options['discount'] ||
        $setting_Options['RegularPrice'] <> $options['RegularPrice']
    ) {
        $wpdb->query("UPDATE {$wpdb->prefix}bazara_products SET priceSync = 0");
    }
    update_option('bazara_visitor_settings', $options);

    // Ensure persons sync cron reflects current setting immediately
    $bazara_options_for_cron = get_option('bazara_options');
    $cron_minutes = empty($bazara_options_for_cron['refresh_interval']) ? 0 : (int) $bazara_options_for_cron['refresh_interval'];
    $is_auto_sync_active = !empty($bazara_options_for_cron['active_auto_sync']);

    if (empty($options['chkCustomersMahak'])) {
        if (function_exists('as_next_scheduled_action') && as_next_scheduled_action('bazara_run_persons_sync')) {
            as_unschedule_all_actions('bazara_run_persons_sync');
        }
    } else if ($is_auto_sync_active && $cron_minutes > 0) {
        if (function_exists('as_next_scheduled_action') && false === as_next_scheduled_action('bazara_run_persons_sync')) {
            as_schedule_recurring_action(strtotime("+ $cron_minutes MINUTES"), (MINUTE_IN_SECONDS * $cron_minutes), 'bazara_run_persons_sync');
        }
    }

    $message = "تنظیمات با موفقیت ثبت شد.";
    wp_send_json(array('result' => $message));
    die();
}
function bazara_get_seo_message($options)
{
    $values = '';
    foreach (bazaraOptionsForSEO as $key => $value) {
        if (in_array($key, $options)) {
            $values .= $value . ', ';
        }
    }
    $message = "تنظیمات $values در <b>سئو</b> سایت تاثیر میگذارند، آیا از اعمال تغییرات مطمئن هستید؟";
    return [
        'status' => $values != '',
        'message' => $message
    ];
}
function bazara_convert_request_to_options($hasRequest = false)
{
    if (!$hasRequest) {
        return [];
    }

    $visitor_settings = get_option('bazara_visitor_settings', []);

    $options = array(
        'chkProduct'        => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_sync_product_toggle'] ?? '')),
        'chkPicture'        => toggle_to_boolean(sanitize_text_field($_REQUEST['chkUploadPics'] ?? '')),
        'chkTitle'          => toggle_to_boolean(sanitize_text_field($_REQUEST['chkTitle'] ?? '')),
        'chkQuantity'       => toggle_to_boolean(sanitize_text_field($_REQUEST['chkQuantity'] ?? '')),
        'chkPrice'          => toggle_to_boolean(sanitize_text_field($_REQUEST['chkPrice'] ?? '')),
        'chkExcludedProductsByCategory' => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_except_category'] ?? '')),
        'ExcludedProductsByCategory'    => sanitize_text_field($_REQUEST['ExcludedProductsByCategory'] ?? ''),
        'chkCategory'       => sanitize_text_field($_REQUEST['category'] ?? ''),
        'chkDontRemoveAttributes' => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_attribute_toggle'] ?? '')),
        'barcode'           => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_barcode_toggle'] ?? '')),
        'description'       => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_description_toggle'] ?? '')),
        'chkCustomer'       => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_person_toggle'] ?? '')),
        'chkCustomersMahak' => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_receive_person_toggle'] ?? '')),
        'radioCustomer'     => sanitize_text_field($_REQUEST['customer'] ?? ''),
        'generalCustomerID' => sanitize_text_field($_REQUEST['publicPerson'] ?? ''),
        'guestPersonID'     => sanitize_text_field($_REQUEST['person'] ?? ''),
        'chkGuestCustomer'  => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_guest_person'] ?? '')),
        'banksMethods'      => sanitize_text_field($_REQUEST['banksMethods'] ?? ''),
        'carrierMethods'    => sanitize_text_field($_REQUEST['carrierMethods'] ?? ''),
        'chkRegularPrice'   => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_regular_price_toggle'] ?? '')),
        'chkSalePrice'      => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_sale_price_toggle'] ?? '')),
        'DiscountPriceOrPercent' => (($_REQUEST['discount'] ?? '') == "percent_discount" 
            ? sanitize_text_field($_REQUEST['btn-percent-discount'] ?? '') 
            : sanitize_text_field($_REQUEST['btn-price-discount'] ?? '')),
        'RegularPrice'      => sanitize_text_field($_REQUEST['btn-reg-price'] ?? ''),
        'publishStatus'     => sanitize_text_field($_REQUEST['btn-check-status'] ?? ''),
        'discount'          => sanitize_text_field($_REQUEST['discount'] ?? ''),
        'selectCurrencySoftware' => sanitize_text_field($_REQUEST['btn-check-soft-currency'] ?? ''),
        'selectCurrencyPlugin'   => sanitize_text_field($_REQUEST['btn-check-site-currency'] ?? ''),
        'chkBankOrder'      => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_bank_order_toggle'] ?? '')),
        'chkShippingOrder'  => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_shipping_order_toggle'] ?? '')),
        'order_id_greater_than' => (!empty($_REQUEST['order_id_greater_than'] ?? '') && ($_REQUEST['order_id_greater_than'] ?? '0') != "0"
            ? (int) sanitize_text_field($_REQUEST['order_id_greater_than'])
            : (int) ($visitor_settings['order_id_greater_than'] ?? 0)),
        'chkLastOrderID'    => toggle_to_boolean(sanitize_text_field($_REQUEST['bazara_last_order_id'] ?? '')),
        'customerGroupID'   => sanitize_text_field($_REQUEST['PersonGroup'] ?? ''),
        'variation_date_condition' => sanitize_text_field($_REQUEST['variationDateCondition'] ?? ''),
        'variationVisibilityType'  => $_REQUEST['variationVisibilityType'] ?? '',
        'StoresSortOrder'   => $_REQUEST['StorePriorityOrders'] ?? [],
        'StorePriorityToggle' => $_REQUEST['bazara_anbar_priority'] ?? '',
        'dateFirstCond'     => $_REQUEST['date_first_select'] ?? '',
        'dateFirstCondPrice' => $_REQUEST['date_first_select_price'] ?? '',
        'dateFirstCondDiscount' => $_REQUEST['date_first_select_discount'] ?? '',
        'dateSecondCond'    => $_REQUEST['date_second_select'] ?? '',
        'dateSecondCondPrice' => $_REQUEST['date_second_select_price'] ?? '',
        'dateSecondCondDiscount' => $_REQUEST['date_second_select_discount'] ?? '',
        'dateThirdCond'     => $_REQUEST['date_third_select'] ?? '',
        'dateThirdCondPrice' => $_REQUEST['date_third_select_price'] ?? '',
        'dateThirdCondDiscount' => $_REQUEST['date_third_select_discount'] ?? '',
        'bazara_regular_multiprice_price_select'   => $_REQUEST['bazara_regular_multiprice_price_select'] ?? '',
        'bazara_regular_multiprice_role_select'    => $_REQUEST['bazara_regular_multiprice_role_select'] ?? '',
        'bazara_regular_multiprice_cheque_select'  => $_REQUEST['bazara_regular_multiprice_cheque_select'] ?? '',
        'bazara_regular_multiprice_role_cheque'    => $_REQUEST['bazara_regular_multiprice_role_cheque'] ?? '',
        'bazara_regular_multiprice_discount_price_select' => $_REQUEST['bazara_regular_multiprice_discount_price_select'] ?? '',
        'bazara_regular_multiprice_role_discount'  => $_REQUEST['bazara_regular_multiprice_role_discount'] ?? '',
    );

    return $options;
}

add_action('wp_ajax_nopriv_bazara_save_visitor_setting', 'bazara_save_visitor_setting');
add_action('wp_ajax_bazara_save_visitor_setting', 'bazara_save_visitor_setting');
function bazara_save_visitor_setting()
{

    check_ajax_referer('bazara_security', 'security');

    $bazara = new BazaraApi(true);
    $options = get_option('bazara_options');

    $active_sync = toggle_to_boolean(sanitize_text_field($_REQUEST['active_auto_sync']));
    $options = array(
        'username'                 => (sanitize_text_field($_REQUEST['username'])),
        'password'                          => (sanitize_text_field($_REQUEST['password'])),
        'refresh_interval'                  => sanitize_text_field($_REQUEST['refresh_interval']),
        'active_auto_sync'                  => $active_sync,
        'publishStatus' => isset($_REQUEST['publishStatus']) ? sanitize_text_field($_REQUEST['publishStatus']) : '',
        'databaseVersion'                  => $options['databaseVersion'],

    );
    if (!$active_sync)
        $options['refresh_interval'] = 0;

    update_option('bazara_options', $options);
    wp_clear_scheduled_hook('mhk_bazara_schedule_hook');

    $validToken = $bazara->login_token();
    $lastVisitor = empty(get_bazara_visitor_options()['VisitorId']) ? '' : get_bazara_visitor_options()['VisitorId'];

    if (!empty($validToken) && $validToken['success'] == false) {
        $message = $validToken['message'];
        wp_send_json(array('status' => false, 'result' => $message));
        die();
    }
    $options['site_name'] = $validToken['object']['UserTitle'];
    $options['DatabaseId'] = $validToken['object']['DatabaseId'];
    $options['PackageNo'] = $validToken['object']['PackageNo'];
    $options['CreditDay'] = isset($validToken['object']['CreditDay']) ? (int)$validToken['object']['CreditDay'] : 0;
    update_option('bazara_options', $options);

    $savedVisitor = $validToken['VisitorID'];

    if (($savedVisitor != $lastVisitor)) {
        $message = "در این سایت قبلا با اطلاعات دیگری همگام سازی انجام شده،در صورت تایید کلیه کالاهای محکی قبلی حذف و یا آپدیت خواهند شد.مطمئن هستید؟";
        $bazara->set_visitor_options(null, $savedVisitor);
        wp_send_json(array('status' => 2, 'result' => $message, 'data' => $validToken['object']));
        die();
    }


    if (empty($lastVisitor))
        $bazara->set_visitor_options(null, $savedVisitor);

    $interval = empty($_REQUEST['refresh_interval']) ? 0 : $_REQUEST['refresh_interval'];
    if ($active_sync)
        bazara_run_cron($interval);
    else
        bazara_clear_cron();

    wp_send_json(array('status' => 1, 'result' => "تنظیمات با موفقیت ذخیره شد", 'data' => $validToken['object']));

    die();
}
add_action('bazara_run_product_sync', 'bazara_run_product_synchronize');
add_action('bazara_run_clean_queue', 'clear_tables_queue');
add_action('bazara_run_sending_orders', 'send_bazara_orders');
add_action('bazara_run_persons_sync', 'bazara_run_persons_synchronize');

function bazara_clear_cron()
{
    if (function_exists('as_next_scheduled_action') && as_next_scheduled_action('bazara_run_product_sync')) {
        as_unschedule_all_actions('bazara_run_product_sync');
    }

    if (function_exists('as_next_scheduled_action') && as_next_scheduled_action('bazara_run_clean_queue')) {
        as_unschedule_all_actions('bazara_run_clean_queue');
    }

    if (function_exists('as_next_scheduled_action') && as_next_scheduled_action('bazara_run_sending_orders')) {
        as_unschedule_all_actions('bazara_run_sending_orders');
    }

    if (function_exists('as_next_scheduled_action') && as_next_scheduled_action('bazara_run_persons_sync')) {
        as_unschedule_all_actions('bazara_run_persons_sync');
    }

    if (wp_next_scheduled('mhk_bazara_sched_hook'))
        wp_clear_scheduled_hook('mhk_bazara_sched_hook');
}
function bazara_run_cron($minutes = 0)
{

    bazara_clear_cron();
    if (false === as_next_scheduled_action('bazara_run_product_sync') && $minutes > 0) {
        as_schedule_recurring_action(strtotime("+ $minutes MINUTES"), (MINUTE_IN_SECONDS * $minutes), 'bazara_run_product_sync');
    }
    if (false === as_next_scheduled_action('bazara_run_sending_orders') && $minutes > 0) {
        as_schedule_recurring_action(strtotime("+ $minutes MINUTES"), (MINUTE_IN_SECONDS * $minutes), 'bazara_run_sending_orders');
    }
    if (false === as_next_scheduled_action('bazara_run_clean_queue')) {
        as_schedule_recurring_action(strtotime("+ 120 MINUTES"), (MINUTE_IN_SECONDS * 120), 'bazara_run_clean_queue');
    }

    // Schedule persons sync only if enabled in visitor settings
    $visitorSetting = get_bazara_visitor_settings();
    $syncPersons = !empty($visitorSetting['chkCustomersMahak']) && $visitorSetting['chkCustomersMahak'];
    if ($syncPersons && false === as_next_scheduled_action('bazara_run_persons_sync') && $minutes > 0) {
        as_schedule_recurring_action(strtotime("+ $minutes MINUTES"), (MINUTE_IN_SECONDS * $minutes), 'bazara_run_persons_sync');
    }
}
function bazara_init_cron()
{
    $minutes = get_option('bazara_options');
    $minutes = empty($minutes['refresh_interval']) ? 0 : $minutes['refresh_interval'];
    if (false === as_next_scheduled_action('bazara_run_product_sync')  && $minutes > 0) {
        as_schedule_recurring_action(strtotime("+ $minutes MINUTES"), (MINUTE_IN_SECONDS * $minutes), 'bazara_run_product_sync');
    }
    if (false === as_next_scheduled_action('bazara_run_sending_orders')  && $minutes > 0) {
        as_schedule_recurring_action(strtotime("+ $minutes MINUTES"), (MINUTE_IN_SECONDS * $minutes), 'bazara_run_sending_orders');
    }
    if (false === as_next_scheduled_action('bazara_run_clean_queue')) {
        as_schedule_recurring_action(strtotime("+ 120 MINUTES"), (MINUTE_IN_SECONDS * 120), 'bazara_run_clean_queue');
    }

    // Ensure persons sync is scheduled only when enabled
    $visitorSetting = get_bazara_visitor_settings();
    $syncPersons = !empty($visitorSetting['chkCustomersMahak']) && $visitorSetting['chkCustomersMahak'];
    if ($syncPersons && false === as_next_scheduled_action('bazara_run_persons_sync') && $minutes > 0) {
        as_schedule_recurring_action(strtotime("+ $minutes MINUTES"), (MINUTE_IN_SECONDS * $minutes), 'bazara_run_persons_sync');
    }
}
add_action('wp_ajax_nopriv_bazara_change_visitor_ajax', 'bazara_change_visitor_ajax');
add_action('wp_ajax_bazara_change_visitor_ajax', 'bazara_change_visitor_ajax');
function bazara_change_visitor_ajax()
{
    check_ajax_referer('bazara_security', 'security');
    change_visitor();

    $message = "تغییرات با موفقیت انجام شد";
    wp_send_json(array('status' => true, 'result' => $message));
    die();
}
function uploadMedia($image_url, $filename)
{
    require_once(ABSPATH . '/wp-admin/includes/image.php');
    require_once(ABSPATH . '/wp-admin/includes/file.php');
    require_once(ABSPATH . '/wp-admin/includes/media.php');
    $media = media_sideload_image($image_url, 0, $filename);
    if (is_wp_error($media))
        return $media;
    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => null,
        'post_parent' => 0,
        'orderby' => 'post_date',
        'order' => 'DESC'
    ));
    return $attachments[0]->ID;
}

function mahak_upload_media($url, $filename)
{
    $errors = '';
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $timeout_seconds = 15;
    //return array('success' => false, 'message' => $picture['Url']);
    // Download file to temp dir
    $temp_file = download_url($url, $timeout_seconds);

    if (!is_wp_error($temp_file)) {
        // Array based on $_FILE as seen in PHP file uploads
        $file = array(
            'name'     => strtotime("now") . $filename, // ex: wp-header-logo.png
            'type'     => 'image/jpg',
            'tmp_name' => $temp_file,
            'error'    => 0,
            'size'     => filesize($temp_file),
        );

        $overrides = array('test_form' => false, 'test_size' => true);

        // Move the temporary file into the uploads directory
        $results = wp_handle_sideload($file, $overrides);

        if (!empty($results['error'])) {
            $errors++;
            return $results['error'];
        } else {
            $filename  = $results['file']; // Full path to the file
            $local_url = $results['url'];  // URL to the file in the uploads dir
            $type      = $results['type']; // MIME type of the file

            $attachment = array(
                'post_mime_type' => $type,
                'post_title' => $filename,
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attach_id = wp_insert_attachment($attachment, $filename);
            $imagenew = get_post($attach_id);
            $fullsizepath = get_attached_file($imagenew->ID);
            $attach_data = wp_generate_attachment_metadata($attach_id, $fullsizepath);
            return $attach_id;
        }
    } else {
        return $temp_file;
    }
}
if (!function_exists('write_log')) {

    function write_log($log)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}
function create_new_woo_product($productService)
{

    return new WC_Product();
}
// Custom function for product creation (For Woocommerce 3+ only)
function create_product($args)
{
    set_time_limit(0);

    try {
        $options = get_bazara_visitor_settings();

        // write_log(json_encode($args));

        $product_id = $args['ProductId'];
        $product_code = $args['ProductCode'];
        $barcode = $args['barcode'];
        $description = $args['description'];

        $SerialProduct = $defaultAttributes = false;



        if (!function_exists('wc_get_product_object_type') && !function_exists('wc_prepare_product_attributes'))
            return false;

        $optionBarcode = $options['barcode'] == 1 || $options['barcode'];
        $optionDescription = $options['description'] == 1 || $options['description'];

        $OptionTitle = $options['chkTitle'] == 1 || $options['chkTitle'];
        $OptionQuantity = $options['chkQuantity'] == 1 || $options['chkQuantity'];
        $OptionPicture = $options['chkPicture'] == 1 || $options['chkPicture'];
        $OptionPrice = $options['chkPrice'] == 1 || $options['chkPrice'];
        $chkExcludedProductsByCategory = $options['chkExcludedProductsByCategory'] == 1 || $options['chkExcludedProductsByCategory'];
        $ExcludedProductsByCategory =  empty($options['ExcludedProductsByCategory']) ? '' :  $options['ExcludedProductsByCategory'];
        $SoftwareCurrency =  empty($options['selectCurrencySoftware']) ? 0 : $options['selectCurrencySoftware'];
        $PluginCurrency = empty($options['selectCurrencyPlugin']) ? 0 : $options['selectCurrencyPlugin'];
        $publishStatus = empty($options['publishStatus']) ? 'draft' : $options['publishStatus'];
        $variation_date_condition = !empty($options['variation_date_condition']) ? $options['variation_date_condition'] : '';

        $DontUpdateproduct = $salePrice = false;
        $map = $data = [];

        if ($optionBarcode)
            $product_code = $barcode;




        $product = bazara_get_product_by_sku($product_code);
        if (!empty($product)) {
            $pp = get_product_by_mahakID($product_id);
            if (!empty($pp)) {
                update_post_meta($pp->get_id(), 'mahak_id', '');
            }
        } else
            $product = get_product_by_mahakID($product_id);

        if ($args['deleted']) {



            update_schedule_sync($args['ProductId'], 'detailSync');
            update_schedule_sync($args['ProductId'], 'stockSync');
            update_schedule_sync($args['ProductId'], 'priceSync');

            update_product_queue($args['ProductId'], 0);

            if (!empty($product) && 'variation' !== $product->get_type()) {
                wp_update_post(array(
                    'ID' => $product->get_id(),
                    'post_status' => 'trash',
                ));
            }


            return array('success' => false, 'message' => '');
        }
        $notExist = false;
        $sku = (!$optionBarcode || $notExist) ? $product_code : '';
        if (empty($product)) {
            // Get an empty instance of the product object (defining it's type)
            $notExist = true;
            $product = wc_get_product_object_type($args['type'], $sku, $publishStatus);
            if (!$product)
                return false;
        }
        $product_date     = get_post_meta($product->get_id(), 'mahak_custom_date', false);

        if (empty($product_date)) {
            update_post_meta($product->get_id(), 'mahak_custom_date',  'yes');
        }
        $product_date     = get_post_meta($product->get_id(), 'mahak_custom_date', true) == 'yes';

        if ((class_exists("sell_simple_with_date_variants") && ($product_date)) || (!$notExist && 'variation' === $product->get_type() && !empty($args['vars'])))
            $SerialProduct = true;

        if ((class_exists("sell_simple_with_date_variants") && $notExist))
            $SerialProduct = true;

        if ($chkExcludedProductsByCategory && !empty($ExcludedProductsByCategory)) {


            $catTerms = get_the_terms($product->get_id(), 'product_cat');
            if (!empty($catTerms)) {
                foreach ($catTerms  as $term) {

                    $product_cat_id = $term->term_id;
                    if ($product_cat_id == $ExcludedProductsByCategory) {
                        $DontUpdateproduct  = true;
                        break;
                    }
                }
            }
        }
        if ($args['detailSync'] == 0) {



            if ($notExist || $OptionTitle)
                $product->set_name($args['name']);

            if (isset($args['slug']))
                $product->set_name($args['slug']);

            if ($optionDescription && isset($args['description']))
                $product->set_description($args['description']);


            if (!$optionBarcode || $notExist)
                $product->set_sku($product_code);


            // Status ('publish', 'pending', 'draft' or 'trash')
            if (!$notExist && 'variation' === $product->get_type())
                $product->set_status('publish');
            else
        if ($notExist && !$SerialProduct)
                $product->set_status($publishStatus);
            else
                $product->set_status($product->get_status());

            // Visibility ('hidden', 'visible', 'search' or 'catalog')
            //$product->set_catalog_visibility(isset($args['visibility']) ? $args['visibility'] : 'visible');

            // Featured (boolean)
            //  $product->set_featured(isset($args['featured']) ? $args['featured'] : false);

            //Skip from nested variable (Customization)
            $attr = $product->get_attributes();
            if(is_object($attr[array_key_first($attr)]) && get_class($attr[array_key_first($attr)]) === 'stdClass'){
                return array('success' => false, 'message' => 'کالای دارای جزئیات نمیتواند بعنوان متغییر یک محصول متغییر همگام سازی شود.');
            }

            //Attribute has null or empty options (Customization)
            $attributes = wc_prepare_product_attributes($args['attributes'], $product->get_id());

            // --- FIX attributes: ensure options is always an array --- //
            if ( ! empty( $attributes ) ) {
                foreach ( $attributes as $key => $attribute ) {

                    // فقط روی WC_Product_Attribute کار کنیم
                    if ( $attribute instanceof WC_Product_Attribute ) {

                        // گرفتن options فعلی
                        $options = $attribute->get_options();

                        // اگر null یا رشته بود → تبدیل به آرایه
                        if ( empty( $options ) ) {
                            $attribute->set_options( [] ); // آرایه خالی بدون مشکل ذخیره می‌شود
                        }
                        elseif ( is_string( $options ) ) {
                            // اگر به اشتباه به صورت رشته آمده مثل "قرمز,آبی"
                            $attribute->set_options( array_map( 'trim', explode( ',', $options ) ) );
                        }
                        elseif ( ! is_array( $options ) ) {
                            // هر نوع اشتباه دیگر → تبدیل امنی انجام می‌دهیم
                            $attribute->set_options( [ strval( $options ) ] );
                        }
                    }
                }
            }

            // foreach ($attributes as $key => $attribute) {
            //     $options = $attribute->get_options();
            //     if (empty($options)) {
            //         return array(
            //             'success' => false,
            //             'message' => "ویژگی '{$attribute->get_name()}' مقدار options ندارد و امکان همگام‌سازی وجود ندارد."
            //         );
            //     }
            // }

            // Attributes et default attributes
            if (isset($args['attributes'])  && !empty($args['vars']))
                $product->set_attributes($attributes);
            if (isset($args['default_attributes']))
                $product->set_default_attributes($args['default_attributes']); // Needs a special formatting

            // Product categories and Tags
            if (isset($args['category_ids']))
                $product->set_category_ids($args['category_ids']);
            if (isset($args['tag_ids']))
                $product->set_tag_ids($args['tag_ids']);

            //extra info.

            if (!empty($args['weight']) && $args['weight'] > 0 && !class_exists('bazara_ratio_calculator'))
                $product->set_weight($args['weight']);

            if (!empty($args['width']) && $args['width'] > 0)
                $product->set_width($args['width']);

            if (!empty($args['length']) && $args['length'] > 0)
                $product->set_length((int)$args['length']);

            if (!empty($args['height']) && $args['height'] > 0)
                $product->set_height($args['height']);
        }

        if ($args['stockSync'] == 0) {
            if (isset($args['qty']) && ($OptionQuantity || $OptionQuantity == 1) && !$DontUpdateproduct) {
            $is_variable = $product->get_type() === 'variable';
            
            if (!empty($args['vars']) && $SerialProduct) {
            $data['qty'] = 0;
            foreach ($args['vars'] as $attr) {
            if ($attr['deleted'] == 1 || $attr['deleted']) continue;
            $data['qty'] = $attr['qty'];
            $args['qty'] += $attr['qty'];
            $data['detail_id'] = $attr['detail_id'];
            if ($data['qty'] > 0)
            $map[] = $data;
            }
            $args['manage_stock'] = (int)$args['qty'] > 0;
            $args['stock_status'] = (int)$args['qty'] > 0 ? 'instock' : 'outofstock';
            // Only set stock quantity for variable products with SerialProduct
            $product->set_stock_quantity($args['qty']);
            }
            
            if (empty($args['vars']) && !$is_variable) {
            $order_qty = get_order_item_qty($product->get_id(), '_product_id');
            $order_qty = is_numeric($order_qty) && $order_qty >= 0 ? $order_qty : 0;
            $bazara_qty = get_bazara_not_converted_qty($args['ProductDetails'][0]);
            $bazara_qty = is_numeric($bazara_qty) && $bazara_qty >= 0 ? $bazara_qty : 0;
            $args['qty'] -= $order_qty + $bazara_qty;
            
            $args['manage_stock'] = $args['qty'] > 0;
            $product->set_stock_quantity($args['qty']);
            $product->set_manage_stock((isset($args['manage_stock']) && $args['qty'] > 0) ? $args['manage_stock'] : false);
            $product->set_stock_status((isset($args['qty']) && $args['qty'] > 0) ? 'instock' : 'outofstock');
            }
            
            if ($is_variable) {
            // Disable stock management at parent product level for variable products
            $product->set_manage_stock(false);
            // Do not set stock quantity for variable products
            $product->set_stock_status('instock'); // Default to instock, will be adjusted based on variations
            }
            
            if (isset($args['manage_stock']) && $args['manage_stock'] && !$is_variable) {
            $product->set_stock_status((isset($args['qty']) && $args['qty'] > 0) ? 'instock' : 'outofstock');
            $product->set_backorders(isset($args['backorders']) ? $args['backorders'] : 'no');
            }
            
            if (class_exists('bazara_ratio_calculator') && !$is_variable)
            $product->set_weight(number_format($args['qty2'], 2));
            
            if (empty($args['vars']))
            update_schedule_sync($args['ProductId'], 'stockSync');
            if (class_exists('WooCommerce_Role_Based_Price_Product_Pricing'))
            delete_product_wcrb_transient($product_id);
            
            if ($args['qty'] <= 0 || $is_variable) {
            if (class_exists('bazara_manage_quantity') && $args['qty'] <= 0) {
            $product->set_stock_quantity(0);
            $product->set_manage_stock(true);
            $product->set_stock_status('instock');
            } else {
            // Check if product is variable and has any instock variations
            $all_variations_outofstock = true;
            
            if ($is_variable) {
            $variations = wc_get_products([
            'type' => 'variation',
            'parent' => $product->get_id(),
            'stock_status' => 'instock',
            'limit' => 1,
            ]);
            $all_variations_outofstock = empty($variations);
            }
            
            // If simple product with qty <= 0 or all variations are out of stock, mark as outofstock
            if ((!$is_variable && $args['qty'] <= 0) || ($is_variable && $all_variations_outofstock)) {
            product_out_of_Stock($product->get_id());
            }
            }
            }
            } else
            update_schedule_sync($args['ProductId'], 'stockSync');
            }
        if ($args['detailSync'] == 0) {
            if (!empty($args['vars'])) {
                $selectedInvisibleVariation = !empty($options['variationVisibilityType']) ? ($options['variationVisibilityType']) : '';
                $invisibles = !empty($selectedInvisibleVariation['invisible']) ? array_values($selectedInvisibleVariation['invisible']) : array();
                $for_variation = !empty($selectedInvisibleVariation['for_variation']) ? array_values($selectedInvisibleVariation['for_variation']) : array();
                if (count($for_variation) < count(get_properties()))
                    $defaultAttributes = true;

                if ($defaultAttributes || (class_exists("sell_simple_with_date_variants"))) {
                    if (!empty($args['vars'])) {
                        $data['qty'] = 0;
                        $visibleAttrs = $defaultAttr = [];
                        $desc = get_properties();
                        if ($variation_date_condition == 2) {

                            usort($args['vars'], 'bazara_sortByGreatest');
                        } else if ($variation_date_condition == 3) {
                            (usort($args['vars'], 'bazara_sortByEarliest'));
                        }
                        foreach ($args['vars'] as $attr) {
                            if ($attr['deleted'] == 1 || $attr['deleted']) continue;
                            $data['qty'] = $attr['qty'];
                            $data['expireDate'] = '';
                            if (!empty($attr['expireDate']))
                                $data['expireDate'] = $attr['expireDate'];
                            $args['qty'] = $attr['qty'];
                            $data['detail_id'] = $attr['detail_id'];
                            if ($data['qty'] > 0) {
                                foreach ($attr['attr'] as $key => $value) {
                                    if (isset($key) && !in_array($key, $visibleAttrs)) {
                                        $visibleAttrs[$key] = $value;
                                    }
                                }
                                $data['visibleAttrs'] = $visibleAttrs;
                                $defaultAttr[] = $data;
                            }
                        }
                        if (!empty($defaultAttr))
                            $defaultAttributes = true;
                        $args['stock_qty'] = $args['qty'];
                        $args['manage_stock'] = (int)$args['stock_qty'] > 0;
                        $args['stock_status'] = (int)$args['stock_qty'] > 0 ? 'instock' : 'outofstock';
                    }
                }
            }
        }
        $PPrice = isset($args['price']) ? $args['price'] : 0;

        if ($args['priceSync'] == 0) {
            if ((!empty($args['regular_price']) || !empty($args['price'])) && ($OptionPrice) && !class_exists('bazara_ratio_calculator')) {
                // Prices
                $product->set_regular_price($args['price']);
                $RegularPrice = $args['regular_price'];
                $product->set_price($RegularPrice);

                $PPrice = $args['price'];
                if ($RegularPrice == 0 || $RegularPrice == -1)
                    $RegularPrice = '';

                if (($RegularPrice == '' || ($RegularPrice > 0 && $RegularPrice < $args['price'])) && $RegularPrice <> -1) {
                    $product->set_sale_price($RegularPrice);
                    $product->set_price($RegularPrice);
                    $PPrice = $RegularPrice;

                    $salePrice = true;
                } else {
                    $product->set_price(isset($args['price']) ? $args['price'] : 0);
                }

                if (class_exists('WooCommerce_Role_Based_Price_Product_Pricing'))
                    delete_product_wcrb_transient($product_id);
            }



            if (empty($args['vars']))
                update_schedule_sync($args['ProductId'], 'priceSync');
            // Taxes
            if (get_option('woocommerce_calc_taxes') === 'yes') {
                $product->set_tax_status($args['tax_status']);
                $product->set_tax_class($args['tax_class']);
            }
        }
        // global $permalink_manager_uris;

        ## --- SAVE PRODUCT --- ##
        $product_id = $product->save();

        if ($optionDescription && isset($args['description']) && $args['detailSync'] == 0) {
            $post_update = array(
                'ID'         => $product_id,
                'post_content' => $args['description']
            );

            wp_update_post($post_update);
        }
        if (class_exists("ChequeShipping") && !empty($args['Cheque_price'])) {
            update_post_meta($product_id, 'mahak_Cheque_price', $args['Cheque_price']);
        }
        if (class_exists('barcode_in_permallinks') && $args['new'] == 1 && !empty($barcode)) {

            wp_update_post(
                array(
                    'ID'        => $product_id,
                    'post_name' => $barcode
                )
            );
        }
        if ($SerialProduct) {


            update_post_meta($product_id, 'mahak_product_serials', $map);
        }

        if ($product_date && !empty($defaultAttr[0]['expireDate'])) {
            $dd = date('Y/m', $defaultAttr[0]['expireDate']);
            $result = substr($dd, 0, 4);
            if ($result < 2000) {
                if (!function_exists('jdate'))
                    require_once plugin_dir_path(__FILE__) . '../libs/jdf.php';
                $dd = jdate('Y/m', $defaultAttr[0]['expireDate']);
            }
            update_post_meta($product_id, 'mahak_closest_date', $dd);
        }
        if ($defaultAttributes) {

            update_post_meta($product_id, 'mahak_product_default_attributes', $defaultAttr);
        } else {
            delete_post_meta($product_id, 'mahak_product_default_attributes');
        }
        if ($args['priceSync'] == 0) {

            $final_price = array();
            if (class_exists('WooCommerce_Role_Based_Price_Product_Pricing')) {
                $enable = 0;
                $role_price_levels = !empty($args['prices_list']) ? json_decode($args['prices_list']) : [];
                $role_price_levels  = (json_decode(json_encode($role_price_levels), true));

                $personGroups = get_person_group();
                if ($role_price_levels) {
                    $allowed_prices = wc_rbp_allowed_price();
                    $OptionPrice = $options['chkPrice'] == 1 || $options['chkPrice'];
                    foreach ($personGroups as $pg) {
                        foreach ($allowed_prices as $price_type) {


                            $p = !empty($pg->SellPriceLevel) && $pg->SellPriceLevel > 0 ? $role_price_levels[$pg->SellPriceLevel]["Price{$pg->SellPriceLevel}"] : '';

                            if (empty($p))
                                $p = '';
                            else
                                $enable = 1;



                            if ($OptionPrice) {

                                if ($p > 0) {
                                    if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
                                        $p /= 10;
                                    } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
                                        $p *= 10;
                                    }
                                }
                            }
                            $final_price[str_replace(" ", "_", $pg->Name)][$price_type] = $p;
                        }
                    }
                }
                update_post_meta($product_id, '_role_based_price', $final_price);
                update_post_meta($product_id, '_enable_role_based_price', $enable);
            }
            //addon
            $wholeSalePrice = '';

            if (class_exists('WooCommerceWholeSalePrices')) {

                $personGroups = get_person_group(false);
                foreach ($personGroups as $pg) {
                    $role_price_levels = !empty($args['prices_list']) ? json_decode($args['prices_list']) : [];
                    $role_price_levels  = (json_decode(json_encode($role_price_levels), true));
                    $p = !empty($pg->SellPriceLevel) && $pg->SellPriceLevel > 0 ? $role_price_levels[$pg->SellPriceLevel]["Price{$pg->SellPriceLevel}"] : '';
                    if ($p > 0) {
                        if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
                            $p /= 10;
                        } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
                            $p *= 10;
                        }
                    }
                    $name = implode('_', explode(' ', $pg->Name));
                    update_post_meta($product_id, "{$name}_wholesale_price", $p);
                    update_post_meta($product_id, "_enable_role_{$name}_have_wholesale_priceased_price", 'true');
                }
            }

            if (class_exists("bazaraDynamicPrice")) {
                $FirstRangeQuantityLevel =  get_post_meta($product_id, "_level_first_dynamic_price", true);
                $firstRangeQuantityTo =  get_post_meta($product_id, "_max_quantity_first_dynamic_price", true);
                $SecondRangeQuantityLevel =  get_post_meta($product_id, "_level_second_dynamic_price", true);
                $secondRangeQuantityTo =  get_post_meta($product_id, "_max_quantity_second_dynamic_price", true);
                $ThirdRangeQuantityLevel =  get_post_meta($product_id, "_level_third_dynamic_price", true);
                $thirdRangeQuantityTo = get_post_meta($product_id, "_max_quantity_third_dynamic_price", true);

                $rules = array();
                $role_price_levels = !empty($args['prices_list']) ? json_decode($args['prices_list']) : [];
                $role_price_levels  = (json_decode(json_encode($role_price_levels), true));

                if ($FirstRangeQuantityLevel > 0)
                    if ($firstRangeQuantityTo > 0) {
                        $p = $role_price_levels[$FirstRangeQuantityLevel]["Price{$FirstRangeQuantityLevel}"];
                        if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
                            $p /= 10;
                        } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
                            $p *= 10;
                        }
                        $rules[$firstRangeQuantityTo] =   $p;
                    }

                if ($SecondRangeQuantityLevel > 0)
                    if ($secondRangeQuantityTo > 0) {
                        $p = $role_price_levels[$SecondRangeQuantityLevel]["Price{$SecondRangeQuantityLevel}"];
                        if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
                            $p /= 10;
                        } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
                            $p *= 10;
                        }
                        $rules[$secondRangeQuantityTo] =  $p;
                    }


                if ($ThirdRangeQuantityLevel > 0)
                    if ($thirdRangeQuantityTo > 0) {
                        $p = $role_price_levels[$ThirdRangeQuantityLevel]["Price{$ThirdRangeQuantityLevel}"];
                        if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
                            $p /= 10;
                        } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
                            $p *= 10;
                        }
                        $rules[$thirdRangeQuantityTo] =  $p;
                    }


                update_post_meta($product_id, '_tiered_price_minimum_qty', 1);
                update_post_meta($product_id, '_tiered_price_rules_type', 0);
                update_post_meta($product_id, '_fixed_price_rules', $rules);
            }
        }

        update_post_meta($product_id, 'mahak_product_unit_ratio', $args['unitRatio']);
        update_post_meta($product_id, 'mahak_id', $args['ProductId']);
        update_post_meta($product_id, 'mahak_product_store_id', $args['store_id']);
        update_post_meta($product_id, 'mahak_product_tax', ($args['TaxPercent'] == '-1' ? 0 : $args['TaxPercent']));
        update_post_meta($product_id, 'mahak_product_charge', ($args['ChargePercent'] == '-1' ? 0 : $args['ChargePercent']));
        if (class_exists('bazara_ratio_calculator') && empty($args['vars'])) {
            update_post_meta($product_id, '_wpg_item_type', 'gold');
            update_post_meta($product_id, '_wpg_item_count', ((int)$qty));
            // update_post_meta( $product_id, '_show_price_fields',0);
            // update_post_meta( $product_id, '_wpg_tax_free', 0);
            // update_post_meta( $product_id, '_wpg_production_fee_mode', 'fixed');
            // update_post_meta( $product_id, '_wpg_base_itme_production_fee',0);
            // update_post_meta( $product_id, '_wpg_production_fee_percent', 0);
            update_post_meta($product_id, '_wpg_interest_mode', 'default');
            // update_post_meta( $product_id, '_wpg_stone_price',0);
            // update_post_meta( $product_id, '_wpg_leather_price', 0);
            // update_post_meta( $product_id, '_wpg_price_field_leather', '');
            // update_post_meta( $product_id, '_wpg_jewel_price', 0);
            // update_post_meta( $product_id, '_wpg_production_fee_fixed', 0);
            update_post_meta($product_id, '_wpg_interest', 0);
            update_post_meta($product_id, '_wpg_price_field_stone', '');
        }
        for ($i = 0; $i < sizeof($args['ProductDetails']); $i++) {

            update_post_meta($product_id, 'mahak_product_detail_id', $args['ProductDetails'][$i]);
        }


        if ((!empty($args['vars']) && !$SerialProduct) || class_exists("sell_simple_with_date_variants")) {
            if ($variation_date_condition == 2) {

                usort($args['vars'], 'bazara_sortByGreatest');
            } else if ($variation_date_condition == 3) {
                (usort($args['vars'], 'bazara_sortByEarliest'));
            }

            $c = 0;
            if (!$SerialProduct) {


                foreach ($args['vars'] as $attr) {
                    if (empty($wholeSalePrice))
                        $wholeSalePrice = 0;
                    if (empty($final_price))
                        $final_price = 0;
                    create_variations($product_id, $attr, $final_price, $wholeSalePrice, $c, $args);
                    $c++;
                }
            }
        }
        if (!empty($args['vars']) && !$SerialProduct) {

            wp_remove_object_terms($product_id, 'simple', 'product_type');
            wp_set_object_terms($product_id, 'variable', 'product_type', true);
        } else if (empty($args['vars']) || $SerialProduct) {
            wp_remove_object_terms($product_id, 'variable', 'product_type');
            wp_set_object_terms($product_id, 'simple', 'product_type', true);
        }


        update_schedule_sync($args['ProductId'], 'detailSync');
        update_product_as_old($args['ProductId']);
        update_product_queue($args['ProductId'], 0);
        update_product_post_id($args['ProductCode'], $product_id);

        // if($OptionPicture){
        //     $bazara = new BazaraApi(true);
        //     $bazara->sync_pictures($args['ProductId'])['message'];
        // }
        wc_delete_product_transients($product_id);

        return array('success' => true, 'message' => '');
    } catch (\Exception $e) {
        return array('success' => false, 'message' => $e->getMessage());
    }
}

function map_serial($key, $value)
{
    return array('ProductDetailID' => $key, 'Qty' => $value);
}
function create_variations($product_id, $args, $final_price, $wholeSalePrice, $c, $productArgs)
{

    $variation = get_product_variation($args['prop_id'], $args['detail_id']);

    $variation_id = $args['prop_id'];
    $deleted = $args['deleted'];
    if (empty($variation_id)) return false;
    update_product_queue($args['detail_id'], 0, false, 'bazara_product_details', 'ProductDetailId');

    // Get the Variable product object (parent)
    $product = wc_get_product($product_id);

    if (empty($variation)) {
        if ($args['deleted'] == 1 || $args['deleted']) {

            update_schedule_sync($args['detail_id'], 'isSync', 1, 'bazara_product_details', 'ProductDetailId');
            update_product_queue($args['detail_id'], 1, false, 'bazara_product_details', 'ProductDetailId');
            return false;
        }

        $variation_post = array(
            'post_title'  => $product->get_name(),
            'post_name'   => 'product-' . $product_id . '-variation',
            'post_status' => 'publish',
            'post_parent' => $product_id,
            'post_type'   => 'product_variation',
            'guid'        => substr($product->get_permalink(), 0, 200)
        );


        // Creating the product variation
        $variation_id = wp_insert_post($variation_post);
        update_post_meta($variation_id, 'mahak_product_detail_id', $args['detail_id']);
        update_post_meta($variation_id, 'prop_id', $args['prop_id']);
        update_post_meta($variation_id, 'mahak_product_store_id', $args['store_id']);
        update_post_meta($variation_id, 'mahak_product_tax', $args['tax']);
        update_post_meta($variation_id, 'mahak_product_charge', $args['charge']);
        $variation = new WC_Product_Variation($variation_id);
    }
    if ($deleted == 1 || $deleted) {
        $id =  $variation->get_id();
        $a = wh_deleteProduct($id, true);
    }


    $options =  get_bazara_visitor_settings();

    $OptionQuantity = $options['chkQuantity'] == 1 || $options['chkQuantity'];
    $OptionPrice = $options['chkPrice'] == 1 || $options['chkPrice'];
    $SoftwareCurrency =  $options['selectCurrencySoftware'];
    $PluginCurrency =  $options['selectCurrencyPlugin'];
    $chkProductsRolePrice =  empty($options['chkProductsRolePrice']) ? 0 : $options['chkProductsRolePrice'];
    $chkExcludedProductsByCategory = $options['chkExcludedProductsByCategory'] == 1 || $options['chkExcludedProductsByCategory'];
    $ExcludedProductsByCategory =  empty($options['ExcludedProductsByCategory']) ? '' :  $options['ExcludedProductsByCategory'];
    $DontUpdateproduct = false;
    $variation->set_parent_id($product_id);


    if ($productArgs['detailSync'] == 0) {


        $variation->set_attributes($args['attr']);

        $variation->set_menu_order($c);



        if ($chkExcludedProductsByCategory && !empty($ExcludedProductsByCategory)) {


            $catTerms = get_the_terms($product_id, 'product_cat');
            if (!empty($catTerms)) {
                foreach ($catTerms  as $term) {

                    $product_cat_id = $term->term_id;
                    if ($product_cat_id == $ExcludedProductsByCategory) {
                        $DontUpdateproduct  = true;
                        break;
                    }
                }
            }
        }
        update_schedule_sync($productArgs['ProductId'], 'detailSync', 1, 'bazara_products', 'ProductID');
    }
    // Prices
    if ($productArgs['stockSync'] == 0) {
        if ($OptionQuantity && !$DontUpdateproduct) {
        $order_qty = get_order_item_qty($variation->get_id(), '_variation_id');
        $order_qty = is_numeric($order_qty) && $order_qty >= 0 ? $order_qty : 0;
        $bazara_qty = get_bazara_not_converted_qty($args['detail_id']);
        $bazara_qty = is_numeric($bazara_qty) && $bazara_qty >= 0 ? $bazara_qty : 0;
        $args['qty'] -= $order_qty + $bazara_qty;
        
        $args['manage_stock'] = $args['qty'] > 0;
        
        $qty = $args['qty'];
        if ($qty <= 0)
        {
            if (class_exists("bazara_manage_quantity")){
                $variation->set_stock_quantity(0);
                $variation->set_manage_stock(true);
                $variation->set_stock_status('instock');
            }else{
                $variation->set_manage_stock(false);
                product_out_of_Stock($variation->get_id());
            }
        } else {
        $variation->set_stock_quantity($args['qty']);
        $variation->set_manage_stock(true);
        $variation->set_stock_status('instock');
        }
        
        update_schedule_sync($productArgs['ProductId'], 'stockSync', 1, 'bazara_products', 'ProductID');
        }
        }
    if ($productArgs['priceSync'] == 0) {

        if ($OptionPrice && !class_exists('bazara_ratio_calculator')) {
            $price = $args['Price'];
            $RegularPrice = $args['Regular_price'];
            if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
                $price /= 10;
                $RegularPrice /= 10;
            } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
                $price *= 10;
                $RegularPrice *= 10;
            }
            $variation->set_regular_price($price);
            $variation->set_price($price);

            if ($RegularPrice == 0)
                $RegularPrice = '';


            if (($RegularPrice == '' || ($RegularPrice > 0 && $RegularPrice < $price)) && $RegularPrice <> -1)
                $variation->set_sale_price($RegularPrice);





            update_schedule_sync($productArgs['ProductId'], 'priceSync', 1, 'bazara_products', 'ProductID');
        }
    }

    $variation_id = $variation->save();
    if ($productArgs['priceSync'] == 0) {

        if (!empty($final_price)) {
            update_post_meta($variation_id, '_role_based_price', $final_price);
            update_post_meta($variation_id, '_enable_role_based_price', 1);
        }
        if (!empty($wholeSalePrice)) {
            update_post_meta($variation_id, 'wholesale_customer_wholesale_price', $wholeSalePrice);
            update_post_meta($variation_id, '_enable_role_wholesale_customer_have_wholesale_priceased_price', 'true');
        }
        if (class_exists('WooCommerceWholeSalePrices') && class_exists('wholeSalePermium')) {

            $personGroups = get_person_group(false);
            foreach ($personGroups as $pg) {

                $role_price_levels = !empty($productArgs['prices_list']) ? json_decode($productArgs['prices_list']) : [];
                $role_price_levels  = (json_decode(json_encode($role_price_levels), true));
                $p = !empty($pg->SellPriceLevel) && $pg->SellPriceLevel > 0 ? $role_price_levels[$pg->SellPriceLevel]["Price{$pg->SellPriceLevel}"] : '';
                if ($p > 0) {
                    if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
                        $p /= 10;
                    } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
                        $p *= 10;
                    }
                }
                $name = str_replace(" ", "_", $pg->Name);
                update_post_meta($variation_id, "{$name}_wholesale_price", $p);
                update_post_meta($variation_id, "_enable_role_{$name}_have_wholesale_priceased_price", 'true');
            }
        }
        if (class_exists("ChequeShipping") && !empty($args['Cheque_price'])) {
            update_post_meta($variation_id, 'mahak_Cheque_price', $args['Cheque_price']);
        }
        if (class_exists('bazara_ratio_calculator')) {
            update_post_meta($variation_id, '_weight', number_format($args['qty2'], 2));
            update_post_meta($variation_id, '_wpg_item_type', 'gold');
            update_post_meta($variation_id, '_wpg_item_count', ((int)$qty));
            update_post_meta($variation_id, '_show_price_fields', 0);
            update_post_meta($variation_id, '_wpg_tax_free', 0);
            update_post_meta($variation_id, '_wpg_production_fee_mode', 'fixed');
            update_post_meta($variation_id, '_wpg_base_itme_production_fee', 0);
            update_post_meta($variation_id, '_wpg_production_fee_percent', 0);
            update_post_meta($variation_id, '_wpg_interest_mode', 'default');
            update_post_meta($variation_id, '_wpg_stone_price', 0);
            update_post_meta($variation_id, '_wpg_leather_price', 0);
            update_post_meta($variation_id, '_wpg_price_field_leather', '');
            update_post_meta($variation_id, '_wpg_jewel_price', 0);
            update_post_meta($variation_id, '_wpg_production_fee_fixed', 0);
            update_post_meta($variation_id, '_wpg_interest', 0);
            update_post_meta($variation_id, '_wpg_price_field_stone', '');
        }

        if (class_exists("bazaraDynamicPrice")) {



            $FirstRangeQuantityLevel =  get_post_meta($variation_id, "_level_first_dynamic_price", true);
            $firstRangeQuantityTo =  get_post_meta($variation_id, "_max_quantity_first_dynamic_price", true);
            $SecondRangeQuantityLevel =  get_post_meta($variation_id, "_level_second_dynamic_price", true);
            $secondRangeQuantityTo =  get_post_meta($variation_id, "_max_quantity_second_dynamic_price", true);
            $ThirdRangeQuantityLevel =  get_post_meta($variation_id, "_level_third_dynamic_price", true);
            $thirdRangeQuantityTo = get_post_meta($variation_id, "_max_quantity_third_dynamic_price", true);

            $rules = array();
            $role_price_levels = !empty($productArgs['prices_list']) ? json_decode($productArgs['prices_list']) : [];
            $role_price_levels  = (json_decode(json_encode($role_price_levels), true));

            if ($FirstRangeQuantityLevel > 0)
                if ($firstRangeQuantityTo > 0) {
                    $p = $role_price_levels[$FirstRangeQuantityLevel]["Price{$FirstRangeQuantityLevel}"];
                    if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
                        $p /= 10;
                    } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
                        $p *= 10;
                    }
                    $rules[$firstRangeQuantityTo] =   $p;
                }

            if ($SecondRangeQuantityLevel > 0)
                if ($secondRangeQuantityTo > 0) {
                    $p = $role_price_levels[$SecondRangeQuantityLevel]["Price{$SecondRangeQuantityLevel}"];
                    if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
                        $p /= 10;
                    } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
                        $p *= 10;
                    }
                    $rules[$secondRangeQuantityTo] =  $p;
                }


            if ($ThirdRangeQuantityLevel > 0)
                if ($thirdRangeQuantityTo > 0) {
                    $p = $role_price_levels[$ThirdRangeQuantityLevel]["Price{$ThirdRangeQuantityLevel}"];
                    if ($SoftwareCurrency == 'rial' && $PluginCurrency == 'toman') {
                        $p /= 10;
                    } else if ($SoftwareCurrency == 'toman' && $PluginCurrency == 'rial') {
                        $p *= 10;
                    }
                    $rules[$thirdRangeQuantityTo] =  $p;
                }

            update_post_meta($variation_id, '_tiered_price_minimum_qty', 1);
            update_post_meta($variation_id, '_tiered_price_rules_type', 0);
            update_post_meta($variation_id, '_fixed_price_rules', $rules);
        }
    }

    update_schedule_sync($args['detail_id'], 'isSync', 1, 'bazara_product_details', 'ProductDetailId');

    update_product_queue($args['detail_id'], 1, false, 'bazara_product_details', 'ProductDetailId');
    if (class_exists('WooCommerce_Role_Based_Price_Product_Pricing'))
        delete_product_wcrb_transient($variation_id);

    wc_delete_product_transients($variation_id); // Clear/refresh the variation cache



}
/**
 * Method to delete Woo Product
 *
 * @param int $id the product ID.
 * @param bool $force true to permanently delete product, false to move to trash.
 * @return \WP_Error|boolean
 */
function wh_deleteProduct($id, $force = FALSE)
{
    $product = wc_get_product($id);

    if (empty($product))
        return new WP_Error(999, sprintf(__('No %s is associated with #%d', 'woocommerce'), 'product', $id));

    // If we're forcing, then delete permanently.
    if ($force) {
        if ($product->is_type('variable')) {
            foreach ($product->get_children() as $child_id) {
                $child = wc_get_product($child_id);
                $child->delete(true);
            }
        } elseif ($product->is_type('grouped')) {
            foreach ($product->get_children() as $child_id) {
                $child = wc_get_product($child_id);
                $child->set_parent_id(0);
                $child->save();
            }
        }

        $product->delete(true);
        $result = $product->get_id() > 0 ? false : true;
    } else {
        $product->delete();
        $result = 'trash' === $product->get_status();
    }

    if (!$result) {
        return new WP_Error(999, sprintf(__('This %s cannot be deleted', 'woocommerce'), 'product'));
    }

    // Delete parent product transients.
    if ($parent_id = wp_get_post_parent_id($id)) {
        wc_delete_product_transients($parent_id);
    }
    return true;
}
function delete_product_wcrb_transient($productID)
{
    global $wpdb;
    $wpdb->query("Delete FROM {$wpdb->prefix}options WHERE option_name LIKE '\_transient%\__wcrbp\__p_{$productID}_%'");
}
//todo : change it to private
function product_out_of_Stock($product_id)
{
$out_of_stock_status = 'outofstock';

// 1. Updating the stock quantity
$stock_updated = update_post_meta($product_id, '_stock', 0);
if ($stock_updated === false) {
error_log("Failed to update _stock meta for product ID $product_id");
}

// 2. Updating the stock status
$status_updated = update_post_meta($product_id, '_stock_status', wc_clean($out_of_stock_status));
if ($status_updated === false) {
error_log("Failed to update _stock_status meta for product ID $product_id");
}

// 3. Updating post term relationship
$terms_updated = wp_set_post_terms($product_id, 'outofstock', 'product_visibility', true);
if (is_wp_error($terms_updated)) {
error_log("Failed to update product_visibility term for product ID $product_id: " . $terms_updated->get_error_message());
}

// 4. Clear/refresh the variation cache
wc_delete_product_transients($product_id);
}

// Utility function that returns the correct product object instance
function wc_get_product_object_type($type, $sku, $publishStatus = 'draft')
{
    // Get an instance of the WC_Product object (depending on his type)
    if (isset($type) && $type === 'variable') {
        $product = new WC_Product_Variable();
        //        var_dump($product);exit();
    } elseif (isset($type) && $type === 'grouped') {
        $product = new WC_Product_Grouped();
    } elseif (isset($type) && $type === 'external') {
        $product = new WC_Product_External();
    } else {
        $product = new WC_Product(); // "simple" By default
    }
    $product->set_sku($sku);
    $product->set_status($publishStatus);

    $product->save();

    if (! is_a($product, 'WC_Product'))
        return false;
    else
        return $product;
}
function bazara_get_product_by_sku($sku)
{
    $posts = get_posts(array(
        'posts_per_page'   => -1,
        'post_type'        => array('product', 'product_variation'),
        'meta_key'   => '_sku',
        'meta_value' => $sku,
        'post_status' => array('publish', 'private', 'draft')
    ));
    $product = null;
    if (!empty($posts))
        $product = wc_get_product($posts[0]->ID);
    return $product;
}
function get_product_by_mahakID($mahak_product_id)
{
    $posts = get_posts(array(
        'posts_per_page'   => -1,
        'post_type'        => 'product',
        'meta_key'   => 'mahak_id',
        'meta_value' => $mahak_product_id,
        'post_status' => 'any'
    ));
    $product = null;
    if (!empty($posts))
        $product = wc_get_product($posts[0]->ID);
    return $product;
}
function get_product_variation($variation_id, $detailID)
{
    global $wpdb;
    
    // Validate input
    if (empty($detailID)) {
        return null;
    }
    
    // Prepare the detailID for SQL query
    $detailID = absint($detailID);
    
    // Direct $wpdb query for better performance
    // Join posts table with postmeta to find variations with matching mahak_product_detail_id
    if (!empty($variation_id)) {
        // Use JOIN for better performance when variation_id is provided
        $variation_id = absint($variation_id);
        $query = $wpdb->prepare(
            "SELECT DISTINCT p.ID, p.post_parent, p.post_date
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
             WHERE p.post_type = 'product_variation'
             AND pm1.meta_key = 'mahak_product_detail_id'
             AND pm1.meta_value = %s
             AND pm2.meta_key = 'prop_id'
             AND pm2.meta_value = %s
             AND p.post_status != 'trash'
             ORDER BY p.post_date DESC",
            $detailID,
            $variation_id
        );
    } else {
        // Simpler query when only detailID is provided
        $query = $wpdb->prepare(
            "SELECT p.ID, p.post_parent, p.post_date
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'product_variation'
             AND pm.meta_key = 'mahak_product_detail_id'
             AND pm.meta_value = %s
             AND p.post_status != 'trash'
             ORDER BY p.post_date DESC",
            $detailID
        );
    }
    
    $results = $wpdb->get_results($query);

    if(empty($results) && !empty($variation_id)){
        $query = $wpdb->prepare(
            "SELECT p.ID, p.post_parent, p.post_date
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'product_variation'
             AND pm.meta_key = 'mahak_product_detail_id'
             AND pm.meta_value = %s
             AND p.post_status != 'trash'
             ORDER BY p.post_date DESC",
            $detailID
        );
    }
    
    $results = $wpdb->get_results($query);
    
    // Handle no results
    if (empty($results)) {
        return null;
    }
    
    // Handle duplicates: check for images and keep the best one
    if (count($results) > 1) {
        $variations_with_images = array();
        $variations_without_images = array();
        
        // Check each variation for images
        foreach ($results as $row) {
            $variation_obj = wc_get_product($row->ID);
            
            if (!$variation_obj || !$variation_obj->is_type('variation')) {
                continue;
            }
            
            // Check if variation has an image
            $image_id = $variation_obj->get_image_id();
            $has_image = !empty($image_id) && wp_attachment_is_image($image_id);
            
            if ($has_image) {
                $variations_with_images[] = array(
                    'ID' => $row->ID,
                    'post_date' => $row->post_date,
                    'product' => $variation_obj
                );
            } else {
                $variations_without_images[] = array(
                    'ID' => $row->ID,
                    'post_date' => $row->post_date,
                    'product' => $variation_obj
                );
            }
        }
        
        // Determine which variation to keep
        $keep_variation = null;
        $duplicate_ids_to_delete = array();
        
        // Safety check: if no valid variations found, return null
        if (empty($variations_with_images) && empty($variations_without_images)) {
            error_log(sprintf(
                'Bazara: No valid variations found for detailID %d. All variations failed validation.',
                $detailID
            ));
            return null;
        }
        
        if (!empty($variations_with_images)) {
            // Keep the first variation with image (already sorted by date DESC, so newest first)
            $keep_variation = $variations_with_images[0];
            // Collect all other IDs for deletion (including other variations with images)
            foreach ($variations_with_images as $idx => $var) {
                if ($idx > 0) {
                    $duplicate_ids_to_delete[] = $var['ID'];
                }
            }
            // Also delete all variations without images
            foreach ($variations_without_images as $var) {
                $duplicate_ids_to_delete[] = $var['ID'];
            }
        } else {
            // No images found, keep the newest one (first in results, already sorted DESC)
            if (!empty($variations_without_images)) {
                $keep_variation = $variations_without_images[0];
                // Delete all others
                foreach ($variations_without_images as $idx => $var) {
                    if ($idx > 0) {
                        $duplicate_ids_to_delete[] = $var['ID'];
                    }
                }
            } else {
                // Fallback: should not happen, but keep the first result
                $keep_variation = array(
                    'ID' => $results[0]->ID,
                    'post_date' => $results[0]->post_date,
                    'product' => wc_get_product($results[0]->ID)
                );
                foreach ($results as $idx => $row) {
                    if ($idx > 0) {
                        $duplicate_ids_to_delete[] = $row->ID;
                    }
                }
            }
        }
        
        // Log the duplicate detection and cleanup
        $all_duplicate_ids = array_map(function($row) {
            return $row->ID;
        }, $results);
        
        error_log(sprintf(
            'Bazara: Duplicate product variations detected for detailID %d. Found %d variations with IDs: %s. Keeping ID: %d. Deleting IDs: %s',
            $detailID,
            count($results),
            implode(', ', $all_duplicate_ids),
            $keep_variation['ID'],
            implode(', ', $duplicate_ids_to_delete)
        ));
        
        // Delete duplicate variations
        foreach ($duplicate_ids_to_delete as $duplicate_id) {
            $duplicate_product = wc_get_product($duplicate_id);
            if ($duplicate_product) {
                // Use force delete to permanently remove
                $duplicate_product->delete(true);
                clean_post_cache($duplicate_id);
                error_log(sprintf(
                    'Bazara: Deleted duplicate variation ID %d for detailID %d',
                    $duplicate_id,
                    $detailID
                ));
            }
        }
        
        // Use the kept variation
        $variation_post_id = $keep_variation['ID'];
        $product = $keep_variation['product'];
    } else {
        // Single result, no duplicates
        $variation_post_id = $results[0]->ID;
        $product = null; // Will be loaded below
    }
    
    // Clear cache to avoid stale results
    clean_post_cache($variation_post_id);
    
    // Also clear WooCommerce product cache
    wc_delete_product_transients($variation_post_id);
    
    // Get the product object if not already loaded (in duplicate case, it's already loaded)
    if (!isset($product) || is_null($product)) {
        $product = wc_get_product($variation_post_id);
    }
    
    // Additional safety check: verify the product is valid and is a variation
    if (!$product || !$product->is_type('variation')) {
        error_log(sprintf(
            'Bazara: Invalid variation product returned for detailID %d. Post ID: %d',
            $detailID,
            $variation_post_id
        ));
        return null;
    }
    
    return $product;
}
/**
 * Cleanup helper: Remove WooCommerce variations for product details marked as deleted in ERP table.
 *
 * Behavior:
 * - Reads ProductDetailId values from {$wpdb->prefix}bazara_product_details where Deleted = 1.
 * - For each ProductDetailId, finds all product_variation posts that have meta 'mahak_product_detail_id' equal to it.
 * - Force-deletes those variations and clears caches.
 *
 * @param int $limit Maximum number of ProductDetailId records to process per call.
 * @return array Summary with processed count and deleted IDs for observability.
 */
function bz_cleanup_variations_marked_deleted($limit = 200)
{
	global $wpdb;

	$limit = absint($limit);
	if ($limit <= 0) {
		$limit = 200;
	}

	$table = $wpdb->prefix . 'bazara_product_details';

	// Fetch distinct ProductDetailId where marked deleted
	$productDetailIds = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT ProductDetailId
			 FROM {$table}
			 WHERE Deleted = %d
			 LIMIT %d",
			1,
			$limit
		)
	);

	if (empty($productDetailIds)) {
		return array(
			'processed' => 0,
			'deleted_variations' => array(),
			'message' => 'No deleted ProductDetailId records found.'
		);
	}

	$deletedVariationIds = array();

	// Prepare and reuse the SQL to find variations by ProductDetailId
	foreach ($productDetailIds as $detailId) {
		$detailId = absint($detailId);
		if ($detailId <= 0) {
			continue;
		}

		$variationIds = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				 WHERE p.post_type = 'product_variation'
				   AND p.post_status != 'trash'
				   AND pm.meta_key = 'mahak_product_detail_id'
				   AND pm.meta_value = %s",
				$detailId
			)
		);

		if (empty($variationIds)) {
			continue;
		}

		error_log(sprintf(
			'Bazara: Deleting %d duplicate/obsolete variations for deleted ProductDetailId %d. Variation IDs: %s',
			count($variationIds),
			$detailId,
			implode(', ', $variationIds)
		));

		foreach ($variationIds as $varId) {
			$varId = absint($varId);
			if ($varId <= 0) {
				continue;
			}

			$variation = wc_get_product($varId);
			if ($variation && $variation->is_type('variation')) {
				$variation->delete(true); // force delete
				clean_post_cache($varId);
				wc_delete_product_transients($varId);
				$deletedVariationIds[] = $varId;
				error_log(sprintf('Bazara: Variation ID %d deleted for ProductDetailId %d', $varId, $detailId));
			}
		}
	}

	return array(
		'processed' => count($productDetailIds),
		'deleted_variations' => $deletedVariationIds,
		'message' => 'Cleanup complete.'
	);
}

/**
 * Cleanup duplicate WooCommerce variations by mahak_product_detail_id.
 *
 * اگر product_detail_id پاس داده شود، فقط همان کالا پردازش می‌شود
 * مناسب برای اجرا در cron job سینک کالا
 *
 * @param string|int|null $product_detail_id
 * @param int            $limit
 * @return array
 */
function bz_cleanup_duplicate_variations_from_report_v2( $product_detail_id = null, $limit = 100 ) {
    global $wpdb;

    $limit = absint( $limit );
    if ( $limit <= 0 ) {
        $limit = 100;
    }

    // فیلتر بر اساس product_detail_id (در صورت وجود)
    $whereDetail = '';
    $params      = [];

    if ( ! empty( $product_detail_id ) ) {
        $whereDetail = ' AND pm.meta_value = %s ';
        $params[]    = (string) $product_detail_id;
    }

    $params[] = $limit;

    // کوئری گروه‌های وریشن تکراری
    $sql = $wpdb->prepare(
        "
        SELECT parent_id, mahak_product_detail_id, cnt, variation_ids
        FROM (
            SELECT
                p.post_parent AS parent_id,
                pm.meta_value AS mahak_product_detail_id,
                COUNT(DISTINCT p.ID) AS cnt,
                GROUP_CONCAT(DISTINCT p.ID ORDER BY p.ID ASC) AS variation_ids
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm
                ON pm.post_id = p.ID
               AND pm.meta_key = 'mahak_product_detail_id'
            WHERE p.post_type = 'product_variation'
              AND p.post_status != 'trash'
              {$whereDetail}
            GROUP BY p.post_parent, pm.meta_value
            HAVING COUNT(DISTINCT p.ID) > 1
        ) duplicates
        LIMIT %d
        ",
        $params
    );

    $duplicateGroups = $wpdb->get_results( $sql );

    if ( empty( $duplicateGroups ) ) {
        return [
            'processed_groups'   => 0,
            'deleted_variations' => [],
            'message'            => 'هیچ وریشن تکراری پیدا نشد.'
        ];
    }

    $processedGroups     = 0;
    $deletedVariationIds = [];

    foreach ( $duplicateGroups as $group ) {
        $processedGroups++;

        // استخراج ID ها
        $variationIds = array_filter(
            array_unique( array_map( 'absint', explode( ',', $group->variation_ids ) ) )
        );

        if ( count( $variationIds ) < 2 ) {
            continue;
        }

        $variations = [];

        foreach ( $variationIds as $vid ) {
            $post = $wpdb->get_row( $wpdb->prepare(
                "SELECT ID, post_date
                 FROM {$wpdb->posts}
                 WHERE ID = %d
                   AND post_type = 'product_variation'
                   AND post_status != 'trash'",
                $vid
            ) );

            if ( ! $post ) {
                continue;
            }

            $imageId = $wpdb->get_var( $wpdb->prepare(
                "SELECT meta_value
                 FROM {$wpdb->postmeta}
                 WHERE post_id = %d
                   AND meta_key = '_thumbnail_id'",
                $vid
            ) );

            $variations[] = [
                'ID'        => $vid,
                'has_image' => ! empty( $imageId ) && wp_attachment_is_image( $imageId ),
                'post_date' => $post->post_date ? strtotime( $post->post_date ) : 0,
            ];
        }

        if ( count( $variations ) < 2 ) {
            continue;
        }

        // انتخاب وریشن برای نگه داشتن
        $withImage    = array_filter( $variations, fn( $v ) => $v['has_image'] );
        $withoutImage = array_filter( $variations, fn( $v ) => ! $v['has_image'] );

        if ( ! empty( $withImage ) ) {
            usort( $withImage, fn( $a, $b ) => $b['post_date'] <=> $a['post_date'] );
            $keep = $withImage[0];
        } else {
            usort( $withoutImage, fn( $a, $b ) => $b['post_date'] <=> $a['post_date'] );
            $keep = $withoutImage[0];
        }

        // تعیین موارد حذف
        $idsToDelete = [];

        foreach ( $variations as $var ) {
            if ( $var['ID'] === $keep['ID'] ) {
                continue;
            }

            if ( $keep['has_image'] ) {
                if ( ! $var['has_image'] || $var['post_date'] < $keep['post_date'] ) {
                    $idsToDelete[] = $var['ID'];
                }
            } else {
                $idsToDelete[] = $var['ID'];
            }
        }

        // حذف فیزیکی
        foreach ( $idsToDelete as $deleteId ) {
            $deleteId = absint( $deleteId );
            if ( $deleteId <= 0 ) {
                continue;
            }

            // حذف متا
            $wpdb->delete( $wpdb->postmeta, [ 'post_id' => $deleteId ] );

            // حذف پست
            $deleted = $wpdb->delete(
                $wpdb->posts,
                [ 'ID' => $deleteId, 'post_type' => 'product_variation' ]
            );

            if ( $deleted ) {
                $deletedVariationIds[] = $deleteId;

                clean_post_cache( $deleteId );
                wp_cache_delete( $deleteId, 'posts' );
                wp_cache_delete( $deleteId, 'post_meta' );
                wc_delete_product_transients( $deleteId );
            }
        }

        // پاک‌سازی کش محصول والد
        if ( ! empty( $group->parent_id ) ) {
            clean_post_cache( $group->parent_id );
            wc_delete_product_transients( $group->parent_id );
        }
    }

    return [
        'processed_groups'   => $processedGroups,
        'deleted_variations' => $deletedVariationIds,
        'message'            => 'پاکسازی وریشن‌های تکراری با موفقیت انجام شد.'
    ];
}


/**
 * Helper to remove duplicate WooCommerce variations detected by the duplicate-variation report.
 *
 * @param int $limit Maximum duplicate groups to process per execution.
 * @return array Summary with processed groups and deleted variation IDs.
 */
function bz_cleanup_duplicate_variations_from_report( $limit = 100 ) {
    global $wpdb;

    $limit = absint( $limit );
    if ( $limit <= 0 ) {
        $limit = 100;
    }

    // پیدا کردن گروه‌های تکراری
    $sql = $wpdb->prepare(
        "SELECT parent_id, mahak_product_detail_id, cnt, variation_ids
         FROM (
             SELECT
                 p.post_parent AS parent_id,
                 pm.meta_value AS mahak_product_detail_id,
                 COUNT(DISTINCT p.ID) AS cnt,
                 GROUP_CONCAT(DISTINCT p.ID ORDER BY p.ID ASC) AS variation_ids
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm
                 ON pm.post_id = p.ID
                AND pm.meta_key = 'mahak_product_detail_id'
             WHERE p.post_type = 'product_variation'
               AND p.post_status != 'trash'
             GROUP BY p.post_parent, pm.meta_value
             HAVING COUNT(DISTINCT p.ID) > 1
         ) duplicates
         LIMIT %d",
        $limit
    );

    $duplicateGroups = $wpdb->get_results( $sql );

    if ( empty( $duplicateGroups ) ) {
        return [
            'processed_groups'   => 0,
            'deleted_variations' => [],
            'message'            => 'هیچ گروه وریشن تکراری پیدا نشد.'
        ];
    }

    $deletedVariationIds = [];
    $processedGroups     = 0;

    foreach ( $duplicateGroups as $group ) {
        $processedGroups++;

        // تبدیل variation_ids به آرایه عددی سالم
        $variationIds = array_filter(
            array_unique( array_map( 'absint', explode( ',', $group->variation_ids ) ) ),
            function( $id ) use ( $wpdb ) {
                if ( $id <= 0 ) {
                    return false;
                }
                // چک مستقیم از دیتابیس
                $exists = $wpdb->get_var( $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE ID = %d AND post_type = 'product_variation' AND post_status != 'trash'",
                    $id
                ) );
                return $exists;
            }
        );

        if ( empty( $variationIds ) ) {
            error_log( "بازارا: هیچ وریشن معتبری در گروه ProductDetailId: {$group->mahak_product_detail_id} (parent {$group->parent_id}) پیدا نشد." );
            continue;
        }

        $variations = [];

        foreach ( $variationIds as $vid ) {
            // مستقیم از دیتابیس چک می‌کنیم
            $post = $wpdb->get_row( $wpdb->prepare(
                "SELECT ID, post_date FROM {$wpdb->posts} WHERE ID = %d AND post_type = 'product_variation' AND post_status != 'trash'",
                $vid
            ) );
            if ( ! $post ) {
                continue;
            }

            // گرفتن تصویر از متادیتا
            $imageId = $wpdb->get_var( $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_thumbnail_id'",
                $vid
            ) );
            $hasImage = ! empty( $imageId ) && wp_attachment_is_image( $imageId );
            $timestamp = $post->post_date ? strtotime( $post->post_date ) : 0;

            $variations[] = [
                'ID'         => $vid,
                'has_image'  => $hasImage,
                'post_date'  => $timestamp,
            ];
        }

        if ( empty( $variations ) ) {
            error_log( "بازارا: گروه تکراری رد شد (هیچ وریشن معتبری نداشت) - ProductDetailId: {$group->mahak_product_detail_id} (parent {$group->parent_id})" );
            continue;
        }

        // جدا کردن وریشن‌ها
        $withImage    = array_filter( $variations, fn($v) => $v['has_image'] );
        $withoutImage = array_filter( $variations, fn($v) => ! $v['has_image'] );

        // انتخاب وریشن برای نگه داشتن
        if ( ! empty( $withImage ) ) {
            usort( $withImage, fn($a, $b) => $b['post_date'] <=> $a['post_date'] );
            $keep = $withImage[0];
        } elseif ( ! empty( $withoutImage ) ) {
            usort( $withoutImage, fn($a, $b) => $b['post_date'] <=> $a['post_date'] );
            $keep = $withoutImage[0];
        } else {
            continue;
        }

        $idsToDelete = [];
        foreach ( $variations as $var ) {
            if ( $var['ID'] === $keep['ID'] ) {
                continue;
            }
            if ( $keep['has_image'] ) {
                if ( ! $var['has_image'] || $var['post_date'] < $keep['post_date'] ) {
                    $idsToDelete[] = $var['ID'];
                }
            } else {
                $idsToDelete[] = $var['ID'];
            }
        }

        if ( ! empty( $idsToDelete ) ) {
            error_log( "بازارا: پاکسازی تکراری‌ها - ProductDetailId: {$group->mahak_product_detail_id} (parent {$group->parent_id}) | نگه‌داشته شده: {$keep['ID']} | حذف: " . implode( ', ', $idsToDelete ) );
        }

        // حذف کاملاً خام و امن با وردپرس
        foreach ( $idsToDelete as $deleteId ) {
            $deleteId = absint( $deleteId );
            if ( $deleteId <= 0 ) {
                continue;
            }

            // چک نهایی وجود پست
            $post_exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE ID = %d AND post_type = 'product_variation' AND post_status != 'trash'",
                $deleteId
            ) );
            if ( ! $post_exists ) {
                error_log( "بازارا: پست $deleteId وجود ندارد یا قبلاً حذف شده." );
                continue;
            }

            // حذف متادیتاها
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->postmeta} WHERE post_id = %d",
                $deleteId
            ) );

            // حذف پست
            $deleted = $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->posts} WHERE ID = %d AND post_type = 'product_variation'",
                $deleteId
            ) );

            if ( $deleted ) {
                $deletedVariationIds[] = $deleteId;
                // پاک کردن کش
                clean_post_cache( $deleteId );
                wp_cache_delete( $deleteId, 'posts' );
                wp_cache_delete( $deleteId, 'post_meta' );
                wc_delete_product_transients( $deleteId );
            } else {
                error_log( "بازارا: حذف پست $deleteId ناموفق بود - احتمالاً مشکل دیتابیس یا مجوزها." );
            }
        }

        // پاک کردن کش والد
        if ( ! empty( $group->parent_id ) ) {
            clean_post_cache( $group->parent_id );
            wp_cache_delete( $group->parent_id, 'posts' );
            wc_delete_product_transients( $group->parent_id );
        }
    }

    return [
        'processed_groups'   => $processedGroups,
        'deleted_variations' => $deletedVariationIds,
        'message'            => 'پاکسازی وریشن‌های تکراری با موفقیت انجام شد.'
    ];
}
// Utility function that prepare product attributes before saving
function wc_prepare_product_attributes($attributes, $pid)
{
    global $woocommerce;

    $data = $array = $vals = array();
    $position = $lastPos = 0;
    $options =  get_bazara_visitor_settings();
    $KeepLastAttributes = toggle_to_boolean($options['chkDontRemoveAttributes']);

    if ($KeepLastAttributes) {

        $data = get_product_attributes_plugin($pid);

        foreach ($data as $taxonomy => $values) {
            $taxonomy_id = wc_attribute_taxonomy_id_by_name($values['name']); // Get taxonomy ID
            $a = wc_get_product_terms($pid, $values['name'], array('fields' => 'names'));
            if (empty($a))
                $a = array_shift(woocommerce_get_product_terms($pid, $values['name'], 'names'));

            $attribute = new WC_Product_Attribute();
            $attribute->set_id($taxonomy_id);
            $attribute->set_name($values['name']);
            $attribute->set_position($lastPos);
            $attribute->set_options($a);
            $attribute->set_visible($values['is_visible']);
            $attribute->set_variation($values['is_variation']);
            $data[$taxonomy] = $attribute; // Set in an array

            $lastPos++; // Increase position

        }
    }

    foreach ($attributes as $attr) {
        foreach ($attr as $taxonomy => $values) {

            $taxonomy = 'pa_' . $taxonomy;
            if ($KeepLastAttributes)
                unset($data[$taxonomy]);
            if (! taxonomy_exists($taxonomy)) {

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


            $taxonomy_id = wc_attribute_taxonomy_id_by_name($taxonomy); // Get taxonomy ID


            $attribute->set_id($taxonomy_id);
            $attribute->set_name($taxonomy);
            $attribute->set_options($values['term_names']);
            $attribute->set_position($position);
            $attribute->set_visible($values['is_visible']);
            $attribute->set_variation($values['for_variation']);

            $data[$taxonomy] = $attribute; // Set in an array

            $position++; // Increase position

        }
    }


    return $data;
}
/**
 * Get a list of all the product attributes for a post type.
 * These require a bit more digging into the values.
 */
function get_product_attributes_plugin($product)
{

    global $wpdb;

    $results = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT pm.meta_value
            FROM {$wpdb->postmeta} AS pm
            LEFT JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id
            WHERE p.ID = %s
            AND p.post_status IN ( 'publish', 'pending', 'private', 'draft' )
            AND pm.meta_key = '_product_attributes'",
        $product
    ));

    // Go through each result, and look at the attribute keys within them.
    $result = array();

    if (!empty($results)) {
        foreach ($results as $_product_attributes) {
            $attributes = maybe_unserialize(maybe_unserialize($_product_attributes));
            if (!empty($attributes) && is_array($attributes)) {
                foreach ($attributes as $key => $attribute) {

                    if (!$key) {
                        continue;
                    }
                    if (!strstr($key, 'pa_')) {
                        if (empty($attribute['name'])) {
                            continue;
                        }
                        $key = $attribute['name'];
                    }

                    $result[$key] = $attribute;
                }
            }
        }
    }
    //var_dump($result);exit();
    // sort($result);

    return $result;
}

/**
 * Create a product variation for a defined variable product ID.
 *
 * @since 3.0.0
 * @param int   $product_id | Post ID of the product parent variable product.
 * @param array $variation_data | The data to insert in the product.
 */


function get_WC_Product_Variation($variation_id)
{
    return new WC_Product_Variation($variation_id);
}

function prefix_get_available_shipping_methods()
{

    if (! class_exists('WC_Shipping_Zones')) {
        return array();
    }

    $zones = WC_Shipping_Zones::get_zones();

    if (! is_array($zones)) {
        return array();
    }

    $shipping_methods = array_column($zones, 'shipping_methods');

    $flatten = array_merge(...$shipping_methods);

    $normalized_shipping_methods = array();

    foreach ($flatten as $key => $class) {
        $normalized_shipping_methods[$class->id] = $class->method_title;
    }
    $normalized_shipping_methods["Tapin_Pishtaz_Method"] = "پیشتاز(Tapin)";
    $normalized_shipping_methods["local_pickup"] = "پیشتاز(پلاگین حمل و نقل)";
    $normalized_shipping_methods["legacy_advanced_shipping"] = "حمل و نقل پیشرفته(legacy)";
    $normalized_shipping_methods["advanced_shipping"] = "حمل و نقل پیشرفته";
    $normalized_shipping_methods["flat_rate"] = "پست معمولی";

    return $normalized_shipping_methods;
}
function get_shipping_method()
{

    if (! class_exists('WC_Shipping_Zones')) {
        return array();
    }
    $shippings = [];
    $methods = prefix_get_available_shipping_methods();
    foreach ($methods as $key => $value) {
        $shippings[] = "shippingPerson_" . $key;
        $shippings[] = "bazara_new_ship_" . $key . '_toggle';
    }
    return $shippings;
}
function get_bank_methods()
{
    $methods = [];
    $gateways        = WC()->payment_gateways->payment_gateways();
    foreach ($gateways as $gid => $gateway) {
        if (isset($gateway->enabled) && 'yes' === $gateway->enabled)
            //$methods[] = "selectBank_" . $gid;
            $methods[] = "bazara_new_bank_" . $gid . '_toggle';
    }
    $methods[] = "bazara_new_bank_Digikala_toggle";

    return $methods;
}
add_action('admin_post_bazara_repair_database', 'bazara_repair_database');
add_action('admin_post_nopriv_bazara_repair_database', 'bazara_repair_database');
function bazara_repair_database()
{
    $bazara = new BazaraApi(true);
    $bazara->repair_database();
    echo "بازسازی جداول با موفقیت انجام شد";
}
add_action('admin_post_clear_tables_queue', 'clear_tables_queue');
add_action('admin_post_nopriv_clear_tables_queue', 'clear_tables_queue');

function bazara_run_product_synchronize()
{
    $bazara_options = bazara_get_options();

    if (isset($bazara_options['CreditDay']) && $bazara_options['CreditDay'] < 0) {
        bazara_save_log(date_i18n('Y-m-j'), 'اتمام اعتبار', 'expired', 'error');
        return false;
    }

    bazara_save_log(date_i18n('Y-m-j'), 'شروع همگام سازی', 'start', 'success');

    $bazara = new BazaraApi(true);
    $visitorSetting = get_bazara_visitor_settings();
    $syncPicture = !empty($visitorSetting['chkPicture']) && $visitorSetting['chkPicture'];
    $syncProduct = !empty($visitorSetting['chkProduct']) && $visitorSetting['chkProduct'];
    $syncPersons = !empty($visitorSetting['chkCustomersMahak']) && $visitorSetting['chkCustomersMahak'];
    $syncCategory = (!empty($visitorSetting['chkCategory']) && $visitorSetting['chkCategory']) ? ($visitorSetting['chkCategory'] == "cat" ? BAZARA_PRODUCT_CATEGORY : BAZARA_PRODUCT_SUB_CATEGORY) : false;

    if ($syncProduct) {
        if(!$syncPersons){
            $entities = [
                'Settings',
                'ProductSync', // New combined sync for products
                'ProductDetailStoreAssets',
                'Stores',
                'PersonGroups',
                'PropertyDescriptions',
                'ExtraDatas',
                'Pictures',
                'PhotoGalleries',
                'Banks',
                'VisitorPersonSync',
                'PersonSync',
                'SubCategory',
                'Orders',
                'OrderDetails',
                'Regions'
            ];
        } else {
            $entities = [
                'Settings',
                'ProductSync', // New combined sync for products
                'ProductDetailStoreAssets',
                'Stores',
                'PersonGroups',
                'PropertyDescriptions',
                'ExtraDatas',
                'Pictures',
                'PhotoGalleries',
                'Banks',
                'VisitorPersonSync',
                'PersonSync',
                'SubCategory',
                'Orders',
                'OrderDetails',
                'Regions'
            ];
        }

        if (class_exists('bazara_addOns'))
            $entities[] = 'Transactions';

        for ($i = 0; $i < count($entities); $i++) {
            $bazara->bazara_copy_entities($entities[$i], 0, 100000);
            bazara_save_log(date_i18n('Y-m-j'),'در حال همگام سازی',json_encode($entities[$i]),'test');
        }

        bz_cleanup_duplicate_variations_from_report();
        
        

        if (get_product_cnt() > 0) {
            if ($syncCategory)
                $bazara->start_sync_category(null, $syncCategory);

            $message = $bazara->start_sync_new_product(0, 10000, true)['message'];
            clear_junk_data();
        }

        if ($syncPicture) {
            $bazara->sync_pictures()['message'];
        }
    }

    if (class_exists('bazara_addOns')) {
        $bazara->bazara_copy_entities("Transactions", 0, 100000);
    }

    // Persons sync moved to its own cron job (bazara_run_persons_sync)

    bazara_save_log(date_i18n('Y-m-j'), ' اتمام همگام سازی', 'end', 'success');
}
function bazara_run_persons_synchronize()
{
    $visitorSetting = get_bazara_visitor_settings();
    $syncPersons = !empty($visitorSetting['chkCustomersMahak']) && $visitorSetting['chkCustomersMahak'];
    if (!$syncPersons) return;

    $bazara = new BazaraApi(true);
    $bazara->start_sync_persons();
}
function send_bazara_orders()
{
    $bazara = new BazaraApi(true);
    $bazara->start_sync_orders();
}
function clear_junk_data()
{
    global  $wpdb;

    //sometimes woocommerce creating dupplicate products
    //plugin will find and delete the dupplicated data

    $table_name_post_meta = $wpdb->prefix . 'postmeta';
    $table_name_post = $wpdb->prefix . 'posts';
    $table_name_bazara_products = $wpdb->prefix . 'bazara_products';
    $allDupplicatedSku = $wpdb->get_col($wpdb->prepare("SELECT meta_value as pid  FROM $table_name_post_meta WHERE `meta_key` LIKE '_sku' GROUP by meta_value HAVING COUNT(*) > 1"));

    if (!empty($allDupplicatedSku)) {
        $dupplicatedIDS = $wpdb->get_col($wpdb->prepare("SELECT MAX(post_id) as pid  FROM  $table_name_post_meta WHERE `meta_key` LIKE '_sku' GROUP by meta_value HAVING COUNT(*) > 1"));
        $dupplicatedIDS = $wpdb->get_col($wpdb->prepare("SELECT ID  FROM  $table_name_post WHERE post_type='product' and ID IN ( '" . implode("','", $dupplicatedIDS) . "' )"));
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name_post WHERE  ID IN ( '" . implode("','", $dupplicatedIDS) . "' )"));
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name_post_meta WHERE post_id IN ( '" . implode("','", $dupplicatedIDS) . "' )"));
        // $wpdb->query($wpdb->prepare("UPDATE $table_name_bazara_products set detailSync = 0,stockSync = 0,priceSync = 0 WHERE ProductCode IN ( '" . implode( "','", $allDupplicatedSku ) . "' )"));

    }
}
function clear_tables_queue()
{
    global  $wpdb;

    if (!class_exists("bazara_manage_quantity")) {
        $table_name_post_meta = $wpdb->prefix . 'postmeta';
        $table_name_post = $wpdb->prefix . 'posts';

        $wpdb->query($wpdb->prepare("DELETE FROM $table_name_post_meta where $table_name_post_meta.post_id NOT IN (select ID from $table_name_post)"));
    }
}
function bazara_purge_product()
{
    if (defined('LSCWP_V')) {
        do_action('litespeed_purge_posttype', 'product');
    }
}
function get_last_order_id()
{
    global $wpdb;
    $statuses = array_keys(wc_get_order_statuses());
    $statuses = implode("','", $statuses);

    // Getting last Order ID (max value)
    $results = $wpdb->get_col("
        SELECT MAX(ID) FROM {$wpdb->prefix}posts
        WHERE post_type LIKE 'shop_order'
        AND post_status IN ('$statuses')
    ");
    return reset($results);
}
function get_order_item_meta_payment_hpos($orderid)
{
    $hpos_enable = get_option('woocommerce_custom_orders_table_enabled') === 'yes';
    if(!$hpos_enable){
        return null;
    }

    global $wpdb;

    $table_order_item = "{$wpdb->prefix}wc_orders";

    $results = $wpdb->get_results($wpdb->prepare("SELECT * 	
	from `$table_order_item` as p 
	where  p.id = %d", $orderid));

    if ($results)
        return $results[0];
    return null;
}
function get_orders_hpos($orderID = 32220)
{
    global $wpdb;
    $table_order_item = "{$wpdb->prefix}wc_orders";
    $query = $wpdb->prepare("
        SELECT p.id 
        FROM `$table_order_item` as p
        LEFT OUTER JOIN `{$wpdb->prefix}postmeta` pm 
        ON (p.id=pm.post_id AND pm.meta_key = 'mahak_id') 
        WHERE p.status IN ('wc-completed', 'wc-processing', 'wc-processing5', 'wc-pws-packaged', 'wc-pws-shipping', 'wc-arrival-shipment')
        AND p.id >= %d
        AND (pm.meta_key IS NULL)
        ORDER BY p.id ASC", $orderID); // تغییر به مرتب‌سازی صعودی ID
    $results = $wpdb->get_col($query);

    return $results;
}
function get_left_behind_orders($max_id)
{
    global $wpdb;
    
    // اگر max_id معتبر نباشد، هیچ سفارش جا‌مانده‌ای وجود ندارد
    if (empty($max_id) || $max_id <= 0) {
        return array();
    }
    
    $query = $wpdb->prepare("
        SELECT posts.ID
        FROM  `{$wpdb->posts}` AS posts 
        JOIN (SELECT p.ID, p.post_title 
              FROM `{$wpdb->posts}` as p 
              LEFT OUTER JOIN `{$wpdb->prefix}postmeta` pm 
              ON (p.ID=pm.post_id AND pm.meta_key = 'mahak_id') 
              WHERE p.post_type = 'shop_order' 
              AND (pm.meta_key IS NULL)) s2 
        ON s2.ID = posts.ID
        WHERE posts.post_type = 'shop_order' 
        AND posts.post_status IN ('wc-completed', 'wc-processing', 'wc-processing5', 'wc-pws-packaged', 'wc-pws-shipping', 'wc-arrival-shipment')
        AND posts.ID < %d
        ORDER BY posts.ID ASC", $max_id);
    $results = $wpdb->get_col($query);

    return $results;
}
function get_left_behind_orders_hpos($max_id)
{
    global $wpdb;
    
    // اگر max_id معتبر نباشد، هیچ سفارش جا‌مانده‌ای وجود ندارد
    if (empty($max_id) || $max_id <= 0) {
        return array();
    }
    
    $table_order_item = "{$wpdb->prefix}wc_orders";
    $query = $wpdb->prepare("
        SELECT p.id 
        FROM `$table_order_item` as p
        LEFT OUTER JOIN `{$wpdb->prefix}postmeta` pm 
        ON (p.id=pm.post_id AND pm.meta_key = 'mahak_id') 
        WHERE p.status IN ('wc-completed', 'wc-processing', 'wc-processing5', 'wc-pws-packaged', 'wc-pws-shipping', 'wc-arrival-shipment')
        AND p.id < %d
        AND (pm.meta_key IS NULL)
        ORDER BY p.id ASC", $max_id);
    $results = $wpdb->get_col($query);

    return $results;
}
function get_left_behind_orders_from_limit($sync_limit, $max_id)
{
    global $wpdb;
    
    // اگر sync_limit یا max_id معتبر نباشند، هیچ سفارش جا‌مانده‌ای وجود ندارد
    if (empty($sync_limit) || $sync_limit <= 0 || empty($max_id) || $max_id <= 0 || $sync_limit >= $max_id) {
        return array();
    }
    
    $query = $wpdb->prepare("
        SELECT posts.ID
        FROM  `{$wpdb->posts}` AS posts 
        JOIN (SELECT p.ID, p.post_title 
              FROM `{$wpdb->posts}` as p 
              LEFT OUTER JOIN `{$wpdb->prefix}postmeta` pm 
              ON (p.ID=pm.post_id AND pm.meta_key = 'mahak_id') 
              WHERE p.post_type = 'shop_order' 
              AND (pm.meta_key IS NULL)) s2 
        ON s2.ID = posts.ID
        WHERE posts.post_type = 'shop_order' 
        AND posts.post_status IN ('wc-completed', 'wc-processing', 'wc-processing5', 'wc-pws-packaged', 'wc-pws-shipping', 'wc-arrival-shipment')
        AND posts.ID >= %d
        AND posts.ID < %d
        ORDER BY posts.ID ASC", $sync_limit, $max_id);
    $results = $wpdb->get_col($query);

    return $results;
}
function get_left_behind_orders_from_limit_hpos($sync_limit, $max_id)
{
    global $wpdb;
    
    // اگر sync_limit یا max_id معتبر نباشند، هیچ سفارش جا‌مانده‌ای وجود ندارد
    if (empty($sync_limit) || $sync_limit <= 0 || empty($max_id) || $max_id <= 0 || $sync_limit >= $max_id) {
        return array();
    }
    
    $table_order_item = "{$wpdb->prefix}wc_orders";
    $query = $wpdb->prepare("
        SELECT p.id 
        FROM `$table_order_item` as p
        LEFT OUTER JOIN `{$wpdb->prefix}postmeta` pm 
        ON (p.id=pm.post_id AND pm.meta_key = 'mahak_id') 
        WHERE p.status IN ('wc-completed', 'wc-processing', 'wc-processing5', 'wc-pws-packaged', 'wc-pws-shipping', 'wc-arrival-shipment')
        AND p.id >= %d
        AND p.id < %d
        AND (pm.meta_key IS NULL)
        ORDER BY p.id ASC", $sync_limit, $max_id);
    $results = $wpdb->get_col($query);

    return $results;
}
function get_orders_address_hpos($orderID = 32220, $address_type = 'shipping') //zamannian 1403/05/23
{
    global $wpdb;

    $table_order_addresses = "{$wpdb->prefix}wc_order_addresses";

    $query = $wpdb->prepare("SELECT *
	from
	`$table_order_addresses` 
	 where address_type = %s
	 and order_id = %d ", $address_type, $orderID);
    $results = $wpdb->get_row($query);

    return $results;
}
function get_pictures()
{
    global  $wpdb;
    $query = "SELECT {$wpdb->prefix}bazara_pictures.RowVersion as RowVersion,
                     {$wpdb->prefix}bazara_pictures.PictureId as PictureId,
                     {$wpdb->prefix}bazara_photo_gallery.ItemCode as ItemCode,
                     {$wpdb->prefix}bazara_pictures.Url as Url 
              FROM {$wpdb->prefix}bazara_pictures 
              JOIN {$wpdb->prefix}bazara_photo_gallery 
              ON {$wpdb->prefix}bazara_pictures.PictureId = {$wpdb->prefix}bazara_photo_gallery.PictureId 
              WHERE ({$wpdb->prefix}bazara_pictures.isSync = 0 or {$wpdb->prefix}bazara_pictures.isSync IS NULL) 
              AND ({$wpdb->prefix}bazara_pictures.queue = 0 or {$wpdb->prefix}bazara_pictures.queue IS NULL) 
              AND {$wpdb->prefix}bazara_photo_gallery.Deleted = 0 
              AND {$wpdb->prefix}bazara_pictures.Deleted = 0
              ORDER BY {$wpdb->prefix}bazara_pictures.filename ASC, {$wpdb->prefix}bazara_photo_gallery.ItemCode";
    return $wpdb->get_results($query);
}
function update_wp_roles($roles)
{
    if (empty($roles))
        return;
    $wholeSale = false;
    if (class_exists("WWP_Wholesale_Roles"))
        $wholeSale = true;
    if ($wholeSale)
        $admin_role =  new WWP_Wholesale_Roles();
    foreach ($roles as $role) {
        $key = str_replace(" ", "_", $role['Name']);
        add_role(
            $key,
            $role['Name'],
            array(
                'read' => true,
                'level_0' => true
            )
        );
        if ($wholeSale) {
            $admin_role->addCustomRole($key, $role['Name']);
            $admin_role->registerCustomRole(
                $key,
                $role['Name'],
                array(
                    'desc'                        => '',
                    'onlyAllowWholesalePurchases' => true,
                )
            );
            $admin_role->addCustomCapability($key, 'have_wholesale_price');
        }
    }
}

function get_wp_roles()
{


    global $wp_roles;
    if (!isset($wp_roles))
        $wp_roles = new WP_Roles();
    return $wp_roles->get_names();
}
function get_order_item_qty($pid, $type = '_product_id')
{

    global $wpdb;
    $table_posts = "{$wpdb->prefix}posts";
    $table_postmeta = "{$wpdb->prefix}postmeta";
    $table_order_item = "{$wpdb->prefix}woocommerce_order_items";
    $table_order_item_meta = "{$wpdb->prefix}woocommerce_order_itemmeta";

    $options = get_bazara_visitor_settings();
    $Order_Max_ID = (int)$options['order_id_greater_than'];
    $max_id = (!empty($Order_Max_ID) ? $Order_Max_ID : 0);

    $results =    $wpdb->get_results($wpdb->prepare("select sum(itm2.meta_value) as qty from `$table_posts` p
    LEFT OUTER JOIN `$table_postmeta` pm ON (pm.post_id = p.ID AND (pm.meta_key = 'mahak_id'))
    JOIN `$table_order_item` oi ON oi.order_id = p.ID
    JOIN `$table_order_item_meta` itm ON itm.order_item_id = oi.order_item_id
    JOIN `$table_order_item_meta` itm2 ON itm2.order_item_id = oi.order_item_id and itm2.meta_key='_qty'
     where p.post_type = 'shop_order'
     AND p.post_status NOT IN ('trash', 'wc-cancelled','wc-pending')
     AND (pm.meta_key IS NULL OR pm.meta_value IS NULL OR pm.meta_value = '')
     AND itm.meta_key='%s' AND itm.meta_value = %d AND p.ID >= %d ", $type, $pid, $max_id));

    if ($results)
        return !empty($results[0]) ? $results[0]->qty : 0;
    return null;
}
function get_bazara_not_converted_qty($productDetailId)
{
    global $wpdb;
    $table_orders = "{$wpdb->prefix}bazara_orders";
    $table_orderDetails = "{$wpdb->prefix}bazara_order_details";

    $options = get_bazara_visitor_settings();
    $Order_Max_ID = (int)$options['order_id_greater_than'];
    $max_id = (!empty($Order_Max_ID) ? $Order_Max_ID : 0);
    $results =    $wpdb->get_results($wpdb->prepare("select IFNULL(sum(Count1),0) as qty from `$table_orders` o
    JOIN `$table_orderDetails` od ON od.OrderId = o.OrderId
     where orderCode = 0 AND ProductDetailId = %d 
     AND o.Deleted = false AND od.Deleted = false
     AND o.OrderClientId >= %d ", $productDetailId, $max_id));


    if ($results)
        return !empty($results[0]) ? $results[0]->qty : 0;
    return null;
}
function get_order_item_copoun($order_item_id)
{
    global $wpdb;
    $table_order_item = "{$wpdb->prefix}woocommerce_order_items";
    $table_order_item_meta = "{$wpdb->prefix}woocommerce_order_itemmeta";

    $results =    $wpdb->get_results($wpdb->prepare("SELECT
	max( CASE WHEN pm.meta_key = '_product_id' and p.order_item_id = pm.order_item_id THEN pm.meta_value END ) as productID,
	from
	`$table_order_item` as p,
	`$table_order_item_meta` as pm
	 where order_item_type = 'coupon' and
	 p.order_item_id = pm.order_item_id
	 and p.order_item_id = %d
	 group by p.order_item_id ", $order_item_id));

    if ($results)
        return $results[0];
    return null;
}
function get_order_item_pa_meta($order_item_id)
{
    global $wpdb;
    $table_order_item = "{$wpdb->prefix}woocommerce_order_items";
    $table_order_item_meta = "{$wpdb->prefix}woocommerce_order_itemmeta";

    $results =    $wpdb->get_results($wpdb->prepare("SELECT
    (pm.meta_key) as attributes,
    (pm.meta_value) as attr_values
       from
        `$table_order_item` as p,
	    `$table_order_item_meta` as pm
        where order_item_type = 'line_item' and
        p.order_item_id = pm.order_item_id
        and p.order_item_id = %d and left(pm.meta_key,2)= %s", $order_item_id, 'pa'));

    if ($results)
        return $results;
    return null;
}
function get_order_item_meta($order_item_id)
{
    global $wpdb;
    $table_order_item = "{$wpdb->prefix}woocommerce_order_items";
    $table_order_item_meta = "{$wpdb->prefix}woocommerce_order_itemmeta";

    $results =    $wpdb->get_results($wpdb->prepare("SELECT
	max( CASE WHEN pm.meta_key = '_product_id' and p.order_item_id = pm.order_item_id THEN pm.meta_value END ) as productID,
	max( CASE WHEN pm.meta_key = '_qty' and p.order_item_id = pm.order_item_id THEN pm.meta_value END ) as Qty,
	max( CASE WHEN pm.meta_key = '_variation_id' and p.order_item_id = pm.order_item_id THEN pm.meta_value END ) as variantID
    
	from
	`$table_order_item` as p,
	`$table_order_item_meta` as pm
	 where order_item_type = 'line_item' and
	 p.order_item_id = pm.order_item_id
	 and p.order_item_id = %d
	 group by p.order_item_id ", $order_item_id));

    if ($results)
        return $results[0];
    return null;
}
function get_order_item_meta_shipping_name($orderid)
{
    global $wpdb;
    $table_order_item = "{$wpdb->prefix}woocommerce_order_items";

    $results =    $wpdb->get_results($wpdb->prepare("SELECT order_item_name as name from  `$table_order_item` where order_item_type='shipping' and order_id = %d ", $orderid));

    if ($results)
        return $results[0];
    return null;
}
function get_order_item_meta_shipping($orderid)
{
    global $wpdb;
    $table_order_item = "{$wpdb->prefix}woocommerce_order_items";
    $table_order_item_meta = "{$wpdb->prefix}woocommerce_order_itemmeta";

    $results =    $wpdb->get_results($wpdb->prepare("SELECT
	max( CASE WHEN pm.meta_key = 'method_id' and p.order_item_id = pm.order_item_id THEN pm.meta_value END ) as shipping_method

	from
	`$table_order_item` as p,
	`$table_order_item_meta` as pm
	 where order_item_type = 'shipping' and
	 p.order_item_id = pm.order_item_id
	 and p.order_id = %d
	 group by p.order_item_id ", $orderid));

    if ($results)
        return $results[0];
    return null;
}
function get_order_item_shipping_amount_hpos($orderid)
{
    global $wpdb;
    $table_order_item = "{$wpdb->prefix}woocommerce_order_items";
    $table_order_item_meta = "{$wpdb->prefix}woocommerce_order_itemmeta";

    $results =    $wpdb->get_results($wpdb->prepare("SELECT
	max( CASE WHEN pm.meta_key = 'cost' and p.order_item_id = pm.order_item_id THEN pm.meta_value END ) as cost

	from
	`$table_order_item` as p,
	`$table_order_item_meta` as pm
	 where order_item_type = 'shipping' and
	 p.order_item_id = pm.order_item_id
	 and p.order_id = %d
	 group by p.order_item_id ", $orderid));

    if ($results)
        return $results[0];
    return null;
}
function get_order_item_discount($order_item_id)
{
    global $wpdb;
    $table_order_item = "{$wpdb->prefix}woocommerce_order_items";
    $table_order_item_meta = "{$wpdb->prefix}woocommerce_order_itemmeta";

    $results =    $wpdb->get_results($wpdb->prepare("SELECT
	max( CASE WHEN pm.meta_key = 'discount_amount' and p.order_item_id = pm.order_item_id THEN pm.meta_value END ) as discount
	from
	`$table_order_item` as p,
	`$table_order_item_meta` as pm
	 where order_item_type = 'coupon' and
	 p.order_item_id = pm.order_item_id
	 and p.order_id = %d
	 group by p.order_item_id ", $order_item_id));

    if ($results)
        return $results[0];
    return null;
}
function clear_old_bazara_site()
{
    global  $wpdb;
    $table_name = $wpdb->prefix . 'postmeta';
    if (empty($pid)) return false;
    $wpdb->query($wpdb->prepare("Delete from $table_name where meta_key = 'mahak_id' and "));
    //    $wpdb->query($wpdb->prepare("Update $table_name "))
}
function bazara_update_client_id($target = 'receipt', $val = 0)
{
    global $wpdb;
    $table_name  = "{$wpdb->prefix}bazara_clients";
    $fieldpostfix = $target . '_clientId';
    $wpdb->query($wpdb->prepare("UPDATE  $table_name SET $fieldpostfix = %s ", $val));
}
function bazara_get_last_client_id($target = 'receipt')
{
    global $wpdb;
    $table_name  = "{$wpdb->prefix}bazara_clients";
    $fieldpostfix = $target . '_clientId';

    $results =    $wpdb->get_results("SELECT $fieldpostfix from $table_name");

    return $results[0]->$fieldpostfix;
}

function bazara_get_options()
{
    return get_option('bazara_options', bazara_options_default());
}

function get_latest_versions()
{
    return get_option('bazara_latest_versions', bazara_latest_versions());
}
function get_bazara_visitor_options()
{
    return get_option('bazara_visitor_options', bazara_options_visitor());
}
function get_bazara_visitor_settings()
{
    return get_option('bazara_visitor_settings', bazara_settings_visitor());
}
function get_bazara_taxonomy_term($cat)
{
    $option = get_option('bazara_taxonomy_term_meta', bazara_taxonomy_term());
    return  !empty($option[$cat]) ? $option[$cat] : 0;
}
function update_picture_queue($picID, $flag = 1, $all = false)
{
    global  $wpdb;
    $table_name = $wpdb->prefix . 'bazara_pictures';
    if (empty($picID) && !$all) return false;
    $cond = '';
    if (!$all)
        $cond = "WHERE PictureId={$picID}";
    $wpdb->query($wpdb->prepare("UPDATE $table_name SET queue={$flag} {$cond}"));
}
function update_product_queue($pid, $flag = 1, $all = false, $tbl = 'bazara_products', $field = 'ProductId')
{
    global  $wpdb;
    $table_name = $wpdb->prefix . $tbl;
    if (empty($pid) && !$all) return false;
    $cond = '';
    if (!$all)
        $cond = "WHERE {$field}={$pid}";
    $wpdb->query("UPDATE $table_name SET queue={$flag} {$cond}");
    //
}
function get_bazara_user_token($token = '')
{
    if (empty($token)) {
        $bazara = new BazaraApi(false);
        $token_result = $bazara->login_token();
        if (!$token_result['success'])
            return array('success' => false, 'message' => $token_result['message']);
        $token = $token_result['message'];
    }
    return $token;
}
function bazara_update_latest_versions($name, $value)
{
    $versions = get_latest_versions();
    if ($value > $versions[$name]) {
        $versions[$name] = $value;
        update_option('bazara_latest_versions', $versions, false);
    }
}
function bazara_update_taxonomy_term($taxonomy_id, $catid)
{
    $options = get_option('bazara_taxonomy_term_meta');
    $options[$taxonomy_id] = $catid;
    update_option('bazara_taxonomy_term_meta', $options, false);
}
function bazara_get_latest_RowVersions($name)
{
    $versions = get_latest_versions();
    return  !empty($versions[$name]) ? $versions[$name] : 0;
}

function bazara_validate_plugin_options($options)
{
    if (empty($options['username']))
        return __('mahak username is empty. please set username first.', 'mahak-bazara');
    if (empty($options['password']))
        return __('mahak password is empty. please set password first.', 'mahak-bazara');
    //    if(empty($options['systemSyncID']))
    //        return __('mahak systemSyncID is empty. please set systemSyncID first.','mahak-bazara');
    return "";
}
function bazara_arabicToPersian($string)
{
    $characters = [
        'ك' => 'ک',
        'ى' => 'ی',
        'ي' => 'ی'
    ];
    return str_replace(array_keys($characters), array_values($characters), $string);
}
function text_to_json($arr)
{
    $arr = !empty($arr) ? json_decode($arr) : [];
    return (json_decode(json_encode($arr), true));
}
function toggle_to_boolean($value)
{
    if (empty($value)) return false;
    if ($value == "on" || $value === 1 || $value == "1" || $value == "true" || $value === true)
        return true;

    return false;
}
function add_prefix_id_validation($value)
{
    if (empty($value)) return '';

    return 'btn-reg-price-' . $value;
}
function add_prefix_id_percentDiscount($value)
{
    if (empty($value)) return '';

    return 'btn-percent-discount-' . $value;
}
function add_prefix_id_priceDiscount($value)
{
    if (empty($value)) return '';

    return 'btn-price-discount-' . $value;
}
function add_prefix_variation_name($value)
{
    if (empty($value)) return '';
    $value = sanitize_title($value);
    return 'select[data-attribute_name="attribute_pa_' . $value . '"]';
}
function bazara_payment_method_is_cod($method)
{
    if (empty($method))
        return true;

    $paymentMethods = array('cod', 'cheque', 'bacs');

    if (in_array($method, $paymentMethods))
        return true;

    return false;
}
//add_filter( 'http_request_timeout', 'wp9838c_timeout_extend' );
//
//function wp9838c_timeout_extend( $time )
//{
//    // Default timeout is 5
//    return 100;
//}
add_action('before_delete_post', 'bazara_delete_product_hook');
function bazara_delete_product_hook($post_id)
{
    global $wpdb;

    $pid = get_post_meta($post_id, 'mahak_id', true);
    if (!empty($pid)) {
        update_schedule_sync($pid, 'detailSync', 0);
        update_schedule_sync($pid, 'stockSync', 0);
        update_schedule_sync($pid, 'priceSync', 0);
        update_product_as_old($pid, 1);
        update_picture_flag_for_sync($pid, 0);
    }
}
function get_orders($orderID = 32220)
{
    global $wpdb;
    $where = '';
    if ($orderID > 0)
        $where = " AND posts.ID >= %d";
    $query = $wpdb->prepare("
        SELECT posts.ID
        FROM  `{$wpdb->posts}` AS posts 
        JOIN (SELECT p.ID, p.post_title 
              FROM `{$wpdb->posts}` as p 
              LEFT OUTER JOIN `{$wpdb->prefix}postmeta` pm 
              ON (p.ID=pm.post_id AND pm.meta_key = 'mahak_id') 
              WHERE p.post_type = 'shop_order' 
              AND (pm.meta_key IS NULL)) s2 
        ON s2.ID = posts.ID
        WHERE posts.post_type = 'shop_order' 
        AND posts.post_status IN ('wc-completed', 'wc-processing', 'wc-processing5', 'wc-pws-packaged', 'wc-pws-shipping', 'wc-arrival-shipment')
        {$where}
        ORDER BY posts.ID ASC", $orderID); // تغییر به مرتب‌سازی صعودی ID
    $results = $wpdb->get_col($query);

    return $results;
}
function bazara_validation_options($input)
{
    if (isset($input['systemSyncID'])) {
        $input['systemSyncID'] = sanitize_text_field($input['systemSyncID']);
    }
    //////
    if (isset($input['username'])) {
        $input['username'] = sanitize_text_field($input['username']);
    }
    //////
    if (isset($input['password'])) {
        $input['password'] = sanitize_text_field($input['password']);
    }

    return $input;
}
function bazara_in_array($in, $data)
{
    if (is_array($data)) {
        if (in_array($in, $data))
            return true;
    }
    return false;
}
function bazara_sortByEarliest($a, $b)
{
    return (empty($a['expireDate']) ? 0 : $a['expireDate']) - (empty($b['expireDate']) ? 0 : $b['expireDate']);
}
function bazara_sortByGreatest($a, $b)
{
    return (empty($b['expireDate']) ? 0 : $b['expireDate']) - (empty($a['expireDate']) ? 0 : $a['expireDate']);
}
function bazara_sortByLatestDate($a, $b)
{
    return strtotime($b['term_names']) - strtotime($a['term_names']);
}

function convert_non_persian_chars_to_persian($str)
{
    //main goal: arabic chars: from ؀ U+0600 (&#1536;) to ۿ U+06FF (&#1791;)
    //source: https://unicode-table.com/en
    $right_chars = array(
        'ا',
        'ب',
        'پ',
        'ت',
        'ث',
        'ج',
        'چ',
        'ح',
        'خ',
        'د',
        'ذ',
        'ر',
        'ز',
        'ژ',
        'س',
        'ش',
        'ص',
        'ض',
        'ط',
        'ظ',
        'ع',
        'غ',
        'ف',
        'ق',
        'ک',
        'گ',
        'ل',
        'م',
        'ن',
        'و',
        'ه',
        'ی',
        '‌', /* Zero Width Non-Joiner (nim fasele): U+200C (Unicode) or &#8204; (HTML) */
    );
    //first char of every array is right char
    $all_like_chars = array(
        $alef = array(
            'ا',
            'ݳ',
            'ݴ',
        ),
        $be = array(
            'ب',
            'ٻ',
            'ڀ',
            'ݑ',
            'ݔ',
            'ݕ',
            'ݖ',
        ),
        $pe = array(
            'پ',
            'ݐ',
            'ݒ',
            'ݓ',
        ),
        $te = array(
            'ت',
            'ٺ',
            'ټ',
            'ٽ',
            'ٿ',
        ),
        $se = array(
            'ث',
            'ٹ',
        ),
        $jim = array(
            'ج',
            'ڃ',
            'ڄ',
            'ݼ',
        ),
        $che = array(
            'چ',
            'ڇ',
            'ڿ',
            'ݘ',
            'ݮ',
            'ݯ',
        ),
        $he = array(
            'ح',
            'ځ',
            'ڂ',
            'څ',
            'ݗ',
        ),
        $khe = array(
            'خ',
            'ݲ',
        ),
        $dal = array(
            'د',
            'ڈ',
            'ډ',
            'ڊ',
            'ڋ',
            'ڌ',
            'ڍ',
            'ڎ',
            'ڏ',
            'ڐ',
            'ݙ',
            'ݚ',
            'ۮ',
        ),
        $zal = array(
            'ذ',
        ),
        $re = array(
            'ر',
            'ړ',
            'ڔ',
            'ڕ',
            'ږ',
            'ݛ',
            'ހ',
            'ۯ',
            'ڑ',
            'ڒ',
            'ݬ',
            'ݫ',
            'ڗ',
            'ڙ',
            'ݱ',
        ),
        $ze = array(
            'ز',
        ),
        $zhe = array(
            'ژ',
        ),
        $sin = array(
            'س',
            'ښ',
            'ڛ',
            'ݭ',
            'ݽ',
        ),
        $shin = array(
            'ش',
            'ݜ',
            'ڜ',
            'ݰ',
            'ݾ',
            'ۺ',
        ),
        $sad = array(
            'ص',
            'ڝ',
            'ڞ',
        ),
        $zad = array(
            'ض',
            'ۻ',
        ),
        $ta = array(
            'ط',
            'ڟ',
        ),
        $za = array(
            'ظ',
        ),
        $ain = array(
            'ع',
        ),
        $ghain = array(
            'غ',
            'ڠ',
            'ݝ',
            'ݞ',
            'ݟ',
            'ۼ',
        ),
        $fe = array(
            'ف',
            'ڡ',
            'ڢ',
            'ڣ',
            'ڤ',
            'ڥ',
            'ڦ',
            'ݠ',
            'ݡ',
        ),
        $ghaf = array(
            'ق',
            'ٯ',
            'ڧ',
            'ڨ',
        ),
        $kaf = array(
            'ک',
            'ػ',
            'ؼ',
            'ك',
            'ڪ',
            'ګ',
            'ڬ',
            'ڭ',
            'ڮ',
            'ݢ',
            'ݣ',
            'ݤ',
            'ݿ',
        ),
        $gaf = array(
            'گ',
            'ڰ',
            'ڱ',
            'ڲ',
            'ڳ',
            'ڴ',
        ),
        $lam = array(
            'ل',
            'ڵ',
            'ڶ',
            'ڷ',
            'ڸ',
            'ݪ',
        ),
        $mim = array(
            'م',
            'ݥ',
            'ݦ',
            '۾',
        ),
        $noon = array(
            'ن',
            'ڹ',
            'ں',
            'ڻ',
            'ڼ',
            'ڽ',
            'ݧ',
            'ݨ',
            'ݩ',
        ),
        $vav = array(
            'و',
            'ٶ',
            'ٷ',
            'ۄ',
            'ۅ',
            'ۆ',
            'ۇ',
            'ۈ',
            'ۉ',
            'ۊ',
            'ۋ',
            'ۏ',
            'ݸ',
            'ݹ',
        ),
        $ha = array(
            'ه',
            'ھ',
            'ہ',
            'ۂ',
            'ۃ',
            'ە',
            'ۿ',
        ),
        $ye = array(
            'ی',
            'ؽ',
            'ؾ',
            'ؿ',
            'ي',
            'ٸ',
            'ۍ',
            'ێ',
            'ې',
            'ۑ',
            'ے',
            'ۓ',
            'ݵ',
            'ݶ',
            'ݷ',
            'ݺ',
            'ݻ',
        ),
        //32nd
        $nim_fasele = array(
            '‌', /* Zero Width Non-Joiner (nim fasele): U+200C (Unicode) or &#8204; (HTML) */
            '¬',
        ),
    );
    for ($i = 0; $i <= 32; $i++) {
        $str = str_replace($all_like_chars[$i], $right_chars[$i], $str);
    }
    return $str;
}
function jalali_to_timestamp($date, $first = true)
{
    date_default_timezone_set('Asia/Tehran');
    $time = explode('/', $date);
    $gregorian = jalali_to_gregorian($time[0], $time[1], $time[2], '/');
    /* $gregorian = explode('/', $gregorian);
         if ($first)
             $timeNow = mktime(0, 0, 0, $gregorian[1], $gregorian[2], $gregorian[0]);
         else
             $timeNow = mktime(23, 59, 59, $gregorian[1], $gregorian[2], $gregorian[0]);*/

    return $gregorian;
}

function bazara_is_rtl($string)
{
    $rtl_chars_pattern = '/[\x{0590}-\x{05ff}\x{0600}-\x{06ff}]/u';
    return preg_match($rtl_chars_pattern, $string);
}
function jalali_to_datetimestamp($date, $first = true)
{
    date_default_timezone_set('Asia/Tehran');
    $time = explode('/', $date);
    $gregorian = jalali_to_gregorian($time[0], $time[1], $time[2], '/');
    /* $gregorian = explode('/', $gregorian);
         if ($first)
             $timeNow = mktime(0, 0, 0, $gregorian[1], $gregorian[2], $gregorian[0]);
         else
             $timeNow = mktime(23, 59, 59, $gregorian[1], $gregorian[2], $gregorian[0]);*/

    return $gregorian . ' ' . date('H:i:s', time());
}

add_action('woocommerce_new_order', 'sync_and_update_order_id_greater_than', 10, 2);
function sync_and_update_order_id_greater_than($order_id, $order)
{
    // دریافت تنظیمات
    $options = get_option('bazara_visitor_settings', []);

    // فقط اگر chkLastOrderID فعال باشد
    if (isset($options['chkLastOrderID']) && $options['chkLastOrderID']) {
        // بررسی اینکه آیا سفارش جدید باید سینک شود
        $current_max_id = (int) ($options['order_id_greater_than'] ?? 0);
        if ($order_id > $current_max_id) {
            $bazara = new BazaraApi(true);
            $token_result = $bazara->login_token();
            if ($token_result['success']) {
                $token = $token_result['message'];
                $result = $bazara->bazara_save_order($order_id, $token);
                if ($result['success']) {
                    // آپدیت order_id_greater_than
                    $options['order_id_greater_than'] = (int) $order_id;
                    update_option('bazara_visitor_settings', $options);
                }
            }
        }
    }
}

function is_order_status_valid($order_id)
{
    global $wpdb;
    $valid_statuses = ['wc-completed', 'wc-processing', 'wc-processing5', 'wc-pws-packaged'];

    // بررسی اینکه HPOS فعال است یا خیر
    $hpos_enable = get_option('woocommerce_custom_orders_table_enabled') === 'yes';

    if ($hpos_enable) {
        // حالت HPOS: استفاده از جدول wc_orders
        $table_orders = "{$wpdb->prefix}wc_orders";
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM `$table_orders` WHERE id = %d",
            $order_id
        ));
    } else {
        // حالت عادی: استفاده از جدول posts
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT post_status FROM {$wpdb->posts} WHERE ID = %d AND post_type = 'shop_order'",
            $order_id
        ));
    }

    // بررسی اینکه وضعیت در لیست مجاز است یا خیر
    return in_array($status, $valid_statuses, true);
}

function normalize_datetime_to_gregorian($datetime_shamsi_or_miladi) {
    // جدا کردن تاریخ و ساعت
    list($date_part, $time_part) = explode(' ', $datetime_shamsi_or_miladi);

    // اگر تاریخ شمسی بود (شروع با 13 یا 14)
    if (preg_match('/^13\d{2}-\d{2}-\d{2}$/', $date_part) || preg_match('/^14\d{2}-\d{2}-\d{2}$/', $date_part)) {
        list($y, $m, $d) = explode('-', $date_part);
        $miladi_date = CalendarUtils::toGregorian((int)$y, (int)$m, (int)$d);
        $converted_date = implode('-', $miladi_date);
        return $converted_date . ' ' . $time_part;
    }

    // اگر میلادی بود، همون رو برگردون
    return $datetime_shamsi_or_miladi;
}

// function bazara_sync_log($message)
// {
//     error_log('[BAZARA_SYNC] ' . $message);
// }

// add_action('trash_post', function ($post_id) {

//     if (get_post_type($post_id) !== 'product') {
//         return;
//     }

//     error_log(
//         '[BAZARA_TRASH] ' .
//         'post_id=' . $post_id .
//         ' | time=' . microtime(true)
//     );

// });

// add_action('transition_post_status', function ($new_status, $old_status, $post) {

//     if ($post->post_type !== 'product') {
//         return;
//     }

//     if ($new_status === 'trash') {
//         error_log(
//             '[BAZARA_STATUS_CHANGE] ' .
//             'post_id=' . $post->ID .
//             ' | from=' . $old_status .
//             ' | to=trash' .
//             ' | time=' . microtime(true)
//         );
//     }

// }, 10, 3);