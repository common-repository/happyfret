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

class HappyfretProduct {        
    const SQL_TABLE_NAME = 'happyfret_product';
    
    private $woocommerceId;    
    private $happyfretRef;
    private $name;
    private $illustration;
    private $barCode;
    private $width;
    private $height;
    private $depth;
    private $weight;
    private $stock;
    
    
    
    
    
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
            .'`ref_happyfret` varchar(255) PRIMARY KEY NOT NULL,'
            .'`id_woocommerce` int(11) NULL, '
            .'`name` varchar(255) NULL,'
            .'`illustration` varchar(255) NULL,'
            .'`barcode` varchar(255) NULL,'
            .'`width` int(11) NULL,'
            .'`height` int(11) NULL,'
            .'`depth` int(11) NULL,'
            .'`weight` int(11) NULL,'
            .'`stock` int(11) NULL'
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
            $happyfretProduct = new HappyfretProduct();
            $happyfretProduct->hydrate($result);
            $array[] =  $happyfretProduct;
        }
        return $array; 
    }
    
    /**
     * Cherche tous les objets Personn dans la base de données
     * @global type $wpdb    
     * @return array Un tableau contenant tous les objets Personn
     */
    public static function sqlFindAllUnlinked() {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        $query = 'SELECT * FROM '.$wpdb->prefix.self::SQL_TABLE_NAME . ' WHERE `id_woocommerce` IS NULL';
        $resultats = $wpdb->get_results($query);
        $array = [];
        foreach ($resultats as $result){      
            $happyfretProduct = new HappyfretProduct();
            $happyfretProduct->hydrate($result);
            $array[] =  $happyfretProduct;
        }
        return $array; 
    }
    
    public static function sqlUpdateStock() {
        global $wpdb;
        return $this;
    }
    
    /**
     * 
     * @global type $wpdb    
     * @return array Un tableau contenant tous les objets Personn
     */
    public static function sqlFindByShopProductId($shopProductId) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        
         $result = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM '.$wpdb->prefix.self::SQL_TABLE_NAME . ' WHERE `id_woocommerce` = %d',
                $shopProductId
            )
        );       
        if (null != $result) {
            $product = new HappyfretProduct();
            $product->hydrate($result);
            return $product;
        } else { return null; }        
    }
    
    
    /**
     * 
     * @param type $sqlResult
     * @return $this
     */
    public function hydrate($sqlResult) {
        $this->setHappyfretRef($sqlResult->ref_happyfret);        
        $this->setWoocommerceId($sqlResult->id_woocommerce);
        if (isset($sqlResult->name)) { $this->setName($sqlResult->name); }
        if (isset($sqlResult->barCode)) { $this->setBarCode($sqlResult->barCode); }
        if (isset($sqlResult->width)) { $this->setWidth($sqlResult->width); }
        if (isset($sqlResult->height)) { $this->setHeight($sqlResult->height); }
        if (isset($sqlResult->depth)) { $this->setDepth($sqlResult->depth); }
        if (isset($sqlResult->weight)) { $this->setWeight($sqlResult->weight); }
        if (isset($sqlResult->stock)) { $this->setStock($sqlResult->stock); }
        return $this;
    }
    
      
    /**
     * Enregistre (met à jour) l'objet courant dans la base de données
     * @global type $wpdb
     * @return $this
     */
    public function sqlUpdate() {
        if (true == HAPPYFRET_DEBUG) { 
            error_log(__CLASS__ . ' : ' . __FUNCTION__); 
            //echo '<p>' . __CLASS__ . ' : ' . __FUNCTION__ . '</p>';
        }
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            [
                'name' => $this->getName(),
                'illustration' => $this->getIllustration(),
                'barcode' => $this->getBarCode(),
                'id_woocommerce' => $this->getWoocommerceId(),
                'width' => $this->getWidth(),
                'height' => $this->getHeight(),
                'depth' => $this->getDepth(),
                'weight' => $this->getWeight(),
                'stock' => $this->getStock()
            ],
            ['ref_happyfret' => $this->getHappyfretRef()],
            ['%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d'],
            ['%s']
        );
        return $this;
    }
    
    public static function sqlCreate($happyfretRef) {
        global $wpdb;
	$query = 'INSERT INTO '.$wpdb->prefix.self::SQL_TABLE_NAME.'( `ref_happyfret` ) VALUES (%s)';
	$wpdb->query($wpdb->prepare($query, $happyfretRef));
        $product = new HappyfretProduct();        
        $product->setHappyfretRef($happyfretRef);        
        return $product;
    }
    
    public function sqlDelete() {
        global $wpdb;        
        $wpdb->delete(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            [
                'id_woocommerce' => $this->getWoocommerceId(),
                'ref_happyfret' => $this->getHappyfretRef(),
            ]
        );        
    }
       
    
    /**
     * 
     * @return string
     */
    public function getHappyfretRef() {
        return $this->happyfretRef;
    }    
    
    public function setHappyfretRef($happyfretRef) {
        $this->happyfretRef = $happyfretRef;
        return $this;
    }
    
    /**
     * 
     * @return int|null 
     */
    public function getWoocommerceId() {
        return $this->woocommerceId;
    }    
    public function setWoocommerceId($woocommerceId, $sqlUpdate = false) {
        global $wpdb;
        if (true == $sqlUpdate) {
            $wpdb->update(
                $wpdb->prefix . self::SQL_TABLE_NAME,
                ['id_woocommerce' => $woocommerceId],
                ['id_woocommerce' => $this->getWoocommerceId(), 'id_happyfret' => $this->getHappyfretRef()],
                ['%d'],
                ['%d', '%s']
            );
        }
        $this->woocommerceId = $woocommerceId;
        return $this;
    }
    
        
    /**
     * 
     */
    public function getName() {
        return $this->name;
    }
    public function setName($name) {
        $this->name = $name;
        return $this;
    }
        
    /**
     * 
     */
    public function getIllustration() {
        return $this->illustration;
    }
    public function setIllustration($illustration) {
        $this->illustration = $illustration;
        return $this;
    }
    
    /**
     * 
     */
    public function getBarCode() {
        return $this->barCode;
    }
    public function setBarCode($barCode) {
        $this->barCode = $barCode;
        return $this;
    }
    
    /**
     * 
     */
    public function getHeight() {
        return $this->height;
    }
    public function setHeight($height) {
        $this->height = $height;
        return $this;
    }
    
    /**
     * 
     */
    public function getWidth() {
        return $this->width;
    }
    public function setWidth($width) {
        $this->width = $width;
        return $this;
    }
    
    /**
     * 
     */
    public function getDepth() {
        return $this->depth;
    }
    public function setDepth($depth) {
        $this->depth = $depth;
        return $this;
    }
    
    /**
     * 
     */
    public function getWeight() {
        return $this->weight;
    }
    public function setWeight($weight) {
        $this->weight = $weight;
        return $this;
    }
    
    /**
     * 
     */
    public function getStock() {
        return $this->stock;
    }
    public function setStock($stock) {
        $this->stock = $stock;
        return $this;
    }   
       
}
