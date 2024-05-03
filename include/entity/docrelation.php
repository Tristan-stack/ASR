<?php

class Docrelation{

    public $idt_doc;
    public $idt_asr;

    function __construct($idt_doc = null, $idt_asr = null) {
        $this->idt_doc = $idt_doc !== null ? intval($idt_doc) : null;
        $this->idt_asr = $idt_asr !== null ? intval($idt_asr) : null;
    }

    static function create($idt_doc, $idt_asr){
        $sql = 'INSERT INTO docrelation (idt_doc, idt_asr) VALUES (:idt_doc, :idt_asr)';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt_doc' => $idt_doc, 'idt_asr' => $idt_asr]);
    }

    static function createMultiple($idt_doc, $idt_asrs) {
        foreach ($idt_asrs as $idt_asr) {
            self::create($idt_doc, $idt_asr);
        }
    }

    static function deleteByDocument($idt_doc){
        $sql = 'DELETE FROM docrelation WHERE idt_doc = :idt_doc';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $result = $query->execute(['idt_doc' => $idt_doc]);
    
        return $result;
    }

    static function getCommunesByDocument($idt_doc){
        $sql = 'SELECT * FROM communes 
            JOIN docrelation ON communes.idt_asr = docrelation.idt_asr 
            WHERE docrelation.idt_doc = :idt_doc';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['idt_doc' => $idt_doc]);
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
    
        return $result;
    }

    function chargePOST(){
        if (isset($_POST['idt_doc']) && !empty($_POST['idt_doc'])){
            $this->idt_doc = $_POST['idt_doc'];
        }  

        if (isset($_POST['idt_asr']) && !empty($_POST['idt_asr'])){
            $this->idt_asr = $_POST['idt_asr'];
        }
    }
}