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

class HappyfretPackingOrderProduct {        
    const SQL_TABLE_NAME = 'happyfret_packing_order_product';          
    private $packingOrderId;
    private $productId;
    private $quantity;
    
    
    
    /**
     * Class constructor
     */
    public function __construct() {
        // Nothing to do yet
    }
    
    /**
     * 
     * @global type $wpdb
     * @return string
     */
    public static function getInstallQuery() {
        global $wpdb;
        return 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.self::SQL_TABLE_NAME.'` ( '
            .'`id_happyfret_packing_order` int(11) NOT NULL, '           
            .'`id_product` int(11) NOT NULL, '
            .'`quantity` int(11) NOT NULL, '
            .'PRIMARY KEY (`id_happyfret_packing_order`, `id_product`) '
        .');';                       
    }
    
    /**
     * 
     * @global type $wpdb
     * @return string
     */
    public static function getUninstallQuery() {
        global $wpdb;
        return "DROP TABLE IF EXISTS ".$wpdb->prefix.self::SQL_TABLE_NAME;  
    }
    
     /**
     * 
     */
    public static function sqlFindByPackingOrderId($packingOrderId) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id_happyfret_packing_order, id_product, quantity FROM '.$wpdb->prefix.self::SQL_TABLE_NAME.' WHERE id_happyfret_packing_order = %d',
                $packingOrderId
            )
        );       
        $array = [];
        foreach ($results as $result){   
            $packingOrderProduct = new HappyfretPackingOrderProduct();
            $packingOrderProduct->hydrate($result);
            $array[] =  $packingOrderProduct;
        }
        return $array; 
    }
    
    
    
    
    public static function sqlDeletePackingOrderProductsForPackingOrder($packingOrderId) {
       if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb; 
        
        $wpdb->delete(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            [
                'id_happyfret_packing_order' => $packingOrderId
            ]
        );            
    }
    
    /**
     * 
     * @param type $sqlResult
     * @return $this
     */
    public function hydrate($sqlResult) {          
        $this->setPackingOrderId($sqlResult->id_happyfret_packing_order);
        $this->setProductId($sqlResult->id_product);        
        $this->setQuantity($sqlResult->quantity);
        return $this;
    }
    
  
    /**
     * Enregistre (crée ou met à jour) l'objet courant dans la base de données
     * @return \ParcelType L'objet courant
     */
    public function sqlPersist() {
        $this->sqlDeleteCascade();
        $this->sqlInsert();        
    }
    
    /**
     * Enregistre (crée) l'objet courant dans la base de données
     * @global type $wpdb
     * @return $this
     */
    private function sqlInsert() {
        global $wpdb;              
	$query = 'INSERT INTO '.$wpdb->prefix.self::SQL_TABLE_NAME.'( `id_happyfret_packing_order`, `id_product`, `quantity` ) VALUES (%d, %d, %d)';
	$wpdb->query($wpdb->prepare($query, $this->getPackingOrderId(), $this->getProductId(), $this->getQuantity()));
        $this->id = $wpdb->insert_id;
        return $this;
    }
    
    
    /**
     * Enregistre (met à jour) l'objet courant dans la base de données
     * @global type $wpdb
     * @return $this
     */
    private function sqlUpdate() {
        global $wpdb;
                
        $wpdb->update(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            ['quantity' => $this->getQuantity()],
            ['id_happyfret_packing_order' => $this->getProductOrderId(), 'id_product' => $this->getProductId()],
            ['%d'],
            ['%d', '%d']
        );
        return $this;
    }
    
    public function sqlDeleteCascade() {
        global $wpdb;
        // 
        $wpdb->delete(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            ['id_happyfret_packing_order' => $this->getPackingOrderId(), 'id_product' => $this->getProductId()]
        );
    }
    
    
    
    public function getPackingOrderId() {
        return $this->packingOrderId;
    }
    public function setPackingOrderId($packingOrderId) {
        $this->packingOrderId = $packingOrderId;
        return $this;
    }
    
    
    public function getProductId() {
        return $this->productId;
    }
    public function setProductId($productId) {
        $this->productId = $productId;
        return $this;
    }
       
    public function getQuantity() {
        return $this->quantity;
    }
    public function setQuantity($quantity) {
        $this->quantity = $quantity;
        return $this;
    }       
}
