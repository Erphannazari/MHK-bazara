<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Bazara_Unsynced_Products_Table extends WP_List_Table {

    private $api_data_cache = null;

    // ═══════════════ تنظیمات سینک ═══════════════
    private $sync_settings = [
            'title'       => false,  // chkTitle - نام محصول
            'price'       => false,  // chkPrice - قیمت
            'stock'       => false,  // chkQuantity - موجودی
            'image'       => false,  // chkPicture - تصاویر
            'description' => false,  // description - توضیحات
            'product'     => false,  // chkProduct - محصول (کلی)
    ];

    public function __construct() {
        parent::__construct([
                'singular' => __('Product', 'sp'),
                'plural'   => __('Products', 'sp'),
                'ajax'     => false
        ]);

        // بارگذاری تنظیمات سینک
        $this->load_sync_settings();
    }

    /**
     * بارگذاری تنظیمات سینک از دیتابیس
     * کلیدهای واقعی: chkTitle, chkPrice, chkQuantity, chkPicture, description
     */
    private function load_sync_settings() {
        $options = get_option('bazara_visitor_settings', []);

        $this->sync_settings = [
                'title'       => !empty($options['chkTitle']),      // نام محصول
                'price'       => !empty($options['chkPrice']),      // قیمت
                'stock'       => !empty($options['chkQuantity']),   // موجودی
                'image'       => !empty($options['chkPicture']),    // تصاویر
                'description' => !empty($options['description']),   // توضیحات
                'product'     => !empty($options['chkProduct']),    // محصول کلی
        ];
    }

    /**
     * بررسی فعال بودن یک نوع سینک
     */
    public function is_sync_enabled($type) {
        return isset($this->sync_settings[$type]) && $this->sync_settings[$type];
    }

    public function get_columns() {
        $columns = [
                'cb'          => '<input type="checkbox" />',
                'ProductCode' => __('کد محصول', 'sp'),
                'ProductName' => __('نام محصول', 'sp'),
        ];

        // ستون‌های شرطی بر اساس تنظیمات سینک
        if ($this->is_sync_enabled('title')) {
            $columns['name_comparison'] = __('مقایسه نام', 'sp');
        }

        if ($this->is_sync_enabled('price')) {
            $columns['price_comparison'] = __('مقایسه قیمت', 'sp');
        }

        if ($this->is_sync_enabled('stock')) {
            $columns['stock_comparison'] = __('مقایسه موجودی', 'sp');
        }

        if ($this->is_sync_enabled('description') || $this->is_sync_enabled('title')) {
            $columns['detail_status'] = __('وضعیت جزئیات', 'sp');
        }

        if ($this->is_sync_enabled('image')) {
            $columns['image_status'] = __('تصویر', 'sp');
        }

        $columns['mismatch_reason'] = __('دلیل مغایرت', 'sp');

        return $columns;
    }

    protected function extra_tablenav($which) {
        if ($which == 'top') {
            $source_a = isset($_REQUEST['source_a']) ? $_REQUEST['source_a'] : 'db';

            if ($source_a === 'api') {
                $source_b = 'db';
            } elseif ($source_a === 'db') {
                $source_b = 'woo';
            } else {
                $source_b = isset($_REQUEST['source_b']) ? $_REQUEST['source_b'] : 'woo';
            }

            $api_token = isset($_REQUEST['mahak_api_token']) ? $_REQUEST['mahak_api_token'] : '';
            ?>
            <div class="alignleft actions" style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px; flex-wrap: wrap; background: #fff; padding: 8px; border: 1px solid #ddd;">
                <span style="font-weight: bold; color: #2271b1;">منبع A (مبنا):</span>
                <select name="source_a" id="source_a">
                    <option value="db" <?php selected($source_a, 'db'); ?>>دیتابیس افزونه (Local DB)</option>
                    <option value="api" <?php selected($source_a, 'api'); ?>>API محک (Live)</option>
                </select>

                <span class="dashicons dashicons-arrow-left-alt2" style="margin-top: 5px;"></span>

                <span style="font-weight: bold; color: #d63638;">منبع B (مقصد):</span>
                <select name="source_b" id="source_b">
                </select>

                <input type="text" name="mahak_api_token" id="mahak_api_token"
                       value="<?php echo esc_attr($api_token); ?>"
                       placeholder="توکن API (Bearer)"
                       style="width: 200px; display: none;">

                <input type="submit" name="filter_action" class="button button-primary" value="بررسی مغایرت‌ها">
            </div>

            <!-- ═══════════════ نوار وضعیت تنظیمات سینک ═══════════════ -->
            <div class="alignleft" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 12px 15px; border: 1px solid #dee2e6; border-radius: 6px; margin: 10px 0; width: 100%; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <span style="font-weight: bold; color: #495057; margin-left: 15px;">
                    <span class="dashicons dashicons-admin-settings" style="color: #6c757d;"></span>
                    وضعیت تنظیمات سینک:
                </span>

                <span style="margin: 0 8px; padding: 4px 10px; border-radius: 4px; font-size: 12px; <?php echo $this->is_sync_enabled('title') ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
                    <?php echo $this->is_sync_enabled('title') ? '✅' : '❌'; ?> نام
                </span>

                <span style="margin: 0 8px; padding: 4px 10px; border-radius: 4px; font-size: 12px; <?php echo $this->is_sync_enabled('price') ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
                    <?php echo $this->is_sync_enabled('price') ? '✅' : '❌'; ?> قیمت
                </span>

                <span style="margin: 0 8px; padding: 4px 10px; border-radius: 4px; font-size: 12px; <?php echo $this->is_sync_enabled('stock') ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
                    <?php echo $this->is_sync_enabled('stock') ? '✅' : '❌'; ?> موجودی
                </span>

                <span style="margin: 0 8px; padding: 4px 10px; border-radius: 4px; font-size: 12px; <?php echo $this->is_sync_enabled('image') ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
                    <?php echo $this->is_sync_enabled('image') ? '✅' : '❌'; ?> تصویر
                </span>

                <span style="margin: 0 8px; padding: 4px 10px; border-radius: 4px; font-size: 12px; <?php echo $this->is_sync_enabled('description') ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
                    <?php echo $this->is_sync_enabled('description') ? '✅' : '❌'; ?> توضیحات
                </span>

                <!--                <a href="--><?php //echo admin_url('admin.php?page=bazara-settings'); ?><!--"-->
                <!--                   style="margin-right: 15px; color: #007bff; text-decoration: none; font-size: 12px;">-->
                <!--                    <span class="dashicons dashicons-admin-generic" style="font-size: 14px; vertical-align: middle;"></span>-->
                <!--                    تغییر تنظیمات-->
                <!--                </a>-->
            </div>

            <script>
                jQuery(document).ready(function($){
                    var srcA = $('#source_a');
                    var srcB = $('#source_b');
                    var tokenInput = $('#mahak_api_token');
                    var currentB = '<?php echo esc_js($source_b); ?>';

                    function updateSourceB() {
                        var valA = srcA.val();
                        srcB.empty();
                        if (valA === 'api') {
                            srcB.append('<option value="db">دیتابیس افزونه (Local DB)</option>');
                            tokenInput.fadeIn();
                        } else if (valA === 'db') {
                            srcB.append('<option value="woo">ووکامرس (WooCommerce)</option>');
                            tokenInput.hide();
                        }
                        if (srcB.find("option[value='" + currentB + "']").length > 0) {
                            srcB.val(currentB);
                        }
                    }

                    srcA.change(function() {
                        currentB = '';
                        updateSourceB();
                    });
                    updateSourceB();
                });
            </script>
            <?php
        }
    }

    public function get_bulk_actions() {
        $actions = [];

        // فقط اکشن‌های مرتبط با سینک‌های فعال را نمایش بده
        if ($this->is_sync_enabled('title') || $this->is_sync_enabled('description')) {
            $actions['bulk-detailSync'] = 'سینک جزئیات کامل (نام، اسلاگ، توضیحات، وزن، ابعاد)';
        }

        if ($this->is_sync_enabled('stock')) {
            $actions['bulk-stockSync'] = 'سینک موجودی';
        }

        if ($this->is_sync_enabled('price')) {
            $actions['bulk-priceSync'] = 'سینک قیمت';
        }

        if ($this->is_sync_enabled('image')) {
            $actions['bulk-pictureSync'] = 'سینک تصاویر';
        }

        // اگر حداقل یک سینک فعال باشد، گزینه "همه" را نمایش بده
        if (!empty($actions)) {
            $actions['bulk-all'] = 'سینک همه موارد فعال';
        }

        return $actions;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════════════
     * تابع اصلی prepare_items - با فیلتر Deleted=1
     * ═══════════════════════════════════════════════════════════════════════════
     */
    public function prepare_items() {
        $this->process_bulk_action();

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        global $wpdb;

        $source_a_type = isset($_REQUEST['source_a']) ? $_REQUEST['source_a'] : 'db';
        $source_b_type = isset($_REQUEST['source_b']) ? $_REQUEST['source_b'] : 'woo';
        $api_token     = isset($_REQUEST['mahak_api_token']) ? $_REQUEST['mahak_api_token'] : '';

        // ═══════════════════════════════════════════════════════════════════════════
        // 🔴 تغییر اصلی: فیلتر کردن محصولات حذف‌شده (Deleted=1)
        // فقط محصولاتی که Deleted=0 یا Deleted IS NULL هستند بارگذاری می‌شوند
        // ═══════════════════════════════════════════════════════════════════════════
        $all_products = $wpdb->get_results(
                "SELECT ProductCode, ProductName, Post_ID, ProductId, Deleted 
             FROM {$wpdb->prefix}bazara_products 
             WHERE (Deleted = 0 OR Deleted IS NULL)"
        );

        $data = [];

        foreach ($all_products as $p_row) {
            $sku = trim($p_row->ProductCode);
            if (empty($sku)) continue;

            // ═══════════════════════════════════════════════════════════════════════════
            // 🔴 بررسی دوباره برای اطمینان (در صورتی که کوئری فیلتر نکرده باشد)
            // ═══════════════════════════════════════════════════════════════════════════
            if (isset($p_row->Deleted) && $p_row->Deleted == 1) {
                continue;
            }

            $item_a = $this->fetch_single_item_raw($sku, $source_a_type, $api_token);
            $item_b = $this->fetch_single_item_raw($sku, $source_b_type, $api_token);

            // ═══════════════════════════════════════════════════════════════════════════
            // 🔴 بررسی Deleted در آیتم خام دریافتی از منبع A (اگر از DB باشد)
            // ═══════════════════════════════════════════════════════════════════════════
            if ($source_a_type === 'db' && $item_a && isset($item_a->Deleted) && $item_a->Deleted == 1) {
                continue;
            }

            $std_a = $this->extract_standard_attributes($item_a, $source_a_type);
            $std_b = $this->extract_standard_attributes($item_b, $source_b_type);

            $reasons = [];

            if (!$std_b['exists']) {
                $reasons[] = 'عدم وجود در مقصد';
            } else {
                // ═══════════════ مقایسه‌های شرطی بر اساس تنظیمات ═══════════════

                // 1. مقایسه نام (فقط اگر chkTitle فعال باشد)
                if ($this->is_sync_enabled('title')) {
                    if ($this->normalize_string($std_a['name']) !== $this->normalize_string($std_b['name'])) {
                        $reasons[] = 'نام';
                    }

                    // مقایسه اسلاگ (مرتبط با نام)
                    if ($this->normalize_string($std_a['slug']) !== $this->normalize_string($std_b['slug'])) {
                        if (!empty($std_a['slug']) || !empty($std_b['slug'])) {
                            $reasons[] = 'اسلاگ';
                        }
                    }
                }

                // 2. مقایسه توضیحات (فقط اگر description فعال باشد)
                if ($this->is_sync_enabled('description')) {
                    $desc_a = $this->normalize_string($std_a['description']);
                    $desc_b = $this->normalize_string($std_b['description']);
                    if ($desc_a !== $desc_b) {
                        $reasons[] = 'توضیحات';
                    }
                }

                // 3. مقایسه وزن و ابعاد (مرتبط با جزئیات)
                if ($this->is_sync_enabled('title') || $this->is_sync_enabled('description')) {
                    if (abs($std_a['weight'] - $std_b['weight']) > 0.01) {
                        $reasons[] = 'وزن';
                    }

                    if (
                            abs($std_a['length'] - $std_b['length']) > 0.1 ||
                            abs($std_a['width']  - $std_b['width'])  > 0.1 ||
                            abs($std_a['height'] - $std_b['height']) > 0.1
                    ) {
                        $reasons[] = 'ابعاد';
                    }
                }

                // 4. مقایسه قیمت (فقط اگر chkPrice فعال باشد)
                if ($this->is_sync_enabled('price')) {
                    if (abs($std_a['normalized_price_rial'] - $std_b['normalized_price_rial']) > 10) {
                        $reasons[] = 'قیمت';
                    }
                }

                // 5. مقایسه موجودی (فقط اگر chkQuantity فعال باشد)
                if ($this->is_sync_enabled('stock')) {
                    if ($std_a['stock'] != $std_b['stock']) {
                        $reasons[] = 'موجودی';
                    }
                }

                // 6. مقایسه تصاویر (فقط اگر chkPicture فعال باشد)
                if ($this->is_sync_enabled('image')) {
                    if (count($std_a['images']) != count($std_b['images'])) {
                        $reasons[] = 'تصویر';
                    }
                }
            }

            if (!empty($reasons)) {
                $data[] = [
                        'ProductCode'     => $sku,
                        'std_a'           => $std_a,
                        'std_b'           => $std_b,
                        'mismatch_reason' => implode('، ', $reasons)
                ];
            }
        }

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

        $this->items = $data;
        $this->set_pagination_args([
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    // ═══════════════ ستون‌ها ═══════════════

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="sync_items[]" value="%s" />', $item['ProductCode']);
    }

    function column_ProductCode($item) {
        return '<b style="direction:ltr; display:inline-block;">' . esc_html($item['ProductCode']) . '</b>';
    }

    function column_ProductName($item) {
        return '<span style="font-weight:bold;">' . esc_html($item['std_a']['name']) . '</span>';
    }

    function column_name_comparison($item) {
        if (!$this->is_sync_enabled('title')) {
            return '<span style="color:#aaa">غیرفعال</span>';
        }

        $a = $item['std_a']['name'];
        $b = $item['std_b']['name'];
        if ($this->normalize_string($a) === $this->normalize_string($b)) {
            return '<span style="color:#aaa">---</span>';
        }
        return sprintf(
                '<div style="margin-bottom:5px;"><span style="color:#2271b1; font-weight:bold;">A:</span> %s</div><div><span style="color:#d63638; font-weight:bold;">B:</span> %s</div>',
                esc_html($a),
                esc_html($b)
        );
    }

    function column_price_comparison($item) {
        if (!$this->is_sync_enabled('price')) {
            return '<span style="color:#aaa">غیرفعال</span>';
        }

        $a_rial = $item['std_a']['normalized_price_rial'];
        $b_rial = $item['std_b']['normalized_price_rial'];
        if (abs($a_rial - $b_rial) <= 10) {
            return '<span style="color:#aaa">---</span>';
        }
        $show_a = number_format($item['std_a']['display_price']) . ' ' . $item['std_a']['currency_label'];
        $show_b = number_format($item['std_b']['display_price']) . ' ' . $item['std_b']['currency_label'];
        return sprintf(
                '<div style="margin-bottom:5px;"><span style="color:#2271b1; font-weight:bold;">A:</span> %s</div><div><span style="color:#d63638; font-weight:bold;">B:</span> %s</div>',
                $show_a,
                $show_b
        );
    }

    function column_stock_comparison($item) {
        if (!$this->is_sync_enabled('stock')) {
            return '<span style="color:#aaa">غیرفعال</span>';
        }

        $a = $item['std_a']['stock'];
        $b = $item['std_b']['stock'];
        if ($a == $b) {
            return '<span style="color:#aaa">---</span>';
        }
        return sprintf(
                '<div style="margin-bottom:5px;"><span style="color:#2271b1; font-weight:bold;">A:</span> %s</div><div><span style="color:#d63638; font-weight:bold;">B:</span> %s</div>',
                $a,
                $b
        );
    }

    function column_detail_status($item) {
        $diffs = [];

        if ($this->is_sync_enabled('description')) {
            if ($this->normalize_string($item['std_a']['description']) !== $this->normalize_string($item['std_b']['description'])) {
                $diffs[] = 'توضیحات';
            }
        }

        if ($this->is_sync_enabled('title')) {
            if ($this->normalize_string($item['std_a']['slug']) !== $this->normalize_string($item['std_b']['slug'])) {
                $diffs[] = 'اسلاگ';
            }
        }

        if ($this->is_sync_enabled('title') || $this->is_sync_enabled('description')) {
            if (abs($item['std_a']['weight'] - $item['std_b']['weight']) > 0.01) {
                $diffs[] = 'وزن';
            }
        }

        if (empty($diffs)) {
            return '<span style="color:#aaa">---</span>';
        }
        return '<span style="color:#d63638">مغایرت: ' . implode('، ', $diffs) . '</span>';
    }

    function column_image_status($item) {
        if (!$this->is_sync_enabled('image')) {
            return '<span style="color:#aaa">غیرفعال</span>';
        }

        $a_count = count($item['std_a']['images']);
        $b_count = count($item['std_b']['images']);

        if ($a_count == $b_count) {
            return '<span style="color:#aaa">---</span>';
        }

        return sprintf(
                '<span style="color:#d63638">A: %d تصویر | B: %d تصویر</span>',
                $a_count,
                $b_count
        );
    }

    function column_mismatch_reason($item) {
        return '<span style="color:#d63638; font-weight:bold;">' . esc_html($item['mismatch_reason']) . '</span>';
    }

    function column_default($item, $column_name) {
        return isset($item[$column_name]) ? $item[$column_name] : '';
    }

    // ═══════════════ توابع کمکی ═══════════════

    private function normalize_string($str) {
        $str = (string)$str;
        $str = strip_tags($str);
        $str = preg_replace('/\s+/', '', $str);
        $str = str_replace(['ی', 'ک', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['ي', 'ك', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $str);
        return strtolower($str);
    }

    private function generate_slug_from_name($name) {
        if (empty($name)) return '';
        $slug = trim($name);
        $slug = preg_replace('/\s+/', '-', $slug);
        $slug = preg_replace('/[^\p{L}\p{N}\-]/u', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return mb_strtolower($slug, 'UTF-8');
    }

    // ═══════════════ دریافت داده ═══════════════

    private function fetch_single_item_raw($sku, $source_type, $api_token = '') {
        global $wpdb;
        if ($source_type === 'woo') {
            $id = wc_get_product_id_by_sku($sku);
            return $id ? wc_get_product($id) : null;
        } elseif ($source_type === 'db') {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bazara_products WHERE ProductCode = %s", $sku));
            return $row ? $row : null;
        } elseif ($source_type === 'api') {
            if ($this->api_data_cache === null) {
                $this->api_data_cache = $this->fetch_all_api_data($api_token);
            }
            return isset($this->api_data_cache[$sku]) ? $this->api_data_cache[$sku] : null;
        }
        return null;
    }

    private function fetch_all_api_data($token) {
        if (empty($token)) return [];
        $url = 'https://mahakacc.mahaksoft.com/API/v3/Sync/GetAllData';
        $payload = [
                "fromBankVersion" => 0, "fromProductVersion" => 0, "fromProductDetailVersion" => 0,
                "fromProductDetailStoreAssetVersion" => 0, "pageSize" => 10000
        ];
        $args = [
                'method' => 'POST', 'timeout' => 45,
                'headers' => ['Authorization' => 'Bearer ' . trim($token), 'Content-Type' => 'application/json; charset=utf-8'],
                'body' => json_encode($payload)
        ];
        $response = wp_remote_post($url, $args);
        $data_map = [];
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
            $body = json_decode(wp_remote_retrieve_body($response));
            if (isset($body->Data->Objects)) {
                $objects = $body->Data->Objects;
                $details_map = [];
                if (!empty($objects->ProductDetails)) {
                    foreach ($objects->ProductDetails as $d) {
                        $details_map[$d->ProductId] = $d;
                    }
                }
                $assets_map = [];
                if (!empty($objects->ProductDetailStoreAssets)) {
                    foreach ($objects->ProductDetailStoreAssets as $a) {
                        if(!isset($assets_map[$a->ProductDetailId])) $assets_map[$a->ProductDetailId] = 0;
                        $assets_map[$a->ProductDetailId] += (float)$a->Count1;
                    }
                }

                if (!empty($objects->Products)) {
                    foreach ($objects->Products as $p) {
                        // ═══════════════════════════════════════════════════════════════════════════
                        // 🔴 فیلتر محصولات حذف‌شده از API
                        // ═══════════════════════════════════════════════════════════════════════════
                        if (isset($p->Deleted) && $p->Deleted == 1) {
                            continue;
                        }

                        $sku = trim($p->ProductCode);
                        if(empty($sku)) continue;

                        $p->CalculatedPrice = 0;
                        $p->CalculatedStock = 0;

                        if (!isset($p->Name) && isset($p->ProductName)) {
                            $p->Name = $p->ProductName;
                        }

                        if(isset($details_map[$p->ProductId])) {
                            $det = $details_map[$p->ProductId];
                            if (isset($det->Price1)) {
                                $p->CalculatedPrice = (float)$det->Price1;
                            }
                            if(isset($assets_map[$det->ProductDetailId])) {
                                $p->CalculatedStock = $assets_map[$det->ProductDetailId];
                            }
                            if (isset($det->Weight)) $p->Weight = $det->Weight;
                            if (isset($det->Description)) $p->Description = $det->Description;
                        }
                        $data_map[$sku] = $p;
                    }
                }
            }
        }
        return $data_map;
    }

    private function extract_standard_attributes($item, $source_type) {
        $attr = [
                'name' => '', 'slug' => '', 'description' => '', 'short_description' => '',
                'weight' => 0, 'length' => 0, 'width' => 0, 'height' => 0,
                'category_id' => 0, 'stock' => 0, 'normalized_price_rial' => 0,
                'display_price' => 0, 'currency_label' => '', 'images' => [], 'exists' => false
        ];

        if (!$item) return $attr;
        $attr['exists'] = true;

        // WOOCOMMERCE
        if ($source_type === 'woo' && is_a($item, 'WC_Product')) {
            $attr['name'] = $item->get_name();
            $attr['slug'] = urldecode($item->get_slug());
            $attr['description'] = $item->get_description();
            $attr['short_description'] = $item->get_short_description();
            $attr['weight'] = (float)$item->get_weight();
            $attr['length'] = (float)$item->get_length();
            $attr['width']  = (float)$item->get_width();
            $attr['height'] = (float)$item->get_height();
            $attr['stock'] = (float)$item->get_stock_quantity();
            $cat_ids = $item->get_category_ids();
            $attr['category_id'] = !empty($cat_ids) ? $cat_ids[0] : 0;
            $price = (float)$item->get_price();
            $attr['display_price'] = $price;
            $curr = get_woocommerce_currency();
            if ($curr == 'IRR') {
                $attr['currency_label'] = 'ریال';
                $attr['normalized_price_rial'] = $price;
            } else {
                $attr['currency_label'] = 'تومان';
                $attr['normalized_price_rial'] = $price * 10;
            }
            if ($item->get_image_id()) $attr['images'][] = $item->get_image_id();
        }
        // DB
        elseif ($source_type === 'db') {
            $attr['name'] = isset($item->ProductName) ? $item->ProductName : (isset($item->Name) ? $item->Name : '');

            $slug = '';
            if (isset($item->Slug) && !empty($item->Slug)) $slug = $item->Slug;
            elseif (isset($item->slug) && !empty($item->slug)) $slug = $item->slug;
            elseif (isset($item->post_name) && !empty($item->post_name)) $slug = $item->post_name;
            if (empty($slug) && !empty($attr['name'])) $slug = $this->generate_slug_from_name($attr['name']);
            $attr['slug'] = $slug;

            if (isset($item->description)) $attr['description'] = $item->description;
            elseif (isset($item->Description)) $attr['description'] = $item->Description;

            $attr['weight'] = isset($item->weight) ? (float)$item->weight : (isset($item->Weight) ? (float)$item->Weight : 0);
            $attr['length'] = isset($item->length) ? (float)$item->length : (isset($item->Length) ? (float)$item->Length : 0);
            $attr['width']  = isset($item->width)  ? (float)$item->width  : (isset($item->Width)  ? (float)$item->Width  : 0);
            $attr['height'] = isset($item->height) ? (float)$item->height : (isset($item->Height) ? (float)$item->Height : 0);
            $attr['category_id'] = isset($item->Category) ? $item->Category : (isset($item->CategoryId) ? $item->CategoryId : 0);

            $details = function_exists('get_product_details_unsynced') ? get_product_details_unsynced($item->ProductId, 0) : [];
            $price = 0;
            $stock = 0;

            if (!empty($details)) {
                $det = $details[0];
                $stock = (float)$det->Count1;
                if (function_exists('get_bazara_not_converted_qty')) {
                    $stock -= get_bazara_not_converted_qty($det->ProductDetailId);
                }
                $prices = json_decode($det->Prices, true);
                $lvl = ($det->DefaultSellPriceLevel == -1 ? 1 : $det->DefaultSellPriceLevel);
                if (isset($prices[$lvl]["Price{$lvl}"])) {
                    $price = (float)$prices[$lvl]["Price{$lvl}"];
                }
            }

            $attr['stock'] = $stock;
            $attr['display_price'] = $price;
            $attr['currency_label'] = 'ریال';
            $attr['normalized_price_rial'] = $price;
        }
        // API
        elseif ($source_type === 'api') {
            if (isset($item->Name) && !empty($item->Name)) $attr['name'] = $item->Name;
            elseif (isset($item->ProductName) && !empty($item->ProductName)) $attr['name'] = $item->ProductName;
            elseif (isset($item->Title) && !empty($item->Title)) $attr['name'] = $item->Title;

            $slug = '';
            if (isset($item->Slug) && !empty($item->Slug)) $slug = $item->Slug;
            elseif (isset($item->slug) && !empty($item->slug)) $slug = $item->slug;
            if (empty($slug) && !empty($attr['name'])) $slug = $this->generate_slug_from_name($attr['name']);
            $attr['slug'] = $slug;

            if (isset($item->Description) && !empty($item->Description)) $attr['description'] = $item->Description;
            elseif (isset($item->description) && !empty($item->description)) $attr['description'] = $item->description;

            $attr['weight'] = isset($item->Weight) ? (float)$item->Weight : (isset($item->weight) ? (float)$item->weight : 0);
            $attr['length'] = isset($item->Length) ? (float)$item->Length : (isset($item->length) ? (float)$item->length : 0);
            $attr['width']  = isset($item->Width)  ? (float)$item->Width  : (isset($item->width)  ? (float)$item->width  : 0);
            $attr['height'] = isset($item->Height) ? (float)$item->Height : (isset($item->height) ? (float)$item->height : 0);

            if (isset($item->ProductCategoryId)) $attr['category_id'] = $item->ProductCategoryId;
            elseif (isset($item->CategoryId)) $attr['category_id'] = $item->CategoryId;

            $price = 0;
            if (isset($item->CalculatedPrice) && $item->CalculatedPrice > 0) $price = (float)$item->CalculatedPrice;
            elseif (isset($item->Price1) && $item->Price1 > 0) $price = (float)$item->Price1;
            elseif (isset($item->Price) && $item->Price > 0) $price = (float)$item->Price;

            $stock = 0;
            if (isset($item->CalculatedStock)) $stock = (float)$item->CalculatedStock;
            elseif (isset($item->Stock)) $stock = (float)$item->Stock;
            elseif (isset($item->Count1)) $stock = (float)$item->Count1;

            $attr['stock'] = $stock;
            $attr['display_price'] = $price;
            $attr['currency_label'] = 'ریال';
            $attr['normalized_price_rial'] = $price;

            if (isset($item->Images) && is_array($item->Images)) $attr['images'] = $item->Images;
            elseif (isset($item->ImageUrl) && !empty($item->ImageUrl)) $attr['images'][] = $item->ImageUrl;
        }

        return $attr;
    }

    // ═══════════════ عملیات سینک ═══════════════

    public function process_bulk_action() {
        $action = $this->current_action();
        if (!$action || (strpos($action, 'bulk-') === false)) return;

        $ids = isset($_POST['sync_items']) ? $_POST['sync_items'] : [];
        if (empty($ids)) return;

        $sync_type = str_replace('bulk-', '', $action);

        // ═══════════════ بررسی مجوز سینک بر اساس تنظیمات ═══════════════
        if ($sync_type === 'detailSync' && !$this->is_sync_enabled('title') && !$this->is_sync_enabled('description')) {
            return;
        }
        if ($sync_type === 'stockSync' && !$this->is_sync_enabled('stock')) {
            return;
        }
        if ($sync_type === 'priceSync' && !$this->is_sync_enabled('price')) {
            return;
        }
        if ($sync_type === 'pictureSync' && !$this->is_sync_enabled('image')) {
            return;
        }

        $source_a = isset($_REQUEST['source_a']) ? $_REQUEST['source_a'] : 'db';
        $source_b = isset($_REQUEST['source_b']) ? $_REQUEST['source_b'] : 'woo';
        $api_token = isset($_REQUEST['mahak_api_token']) ? $_REQUEST['mahak_api_token'] : '';

        $success_count = 0;

        foreach ($ids as $sku) {
            $sku = sanitize_text_field($sku);
            $raw_data_a = $this->fetch_single_item_raw($sku, $source_a, $api_token);
            if (!$raw_data_a) continue;

            $std_data_a = $this->extract_standard_attributes($raw_data_a, $source_a);

            $result = false;
            if ($source_b === 'woo') {
                $result = $this->update_woocommerce_product($sku, $std_data_a, $sync_type);
            } elseif ($source_b === 'db') {
                $result = $this->update_local_db_product($sku, $std_data_a, $sync_type);
            }
            if ($result) $success_count++;
        }

        $redirect_url = remove_query_arg(['action', 'action2', 'sync_items', '_wp_http_referer', '_wpnonce']);
        $redirect_url = add_query_arg(['synced_count' => $success_count, 'source_a'=>$source_a, 'source_b'=>$source_b, 'mahak_api_token'=>$api_token], $redirect_url);
        wp_redirect($redirect_url);
        exit;
    }

    private function update_woocommerce_product($sku, $data, $sync_type) {
        $pid = wc_get_product_id_by_sku($sku);
        if (!$pid) return false;
        $product = wc_get_product($pid);
        $updated = false;

        // سینک جزئیات - با بررسی تنظیمات
        if ($sync_type === 'all' || $sync_type === 'detailSync') {
            if ($this->is_sync_enabled('title') && !empty($data['name'])) {
                $product->set_name($data['name']);
                $updated = true;
            }

            if ($this->is_sync_enabled('title') && !empty($data['slug'])) {
                $slug = sanitize_title($data['slug']);
                $product->set_slug($slug);
                wp_update_post(['ID' => $pid, 'post_name' => $slug]);
                $updated = true;
            }

            if ($this->is_sync_enabled('description') && isset($data['description'])) {
                $product->set_description($data['description']);
                $updated = true;
            }

            if (!empty($data['short_description'])) {
                $product->set_short_description($data['short_description']);
                $updated = true;
            }

            if (!empty($data['weight']) && $data['weight'] > 0) {
                $product->set_weight($data['weight']);
                $updated = true;
            }
            if (!empty($data['length'])) { $product->set_length($data['length']); $updated = true; }
            if (!empty($data['width']))  { $product->set_width($data['width']);   $updated = true; }
            if (!empty($data['height'])) { $product->set_height($data['height']); $updated = true; }

            if (!empty($data['category_id']) && function_exists('get_bazara_taxonomy_term')) {
                $term_id = get_bazara_taxonomy_term($data['category_id']);
                if ($term_id) {
                    $product->set_category_ids([$term_id]);
                    $updated = true;
                }
            }
        }

        // سینک قیمت - با بررسی تنظیمات
        if (($sync_type === 'all' || $sync_type === 'priceSync') && $this->is_sync_enabled('price')) {
            $woo_currency = get_woocommerce_currency();
            $price_to_set = $data['normalized_price_rial'];
            if ($woo_currency !== 'IRR') {
                $price_to_set = $price_to_set / 10;
            }
            $product->set_regular_price($price_to_set);
            $product->set_price($price_to_set);
            $updated = true;
        }

        // سینک موجودی - با بررسی تنظیمات
        if (($sync_type === 'all' || $sync_type === 'stockSync') && $this->is_sync_enabled('stock')) {
            $product->set_manage_stock(true);
            $product->set_stock_quantity($data['stock']);
            $product->set_stock_status($data['stock'] > 0 ? 'instock' : 'outofstock');
            $updated = true;
        }

        if ($updated) {
            $saved_id = $product->save();

            if ($sync_type === 'all' || $sync_type === 'detailSync') {
                $post_update = ['ID' => $saved_id];
                if ($this->is_sync_enabled('description')) {
                    $post_update['post_content'] = $data['description'];
                }
                if ($this->is_sync_enabled('title') && !empty($data['slug'])) {
                    $post_update['post_name'] = sanitize_title($data['slug']);
                }
                if (count($post_update) > 1) {
                    wp_update_post($post_update);
                }
            }
            return true;
        }
        return false;
    }

    private function update_local_db_product($sku, $data, $sync_type) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bazara_products';

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE ProductCode = %s", $sku));
        if (!$row) return false;

        $updated = false;

        $find_val = function($keys, $source) {
            foreach ($keys as $k) {
                if (isset($source[$k]) && $source[$k] !== '' && $source[$k] !== null) {
                    return $source[$k];
                }
            }
            return null;
        };

        // جزئیات
        if ($sync_type === 'all' || $sync_type === 'detailSync') {
            $update_data = ['detailSync' => 1];

            if ($this->is_sync_enabled('title')) {
                $name_val = $find_val(['name', 'Name', 'ProductName'], $data);
                if ($name_val) $update_data['ProductName'] = $name_val;
            }

            if ($this->is_sync_enabled('description')) {
                $desc_val = $find_val(['description', 'Description', 'Desc'], $data);
                if ($desc_val !== null) $update_data['Description'] = $desc_val;
            }

            $weight_val = $find_val(['weight', 'Weight', 'productWeight'], $data);
            $len_val    = $find_val(['length', 'Length', 'L'], $data);
            $width_val  = $find_val(['width', 'Width', 'W'], $data);
            $height_val = $find_val(['height', 'Height', 'H'], $data);

            if ($weight_val !== null) $update_data['Weight'] = (float)$weight_val;
            if ($len_val !== null)    $update_data['Length'] = (float)$len_val;
            if ($width_val !== null)  $update_data['Width']  = (float)$width_val;
            if ($height_val !== null) $update_data['Height'] = (float)$height_val;

            if (count($update_data) > 1) {
                $result = $wpdb->update($table_name, $update_data, ['ProductCode' => $sku]);
                if ($result !== false) $updated = true;
            }
        }

        // قیمت
        if (($sync_type === 'all' || $sync_type === 'priceSync') && $this->is_sync_enabled('price')) {
            $price_val = $find_val(['normalized_price_rial', 'Price', 'price'], $data);

            if ($price_val !== null && function_exists('get_product_details_unsynced')) {
                $details = get_product_details_unsynced($row->ProductId, 0);
                if (!empty($details)) {
                    $det = $details[0];
                    $prices = json_decode($det->Prices, true);
                    if (!is_array($prices)) $prices = [];

                    $lvl = ($det->DefaultSellPriceLevel == -1 ? 1 : $det->DefaultSellPriceLevel);
                    $prices[$lvl]["Price{$lvl}"] = (int)$price_val;

                    $wpdb->update(
                            "{$wpdb->prefix}bazara_product_details",
                            ['Prices' => json_encode($prices, JSON_UNESCAPED_UNICODE)],
                            ['ProductDetailId' => $det->ProductDetailId]
                    );
                    $wpdb->update($table_name, ['priceSync' => 1], ['ProductCode' => $sku]);
                    $updated = true;
                }
            }
        }

        // موجودی
        if (($sync_type === 'all' || $sync_type === 'stockSync') && $this->is_sync_enabled('stock')) {
            $stock_val = $find_val(['stock', 'Stock', 'Quantity', 'Count1'], $data);

            if ($stock_val !== null && function_exists('get_product_details_unsynced')) {
                $details = get_product_details_unsynced($row->ProductId, 0);
                if (!empty($details)) {
                    $det_id = $details[0]->ProductDetailId;
                    $exist = $wpdb->get_var($wpdb->prepare("SELECT ProductDetailStoreAssetId FROM {$wpdb->prefix}bazara_product_assets WHERE ProductDetailId=%d", $det_id));

                    if ($exist) {
                        $wpdb->update("{$wpdb->prefix}bazara_product_assets", ['Count1' => $stock_val], ['ProductDetailStoreAssetId' => $exist]);
                    } else {
                        $wpdb->insert("{$wpdb->prefix}bazara_product_assets", [
                                'ProductDetailId' => $det_id,
                                'Count1' => $stock_val,
                                'StoreId' => 1,
                                'Deleted' => 0
                        ]);
                    }
                    $wpdb->update($table_name, ['stockSync' => 1], ['ProductCode' => $sku]);
                    $updated = true;
                }
            }
        }

        return $updated;
    }
}
