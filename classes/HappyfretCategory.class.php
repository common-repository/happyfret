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

class HappyfretCategory {        
    const SQL_TABLE_NAME = 'happyfret_category';
    
    private $id;    
    private $name;
    
    
    
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
            .'`id_category` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT, '
            .'`name` varchar(255) NOT NULL '            
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
    public static function sqlFindOneById($categoryId) {
        if (true == HAPPYFRET_DEBUG) { error_log(__CLASS__ . ' : ' . __FUNCTION__ ); }
        global $wpdb;
        if (null == $categoryId) { return null; }
        $result = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id_category, name FROM '.$wpdb->prefix.self::SQL_TABLE_NAME.' WHERE id_category = %d',
                $categoryId
            )
        );       
        if (null != $result) {
            $category = new HappyfretCategory();
            $category->hydrate($result);
            return $category;
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
        $query = 'SELECT id_category, name FROM '.$wpdb->prefix.self::SQL_TABLE_NAME;
        $resultats = $wpdb->get_results($query);
        $array = [];
        foreach ($resultats as $result){      
            $category = new HappyfretCategory();
            $category->hydrate($result);
            $array[] =  $category;
        }
        return $array; 
    }
    
    
    /**
     * 
     * @param type $sqlResult
     * @return $this
     */
    public function hydrate($sqlResult) {
        $this->setId($sqlResult->id_category);
        $this->setName($sqlResult->name);        
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
	$query = 'INSERT INTO '.$wpdb->prefix.self::SQL_TABLE_NAME.'( `name` ) VALUES (%s)';
	$wpdb->query($wpdb->prepare($query, $this->getName()));
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
            ['name' => $this->getName()],
            ['id_category' => $this->getId()],
            ['%s'],
            ['%d']
        );
        return $this;
    }
    
    public function sqlDeleteCascade() {
        global $wpdb;
        // Effacement des ParcelType liées à la Category
        $wpdb->delete(
            $wpdb->prefix . HappyfretParcelType::SQL_TABLE_NAME,
            ['id_category' => $this->getId()]
        );
        // Effacement de la Category
        $wpdb->delete(
            $wpdb->prefix . self::SQL_TABLE_NAME,
            ['id_category' => $this->getId()]
        );
    }
    
    public static function getHtmlSelect($name, $selectedValue = null) {
        $html = '<select name="' . str_replace('"', '', $name) . '" style="width:100%">';
        foreach (self::sqlFindAll() as $category) {
            $html .= '<option value="' . $category->getId() . '"';
            if ($selectedValue == $category->getId()) {
                $html .= ' selected="selected"';
            }
            $html .= '>' . $category->getName() . '</option>';
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
       
}
