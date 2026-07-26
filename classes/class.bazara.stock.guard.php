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
     * کل سبد را با یک refresh افزایشی و queryهای گروهی اعتبارسنجی می‌کند.
     */
    public function validate_cart(array $cart_items)
    {
        if (empty($cart_items) || !$this->api->quick_fetch_stock_changes()) {
            return [];
        }

        $cart_requests = $this->collect_cart_requests($cart_items);

        if (empty($cart_requests)) {
            return [];
        }

        $products_by_sku = $this->get_products_by_sku(array_keys($cart_requests));

        if (empty($products_by_sku)) {
            return [];
        }

        $details_by_product = $this->get_active_details_by_product(
            array_column($products_by_sku, 'ProductId')
        );
        $detail_ids = [];

        foreach ($details_by_product as $product_detail_ids) {
            $detail_ids = array_merge($detail_ids, $product_detail_ids);
        }

        $detail_ids = array_values(array_unique(array_map('intval', $detail_ids)));
        $stock_map = self::build_stock_map(
            $this->get_assets($detail_ids),
            $this->get_allowed_store_ids()
        );
        $pending_quantity_map = $this->get_pending_quantity_map($detail_ids);
        $errors = [];

        foreach ($cart_requests as $sku => $request) {
            if (!isset($products_by_sku[$sku])) {
                continue;
            }

            $product_id = (int)$products_by_sku[$sku]['ProductId'];
            $available_stock = self::calculate_available_stock(
                $details_by_product[$product_id] ?? [],
                $stock_map,
                $pending_quantity_map
            );

            if ($available_stock < $request['quantity']) {
                $errors[] = [
                    'name' => $request['name'],
                    'available_stock' => $available_stock,
                    'requested_quantity' => $request['quantity'],
                ];
            }
        }

        return $errors;
    }

    /**
     * اقلام تکراری یک SKU را تجمیع می‌کند تا هر محصول فقط یک‌بار بررسی شود.
     */
    private function collect_cart_requests(array $cart_items)
    {
        $requests = [];

        foreach ($cart_items as $cart_item) {
            $product_id = (int)($cart_item['product_id'] ?? 0);
            $variation_id = (int)($cart_item['variation_id'] ?? 0);
            $target_id = $variation_id > 0 ? $variation_id : $product_id;
            $product = $cart_item['data'] ?? null;

            if (!$product || !is_object($product) || !method_exists($product, 'get_sku')) {
                $product = wc_get_product($target_id);
            }

            if (!$product) {
                continue;
            }

            $sku = trim((string)$product->get_sku());

            if ($sku === '' && $variation_id > 0) {
                $parent_product = wc_get_product($product_id);
                $sku = $parent_product ? trim((string)$parent_product->get_sku()) : '';
            }

            if ($sku === '') {
                continue;
            }

            if (!isset($requests[$sku])) {
                $requests[$sku] = [
                    'name' => $product->get_name(),
                    'quantity' => 0.0,
                ];
            }

            $requests[$sku]['quantity'] += (float)($cart_item['quantity'] ?? 0);
        }

        return $requests;
    }

    /**
     * تمام ProductIdهای مورد نیاز سبد را در یک query پیدا می‌کند.
     */
    private function get_products_by_sku(array $skus)
    {
        $skus = array_values(array_unique(array_filter(array_map('strval', $skus))));

        if (empty($skus)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($skus), '%s'));
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT ProductId, ProductCode
                FROM {$this->wpdb->prefix}bazara_products
                WHERE ProductCode IN ({$placeholders}) AND Deleted = 0",
                ...$skus
            ),
            ARRAY_A
        );
        $products = [];

        foreach ($rows as $row) {
            $products[(string)$row['ProductCode']] = $row;
        }

        return $products;
    }

    /**
     * Detailهای فعال را گروهی می‌خواند و بچ‌های منقضی را کنار می‌گذارد.
     */
    private function get_active_details_by_product(array $product_ids)
    {
        $product_ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));

        if (empty($product_ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
        $details = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT ProductId, ProductDetailId, Properties
                FROM {$this->wpdb->prefix}bazara_product_details
                WHERE ProductId IN ({$placeholders}) AND Deleted = 0",
                ...$product_ids
            ),
            ARRAY_A
        );
        $date_property_codes = array_map(
            'intval',
            $this->wpdb->get_col(
                "SELECT PropertyDescriptionCode
                FROM {$this->wpdb->prefix}bazara_product_properties
                WHERE DataType = " . (int)BAZARA_PROPERTY_DATE_TYPE . " AND Deleted = 0"
            )
        );
        $details_by_product = [];

        foreach ($details as $detail) {
            if ($this->is_detail_expired($detail['Properties'] ?? '', $date_property_codes)) {
                continue;
            }

            $product_id = (int)$detail['ProductId'];
            $details_by_product[$product_id][] = (int)$detail['ProductDetailId'];
        }

        return $details_by_product;
    }

    /**
     * تاریخ‌های معتبر شمسی یا میلادی را بررسی می‌کند. مقدار نامعتبر باعث حذف
     * Detail نمی‌شود تا خطای داده، فروش محصول دارای موجودی را بی‌دلیل مسدود نکند.
     */
    private function is_detail_expired($properties, array $date_property_codes)
    {
        if (empty($properties) || empty($date_property_codes)) {
            return false;
        }

        $properties = json_decode($properties, true);

        if (!is_array($properties)) {
            return false;
        }

        foreach ($properties as $property) {
            $code = (int)($property['C'] ?? 0);

            if (!in_array($code, $date_property_codes, true)) {
                continue;
            }

            $expiration = $this->parse_date_timestamp($property['V'] ?? '');

            if ($expiration !== null && $expiration < strtotime('today', current_time('timestamp'))) {
                return true;
            }
        }

        return false;
    }

    private function parse_date_timestamp($value)
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

    private function get_assets(array $detail_ids)
    {
        if (empty($detail_ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($detail_ids), '%d'));

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT ProductDetailId, StoreId, Count1, Deleted
                FROM {$this->wpdb->prefix}bazara_product_assets
                WHERE ProductDetailId IN ({$placeholders})",
                ...$detail_ids
            ),
            ARRAY_A
        );
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
