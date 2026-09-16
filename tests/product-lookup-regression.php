<?php

define('ABSPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

$GLOBALS['bazara_lookup_test_request'] = [];

function bazara_get_options()
{
    return [];
}

function get_bazara_visitor_options()
{
    return [];
}

function get_bazara_visitor_settings()
{
    return [];
}

function get_latest_versions()
{
    return [];
}

function bazara_visitor_soft_settings()
{
    return [];
}

function add_query_arg($params, $url)
{
    return $url . '?' . http_build_query($params);
}

function wp_remote_get($url, $args)
{
    $GLOBALS['bazara_lookup_test_request'] = [
        'url' => $url,
        'args' => $args,
    ];

    return [
        'response' => ['code' => 200],
        'body' => json_encode([
            'Result' => true,
            'Data' => [
                'Objects' => [
                    'Product' => [
                        'ProductId' => 10074062,
                        'Deleted' => false,
                    ],
                ],
            ],
        ]),
    ];
}

function is_wp_error($response)
{
    return false;
}

function wp_remote_retrieve_response_code($response)
{
    return $response['response']['code'];
}

function wp_remote_retrieve_body($response)
{
    return $response['body'];
}

require_once dirname(__DIR__) . '/classes/class.bazara.api.php';

function bazara_lookup_assert_same($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$api = new BazaraApi(true);
$result = $api->lookup_product_info(10074062, 'test-token');

bazara_lookup_assert_same(
    'https://mahakacc.mahaksoft.com/API/v3/Lookup/GetProductInfo?productId=10074062',
    $GLOBALS['bazara_lookup_test_request']['url'],
    'آدرس درخواست Lookup باید با Base URL نسخه سه ساخته شود.'
);

bazara_lookup_assert_same(
    'Bearer test-token',
    $GLOBALS['bazara_lookup_test_request']['args']['headers']['Authorization'],
    'توکن Lookup باید با هدر استاندارد Bearer ارسال شود.'
);

bazara_lookup_assert_same(
    10074062,
    $result['message']['Product']['ProductId'] ?? null,
    'پاسخ معتبر Lookup باید به guard برگردانده شود.'
);

echo "Product lookup regression tests passed.\n";
