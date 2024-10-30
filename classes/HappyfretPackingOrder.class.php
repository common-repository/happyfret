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


class HappyfretPackingOrder {        
    const SQL_TABLE_NAME = 'happyfret_packing_order';    
    private $id;    
    private $parcelType;
    private $orderId = null;
    private $userId;
    private $products;
    
    
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
            .'`id_happyfret_packing_order` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT, '
            .'`id_happyfret_parcel_type` int(11) NOT NULL, '
            .'`id_order` int(11) NULL DEFAULT NULL, '
            .'`id_user` int(11) NULL '
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
    public static function sqlFindOneById($packingOrderId) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        if (null == $packingOrderId) { return null; }
        $result = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id_happyfret_packing_order, id_happyfret_parcel_type, id_order, id_user FROM '.$wpdb->prefix.self::SQL_TABLE_NAME.' WHERE id_happyfret_packing_order = %d',
                $packingOrderId
            )
        );       
        if (null != $result) {
            $packingOrder = new HappyfretPackingOrder();
            $packingOrder->hydrate($result);
            return $packingOrder;
        } else { return null; }
    }
    
    public static function sqlFindOneByOrderId($orderId) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        if (null == $orderId) { return null; }
        $result = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id_happyfret_packing_order, id_happyfret_parcel_type, id_order, id_user FROM '.$wpdb->prefix.self::SQL_TABLE_NAME.' WHERE id_order = %d',
                $orderId
            )
        );       
        if (null != $result) {
            $packingOrder = new HappyfretPackingOrder();
            $packingOrder->hydrate($result);
            return $packingOrder;
        } else { return null; }
    }
    
    /**
     * Cherche tous les objets Personn dans la base de données
     * @global type $wpdb    
     * @return array Un tableau contenant tous les objets Personn
     */
    public static function sqlFindAll() {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        $query = 'SELECT id_happyfret_packing_order, id_happyfret_parcel_type, id_order, id_user FROM '.$wpdb->prefix.self::SQL_TABLE_NAME;
        $resultats = $wpdb->get_results($query);
        $array = [];
        foreach ($resultats as $result){   
            $packingOrder = new HappyfretPackingOrder();
            $packingOrder->hydrate($result);
            $array[] =  $packingOrder;
        }
        return $array; 
    }
    
    
    public static function sqlDeleteTempPackingOrderForUser($userId) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;               
        $results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id_happyfret_packing_order FROM '.$wpdb->prefix.self::SQL_TABLE_NAME.' WHERE id_order = 0 AND id_user = %d',
                $userId
            )
        );   
        foreach($results as $result) {
            HappyfretPackingOrderProduct::sqlDeletePackingOrderProductsForPackingOrder($result->id_happyfret_packing_order);
        }
        
        $wpdb->delete(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            [
                'id_order' => 0,
                'id_user' => $userId
            ]
        );            
    }
    
    public static function sqlSetOrderIdForUser($userId, $orderId) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ . 'userId : ' . $userId . ' --- orderId : ' . $orderId); }
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            ['id_order' => $orderId],
            ['id_user' => $userId, 'id_order' => 0],
            ['%d'],
            ['%d', '%d']
        );
    }
    
    
    public static function sqlCreateFromSelectedParcels($selectedParcels) {
        $packingOrderIds = [];
        // Create new packing orders for this cart      
        foreach($selectedParcels as $selectedParcel) {  
            if (is_array($selectedParcel)) {
                // Creates a packing order for the selected parcel if the cart id != 0
                if (0 != get_current_user_id()) {
                    $packingOrder = new HappyfretPackingOrder();
                    $packingOrder->setUserId(get_current_user_id());
                    $packingOrder->setParcelType(HappyfretParcelType::sqlFindOneByRef($selectedParcel['ref']));
                    $packingOrder->sqlPersist();                            
                    foreach($selectedParcel['products'] as $product) {
                        $packingOrderProduct = new HappyfretPackingOrderProduct();
                        $packingOrderProduct->setPackingOrderId($packingOrder->getId());
                        $packingOrderProduct->setProductId($product['ref']);
                        $packingOrderProduct->setQuantity($product['unitsCount']);
                        $packingOrderProduct->sqlPersist();                                                                                       
                    }
                    $packingOrderIds[] = $packingOrder->getId();
                }
            }
        } 
        return $packingOrderIds;
    }
    
    /**
     * 
     * @param type $sqlResult
     * @return $this
     */
    public function hydrate($sqlResult) {          
        $this->setId($sqlResult->id_happyfret_packing_order);
        $this->setParcelType(HappyfretParcelType::sqlFindOneById($sqlResult->id_happyfret_parcel_type));        
        $this->setOrderId($sqlResult->id_order);
        $this->setUserId($sqlResult->id_user);
        return $this;
    }
    
  
    /**
     * Enregistre (crée ou met à jour) l'objet courant dans la base de données
     * @return \ParcelType L'objet courant
     */
    public function sqlPersist() {
        if (null == $this->getId()) {
            return $this->sqlInsert();
        } else {
            return $this->sqlUpdate();
        }
    }
    
    /**
     * Enregistre (crée) l'objet courant dans la base de données
     * @global type $wpdb
     * @return $this
     */
    private function sqlInsert() {        
        global $wpdb;
            
        if (null == $this->getOrderId()) {
            $orderType = '%s';
            $orderId = 'null';
        } else {
            $orderType = '%d';
            $orderId = $this->getOrderId();
        }       
        if (null == $this->getUserId()) {
            $userType = '%s';
            $userId = 'null';
        } else {
            $userType = '%d';
            $userId = $this->getUserId();
        }        
	$query = 'INSERT INTO '.$wpdb->prefix.self::SQL_TABLE_NAME.'( `id_happyfret_parcel_type`, `id_order`, `id_user` ) VALUES (%s, '.$orderType.', '.$userType.')';
	$wpdb->query($wpdb->prepare($query, $this->getParcelType()->getId(), $orderId, $userId));
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
            
        if (null == $this->getOrderId()) {
            $orderType = '%s';
            $orderId = 'null';
        } else {
            $orderType = '%d';
            $orderId = $this->getOrderId();
        }       
        if (null == $this->getUserId()) {
            $userType = '%s';
            $userId = 'null';
        } else {
            $userType = '%d';
            $userId = $this->getUserId();
        }  

        $wpdb->update(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            ['id_happyfret_parcel_type' => $this->getParcelType()->getId(), 'id_order' => $orderId, 'id_user' => $userId],
            ['id_happyfret_packing_order' => $this->getId()],
            ['%s', $orderType, $userType],
            ['%d']
        );
        return $this;
    }
    
    public function sqlDeleteCascade() {
        global $wpdb;
        // 
        $wpdb->delete(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            ['id_happyfret_parcel_type' => $this->getId()]
        );
    }
    
    
    public function getArrayValue() {
        $arrayValue = [
            'width' => $this->getParcelType()->getWidth()/10,
            'height' => $this->getParcelType()->getHeight()/10,
            'depth' => $this->getParcelType()->getDepth()/10,
            'weight' => 0,
            'products' => []
        ];
        foreach($this->getProducts() as $packingOrderProduct) {                        
            $wcProduct =  wc_get_product($packingOrderProduct->getProductId());
            $arrayValue['weight'] += $packingOrderProduct->getQuantity() * $wcProduct->get_weight();
            $arrayValue['products'][] = [
                'name' => $wcProduct->get_name(),
                'weight' => $wcProduct->get_weight(),
                'quantity' => $packingOrderProduct->getQuantity()
            ];
        }
        return $arrayValue;
    }
    
    
    public function getId() {
        return $this->id;
    }
    private function setId($id) {
        $this->id = $id;
        return $this;
    }
    
    
    public function getParcelType() {
        return $this->parcelType;
    }
    public function setParcelType($parcelType) {
        $this->parcelType = $parcelType;
        return $this;
    }
       
    public function getUserId() {
        return $this->userId;
    }
    public function setUserId($userId) {
        $this->userId = $userId;
        return $this;
    }
       
    public function getOrderId() {
        return $this->orderId;
    }
    public function setOrderId($orderId) {
        $this->orderId = $orderId;
        return $this;
    }
    
    
    public function getProducts() {
        if (null == $this->products) {
            $this->products = HappyfretPackingOrderProduct::sqlFindByPackingOrderId($this->getId());
        }
        return $this->products;
    }
}
