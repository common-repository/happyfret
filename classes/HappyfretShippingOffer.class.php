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

class HappyfretShippingOffer {        
    const SQL_TABLE_NAME = 'happyfret_shipping_offer';
    
    private $id;    
    private $companyId;
    private $offerCode;
    
    
    
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
            .'`id_shipping_offer` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT, '
            .'`id_company` varchar(255) NOT NULL, '
            .'`code_offer` varchar(255) NOT NULL, '
            .'UNIQUE KEY (`id_company`, `code_offer`) '
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
    public static function sqlFindOneById($shippingOfferId) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        if (null == $categoryId) { return null; }
        $result = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM '.$wpdb->prefix.self::SQL_TABLE_NAME.' WHERE id_shipping_offer = %d',
                $shippingOfferId
            )
        );       
        if (null != $result) {
            $shippingOffer = new HappyfretShippingOffer();
            $shippingOffer->hydrate($result);
            return $shippingOffer;
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
        $resultats = $wpdb->get_results($query);
        $array = [];
        foreach ($resultats as $result){      
            $shippingOffer = new HappyfretShippingOffer();
            $shippingOffer->hydrate($result);
            $array[] =  $shippingOffer;
        }
        return $array; 
    }
    
    public static function sqlFindOrCreate($companyId, $offerCode) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        $result = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM '.$wpdb->prefix.self::SQL_TABLE_NAME.' WHERE id_company = %s AND code_offer = %s',
                $companyId,
                $offerCode
            )
        );       
        if (null == $result) {
            $shippingOffer = new HappyfretShippingOffer();
            $shippingOffer->setCompanyId($companyId);
            $shippingOffer->setOfferCode($offerCode);
            $shippingOffer->sqlPersist();
        } else {
            $shippingOffer = new HappyfretShippingOffer();
            $shippingOffer->hydrate($result);
        }
        return $shippingOffer;
    }
    
    
    /**
     * 
     * @param type $sqlResult
     * @return $this
     */
    public function hydrate($sqlResult) {
        $this->setId($sqlResult->id_shipping_offer);
        $this->setCompanyId($sqlResult->id_company);        
        $this->setOfferCode($sqlResult->code_offer);
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
	$query = 'INSERT INTO '.$wpdb->prefix.self::SQL_TABLE_NAME.'( `id_company`, `code_offer` ) VALUES (%s, %s)';
	$wpdb->query($wpdb->prepare($query, $this->getCompanyId(), $this->getOfferCode()));
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
            ['id_company' => $this->getCompanyId(), 'offer_code' => $this->getOfferCode()],
            ['id_shipping_offer' => $this->getId()],
            ['%s', '%s'],
            ['%d']
        );
        return $this;
    }
    
    public function sqlDeleteCascade() {
        global $wpdb;        
        // Effacement du ShippingOffer
        $wpdb->delete(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            ['id_shipping_offer' => $this->getId()]
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
    
 
    
    /**
     * 
     * @return string 
     */
    public function getCompanyId() {
        return $this->companyId;
    }    
    /**
     *
     * @param string 
     * @return \ParcelType
     */
    public function setCompanyId($companyId) {
        $this->companyId = $companyId;
        return $this;
    }
       
    /**
     * 
     * @return string 
     */
    public function getOfferCode() {
        return $this->offerCode;
    }    
    /**
     *
     * @param string 
     * @return \ParcelType
     */
    public function setOfferCode($offerCode) {
        $this->offerCode = $offerCode;
        return $this;
    }
       
}
