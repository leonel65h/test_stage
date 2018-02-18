<?php

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DBAL 3.1.0
 * 
 * Abstraction database layer implement object \DateTime structire
 * nullable value for many to one relation 
 *
 * @author Atemkeng Azankang  
 */
class DBAL extends Database {

    /**
     *
     * @var type 
     */
    protected $object;

    /**
     *
     * @var type 
     */
    protected $objectName;
    protected $table;
    protected $instanceid;

    /**
     *
     * @var type 
     */
    protected $objectVar;

    /**
     *
     * @var type 
     */
    protected $objectValue;

    /**
     *
     * @var type 
     */
    protected $nbVar;

    /**
     * collection d'objet utilisé dans les relations de n:n et dans les relations 1:n bidirectionnelles
     * @var type 
     */
    protected $listeEntity;
    protected $objectCollection;

    /**
     *
     * @var type 
     */
    private $select;

    /**
     *
     * @var type 
     */
    private $en;

    /**
     * liste des entités en relation de 1:n et 1:1
     * @var type 
     */
    protected $entity_link_list;
    private $iterat;
    private $update = false;

    public function __construct($object = null) {
        parent::__construct();
//        global $em;
//        $this->em = $em;

        $this->instanciateVariable($object);
    }

//    public static function getEntityManager() {
    public static function getEntityManager() {
        global $enittycollection;
        
        $global_navigation = Core::buildOriginCore();
        $enittyfoldes = [];

        foreach ($global_navigation as $key => $project) {
            if (is_object($project)) {
                foreach ($project->listmodule as $key => $module) {
                    if (is_object($module)) {
                        $enittyfoldes[] = __DIR__ . "/../../src/" . $project->name . "/" . $module->name . "/Entity";
                        foreach ($module->listentity as $key => $entity) {
                                $enittycollection[strtolower($entity->name)] = __DIR__ . "/../../src/" . $project->name . "/" . $module->name;
                        }
                    }
                }
            }
        }

        // Create a simple "default" Doctrine ORM configuration for Annotations
        $isDevMode = true;
        $config = Setup::createAnnotationMetadataConfiguration($enittyfoldes, $isDevMode);
        // or if you prefer yaml or XML
        //$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
        //$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);
        // database configuration parameters
        $conn = array(
            'driver' => 'pdo_mysql',
            'dbname' => dbname,
            'user' => dbuser,
            'password' => dbpassword,
            'host' => dbhost,
                //    'path' => __DIR__ . '/db.sqlite',
        );

        // obtaining the entity manager
        return EntityManager::create($conn, $config);
    }

    protected $em;

    public function is_doctrine_entity($className) {
//        $this->em = $this->getEntityManager();
        return !$this->em->getMetadataFactory()->isTransient("\\" . $className);
    }

    public function getDoctrineMetadata($className) {

        if ($this->is_doctrine_entity($className))
            return $this->em->getClassMetadata("\\" . $className);

        return null;
    }

    /**
     * persiste les entités issue d'un attribut en relation de n:n
     * 
     * @param integer $id l'id de l'entité proprietère de la relation
     * @return 
     */
    /*
     * ok la il se pose un souci jusque la je n'ai pu persister que les entités deja formé dans le controller
     * et juste ajouté dans l'instance. or il ya des situation ou c'est dans le dao qu'on doit former cette
     * entité et la c'est vraiment la galere du moins pour le moment vu que je n'ai pas encore trouvé de
     * solution. je pense pour une instanciation dynamique de l'entité en question mais massa je ne sais pas
     * ou prendre l'autre la hummmmm!!!!
     */
    private function manyToManyAdd($id, $update = false, $change_collection = []) {
        /**
         * on traite chaque attribut de maniere unique, attribut qui est lui aussi une liste d'entité
         */
        $success = true;
        foreach ($this->objectCollection as $index => $listentity) {

            /**
             * on isole chaque entité contenu dans la liste des entités
             */
            $association = true;
            foreach ($listentity as $entity) {

                if (!$entity->getId())
                    break;
                /**
                 * chaque entité est persistée
                 */
                // valeur des attributs de la table
                $values = [];

                $entityName = strtolower(get_class($entity));

                if ($this->tableExists($entityName . '_' . $this->objectName)) {
                    $entityTable = $entityName . "_" . $this->objectName;
                    $direction = "ld";
                } elseif ($this->tableExists($this->objectName . "_" . $entityName)) {
                    $entityTable = $this->objectName . "_" . $entityName;
                    $direction = "rl";
                } else {
                    $association = false;
                    $entityTable = $entityName;
                    $direction = "lr";
                }


                /**
                 * on instantie la class $entityTable trop cool
                 */
                $persistecollection = true;
                $reflect = new ReflectionClass($entityTable);
                $object = $reflect->newInstance();
                $objectValue = array_values((array) $entity);
                $objectValueEntity = array_values((array) $object);
                $nbvar = count($objectValueEntity);
                $parameterQuery = '?';
                for ($i = 1; $i < $nbvar; $i++) {
                    $parameterQuery .= ',?';
                }
                $values[] = '';
                for ($j = 1; $j < $nbvar - 2; $j++) {
                    if (is_object($objectValue[$j]))
                        $values[] = $objectValue[$j]->getId();
                    else
                        $values[] = $objectValue[$j];
                }

                if ($direction == "lr") {

                    $persistecollection = false;
                    $sql = "update `" . $entityTable . "` set " . $this->objectName . "_id = $id where id = " . $entity->getId();

                    $query = $this->link->prepare($sql);
                    $success = $query->execute() or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql, $entityTable, $values));
                } elseif ($direction == "ld") {
                    $values[] = $id;
                    if ($association) {
                        $values[] = $entity->getId();
                        if (!$entity->getId())
                            $persistecollection = false;
                    }else {
                        $id_call = array_values((array) $entity);
                        $values[] = $id_call[count($id_call) - 1]->getId();
                    }

                    $sql = "insert into `" . $entityTable . "` value (" . $parameterQuery . ")";

                    $query = $this->link->prepare($sql);
                    $success = $query->execute($values) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql, $entityTable, $values));
                } else {

                    if ($association) {
                        $values[] = $entity->getId();
                        if (!$entity->getId())
                            $persistecollection = false;
                    }else {
                        $id_call = array_values((array) $entity);
                        $values[] = $id_call[count($id_call) - 1]->getId();
                        //on vérifie si le dernier attribut (exclave) est non null dans
                        if (!$id_call[count($id_call) - 1]->getId())
                            $persistecollection = false;
                    }
                    $values[] = $id;

                    $sql = "insert into `" . $entityTable . "` value (" . $parameterQuery . ")";

                    $query = $this->link->prepare($sql);
                    $success = $query->execute($values) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql, $entityTable, $values));
                }

                if ($persistecollection) {
                    $success = true;
                }
            }
        }

        return $success;
    }

    /**
     * cette methode est utilisé lors de l'update et non lors du delete mais il se peut que il ait une
     * moyen de faire son update sans les supprimer. mais je ne l'ai pas encore trouvé donc sa rester une
     * hypothèse
     * 
     * @param type $id
     * @return type
     */
    private function manyToManyDelete($id, $change_collection = []) {

        $sql = "";
        $success = "";
        if (!$change_collection)
            return true;

        foreach ($change_collection as $i => $listentity) {
            
            if (!empty($listentity['todrop'])) {

                $entityName = strtolower(get_class($listentity['todrop'][0]));
                $objectarray = (array) $listentity['todrop'][0];
                $arrayvalues = array_values($objectarray);
                foreach ($arrayvalues as $value) {
                    
                        foreach ($listentity['todrop'] as $entity) {
                            if ($this->tableExists($entityName . '_' . $this->objectName)) {
                                $entityTable = $entityName . "_" . $this->objectName;
                            } elseif ($this->tableExists($this->objectName . "_" . $entityName)) {
                                $entityTable = $this->objectName . "_" . $entityName;
                            } else {
                                $entityTable = $entityName;
                            }

                            $sql .= "delete from " . $entityTable . "  where " . $entityName . "_id = " . $entity->getId() . " and " . $this->objectName . "_id = $id; ";
                        }
                        
                }
            } else {
                $success = TRUE;
            }
        }
        
        if ($sql != "") {

            $query = $this->link->prepare($sql);
            $success = $query->execute() or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql));
        }
        return $success;
    }

    private function dynamicInstance($entity_name) {
        /**
         * on instantie la class $entityTable trop cool
         */
        $reflect = new ReflectionClass($entity_name);
        $object = $reflect->newInstance();
        $list_entity_link = [];
        foreach ((array) $object as $value) {
            if (is_object($value)) {
                $list_entity_link[] = $value;
            }
        }

        return ['object' => $object, "list_entity_link" => $list_entity_link];
    }

    public function belongto($entity, $relation) {

        if (is_object($relation)) {
            if ($relation->getId())
                return $relation->__show();
            else {
                $obarray = (array) $entity;
                $relationname = get_class($relation);
                if (isset($obarray[$relationname . "_id"]))
                    $id = $obarray[$relationname . "_id"];
                else {
                    return $relation;
                }
            }
        } elseif (!is_object($relation)) {

            $obarray = (array) $entity;
            if (isset($obarray[$relation . "_id"]))
                $id = $obarray[$relation . "_id"];
            elseif (isset($obarray[$relation]))
                $id = $obarray[$relation]->getId();
            else {
                foreach ($obarray as $obkey => $value) {
                    $key = str_replace(get_class($entity), '', $obkey);
                    $key = str_replace('*', '', $key);

                    if (is_object($value) && strtolower(get_class($value)) == $relation) {

                        $id = $obarray[$obkey]->getId();
                        break;
                    }
                }
            }

            $reflection = new ReflectionClass($relation);
            $relation = $reflection->newInstance();
        }

        $qb = new QueryBuilder($relation);
        return $qb->select()->where("id", "=", $id)->__getOneRow();
    }

    public function hasmany($entity, $collection) {

        $objectName = strtolower(get_class($entity));
        $collectionName = strtolower(get_class($collection));

        if ($this->tableExists($collectionName . '_' . $objectName)) {

            $entityTable = $collectionName . '_' . $objectName;
        } elseif ($this->tableExists($objectName . "_" . $collectionName)) {

            $entityTable = $objectName . "_" . $collectionName;
        } elseif ($this->tableExists($collectionName)) {

            $entityTable = $collectionName;
            $tableinstance = ucfirst($entityTable);

            $qb = new QueryBuilder($collection);
            return $qb->select()
                            ->where($entity)
                            ->__getAll();
        }

        $tableinstance = ucfirst($entityTable);

        $qb = new QueryBuilder($collection);
        return $qb->select()
                        ->where($collectionName . ".id")
                        ->in(
                                $qb->addselect($collectionName . "_id", new $tableinstance)
                                ->where($entity)
                                ->close()
                        )
                        ->__getAll();
    }

    /**
     * createDbal
     * persiste les entités en base de données.
     * 
     * @param \stdClass $object
     * @return int l'id de l'entité persisté
     */
    public function insertserialiseDbal($object, $listentity) {
        if ($object)
            $this->instanciateVariable($object);

        foreach ($listentity as $entity) {
            $objectarray = (array) $entity;
            $objectvalue = array_values($objectarray);
            $rowvalue = [];
            foreach ($objectarray as $key => $value) {
                if (is_object($value) and get_class($value) != 'DateTime')
                    $rowvalue[] = $value->getId();
                elseif (is_object($value) and get_class($value) == 'DateTime') {
                    //$rowvalue[] = $value->getDate();
                    //echo "entre la";
                    $date = array_values((array) $value);
                    $rowvalue[] = $date[0];
                } else
                    $rowvalue[] = $value;
            }

            $finalvalue[] = "(''" . implode(",", $rowvalue) . ")";
        }
        $parameterQuery = 'id';

        for ($i = 1; $i < $this->nbVar; $i++) {
            $parameterQuery .= ',' . $this->objectVar[$i];
        }

        $sql = "insert into " . $this->table . " (" . $parameterQuery . ")  values ";
        //$sql = "insert into ".$this->objectName." value ";
        $sql .= implode(",", $finalvalue) . ';';
        //die(var_dump($sql));
        return $sql;
    }

    /**
     * createDbal
     * persiste les entités en base de données.
     * 
     * @param \stdClass $object
     * @return int l'id de l'entité persisté
     */
    public function updateserialiseDbal($table, $var, $arrayvalues) {

        if (is_object($table))
            $table = strtolower(get_class($table));

        $ids = array_keys($arrayvalues);
        $sql = "";
        if (count($arrayvalues) == 1) {
            $sql = " update " . $table . " set $var = '" . $arrayvalues[$ids[0]] . "' WHERE id = " . $ids[0] . "; ";
        } else {
            $parameterQuery = "";
            foreach ($arrayvalues as $key => $value) {
                //$parameterQuery .= " WHEN $key THEN $value ";
                $sql .= " update " . $table . " set $var = '" . $value . "' WHERE id = " . $key . "; ";
            }
        }

        return $sql;
    }

    /**
     * createDbal
     * persiste les entités en base de données.
     * 
     * @param \stdClass $object
     * @return int l'id de l'entité persisté
     */
    public function deleteserialiseDbal($object, $listid) {
        if ($object)
            $this->instanciateVariable($object);

        $sql = "DELETE from " . $this->table . " WHERE id IN (" . implode(",", $listid) . ")";

        return $sql;
    }

    /**
     * createDbal
     * persiste les entités en base de données.
     * 
     * @param \stdClass $object
     * @return int l'id de l'entité persisté
     */
    public function executeDbal($sql, $values = [], $action = 0) {

        $query = $this->link->prepare($sql);
        $return = $query->execute($values) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql, $values));

        if ($action == 0) {
            // nothing
        } elseif ($action == 1) {
            $req = $this->link->prepare("select @@IDENTITY as id");
            $req->execute();
            $id = $req->fetch() or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql));
            $return = $id['id'];
        } elseif ($action == 2) {
            $return = $query->fetchAll() or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql));
        } elseif ($action == 3) {
            $return = $query->fetchObject($this->objectName) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql));
        }

        return $return;
    }

    /**
     * createDbal
     * persiste les entités en base de données.
     * 
     * @param \stdClass $object
     * @return int l'id de l'entité persisté
     */
    public function createDbal($object = null) {
        if ($object)
            $this->instanciateVariable($object);

        $values = [];
        $parameterQuery = '?';

        for ($i = 1; $i < $this->nbVar; $i++) {
            $parameterQuery .= ',?';
        }

        $sql = "insert into `" . $this->table . "` (" . strtolower(implode(',', $this->objectVar)) . ") values (" . strtolower($parameterQuery) . ")";

        foreach ($this->objectValue as $value) {
            $values[] = $value;
        }

        $id = $this->executeDbal($sql, $values, 1);

        if (isset($this->objectCollection) && is_array($this->objectCollection) && !empty($this->objectCollection)) {

            $this->manyToManyAdd($id, false, null);
        }

        $this->object->setId($id);

        return $this->object->getId();
    }

    /**
     * updateDbal
     * met à jour l'entité passé en parametre. et celon la valeur de $change_collection, la collection d'objet de l'entité en question.
     * 
     * @param \stdClass $object l'entité a persister
     * @param array $change_collection Autorise la modification de la collection d'objet en bd true par defaut
     * @return \stdClass
     */
    public function updateDbal($object = null) {

        global $_ENTITY_COLLECTION;

        if ($object):
            $this->instanciateVariable($object);
        endif;

        $this->update = true;
        $parameterQuery = $this->objectVar[1] . '=?';
        for ($i = 2; $i < $this->nbVar; $i++) {
            $parameterQuery .= ',' . $this->objectVar[$i] . '=?';
        }
        $values = $this->objectValue;
        array_splice($values, 0, 1);
        $values[] = $this->objectValue[0];

        $sql = "update `" . $this->table . "` set " . strtolower($parameterQuery) . " where id = ? ";

        $query = $this->link->prepare($sql);

        $result = $query->execute($values) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql));

        $this->manyToManyDelete($this->objectValue[0], $_ENTITY_COLLECTION);

        if (isset($this->objectCollection) && is_array($this->objectCollection) && !empty($this->objectCollection)) {

            $this->manyToManyAdd($object->getId(), false, null);
        }

        return $result;
    }

    /**
     * findAllDbal
     * returne toutes les occurences de l'entite en bd
     * 
     * @return array
     */
    public function findAllDbal($critere = "") {
        $sql = 'select * from `' . $this->table . '` ' . $critere;
        $query = $this->link->prepare($sql);
        $query->execute();
        $flowBD = $query->fetchAll(PDO::FETCH_CLASS, $this->objectName);

        return $flowBD;
    }

    public function findAllDbalBaseEntity($critere = "") {
        $sql = 'select * from `' . $this->table . '`' . $critere;
        $query = $this->link->prepare($sql);
        $query->execute();
        $flowBD = $query->fetchAll(PDO::FETCH_CLASS, $this->objectName);

        return $flowBD;
    }

    public function findAllDbalEntireEntity($list = false, $object = null) {

        if ($object):
            $this->instanciateVariable($object);
        endif;

        $sql = 'select * from `' . $this->table . '`';
        if (!empty($this->entity_link_list)) {
            foreach ($this->entity_link_list as $entity_link) {
                $sql .= " left join `" . strtolower(get_class($entity_link)) . "` on " . strtolower(get_class($entity_link)) . ".id = " . $this->table . "." . strtolower(get_class($entity_link)) . "_id";
            }
        }

        return $this->__findAll($sql, [], false, true);
    }

    public function findByIdDbal($object = null, $recursif = true, $collection = false) {

        if ($object):
            $this->instanciateVariable($object);
        endif;

        $sql = 'select * from `' . $this->table . '`';
        if (!empty($this->entity_link_list)) {
            foreach ($this->entity_link_list as $entity_link) {
                $sql .= " left join `" . strtolower(get_class($entity_link)) . "` on " . strtolower(get_class($entity_link)) . ".id = " . $this->table . "." . strtolower(get_class($entity_link)) . "_id";
            }
        }
//        var_dump( $this->objectVar);
        $sql .= ' where ' . $this->table . '.' . $this->objectVar[0] . ' = ? ';

        return $this->__findOne($sql, array($this->objectValue[0]), $collection, $recursif);
    }

    public function deleteDbal($object = null) {

        if ($object):
            $this->instanciateVariable($object);
        endif;

        $sql = "delete from " . $this->table . " where " . $this->objectVar[0] . " = ?";
        $query = $this->link->prepare($sql);
        $retour = $query->execute(array($this->objectValue[0])) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo()));

        return $retour;
    }

    protected function __count($sql, $values = []) {

        $query = $this->link->prepare($sql);
        $query->execute($values) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $sql, $query->errorInfo()));

        return $query->fetchColumn();
    }

    /**
     * Return the row of the database map with the object.
     * 
     * @param String $sql
     * @param Array $values
     * @return Object
     */
    protected function __findOneRow($sql, $values = []) {

        $req = $this->link->prepare($sql);
        $req->execute($values) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $sql, $req->errorInfo()));

        $flowBD = $req->fetchObject($this->objectName);
        if (!$flowBD) {
            return new $this->objectName;
        }
        $flowBD->dvfetched = true;

        return $flowBD;
    }

    /**
     * return entire entity with all linked one
     * 
     * @param type $sql
     * @param type $values
     * @param type $collection
     * @param type $recursif
     * @return type
     */
    protected function __findOne($sql, $values = [], $collection = false, $recursif = true) {

        $req = $this->link->prepare($sql);
        $req->execute($values) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $sql, $req->errorInfo()));

        $arrayReturn = $this->listeEntity;

        if (empty($this->entity_link_list) and empty($this->objectCollection))
            $flowBD = $req->fetchObject($this->objectName);
        elseif ($arrayReturn)
            $flowBD = $this->join($req->fetch(PDO::FETCH_NAMED), $this->object, true, $recursif);
        else
            $flowBD = $this->join($req->fetch(PDO::FETCH_NAMED), $this->object, $collection, $recursif);

        if (!$flowBD)
            $flowBD = $this->object;

        $flowBD->dvfetched = true;

        return $flowBD;
    }

    /**
     * Return array of base entity
     * 
     * @param type $sql
     * @param type $values
     * @return type
     */
    protected function __findAllRow($sql, $values = []) {

        $query = $this->link->prepare($sql);
        $query->execute($values) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql));

        $flowBD = $query->fetchAll(PDO::FETCH_CLASS, $this->objectName);

        return $flowBD;
    }

    /**
     * Return array of entire entity
     * 
     * @param type $sql
     * @param type $values
     * @param type $collection
     * @param type $recursif
     * @return array
     */
    protected function __findAll($sql, $values = [], $collection = false, $recursif = false) {

        $query = $this->link->prepare($sql);
        $query->execute($values) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql));

        if (empty($this->entity_link_list))
            $retour = $query->fetchAll(PDO::FETCH_CLASS, $this->objectName);
        elseif ($arraybd = $query->fetchAll(PDO::FETCH_NAMED)) {
            foreach ($arraybd as $row)
                $liste[] = $this->join($row, $this->object, $collection, $recursif);
            $retour = $liste;
        } else
            $retour = array();

//            $this->ResetObject();
        return $retour;
    }

    private function inarray4($flowvalue) {
        $return = null;
        for ($i = 0; $i < count($flowvalue); $i++) {
            if (isset($flowvalue[$i])) {
                $return = $flowvalue[$i];
                unset($flowvalue[$i]);
                break;
            }
        }
        return $return;
    }

    /**
     * orm v_3.0
     * utilisé pour les findById()
     * 
     * methode recurcivi qui permet de recreer les entités imbriques dans l'entité courantes
     * prend en parametre un tableau qui est le résultat d'une requete avec jointure et retourne
     * l'entité souhaité.
     * la version actuelle ne peut traiter que la premiere couche d'imbrication mais c'est deja un bon debut
     * 
     * @param type $flowBD
     * @param type $object
     * @param type $entity_link_list
     * @param type $arrayReturn
     * @param type $list
     * @param type $recursif
     * 
     * @return type
     */
    private function orm($flowBD, $object, $imbricateindex = 0, $recursif = true, $collection = false) {

        $object_array = (array) $object;

        foreach ($object_array as $key => $value) {
//                    $imbricateindex = 0;

            $k = str_replace(get_class($object), '', $key);
            $k = str_replace('*', '', $k);
            $k2 = substr($k, 2);

            foreach ($flowBD as $key2 => $value2) {

                if (is_object($value)) {

                    if (strtolower(get_class($value)) . '_id' == $key2) {

                        if (is_array($flowBD[$key2])) {

                            $imbricateindex++;

                            $value->setId($this->inarray4($flowBD[$key2]));

                            if ($recursif)
                                $object_array[$key] = $this->findByIdDbal($value);
                            else
                                $object_array[$key] = $value;
                        }else {

                            $value->setId($flowBD[$key2]);
                            if ($recursif)
                                $object_array[$key] = $this->findByIdDbal($value);
                            else
                                $object_array[$key] = $value;
                        }


                        $this->instanciateVariable($object);
                        break;
                    }
                }

                elseif (is_array($value)) {
                    if($collection)
                        $object_array[$key] = $object->__hasmany(strtolower(get_class($value[0])));
                    else
                        $object_array[$key] = $value;
//                    $object_array[$key] = [];
                    
//                                $object_array[$key] = $this->manyToManySelect($object->getId(), strtolower(get_class($object)), $value[0]);

                    break;
                } else {
                    if ($k2 == $key2) {
                        if (is_array($flowBD[$key2])) {
                            $object_array[$key] = $flowBD[$key2][0];
                        } else {
                            $object_array[$key] = $flowBD[$key2];
                        }
                        break;
                    }
                }
            }
        }

        return $object_array;
    }

    /**
     * 
     * @param type $flowBD les données de la bd extrait par PDO avec le parametre PDO::FETCH_NAMED
     * @param type $object the instance of the entity
     * @param Boolean $collection 
     * @param Boolean $recursif weither or not the requeste should go deeper in finding entity relation.
     * @return type
     */
    private function join($flowBD, $object, $collection = false, $recursif = true) {

        if (!is_array($flowBD)) {

            return null;
        }

        $object_array = $this->orm($flowBD, $object, 0, $recursif, $collection);

        return Bugmanager::cast((object) $object_array, get_class($object));
    }

    /**
     * methode qui initialise les variables d'instance. elle est notament utilisé pour permetre de persister
     * des entités en utilisant directement les methodes du dbal sans passé par le dao comme ça se faisait
     * avant.
     * 
     * @param type $object
     */
    protected function instanciateVariable($object) {
        global $em;

        $this->entity_link_list = [];
        $this->listeEntity = [];
        $this->objectCollection = [];
        $this->select = false;

        if (is_object($object)) {

            $classmetadata = $em->getClassMetadata("\\" . get_class($object));
            $this->instanceid = $object->getId();
            $objecarray = (array) $object;
            if (isset($objecarray["dvfetched"])) {
                unset($objecarray["dvfetched"]);
            }

            $this->object = $object;
            $this->objectName = strtolower(get_class($object));

            $this->objectValue = array_values($objecarray);
            $heritage = false;
            $i = 0;
            $j = 0;
            $k = 0;
            $this->table = strtolower($this->objectName);

            $fieldname = array_keys($classmetadata->fieldNames);
            $association = array_keys($classmetadata->associationMappings);
//                if(!$this->tableExists($this->table)){
//                    
//                    if($metadata = $this->getDoctrineMetadata($this->objectName)){
//                        $this->table = $metadata->table['name'];
//                    }
//                    
//                }

            foreach ($objecarray as $obkey => $value) {
                // gere les attributs hérités en visibilité protected

//                $key = str_replace(get_class($object), '', $obkey);
//                $key = str_replace('*', '', $key);
                if (is_object($value)) {
                    $classname = get_class($value);
                    if (isset($association[$k]) && $classname != 'DateTime' && $association[$k] != $classname) {
                        
                        $this->objectVar[] = get_class($value) . '_id';
                        $heritage = true;
//                        $class = get_class($value);
                        $this->entity_link_list[] = $value;
                        $this->objectValue[$i] = $value->getId();
                        $k++;
                    } elseif ($classname == 'DateTime') {
                        //$date = new DateTime();
                        $this->objectVar[] = $fieldname[$j];
//                        $this->objectVar[] = substr($key, 2);
                        $date = array_values((array) $value);
                        $this->objectValue[$i] = $date[0];
                        $j++;
                    }
                } else if (is_array($value)) {

                    $this->objectCollection[] = $this->objectValue[$i];
//                    $this->objectCollection[] = array_pull($this->objectValue, $i);
                    unset($this->objectValue[$i]);
                } else {
//                    $this->objectVar[] = substr($key, 2);
                    $this->objectVar[] = $fieldname[$j];
                    $j++;
                }

                $i++;
            }

            if ($heritage && substr($this->objectVar[count($this->objectVar) - 1], strlen($classname)) == 'id') {

                unset($this->objectVar[count($this->objectVar) - 1]);
                unset($this->objectValue[count($this->objectValue) - 1]);
            }

            $this->nbVar = count((array) $this->objectVar);
            $this->en = $this->nbVar;
//                $this->inbricate();
            $this->en = $this->en - 1;
        }
    }

    /**
     * Réinitialise l'instance du DBAL
     * c'est notament utile pour la persistance des objets avec des attributs null dans le cas d'une relation 1:n
     */
    private function ResetObject() {
        $link = $this->link;
        foreach ($this as $key => $value) {
            unset($this->$key);
        }
        $this->link = $link;
    }

    /**
     * Check if a table exists in the current database.
     *
     * @param PDO $pdo PDO instance connected to a database.
     * @param string $table Table to search for.
     * @return bool TRUE if table exists, FALSE if no table found.
     */
    protected function tableExists($table, $pdo = null) {

        // Try a select statement against the table
        // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
        try {
            $query = $this->link->query("SELECT 1 FROM " . strtolower($table) . " LIMIT 1");
        } catch (Exception $e) {
            // We got an exception == table not found
            return FALSE;
        }
        // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
        if ($query) {
            $result = $query->fetch();
            return true;
        } else {
            return false;
        }
    }

}