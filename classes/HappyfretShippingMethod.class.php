<?php
 
/**
 * Plugin Name: TutsPlus Shipping
 * Plugin URI: http://code.tutsplus.com/tutorials/create-a-custom-shipping-method-for-woocommerce--cms-26098
 * Description: Custom Shipping Method for WooCommerce
 * Version: 1.0.0
 * Author: Igor Benić
 * Author URI: http://www.ibenic.com
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /lang
 * Text Domain: tutsplus
 * 
 * @package    HappyFret
 * @subpackage HappyFret/classes
 * @author     Advise Communication <rmariage@advise.fr>
 */
 
if ( ! defined( 'WPINC' ) ) {
 
    die;
 
}
 
/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
    function happyfret_shipping_method() {
        if ( ! class_exists( 'HappyfretShippingMethod' ) ) {
            class Happyfret_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id                 = 'happyfret_shipping_method_'; 
                    $this->method_title       = __( 'Happyfret Shipping', HappyfretPlugin::TEXT_DOMAIN );  
                    $this->method_description = __( 'Custom Shipping Method for Happyfret', HappyfretPlugin::TEXT_DOMAIN ); 
                    
                    // Availability & Countries
                    $this->availability = 'including';
                    $this->countries = array(
                        'FR',      
                        'US'
                    );                   
 
                    $this->init();
 
                    $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Happyfret Shipping', HappyfretPlugin::TEXT_DOMAIN );
                }
 
                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); 
                    $this->init_settings(); 
 
                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
 
                /**
                 * Define settings field for this shipping
                 * @return void 
                 */
                function init_form_fields() { 
                    $this->form_fields = array(
                        'enabled' => array(
                            'title' => __( 'Enable', HappyfretPlugin::TEXT_DOMAIN ),
                            'type' => 'checkbox',
                            'description' => __( 'Enable this shipping', HappyfretPlugin::TEXT_DOMAIN ),
                            'default' => 'yes'
                        ),
                        'title' => array(
                            'title' => __( 'Title', HappyfretPlugin::TEXT_DOMAIN ),
                            'type' => 'text',
                            'description' => __( 'Title to be display on site', HappyfretPlugin::TEXT_DOMAIN ),
                            'default' => __( 'Happyfret Shipping', HappyfretPlugin::TEXT_DOMAIN )
                        ),
                    );
                }
 
                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping($package = Array() ) {
                    $parcelsPrices = [];
                    $packingOrderIds = [];
                    if (HappyfretPlugin::CLIENT_TYPE_STANDALONE == get_option('happyfret_client_type')) {
                        $parcels = HappyfretParcelType::sqlFindAllAvailable();
                        if (0 == count($parcels)) {
                            return;
                        }                            
                        $products = self::getProductsFromPackage($package);
                        $myParcels = self::happyfret_compute_parcels_array($parcels);                        
                        $selectedParcels = HappyfretApi::resolveParcels($products, $myParcels);
                        if (0 < count($selectedParcels)) {
                            // Delete old packing orders for this cart                   
                            HappyfretPackingOrder::sqlDeleteTempPackingOrderForUser(get_current_user_id());
                            // Create packing orders
                            $packingOrderIds = HappyfretPackingOrder::sqlCreateFromSelectedParcels($selectedParcels);                                                           
                            // Gets shipping price from API                        
                            $parcelsPrices = HappyfretApi::getShippingRates(self::happyfret_compute_shipping_address_from_happyfret(HappyfretPlugin::happyfret_compute_sender_address()), self::happyfret_compute_shipping_address($package['destination']), $selectedParcels);
                        }
                    }
                    if (HappyfretPlugin::CLIENT_TYPE_LOGISTICS == get_option('happyfret_client_type')) {
                        $products = []; 
                        $hasUnlikedProducts = false;
                        $hasLinkedProducts = false;
                        foreach($package['contents'] as $item_id => $values) {
                            $happyFretProduct = HappyfretProduct::sqlFindByShopProductId($values['product_id']);
                            if (null == $happyFretProduct) {
                                $hasUnlikedProducts = true;
                            } else {
                                $hasLinkedProducts = true;
                                $products[$happyFretProduct->getHappyfretRef()] = $values['quantity'] ; 
                            }                                                       
                        }
                        if (($hasLinkedProducts && $hasUnlikedProducts) /*|| (!$hasLinkedProducts && !$hasUnlikedProducts)*/) {
                            echo '<div class="woocommerce-error" role="alert">' .__('Products you have selected come from different warehouses. They can\'t be shipped together', HappyfretPlugin::TEXT_DOMAIN) . '</div>';
                        }
                        if($hasLinkedProducts && (!$hasUnlikedProducts)) {                            
                            $parcelsPrices = HappyfretApi::getLogisticsShippingRates(self::happyfret_compute_shipping_address($package['destination']), $products);
                        }
                        if((!$hasLinkedProducts) && $hasUnlikedProducts) {                           
                            $parcels = HappyfretParcelType::sqlFindAllAvailable();
                            if (0 == count($parcels)) {
                                return;
                            }                            
                            $products = self::getProductsFromPackage($package);
                            $myParcels = self::happyfret_compute_parcels_array($parcels);                            
                            $selectedParcels = HappyfretApi::resolveParcels($products, $myParcels);
                            //echo '--<pre>'; print_r($selectedParcels); echo '</pre>--';
                            if (0 < count($selectedParcels)) {
                                // Delete old packing orders for this cart                   
                                HappyfretPackingOrder::sqlDeleteTempPackingOrderForUser(get_current_user_id());
                                // Create packing orders
                                $packingOrderIds = HappyfretPackingOrder::sqlCreateFromSelectedParcels($selectedParcels);                                                           
                                // Gets shipping price from API                     
                                $parcelsPrices = HappyfretApi::getShippingRates(self::happyfret_compute_shipping_address_from_happyfret(HappyfretPlugin::happyfret_compute_sender_address()), self::happyfret_compute_shipping_address($package['destination']), $selectedParcels);
                            }
                        }
                    }
                        

                    // Create and add shipping rates
                    if (is_array($parcelsPrices)) {
                        foreach($parcelsPrices as $company) {
                            //echo '<pre>'; print_r($company); echo '</pre>';
                            if (is_array($company) && array_key_exists('offers', $company)) {
                                foreach ($company['offers'] as $offer) {
                                    $shippingOffer = HappyfretShippingOffer::sqlFindOrCreate($company['id'], $offer['code']);                                
                                    $rate = array(
                                        'id' => $this->id.$shippingOffer->getId(),
                                        'label' => $this->title . ' : ' . $company['transporteur'] . ' ' . $offer['service'],
                                        'cost' => $offer['prix'],   
                                        'meta_data' => [
                                            'packingOrderIds' => implode(',', $packingOrderIds),
                                            'shippingCompanyId' => $company['id'],
                                            'shippingOfferCode' => $offer['code']
                                        ]
                                    );
                                    $this->add_rate( $rate );                                                    
                                }
                            }
                        } 
                    }
                    
                }
                
                /**
                 * 
                 * @param type $parcels
                 * @return type
                 */
                private static function happyfret_compute_parcels_array($parcels) {
                    $myParcels  = [];
                    foreach($parcels as $parcel) {
                        $stock = 0;
                        foreach(HappyfretParcelTypeStock::sqlFindByParcelType($parcel) as $parcelStock) {
                            $stock += $parcelStock->getQuantity();
                        }
                        $myParcels[] = [
                            'ref'           => $parcel->getRef(),
                            'category'      => $parcel->getCategory()->getName(),
                            'name'          => $parcel->getName(),
                            'width'         => round($parcel->getWidth()*10, 0, PHP_ROUND_HALF_UP),
                            'height'        => round($parcel->getHeight()*10, 0, PHP_ROUND_HALF_UP),
                            'depth'         => round($parcel->getDepth()*10, 0, PHP_ROUND_HALF_UP),
                            'parcelWeight'  => round($parcel->getParcelWeight()*1000, 0, PHP_ROUND_HALF_UP),
                            'maxWeight'     => round($parcel->getMaxWeight()*1000, 0, PHP_ROUND_HALF_UP),
                            'stock'         => $stock,      
                            'price'         => [
                                'amount' => $parcel->getPrice(),
                                'currency' => 'EUR'
                            ],
                            'visible'       => $parcel->getVisible() 
                        ];
                    }
                    return $myParcels;
                }
                
                private static function getProductsFromPackage($package) {
                    $products = [];        
                    foreach($package['contents'] as $item_id => $values) {
                        $_product = $values['data']; 
                        $products[] = [                
                            'ref'         => $values['product_id'],
                            'unitWidth'   => $_product->get_width(),
                            'unitHeight'  => $_product->get_height(),
                            'unitDepth'   => $_product->get_length(),
                            'unitWeight'  => round(null == $_product->get_weight() ? 0 : $_product->get_weight()*1000, 0, PHP_ROUND_HALF_UP),
                            'unitsCount'  => $values['quantity']   
                        ];
                    }  
                    return $products;
                }
                
                private static function happyfret_compute_shipping_address($destinationArray) {
                    return [
                        'address1' => $destinationArray['address_1'],
                        'address2' => $destinationArray['address_2'],
                        'zip' => $destinationArray['postcode'],
                        'city' => $destinationArray['city'],
                        'state' => $destinationArray['state'],
                        'country' => $destinationArray['country'],
                    ];
                }
                
                private static function happyfret_compute_shipping_address_from_happyfret($destinationArray) {
                    return [
                        'address1' => $destinationArray['Address 1'],
                        'address2' => $destinationArray['Address 2'],
                        'zip' => $destinationArray['Zip code'],
                        'city' => $destinationArray['City'],
                        'state' => $destinationArray['State'],
                        'country' => $destinationArray['Country']
                    ];
                }
                
               
                                
            }
        }
    } 
    add_action( 'woocommerce_shipping_init', 'happyfret_shipping_method' );
 
    function add_happyfret_shipping_method( $methods ) {
        $methods[] = 'Happyfret_Shipping_Method';
        return $methods;
    }
 
    add_filter( 'woocommerce_shipping_methods', 'add_happyfret_shipping_method' );
       
}