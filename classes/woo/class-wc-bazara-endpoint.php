<?php
if (!defined("ABSPATH"))
{
    die;
}
if (!class_exists("Endpoint"))
{
    class BazaraEndpoint
    {
        public $action = "send_bazara_order";
        public function __construct()
        {
            add_action("init", array(
                $this,
                "add_endpoint"
            ));
            add_action("query_vars", array(
                $this,
                "add_query_vars"
            ));
            add_action("parse_request", array(
                $this,
                "handle_document_requests"
            ));
        }
        public function get_identifier()
        {
            return apply_filters("wpo_wcpdf_pretty_document_link_identifier", "wcpdf");
        }
        public function add_endpoint()
        {
            $match1 = !empty($matches[1]) ? $matches[1] : 0;
            $match2 = !empty($matches[2]) ? $matches[2] : 0;
            $match3 = !empty($matches[3]) ? $matches[3] : 0;

            add_rewrite_rule("^" . $this->get_identifier() . "/([^/]*)/([^/]*)/([^/]*)?", "index.php?action=send_bazara_order&order_ids=$match1&access_key=$match2&type=$match3", "top");
        }
        public function add_query_vars($vars)
        {
            $vars[] = "action";
            $vars[] = "type";
            $vars[] = "order_ids";
            $vars[] = "access_key";
            return $vars;
        }
        public function handle_document_requests()
        {
            global $wp;
            if (!empty($wp->query_vars["action"]) && $this->action == $wp->query_vars["action"])
            {
                if (!empty($wp->query_vars["order_ids"]) && !empty($wp->query_vars["access_key"]))
                {
                    $_REQUEST["action"] = $this->action;
                    $_REQUEST["order_ids"] = sanitize_text_field($wp->query_vars["order_ids"]);
                    $_REQUEST["access_key"] = sanitize_text_field($wp->query_vars["access_key"]);
                    $_REQUEST["type"] = sanitize_text_field($wp->query_vars["type"]);
                    do_action("wp_ajax_" . $this->action);
                }
            }
        }
        public function get_document_link($order, $additional_vars = array(),$type = 'order')
        {
            if (empty($order))
            {
                return '';
            }
            if (is_user_logged_in())
            {
                $access_key = wp_create_nonce($this->action);
            }
           
            else
            {
                return '';
            }
            
                $document_link = add_query_arg(array(
                    "action" => $this->action,
                    "order_ids" => $order->get_id() ,
                    "access_key" => $access_key,
                    "type" => $type 

                ) , admin_url("admin-ajax.php"));
            
            $additional_vars = apply_filters("bazara_wc_order_document_link_additional_vars", $additional_vars, $order);
            if (!empty($additional_vars) && is_array($additional_vars))
            {
                $document_link = add_query_arg($additional_vars, $document_link);
            }
            return esc_url($document_link);
        }
    }
}
return new BazaraEndpoint();