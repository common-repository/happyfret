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

class HappyfretParcelType {        
    const SQL_TABLE_NAME = 'happyfret_parcel_type';
    
    private $id;
    private $category;
    private $ref;
    private $name;
    private $width = 0;
    private $height = 0;
    private $depth = 0;
    private $parcelWeight = 0;
    private $maxWeight = 0;
    private $price = 0;
    private $visible = 1;
    
    
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
            .'`id_happyfret_parcel_type` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT, '
            .'`id_category` int(11) NULL, '
            .'`ref` varchar(255) NOT NULL, '
            .'`name` varchar(255) NOT NULL, '
            .'`width` int(11) NOT NULL, '
            .'`height` int(11) NOT NULL, '
            .'`depth` int(11) NOT NULL, '
            .'`parcel_weight` decimal(10,2) NOT NULL, '
            .'`max_weight` decimal(10,2) NOT NULL, '
            .'`price` decimal(10,2) NOT NULL, '
            .'`visible` int(1), '
            .'FOREIGN KEY (id_category) REFERENCES '.$wpdb->prefix.HappyfretCategory::SQL_TABLE_NAME.'(id_category) '
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
     * Cherche un objet ParcelType dans la base de données à partir de son id
     * @global type $wpdb
     * @param int $parcelTypeId L'id de la Personn recherchée
     * @return \ParcelTypeId|null L'objet Personn recherché s'il existe, null sinon
     */
    public static function sqlFindOneById($parcelTypeId) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        if (null == $parcelTypeId) { return null; }
        $result = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id_happyfret_parcel_type, id_category, ref, name, width, height, depth, parcel_weight, max_weight, price, visible FROM '.$wpdb->prefix.self::SQL_TABLE_NAME.' WHERE id_happyfret_parcel_type = %d',
                $parcelTypeId
            )
        );       
        if (null != $result) {
            $parcelType = new HappyfretParcelType();
            $parcelType->hydrate($result);
            return $parcelType;
        } else { return null; }
    }
    
    /**
     *
     */
    public static function sqlFindOneByRef($ref) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        if (null == $ref) { return null; }
        $result = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id_happyfret_parcel_type, id_category, ref, name, width, height, depth, parcel_weight, max_weight, price, visible FROM '.$wpdb->prefix.self::SQL_TABLE_NAME.' WHERE ref = %s',
                $ref
            )
        );       
        if (null != $result) {
            $parcelType = new HappyfretParcelType();
            $parcelType->hydrate($result);
            return $parcelType;
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
        $query = 'SELECT id_happyfret_parcel_type, id_category, ref, name, width, height, depth, parcel_weight, max_weight, price, visible FROM '.$wpdb->prefix.self::SQL_TABLE_NAME;
        $resultats = $wpdb->get_results($query);
        $array = [];
        foreach ($resultats as $result){      
            $parcelType = new HappyfretParcelType();
            $parcelType->hydrate($result);
            $array[] =  $parcelType;
        }
        return $array; 
    }
    
    /**
     * Cherche tous les objets Personn dans la base de données
     * @global type $wpdb    
     * @return array Un tableau contenant tous les objets Personn
     */
    public static function sqlFindAllAvailable() {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;        
        $query = 'SELECT T.id_happyfret_parcel_type, T.id_category, T.ref, T.name, T.width, T.height, T.depth, T.parcel_weight, T.max_weight, T.price, T.visible, SUM(S.quantity) stock '
            .'FROM '.$wpdb->prefix.self::SQL_TABLE_NAME . ' T '
            .'INNER JOIN '.$wpdb->prefix. HappyfretParcelTypeStock::SQL_TABLE_NAME . ' S ON T.id_happyfret_parcel_type = S.id_happyfret_parcel_type '
            .'GROUP BY T.id_happyfret_parcel_type '
            .'HAVING T.visible = 1 AND stock > 0';
        $resultats = $wpdb->get_results($query);            
        $array = [];
        foreach ($resultats as $result){      
            $parcelType = new HappyfretParcelType();
            $parcelType->hydrate($result);
            $array[] =  $parcelType;
        }
        return $array; 
    }
    
    
    /**
     * 
     * @param $sqlResult
     * @return $this
     */
    public function hydrate($sqlResult) {
        if (true == HAPPYFRET_DEBUG) { 
            error_log(__CLASS__ . ' : ' . __FUNCTION__ );
            error_log(print_r($sqlResult, true));
        }        
        $this->setId($sqlResult->id_happyfret_parcel_type);        
        if (null != $sqlResult->id_category) {
            $this->setCategory(HappyfretCategory::sqlFindOneById($sqlResult->id_category));
        }
        $this->setRef($sqlResult->ref);
        $this->setName($sqlResult->name);
        $this->setWidth($sqlResult->width);
        $this->setHeight($sqlResult->height);
        $this->setDepth($sqlResult->depth);
        $this->setParcelWeight($sqlResult->parcel_weight);
        $this->setMaxWeight($sqlResult->max_weight);
        $this->setPrice($sqlResult->price);
        $this->setVisible($sqlResult->visible);
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
        if (null == $this->getCategory()) {
            $categoryId = 'null';
            $categoryType = 'null';
        } else {
            $categoryId = $this->getCategory()->getId();
            $categoryType = '%d';
        }
	$query = 'INSERT INTO '.$wpdb->prefix.self::SQL_TABLE_NAME.'( `id_category`, `ref`, `name`, `width`, `height`, `depth`, `parcel_weight`, `max_weight`, `price`, `visible`) VALUES ('.$categoryType.', %s, %s, %d, %d, %d, %f, %f, %f, %d)';
	$wpdb->query($wpdb->prepare($query, $categoryId, $this->getRef(), $this->getName(), $this->getWidth(), $this->getHeight(), $this->getDepth(), $this->getParcelWeight(), $this->getMaxWeight(), $this->getPrice(), $this->getVisible()));
        $this->id = $wpdb->insert_id;
        return $this;
    }
    
    
    /**
     * Enregistre (met à jour) l'objet courant dans la base de données
     * @global type $wpdb
     * @return $this
     */
    private function sqlUpdate() {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        if (null == $this->getCategory()) {
            $categoryId = null;
        } else {
            $categoryId = $this->getCategory()->getId();            
        }
        $wpdb->update(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            ['id_category' => $categoryId, 'ref' => $this->getRef(), 'name' => $this->getName(), 'width' => $this->getWidth(), 'height' => $this->getHeight(), 'depth' => $this->getDepth(), 'parcel_weight' => $this->getParcelWeight(), 'max_weight' => $this->getMaxWeight(), 'price' => $this->getPrice(), 'visible' => $this->getVisible()],
            ['id_happyfret_parcel_type' => $this->getId()],
            ['%s', '%s', '%s', '%d', '%d', '%d', '%f', '%f', '%f', '%d'],
            ['%d']
        );
        return $this;
    }
    
    public function sqlDeleteCascade() {
        global $wpdb;
        // Effacement des ParcelTypeStock liées au ParcelType
        $wpdb->delete(
            $wpdb->prefix . HappyfretParcelTypeStock::SQL_TABLE_NAME,
            ['id_happyfret_parcel_type' => $this->getId()]
        );
        // Effacement des PackingOrder liées au ParcelType
        $wpdb->delete(
            $wpdb->prefix . HappyfretPackingOrder::SQL_TABLE_NAME,
            ['id_happyfret_parcel_type' => $this->getId()]
        );
        // Effacement du ParcelType
        $wpdb->delete(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            ['id_happyfret_parcel_type' => $this->getId()]
        );
    }
    
    
    public static function getHtmlSelect($name, $selectedValue = null) {
        $html = '<select name="' . str_replace('"', '', $name) . '" style="width:100%">';
        foreach (self::sqlFindAll() as $parcelType) {
            $html .= '<option value="' . $parcelType->getId() . '"';
            if ($selectedValue == $parcelType->getId()) {
                $html .= ' selected="selected"';
            }
            $html .= '>' . $parcelType->getName() . '</option>';
        }
        $html .= '</select>';
        return $html;
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
     * @return 
     */
    public function getCategory() {
        return $this->category;
    }
    
    /**
     *    
     * @return \HappyfretParcelType
     */
    public function setCategory($category) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        $this->category = $category;
        return $this;
    }
    
    /**
     * 
     * @return string 
     */
    public function getRef() {
        return $this->ref;
    }
    
    /**
     *
     * @param string 
     * @return \HappyfretParcelType
     */
    public function setRef($ref) {
        $this->ref = $ref;
        return $this;
    }
    
    /**
     * 
     * @return string 
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     *
     * @param string 
     * @return \ParcelType
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }
    
    public function getWidth() {
        return $this->width;
    }
    
    public function setWidth($width) {
        $this->width = $width;
        return $this;
    }
     
    public function getHeight() {
        return $this->height;
    }

    public function setHeight($height) {
        $this->height = $height;
        return $this;
    }

    public function getDepth() {
        return $this->depth;
    }
        
    public function setDepth($depth) {
        $this->depth = $depth;
        return $this;
    }

    public function getParcelWeight() {
        return $this->parcelWeight;
    }
        
    public function setParcelWeight($parcelWeight) {
        $this->parcelWeight = $parcelWeight;
        return $this;
    }

    public function getMaxWeight() {
        return $this->maxWeight;
    }
        
    public function setMaxWeight($maxWeight) {
        $this->maxWeight = $maxWeight;
        return $this;
    }

    public function getPrice() {
        return $this->price;
    }
        
    public function setPrice($price) {
        $this->price = $price;
        return $this;
    }

    public function getVisible() {
        return $this->visible;
    }
        
    public function setVisible($visible) {
        $this->visible = $visible;
        return $this;
    }    
}
