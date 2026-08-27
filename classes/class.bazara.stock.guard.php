<?php

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

final class Bazara_Stock_Guard
{
    private $api;
    private $wpdb;

    public function __construct($api, $wpdb)
    {
        $this->api = $api;
        $this->wpdb = $wpdb;
    }

    /**
     * موجودی را بر اساس Detail و فقط برای انبارهای مجاز تجمیع می‌کند.
     */
    public static function build_stock_map(array $assets, array $allowed_store_ids = [])
    {
        $stock_map = [];
        $allowed_store_ids = array_flip(array_map('intval', $allowed_store_ids));

        foreach ($assets as $asset) {
            $detail_id = (int)($asset['ProductDetailId'] ?? 0);
            $store_id = (int)($asset['StoreId'] ?? 0);
            $is_deleted = !empty($asset['Deleted']);

            if ($detail_id <= 0 || $is_deleted) {
                continue;
            }

            if (!empty($allowed_store_ids) && !isset($allowed_store_ids[$store_id])) {
                continue;
            }

            if (!isset($stock_map[$detail_id])) {
                $stock_map[$detail_id] = 0.0;
            }

            $stock_map[$detail_id] += (float)($asset['Count1'] ?? 0);
        }

        return $stock_map;
    }

    /**
     * موجودی قابل فروش را پس از کسر سفارش‌های تبدیل‌نشده محاسبه می‌کند.
     */
    public static function calculate_available_stock(
        array $detail_ids,
        array $stock_map,
        array $pending_quantity_map
    ) {
        $available_stock = 0.0;

        foreach (array_unique(array_map('intval', $detail_ids)) as $detail_id) {
            $stock = (float)($stock_map[$detail_id] ?? 0);
            $pending_quantity = (float)($pending_quantity_map[$detail_id] ?? 0);
            $available_stock += $stock - $pending_quantity;
        }

        return max(0.0, $available_stock);
    }

    /**
     * مقدار ذخیره‌شده ترتیب انبارها را به فهرست یکتای شناسه‌ها تبدیل می‌کند.
     */
    public static function normalize_store_ids($store_ids)
    {
        if (is_string($store_ids)) {
            $store_ids = explode(',', $store_ids);
        }

        if (!is_array($store_ids)) {
            return [];
        }

        $normalized_ids = [];

        foreach ($store_ids as $store_id) {
            if (!is_numeric(trim((string)$store_id))) {
                continue;
            }

            $store_id = (int)$store_id;

            if ($store_id > 0 && !in_array($store_id, $normalized_ids, true)) {
                $normalized_ids[] = $store_id;
            }
        }

        return $normalized_ids;
    }

    /**
     * Detailهای فعال پاسخ Lookup را با درنظرگرفتن حذف و تاریخ انقضا برمی‌گرداند.
     */
    public static function get_active_detail_ids(array $details, array $property_descriptions)
    {
        $date_property_codes = [];

        foreach ($property_descriptions as $property_description) {
            if (!empty($property_description['Deleted'])) {
                continue;
            }

            if ((int)($property_description['DataType'] ?? 0) === (int)BAZARA_PROPERTY_DATE_TYPE) {
                $date_property_codes[] = (int)($property_description['PropertyDescriptionCode'] ?? 0);
            }
        }

        $date_property_codes = array_values(array_filter(array_unique($date_property_codes)));
        $detail_ids = [];

        foreach ($details as $detail) {
            $detail_id = (int)($detail['ProductDetailId'] ?? 0);

            if ($detail_id <= 0 || !empty($detail['Deleted'])) {
                continue;
            }

            if (self::is_detail_expired($detail['Properties'] ?? null, $date_property_codes)) {
                continue;
            }

            $detail_ids[] = $detail_id;
        }

        return array_values(array_unique($detail_ids));
    }

    /**
     * موجودی Detail انتخاب‌شده هر قلم را با پاسخ لحظه‌ای Lookup اعتبارسنجی می‌کند.
     */
    public function validate_cart(array $cart_items)
    {
        if (empty($cart_items)) {
            return [];
        }

        $cart_requests = $this->collect_cart_requests($cart_items);

        if (empty($cart_requests)) {
            return [];
        }

        foreach ($cart_requests as $request) {
            if ($request['mahak_product_id'] <= 0 || $request['mahak_detail_id'] <= 0) {
                return [['reason' => 'verification_failed']];
            }
        }

        $token_result = $this->api->login_token();

        if (empty($token_result['success']) || empty($token_result['message'])) {
            return [['reason' => 'verification_failed']];
        }

        $lookup_cache = [];
        $detail_ids = array_values(array_unique(array_column($cart_requests, 'mahak_detail_id')));
        $allowed_store_ids = $this->get_allowed_store_ids();

        foreach ($cart_requests as $request) {
            $product_id = $request['mahak_product_id'];

            if (isset($lookup_cache[$product_id])) {
                continue;
            }

            $response = $this->api->lookup_product_info($product_id, $token_result['message']);

            if (empty($response['success']) || !is_array($response['message'] ?? null)) {
                return [['reason' => 'verification_failed']];
            }

            $objects = $response['message'];
            $product = $objects['Product'] ?? [];
            $product_is_available = is_array($product)
                && empty($product['Deleted'])
                && (int)($product['ProductId'] ?? 0) === $product_id;
            $active_detail_ids = $product_is_available
                ? self::get_active_detail_ids(
                    $objects['ProductDetails'] ?? [],
                    $objects['PropertyDescriptions'] ?? []
                )
                : [];

            $lookup_cache[$product_id] = [
                'active_detail_ids' => $active_detail_ids,
                'stock_map' => self::build_stock_map(
                    $objects['ProductDetailStoreAssets'] ?? [],
                    $allowed_store_ids
                ),
            ];
        }

        $pending_quantity_map = $this->get_pending_quantity_map($detail_ids);
        $errors = [];

        foreach ($cart_requests as $request) {
            $lookup = $lookup_cache[$request['mahak_product_id']];
            $detail_id = $request['mahak_detail_id'];

            if (!in_array($detail_id, $lookup['active_detail_ids'], true)) {
                $errors[] = [
                    'name' => $request['name'],
                    'available_stock' => 0.0,
                    'requested_quantity' => $request['quantity'],
                    'reason' => 'unavailable',
                ];
                continue;
            }

            $available_stock = self::calculate_available_stock(
                [$detail_id],
                $lookup['stock_map'],
                $pending_quantity_map
            );

            if ($available_stock < $request['quantity']) {
                $errors[] = [
                    'name' => $request['name'],
                    'available_stock' => $available_stock,
                    'requested_quantity' => $request['quantity'],
                    'reason' => 'insufficient_stock',
                ];
            }
        }

        return $errors;
    }

    /**
     * اقلام تکراری یک Detail را تجمیع می‌کند تا موجودی واریشن‌ها مخلوط نشود.
     */
    private function collect_cart_requests(array $cart_items)
    {
        $requests = [];

        foreach ($cart_items as $cart_item) {
            $product_id = (int)($cart_item['product_id'] ?? 0);
            $variation_id = (int)($cart_item['variation_id'] ?? 0);
            $target_id = $variation_id > 0 ? $variation_id : $product_id;
            $quantity = (float)($cart_item['quantity'] ?? 0);

            if ($product_id <= 0 || $target_id <= 0 || $quantity <= 0) {
                continue;
            }

            $product = $cart_item['data'] ?? null;

            if (!$product || !is_object($product) || !method_exists($product, 'get_name')) {
                $product = wc_get_product($target_id);
            }

            $mahak_product_id = (int)get_post_meta($product_id, 'mahak_id', true);
            $mahak_detail_id = (int)get_post_meta($target_id, 'mahak_product_detail_id', true);
            $request_key = $mahak_product_id > 0 && $mahak_detail_id > 0
                ? $mahak_product_id . ':' . $mahak_detail_id
                : 'wc:' . $target_id;

            if (!isset($requests[$request_key])) {
                $requests[$request_key] = [
                    'name' => $product ? $product->get_name() : '#' . $target_id,
                    'quantity' => 0.0,
                    'mahak_product_id' => $mahak_product_id,
                    'mahak_detail_id' => $mahak_detail_id,
                ];
            }

            $requests[$request_key]['quantity'] += $quantity;
        }

        return $requests;
    }

    /**
     * تاریخ‌های معتبر شمسی یا میلادی را بررسی می‌کند. مقدار نامعتبر باعث حذف
     * Detail نمی‌شود تا خطای داده، فروش محصول دارای موجودی را بی‌دلیل مسدود نکند.
     */
    private static function is_detail_expired($properties, array $date_property_codes)
    {
        if (empty($properties) || empty($date_property_codes)) {
            return false;
        }

        if (is_string($properties)) {
            $properties = json_decode($properties, true);
        }

        if (!is_array($properties)) {
            return false;
        }

        foreach ($properties as $property) {
            $code = (int)($property['C'] ?? 0);

            if (!in_array($code, $date_property_codes, true)) {
                continue;
            }

            $expiration = self::parse_date_timestamp($property['V'] ?? '');

            if ($expiration !== null && $expiration < strtotime('today', current_time('timestamp'))) {
                return true;
            }
        }

        return false;
    }

    private static function parse_date_timestamp($value)
    {
        $value = trim((string)$value);

        if ($value === '') {
            return null;
        }

        $normalized = str_replace(['.', '-'], '/', $value);
        $year = (int)substr($normalized, 0, 4);

        if ($year > 0 && $year < 1700 && function_exists('jalali_to_timestamp')) {
            $timestamp = jalali_to_timestamp($normalized);
        } else {
            $timestamp = strtotime($value);
        }

        return $timestamp === false ? null : (int)$timestamp;
    }

    /**
     * منطق انتخاب انبار را مطابق تنظیمات فعلی همگام‌سازی موجودی نگه می‌دارد.
     */
    private function get_allowed_store_ids()
    {
        $visitor_options = get_bazara_visitor_options();
        $visitor_settings = get_bazara_visitor_settings();
        $priority_store_ids = self::normalize_store_ids(
            $visitor_settings['StoresSortOrder'] ?? []
        );

        if (!empty($visitor_settings['StorePriorityToggle']) && !empty($priority_store_ids)) {
            return $priority_store_ids;
        }

        $store_id = (int)($visitor_options['StoreID'] ?? 0);

        return $store_id > 0 ? [$store_id] : [];
    }

    /**
     * مقدار تمام سفارش‌های تبدیل‌نشده را با یک query گروهی دریافت می‌کند.
     */
    private function get_pending_quantity_map(array $detail_ids)
    {
        if (empty($detail_ids)) {
            return [];
        }

        $settings = get_bazara_visitor_settings();
        $minimum_order_id = (int)($settings['order_id_greater_than'] ?? 0);
        $placeholders = implode(',', array_fill(0, count($detail_ids), '%d'));
        $query_args = array_merge($detail_ids, [$minimum_order_id]);
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT od.ProductDetailId, IFNULL(SUM(od.Count1), 0) AS quantity
                FROM {$this->wpdb->prefix}bazara_orders o
                INNER JOIN {$this->wpdb->prefix}bazara_order_details od
                    ON od.OrderId = o.OrderId
                WHERE o.orderCode = 0
                    AND od.ProductDetailId IN ({$placeholders})
                    AND o.Deleted = 0
                    AND od.Deleted = 0
                    AND o.OrderClientId >= %d
                GROUP BY od.ProductDetailId",
                ...$query_args
            ),
            ARRAY_A
        );
        $pending_quantity_map = [];

        foreach ($rows as $row) {
            $pending_quantity_map[(int)$row['ProductDetailId']] = (float)$row['quantity'];
        }

        return $pending_quantity_map;
    }
}
