<?php

define('BAZARA_PROPERTY_DATE_TYPE', 5);
define('ARRAY_A', 'ARRAY_A');

require_once dirname(__DIR__) . '/classes/class.bazara.stock.guard.php';

$GLOBALS['bazara_stock_guard_test_meta'] = [];
$GLOBALS['bazara_stock_guard_test_visitor_options'] = ['StoreID' => 26996];
$GLOBALS['bazara_stock_guard_test_visitor_settings'] = [
    'StorePriorityToggle' => false,
    'StoresSortOrder' => [],
    'order_id_greater_than' => 0,
];

function get_post_meta($post_id, $key, $single = false)
{
    return $GLOBALS['bazara_stock_guard_test_meta'][$post_id][$key] ?? '';
}

function update_meta_cache($meta_type, $object_ids)
{
    return true;
}

function wc_get_product($product_id)
{
    return null;
}

function get_bazara_visitor_options()
{
    return $GLOBALS['bazara_stock_guard_test_visitor_options'];
}

function get_bazara_visitor_settings()
{
    return $GLOBALS['bazara_stock_guard_test_visitor_settings'];
}

function current_time($type)
{
    return time();
}

final class Bazara_Stock_Guard_Test_Product
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function get_name()
    {
        return $this->name;
    }
}

final class Bazara_Stock_Guard_Test_Api
{
    public $lookup_calls = [];
    private $responses;

    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function login_token()
    {
        return ['success' => true, 'message' => 'test-token'];
    }

    public function lookup_product_info($product_id, $token = '')
    {
        $this->lookup_calls[] = $product_id;

        return $this->responses[$product_id] ?? [
            'success' => false,
            'message' => 'Not found',
        ];
    }
}

final class Bazara_Stock_Guard_Test_Wpdb
{
    public $prefix = 'wp_';
    private $pending_rows;

    public function __construct(array $pending_rows = [])
    {
        $this->pending_rows = $pending_rows;
    }

    public function prepare($query, ...$args)
    {
        return $query;
    }

    public function get_results($query, $output)
    {
        return $this->pending_rows;
    }
}

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

$variable_lookup = [
    'Product' => [
        'ProductId' => 10074062,
        'Deleted' => false,
    ],
    'ProductDetails' => [
        [
            'ProductDetailId' => 11419407,
            'Properties' => '[{"C":4,"V":"24"}]',
            'Deleted' => false,
        ],
        [
            'ProductDetailId' => 11419408,
            'Properties' => '[{"C":4,"V":"32"}]',
            'Deleted' => false,
        ],
        [
            'ProductDetailId' => 11419409,
            'Properties' => null,
            'Deleted' => false,
        ],
    ],
    'ProductDetailStoreAssets' => [
        [
            'ProductDetailId' => 11419407,
            'StoreId' => 26996,
            'Count1' => 10,
            'Deleted' => false,
        ],
        [
            'ProductDetailId' => 11419408,
            'StoreId' => 26996,
            'Count1' => 20,
            'Deleted' => false,
        ],
        [
            'ProductDetailId' => 11419409,
            'StoreId' => 26996,
            'Count1' => 0,
            'Deleted' => false,
        ],
    ],
    'PropertyDescriptions' => [
        [
            'PropertyDescriptionCode' => 4,
            'DataType' => 4,
            'Deleted' => false,
        ],
    ],
];

bazara_stock_guard_assert_same(
    [11419407, 11419408, 11419409],
    Bazara_Stock_Guard::get_active_detail_ids(
        $variable_lookup['ProductDetails'],
        $variable_lookup['PropertyDescriptions']
    ),
    'ویژگی سایز با DataType چهار نباید به‌عنوان تاریخ انقضا حذف شود.'
);

$GLOBALS['bazara_stock_guard_test_meta'] = [
    501 => ['mahak_id' => 10074062],
    601 => ['mahak_product_detail_id' => 11419407],
    602 => ['mahak_product_detail_id' => 11419408],
];
$variable_api = new Bazara_Stock_Guard_Test_Api([
    10074062 => ['success' => true, 'message' => $variable_lookup],
]);
$variable_guard = new Bazara_Stock_Guard(
    $variable_api,
    new Bazara_Stock_Guard_Test_Wpdb()
);
$variable_errors = $variable_guard->validate_cart([
    [
        'product_id' => 501,
        'variation_id' => 601,
        'quantity' => 25,
        'data' => new Bazara_Stock_Guard_Test_Product('پیتزا سایز ۲۴'),
    ],
    [
        'product_id' => 501,
        'variation_id' => 602,
        'quantity' => 15,
        'data' => new Bazara_Stock_Guard_Test_Product('پیتزا سایز ۳۲'),
    ],
]);

bazara_stock_guard_assert_same(
    [
        [
            'name' => 'پیتزا سایز ۲۴',
            'available_stock' => 10.0,
            'requested_quantity' => 25.0,
            'reason' => 'insufficient_stock',
        ],
    ],
    $variable_errors,
    'درخواست ۲۵ عدد از Detail دارای موجودی ۱۰ نباید با موجودی مجموع واریشن‌ها قبول شود.'
);

bazara_stock_guard_assert_same(
    [10074062],
    $variable_api->lookup_calls,
    'واریشن‌های یک محصول باید فقط یک درخواست Lookup ایجاد کنند.'
);

$simple_lookup = [
    'Product' => [
        'ProductId' => 10074041,
        'Deleted' => false,
    ],
    'ProductDetails' => [
        [
            'ProductDetailId' => 11419384,
            'Properties' => null,
            'Deleted' => false,
        ],
    ],
    'ProductDetailStoreAssets' => [
        [
            'ProductDetailId' => 11419384,
            'StoreId' => 26996,
            'Count1' => 75,
            'Deleted' => false,
        ],
    ],
    'PropertyDescriptions' => [],
];

$GLOBALS['bazara_stock_guard_test_meta'] = [
    502 => [
        'mahak_id' => 10074041,
        'mahak_product_detail_id' => 11419384,
    ],
];
$simple_guard = new Bazara_Stock_Guard(
    new Bazara_Stock_Guard_Test_Api([
        10074041 => ['success' => true, 'message' => $simple_lookup],
    ]),
    new Bazara_Stock_Guard_Test_Wpdb()
);
$simple_errors = $simple_guard->validate_cart([
    [
        'product_id' => 502,
        'variation_id' => 0,
        'quantity' => 76,
        'data' => new Bazara_Stock_Guard_Test_Product('ماکارونی'),
    ],
]);

bazara_stock_guard_assert_same(
    75.0,
    $simple_errors[0]['available_stock'] ?? null,
    'محصول ساده باید از Detail خودش و موجودی ۷۵ عددی پاسخ Lookup بررسی شود.'
);

$failed_lookup_guard = new Bazara_Stock_Guard(
    new Bazara_Stock_Guard_Test_Api([]),
    new Bazara_Stock_Guard_Test_Wpdb()
);

bazara_stock_guard_assert_same(
    [['reason' => 'verification_failed']],
    $failed_lookup_guard->validate_cart([
        [
            'product_id' => 502,
            'variation_id' => 0,
            'quantity' => 1,
            'data' => new Bazara_Stock_Guard_Test_Product('ماکارونی'),
        ],
    ]),
    'شکست Lookup باید checkout را به‌صورت fail-closed متوقف کند.'
);

echo "Stock guard regression tests passed.\n";
