<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


class Bazara_WOO_Orders {

    function __construct()	{
        add_action( 'woocommerce_admin_order_actions_end', array( $this, 'bazara_add_listing_actions' ) );
        add_action('woocommerce_order_list_table_restrict_manage_orders', array( $this,'bazara_custom_status_dropdown_filter' ));
        add_action('restrict_manage_posts', array( $this,'bazara_custom_status_dropdown_filter_v1' ));
        add_filter('woocommerce_order_query_args', array( $this,'bazara_filter_orders_by_status' ));
        add_filter('pre_get_posts', array( $this,'bazara_filter_orders_by_status_v1' ));

        add_action(
            "wp_ajax_send_bazara_order",
            [$this, "wc_bazara_send_order_ajax"]
        );
        add_action(
            "wp_ajax_nopriv_send_bazara_order",
            [$this, "wc_bazara_send_order_ajax"]
        );

        if (defined("WC_VERSION") && version_compare(WC_VERSION, "3.3", ">="))
        {
            add_filter("bulk_actions-edit-shop_order", array(
                $this,
                "bulk_actions"
            ) , 20);
            add_filter("bulk_actions-woocommerce_page_wc-orders", array(
                $this,
                "bulk_actions"
            ) , 20);
        }
        else
        {
            add_action("admin_footer", array(
                $this,
                "bulk_actions_js"
            ));
        }
    }

    function bazara_filter_orders_by_status_v1($query) {
        global $pagenow, $typenow;
        if ('edit.php' === $pagenow && 'shop_order' === $typenow && isset($_GET['order_status']) && $_GET['order_status'] !== '') {
            // Get the selected status from the dropdown
            $selected_status = sanitize_text_field($_GET['order_status']);
            // Modify the query to include only orders with the selected status

            if ($selected_status == "mahak_unsync"){

                $exclude_statuses = array('wc-cancelled');
                $post_statuses = get_post_stati();
                $allowed_statuses = array_diff($post_statuses, $exclude_statuses);
                $query->set('post_status', $allowed_statuses);

                $meta_query = array(
                    'relation' => 'OR',
                    array(
                        'key'     => 'mahak_id',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => 'mahak_id',
                        'value' => ''
                    )
                );
                $query->set( 'meta_query', $meta_query);

            }
            else
                $query->set('post_status', $selected_status);
        }
    }
    function bazara_custom_status_dropdown_filter_v1() {
        global $typenow;
        if ('shop_order' === $typenow) {
            $order_statuses = wc_get_order_statuses();
            echo '<select name="order_status" class="postform">';
            echo '<option value="">فیلتر وضعیت سفارش</option>';
            foreach ($order_statuses as $status_key => $status_label) {
                $selected = isset($_GET['order_status']) && $_GET['order_status'] === $status_key ? 'selected' : '';
                echo '<option value="' . esc_attr($status_key) . '" ' . $selected . '>' . esc_html($status_label) . '</option>';
            }
            echo '<option value="mahak_unsync" ' . $selected . '>ارسال نشده به محک</option>';
            echo '</select>';
        }
    }
    function bazara_custom_status_dropdown_filter() {
        if(!function_exists('get_current_screen'))
            return false;
        $screen = get_current_screen();
        if (empty($screen) || !isset($screen->post_type)) {
            return false;
        }
        $post_type = $screen->post_type;
        if ('shop_order' === $post_type) {
            $order_statuses = wc_get_order_statuses();

            echo '<select name="order_status" class="postform">';
            echo '<option value="">Filter by Status</option>';
            foreach ($order_statuses as $status_key => $status_label) {
                $selected = isset($_GET['order_status']) && $_GET['order_status'] === $status_key ? 'selected' : '';
                echo '<option value="' . esc_attr($status_key) . '" ' . $selected . '>' . esc_html($status_label) . '</option>';
            }
            if(isset($_GET['order_status']) && $_GET['order_status'] == 'mahak_unsync')
            $selected = 'selected';                

            echo '<option value="mahak_unsync" ' . $selected . '>ارسال نشده به محک</option>';
            echo '</select>';
        }
    }

    public function bazara_filter_orders_by_status($args) {

        if(!function_exists('get_current_screen'))
        return $args;
        $screen = get_current_screen();
        if (empty($screen) || !isset($screen->post_type)) {
            return $args;
        }
        $post_type = $screen->post_type;

        if (is_admin()) {
            if ( 'shop_order' === $post_type && isset($_GET['order_status']) && $_GET['order_status'] !== '') {
                $selected_status = sanitize_text_field($_GET['order_status']);
                if ($selected_status === 'mahak_unsync') {

                    $exclude_statuses = array('wc-cancelled');
                    $post_statuses = get_post_stati();
                    $allowed_statuses = array_diff($post_statuses, $exclude_statuses);
                    $args['status'] = $allowed_statuses;

                    $meta_query = array(
                        'relation' => 'OR',
                        array(
                            'key'     => 'mahak_id',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key'   => 'mahak_id',
                            'value' => '',
                        ),
                    );
                    $args['meta_query'] = $meta_query;

                } else {
                    $args['status'] = array( $selected_status);
                }
            }
        }
        return $args;
    }

    function wc_bazara_send_order_ajax()
    {
        if (empty($_REQUEST["access_key"])) {
            foreach (
                [
                    "_wpnonce",
                    "order_key",
                ]
                as $legacy_key
            ) {
                if (!empty($_REQUEST[$legacy_key])) {
                    $_REQUEST["access_key"] = sanitize_text_field($_REQUEST[$legacy_key]);
                }
            }
        }
        $valid_nonce =
            !empty($_REQUEST["access_key"]) &&
            !empty($_REQUEST["action"]) &&
            wp_verify_nonce(
                $_REQUEST["access_key"],
                $_REQUEST["action"]
            );
        if (empty($_REQUEST["access_key"])) {
            wp_die($_REQUEST["access_key"]);
        }

        if (empty($_REQUEST["order_ids"]) || empty($_REQUEST["type"])) {
            wp_die(
                esc_attr__(
                    "You haven't selected any orders",
                    "woocommerce-bazara_orders"
                )
            );
        }


        $order_ids = (array) array_map(
            "absint",
            explode(
                "x",
                $_REQUEST["order_ids"]
            )
        );
        $order = false;
        if ($_REQUEST["type"] == "log") {
            $error = get_post_meta($_REQUEST["order_ids"], 'mahak_error', true);
            echo $error;
            die;
        }
        $bazaraApi = new BazaraApi(true);

        foreach ($order_ids as  $order_id) {
            $exist = get_post_meta($order_id, 'mahak_id', true);
            if (!empty($exist)){
                delete_post_meta($order_id, 'mahak_id');
                delete_post_meta($order_id, 'mahak_error');
            }
            $api_result = $bazaraApi->bazara_save_order($order_id, null);
        }
        die();
    }
    public function return_false()
    {
        return false;
    }

    public function bulk_actions($actions)
    {
        foreach ($this->get_bulk_actions() as $action => $title)
        {
            $actions[$action] = $title;
        }
        return $actions;
    }
    public function is_order_page()
    {
        $screen = get_current_screen();
        if (!is_null($screen) && in_array($screen->id, array(
                "shop_order",
                "edit-shop_order",
                "woocommerce_page_wc-orders"
            )))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    public function bulk_actions_js()
    {
        if ( $this->is_order_page() ) {

            ?>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    <?php
                    foreach ($this->get_bulk_actions() as $action => $title) {?>
                    jQuery('<option>').val('<?php echo esc_attr($action);?>').html('<?php echo esc_attr($title);?>').appendTo("select[name='action'], select[name='action2']");
                    <?php } ?>
                });
            </script>

            <?php
        }
    }
    public function get_bulk_actions()
    {
        $actions = array(
            "bazara_order" => "ارسال فاکتور بازارا",
        );
        return $actions;
    }
    public function bazara_add_listing_actions( $order ) {
        // do not show buttons for trashed orders
        if ( $order->get_status() == 'trash' ) {
            return;
        }

        $listing_actions = array();
        $existing = !empty(get_post_meta($order->get_id(), 'mahak_error',true));
        $icon = PLUGIN_DIR_URL.'assets/img/packing-slip.svg';
        $url = BAZARA_WC_ORDER()->endpoint->get_document_link($order,'','log');
        $listing_actions['bazara_error'] = array(
            'url'    => esc_url( $url ),
            'img'    => $icon,
            'alt'    => "نمایش خطا ارسال فاکتور بازارا",
            'exists' => $existing,
            'class'  => '',
        );
        $existing = !empty(get_post_meta($order->get_id(), 'mahak_id',true));
        $icon = PLUGIN_DIR_URL.'assets/img/invoice.svg';
        $url = BAZARA_WC_ORDER()->endpoint->get_document_link($order,'','order');
        $listing_actions['bazara_order'] = array(
            'url'    => esc_url( $url ),
            'img'    => $icon,
            'alt'    => "ارسال فاکتور بازارا",
            'exists' => $existing,
            'class'  => '',
        );


        foreach ( $listing_actions as $action => $data ) {
            if ( ! isset( $data['class'] ) ) {
                $data['class'] = $data['exists'] ? "exists {$action}" : $action;
            }

            $exists = $data['exists'] ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z"></path></svg>' : '';

            printf(
                '<a href="%1$s" class="button tips wpo_wcpdf %2$s" target="_blank" alt="%3$s" data-tip="%3$s" style="background-image:url(%4$s);">%5$s</a>',
                esc_attr( $data['url'] ),
                esc_attr( $data['class'] ),
                esc_attr( $data['alt'] ),
                esc_attr( $data['img'] ),
                $exists
            );
        }
    }
}
new Bazara_WOO_Orders();