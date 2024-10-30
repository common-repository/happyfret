<?php
/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    HappyFret
 * @subpackage HappyFret/classes
 * @author     Advise Communication <rmariage@advise.fr>
 */

class HappyfretProductsSynchronizer {
    
    private static $_instance = null;
    private $happyfretProducts = null;    
    
    private function __construct() {
        // Nothing to do
    }
    
    /**
     *
     */
    public static function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new HappyfretProductsSynchronizer();  
        }
        return self::$_instance;
    }
    
    public function getHappyfretProducts() {
        if (null == $this->happyfretProducts) {
            //$this->apiLoadHappyfretProducts();
            $this->happyfretProducts = HappyfretProduct::sqlFindAll();
        }
        return $this->happyfretProducts;
    }
    
    /**
     * 
     * @param int $woocommerceId
     * @return HappyfretProduct
     */
    public function findHappyfretProductByWoocommerceId($woocommerceId) {        
        foreach($this->getHappyfretProducts() as $happyfretProduct) {
            if ($woocommerceId == $happyfretProduct->getWoocommerceId()) {
                return $happyfretProduct;
            }
        }
        return null;
    }
    
    /**
     * 
     * @param string $happyfretRef
     * @return HappyfretProduct
     */
    public function findHappyfretProductByHappyfretRef($happyfretRef) {        
        foreach($this->getHappyfretProducts() as $happyfretProduct) {
            if ($happyfretRef == $happyfretProduct->getHappyfretRef()) {
                return $happyfretProduct;
            }
        }
        return null;
    }
    
    
    public function apiLoadHappyfretProducts() {
        $existingRefs = [];
        // Foreach product known in Happyfret
        foreach(HappyfretApi::getProducts()as $apiProduct) {
            // Look for the product in database
            $happyfretProduct = $this->findHappyfretProductByHappyfretRef($apiProduct['ref']);
            if (null == $happyfretProduct ) {
                // If the product is unknown it is created
                $happyfretProduct = HappyfretProduct::sqlCreate($apiProduct['ref']);
            }
            // Products data are updated
            $happyfretProduct->setName($apiProduct['name']);
            $happyfretProduct->setBarCode($apiProduct['barCode']);
            $happyfretProduct->setWidth($apiProduct['width']);
            $happyfretProduct->setHeight($apiProduct['height']);
            $happyfretProduct->setDepth($apiProduct['depth']);
            $happyfretProduct->setWeight($apiProduct['weight']);
            $happyfretProduct->setStock($apiProduct['stock']);            
            $happyfretProduct->sqlUpdate();
            
            // Keep record of the existing reference
            $existingRefs[] = $apiProduct['ref'];
        }
        // Delete all HappyFretProducts not returned by the api
        if ($this->happyfretProducts) {
            foreach($this->happyfretProducts as $happyfretProduct) {
                if (!in_array($happyfretProduct->getHappyfretRef(), $existingRefs)) {
                    $happyfretProduct->sqlDelete();                
                }
            }
        }
        // Destroy existing list of HappyfretProduct to make sure it will be reloaded
        $this->happyfretProducts = null;
    }
    
    public function getLinkedWoocommerceIds() {
        $linkedWoocommerceIds = [];
        foreach ($this->getHappyfretProducts() as $happyfretProduct) {
            if (null != $happyfretProduct->getWoocommerceId()) {
                $linkedWoocommerceIds[] = $happyfretProduct->getWoocommerceId();
            }
        }
        return $linkedWoocommerceIds;
    }
      
    
}