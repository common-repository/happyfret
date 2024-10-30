<?php
/*
* Plugin Name: Happyfret
* Plugin URI: http://www.advise.fr
* Description: Plugin transporteur Happy Fret pour WooCommerce 
* Version: 1.2.1
* Author: @RenaudMG
* Author URI: http://www.advise.fr
* Text Domain: happyfret
* Domain Path: /languages
*/

if ( ! defined('ABSPATH')) { exit; }  // if direct access 

if ( ! defined('HAPPYFRET_DEBUG')) { define('HAPPYFRET_DEBUG', true); }

// Dépendancies
include_once plugin_dir_path( __FILE__ ).'classes/HappyfretApi.class.php';
include_once plugin_dir_path( __FILE__ ).'classes/HappyfretCategory.class.php';
include_once plugin_dir_path( __FILE__ ).'classes/HappyfretPackageTracking.class.php';
include_once plugin_dir_path( __FILE__ ).'classes/HappyfretPackingOrder.class.php';
include_once plugin_dir_path( __FILE__ ).'classes/HappyfretPackingOrderProduct.class.php';
include_once plugin_dir_path( __FILE__ ).'classes/HappyfretParcelTypeStock.class.php';
include_once plugin_dir_path( __FILE__ ).'classes/HappyfretParcelType.class.php';
include_once plugin_dir_path( __FILE__ ).'classes/HappyfretProduct.class.php';
include_once plugin_dir_path( __FILE__ ).'classes/HappyfretProductSynchronizer.class.php';
include_once plugin_dir_path( __FILE__ ).'classes/HappyfretShippingOffer.class.php'; 


// Appelle le constructeur du plugin
new HappyFretPlugin();

/**
* Classe utilisée pour manipuler le plugin
*/
class HappyfretPlugin {
    const ADMIN_MENU_NAME  = 'HappyFret';
    
    const CATEGORY_SUBMENU_NAME_SETTINGS = 'HappyFretSettings';
    const CATEGORY_SUBMENU_NAME_PARCELS = 'HappyFretParcels';
    const CATEGORY_SUBMENU_NAME_CATEGORIES = 'HappyFretCategory';
    const CATEGORY_SUBMENU_NAME_PACKING_ORDERS = 'HappyFretPackingOrders';
    const CATEGORY_SUBMENU_NAME_SHIPPING_LABELS = 'HappyFretShippingLabels';
    const ADMIN_SUBMENU_PRODUCTS_SYNCHRONIZATION = 'HappyFretProductsSynchronisation';
    const TEXT_DOMAIN = 'happyfret';
    const CLIENT_TYPE_STANDALONE = 'standalone';
    const CLIENT_TYPE_LOGISTICS = 'logistics';
    
        
    // Default values
    private $categories = [
        [        
            'name' => 'Enveloppe',
        ],
        [        
            'name' => 'Carton'
        ]
    ];
    private $parcelTypes = [
        [
            'ref' =>            'Cx040',
            'name' =>           'Carton 40',
            'categoryName' =>   'Carton',
            'width' =>          40,
            'height' =>         40,
            'depth' =>          40,
            'parcelWeight' =>   1,
            'maxWeight' =>      10,
            'visible'=>         true,
            'price' =>          1.5
        ]
    ];
    private $parcelTypeStock = [
        [
            'parcelTypeName' => 'Carton 40',
            'quantity' =>       10
        ]
    ];
    
    private static $options = [
        'happyfret_api_key' => [
            'name' => 'Your API Key',
            'default' => null,
            'display' => true
        ],
        'happyfret_api_email' => [
            'name' => 'Your API Email',
            'default' => null,
            'display' => true
        ],
        'happyfret_api_base_url' => [
            'name' => 'API base URL',
            'default' => 'http://logistique.happyfret.com/api/cms/',
            'display' => false
        ],
        'happyfret_api_account_validation' => [
            'name' => 'API account validation URL',
            'default' => 'test-api-key',
            'display' => false
        ],      
        'happyfret_api_get_profile' => [
            'name' => 'API profile URL',
            'default' => 'user-profile',
            'display' => false
        ],      
        'happyfret_api_resolve_parcels' => [
            'name' => 'API resolve parcels URL',
            'default' => 'resolve-parcels',
            'display' => false
        ],
        'happyfret_api_logistics_resolve_parcels' => [
            'name' => 'API logistics resolve parcels URL',
            'default' => 'logistics-resolve-parcels',
            'display' => false
        ],
        'happyfret_api_get_shipping_rate' => [
            'name' => 'API get shipping rate URL',
            'default' => 'shipping-rate',
            'display' => false
        ],        
        'happyfret_api_products' => [
            'name' => 'API products URL',
            'default' => 'products',
            'display' => false
        ],   
        'happyfret_api_logistics_transport_offers' => [
            'name' => 'API logistics transport offers URL',
            'default' => 'get-logistics-transport-offers',
            'display' => false
        ],   
        'happyfret_api_standalone_transport_offers' => [
            'name' => 'API standalone transport offers URL',
            'default' => 'get-standalone-transport-offers',
            'display' => false
        ],   
        'happyfret_api_order_confirmation' => [
            'name' => 'API order confirmation',
            'default' => 'accept-standalone-transport-offer',
            'display' => false
        ],   
        /*'happyfret_api_logistics_transport_offers' => [
            'name' => 'API logistics transport offers URL',
            'default' => 'get-logistics-transport-offers',
            'display' => false
        ],*/          
        'happyfret_api_logistics_order_confirmation' => [
            'name' => 'API logistics order confirmation',
            'default' => 'accept-logistics-transport-offer',
            'display' => false
        ],   
    ];
    private static $senderAddress = [
        'happyfret_sender_name' => [
            'name' => 'Name',
            'default' => ''
        ],
        'happyfret_sender_address1' => [
            'name' => 'Address 1',
            'default' => ''
        ],
        'happyfret_sender_address2' => [
            'name' => 'Address 2',
            'default' => ''
        ],
        'happyfret_sender_address3' => [
            'name' => 'Address 3',
            'default' => ''
        ],
        'happyfret_sender_zip' => [
            'name' => 'Zip code',
            'default' => ''
        ],
        'happyfret_sender_city' => [
            'name' => 'City',
            'default' => ''
        ],
        'happyfret_sender_state' => [
            'name' => 'State',
            'default' => ''
        ],
        'happyfret_sender_country' => [
            'name' => 'Country',
            'default' => 'FR'
        ],
        'happyfret_sender_contact' => [
            'name' => 'Contact',
            'default' => ''
        ],
        'happyfret_sender_phone' => [
            'name' => 'Phone number',
            'default' => ''
        ],
        'happyfret_sender_mobile' => [
            'name' => 'Mobile',
            'default' => ''
        ],
        'happyfret_sender_email' => [
            'name' => 'Email',
            'default' => ''
        ]       
    ];




    /**
    * Class contructor
    */
    public function __construct() { 
        register_activation_hook(__FILE__, array($this, 'happyfret_install'));
        register_deactivation_hook(__FILE__, array($this,'happyfret_uninstall'));
        register_uninstall_hook(__FILE__, 'happyfret_uninstall');
        
        add_action('admin_menu', array($this, 'happyfret_add_admin_menu'));        
        add_action('admin_head', array($this,'happyfret_custom_css'));
        add_action('init', array($this, 'happyfret_load_text_domain'));   
                
        add_action( 'woocommerce_order_status_completed', array($this, 'happyfret_process_order_status_completed') );
        add_action( 'woocommerce_order_status_changed', array($this, 'happyfret_process_order_status_changed'), 10, 3 );
        add_action( 'woocommerce_checkout_order_processed', array($this, 'happyfret_process_checkout_order_processed'),  1, 1  );
        
        add_action( 'admin_notices', array($this,'happyfret_check_api_configuration'));
        
        add_action( 'admin_post_save_parcel_type', array($this, 'happyfret_process_post_save_parceltype') );
        add_action( 'admin_post_save_settings', array($this, 'happyfret_process_post_save_settings') );
        add_action( 'admin_post_save_category', array($this, 'happyfret_process_post_save_category') );
    
        global $wpdb;        
        if (true == HAPPYFRET_DEBUG) {
            $wpdb->show_errors();
        } else {
            $wpdb->hide_errors();
        }
        
        include_once plugin_dir_path( __FILE__ ).'/classes/HappyfretShippingMethod.class.php';
         
    }
	
    /**
    * Plugin install -> database creation
    */
    public function happyfret_install() {   
        global $wpdb;     
        $wpdb->show_errors();
        // Tables creation
        $wpdb->query(HappyfretCategory::getInstallQuery());
        $wpdb->query(HappyfretParcelType::getInstallQuery());            
        $wpdb->query(HappyfretParcelTypeStock::getInstallQuery());   
        $wpdb->query(HappyfretPackageTracking::getInstallQuery());   
        $wpdb->query(HappyfretPackingOrder::getInstallQuery());   
        $wpdb->query(HappyfretPackingOrderProduct::getInstallQuery());   
        $wpdb->query(HappyfretProduct::getInstallQuery());   
        $wpdb->query(HappyfretShippingOffer::getInstallQuery());   
        
        // Default values recording   
        $storedCategories = [];
        foreach ($this->categories as $categoryArray) {
            $category = new HappyfretCategory();
            $category->setName($categoryArray['name']);
            $category->sqlPersist();
            $storedCategories[$category->getName()] = $category;
        };
        $storedParcelTypes = [];
        foreach ($this->parcelTypes as $parcelTypeArray) {
            $parcelType = new HappyfretParcelType();
            $parcelType->setRef($parcelTypeArray['ref']);
            $parcelType->setName($parcelTypeArray['name']);
            $parcelType->setCategory($storedCategories[$parcelTypeArray['categoryName']]);
            $parcelType->setWidth($parcelTypeArray['width']);
            $parcelType->setHeight($parcelTypeArray['height']);
            $parcelType->setDepth($parcelTypeArray['depth']);
            $parcelType->setParcelWeight($parcelTypeArray['parcelWeight']);
            $parcelType->setMaxWeight($parcelTypeArray['maxWeight']);
            $parcelType->setVisible($parcelTypeArray['visible']);
            $parcelType->setPrice($parcelTypeArray['price']);
            $parcelType->sqlPersist();
            $storedParcelTypes[$parcelType->getName()] = $parcelType;
        } 
        foreach($this->parcelTypeStock as $parcelTypeStockArray) {
            $parcelTypeStock = new HappyfretParcelTypeStock();
            $parcelTypeStock->setParcelType($storedParcelTypes[$parcelTypeStockArray['parcelTypeName']]);
            $parcelTypeStock->setQuantity($parcelTypeStockArray['quantity']);
            $parcelTypeStock->setDate(date('Y-m-d'));
            $parcelTypeStock->sqlPersist();
        }        
    }
	
    /**
     * Plugin uninstall -> delete all tables in database
     * @global type $wpdb
     */
    public static function happyfret_uninstall() {
        global $wpdb;
        $wpdb->query(HappyfretPackingOrderProduct::getUninstallQuery());
        $wpdb->query(HappyfretPackingOrder::getUninstallQuery());
        $wpdb->query(HappyfretParcelTypeStock::getUninstallQuery());
        $wpdb->query(HappyfretParcelType::getUninstallQuery());
        $wpdb->query(HappyfretCategory::getUninstallQuery());
        $wpdb->query(HappyfretProduct::getUninstallQuery());
        $wpdb->query(HappyfretShippingOffer::getUninstallQuery());
    }
     
    /**
     * Add a menu to the WordPress admin
     */
    public function happyfret_add_admin_menu() {
        add_menu_page('Happy Fret', __('HappyFret', self::TEXT_DOMAIN), 'manage_options', self::ADMIN_MENU_NAME, array($this, 'happyfret_display_introduction'), plugin_dir_url(__FILE__).'assets/logo.png' );
        
        add_submenu_page(self::ADMIN_MENU_NAME, 'Settings', __('Plugin settings', self::TEXT_DOMAIN), 'manage_options', self::CATEGORY_SUBMENU_NAME_SETTINGS, array($this, 'happyfret_display_admin_settings'));
        add_submenu_page(self::ADMIN_MENU_NAME, 'Parcel Categories', __('Parcel Categories', self::TEXT_DOMAIN), 'manage_options', self::CATEGORY_SUBMENU_NAME_CATEGORIES, array($this, 'happyfret_display_parcel_category_submenu'));
        add_submenu_page(self::ADMIN_MENU_NAME, 'Parcels', __('Parcels', self::TEXT_DOMAIN), 'manage_options', self::CATEGORY_SUBMENU_NAME_PARCELS, array($this, 'happyfret_display_admin_menu'));
        add_submenu_page(self::ADMIN_MENU_NAME, 'Packing Orders', __('Packing Orders', self::TEXT_DOMAIN), 'manage_options', self::CATEGORY_SUBMENU_NAME_PACKING_ORDERS, array($this, 'happyfret_display_packing_orders'));
        add_submenu_page(self::ADMIN_MENU_NAME, 'Shipping Labels', __('Shipping Labels', self::TEXT_DOMAIN), 'manage_options', self::CATEGORY_SUBMENU_NAME_SHIPPING_LABELS, array($this, 'happyfret_display_shipping_labels'));

        if (HappyfretPlugin::CLIENT_TYPE_LOGISTICS == get_option('happyfret_client_type')) {
            add_submenu_page(self::ADMIN_MENU_NAME, 'Products synchronization', __('Products synchronization', self::TEXT_DOMAIN), 'manage_options', self::ADMIN_SUBMENU_PRODUCTS_SYNCHRONIZATION, array($this, 'happyfret_display_products_synchronization'));
        }
        // This page will be under "Settings"
        add_options_page(self::ADMIN_MENU_NAME, 'Happy Fret', 'manage_options', 'happyfret-settings', array( $this, 'create_admin_page' ));        
        
    }
    
    public function happyfret_display_introduction() {                     
        $this->happyfret_display_admin_h1();                    
        ?>
        <div class="panel">
            <div class="panel-body">  
                <h2>Happyfret : le plugin qui simplifie vos expéditions</h2>
                <p>Que vous gériez vous-même vos expéditions ou que vous ayez souscrit au service de logistique Happyfret, vous pouvez bénéficer des services de courtage pour bénéficier des meilleures offres de transport pour vos expéditions.</p>
                <p>Pour en savoir plus sur les services Happyfret et obtenir votre clé d'API, rendez-vous sur <a href="https://www.happyfret.com/" target="_blank">https://www.happyfret.com/</a>. Si vous possédez déjà votre clé d'API, vous pouvez configurer votre plugin Happyfret sans attendre.</p>
            </div>                
        </div>
        <?php
    }
    
    
    /**
     * 
     */
    public function happyfret_display_admin_settings() {        
        $this->happyfret_display_admin_h1();                    
        ?>
        <div class="panel">
            <div class="panel-heading"><?php _e('Plugin settings', self::TEXT_DOMAIN);?></div>
            <div class="panel-body">  
                <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                    <input type="hidden" name="action" value="save_settings" />
                    <table class="table table-bordered th-left" cellpadding="0" cellspacing="0">
                        <tr><th colspan="2">&nbsp;</th><tr>
                        <tr><th colspan="2"><?php _e('API Settings', self::TEXT_DOMAIN); ?></th><tr>
                        <tr><th colspan="2">&nbsp;</th><tr>
                        <?php foreach (self::$options as $optionName => $optionArray) { if ($optionArray['display']) { ?>
                        <tr>
                            <th class="col-1"><?php _e($optionArray['name'], self::TEXT_DOMAIN);?></th>
                            <td class="col-3"><input type="text" name="<?php echo $optionName; ?>" value="<?php echo get_option($optionName); ?>" style="width:100%"></td>
                        </tr>
                        <?php }} ?>
                        <tr><th colspan="2">&nbsp;</th></tr>
                        <tr><th colspan="2"><?php _e('Sender Settings', self::TEXT_DOMAIN); ?></th></tr>
                        <tr><th colspan="2">&nbsp;</th></tr>
                        <?php foreach (self::$senderAddress as $optionName => $optionArray) { ?>
                        <tr>
                            <th class="col-1"><?php _e($optionArray['name'], self::TEXT_DOMAIN);?></th>
                            <td class="col-3"><input type="text" name="<?php echo $optionName; ?>" value="<?php echo get_option($optionName); ?>" style="width:100%"></td>
                        </tr>
                        <?php } ?>
                    </table>  
                    <div class="text-right">
                        <button type="submit" class="button button-primary"><?php _e('Save settings', self::TEXT_DOMAIN); ?></button>
                    </div>
                </form>                                
            </div>
        </div>
        <?php
    }
    
    
    /**
     * Display plugin administration page
     */
    public function happyfret_display_admin_menu() {
        $this->happyfret_display_admin_h1();
        if (isset($_POST['action'])) {
            if ('add_parcel_type' == sanitize_text_field($_POST['action'])) {
                $parcelType = new HappyfretParcelType();
                $this->happyfret_display_parceltype_form($parcelType, __('New parcel type', self::TEXT_DOMAIN));
            }
            if ('edit_parcel_type' == sanitize_text_field($_POST['action'])) {
                $parcelType = HappyfretParcelType::sqlFindOneById(filter_var($_POST['parcel-type-id'], FILTER_SANITIZE_NUMBER_INT));
                $this->happyfret_display_parceltype_form($parcelType, __('Edit parcel type', self::TEXT_DOMAIN));
            }
            if ('delete_parcel_type' == sanitize_text_field($_POST['action'])) {
                $parcelType = HappyfretParcelType::sqlFindOneById(filter_var($_POST['parcel-type-id'], FILTER_SANITIZE_NUMBER_INT));
                $parcelType->sqlDeleteCascade();
                if (true == HAPPYFRET_DEBUG) { error_log('Parcel Type #' . $parcelType->getId() . ' has been deleted.</p>'); }
                $this->happyfret_display_parceltype_list();
            }            
            if ('show_parcel_type_stock' == sanitize_text_field($_POST['action'])) {
                $parcelType = HappyfretParcelType::sqlFindOneById(filter_var($_POST['parcel-type-id'], FILTER_SANITIZE_NUMBER_INT));
                $this->happyfret_display_parceltype_stock_list($parcelType);
            }
            if ('add_parcel_type_stock' == sanitize_text_field($_POST['action'])) {
                $parcelType = HappyfretParcelType::sqlFindOneById(filter_var($_POST['parcel-type-id'], FILTER_SANITIZE_NUMBER_INT));
                $parcelTypeStock = new HappyfretParcelTypeStock();
                $parcelTypeStock->setParcelType($parcelType);
                $this->happyfret_display_parceltype_stock_form($parcelTypeStock, __('New parcel type stock', self::TEXT_DOMAIN));
            }
            if ('edit_parcel_type_stock' == sanitize_text_field($_POST['action'])) {
                $parcelTypeStock = HappyfretParcelTypeStock::sqlFindOneById(filter_var($_POST['parcel-type-stock-id'], FILTER_SANITIZE_NUMBER_INT));
                $this->happyfret_display_parceltype_stock_form($parcelTypeStock, __('Edit parcel type', self::TEXT_DOMAIN));
            }
            if ('delete_parcel_type_stock' == sanitize_text_field($_POST['action'])) {
                $parcelTypeStock = HappyfretParcelTypeStock::sqlFindOneById(filter_var($_POST['parcel-type-stock-id'], FILTER_SANITIZE_NUMBER_INT));
                $parcelTypeStock->sqlDeleteCascade();
                if (true == HAPPYFRET_DEBUG) { error_log('Parcel Type Stock #' . $parcelTypeStock->getId() . ' has been deleted.'); }
                $this->happyfret_display_parceltype_stock_list($parcelTypeStock->getParcelType());
            }
            if ('save_parcel_type_stock' == sanitize_text_field($_POST['action'])) {
                if ('' == $_POST['parcel-type-stock-id']) {
                    $parcelTypeStock = new HappyfretParcelTypeStock();
                } else {
                    $parcelTypeStock = HappyfretParcelTypeStock::sqlFindOneById(filter_var($_POST['parcel-type-stock-id'], FILTER_SANITIZE_NUMBER_INT));
                }
                if (isset($_POST['parcel-type-id'])) {
                    $parcelTypeStock->setParcelType(HappyfretParcelType::sqlFindOneById(filter_var($_POST['parcel-type-id'], FILTER_SANITIZE_NUMBER_INT)));
                }
                $parcelTypeStock->setDate(DateTime::createFromFormat('d/m/Y', $_POST['parcel-type-stock-date'])->format('Y-m-d'));
                $parcelTypeStock->setQuantity(filter_var($_POST['parcel-type-stock-quantity'], FILTER_SANITIZE_NUMBER_INT));
                $parcelTypeStock->sqlPersist();
                $this->happyfret_display_parceltype_stock_list($parcelTypeStock->getParcelType());
            }
        } else {            
            $this->happyfret_display_parceltype_list();
        }  
    }
    
    
    /**
     * 
     */
    public function happyfret_process_post_save_settings() {
        foreach (self::$options as $optionName => $optionLabel) {
            if (isset($_POST[$optionName])) {
                update_option($optionName, sanitize_text_field($_POST[$optionName]));
            }
        }
        foreach (self::$senderAddress as $optionName => $optionLabel) {
            if (isset($_POST[$optionName])) {
                update_option($optionName, sanitize_text_field($_POST[$optionName]));
            }
        }
        $this->happyfret_check_api_configuration(true);            
        // Redirect to the parcel types list
        wp_redirect(admin_url('admin.php?page=' . self::CATEGORY_SUBMENU_NAME_SETTINGS));
        // Must exit() !
        exit();        
    }
    
    
    /**
     * 
     */
    public function happyfret_process_post_save_category() {
        if ('save_category' == sanitize_text_field($_POST['action'])) {
                if ('' == $_POST['category-id']) {
                    $category = new HappyfretCategory();
                } else {
                    $category = HappyfretCategory::sqlFindOneById(filter_var($_POST['category-id'], FILTER_SANITIZE_NUMBER_INT));
                }                
                $category->setName(filter_var($_POST['category-name'], FILTER_SANITIZE_STRING));
                $category->sqlPersist();
        }
        // Redirect to the parcel types list
        wp_redirect(admin_url('admin.php?page=' . self::CATEGORY_SUBMENU_NAME_CATEGORIES));
        // Must exit() !
        exit();        
    }
    
    
    public function happyfret_process_post_save_parceltype() {
        if ('' == $_POST['parcel-type-id']) {
            $parcelType = new HappyfretParcelType();
        } else {
            $parcelType = HappyfretParcelType::sqlFindOneById(filter_var($_POST['parcel-type-id'], FILTER_SANITIZE_NUMBER_INT));
        }
        $parcelType->setRef(filter_var($_POST['parcel-type-ref'], FILTER_SANITIZE_STRING));
        $parcelType->setName(filter_var($_POST['parcel-type-name'], FILTER_SANITIZE_STRING));
        if (isset($_POST['parcel-type-category'])) {
            $parcelType->setCategory(HappyfretCategory::sqlFindOneById(filter_var($_POST['parcel-type-category'], FILTER_SANITIZE_NUMBER_INT)));
        }
        $parcelType->setWidth(filter_var($_POST['parcel-type-width'], FILTER_SANITIZE_NUMBER_INT));
        $parcelType->setHeight(filter_var($_POST['parcel-type-height'], FILTER_SANITIZE_NUMBER_INT));
        $parcelType->setDepth(filter_var($_POST['parcel-type-depth'], FILTER_SANITIZE_NUMBER_INT));        
        $parcelType->setParcelWeight($_POST['parcel-type-parcel-weight']);
        $parcelType->setMaxWeight($_POST['parcel-type-max-weight']);
        $parcelType->setPrice($_POST['parcel-type-price']);                
        if (isset($_POST['parcel-type-visible'])) {
            $parcelType->setVisible(true);
        } else {
            $parcelType->setVisible(false);
        }
        $parcelType->sqlPersist();
        // Redirect to the parcel types list
        wp_redirect(admin_url('admin.php?page=' . self::CATEGORY_SUBMENU_NAME_PARCELS));
        // Must exit() !
        exit();
    }
    
    /**
     * 
     * @global type $wpdb
     */
    private function happyfret_display_parceltype_list() {        
        global $wpdb;
        ?>
        <div class="panel">                       
            <div class="panel-heading"><?php _e('Parcel types list', self::TEXT_DOMAIN);?></div>
            <div class="panel-body">  
                <div class="text-right">
                    <form method="post">
                        <input type="hidden" name="action" value="add_parcel_type" />
                        <button type="submit" class="button button-primary"><?php _e('Add a new parcel type', self::TEXT_DOMAIN); ?></button>
                    </form>
                </div>
                <table class="table table-bordered" border="1" cellspacing="0" cellpadding="5" width="100%">
                    <thead>
                        <tr>
                            <th><?php _e('Id', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Category', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Reference', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Name', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Width', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Height', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Depth', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Parcel weight', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Max. weight', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Price', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Visible', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Stock', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Action', self::TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach(HappyfretParcelType::sqlFindAll() as $parcelType) { ?>
                        <tr>
                            <td><?php echo $parcelType->getId(); ?></td>
                            <td>
                                <?php 
                                if (null != $parcelType->getCategory()) {
                                    echo $parcelType->getCategory()->getName();
                                }
                                ?>
                            </td>
                            <td><?php if (null != $parcelType->getRef()) { echo $parcelType->getRef(); } ?></td>
                            <td><?php if (null != $parcelType->getName()) { echo $parcelType->getName(); } ?></td>
                            <td><?php echo $parcelType->getWidth(); ?></td>
                            <td><?php echo $parcelType->getHeight(); ?></td>
                            <td><?php echo $parcelType->getDepth(); ?></td>
                            <td><?php echo $parcelType->getParcelWeight(); ?></td>
                            <td><?php echo $parcelType->getMaxWeight(); ?></td>
                            <td><?php echo $parcelType->getPrice(); ?></td>
                            <td><?php echo $parcelType->getVisible(); ?></td>
                            <td>
                                <?php      
                                $stock = 0;
                                foreach(HappyfretParcelTypeStock::sqlFindByParcelType($parcelType) as $parcelTypeStock) {
                                    $stock += $parcelTypeStock->getQuantity();
                                }
                                echo $stock;
                                ?>
                            </td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="action" value="add_parcel_type_stock" />
                                    <input type="hidden" name="parcel-type-id" value="<?php echo $parcelType->getId(); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e('Add a new stock entry', self::TEXT_DOMAIN); ?></button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="action" value="show_parcel_type_stock" />
                                    <input type="hidden" name="parcel-type-id" value="<?php echo $parcelType->getId(); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e('Show stock', self::TEXT_DOMAIN); ?></button>
                                </form>                                
                                <form method="post">
                                    <input type="hidden" name="action" value="edit_parcel_type" />
                                    <input type="hidden" name="parcel-type-id" value="<?php echo $parcelType->getId(); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e('Edit', self::TEXT_DOMAIN); ?></button>
                                </form>
                
                                <form method="post" onsubmit="return confirm('<?php _e('Do you really want to delete this parcel type ?', self::TEXT_DOMAIN); ?>');">
                                    <input type="hidden" name="action" value="delete_parcel_type" />
                                    <input type="hidden" name="parcel-type-id" value="<?php echo $parcelType->getId(); ?>" />
                                    <button type="submit" class="button button-link-delete"><?php _e('Delete', self::TEXT_DOMAIN); ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="text-right">
                    <form method="post">
                        <input type="hidden" name="action" value="add_parcel_type" />
                        <button type="submit" class="button button-primary"><?php _e('Add a new parcel type', self::TEXT_DOMAIN); ?></button>
                    </form>
                </div>
            </div>
        </div> 
        <?php
    }
    
    
    /**
     * 
     * @param type $category
     */
    private function happyfret_display_parceltype_form($parcelType, $title = '') {       
        ?>
        <div class="panel">                       
            <div class="panel-heading"><?php echo $title; ?></div>
            <div class="panel-body">  
                <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                    <input type="hidden" name="action" value="save_parcel_type" />
                    <input type="hidden" name="parcel-type-id" value="<?php echo $parcelType->getId(); ?>" />
                    <table class="table table-bordered">
                        <tr>
                            <th><?php _e('Reference', self::TEXT_DOMAIN);?></th>
                            <td><input type="text" name="parcel-type-ref" maxlength="255" value="<?php echo $parcelType->getRef(); ?>" style="width:100%" /></td>
                        </tr>
                        <tr>
                            <th><?php _e('Name', self::TEXT_DOMAIN);?></th>
                            <td><input type="text" name="parcel-type-name" maxlength="255" value="<?php echo $parcelType->getName(); ?>" style="width:100%" /></td>
                        </tr>
                        <tr>
                            <th><?php _e('Category', self::TEXT_DOMAIN);?></th>
                            <td>                                
                                <?php 
                                    if (null == $parcelType->getCategory()) {
                                        echo HappyfretCategory::getHtmlSelect('parcel-type-category');
                                    } else {
                                        echo HappyfretCategory::getHtmlSelect('parcel-type-category', $parcelType->getCategory()->getId());
                                    }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Width', self::TEXT_DOMAIN);?></th>
                            <td><input type="number" name="parcel-type-width"  value="<?php echo $parcelType->getWidth(); ?>" style="width:100%" /></td>
                        </tr>
                        <tr>
                            <th><?php _e('Height', self::TEXT_DOMAIN);?></th>
                            <td><input type="number" name="parcel-type-height"  value="<?php echo $parcelType->getHeight(); ?>" style="width:100%" /></td>
                        </tr>
                        <tr>
                            <th><?php _e('Depth', self::TEXT_DOMAIN);?></th>
                            <td><input type="number" name="parcel-type-depth"  value="<?php echo $parcelType->getDepth(); ?>" style="width:100%" /></td>
                        </tr>
                        <tr>
                            <th><?php _e('Parcel weight', self::TEXT_DOMAIN);?></th>
                            <td><input type="number" name="parcel-type-parcel-weight" placeholder="1.0" step="0.01"  value="<?php echo $parcelType->getParcelWeight(); ?>" style="width:100%" /></td>
                        </tr>
                        <tr>
                            <th><?php _e('Max. weight', self::TEXT_DOMAIN);?></th>
                            <td><input type="number" name="parcel-type-max-weight" placeholder="1.0" step="0.01" value="<?php echo $parcelType->getMaxWeight(); ?>" style="width:100%" /></td>
                        </tr>
                        <tr>
                            <th><?php _e('Price', self::TEXT_DOMAIN);?></th>
                            <td><input type="number" name="parcel-type-price" placeholder="1.0" step="0.01" value="<?php echo $parcelType->getPrice(); ?>" style="width:100%" /></td>
                        </tr>
                        <tr>
                            <th><?php _e('Visible', self::TEXT_DOMAIN);?></th>
                            <td><input type="checkbox" name="parcel-type-visible" maxlength="255" value="1" <?php if (true == $parcelType->getVisible()) { echo 'checked="checked"'; } ?> /></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="text-right">
                                <button type="submit" class="button button-primary"><?php _e('Save'); ?></button>
                                <a href="<?php echo $_SERVER['REQUEST_URI']; ?>" class="button button-link-delete"><?php _e('Cancel'); ?></a>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
        <?php
    }
    
    
    /**
     * 
     */
    public function happyfret_display_parcel_category_submenu() {
        $this->happyfret_display_admin_h1();
        if (isset($_POST['action'])) {
            if ('add_category' == sanitize_text_field($_POST['action'])) {
                $category = new HappyfretCategory();
                $this->happyfret_display_parcel_category_form($category, __('New parcel category', self::TEXT_DOMAIN));
            }
            if ('edit_category' == sanitize_text_field($_POST['action'])) {
                $category = HappyfretCategory::sqlFindOneById(filter_var($_POST['category-id'], FILTER_SANITIZE_NUMBER_INT));
                $this->happyfret_display_parcel_category_form($category, __('Edit parcel category', self::TEXT_DOMAIN));
            }
            if ('delete_category' == sanitize_text_field($_POST['action'])) {
                $category = HappyfretCategory::sqlFindOneById(filter_var($_POST['category-id'], FILTER_SANITIZE_NUMBER_INT));
                $category->sqlDeleteCascade();
                if (true == HAPPYFRET_DEBUG) { error_log('Category #' . $category->getId() . ' has been deleted.'); }
                $this->happyfret_display_parcel_category_list();
            }            
        } else {            
            $this->happyfret_display_parcel_category_list();
        }        
    }
    
    /**
     * 
     */
    private function happyfret_display_parcel_category_list() {
        ?>
        <div class="panel">                       
            <div class="panel-heading"><?php _e('Parcel categories list', self::TEXT_DOMAIN);?></div>
            <div class="panel-body">  
                <div class="text-right">
                    <form method="post">
                        <input type="hidden" name="action" value="add_category" />
                        <button type="submit" class="button button-primary"><?php _e('Add a new category', self::TEXT_DOMAIN); ?></button>
                    </form>
                </div>
                <table class="table table-bordered" border="1" cellspacing="0" cellpadding="5">
                    <thead>
                        <th><?php _e('Id', self::TEXT_DOMAIN); ?></th>
                        <th><?php _e('Name', self::TEXT_DOMAIN); ?></th>
                        <th><?php _e('Action', self::TEXT_DOMAIN); ?></th>
                    </thead>
                    <tbody>
                        <?php foreach (HappyfretCategory::sqlFindAll() as $category) { ?>
                        <tr>
                            <td><?php echo $category->getId(); ?></td>
                            <td><?php echo $category->getName(); ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="action" value="edit_category" />
                                    <input type="hidden" name="category-id" value="<?php echo $category->getId(); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e('Edit', self::TEXT_DOMAIN); ?></button>
                                </form>
                                <form method="post" onsubmit="return confirm('<?php _e('Do you really want to delete this category?', self::TEXT_DOMAIN); ?>');">
                                    <input type="hidden" name="action" value="delete_category" />
                                    <input type="hidden" name="category-id" value="<?php echo $category->getId(); ?>" />
                                    <button type="submit" class="button button-link-delete"><?php _e('Delete', self::TEXT_DOMAIN); ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="text-right">
                    <form method="post">
                        <input type="hidden" name="action" value="add_category" />
                        <button type="submit" class="button button-primary"><?php _e('Add a new category', self::TEXT_DOMAIN); ?></button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 
     * @param type $category
     */
    private function happyfret_display_parcel_category_form($category, $panelHeading) {
        ?>
        <div class="panel">                       
            <div class="panel-heading"><?php echo $panelHeading; ?></div>
            <div class="panel-body">  
                <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                    <input type="hidden" name="action" value="save_category" />
                    <input type="hidden" name="category-id" value="<?php echo $category->getId(); ?>" />
                    <table class="table table-bordered">
                        <tr>
                            <th><?php _e('Name');?></th>
                            <td><input type="text" name="category-name" maxlength="255" value="<?php echo $category->getName(); ?>" style="width:100%" /></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="text-right">
                                <button type="submit" class="button button-primary"><?php _e('Save'); ?></button>
                                <a href="<?php echo $_SERVER['REQUEST_URI']; ?>" class="button button-link-delete"><?php _e('Cancel'); ?></a>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
        <?php
    }
    
    
    /**
     * 
     */
    private function happyfret_display_parceltype_stock_list($parcelType) {
        ?>
        <div class="panel">                       
            <div class="panel-heading"><?php echo __('Parcel type stock list for parcel type : ', self::TEXT_DOMAIN) . ' ' . $parcelType->getName();?></div>
            <div class="panel-body">  
                <div class="text-right">
                    <form method="post">
                        <input type="hidden" name="action" value="add_parcel_type_stock" />
                        <input type="hidden" name="parcel-type-id" value="<?php echo $parcelType->getId(); ?>" />
                        <button type="submit" class="button button-primary"><?php _e('Add a new stock entry', self::TEXT_DOMAIN); ?></button>
                    <a href="<?php echo $_SERVER['REQUEST_URI']; ?>" class="button button-link-delete"><?php _e('Return to parcel type list', self::TEXT_DOMAIN); ?></a>
                    </form>
                </div>
                <table class="table table-bordered" border="1" cellspacing="0" cellpadding="5">
                    <thead>
                        <th><?php _e('Id', self::TEXT_DOMAIN); ?></th>
                        <th><?php _e('Date', self::TEXT_DOMAIN); ?></th>
                        <th><?php _e('Order', self::TEXT_DOMAIN); ?></th>
                        <th><?php _e('Quantity', self::TEXT_DOMAIN); ?></th>
                        <th><?php _e('Action', self::TEXT_DOMAIN); ?></th>
                    </thead>
                    <tbody>
                        <?php foreach (HappyfretParcelTypeStock::sqlFindByParcelType($parcelType) as $parcelTypeStock) { ?>
                        <tr>
                            <td><?php echo $parcelTypeStock->getId(); ?></td>
                            <td><?php echo date('d/m/Y', $parcelTypeStock->getDate()); ?></td>
                            <td><?php echo $parcelTypeStock->getOrderId(); ?></td>
                            <td><?php echo $parcelTypeStock->getQuantity(); ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="action" value="edit_parcel_type_stock" />
                                    <input type="hidden" name="parcel-type-id" value="<?php echo $parcelType->getId(); ?>" />
                                    <input type="hidden" name="parcel-type-stock-id" value="<?php echo $parcelTypeStock->getId(); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e('Edit'); ?></button>
                                </form>
                                <form method="post" onsubmit="return confirm('<?php _e('Do you really want to delete this stock entry?'); ?>');">
                                    <input type="hidden" name="action" value="delete_parcel_type_sotck" />
                                    <input type="hidden" name="parcel-type-id" value="<?php echo $parcelType->getId(); ?>" />
                                    <button type="submit" class="button button-link-delete"><?php _e('Delete'); ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="text-right">
                    <form method="post">
                        <input type="hidden" name="action" value="add_parcel_type_stock" />
                        <input type="hidden" name="parcel-type-id" value="<?php echo $parcelType->getId(); ?>" />
                        <button type="submit" class="button button-primary"><?php _e('Add a new stock entry', self::TEXT_DOMAIN); ?></button>
                        <a href="<?php echo $_SERVER['REQUEST_URI']; ?>" class="button button-link-delete"><?php _e('Return to parcel type list', self::TEXT_DOMAIN); ?></a>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function happyfret_display_parceltype_stock_form($parcelTypeStock, $title) {       
        ?>
        <div class="panel">                       
            <div class="panel-heading"><?php echo $title; ?></div>
            <div class="panel-body">  
                <form method="post">
                    <input type="hidden" name="action" value="save_parcel_type_stock" />
                    <input type="hidden" name="parcel-type-stock-id" value="<?php echo $parcelTypeStock->getId(); ?>" />
                    <table class="table table-bordered">
                        <tr>
                            <th><?php _e('Parcel type', self::TEXT_DOMAIN); ?></th>
                            <td>
                                <?php
                                if (null == $parcelTypeStock->getParcelType()) {
                                    echo HappyfretParcelType::getHtmlSelect("parcel-type-id");
                                } else {
                                    echo HappyfretParcelType::getHtmlSelect("parcel-type-id", $parcelTypeStock->getParcelType()->getId());
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Date', self::TEXT_DOMAIN);?></th>
                            <td><input type="text" name="parcel-type-stock-date" maxlength="255" value="<?php if (null == $parcelTypeStock->getDate()) { echo date('d/m/Y'); } else { echo date('d/m/Y', $parcelTypeStock->getDate()); } ?>" placeholder="dd/mm/yyyy" style="width:100%" /></td>
                        </tr>
                        <tr>
                            <th><?php _e('Quantity', self::TEXT_DOMAIN);?></th>
                            <td><input type="number" name="parcel-type-stock-quantity" <?php if (null != $parcelTypeStock->getQuantity()) { echo 'value="'.$parcelTypeStock->getQuantity().'"'; } ?> style="width:100%" /></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="text-right">
                                <button type="submit" class="button button-primary"><?php _e('Save'); ?></button>
                                <a href="<?php echo $_SERVER['REQUEST_URI']; ?>" class="button button-link-delete"><?php _e('Cancel'); ?></a>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
        <?php
    }
    
    
    public function happyfret_display_packing_orders() {
        $this->happyfret_display_admin_h1();
        ?>
        <div class="panel">
            <div class="panel-heading"><?php _e('Packing orders', self::TEXT_DOMAIN); ?></div>
            <div class="panel-body">
                <table class="table table-bordered" border="1" cellspacing="0" cellpadding="5" width="100%">
                    <thead>
                        <tr>
                            <?php /*<th><?php _e('Packing Order Id', self::TEXT_DOMAIN); ?></th>*/ ?>
                            <?php /*<th><?php _e('Client', self::TEXT_DOMAIN); ?></th>*/ ?>
                            <th><?php _e('Order', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Billing Address', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Shipping Address', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Parcel Type', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Products to pack', self::TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach(HappyfretPackingOrder::sqlFindAll() as $packingOrder) {
                            $order = wc_get_order($packingOrder->getOrderId());
                            if (null != $order) {
                        ?>
                                <tr>
                                    <?php /*<td><?php echo $packingOrder->getId(); ?></td>*/ ?>
                                    <?php /*<td><?php echo $packingOrder->getUserId(); ?></td>*/ ?>
                                    <td valign="top"><?php echo __('Order #', self::TEXT_DOMAIN) . ' ' . $packingOrder->getOrderId() . ' : ' . $order->get_total() .' <small>'. $order->get_currency() . '</small>'; ?></td>
                                    <td valign="top"><?php echo self::happyfret_get_billing_address($order); ?></td>
                                    <td valign="top"><?php echo self::happyfret_get_shipping_address($order); ?></td>                                
                                    <td valign="top"><?php if (null == $packingOrder->getParcelType()) { _e('Deleted parcel type', self::TEXT_DOMAIN); } else { echo $packingOrder->getParcelType()->getName(); } ?></td>
                                    <td valign="top">
                                        <ul>
                                        <?php foreach($packingOrder->getProducts() as $product) { ?>
                                        <li><?php echo $product->getQuantity();?> x <?php echo wc_get_product($product->getProductId())->get_name();?></li>
                                        <?php } ?>
                                        </ul>
                                    </td>
                                </tr>
                        <?php
                            } 
                        } 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    
    public function happyfret_display_shipping_labels() {
        $this->happyfret_display_admin_h1();
        $shippingLabels = HappyfretPackageTracking::sqlFindAll();
        ?>
        <div class="panel">
            <div class="panel-heading"><?php _e('Packing orders', self::TEXT_DOMAIN); ?></div>
            <div class="panel-body">
                <table class="table table-bordered" border="1" cellspacing="0" cellpadding="5" width="100%">
                    <thead>
                        <tr>
                            <th><?php _e('Order Id', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Tracking number', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Packages tracking numbers', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('PDF', self::TEXT_DOMAIN); ?></th>
                            <?php /*<th><?php _e('Expedition Id', self::TEXT_DOMAIN); ?></th>*/?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shippingLabels as $label) { ?>
                        <tr>
                            <td><?php echo $label->getOrderId(); ?></td>
                            <td><?php echo $label->getTrackingNumber(); ?></td>
                            <td>
                                <ul>
                                    <?php  
                                    if (is_array(json_decode($label->getPackageTrackingNumber()))) {
                                    foreach(json_decode($label->getPackageTrackingNumber()) as $packageTrackingNumber) { ?>
                                        <li><?php echo $packageTrackingNumber; ?></li>
                                    <?php } }?>
                                </ul>
                            </td>
                            <td><a href="<?php echo $label->getPdf(); ?>" target="_blank"><?php echo $label->getPdf(); ?></a></td>
                            <?php /*<td><?php echo $label->getExpeditionId(); ?></td>*/?>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    
    /**
     * 
     */
    public function happyfret_load_text_domain() {
        load_plugin_textdomain( self::TEXT_DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages'  );        
    }
    
    /**
     * Ajout du style css pour l'interface d'administration
     */
    public function happyfret_custom_css() {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        ?>
        <style>
            .panel {
                border: solid 1px #355a70;
                box-shadow: 1px 1px 1px #355a70;
                margin: 10px;
            }
            .panel .panel-heading {                    
                color: #fff;
                background-color: #355a70;
                border-bottom: solid 1px #355a70;
                padding: 10px;
                margin: 0;
                font-size: 1.2em;
                line-height: 1.2em;
            }
            .panel .panel-body{
                padding: 10px;                    
                margin: 0;
                background-color: #fff;
            }                  
            .panel .panel-body table {
                margin: 10px 0;
                width: 100%;
            }
            .panel .panel-body table th {
                background-color: #eee;
            }
            .panel .panel-body table td form {
                display: inline-block;
            }
            .panel .panel-footer {
                padding: 10px;                    
                margin: 0;
                background-color: #fff;
                border-top: solid 1px #355a70;
            }
            .text-right {
                text-align: right;
            }
            .btn {
                border-radius: 3px;
                border: none;
                box-shadow: 1px 1px 1px #999;
                padding: 5px 10px;
            }
            .btn-primary { 
                background-color: #007bff; 
                color: #fff
            }
            .btn-secondary { 
                background-color: #6c757d; 
                color: #fff
            }
            .btn-success { 
                background-color: #28a745; 
                color: #fff
            }
            .btn-danger { 
                background-color: #dc3545;
                color: #fff
            }            
            .col-1 { width: 25%; }
            .col-2 { width: 50%; }
            .col-3 { width: 75%; }
            .col-4 { width: 100%; }
            .th-left th { 
                text-align: left;
                padding-left: 10px;
            }
        </style>
        <?php
    }
    
    private function happyfret_display_admin_h1() {
        ?><h1><img src="<?php echo plugin_dir_url(__FILE__) . 'assets/logo.png' ?>" /> <?php echo _e('Happy Fret'); ?></h1><?php
    }
    
    
    public function happyfret_process_order_status_completed( $order_id ) {        
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }  
        $order = wc_get_order( $order_id );
        
        $senderAddress = $this->happyfret_compute_sender_address();
                
        $shippingAddress = [
            'name' => $order->get_shipping_company(),
            'address1' => $order->get_shipping_address_1(),
            'address2' => $order->get_shipping_address_2(),
            'address3' => '',
            'country' => $order->get_shipping_country(),
            'zip' => $order->get_shipping_postcode(),
            'city' => $order->get_shipping_city(),
            'state' => $order->get_shipping_state(),
            'contact' => trim($order->get_shipping_first_name() . ' ' .$order->get_shipping_last_name()),
            'phone' => $order->get_billing_phone(),
            'mobile' => '',
            'email' => $order->get_billing_email()
        ];
                
        $orderData = [
            'orderId' => $order_id,
            'shippingCompanyId' => self::happyfret_get_shipping_company_id($order_id),
            'shippingOfferCode' => self::happyfret_get_shipping_offer_code($order_id)
        ];
        
        $parcels = [];
        foreach (self::happyfret_get_packing_order_ids($order_id) as $packingOrderId) {
            $packingOrder = HappyfretPackingOrder::sqlFindOneById($packingOrderId);
            $parcels[] = $packingOrder->getArrayValue();
        }                     
        $trackingData = HappyfretApi::orderConfirmation($senderAddress, $shippingAddress, $parcels, $orderData);        
        $packageTracking = new HappyfretPackageTracking();
        $packageTracking->setOrderId($order_id);
        $packageTracking->setTrackingNumber($trackingData['TrackingNumber']);
        $packageTracking->setPackageTrackingNumber(json_encode($trackingData['PackageTrackingNumber']));
        $packageTracking->setPdf($trackingData['pdf']);
        $packageTracking->setExpeditionId($trackingData['id_expedition']);
        $packageTracking->sqlInsert();
    }
    
    public function happyfret_process_order_status_changed( $order_id, $old_status, $new_status ) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }        
        // Nothing to do
    }
    
    public function happyfret_process_checkout_order_processed ($order_id) {        
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }        
        foreach (self::happyfret_get_packing_order_ids($order_id) as $packingOrderId) {
            $currentPackingOrder = HappyfretPackingOrder::sqlFindOneById($packingOrderId);
            if (null != $currentPackingOrder) {
                // Stores order id into packing order
                $currentPackingOrder->setOrderId($order_id);
                $currentPackingOrder->sqlPersist();                   
                // Create parcel type stock record            
                $parcelTypeStock = new HappyfretParcelTypeStock();
                $parcelTypeStock->setOrderId($order_id);
                $parcelTypeStock->setParcelType($currentPackingOrder->getParcelType());
                $parcelTypeStock->setQuantity(-1);
                $parcelTypeStock->setDate(date('Y-m-d'));
                $parcelTypeStock->sqlPersist();
            }
        }  
    }
    
    
    
    private static function happyfret_get_packing_order_ids($order_id) {
        $packingOrderIds = [];
        $order = wc_get_order( $order_id );
        foreach( $order->get_items('shipping') as $item_id => $item_shipping){            
            if ($item_shipping->get_meta('packingOrderIds')) {
                foreach( explode(',', $item_shipping->get_meta('packingOrderIds')) as $packingOrderId ) {
                    $packingOrderIds[] = $packingOrderId;
                }
            }
        }
        return $packingOrderIds;
    }
    
    private static function happyfret_get_shipping_company_id($order_id) {        
        $order = wc_get_order( $order_id );
        foreach( $order->get_items('shipping') as $item_id => $item_shipping){            
            if ($item_shipping->get_meta('shippingCompanyId')) {
                return $item_shipping->get_meta('shippingCompanyId');
            }
        }
        return null;
    }
    
    private static function happyfret_get_shipping_offer_code($order_id) {        
        $order = wc_get_order( $order_id );
        foreach( $order->get_items('shipping') as $item_id => $item_shipping){            
            if ($item_shipping->get_meta('shippingOfferCode')) {
                return $item_shipping->get_meta('shippingOfferCode');
            }
        }
        return null;
    }
    

    
    function happyfret_check_api_configuration($displaySuccess =  false )  {
        $clientType = self::CLIENT_TYPE_STANDALONE;
        if (true == $this->happyfret_api_check_settings()) {
            if (true == HappyfretApi::accountValidation()) {
                if ($displaySuccess) {
                    ?><div class="notice notice-success is-dismissible"><p><?php _e( 'Happyfret API key successfully checked !', self::TEXT_DOMAIN ); ?></p></div><?php
                }
                if (HappyfretApi::getProfile()['client']['subscribed']) { $clientType = self::CLIENT_TYPE_LOGISTICS; }                
            } else {
                ?><div class="notice notice-error is-dismissible"><p><?php _e( 'Happyfret API key error !', self::TEXT_DOMAIN ); ?></p></div><?php
            }        
        } else {
            if (!(isset($_POST['action']) && ('save_settings' == sanitize_text_field($_POST['action'])))) {
                ?><div class="notice notice-error is-dismissible"><p><?php _e( 'Happyfret API configuration error !', self::TEXT_DOMAIN ); ?></p></div><?php
            }
        }
        update_option('happyfret_client_type', $clientType);
    }
    
    
    private function happyfret_api_check_settings() {
        $set = true;
        foreach (self::$options as $optionName => $optionValues) {
            if ('' == get_option($optionName)) {
                if (null == $optionValues['default']) {
                    $set = false;
                } else {
                    update_option($optionName, $optionValues['default']);
                }
            }
        }
        foreach (self::$senderAddress as $optionName => $optionValues) {
            if ('' == get_option($optionName)) {
                if (null == $optionValues['default']) {
                    //$set = false;
                } else {
                    update_option($optionName, $optionValues['default']);
                }
            }
        }
        return $set;
    }
    
   
    
    
    
    
    public function happyfret_display_products_synchronization() {        
        $happyfretProductsSynchronizer = HappyfretProductsSynchronizer::getInstance();        
        if (isset($_POST['action'])) {
            switch (sanitize_text_field($_POST['action'])) {
            case 'link-to-happyfret' : 
                foreach($_POST['product-ids'] as $productId) {
                    switch (sanitize_text_field($_POST['ref-happyfret-'.$productId])) {
                        case 'no-action':
                            // Do nothing
                            break;
                        case 'create-new':                                                                                   
                            $wcProduct = wc_get_product($productId);
                            $happyfretProduct = HappyfretApi::createProduct(
                                $wcProduct->get_name(),
                                $wcProduct->get_sku(),
                                null == $wcProduct->get_image_id() ? null : wp_get_attachment_url( $wcProduct->get_image_id()),
                                $wcProduct->get_width(),
                                $wcProduct->get_height(),
                                $wcProduct->get_length(),
                                $wcProduct->get_weight()                                    
                            );
                            if (!is_array($happyfretProduct)) {
                                $happyfretProduct->setWoocommerceId($productId);
                                $happyfretProduct->sqlUpdate();    
                            }
                            break;
                        default:
                            $happyfretProduct = $happyfretProductsSynchronizer->findHappyfretProductByHappyfretRef(sanitize_text_field($_POST['ref-happyfret-'.$productId]));
                            $happyfretProduct->setWoocommerceId($productId);
                            $happyfretProduct->sqlUpdate();                            
                            break;                        
                    }

                }
                break;
            case 'link-to-shop' : 
                foreach($_POST['happyfret-refs'] as $happyfretRef) {
                    switch (sanitize_text_field($_POST['id-woocommerce-'.$happyfretRef])) {
                        case 'no-action':
                            // Do nothing
                            break;
                        case 'create-new': 
                            $happyfretProduct = $happyfretProductsSynchronizer->findHappyfretProductByHappyfretRef($happyfretRef);                            
                            $post_id = wp_insert_post( array(
                                'post_title' => $happyfretProduct->getName(),
                                'post_content' => '',
                                'post_status' => 'publish',
                                'post_type' => "product",
                            ) );
                            wp_set_object_terms( $post_id, 'simple', 'product_type' );
                            update_post_meta( $post_id, '_visibility', 'visible' );
                            update_post_meta( $post_id, '_stock_status', 'instock');
                            update_post_meta( $post_id, 'total_sales', '0' );
                            update_post_meta( $post_id, '_downloadable', 'no' );
                            update_post_meta( $post_id, '_virtual', 'no' );
                            update_post_meta( $post_id, '_regular_price', '' );
                            update_post_meta( $post_id, '_sale_price', '' );
                            update_post_meta( $post_id, '_purchase_note', '' );
                            update_post_meta( $post_id, '_featured', 'no' );
                            update_post_meta( $post_id, '_weight', $happyfretProduct->getWeight() );
                            update_post_meta( $post_id, '_length', $happyfretProduct->getDepth() );
                            update_post_meta( $post_id, '_width', $happyfretProduct->getWidth() );
                            update_post_meta( $post_id, '_height', $happyfretProduct->getHeight() );
                            update_post_meta( $post_id, '_sku', $happyfretProduct->getBarCode() );
                            update_post_meta( $post_id, '_product_attributes', array() );
                            update_post_meta( $post_id, '_sale_price_dates_from', '' );
                            update_post_meta( $post_id, '_sale_price_dates_to', '' );
                            update_post_meta( $post_id, '_price', '' );
                            update_post_meta( $post_id, '_sold_individually', '' );
                            update_post_meta( $post_id, '_manage_stock', 'yes' );
                            update_post_meta( $post_id, '_backorders', 'no' );
                            update_post_meta( $post_id, '_stock', $happyfretProduct->getStock() );                                                                                                              
                            $happyfretProduct->setWoocommerceId($post_id);
                            $happyfretProduct->sqlUpdate();
                            break;
                        default:
                            $happyfretProduct = $happyfretProductsSynchronizer->findHappyfretProductByHappyfretRef(sanitize_text_field($happyfretRef));
                            $happyfretProduct->setWoocommerceId(filter_var($_POST['id-woocommerce-'.$happyfretRef], FILTER_SANITIZE_NUMBER_INT));
                            $happyfretProduct->sqlUpdate();                         
                            break;                        
                    }

                }
                break;
            case 'update-shop-stock' : 
                $wcProduct = wc_get_product($_POST['shop-id']);
                $wcProduct->set_stock_quantity(filter_var($_POST['stock'], FILTER_SANITIZE_NUMBER_INT));
                $wcProduct->save();
                break;            
            }
        }               
        // Refresh all HappyfretProducts from API
        $happyfretProductsSynchronizer->apiLoadHappyfretProducts();   
        
        // Display page title
        $this->happyfret_display_admin_h1();
        
        ?>
        
        
        <?php $unlinkedWoocommerceProducts = wc_get_products(['exclude' => $happyfretProductsSynchronizer->getLinkedWoocommerceIds()]); ?>
        <?php if(0 != count($unlinkedWoocommerceProducts)) { ?>
        <div class="panel">                       
            <div class="panel-heading"><?php _e('Products not linked to Happyfret', self::TEXT_DOMAIN);?></div>
            <div class="panel-body">                   
                <form method="post">
                    <table class="table table-bordered" border="1" cellspacing="0" cellpadding="5">
                        <input type="hidden" name="action" value="link-to-happyfret" />
                        <?php 
                        foreach ($unlinkedWoocommerceProducts as $wcProduct) {
                        ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="product-ids[]" value="<?php echo $wcProduct->get_id(); ?>" />
                                    <?php echo $wcProduct->get_id(); ?>
                                </td>
                                <td><?php echo $wcProduct->get_name(); ?></td>
                                <td>
                                    <?php                                
                                    echo self::happyfret_products_array_to_select(HappyfretProduct::sqlFindAllUnlinked()/*$happyfretProductsSynchronizer->getHappyfretProducts()*/, 'ref-happyfret-' . $wcProduct->get_id(), $happyfretProductsSynchronizer->findHappyfretProductByWoocommerceId($wcProduct->get_id()), true);
                                    ?>
                                </td>
                            </tr>
                        <?php  } ?>
                    </table>
                    <div class="text-right">
                        <button type="submit" class="button button-primary"><?php _e('Save settings', self::TEXT_DOMAIN); ?></button>
                    </div>
                </form>               
            </div>
        </div>
        <?php } ?>
        
        
        <?php $unlinkedHappyfretProducts = HappyfretProduct::sqlFindAllUnlinked(); ?>
        <?php if (0 != count($unlinkedHappyfretProducts)) { ?>
        <div class="panel">
            <div class="panel-heading"><?php _e('Happyfret products not linked to the shop', self::TEXT_DOMAIN); ?></div>
            <div class="panel-body">
                <form method="post">
                    <input type="hidden" name="action" value="link-to-shop" />
                    <table class="table table-bordered" border="1" cellspacing="0" cellpadding="5">
                        <thead>
                            
                        </thead>
                        <tbody>
                            <?php foreach($unlinkedHappyfretProducts as $happyfretProduct) { ?>
                            <tr>
                                <input type="hidden" name="happyfret-refs[]" value="<?php echo $happyfretProduct->getHappyfretRef(); ?>" />
                                <td><?php echo $happyfretProduct->getHappyfretRef(); ?></td>
                                <td><?php echo $happyfretProduct->getName(); ?></td>
                                <td>
                                    <select name="id-woocommerce-<?php echo $happyfretProduct->getHappyfretRef(); ?>">
                                        <option value="no-action"><?php _e('Do nothing', self::TEXT_DOMAIN); ?></option>
                                        <option value="create-new"><?php _e('Create new product in shop', self::TEXT_DOMAIN); ?></option>
                                        <?php foreach($unlinkedWoocommerceProducts as $wcProduct) { ?>
                                        <option value="<?php echo $wcProduct->get_id(); ?>"><?php echo $wcProduct->get_id(); ?> - <?php echo $wcProduct->get_name(); ?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <div class="text-right">
                        <button type="submit" class="button button-primary"><?php _e('Save settings', self::TEXT_DOMAIN); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php } ?>
        
        
        <?php if( 0 != count($happyfretProductsSynchronizer->getLinkedWoocommerceIds())) { ?>
        <div class="panel">                       
            <div class="panel-heading"><?php _e('Products linked to Happyfret', self::TEXT_DOMAIN);?></div>
            <div class="panel-body">                 
                <form method="post">
                    <table class="table table-bordered" border="1" cellspacing="0" cellpadding="5">
                        <thead>
                        <tr>
                            <th><?php _e('Product Id', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Product Name', self::TEXT_DOMAIN); ?></th>
                            <th colspan="2"><?php _e('Stock', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Happyfret ref', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Happyfret name', self::TEXT_DOMAIN); ?></th>
                            <th><?php _e('Happyfret stock', self::TEXT_DOMAIN); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                            <?php                         
                            foreach (wc_get_products(['include' => $happyfretProductsSynchronizer->getLinkedWoocommerceIds()]) as $wcProduct) {
                                $happyfretProduct = $happyfretProductsSynchronizer->findHappyfretProductByWoocommerceId($wcProduct->get_id());
                            ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="product-ids[]" value="<?php echo $wcProduct->get_id(); ?>" />
                                        <?php echo $wcProduct->get_id(); ?>
                                    </td>
                                    <td><?php echo $wcProduct->get_name(); ?></td>
                                    <td><?php echo $wcProduct->get_stock_quantity(); ?></td>
                                    <td>
                                        <form method="post">
                                            <input type="hidden" name="action" value="update-shop-stock" />
                                            <input type="hidden" name="shop-id" value="<?php echo $wcProduct->get_id(); ?>" />
                                            <input type="hidden" name="stock" value="<?php echo $happyfretProduct->getStock(); ?>" />
                                            <button type="submit" class="button button-primary"><?php _e('Update from Happyfret', self::TEXT_DOMAIN); ?></button>
                                        </form>
                                    </td>
                                    <td><?php echo $happyfretProduct->getHappyfretRef(); ?></td>
                                    <td><?php echo $happyfretProduct->getName(); ?></td>
                                    <td><?php echo $happyfretProduct->getStock();?></td>                                                             
                                </tr>
                            <?php  } ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>        
        <?php } ?>
        
        <?php        
    }
    
    
    private static function happyfret_products_array_to_select($products, $selectName, $selectedValue, $createNew = false) {
        $select = '<select name="'.$selectName.'">';
        $select .= '<option value="no-action">' . __('Do nothing', self::TEXT_DOMAIN) . '</option>';
        if (true == $createNew) {
            $select .= '<option value="create-new">' . __('Create new product in Happyfret', self::TEXT_DOMAIN) . '</option>';
        }
        foreach($products as $product) {
            $select .= '<option value="' . $product->getHappyfretRef() . '"';
            if ($selectedValue == $product->getHappyfretRef()) {
                $select .= ' selected="selected"';
            }
            $select .= '>' . $product->getHappyfretRef() . ' - ' . $product->getName() . '</option>';
        }
        $select .= '</select>';
        return $select;
    }
    
    
    public static function happyfret_compute_sender_address() {
        $address = [];
        foreach (self::$senderAddress as $optionName => $optionValue) {
            $address[$optionValue['name']] = get_option($optionName);
        }
        return $address;
    }
    
    
    private static function happyfret_get_billing_address($order) {
        $fullAddress = [];
        if ('' != $order ->get_billing_first_name()) { $fullAddress[] = $order->get_billing_first_name(); }
        if ('' != $order ->get_billing_last_name()) { $fullAddress[] = $order->get_billing_last_name(); }
        if ('' != $order ->get_billing_address_1()) { $fullAddress[] = $order->get_billing_address_1(); }
        if ('' != $order ->get_billing_address_2()) { $fullAddress[] = $order->get_billing_address_2(); }
        if ('' != $order ->get_billing_postcode()) { $fullAddress[] = $order->get_billing_postcode(); }
        if ('' != $order ->get_billing_city()) { $fullAddress[] = $order->get_billing_city(); }
        if ('' != $order ->get_billing_country()) { $fullAddress[] = $order->get_billing_country(); }
        return implode('<br>', $fullAddress);
    }
    
    private static function happyfret_get_shipping_address($order) {
        $fullAddress = [];
        if ('' != $order ->get_shipping_first_name()) { $fullAddress[] = $order->get_shipping_first_name(); }
        if ('' != $order ->get_shipping_last_name()) { $fullAddress[] = $order->get_shipping_last_name(); }
        if ('' != $order ->get_shipping_address_1()) { $fullAddress[] = $order->get_shipping_address_1(); }
        if ('' != $order ->get_shipping_address_2()) { $fullAddress[] = $order->get_shipping_address_2(); }
        if ('' != $order ->get_shipping_postcode()) { $fullAddress[] = $order->get_shipping_postcode(); }
        if ('' != $order ->get_shipping_city()) { $fullAddress[] = $order->get_shipping_city(); }
        if ('' != $order ->get_shipping_country()) { $fullAddress[] = $order->get_shipping_country(); }
        return implode('<br>', $fullAddress);
    }
}
