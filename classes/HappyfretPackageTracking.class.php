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

class HappyfretPackageTracking {        
    const SQL_TABLE_NAME = 'happyfret_package_tracking';
    
    private $id_order;    
    private $tracking_number;
    private $package_tracking_number;
    private $pdf;
    private $id_expedition;
    
    
    
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
            .'`id_order` int(11) PRIMARY KEY, '
            .'`tracking_number` varchar(255), '            
            .'`package_tracking_number` text, '
            .'`pdf` varchar(255), '            
            .'`id_expedition` int(11) '            
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
     * Cherche un objet dans la base de données à partir de son id
     * @global type $wpdb
     * @param int $categoryId L'id de la Personn recherchée
     * @return \ParcelTypeId|null L'objet Personn recherché s'il existe, null sinon
     */
    public static function sqlFindOneByOrderId($orderId) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        if (null == $orderId) { return null; }
        $result = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM '.$wpdb->prefix.self::SQL_TABLE_NAME.' WHERE id_order = %d',
                $orderId
            )
        );       
        if (null != $result) {
            $packageTracking = new HappyfretPackageTracking();
            $packageTracking->hydrate($result);
            return $packageTracking;
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
        $query = 'SELECT * FROM '.$wpdb->prefix.self::SQL_TABLE_NAME;
        $results = $wpdb->get_results($query);
        $array = [];
        foreach ($results as $result){      
            $packageTracking = new HappyfretPackageTracking();
            $packageTracking->hydrate($result);
            $array[] =  $packageTracking;
        }
        return $array; 
    }
    
    
    /**
     * 
     * @param type $sqlResult
     * @return $this
     */
    public function hydrate($sqlResult) {
        $this->setOrderId($sqlResult->id_order);    
        $this->setTrackingNumber($sqlResult->tracking_number);
        $this->setPackageTrackingNumber($sqlResult->package_tracking_number);
        $this->setPdf($sqlResult->pdf);
        $this->setExpeditionId($sqlResult->id_expedition);
        return $this;
    }
    
  
    /**
     * Enregistre (crée ou met à jour) l'objet courant dans la base de données
     * @return \ParcelType L'objet courant
     */
    public function sqlPersist() {
        if (null == $this->getOrderId()) {
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
    public function sqlInsert() {
        global $wpdb;
	$query = 'INSERT INTO '.$wpdb->prefix.self::SQL_TABLE_NAME.'( `id_order`, `tracking_number`, `package_tracking_number`, `pdf`, `id_expedition` ) VALUES (%d, %s, %s, %s, %d)';
	$wpdb->query($wpdb->prepare($query, $this->getOrderId(), $this->getTrackingNumber(), $this->getPackageTrackingNumber(),$this->getPdf(), $this->getExpeditionId()));
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
            ['tracking_number' => $this->getTrackingNumber(), 'package_tracking_number' => $this->getPackageTrackingNumber(), 'pdf' => $this->getPdf(), 'id_expedition' => $this->getExpeditionId()],
            ['id_order' => $this->getOrderId()],
            ['%s', '%s', '%s', '%d'],
            ['%d']
        );
        return $this;
    }
    
    public function sqlDeleteCascade() {
        global $wpdb;        
        $wpdb->delete(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            ['id_order' => $this->getOrderId()]
        );
    }
       
    
    /**
     * 
     * @return int L'id de l'objet courant
     */
    public function getOrderId() {
        return $this->id_order;
    }
    
    public function setOrderId($orderId) {
        $this->id_order = $orderId;
        return $this;
    }
    
    
    public function getTrackingNumber() { 
        return $this->tracking_number;
    }
    public function setTrackingNumber($trackingNumber) {
        $this->tracking_number = $trackingNumber;
    }
    
    public function getPackageTrackingNumber() {
        return $this->package_tracking_number;
    }
    public function setPackageTrackingNumber($packageTrackingNumber) {
        $this->package_tracking_number = $packageTrackingNumber;
    }
    
    public function getPdf() {
        return $this->pdf;
    }
    public function setPdf($pdf) {
        $this->pdf = $pdf;
    }
    
    public function getExpeditionId() {
        return $this->id_expedition;
    }
    public function setExpeditionId($expeditionId) {
        $this->id_expedition = $expeditionId;
    }   
}
