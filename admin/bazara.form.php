<?php

if (! defined('ABSPATH')) {
    exit;
}

function form_page()
{

    if (! current_user_can('shop_bazara')) return;
    $active_tab = 'pre_options';
    $visitor = empty(bazara_get_options()) ? '' : bazara_get_options();
    $visitorOption = empty(get_bazara_visitor_settings()) ? '' : get_bazara_visitor_settings();

    if (isset($_GET['nav'])) {
        $active_tab = $_GET['nav'];
    } ?>


    <!-- <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
            <input type="hidden" name="action" value="test_form"/>

          <br/>
            <input type="submit" class="button button-primary" value="repair" />
        </form>      -->
    <?php
    load_modal_user_login();
    bazara_modal_seo_confirm();

    if ($active_tab == 'pre_options') {
    ?>

        <form method="post" autocomplete="off" id="bazara_setting_update" class="bazara_activation_form">

            <div class="bazara_admim_conf">

                <div class="bazara_log_setge">

                    <div class="bazara_admin_left_side_content">


                        <div class="bazara_sts_logo">
                            <div class="bazara_save_button bazara-button-spinner success" id="bazara-library-sync-button"><label>ذخیره تغییرات</label></div>

                            <span class="bazara-display_inline">

                                <img src="<?= plugin_dir_url(dirname(__FILE__)) ?>assets/img/logo.png">
                                <span class="bazara_plugin_version"><?= bazara::bazara_version() ?></span>

                            </span>
                        </div>



                        <div style="background-color:#364656;min-height: 30px!important">
                            <ul class="bazara-navbar">
                                <?php
                                include dirname(__FILE__) . '/ui/templates/header.php';

                                ?>

                            </ul>
                        </div>
                        <div class="setting-notice-box"></div>

                        <div class="bazara-settings_body">

                            <div class="bazara-tab-wrapper" style="top: 32px;margin-top:20px">

                                <ul class="bazara-tab-ul">
                                    <li><a href="?page=bazara_settings&amp;tab=license" class="updatetabview bazara-nav-tab bazara-nav-tab-active" tab="license">اتصال به نرم افزار</a></li>

                                    <li><a href="?page=bazara_settings&amp;tab=sync" class="updatetabview bazara-nav-tab" tab="sync">همگام سازی</a></li>

                                    <li><a href="?page=bazara_settings&amp;tab=about" class="customfieldsNavTab updatetabview bazara-nav-tab" tab="about" style="display:none">درباره افزونه</a></li>

                                    <li id="bazara-tab-magic-line" style="width: 489px; left: 0px; top: 48px; display: list-item;"></li>
                                    <li id="bazara-tab-magic-line" style="display: none"></li>
                                </ul>

                            </div>


                            <div id="bazara_setting_form_div" class="bazara_settings_Form" data-select2-id="bazara_setting_form_div">
                                <div data-tab="license" class="bazara_admin_in_pt license bazaratabview bazara_currentactive">


                                    <?php
                                    include dirname(__FILE__) . '/ui/templates/license-tab.php';

                                    ?>
                                </div>
                                <div data-tab="sync" class="bazara_admin_in_pt sync bazaratabview " style="display:none">
                                    <?php
                                    include dirname(__FILE__) . '/ui/templates/synchronize-tab.php';

                                    ?>
                                </div>

                                <div data-tab="about" class="bazara_admin_in_pt about bazaratabview " style="display:none">

                                    <?php
                                    include dirname(__FILE__) . '/ui/templates/about.php';

                                    ?>
                                </div>

                            </div>

                        </div>
                    </div>

                </div><!-- /.wrap -->

        </form>
        </div>
    <?php
    } else { ?>

        <form method="post" autocomplete="off" id="bazara_form_setting" class="bazara_activation_form">

            <div class="bazara_admim_conf">

                <div class="bazara_log_setge">

                    <div class="bazara_admin_left_side_content">


                        <div class="bazara_sts_logo">
                            <div id="bazara_save_form_setting" class="bazara_save_button bazara-button-spinner success" id="bazara-library-sync-button"><label>ذخیره تغییرات</label></div>

                            <span class="bazara-display_inline">

                                <img src="<?= plugin_dir_url(dirname(__FILE__)) ?>assets/img/logo.png">
                                <span class="bazara_plugin_version"><?= bazara::bazara_version() ?></span>

                            </span>
                        </div>



                        <div style="background-color:#364656;min-height: 30px!important">
                            <ul class="bazara-navbar">
                                <?php
                                include dirname(__FILE__) . '/ui/templates/header.php';

                                ?>

                            </ul>
                        </div>
                        <div class="setting-notice-box"></div>

                        <div class="bazara-settings_body">

                            <div class="bazara-tab-wrapper" style="top: 32px;margin-top:20px">

                                <ul class="bazara-tab-ul">
                                    <li><a href="?page=bazara_settings&amp;tab=products" class="updatetabview bazara-nav-tab bazara-nav-tab-active" tab="products">محصولات</a></li>

                                    <li><a href="?page=bazara_settings&amp;tab=customers" class="updatetabview bazara-nav-tab" tab="customers">مشتریان</a></li>

                                    <li><a href="?page=bazara_settings&amp;tab=orders" class="customfieldsNavTab updatetabview bazara-nav-tab" tab="orders">سفارشات</a></li>
                                    <li><a id="price_tab" href="?page=bazara_settings&amp;tab=prices" class="customfieldsNavTab updatetabview bazara-nav-tab" tab="prices">قیمت ها</a></li>

                                    <li id="bazara-tab-magic-line" style="width: 84px; left: 984.016px; top: 56px; display: list-item;"></li>
                                    <li id="bazara-tab-magic-line" style="display: none"></li>
                                    <li id="bazara-tab-magic-line" style="display: none"></li>
                                </ul>

                            </div>




                            <div id="bazara_setting_form_div" class="bazara_settings_Form">

                                <div data-tab="products" class="bazara_admin_in_pt products bazaratabview bazara_currentactive">
                                    <?php
                                    include dirname(__FILE__) . '/ui/templates/products-tab.php';

                                    ?>

                                </div>
                                <div data-tab="prices" class="bazara_admin_in_pt prices bazaratabview " style="display:none">
                                    <?php
                                    include dirname(__FILE__) . '/ui/templates/price-tab.php';

                                    ?>
                                </div>
                                <div data-tab="orders" class="bazara_admin_in_pt orders bazaratabview " style="display:none">
                                    <?php
                                    include dirname(__FILE__) . '/ui/templates/order-tab.php';

                                    ?>
                                </div>
                                <div data-tab="customers" class="bazara_admin_in_pt customers bazaratabview " style="display:none">

                                    <?php
                                    include dirname(__FILE__) . '/ui/templates/persons-tab.php';

                                    ?>
                                </div>

                            </div>
                        </div>
                    </div>
        </form>
    <?php

    }
}


function load_bazara_wp_admin_style($hook)
{
    if ($hook != 'admin_page_commissions_detail' && !strpos(strtolower($hook), 'bazara')) {
        return;
    }
    global $offset;


    wp_enqueue_style('mahak-bazara-style', plugin_dir_url(dirname(__FILE__)) . 'assets/css/gs.min.css', array(), '7.0.0.70', 'screen');
    wp_enqueue_style('mahak-bazara-custom-admin', plugin_dir_url(dirname(__FILE__)) . 'assets/css/custom-admin.css', array(), '7.0.0.93', 'screen');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-sortable');

    wp_enqueue_script('mahak-bazara-ui-scripts', plugins_url('assets/js/ui.js', dirname(__FILE__)), array('jquery'), '1.1.162', true);
    wp_enqueue_script('mahak-bazara-scripts', plugins_url('assets/js/scripts.js', dirname(__FILE__)), array('jquery'), '8.0.86', true);

    wp_localize_script('mahak-bazara-scripts', 'params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'ajax_nonce' => wp_create_nonce('bazara_security'),
        'plugin_dir_url' => plugin_dir_url(dirname(__FILE__)),
        'batch' => $offset,
        'admin_post' => esc_url(admin_url('admin-post.php')),
        'visitor_setting' => empty(bazara_get_options()) ? '' : bazara_get_options()

    ));

    wp_register_style(
        'select2css',
        'https://lib.arvancloud.ir/select2/3.4.8/select2.css',
        array(),
        '1.0',
        'all'
    );    
    wp_register_script(
        'select2',
        'https://lib.arvancloud.ir/select2/3.4.8/select2.js',
        array('jquery'),
        '1.0',
        true
    );
    wp_enqueue_style('select2css');
    wp_enqueue_script('select2');
}

function load_modal_user_login()
{
    ?>
    <div id="bazaraModalSupportLogin" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button" onclick="modalClose()">&times;</span>
            <p>فقط پشتیبان اجازه تغییر اطلاعات را دارد!</p>
            <p>لطفا ابتدا لاگین کنید.</p>
            <input type="password" id="syncPasswordInput" placeholder="رمز عبور" autocomplete="off">
            <input type="hidden" id="modalCallbackForm" value="">
            <span class="button button-primary" id="syncSubmitLogin" onclick="syncSubmitLogin()">ارسال</span>
        </div>
    </div>
    <style>
        .modal {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: right;
        }

        .close-button {
            float: left;
            cursor: pointer;
            color: #fff;
            background: #d56363;
            padding: 0px 6px;
            border-radius: 5px;
        }
    </style>
<?php
}
function bazara_modal_seo_confirm()
{
?>
    <div id="bazaraModalSeoConfirm" class="modal" style="display: none;">
        <div class="modal-content">

            <table>
                <tr>
                    <td colspan="2">
                        <span class="close-button" onclick="modalClose()">&times;</span>
                    </td>
                </tr>
                <tr>
                    <td><i class="dashicons-before dashicons-warning" style="color: red"></i></td>
                    <td>
                        <p id="bazaraModalSeoConfirmMessage"></p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="button button-primary" onclick="bazaraModalSeoConfirmSubmit()">مورد تایید است</span>
                        <span class="button button-secondary" onclick="modalClose()" style="float:left;">لغو</span>
                    </td>
                </tr>
            </table>

            <input type="hidden" id="bazaraModalSeoConfirmDataSync" value="">
            <input type="hidden" id="bazaraModalSeoConfirmDataLogin" value="">


        </div>
    </div>
<?php
}

add_action('admin_enqueue_scripts', 'load_bazara_wp_admin_style');
