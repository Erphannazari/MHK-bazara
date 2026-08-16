<?php

require_once dirname(__DIR__) . '/classes/class.bazara.stock.guard.php';

function bazara_stock_guard_assert_same($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(
            STDERR,
            sprintf(
                "%s\nExpected: %s\nActual: %s\n",
                $message,
                var_export($expected, true),
                var_export($actual, true)
            )
        );
        exit(1);
    }
}

$assets = [
    [
        'ProductDetailId' => 101,
        'StoreId' => 1,
        'Count1' => 2,
        'Deleted' => 0,
    ],
    [
        'ProductDetailId' => 101,
        'StoreId' => 2,
        'Count1' => 3,
        'Deleted' => 0,
    ],
    [
        'ProductDetailId' => 102,
        'StoreId' => 1,
        'Count1' => 5,
        'Deleted' => 1,
    ],
];

$stock_map = Bazara_Stock_Guard::build_stock_map($assets, [1]);

bazara_stock_guard_assert_same(
    [101 => 2.0],
    $stock_map,
    'موجودی باید فقط از انبارهای مجاز و Assetهای حذف‌نشده محاسبه شود.'
);

bazara_stock_guard_assert_same(
    1.0,
    Bazara_Stock_Guard::calculate_available_stock(
        [101, 102],
        [101 => 2.0, 102 => 4.0],
        [101 => 1.0, 102 => 4.0]
    ),
    'سفارش‌های تبدیل‌نشده باید فقط یک‌بار از موجودی هر Detail کسر شوند.'
);

bazara_stock_guard_assert_same(
    0.0,
    Bazara_Stock_Guard::calculate_available_stock(
        [101],
        [101 => 1.0],
        [101 => 3.0]
    ),
    'موجودی قابل فروش نباید منفی شود.'
);

bazara_stock_guard_assert_same(
    [3, 1],
    Bazara_Stock_Guard::normalize_store_ids('3, 1, 3, invalid'),
    'شناسه انبارها باید عددی، یکتا و با ترتیب تنظیم‌شده باقی بمانند.'
);

echo "Stock guard regression tests passed.\n";
