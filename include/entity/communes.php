<?php
include('./include/connexion.php');
include('./include/entity/users.php');
include('./include/entity/documents.php');
include('./include/entity/categories.php');
include('./include/entity/docrelation.php');

//arborescence : 
//SDEA/include/connexion.php
//SDEA/include/entity/communes.php
//SDEA/controleur.php
//SDEA/templates/home/home.html.twig
class Asr{
    public $idt_asr;
    public $nom;
    public $cp;

    function __construct($idt_asr = null, $nom = null, $cp = null) {
        $this->idt_asr = $idt_asr !== null ? intval($idt_asr) : null;
        $this->nom = $nom;
        $this->cp = $cp;
    }

    static function readAll(){
        // echo "readAll est appelé";
        // var_dump("readAll est appelé"); 
        $sql = 'SELECT * FROM communes';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute();
        
        // Ajoutez cette ligne ici
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Asr');
        
        $tableau = $query->fetchAll();
        return $tableau;
    }

    static function readOne($idt_asr){
        $sql = 'SELECT * FROM communes WHERE idt_asr = :idt_asr';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt_asr' => $idt_asr]);
        
        // Ajoutez cette ligne ici
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Asr');
        
        $asr = $query->fetch();
        return $asr;
    }

    static function create($nom, $cp){
        $sql = 'INSERT INTO communes (nom, cp) VALUES (:nom, :cp)';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['nom' => $nom, 'cp' => $cp]);
    }

    static function update($idt_asr, $nom, $cp){
        $sql = 'UPDATE communes SET nom = :nom, cp = :cp WHERE idt_asr = :idt_asr';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt_asr' => $idt_asr, 'nom' => $nom, 'cp' => $cp]);
    }

    static function delete($idt_asr){
        $sql = 'DELETE FROM communes WHERE idt_asr = :idt_asr';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt_asr' => $idt_asr]);
    }

    static function readCommuneByDoc($id_doc) {
        $sql = 'SELECT a.* FROM communes a 
                INNER JOIN docrelation dr ON a.idt_asr = dr.idt_asr
                WHERE dr.idt_doc = :idt_doc'; 
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt_doc' => $id_doc]); 
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Asr');
        return $query->fetchAll();
    }

    static function readName($idt_asr){
        $sql = 'SELECT nom FROM communes WHERE idt_asr = :idt_asr';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt_asr' => $idt_asr]);
        return $query->fetchColumn();
    }

    function chargePOST(){
        if(isset($_POST['nom'])){
            $this->nom = htmlspecialchars($_POST['nom']);
        } else {
            $this->nom = '';
        }
    
        if(isset($_POST['cp'])){
            $this->cp = htmlspecialchars($_POST['cp']);
        } else {
            $this->cp = '';
        }
    }

}