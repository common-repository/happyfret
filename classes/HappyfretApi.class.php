<?php
/**
 *
 * @package    HappyFret
 * @subpackage HappyFret/classes
 * @author     Advise Communication <rmariage@advise.fr>
 */

class HappyfretApi {
    
    /**
     * Gets HappyFret user profile
     * @return array
     */
    public static function getProfile() {
        $url = get_option('happyfret_api_base_url') . get_option('happyfret_api_get_profile');        
        $args = [
            'headers' => [
                'x-auth-apikey' => get_option('happyfret_api_key'),
                'x-auth-email' => get_option('happyfret_api_email')
            ]
        ];
        $response = wp_remote_get($url, $args);
        if (200 != wp_remote_retrieve_response_code($response)) {
            ?><div class="notice notice-error is-dismissible"><p><?php _e( 'Happyfretproducts retrieval error !', HappyfretPlugin::TEXT_DOMAIN ); ?></p></div><?php
            return [];
        } else {
            return json_decode(wp_remote_retrieve_body($response), true);
        }        
    }
             
    /**
     * Tries to validate the HappyFret client account
     * @return bool
     */
    public static function accountValidation() {
        $url = get_option('happyfret_api_base_url') . get_option('happyfret_api_account_validation');        
        $args = [
            'headers' => [
                'x-auth-apikey' => get_option('happyfret_api_key'),
                'x-auth-email' => get_option('happyfret_api_email')
            ]
        ];
        $response = wp_remote_post($url, $args);
        if (200 == wp_remote_retrieve_response_code($response)) {
            return ('ok' == json_decode(wp_remote_retrieve_body($response))->status);
        } 
        return false;        
    }
    
    
    /**
     * Gets the list of products stored in HappyFret warehouse
     * @return type
     */
    public static function getProducts() {
        $url = get_option('happyfret_api_base_url') . get_option('happyfret_api_products');        
        $args = [
            'headers' => [
                'x-auth-apikey' => get_option('happyfret_api_key'),
                'x-auth-email' => get_option('happyfret_api_email')
            ]
        ];
        $response = wp_remote_get($url, $args);
        if (200 != wp_remote_retrieve_response_code($response)) {
            ?><div class="notice notice-error is-dismissible"><p><?php _e( 'Happyfretproducts retrieval error !', HappyfretPlugin::TEXT_DOMAIN ); ?></p></div><?php
            return [];
        } else {
            return json_decode(wp_remote_retrieve_body($response), true);
        }             
    }
    
    /**
     * Creates a product in the HappyFret warehouse
     * @param type $productName
     * @param type $barCode
     * @param type $illustration
     * @param type $width
     * @param type $height
     * @param type $depth
     * @param type $weight
     * @return type
     */
    public static function createProduct($productName, $barCode = null, $illustration = null, $width = null, $height = null, $depth = null, $weight = null) {
        $url = get_option('happyfret_api_base_url') . get_option('happyfret_api_products');        
        $args = [
            'headers' => [
                'x-auth-apikey' => get_option('happyfret_api_key'),
                'x-auth-email' => get_option('happyfret_api_email'),               
            ],
            'body' => [                
                'name' => $productName
            ]
        ];
        if (null != $barCode) {$args['body']['barCode'] = $barCode;}
        if (null != $illustration) {$args['body']['illustration'] = $illustration;}
        if (null != $width) {$args['body']['width'] = $width;}
        if (null != $height) {$args['body']['height'] = $height;}
        if (null != $depth) {$args['body']['depth'] = $depth;}
        if (null != $weight) {$args['body']['weight'] = $weight;}
        $response = wp_remote_post($url, $args);
        if (200 != wp_remote_retrieve_response_code($response)) {
            ?><div class="notice notice-error is-dismissible"><p><?php _e( 'Happyfretproducts creation error !', HappyfretPlugin::TEXT_DOMAIN ); ?></p></div><?php
            return [];
        } else {
            $curlProducts = json_decode(wp_remote_retrieve_body($response), true);
            foreach($curlProducts as $curlProduct) {
                $happyfretProduct = HappyfretProduct::sqlCreate($curlProduct['ref']);
                $happyfretProduct->setName($curlProduct['name']);
                $happyfretProduct->setBarCode($curlProduct['barCode']);
                if (null != $curlProduct['illustration']) {
                    $happyfretProduct->setIllustration($curlProduct['illustration']['versions']['default']['url'] . '/' . $curlProduct['illustration']['versions']['default']['name']);
                }
                $happyfretProduct->setHeight($curlProduct['height']);
                $happyfretProduct->setWidth($curlProduct['width']);
                $happyfretProduct->setDepth($curlProduct['depth']);
                $happyfretProduct->setWeight($curlProduct['weight']);
                $happyfretProduct->setStock($curlProduct['stock']);
                $happyfretProduct->sqlUpdate();
                return $happyfretProduct;
            }
            return null;
        }           
    }
    
    
    /**
     * Gets the best packing solution 
     * @param array $products List of products to pack
     * @param array $parcels List of available parcels for packing
     * @return array
     */
    public static function resolveParcels($products, $parcels) {  
        $url = get_option('happyfret_api_base_url') . get_option('happyfret_api_resolve_parcels');        
        $args = [
            'headers' => [
                'x-auth-apikey' => get_option('happyfret_api_key'),
                'x-auth-email' => get_option('happyfret_api_email')
            ],
            'body' => [
                'products' => $products,
                'parcels' => $parcels
            ]
        ];
        $response = wp_remote_post($url, $args);        
        if (200 == wp_remote_retrieve_response_code($response)) {
            return json_decode(wp_remote_retrieve_body($response), true);
        } 
        return [];                  
    }                                                              


    /**
     * Gets all available shipping rates for the selected parcels
     * @param type $addressFrom Sender address for the shipping
     * @param type $addressTo Recipient address for the shipping
     * @param type $parcels List of parcels to send
     * @return array
     */
    public static function getShippingRates($addressFrom, $addressTo, $parcels) {
        $url = get_option('happyfret_api_base_url') . get_option('happyfret_api_standalone_transport_offers');        
        $args = [
            'headers' => [
                'x-auth-apikey' => get_option('happyfret_api_key'),
                'x-auth-email' => get_option('happyfret_api_email')
            ],
            'body' => [
                'from' => $addressFrom,
                'to' => $addressTo,
                'parcels' => $parcels
            ]
        ];
        $response = wp_remote_post($url, $args);
        if (200 == wp_remote_retrieve_response_code($response)) {
            return json_decode(wp_remote_retrieve_body($response), true);
        } 
        return [];         
    }
    
    /**
     * Gets all available shipping rates for the selected parcels
     * @param type $addressTo Recipient address for the shipping
     * @param type $parcels List of parcels to send
     * @return array
     */
    public static function getLogisticsShippingRates($addressTo, $products) {    
        $url = get_option('happyfret_api_base_url') . get_option('happyfret_api_logistics_transport_offers');        
        $args = [
            'headers' => [
                'x-auth-apikey' => get_option('happyfret_api_key'),
                'x-auth-email' => get_option('happyfret_api_email')
            ],
            'body' => [
                'to' => $addressTo,
                'products' => $products
            ]
        ];
        $response = wp_remote_post($url, $args);
        if (200 == wp_remote_retrieve_response_code($response)) {
            return json_decode(wp_remote_retrieve_body($response), true);
        } 
        return [];        
    }
    
    /**
     * Confirms an order to the HappyFret Information System
     * @param type $addressFrom Sender address for the shipping
     * @param type $addressTo Recipient address for the shipping
     * @param type $parcels List of parcels to send
     * @param type $orderData Internal order data
     * @return array
     * @throws Exception
     */
    public static function orderConfirmation($addressFrom, $addressTo, $parcels, $orderData) {
        switch(get_option('happyfret_client_type')) {
            case HappyfretPlugin::CLIENT_TYPE_STANDALONE:                
                $url = get_option('happyfret_api_base_url') . get_option('happyfret_api_order_confirmation');
                break;
            case HappyfretPlugin::CLIENT_TYPE_LOGISTICS:
                $url = get_option('happyfret_api_base_url') . get_option('happyfret_api_logistics_order_confirmation');
                break;
            default :
                throw new Exception(__('Unknown client type. Please check Happyfret API configuration.', HappyfretPlugin::TEXT_DOMAIN));            
        }  
        $args = [
            'headers' => [
                'x-auth-apikey' => get_option('happyfret_api_key'),
                'x-auth-email' => get_option('happyfret_api_email')
            ],
            'body' => [
                'to' => $addressTo,
                'parcels' => $parcels,
                'order' => $orderData
            ]
        ];               
        if (HappyfretPlugin::CLIENT_TYPE_STANDALONE == get_option('happyfret_client_type')) {
            $args['body']['from'] = $addressFrom;
        }        
        $response = wp_remote_post($url, $args);
        if (200 == wp_remote_retrieve_response_code($response)) {
            return json_decode(wp_remote_retrieve_body($response), true);
        } 
        return [];        
    }           
}