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
        $sql = 'SELECT d.*, c.label_type_doc FROM documents d 
                LEFT JOIN categories c ON d.type_doc = c.idt';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute();
        
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Documents');
        
        $tableau = $query->fetchAll();
        return $tableau;
    }


    static function getAllTypes(){
        $sql = 'SELECT DISTINCT type_doc FROM documents ORDER BY type_doc';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute();
        // var_dump($query->errorInfo());
        
        $types = $query->fetchAll(PDO::FETCH_COLUMN);
        // var_dump($types);
        return $types;
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

    static function readByDate($year){
        $pdo = connexion();
        var_dump($year);
        
        if (empty($year)) {
            // Si aucune année n'est fournie, renvoyez tous les documents
            $sql = 'SELECT d.*, c.label_type_doc FROM documents d LEFT JOIN categories c ON d.type_doc = c.idt';
            $query = $pdo->prepare($sql);
            $query->execute();
        } else {
            // Sinon, renvoyez les documents pour l'année spécifiée
            $sql = 'SELECT d.*, c.label_type_doc FROM documents d LEFT JOIN categories c ON d.type_doc = c.idt WHERE YEAR(d.date_doc) = :year';
            $query = $pdo->prepare($sql);
            $query->execute(['year' => $year]);
        }
        
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Documents');
        
        $documents = $query->fetchAll();
        return $documents;
    }

    static function getAvailableDates(){
        $sql = 'SELECT DISTINCT YEAR(date_doc) as year FROM documents ORDER BY year DESC';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute();
        
        $years = $query->fetchAll(PDO::FETCH_COLUMN);
        return $years;
    }

    static function isPathInDatabase($link){
        $sql = 'SELECT COUNT(*) FROM documents WHERE link = :link';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['link' => $link]);
        
        // Si le nombre de lignes retournées est supérieur à 0, le chemin est déjà dans la base de données
        return $query->fetchColumn() > 0;
    }

    
    static function create($titre, $link, $type_doc, $date_doc, $idt_doc){
        echo 'create';
        var_dump($titre, $link, $type_doc, $date_doc, $idt_doc); // Vérifiez les valeurs des paramètres
        $sql = 'INSERT INTO documents (idt_doc, titre, link, type_doc, date_doc) VALUES (:idt_doc, :titre, :link, :type_doc, :date_doc)';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $result = $query->execute([
            'idt_doc' => $idt_doc,
            'titre' => $titre,
            'link' => $link,
            'type_doc' => $type_doc,
            'date_doc' => $date_doc
        ]);

        var_dump($result); // Vérifiez le résultat de l'exécution de la requête

        if ($result) {
            // Si la requête a réussi, retournez l'ID du document inséré
            $lastInsertId = $pdo->lastInsertId();
            var_dump($lastInsertId); // Vérifiez l'ID du dernier document inséré
            return $lastInsertId;
        }

        return $result;
    }


    static function update($idt_doc, $titre){
        $sql = 'UPDATE documents SET titre = :titre WHERE idt_doc = :idt_doc';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $result = $query->execute([
            'idt_doc' => $idt_doc,
            'titre' => $titre
        ]);
    
        return $result;
    }

    static function delete($idt_doc){
        $sql = 'DELETE FROM documents WHERE idt_doc = :idt_doc';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $result = $query->execute(['idt_doc' => $idt_doc]);
    
        return $result;
    }

    function chargePOST(){
        if (isset($_POST['idt_doc']) && !empty($_POST['idt_doc'])){
            $this->idt_doc = htmlspecialchars($_POST['idt_doc']);
        }
    
        if(isset($_POST['titre'])){
            $this->titre = htmlspecialchars($_POST['titre']);
        } else {
            $this->titre = '';
        }
    
        if(isset($_POST['link'])){
            $this->link = htmlspecialchars($_POST['link']);
        } else {
            $this->link = '';
        }
    
        if(isset($_POST['type_doc'])){
            $this->type_doc = htmlspecialchars($_POST['type_doc']);
        } else {
            $this->type_doc = '';
        }
    
        if(isset($_POST['date_doc'])){
            $this->date_doc = htmlspecialchars($_POST['date_doc']);
        } else {
            $this->date_doc = '';
        }
    }
}