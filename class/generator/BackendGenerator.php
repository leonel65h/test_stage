<?php

class BackendGenerator {

    public function entityGenerator($entity) {

        $name = strtolower($entity->name);

        $antislash = str_replace(" ", "", " \ ");

        unset($entity->attribut[0]);

        $fichier = fopen('Entity/' . ucfirst($name) . '.php', 'w');

        fputs($fichier, "<?php 
    /**
     * @Entity @Table(name=\"" . $name . "\")
     * */
    class " . ucfirst($name) . " extends \Model implements JsonSerializable{\n");
        $method = "";
        $construteur = "
        public function __construct($" . "id = null){
            
                if( $" . "id ) { $" . "this->id = $" . "id; }   
                          ";
        $attrib = "";

        if (!empty($entity->relation)) {

            $construteur .= "";
            foreach ($entity->relation as $relation) {

                if ($relation->cardinality == 'manyToMany') {

                    $manytomany = [
                        "name" => $name."_".$relation->entity,
                        "ref" => null,
                        "attribut" => [],
                        "relation" => [
                            [
                            "entity" => $relation->entity,
                            "cardinality" => "manyToOne",
                            "nullable" => "not",
                            "ondelete" => "cascade",
                            "onupdate" => "cascade"
                            ],
                            [
                            "entity" => $name,
                            "cardinality" => "manyToOne",
                            "nullable" => "not",
                            "ondelete" => "cascade",
                            "onupdate" => "cascade"
                            ],
                        ]
                    ];

                    $construteur .= "\n\t\t\t$" . "this->" . $relation->entity . " = EntityCollection::entity_collection('" . $relation->entity . "');";

                    $attrib .= "
        /**
         * " . $relation->cardinality . "
         * @var " . $antislash . ucfirst($relation->entity) . "
         */
        public $" . $relation->entity . ";\n";

                $method .= "
        /**
         *  " . $relation->cardinality . "
         *	@return " . $antislash . ucfirst($relation->entity) . "
         */
        function get" . ucfirst($relation->entity) . "() {
            return $" . "this->" . $relation->entity . ";
        }";
                    $method .= "
        function add" . ucfirst($relation->entity) . "(" . $antislash . ucfirst($relation->entity) . " $" . $relation->entity . "){
            $" . "this->" . $relation->entity . "[] = $" . $relation->entity . ";
        }

        function drop" . ucfirst($relation->entity) . "Collection() {
                $" . "this->" . $relation->entity . " = EntityCollection::entity_collection('" . $relation->entity . "');
        }

                        ";
                } elseif ($relation->cardinality == 'oneToOne' or $relation->nullable == 'DEFAULT') {

                    $construteur .= "\n\t$" . "this->" . $relation->entity . " = new " . ucfirst($relation->entity) . "();";

                    $attrib .= "
        /**
         * @" . ucfirst($relation->cardinality) . "(targetEntity=\"" . $antislash . ucfirst($relation->entity) . "\")
         * , inversedBy=\"reporter\"
         * @var " . $antislash . ucfirst($relation->entity) . "
         */
        public $" . $relation->entity . ";\n";

                $method .= "
        /**
         *  " . $relation->cardinality . "
         *	@return " . $antislash . ucfirst($relation->entity) . "
         */
        function get" . ucfirst($relation->entity) . "() {
            $" . "this->". $relation->entity . " = $" . "this->__belongto(\"" . $relation->entity . "\");
            return $" . "this->" . $relation->entity . ";
        }";
                    $method .= "
        function set" . ucfirst($relation->entity) . "(" . $antislash . ucfirst($relation->entity) . " $" . $relation->entity . " = null) {
            $" . "this->" . $relation->entity . " = $" . $relation->entity . ";
        }
                        ";
                } else {

                    $construteur .= "\n\t$" . "this->" . $relation->entity . " = new " . ucfirst($relation->entity) . "();";

                    $attrib .= "
        /**
         * @" . ucfirst($relation->cardinality) . "(targetEntity=\"" . $antislash . ucfirst($relation->entity) . "\")
         * , inversedBy=\"reporter\"
         * @var " . $antislash . ucfirst($relation->entity) . "
         */
        public $" . $relation->entity . ";\n";

                $method .= "
        /**
         *  " . $relation->cardinality . "
         *	@return " . $antislash . ucfirst($relation->entity) . "
         */
        function get" . ucfirst($relation->entity) . "() {
            $" . "this->". $relation->entity . " = $" . "this->__belongto(\"$" . "this->" . $relation->entity . "\");
            return $" . "this->" . $relation->entity . ";
        }";
                    $method .= "
        function set" . ucfirst($relation->entity) . "(" . $antislash . ucfirst($relation->entity) . " $" . $relation->entity . ") {
            $" . "this->" . $relation->entity . " = $" . $relation->entity . ";
        }
                        ";
                }
            }
        }

        $construteur .= "\n}\n";

        $construt = "
        /**
         * @Id @GeneratedValue @Column(type=\"integer\")
         * @var int
         * */
        protected $" . "id;";
        $otherattrib = false;

//        if(isset($entity->attribut[1])){
//        var_dump($entity->attribut);
//        die;

        foreach ($entity->attribut as $attribut) {

            $length = "";
            $nullable = "";

            if ($attribut->datatype == "string") {
                $length = ', length=' . $attribut->size . '';
            }

            if ($attribut->nullable == 'default') {
                $nullable = ", nullable=true";
            }
            $construt .= "
        /**
         * @Column(name=\"" . $attribut->name . "\", type=\"" . $attribut->datatype . "\" $length $nullable)
         * @var " . $attribut->datatype . "
         **/
        private $" . $attribut->name . ";";
        }
        $otherattrib = true;
//        }

        $construt .= " 
        " . $attrib . "

        " . $construteur . "
        public function getId() {
            return $" . "this->id;
        }";
        if ($otherattrib) {
            foreach ($entity->attribut as $attribut) {

                if (in_array($attribut->formtype, ['document', 'image', 'music', 'video'])) {
                    $construt .= "
					
        public function show" . ucfirst($attribut->name) . "() {
            return UploadFile::show($" . "this->" . $attribut->name . ", '" . $name . "');
        }
        
        public function get" . ucfirst($attribut->name) . "() {
            return $" . "this->" . $attribut->name . ";
        }

        public function upload" . ucfirst($attribut->name) . "($" . $attribut->name . ") {
            
            $" . "path = '" . $name . "';
            UploadFile::deleteFile($" . "this->" . $attribut->name . ", $" . "path);
            ";
                    if ($attribut->formtype == 'document') {
                        $construt .= "	
            $" . "result = UploadFile::document($" . "path, $" . $attribut->name . ");";
                    } elseif ($attribut->formtype == 'music') {
                        $construt .= "	
            $" . "result = UploadFile::music($" . "path, $" . $attribut->name . ");";
                    } elseif ($attribut->formtype == 'video') {
                        $construt .= "	
            $" . "result = UploadFile::video($" . "path, $" . $attribut->name . ");";
                    } elseif ($attribut->formtype == 'image') {
                        $construt .= "	
            $" . "result = UploadFile::image($" . "path, $" . $attribut->name . ");";
                    }
                    $construt .= "
            if($" . "result['success']){
                $" . "this->" . $attribut->name . " = $" . "result['file']['hashname'];
            }

            return $" . "result;
        }";
                } elseif (in_array($attribut->formtype, ['date', 'datepicker'])) {
                    $construt .= "

        public function get" . ucfirst($attribut->name) . "() {
                if(is_object($" . "this->" . $attribut->name . "))
                        return $" . "this->" . $attribut->name . ";
                else
                        return new DateTime($" . "this->" . $attribut->name . ");
        }

        public function set" . ucfirst($attribut->name) . "($" . $attribut->name . ") {
                    if(is_object($" . $attribut->name . "))
                            $" . "this->" . $attribut->name . " = $" . $attribut->name . ";
                    else
                            $" . "this->" . $attribut->name . " = new DateTime($" . $attribut->name . ");
        }";
                } elseif ($attribut->formtype == 'liste') {
                    $construt .= "
        public function get" . ucfirst($attribut->name) . "List() {
            return $" . "this->" . $attribut->name . ";
        }
		
        public function get" . ucfirst($attribut->name) . "() {
            return $" . "this->" . $attribut->name . ";
        }

        public function set" . ucfirst($attribut->name) . "($" . $attribut->name . ") {
            $" . "this->" . $attribut->name . " = $" . $attribut->name . ";
        }
        ";
                } else {
                    $construt .= "
        public function get" . ucfirst($attribut->name) . "() {
            return $" . "this->" . $attribut->name . ";
        }

        public function set" . ucfirst($attribut->name) . "($" . $attribut->name . ") {
            $" . "this->" . $attribut->name . " = $" . $attribut->name . ";
        }
        ";
                }
            }
        }
        $construt .= $method . "
        
        public function jsonSerialize() {
                return [
                        'id' => $" . "this->id,";
        foreach ($entity->attribut as $attribut) {
            $construt .= "
                                '" . $attribut->name . "' => $" . "this->" . $attribut->name . ",";
        }
        if (!empty($entity->relation)) {
            foreach ($entity->relation as $relation) {
                $construt .= "
                                '" . $relation->entity . "' => $" . "this->" . $relation->entity . ",";
            }
        }
        $construt .= "
                ];
        }
        ";

        fputs($fichier, $construt);
        fputs($fichier, "\n}\n");

        fclose($fichier);
        
        
        if(isset($manytomany)){
            $entitycollection = (object) $manytomany;
            $entitycollection->relation[0] = (object) $entitycollection->relation[0];
            $entitycollection->relation[1] = (object) $entitycollection->relation[1];
            
            $this->entityGenerator($entitycollection);
        }
        
    }

    /* 	CREATION DU CONTROLLER 	 */

    public function controllerGenerator($entity) {
        $name = strtolower($entity->name);

        $classController = fopen('Controller/' . ucfirst($name) . 'Controller.php', 'w');

        $contenu = "<?php \n
    class " . ucfirst($name) . "Controller extends Controller{

            /**
             * retourne l'instance de l'entité ou un json pour les requete asynchrone (ajax)
             *
             * @param type $" . "id
             * @return \Array
             */
            public  function showAction($" . "id){

                    $" . $name . " = " . ucfirst($name) . "::find($" . "id);

                    return array( 'success' => true, 
                                    '" . $name . "' => $" . $name . ",
                                    'detail' => 'detail de l\'action.');

            }

            /**
             * Data for creation form
             * @Sequences: controller - genesis - ressource/view/form
             * @return \Array
            */
            public function __newAction(){

                    return 	array(	'success' => true, // pour le restservice
                                    '" . $name . "' => new " . ucfirst($name) . "(),
                                    'action_form' => 'create', // pour le web service
                                    'detail' => ''); //Detail de l'action ou message d'erreur ou de succes

            }

            /**
             * Action on creation form
             * @Sequences: controller - genesis - ressource/view/form
             * @return \Array
            */
            public function createAction(){
                    extract($" . "_POST);
                    $" . "this->err = array();

                    $" . $name . " = $" . "this->form_generat(new " . ucfirst($name) . "(), $" . $name . "_form);\n ";
        // gestion des relations many to many dans le controller
        $mtm = [];
        $mtmedit = [];
        $iter = 0;
        if (!empty($entity->relation)) {
            //relation sera l'entité 
            foreach ($entity->relation as $relation) {

                if ($relation->cardinality == "oneToOne") {
                    $contenu .= "
                        $" . $relation->entity . "Ctrl = new " . ucfirst($relation->entity) . "Controller();
                        extract($" . $relation->entity . "Ctrl->createAction());
                        $" . $name . "->set" . ucfirst($relation->entity) . "($" . $relation->entity . "); ";
                } elseif (false) {//$relation->cardinality == "manyToMany" &&
                    $mtm[] = "
                        if (!empty($" . "id_" . $relation->entity . ")){
                                foreach($" . "id_" . $relation->entity . " as $" . "id){
                                        $" . $relation->entity . "Dao = new " . ucfirst($relation->entity) . "DAO();
                                        $" . $name . "->add" . ucfirst($relation->entity) . "($" . $relation->entity . "Dao->findById($" . "id));
                                }
                        }";

                    $mtmedit[] = "
                        if (!empty($" . "id_" . $relation->entity . ") && 
                                        $" . "update_collection = $" . "this->updateEntityCollection($" . "_GET['collection'], $" . "id_" . $relation->entity . ")){
                                $" . $name . "->remove" . ucfirst($relation->entity) . "();
                                foreach($" . "id_" . $relation->entity . " as $" . "id){
                                        $" . $relation->entity . "Dao = new " . ucfirst($relation->entity) . "DAO();
                                        $" . $name . "->add" . ucfirst($relation->entity) . "($" . $relation->entity . "Dao->findById($" . "id));
                                }
                        }else
                                $" . $name . "->drop" . ucfirst($relation->entity) . "Collection();
                                $" . "update_collection = true;\n";
                    $iter++;
                }
            }
        }
        $contenu .= "\n" . implode($mtm, "\n");
        $otherattrib = false;
        if (isset($entity->attribut[1])) {
            $otherattrib = true;
            foreach ($entity->attribut as $attribut) {
//			for($i = 1; $i < count($entity->attribut); $i++){
                if (in_array($attribut->formtype, ['document', 'music', 'video', 'image']))
                    $contenu .= " 
                        UploadFile::__FILE_SANITIZE($" . $name . ", '" . $attribut->name . "');
                        ";
            }
        }

        $contenu .= "
                    if ( $" . "id = $" . $name . "->__insert()) {
                            return 	array(	'success' => true, // pour le restservice
                                            '" . $name . "' => $" . $name . ",
                                            'redirect' => 'index', // pour le web service
                                            'detail' => ''); //Detail de l'action ou message d'erreur ou de succes
                    } else {
                            return 	array(	'success' => false, // pour le restservice
                                            '" . $name . "' => $" . $name . ",
                                            'action_form' => 'create', // pour le web service
                                            'detail' => 'error data not persisted'); //Detail de l'action ou message d'erreur ou de succes
                    }

            }

            /**
             * Data for edit form
             * @Sequences: controller - genesis - ressource/view/form
             * @param type $" . "id
             * @return \Array
                                         */ 
            public function __editAction($" . "id){

                   $" . $name . " = " . ucfirst($name) . "::find($" . "id);

                    return array('success' => true, // pour le restservice
                                    '" . $name . "' => $" . $name . ",
                                    'action_form' => 'update&id='.$" . "id, // pour le web service
                                    'detail' => ''); //Detail de l'action ou message d'erreur ou de succes

            }

            /**
             * Action on edit form
             * @Sequences: controller - genesis - ressource/view/index
             * @param type $" . "id
             * @return \Array
            */
            public function updateAction($" . "id){
                    extract($" . "_POST);
                        
                    $" . $name . " = $" . "this->form_generat(new " . ucfirst($name) . "($" . "id), $" . $name . "_form);

                    "; //.implode($mtmedit, "\n")
        if ($otherattrib):
            foreach ($entity->attribut as $attribut) {
//                            for($i = 1; $i < count($entity->attribut); $i++){
                if (in_array($attribut->formtype, ['document', 'music', 'video', 'image']))
                    $contenu .= " 
                        UploadFile::__FILE_SANITIZE($" . $name . ", '" . $attribut->name . "');\n";
            }
        endif;
        $contenu .= "
                    if ($" . $name . "->__update()) {
                            return 	array(	'success' => true, // pour le restservice
                                            '" . $name . "' => $" . $name . ",
                                            'redirect' => 'index', // pour le web service
                                            'detail' => ''); //Detail de l'action ou message d'erreur ou de succes
                    } else {
                            return 	array(	'success' => false, // pour le restservice
                                            '" . $name . "' => $" . $name . ",
                                            'action_form' => 'update&id='.$" . "id, // pour le web service
                                            'detail' => 'error data not updated'); //Detail de l'action ou message d'erreur ou de succes
                    }
            }

            /**
             * 
             *
             * @param type $" . "id
             * @return \Array
             */
            public function listAction($" . "next = 1, $" . "per_page = 10){

                                                $" . "lazyloading = $" . "this->lazyloading(new " . ucfirst($name) . "(), $" . "next, $" . "per_page);

                                                return array('success' => true, // pour le restservice
                                                    'lazyloading' => $" . "lazyloading, // pour le web service
                                                    'detail' => '');

                                        }

            public function deleteAction($" . "id){

                    $" . $name . " = " . ucfirst($name) . "::find($" . "id);

			";
        if ($otherattrib):
            foreach ($entity->attribut as $attribut) {
                if (in_array($attribut->formtype, ['document', 'image', 'musique', 'video']))
                    $contenu .= " 
                   $" . $name . "->deleteFile($" . $name . "->get" . ucfirst($attribut->name) . "(), '" . $name . "');";
            }
        endif;
        $contenu .= "
                    if( $" . $name . "->__delete() )
                            return 	array(	'success' => true, // pour le restservice
                                            'redirect' => 'index', // pour le web service
                                            'detail' => ''); //Detail de l'action ou message d'erreur ou de succes
                    else
                            return 	array(	'success' => false, // pour le restservice
                                                                                                                        '" . $name . "' => $" . $name . ",
                                            'detail' => 'Des problèmes sont survenus lors de la suppression de l\'élément.'); //Detail de l'action ou message d'erreur ou de succes
            }

	}\n";
        fputs($classController, $contenu);
        //fputs($classController, "\n}\n");

        fclose($classController);
    }

    /* CREATION OF CORE */

    public function coreGenerator($entity) {
        $name = strtolower($entity->name);

        /* if($name == 'utilisateur')
          return 0; */

        $entitycore = fopen('Core/' . $name . 'Core.json', 'w');
        $contenu = json_encode($entity);
        fputs($entitycore, $contenu);

        fclose($entitycore);
    }

    /* CREATION DU FORM */

    public function formGenerator($entity, $listmodule) {

        $name = strtolower($entity->name);
        $traitement = new Traitement();

        /* if($name == 'utilisateur')
          return 0; */
        $field = '';
        unset($entity->attribut[0]);

        foreach ($entity->attribut as $attribut) {

            $field .= "
            $" . "entitycore->field['" . $attribut->name . "'] = [
                \"label\" => '" . ucfirst($attribut->name) . "', \n";

            if ($attribut->nullable == 'default') {
                $field .= "\t\t\tFH_REQUIRE => false,\n ";
            }

            if ($attribut->formtype == 'text' or $attribut->formtype == 'float') {
                $field .= "\t\t\t\"type\" => FORMTYPE_TEXT, 
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'integer' or $attribut->formtype == 'number') {
                $field .= "\t\t\t\"type\" => FORMTYPE_NUMBER, 
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(),  ";
            } elseif ($attribut->formtype == 'textarea') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'date') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'time') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'datetime') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'datepicker') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'radio') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'email') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'document') {
                $field .= "\t\t\t\"type\" => FORMTYPE_FILE,
                FH_FILETYPE => FILETYPE_" . strtoupper($attribut->formtype) . ",  
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(),
                \"src\" => $" . $name . "->show" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'video') {
                $field .= "\t\t\t\"type\" => FORMTYPE_FILE,
                \"filetype\" => FILETYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(),
                \"src\" => $" . $name . "->show" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'music') {
                $field .= "\"type\" => FORMTYPE_FILE,
                \"filetype\" => FILETYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(),
                \"src\" => $" . $name . "->show" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'image') {
                $field .= "\t\t\t\"type\" => FORMTYPE_FILE,
                \"filetype\" => FILETYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(),
                \"src\" => $" . $name . "->show" . ucfirst($attribut->name) . "(), ";
            } else {
                $field .= "\"type\" => FORMTYPE_TEXT,
                \"value\" => $" . $name . "->get" . ucfirst($attribut->name) . "(), ";
            }

            $field .= "
            ];\n";
        }

        if (!empty($entity->relation)) {
            foreach ($entity->relation as $relation) {

                $entitylink = $traitement->relation($listmodule, $relation->entity);
                $entrel = ucfirst(strtolower($relation->entity));
                $key = 0;
                $enititylinkattrname = "id";
                $entitylink->attribut = (array) $entitylink->attribut;

                if (isset($entitylink->attribut[1])) {
                    $key = 1;
                    $enititylinkattrname = $entitylink->attribut[$key]->name;
                }

                if ($relation->cardinality == 'manyToOne') {
                    $field .= "
                $" . "entitycore->field['" . $relation->entity . "'] = [
                    \"type\" => FORMTYPE_SELECT, 
                    \"value\" => $" . $name . "->get" . ucfirst($relation->entity) . "()->getId(),
                    \"label\" => '" . ucfirst($relation->entity) . "',
                    \"options\" => FormManager::Options_Helper('" . $enititylinkattrname . "', " . ucfirst($relation->entity) . "::allrows()),
                ];\n";
                } elseif ($relation->cardinality == 'oneToOne') {
                    $field .= "
                $" . "entitycore->field['" . $relation->entity . "'] = [
                    \"type\" => FORMTYPE_INJECTION, 
                    FH_REQUIRE => true,
                    \"label\" => '" . ucfirst($relation->entity) . "',
                    \"imbricate\" => " . ucfirst($relation->entity) . "Form::__renderForm($" . $name . "->get" . ucfirst($relation->entity) . "()),
                ];\n";
                } elseif ($relation->cardinality == 'manyToMany') {
                    $field .= "
                $" . "entitycore->field['" . $relation->entity . "'] = [
                    \"type\" => FORMTYPE_CHECKBOX, 
                    \"values\" => FormManager::Options_Helper('" . $enititylinkattrname . "', $" . $name . "->get" . ucfirst($relation->entity) . "()),
                    \"label\" => '" . ucfirst($relation->entity) . "',
                    \"options\" => FormManager::Options_ToCollect_Helper('" . $enititylinkattrname . "', new " . ucfirst($relation->entity) . "(), $" . $name . "->get" . ucfirst($relation->entity) . "()),
                ];\n";
                }
            }
        }

        $contenu = "<?php \n
    class " . ucfirst($name) . "Form extends FormManager{

        public static function formBuilder(\\" . ucfirst($name) . " $" . $name . ", $" . "action = null, $" . "button = false) {
            $" . "entitycore = $" . $name . "->scan_entity_core();
            
            $" . "entitycore->formaction = $" . "action;
            $" . "entitycore->formbutton = $" . "button;
                
            " . $field . "

            return $" . "entitycore;
        }
        
        public static function __renderForm(\\" . ucfirst($name) . " $" . $name . ", $" . "action = null, $" . "button = false) {
            return FormFactory::__renderForm(" . ucfirst($name) . "Form::formBuilder($" . $name . ", $" . "action, $" . "button));
        }
        
    }
    ";
        $entityform = fopen('Form/' . ucfirst($name) . 'Form.php', 'w');
        fputs($entityform, $contenu);

        fclose($entityform);
    }

    /* CREATION DU DAO */

    public function daoGenerator($entity) {
        $name = strtolower($entity->name);

        /* if($name == 'utilisateur')
          return 0; */

        $classDao = fopen('Dao/' . ucfirst($name) . 'DAO.php', 'w');
        $contenu = "<?php \n
	class " . ucfirst($name) . "DAO extends DBAL{
			
		public function __construct() {
			parent::__construct(new " . ucfirst($name) . "());
		}			
		
	}";

        fputs($classDao, $contenu);

        fclose($classDao);
    }

}