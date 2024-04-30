<?php

class Documents{

    public $idt_doc;
    public $titre;
    public $link;
    public $type_doc;
    public $date_doc;
    public $label_type_doc;

    function __construct($idt_doc = null, $titre = null, $link = null, $type_doc = null, $date_doc = null) {
        $this->idt_doc = $idt_doc !== null ? intval($idt_doc) : null;
        $this->titre = $titre;
        $this->link = $link;
        $this->type_doc = $type_doc;
        $this->date_doc = $date_doc;
    }

    static function readAll($page = 1, $limit = 10){
        $offset = max(0, ($page - 1) * $limit);
        $sql = 'SELECT d.*, c.label_type_doc FROM documents d 
                LEFT JOIN categories c ON d.type_doc = c.idt 
                LIMIT ' . $offset . ', ' . $limit;
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute();
        
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Documents');
        
        $tableau = $query->fetchAll();
        return $tableau;
    }

    static function readOne($idt_doc){
        $sql = 'SELECT * FROM documents WHERE idt_doc = :idt_doc';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt_doc' => $idt_doc]);
        
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Documents');
        
        $documents = $query->fetch();
        return $documents;
    }

    static function readDocByCommune($idt_asr){
        $sql = 'SELECT documents.* FROM documents
                JOIN docrelation ON documents.idt_doc = docrelation.idt_doc
                JOIN communes ON docrelation.idt_asr = communes.idt_asr
                WHERE communes.idt_asr = :idt_asr';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt_asr' => $idt_asr]);
        
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Documents');
        
        $documents = $query->fetchAll();
        return $documents;
    }

    static function countAll(){
        $sql = 'SELECT COUNT(*) FROM documents';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute();
        
        return $query->fetchColumn();
    }

    static function readByDate($date){
        $pdo = connexion();
    
        if (empty($date)) {
            // Si aucune date n'est fournie, renvoyez tous les documents
            $sql = 'SELECT * FROM documents';
            $query = $pdo->prepare($sql);
            $query->execute();
        } else {
            // Sinon, renvoyez les documents pour la date spécifiée
            $sql = 'SELECT * FROM documents WHERE date_doc = :date_doc';
            $query = $pdo->prepare($sql);
            $query->execute(['date_doc' => $date]);
        }
        
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Documents');
        
        $documents = $query->fetchAll();
        return $documents;
    }

    static function getAvailableDates(){
        $sql = 'SELECT DISTINCT date_doc FROM documents ORDER BY date_doc';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute();
        
        $dates = $query->fetchAll(PDO::FETCH_COLUMN);
        return $dates;
    }

    function chargePOST(){
        if (isset($_POST['idt_doc']) && !empty($_POST['idt_doc'])){
            $this->idt_doc = $_POST['idt_doc'];
        }

        if(isset($_POST['titre'])){
            $this->titre = $_POST['titre'];
        } else {
            $this->titre = '';
        }

        if(isset($_POST['link'])){
            $this->link = $_POST['link'];
        } else {
            $this->link = '';
        }

        if(isset($_POST['type_doc'])){
            $this->type_doc = $_POST['type_doc'];
        } else {
            $this->type_doc = '';
        }

        if(isset($_POST['date_doc'])){
            $this->date_doc = $_POST['date_doc'];
        } else {
            $this->date_doc = '';
        }

    }
}