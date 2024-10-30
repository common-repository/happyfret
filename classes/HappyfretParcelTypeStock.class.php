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

class HappyfretParcelTypeStock {        
    const SQL_TABLE_NAME = 'happyfret_parcel_type_stock';
    
    private $id;
    private $parcelType;
    private $orderId;
    private $date;
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
            .'`id_happyfret_parcel_type_stock` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT, '
            .'`id_happyfret_parcel_type` int(11)NOT NULL, '
            .'`id_order` int(11) NULL, '
            .'`date` datetime NOT NULL, '
            .'`quantity` int(11) NOT NULL '           
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
     * Cherche un objet ParcelTypeStock dans la base de données à partir de son id
     * @global type $wpdb
     * @param int $parcelTypeStockId L'id de la Personn recherchée
     * @return \ParcelTypeStock|null L'objet Personn recherché s'il existe, null sinon
     */
    public static function sqlFindOneById($parcelTypeStockId) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        if (null == $parcelTypeStockId) { return null; }
        $result = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id_happyfret_parcel_type_stock, id_happyfret_parcel_type, id_order, date, quantity FROM '.$wpdb->prefix.self::SQL_TABLE_NAME.' WHERE id_happyfret_parcel_type_stock = %d',
                $parcelTypeStockId
            )
        );       
        if (null != $result) {
            $parcelTypeStock = new HappyfretParcelTypeStock();
            $parcelTypeStock->hydrate($result);
            return $parcelTypeStock;
        } else { return null; }
    }
    
 
    public static function sqlFindByParcelType($parcelType) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        $array = [];
        if (null == $parcelType) { return $array; }
        $resultats = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id_happyfret_parcel_type_stock, id_happyfret_parcel_type, id_order, date, quantity FROM '.$wpdb->prefix.self::SQL_TABLE_NAME.' WHERE id_happyfret_parcel_type = %d',
                $parcelType->getId()
            )
        );       
        foreach ($resultats as $result){      
            $parcelTypeStock = new HappyfretParcelTypeStock();
            $parcelTypeStock->hydrate($result);
            $array[] =  $parcelTypeStock;
        }
        return $array; 
    }
    
    /**
     * Cherche tous les objets Personn dans la base de données
     * @global type $wpdb    
     * @return array Un tableau contenant tous les objets Personn
     */
    public static function sqlFindAll() {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        $query = 'SELECT id_happyfret_parcel_type_stock, id_happyfret_parcel_type, id_order, date, quantity FROM '.$wpdb->prefix.self::SQL_TABLE_NAME;
        $resultats = $wpdb->get_results($wpdb->prepare($query));
        $array = [];
        foreach ($resultats as $result){      
            $parcelTypeStock = new HappyfretParcelTypeStock();
            $parcelTypeStock->hydrate($result);
            $array[] =  $parcelTypeStock;
        }
        return $array; 
    }
    
    
    /**
     * 
     * @param array $sqlResult
     * @return $this
     */
    public function hydrate($sqlResult) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        $this->setId($sqlResult->id_happyfret_parcel_type_stock);
        $this->setParcelType(HappyfretParcelType::sqlFindOneById($sqlResult->id_happyfret_parcel_type));
        $this->setOrderId($sqlResult->id_order);
        $this->setDate(strtotime($sqlResult->date));
        $this->setQuantity($sqlResult->quantity);
        return $this;
    }
    
    /*// …
    public function hydrate(array $donnees)
    {
      foreach ($donnees as $key => $value)
      {
        // On récupère le nom du setter correspondant à l'attribut.
        $method = 'set'.ucfirst($key);

        // Si le setter correspondant existe.
        if (method_exists($this, $method))
        {
          // On appelle le setter.
          $this->$method($value);
        }
      }
    }
    // …*/
    
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
	$query = 'INSERT INTO '.$wpdb->prefix.self::SQL_TABLE_NAME.'(`id_happyfret_parcel_type`, `id_order`, `date`, `quantity`) VALUES (%d, %s, %s, %d)';
        if (null == $this->getOrderId()) {
            $orderId = 'null';
        } else {
            $orderId = $this->getOrderId();
        }
	//$wpdb->query($wpdb->prepare($query, $this->getParcelType()->getId(), $orderId, $this->getDate()->format('Y-m-d'), $this->getQuantity()));
	$wpdb->query($wpdb->prepare($query, $this->getParcelType()->getId(), $orderId, $this->getDate(), $this->getQuantity()));
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
            $orderId = 'null';
        } else {
            $orderId = $this->getOrderId();
        }
        $wpdb->update(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            //['id_happyfret_parcel_type' => $this->getParcelType()->getId(), 'id_order' => $orderId, 'date'  => $this->getDate()->format('Y-m-d H:i:s'), 'quantity' => $this->getQuantity()],
            ['id_happyfret_parcel_type' => $this->getParcelType()->getId(), 'id_order' => $orderId, 'date'  => $this->getDate(), 'quantity' => $this->getQuantity()],
            ['id_happyfret_parcel_type_stock' => $this->getId()],
            ['%d', '%s', '%s', '%d'],
            ['%d']
        );
        return $this;
    }
    
    public function sqlDeleteCascade() {
        global $wpdb;
        // Effacement du ParcelTypeStock
        $wpdb->delete(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            ['parcel_type_id' => $this->getId()]
        );
    }
    
    /**
     * 
     * @return int L'id de l'objet courant
     */
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
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        $this->parcelType = $parcelType;
        return $this;
    }
    
    
    public function getOrderId() {
        return $this->orderId;
    }
 
    public function setOrderId($orderId) {
        $this->orderId = $orderId;
        return $this;
    }
    
    public function getDate() {
        return $this->date;
    }
    
    public function setDate($date) {
        $this->date = $date;
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
