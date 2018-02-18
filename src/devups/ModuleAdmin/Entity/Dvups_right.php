<?php 
    
/**
 * @Entity @Table(name="dvups_right")
 * */
    class Dvups_right extends Model implements JsonSerializable{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
        private $id;
    /**
     * @Column(name="name", type="string" , length=255 )
     * @var string
     * */
        private $name;        

        
        public function __construct($id = null){
            
                if( $id ) { $this->id = $id; }   
                          
}

        public function getId() {
            return $this->id;
        }

        public function setId($id) {
            $this->id = $id;
        }
        public function getName() {
            return $this->name;
        }

        public function setName($name) {
            $this->name = $name;
        }
                
        public function jsonSerialize() {
                return [
                                'name' => $this->name,
                ];
        }
        
}
