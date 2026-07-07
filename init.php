<?php

use bazara_Log\log\tables\bazara_log_Table;

$version = "20000";
$call_WebService = false;
$offset = 20;
$Qauntity = "Count1";

class bazara
{
    private static $instance = null;
    private $min_php = '5.6.0';
    private $dbVersion = 45;
	public $products_obj,$dynamic_table_obj;
    public $user_dashboard_addons = null;
    public $endpoint;
    public $orders;
    public $assets;
    private function __construct()
    {
        $this->init();
        register_activation_hook(BAZARA_PLUGIN_FILE, array($this, 'activate'));
        //register_deactivation_hook(BAZARA_PLUGIN_FILE, array($this, 'deactivate'));

    }
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    public static function bazara_version() {
        $plugin_data = get_plugin_data( __FILE__ );
        $plugin_version = $plugin_data['Version'];
        return $plugin_version;
    }

    function init()
    {

        require_once plugin_dir_path( __FILE__ ) . 'classes/class.bazara.lang.php';

        require_once plugin_dir_path(__FILE__) . 'libs/jalali/Converter.php';
        require_once plugin_dir_path(__FILE__) . 'libs/jalali/Jalalian.php';
        require_once plugin_dir_path(__FILE__) . 'libs/jalali/CalendarUtils.php';
        // require_once plugin_dir_path(__FILE__) . 'libs/jalali/helpers.php';


        if ( is_admin() ) {
            require_once plugin_dir_path( __FILE__ ) . 'admin/bazara.form.php';
            require_once plugin_dir_path( __FILE__ ) . 'admin/woo_products_panel_hooks.php';
            require_once plugin_dir_path( __FILE__ ) . 'classes/log/tables/class-wp-list-table.php';
			require_once plugin_dir_path( __FILE__ ) . 'classes/log/tables/class-bazara-log-handler.php';
			require_once plugin_dir_path( __FILE__ ) . 'classes/log/tables/class-bazara-log-table.php';

            if (defined( 'BAZARA_ADDONS_TYPE' ) && BAZARA_ADDONS_TYPE == BAZARA_ADDONS_TYPE_QUANTITY_CONDITION)
            {
                require_once BAZARA_ADDONS_DIR_PATH . 'addons/ratio-calculator/RatioCalculator.php';
                new bazara_ratio_calculator();
            }
        }
       require_once plugin_dir_path( __FILE__ ) . 'classes/log/class-log.php';
       require_once plugin_dir_path( __FILE__ ) . 'classes/log/class-logwriter.php';
       require_once plugin_dir_path( __FILE__ ) . 'libs/Enums.php';
       require_once plugin_dir_path( __FILE__ ) . 'classes/functions.php';
       require_once plugin_dir_path( __FILE__ ) . 'classes/class.bazara.api.php';

       require_once plugin_dir_path( __FILE__ ) . 'classes/class.products.table.php';
       if(!function_exists('jalali_to_gregorian'))
      require_once plugin_dir_path( __FILE__ ) . 'libs/jdf.php';

      $this->orders = include_once (plugin_dir_path( __FILE__ ) . 'classes/woo/class.woo.orders.php');
      $this->endpoint = include_once (plugin_dir_path( __FILE__ ) . 'classes/woo/class-wc-bazara-endpoint.php');
      $this->assets = include_once (plugin_dir_path( __FILE__ ) . 'classes/woo/class-wc-bazara-assets.php');

        add_action( 'admin_init', [ $this, 'install' ] );
        add_action( 'plugins_loaded', array($this, 'load_plugin'));
        if (class_exists('sell_jeans_to_wholeSaler'))
        add_action('init', [ $this,'init_woo']);
        if (class_exists('sell_simple_with_date_variants') && !class_exists('sell_simple_with_date_variants_without_date'))
        add_action('init', [ $this,'init_woo_date_variation']);


    }
    function init_woo_date_variation()
    {
        require_once plugin_dir_path( __FILE__ ) . 'classes/variations.php';
        add_action( 'woocommerce_single_product_summary', function() {
            global $product;
            $id = $product->get_id();
            $getClosestDate = get_post_meta( $id, 'mahak_closest_date', true);
            $mahak_custom_date = get_post_meta( $id, 'mahak_custom_date', true);
            if ($mahak_custom_date)
            echo '<p class="price">تاریخ انقضا :  <span class="woocommerce-Price-amount">'.$getClosestDate . '</span></p>';
            
        }, 10);
    }
    function init_woo()
    {
        $user_ID = get_current_user_id();
        $isUserGroup =  $this->bazara_user_has_role($user_ID,BAZARA_WCPDF_USER_COLLEGE) || $this->bazara_user_has_role($user_ID,BAZARA_WCPDF_USER_WHOLESALER);
        require_once plugin_dir_path( __FILE__ ) . 'classes/woo/user-avatar.php';
        if ($isUserGroup)
        require_once plugin_dir_path( __FILE__ ) . 'classes/woo/class.woo.php';

    }
    function bazara_user_has_role($user_id, $role_name)
    {
        $user_meta = get_userdata($user_id);
        $user_roles = $user_meta->roles;
        return in_array($role_name, $user_roles);
    }
    function bazara_remove_clear_text( $value ) {

		$value = "";
		return $value;
	
	}
    public static function set_screen( $status, $option, $value ) {
		return $value;
	}
    public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Products',
			'default' => 10,
			'option'  => 'product_per_page'
		];

		add_screen_option( $option, $args );

		$this->products_obj = new Bazara_Products_List();
        if (class_exists("bazaraDynamicPrice"))
        $this->dynamic_table_obj = new Bazara_Dynamic_Price_List();
	}
    
    public function plugin_menu() {
		add_menu_page(
			esc_html__('Bazara API Settings','mahak-bazara'),
			esc_html__('همگام سازی محک','mahak-bazara'),
			'shop_bazara',
			'bazara',
			'form_page',
			 plugin_dir_url( __FILE__). 'assets/img/New-Logo-MahakSoft-28.png',
			null
		);
        $hook_st = add_submenu_page(
            'bazara',
            esc_html__('تنظیمات','mahak-bazara'),
            esc_html__('تنظیمات','mahak-bazara'),
            'manage_options',
            'bazara_settings_tabs',
            [ $this, 'bazara_settings_tabs_page' ]
        );
        add_action( "load-$hook_st", [ $this, 'screen_option' ] );
        add_action('admin_enqueue_scripts', [$this, 'enqueue_bazara_admin_styles']);

         if (class_exists("bazaraDynamicPrice"))
         add_submenu_page( 'bazara', esc_html__('فروش کالا با قیمت متغیر','mahak-bazara'), esc_html__('فروش کالا با قیمت متغیر','mahak-bazara'),
		 'manage_options', 'bazara_dynamic_price_list',[ bazaraDynamicPrice::instance(), 'render' ]);
	}

    public function enqueue_bazara_admin_styles($hook) {
        if (strpos($hook, 'bazara_settings_tabs') == false) {
            return;
        }
        wp_enqueue_style('bazara-admin-styles', plugin_dir_url(__FILE__) . '../woocommerce/assets/css/admin-rtl.css');
    }

    public function bazara_settings_tabs_page()
    {
        ?>
<div id="wpbody-content">
    <div class="wrap woocommerce">
        <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
            <a href="?page=bazara_settings_tabs"
                class="nav-tab <?php echo isset($_GET['tab']) ? '' : 'nav-tab-active'; ?>">لیست محصولات</a>
            <a href="?page=bazara_settings_tabs&tab=unsynced_products"
                class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'unsynced_products' ? 'nav-tab-active' : ''; ?>">لیست
                محصولات دارای مغایرت</a>
            <a href="?page=bazara_settings_tabs&tab=tools"
                class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'tools' ? 'nav-tab-active' : ''; ?>">ابزار</a>
            <a href="?page=bazara_settings_tabs&tab=reports"
                class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'reports' ? 'nav-tab-active' : ''; ?>">گزارشات</a>
        </nav>
        <h1 class="screen-reader-text">ابزار</h1>
        <?php
                $tab = isset($_GET['tab']) ? $_GET['tab'] : '';
                switch ($tab) {
                    case 'unsynced_products':
                        $this->bazara_products_unsynced_list_page();
                        break;
                    case 'tools':
                        $this->bazara_settings_tools_page();
                        break;
                    case 'reports':
                        $this->bazara_settings_reports_page();
                        break;
                    default:
                        $this->bazara_settings_products_page();
                        break;
                }

                ?>

    </div>
</div>
<?php
    }

    public function bazara_products_unsynced_list_page()
    {
        if (!class_exists('WP_List_Table')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }
        if (!class_exists('Bazara_Unsynced_Products_Table')) {
            require_once plugin_dir_path(__FILE__) . 'classes/class_bazara_unsynced_products_table.php';
        }
        $products_table = new Bazara_Unsynced_Products_Table();
        $products_table->prepare_items();
        ?>
<div class="wrap">
    <h2>لیست محصولات دارای مغایرت</h2>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <form method="post">
                        <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>">
                        <?php
                                $products_table->search_box(__('جستجو', 'textdomain'), 'product_search');
                                $products_table->display();
                                ?>
                    </form>
                </div>
            </div>
        </div>
        <br class="clear">
    </div>
</div>
<?php
    }

    public function bazara_settings_tools_page()
    {
        ?>
<table class="wc_status_table wc_status_table--tools widefat">
    <tbody class="tools">
        <tr>
            <th>
                <strong class="">بازسازی اطلاعات ذخیره شده در جداول بازارا</strong>
                <p class="">با اجرای این عملیات ، کلیه اطلاعات بازسازی خواهند شد</p>
            </th>
            <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="POST">

                <td class="run-tool">
                    <input type="hidden" name="action" value="bazara_repair_database" />

                    <input type="submit" class="button button-large" value="بازسازی اطلاعات">
                </td>
            </form>
        </tr>
        <tr class="">
            <th>
                <strong class="">حذف اطلاعات بدون والد</strong>
                <p class=""> با اجرای این عملیات ، کلیه پست های بدون والد حذف خواهند شد</p>
            </th>
            <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="POST">
                <td class="run-tool">
                    <input type="hidden" name="action" value="clear_tables_queue" />
                    <input type="submit" class="button button-large" value="حذف اطلاعات بدون والد">
                </td>
            </form>
        </tr>
    </tbody>
</table>
<?php
    }

    public function bazara_settings_reports_page()
    {
        if (!class_exists('WP_List_Table')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }
        if (!class_exists('Bazara_Settings_Reports_Table')) {
            require_once plugin_dir_path(__FILE__) . 'classes/class_bazara_settings_reports_table.php';
        }
        $logs_table = new Bazara_Settings_Reports_Table();

        ?>
<div class="wrap">
    <h2>لیست گزارش سینک های انجام شده و نشده</h2>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <form method="post">
                        <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
                        <?php
                                $logs_table->prepare_items();
                                $logs_table->search_box(__('جستجو', 'textdomain'), 'log_search');
                                $logs_table->display();
                                ?>
                    </form>
                </div>
            </div>
        </div>
        <br class="clear">
    </div>
</div>
<?php
    }

    public function bazara_settings_products_page()
    {
        ?>
<div class="wrap">
    <h2>لیست محصولات </h2>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <form method="post">
                        <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />

                        <?php
                                $this->products_obj->prepare_items();
                                $this->products_obj->search_box( 'جستجو', 'search' );

                                $this->products_obj->display();
                                ?>
                    </form>
                </div>
            </div>
        </div>
        <br class="clear">
    </div>
</div>
<?php
    }
    public function plugin_settings_page() {
		?>
<div class="wrap">
    <h2>لیست محصولات </h2>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <form method="post">
                        <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />

                        <?php
                                $this->products_obj->prepare_items();
                                $this->products_obj->search_box( 'جستجو', 'search' );

								$this->products_obj->display();
                                ?>
                    </form>
                </div>
            </div>
        </div>
        <br class="clear">
    </div>
</div>
<?php
	}

    function load_plugin() {
        
        load_plugin_textdomain( 'mahak-bazara', false, basename( dirname( __FILE__ ) ) . '/languages/' );

        self::bazara_register_settings();

        $role = get_role( 'administrator' );
        if( '' != $role ) {
            $role->add_cap( 'shop_bazara' );
        }
        $role = get_role( 'shop_manager' );
        if( '' != $role ) {
            $role->add_cap( 'shop_bazara' );
            $role->add_cap( 'create_users' );
            $role->add_cap( 'delete_users' );
            $role->add_cap( 'edit_users' );
            $role->add_cap( 'list_users' );
            $role->add_cap( 'promote_users' );
            $role->add_cap( 'remove_users' );
        }
        add_filter('option_page_capability_bazara_options',function($cap){
            return "shop_bazara";
        },20);

         add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
        add_filter( 'set_screen_option', [ __CLASS__, 'set_screen' ], 10, 3 );
        
        add_filter('plugin_action_links_' . BAZARA_PLUGIN_BASE_NAME, array( $this, 'plugin_action_links' ) );

    }
    public function install() {
        $this->update_plugin_tables();
		bazara_init_cron();
	}
    function bazara_register_settings() {

        register_setting(
            'bazara_options',
            'bazara_options',
            'bazara_validation_options'
        );
    }
    public function is_supported_php() {
        if ( version_compare( PHP_VERSION, $this->min_php, '<=' ) ) {
            return false;
        }

        return true;
    }
    public function activate()
    {
        if ( ! $this->is_supported_php() ) {
            wc_print_notice( sprintf( __( 'The Minimum PHP Version Requirement for <b>Order Notification</b> is %s. You are Running PHP %s', 'dokan' ), $this->min_php, phpversion(), 'error' ) );
            exit;
        }

        $this->create_plugin_database_tables();
        
        if(class_exists('bazara_addOns') && BAZARA_ADDONS_TYPE == BAZARA_ADDONS_TYPE_USER_DASHBOARD) 
        $this->user_dashboard_addons->activate();

    }
    function deactivate()
    {
        if ( ! $this->is_supported_php() ) {
            wc_print_notice( sprintf( __( 'The Minimum PHP Version Requirement for <b>Order Notification</b> is %s. You are Running PHP %s', 'dokan' ), $this->min_php, phpversion(), 'error' ) );
            exit;
        }

        $this->drop_tables();
        delete_option("bazara_latest_versions");
//        delete_option("bazara_visitor_settings");
        delete_option("bazara_visitor_options");

    }
    function update_plugin_tables()
    {

        $versions = get_option( 'bazara_options');

        
        $databseVersion = !empty($versions['databaseVersion']) ? ((int)$versions['databaseVersion']) : 0;

        if ($this->dbVersion > $databseVersion){

            global $table_prefix, $wpdb;
            $tblname = 'bazara_banks';
            $wp_track_table = $table_prefix . $tblname;
            $databaseSchema = $wpdb->dbname;
            $charset_collate = $wpdb->get_charset_collate();
            $sql = array();
            
            if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
            {
                $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
                ( `b_b_id` INT(11)  NOT NULL auto_increment,
                `BankId` INT(11)   NULL ,
                `BankClientId` int(11)  NULL,
                `BankCode` int(11)  NULL,
                `Name` Text  NULL,
                `Description` Text  NULL,
                `Deleted` TINYINT(1)  NULL,
                `RowVersion` int(11)  NULL,
                UNIQUE (`b_b_id`) ) $charset_collate";

            }
            $tblname = 'bazara_persons';
            $wp_track_table = $table_prefix . $tblname;
            $databaseSchema = $wpdb->dbname;
            $charset_collate = $wpdb->get_charset_collate();
            
            if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
            {
                $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
                ( `p_id` INT(11)  NOT NULL auto_increment,
                `PersonId` INT(11)   NULL ,
                `PersonClientID` int(11)  NULL,
                `PersonGroupId` int(11)  NULL,
                `PersonCode` int(11)  NULL,
                `FirstName` Text  NULL,
                `LastName` Text  NULL,
                `Email` Text  NULL,
                `Deleted` TINYINT(1)  NULL,
                `RowVersion` int(11)  NULL,
                `isSync` TINYINT(1)  NULL,
                `Mobile` varchar(100)  NULL,
                `Address` varchar(700)  NULL,
                UNIQUE (`p_id`) ) $charset_collate";

            }
            $tblname = 'bazara_sub_category';
            $wp_track_table = $table_prefix . $tblname;

            if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
            {
                $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
                ( `cat_id` BIGINT  NOT NULL auto_increment,
                `ProductCategoryId` INT(11)   NULL ,
                `Name` Text  NULL,
                `RowVersion` BIGINT  NULL,
                `Deleted` TINYINT(1)  NULL,
                `isSync` TINYINT(1)  NULL,
                UNIQUE (`cat_id`) ) $charset_collate";

            }
            $tblname = 'bazara_stores';
            $wp_track_table = $table_prefix . $tblname;

            if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
            {
                $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
                ( `s_id` INT(11)  NOT NULL auto_increment,
                `StoreId` INT(11)   NULL ,
                `StoreCode` int(11)  NULL,
                `Name` Text  NULL,
                `Comment` Text  NULL,
                `Deleted` TINYINT(1)  NULL,
                `RowVersion` int(11)  NULL,
                UNIQUE (`s_id`) ) $charset_collate";

            }
            $tblname = 'bazara_orders';
            $wp_track_table = $table_prefix . $tblname;

            if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
            {
                $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
                ( `o_id` bigint(11)  NOT NULL auto_increment,
                `OrderId` bigint(11)   NULL ,
                `OrderClientId` bigint(11)  NULL,
                `OrderCode` bigint(11)  NULL,
                `PersonId` bigint(11)  NULL,
                `OrderDate` Text  NULL,
                `Deleted` TINYINT(1)  NULL,
                `RowVersion` int(11)  NULL,
                UNIQUE (`o_id`) ) $charset_collate";

            }
            $tblname = 'bazara_order_details';
            $wp_track_table = $table_prefix . $tblname;

            if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
            {
                $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
                ( `o_d_id` bigint(11)  NOT NULL auto_increment,
                `OrderId` bigint(11)   NULL ,
                `OrderDetailId` bigint(11)  NULL,
                `OrderDetailClientId` bigint(11)  NULL,
                `ProductDetailId` bigint(11)  NULL,
                `Count1` bigint(11)  NULL,
                `Count2` bigint(11)  NULL,
                `Price` Text  NULL,
                `Deleted` TINYINT(1)  NULL,
                `RowVersion` int(11)  NULL,
                UNIQUE (`o_d_id`) ) $charset_collate";

            }
            if (!empty($sql))
            {
                require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
                foreach ($sql as $sq)
                {
                    dbDelta($sq);

                }
                
            }
            $tblname = 'bazara_persons';
            $wp_track_table = $table_prefix . $tblname;

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'PersonCode' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD PersonCode INT(11) NULL,Drop COLUMN FirstName,ADD FirstName Text NULL");

            }
         
            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'isSync' AND TABLE_SCHEMA = '$databaseSchema'"  );
           
            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD isSync TINYINT(1)  NULL DEFAULT 0,ADD PersonGroupId INT(11)  NULL,ADD Mobile varchar(100)  NULL,ADD Address varchar(700)");

            }
           
            $tblname = 'bazara_products';
            $wp_track_table = $table_prefix . $tblname;

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'barcode' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD barcode Text NULL");

            }

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'isSync' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(!empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD detailSync TINYINT(1),ADD stockSync TINYINT(1),ADD priceSync TINYINT(1)");
                $wpdb->query("UPDATE $wp_track_table SET detailSync = 1,stockSync = 1,priceSync = 1 where isSync = 1");
                $wpdb->query("ALTER TABLE $wp_track_table Drop COLUMN isSync");

            }

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'new' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD new tinyint(1) NULL");
                $wpdb->query("UPDATE $wp_track_table set new = 0");

            }

    

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'Post_ID' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD Post_ID BIGINT NULL");

            }

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'queue' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD queue Text NULL");

            }
            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'weight' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD weight DECIMAL(23,8) NULL,ADD width DECIMAL(23,8) NULL,ADD height DECIMAL(23,8) NULL,ADD length DECIMAL(23,8) NULL");

            }
            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'unitName1' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD unitName1 varchar(200) NULL,ADD unitName2 varchar(200) NULL");

            }
            $tblname = 'bazara_product_details';
            $wp_track_table = $table_prefix . $tblname;

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'isSync' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD isSync TINYINT(1)  NULL DEFAULT 0 ,ADD queue TINYINT(1)  NULL DEFAULT 0");

            }
            $tblname = 'bazara_product_details';
            $wp_track_table = $table_prefix . $tblname;

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'Prices' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD Prices varchar(800)  NULL ");

            }

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'Discounts' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD Discounts TEXT  NULL,ADD DefaultSellPriceLevel TINYINT(1)  NULL DEFAULT 0,ADD DefaultDiscountLevel TINYINT(1)  NULL DEFAULT 0,DROP COLUMN Price,DROP COLUMN Regular_price ");
                $wpdb->query($wpdb->prepare("DELETE FROM $wp_track_table"));

            }
            
            $tblname = 'bazara_pictures';
            $wp_track_table = $table_prefix . $tblname;

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'Deleted' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
            $wpdb->query("ALTER TABLE $wp_track_table ADD Deleted TINYINT(1)  NULL DEFAULT 0 ");
            }

            $tblname = 'bazara_photo_gallery';
            $wp_track_table = $table_prefix . $tblname;

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'Deleted' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
            $wpdb->query("ALTER TABLE $wp_track_table ADD Deleted TINYINT(1)  NULL DEFAULT 0 ");
            }
            $tblname = 'bazara_category';
            $wp_track_table = $table_prefix . $tblname;

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'isSync' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
            $wpdb->query("ALTER TABLE $wp_track_table ADD isSync TINYINT(1)  NULL DEFAULT 0 ");
            }

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'ExtraDataId' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
            $wpdb->query("ALTER TABLE $wp_track_table ADD ExtraDataId BIGINT  NULL DEFAULT 0 ");
            }
            
            $tblname = 'bazara_product_properties';
            $wp_track_table = $table_prefix . $tblname;

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'DisplayType' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
            $wpdb->query("ALTER TABLE $wp_track_table ADD DataType TINYINT(3)  NULL DEFAULT 0,ADD DisplayType TINYINT(3)  NULL DEFAULT 0");
            }
           
            $tblname = 'bazara_products';
            $wp_track_table = $table_prefix . $tblname;
            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'lenght' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(!empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table drop column lenght");
                $wpdb->query("ALTER TABLE $wp_track_table add length DECIMAL(23,8) NULL");

            }
            $tblname = 'bazara_products';
            $wp_track_table = $table_prefix . $tblname;
            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'length' AND TABLE_SCHEMA = '$databaseSchema'"  );
            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table ADD length DECIMAL(23,8) NULL");

            }
            $tblname = 'bazara_products';
            $wp_track_table = $table_prefix . $tblname;
            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'unitRatio' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table add unitRatio float NULL");

            }
            $tblname = 'bazara_products';
            $wp_track_table = $table_prefix . $tblname;
            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'description' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table add description text NULL");

            }
            $tblname = 'bazara_products';
            $wp_track_table = $table_prefix . $tblname;
            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'RowVersion' AND DATA_TYPE='BIGINT' AND TABLE_SCHEMA = '$databaseSchema'"  );
            if(empty($row)){
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_visitor_products';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                // $tblname = 'bazara_transactions';
                // $wp_track_table = $table_prefix . $tblname;
                // $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_stores';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_product_properties';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_product_details';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_product_assets';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_pictures';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_photo_gallery';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_persons';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_person_groups';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_orders';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_order_details';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_extra_data';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_banks';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_sub_category';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                $tblname = 'bazara_regions';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` BIGINT";
                foreach ($sq as $s)
                    {
                        $wpdb->query( $s );

                    }
            }
            $tblname = 'bazara_products';
            $wp_track_table = $table_prefix . $tblname;
            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'RowVersion' AND DATA_TYPE='decimal' AND TABLE_SCHEMA = '$databaseSchema'"  );
            if(empty($row)){
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_visitor_products';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                // $tblname = 'bazara_transactions';
                // $wp_track_table = $table_prefix . $tblname;
                // $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_stores';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_product_properties';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_product_details';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_product_assets';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_pictures';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_photo_gallery';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_persons';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_person_groups';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_orders';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_order_details';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_extra_data';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_banks';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_sub_category';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                $tblname = 'bazara_regions';
                $wp_track_table = $table_prefix . $tblname;
                $sq[] = "ALTER TABLE $wp_track_table MODIFY column `RowVersion` decimal(38,0)";
                foreach ($sq as $s)
                    {
                        $wpdb->query( $s );

                    }
            }
            $tblname = 'bazara_logs';
            $wp_track_table = $table_prefix . $tblname;
            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$wp_track_table' AND column_name = 'log_updated_date' AND TABLE_SCHEMA = '$databaseSchema'"  );

            if(empty($row)){
                $wpdb->query("ALTER TABLE $wp_track_table add log_updated_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }
            if (class_exists('bazara_ratio_calculator'))
            {
            $title = 'گرم';    
            $attrs = array();
                $attributes = wc_get_attribute_taxonomies();
                if (!empty($attributes)) {
                    foreach ($attributes as $key => $value) {
                        array_push($attrs, $attributes[$key]->attribute_name);
                    }
                }
            if (!in_array($title, $attrs)) {
                $args = array(
                    'slug' => sanitize_title($title),
                    'name' => $title,
                    'type' => 'select',
                    'orderby' => 'menu_order',
                    'has_archives' => false,
                    'limit' => 1,
                    'is_in_stock' => 1
                );

                wc_create_attribute($args);
                WC_Post_Types::register_taxonomies();


            }
            $tblname = 'bazara_product_assets';
            $wp_track_table = $table_prefix . $tblname;

            $wpdb->query("ALTER TABLE $wp_track_table Modify Count1 DECIMAL(23,9)  NULL,Modify Count2 DECIMAL(23,9)  NULL  ");
            
            
          }
            $versions['databaseVersion'] = $this->dbVersion;
            update_option( 'bazara_options', $versions ,false);
        }
    }
    function empty_tables()
    {
        global $wpdb;
        $sql = array();

        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_product_assets";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_products";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_product_details";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_product_properties";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_extra_data";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_person_groups";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_visitor_products";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_pictures";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_photo_gallery";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_category";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_detail_properties";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_persons";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_banks";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_stores";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_sub_category";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_orders";
        $sql[] = "DELETE FROM {$wpdb->prefix}bazara_order_details";

        foreach ($sql as $sq)
        {
            $wpdb->query( $sq );

        }
    }
    function drop_tables()
    {
        global $wpdb;
        $sql = array();

        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_product_assets";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_products";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_product_details";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_product_properties";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_extra_data";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_person_groups";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_visitor_products";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_pictures";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_photo_gallery";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_category";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_detail_properties";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_persons";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_banks";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_stores";
        $sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}bazara_sub_category";

        foreach ($sql as $sq)
        {
            $wpdb->query( $sq );

        }
    }
    function create_plugin_database_tables()
    {
        global $table_prefix, $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = array();
        $tblname = 'bazara_logs';
        $wp_track_table = $table_prefix . $tblname;

        #Check to see if the table exists already, if not, then create it

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `log_id` MEDIUMINT UNSIGNED NOT NULL auto_increment,
			`log_date` varchar(20) NOT NULL,
			`log_title` Text NOT NULL,
			`log_comment` Text NOT NULL,
			`is_success` TINYINT(1) NOT NULL,
            `log_updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE (`log_id`) ) $charset_collate";


        }
        $tblname = 'bazara_persons';
        $wp_track_table = $table_prefix . $tblname;
        
        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
            ( `p_id` INT(11)  NOT NULL auto_increment,
            `PersonId` INT(11)   NULL ,
            `PersonClientID` int(11)  NULL,
            `PersonGroupId` int(11)  NULL,
            `PersonCode` int(11)  NULL,
            `FirstName` Text  NULL,
            `LastName` Text  NULL,
            `Email` Text  NULL,
            `Deleted` TINYINT(1)  NULL,
            `RowVersion` bigint  NULL,
            `isSync` TINYINT(1)  NULL,
            `Mobile` varchar(100)  NULL,
            `Address` varchar(700)  NULL,
            UNIQUE (`p_id`) ) $charset_collate";

        }

        $tblname = 'bazara_products';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `p_id` INT(11)  NOT NULL auto_increment,
			`ProductId` int(11)  NULL,
			`ProductCode` int(11)  NULL,
			`ProductName` Text  NULL,
            `unitName1` varchar(200)  NULL,
            `unitName2` varchar(200)  NULL,
            `unitRatio` float  NULL,
			`Status` Text  NULL,
			`Category` Text  NULL,
			`TaxPercent` INT(10)  NULL,
			`ChargePercent` INT(10) NOT NULL,
			`tax` Text NULL,
			`store_id` INT(10) NULL,
			`qty` INT(10)  NULL,
			`sku` Text NULL,
			`Deleted` TINYINT(1)  NULL,
			`stockSync` TINYINT(1)  NULL,
            `detailSync` TINYINT(1)  NULL,
			`priceSync` TINYINT(1)  NULL,
			`queue` TinyInt(1)  NULL,
			`RowVersion` INT(11)  NULL,
			`new` TINYINT(1)  NULL,
            `Post_ID` BIGINT  NULL,
            `width` float  NULL,
            `length` float  NULL,
            `height` float  NULL,
            `weight` float  NULL,
			`barcode` Text  NULL,
            `description` Text  NULL,
			UNIQUE (`p_id`) ) $charset_collate";


        }

        $tblname = 'bazara_product_assets';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `p_a_id` INT(11)  NOT NULL auto_increment,
			`ProductDetailStoreAssetId` int(11)  NULL,
			`ProductDetailId` int(11)  NULL,
			`Count1` DECIMAL(23,9)  NULL,
			`Count2` DECIMAL(23,9)  NULL,
			`StoreId` int(11)  NULL,
			`RowVersion` INT(11)  NULL,
			`Deleted` TINYINT(1)  NULL,
			UNIQUE (`p_a_id`) ) $charset_collate";


        }
        $tblname = 'bazara_product_details';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `p_d_id` INT(11)  NOT NULL auto_increment,
			`ProductId` int(11)  NULL,
			`ProductDetailId` varchar(20)  NULL,
			`Properties` Text  NULL,
			`Prices` varchar(800)  NULL,
            `Discounts` varchar(800)  NULL,
			`DefaultSellPriceLevel` TINYINT(1)  NULL,
			`DefaultDiscountLevel` TINYINT(1)  NULL,
			`Deleted` TINYINT(1)  NULL,
			`isSync` TINYINT(1)  NULL,
			`queue` TinyInt(1)  NULL,
			`RowVersion` INT(11)  NULL,
			UNIQUE (`p_d_id`) ) $charset_collate";


        }
        $tblname = 'bazara_product_attributes';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `b_b_p_aid` INT(11)  NOT NULL auto_increment,
			`ProductDetailId` int(11)  NULL,
			`Code` int(11) NULL,
			`Value` int(11)  NULL,
			UNIQUE (`b_b_p_aid`) ) $charset_collate";


        }
        $tblname = 'bazara_product_properties';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `p_p_id` INT(11)  NOT NULL auto_increment,
			`PropertyDescriptionCode` int(11)  NULL,
			`PropertyDescriptionId` int(11)  NULL,
            `DataType` int(4)  NULL,
			`DisplayType` int(4)  NULL,
			`Title` Text  NULL,
			`Deleted` TINYINT(1)  NULL,
			`RowVersion` INT(11)  NULL,
			UNIQUE (`p_p_id`) ) $charset_collate";

        }
        $tblname = 'bazara_extra_data';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `ExtraDataId` INT(11)  NOT NULL auto_increment,
			`ItemType` int(11)  NULL,
			`ItemId` int(11)  NULL,
			`Data` Text  NULL,
			`Deleted` TINYINT(1)  NULL,
			`RowVersion` INT(11)  NULL,
			UNIQUE (`ExtraDataId`) ) $charset_collate";

        }
        $tblname = 'bazara_regions';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `RegionID` INT(11)  NOT NULL auto_increment,
			`CityID` INT(11)  NULL,
			`CityName` Text  NULL,
			`ProvinceID` int(11)  NULL,
			`ProvinceName` Text  NULL,
			`MapCode` Text  NULL,
			`RowVersion` INT(11)  NULL,
			UNIQUE (`RegionID`) ) $charset_collate";

        }
        $tblname = 'bazara_clients';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {

            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `order_detail_clientId` INT UNSIGNED  NULL,
			`order_clientId` INT  NULL ,
			`receipt_clientId` INT  NULL ,
			`cheque_clientId` INT  NULL ,
			`person_clientId` INT  NULL ) $charset_collate";
        }

        $tblname = 'bazara_visitor_products';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `v_id` INT(11)  NOT NULL auto_increment,
			`VisitorProductId` INT(11)   NULL ,
			`ProductDetailId` int(11)  NULL,
			`VisitorId` int(11)  NULL,
			`Deleted` TINYINT(1)  NULL,
			`RowVersion` INT(11)  NULL,
			UNIQUE (`v_id`) ) $charset_collate";

        }

        $tblname = 'bazara_person_groups';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `g_id` INT(11)  NOT NULL auto_increment,
			`PersonGroupId` INT(11)   NULL ,
			`Name` Text  NULL,
			`DiscountPercent` int(11)  NULL,
			`SellPriceLevel` int(11)  NULL,
			`RowVersion` int(11)  NULL,
			UNIQUE (`g_id`) ) $charset_collate";

        }

        $tblname = 'bazara_category';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `ca_id` INT(11)  NOT NULL auto_increment,
			`ExtraDataId` INT(11)   NULL ,
			`CategoryID` INT(11)   NULL ,
			`CategoryName` Text  NULL,
			`ItemType` int(11)  NULL,
			`ParentID` int(11)  NULL,
			`term_id` int(11)  NULL,
            `isSync` TINYINT(1)  NULL,
			UNIQUE (`ca_id`) ) $charset_collate";

        }
        $tblname = 'bazara_sub_category';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `cat_id` BIGINT  NOT NULL auto_increment,
			`ProductCategoryId` INT(11)   NULL ,
			`Name` Text  NULL,
			`RowVersion` BIGINT  NULL,
			`Deleted` TINYINT(1)  NULL,
            `isSync` TINYINT(1)  NULL,
			UNIQUE (`cat_id`) ) $charset_collate";

        }
        $tblname = 'bazara_pictures';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `pi_id` INT(11)  NOT NULL auto_increment,
			`PictureId` INT(11)   NULL ,
			`FileName` Text  NULL,
			`Url` Text NULL,
			`isSync` TinyInt(1)  NULL,
			`queue` TinyInt(1)  NULL,
            `RowVersion` int(11)  NULL,
			`Deleted` TINYINT(1)  NULL,

			UNIQUE (`pi_id`) ) $charset_collate";

        }
        $tblname = 'bazara_photo_gallery';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `pg_id` INT(11)  NOT NULL auto_increment,
			`PhotoGalleryId` INT(11)   NULL ,
			`PictureId` int(11)  NULL,
			`ItemCode` int(11)  NULL,
			`RowVersion` int(11)  NULL,
			`Deleted` TINYINT(1)  NULL,

			UNIQUE (`pg_id`) ) $charset_collate";

        }

        $tblname = 'bazara_detail_properties';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `pdp_id` INT(11)  NOT NULL auto_increment,
			`ProductDetailId` INT(11)   NULL ,
			`PropertyID` int(11)  NULL,
			`PropertyTitle` int(11)  NULL,
			UNIQUE (`pdp_id`) ) $charset_collate";

        }

        $tblname = 'bazara_banks';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `b_b_id` INT(11)  NOT NULL auto_increment,
			`BankId` INT(11)   NULL ,
			`BankClientId` int(11)  NULL,
			`BankCode` int(11)  NULL,
            `Name` Text  NULL,
			`Description` Text  NULL,
			`Deleted` TINYINT(1)  NULL,
			`RowVersion` int(11)  NULL,
			UNIQUE (`b_b_id`) ) $charset_collate";

        }
        $tblname = 'bazara_stores';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `s_id` INT(11)  NOT NULL auto_increment,
			`StoreId` INT(11)   NULL ,
			`StoreCode` int(11)  NULL,
            `Name` Text  NULL,
			`Comment` Text  NULL,
			`Deleted` TINYINT(1)  NULL,
			`RowVersion` int(11)  NULL,
			UNIQUE (`s_id`) ) $charset_collate";

        }
        $tblname = 'bazara_orders';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `o_id` bigint(11)  NOT NULL auto_increment,
			`OrderId` bigint(11)   NULL ,
			`OrderClientId` bigint(11)  NULL,
            `OrderCode` bigint(11)  NULL,
            `PersonId` bigint(11)  NULL,
			`OrderDate` Text  NULL,
			`Deleted` TINYINT(1)  NULL,
			`RowVersion` int(11)  NULL,
			UNIQUE (`o_id`) ) $charset_collate";

        }
        $tblname = 'bazara_order_details';
        $wp_track_table = $table_prefix . $tblname;

        if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table)
        {
            $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}$tblname`
			( `o_d_id` bigint(11)  NOT NULL auto_increment,
			`OrderId` bigint(11)   NULL ,
			`OrderDetailId` bigint(11)  NULL,
            `OrderDetailClientId` bigint(11)  NULL,
            `ProductDetailId` bigint(11)  NULL,
            `Count1` bigint(11)  NULL,
            `Count2` bigint(11)  NULL,
			`Price` Text  NULL,
			`Deleted` TINYINT(1)  NULL,
			`RowVersion` int(11)  NULL,
			UNIQUE (`o_d_id`) ) $charset_collate";

        }
        if (!empty($sql))
        {
            require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
            foreach ($sql as $sq)
            {
                dbDelta($sq);

            }

            $tablename = 'bazara_clients';
            $wp_track_table = $table_prefix . $tablename;



            $results =	$wpdb->get_results("SELECT count(1) as cnt from $wp_track_table");
            if ($results[0]->cnt == 0)
            {
                $wpdb->insert( $wp_track_table, array(
                    'order_detail_clientId' => 0,
                    'order_clientId' => 0,
                    'receipt_clientId' =>0,
                    'cheque_clientId' => 0,
                    'person_clientId' => 0
                ),
                    array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )) ;
            }


        }
    }
    function plugin_action_links( $links ) {
        $links[] = '<a href="' . admin_url( 'admin.php?page=bazara&nav=sync_options' ) . '">' . __( 'تنظیمات', 'bazara' ) . '</a>';
        return $links;
    }
    
}
function BAZARA_WC_ORDER() {
	return bazara::instance();
}
BAZARA_WC_ORDER();