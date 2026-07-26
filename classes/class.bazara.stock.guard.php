<?php

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

final class Bazara_Stock_Guard
{
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
}
