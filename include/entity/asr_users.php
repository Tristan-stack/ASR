<?php

class User{

    public $id;
    public $username;
    public $email;
    public $password;
    public $date_last_action;
    public $date_last_connexion;
    public $role;

    function __construct($id = null, $username = null, $email = null, $password = null, $date_last_action = null, $date_last_connexion = null, $role = null) {
        $this->id = $id !== null ? intval($id) : null;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->date_last_action = $date_last_action;
        $this->date_last_connexion = $date_last_connexion;
        $this->role = $role;
    }

    static function readAll(){
        $sql = 'SELECT * FROM asr_users';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute();
        
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'User');
        
        $tableau = $query->fetchAll();
        return $tableau;
    }

    static function readOne($id){
        $sql = 'SELECT * FROM asr_users WHERE id = :id';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['id' => $id]);
        
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'User');
        
        $user = $query->fetch();
        return $user;
    }

    function create(){
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        
        // Assurez-vous que les autres propriétés de l'utilisateur sont initialisées
        // (role)
        
        // Générez la date au format approprié
        $dateLastAction = date('Y-m-d H:i:s'); // Remplacez cette valeur par la date souhaitée
        $dateLastConnexion = date('Y-m-d H:i:s'); // Remplacez cette valeur par la date souhaitée
        
        $sql = 'INSERT INTO asr_users (username, email, password, date_last_action, date_last_connexion, role) VALUES (:username,:email, :password, :date_last_action, :date_last_connexion, :role)';
        
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->bindValue(':username', $this->username, PDO::PARAM_STR);
        $query->bindValue(':email', $this->email, PDO::PARAM_STR);
        $query->bindValue(':password', $hashedPassword, PDO::PARAM_STR); // Utilisez le mot de passe haché
        $query->bindValue(':date_last_action', $dateLastAction, PDO::PARAM_STR); // Utilisez la date formatée
        $query->bindValue(':date_last_connexion', $dateLastConnexion, PDO::PARAM_STR); // Utilisez la date formatée
        $query->bindValue(':role', $this->role, PDO::PARAM_STR);
        $query->execute();
        $this->id = $pdo->lastInsertId();
    }
    


    static function update($id, $username, $email, $password, $date_last_action, $date_last_connexion, $role){
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
        $sql = 'UPDATE asr_users SET username = :username, email = :email, password = :password, date_last_action = :date_last_action, date_last_connexion = :date_last_connexion, role = :role WHERE id = :id';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['id' => $id, 'username'=> $username, 'email' => $email, 'password' => $hashedPassword, 'date_last_action' => $date_last_action, 'date_last_connexion' => $date_last_connexion, 'role' => $role]);
    }

    static function delete($id){
        $sql = 'DELETE FROM asr_users WHERE id = :id';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['id' => $id]);
    }

    public static function emailExists($email) {
        $sql = 'SELECT * FROM asr_users WHERE email = :email';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->bindValue(':email', $email, PDO::PARAM_STR);
        $query->execute();
        $userData = $query->fetchObject('User');
    
        if ($userData) {
            return true;
        } else {
            return false;
        }
    }

    public static function usernameExists($username) {
        $sql = 'SELECT * FROM asr_users WHERE username = :username';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->bindValue(':username', $username, PDO::PARAM_STR);
        $query->execute();
        $userData = $query->fetchObject('User');
    
        if ($userData) {
            return true;
        } else {
            return false;
        }
    }


    static function readByUsername($username){
        $sql = 'SELECT * FROM asr_users WHERE username = :username';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['username' => $username]);
        
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'User');
        
        $user = $query->fetch();
        return $user;
    }

    public static function authenticate($username, $password) {
        $user = self::readByUsername($username);
        if ($user && password_verify($password, $user->password)) {
            return $user;
        } else {
            return false;
        }
    }

    function chargePOST(){
        if (isset($_POST['id']) && !empty($_POST['id'])){
            $this->id = $_POST['id'];
        }

        if(isset($_POST['username'])){
            $this->username = $_POST['username'];
        } else {
            $this->username = '';
        }

        if(isset($_POST['email'])){
            $this->email = $_POST['email'];
        } else {
            $this->email = '';
        } 

        if(isset($_POST['password'])){
            $this->password = $_POST['password'];
        } else {
            $this->password = '';
        }

        if(isset($_POST['date_last_action'])){
            $this->date_last_action = $_POST['date_last_action'];
        } else {
            $this->date_last_action = '';
        }

        if(isset($_POST['date_last_connexion'])){
            $this->date_last_connexion = $_POST['date_last_connexion'];
        } else {
            $this->date_last_connexion = '';
        }

        if(isset($_POST['role'])){
            $this->role = $_POST['role'];
        } else {
            $this->role = '';
        }
    }
}