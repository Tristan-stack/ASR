<?php

class Categories{

    public $idt;
    public $label_type_doc;

    public function __construct($idt, $label_type_doc){
        $this->idt = $idt;
        $this->label_type_doc = $label_type_doc;
    }

    static function readAll(){
        $sql = 'SELECT * FROM categories';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute();

        $tableau = $query->fetchAll();
        return $tableau;
    }

    static function readOne($idt){
        $sql = 'SELECT * FROM categories WHERE idt = :idt';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt' => $idt]);
    
        $result = $query->fetch();
    
        if ($result) {
            return new Categories($result['idt'], $result['label_type_doc']);
        } else {
            return null;
        }
    }

    static function create($label_type_doc){
        $sql = 'INSERT INTO categories (label_type_doc) VALUES (:label_type_doc)';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['label_type_doc' => $label_type_doc]);
    }

    function update(){
        $sql = 'UPDATE categories SET label_type_doc = :label_type_doc WHERE idt = :idt';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt' => $this->idt, 'label_type_doc' => $this->label_type_doc]);
    }

    static function delete($idt){
        $sql = 'DELETE FROM categories WHERE idt = :idt';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt' => $idt]);
    }

    public function getDocuments(){
        $sql = 'SELECT * FROM documents WHERE type_doc = :idt';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt' => $this->idt]);
    
        $documents = $query->fetchAll();
        return $documents;
    }

    function chargePOST(){
        if(isset($_POST['idt'])){
            $this->idt = htmlspecialchars($_POST['idt']);
        } else {
            $this->idt = '';
        }
    
        if(isset($_POST['label_type_doc'])){
            $this->label_type_doc = htmlspecialchars($_POST['label_type_doc']);
        } else {
            $this->label_type_doc = '';
        }
    }
}