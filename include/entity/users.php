<?php

class User{

    public $id;
    public $username;
    public $email;
    public $password;
    public $date_last_action;
    public $date_last_connexion;
    public $role_id;

    function __construct($id = null, $username = null, $email = null, $password = null, $date_last_action = null, $date_last_connexion = null, $role_id = null) { // Changed $role to $role_id
        $this->id = $id !== null ? intval($id) : null;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->date_last_action = $date_last_action;
        $this->date_last_connexion = $date_last_connexion;
        $this->role_id = $role_id;
    }

    static function readAll(){
        $sql = 'SELECT * FROM users';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute();
        
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'User');
        
        $tableau = $query->fetchAll();
        return $tableau;
    }

    static function readOne($id){
        $sql = 'SELECT * FROM users WHERE id = :id';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['id' => $id]);
        
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'User');
        
        $user = $query->fetch();
        return $user;
    }

    function create(){
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
    
     
        $dateLastAction = date('Y-m-d H:i:s');
        $dateLastConnexion = date('Y-m-d H:i:s'); 
        
        $sql = 'INSERT INTO users (username, email, password, date_last_action, date_last_connexion, role_id) VALUES (:username,:email, :password, :date_last_action, :date_last_connexion, :role_id)'; 
        
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->bindValue(':username', $this->username, PDO::PARAM_STR);
        $query->bindValue(':email', $this->email, PDO::PARAM_STR);
        $query->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $query->bindValue(':date_last_action', $dateLastAction, PDO::PARAM_STR); 
        $query->bindValue(':date_last_connexion', $dateLastConnexion, PDO::PARAM_STR); 
        $query->bindValue(':role_id', $this->role_id, PDO::PARAM_INT); 
        $query->execute();
        $this->id = $pdo->lastInsertId();
    }
    


    static function update($id, $newValues){ 
        $user = self::readOne($id);
        if (!$user) {
            
            return;
        }
    
        $username = array_key_exists('username', $newValues) ? $newValues['username'] : $user->username;
        $email = array_key_exists('email', $newValues) ? $newValues['email'] : $user->email;
        $password = array_key_exists('password', $newValues) ? password_hash($newValues['password'], PASSWORD_DEFAULT) : $user->password;
        $date_last_action = array_key_exists('date_last_action', $newValues) ? $newValues['date_last_action'] : $user->date_last_action;
        $date_last_connexion = array_key_exists('date_last_connexion', $newValues) ? $newValues['date_last_connexion'] : $user->date_last_connexion;
        $role_id = array_key_exists('role_id', $newValues) ? $newValues['role_id'] : $user->role_id;
    
        $sql = 'UPDATE users SET username = :username, email = :email, password = :password, date_last_action = :date_last_action, date_last_connexion = :date_last_connexion, role_id = :role_id WHERE id = :id'; 
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['id' => $id, 'username'=> $username, 'email' => $email, 'password' => $password, 'date_last_action' => $date_last_action, 'date_last_connexion' => $date_last_connexion, 'role_id' => $role_id]); 
    }

    static function delete($id){
        $sql = 'DELETE FROM users WHERE id = :id';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute(['id' => $id]);
    }

    public static function emailExists($email) {
        $sql = 'SELECT * FROM users WHERE email = :email';
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
        $sql = 'SELECT * FROM users WHERE username = :username';
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
    
    function login(){
        echo "La méthode login est appelée."; 
        // var_dump($this->username);
        // var_dump($this->password);
        $sql = 'SELECT * FROM users WHERE username = :username';
        $pdo = connexion();
        // var_dump($pdo);
        $query = $pdo->prepare($sql);
        $query->bindValue(':username', $this->username, PDO::PARAM_STR);
        $query->execute();
        $userData = $query->fetch(PDO::FETCH_ASSOC);
        // var_dump($userData);
        
        // Vérifier le mot de passe avec password_verify()
        if ($userData && password_verify($this->password, $userData['password'])) {
            $this->id = $userData['id'];
            $this->username = $userData['username'];
            $this->email = $userData['email'];
            $this->date_last_action = $userData['date_last_action'];
            $this->date_last_connexion = $userData['date_last_connexion'];
            $this->role_id = $userData['role_id'];
    
            // var_dump($this);
    
            return true;
        } else {
            return false;
        }
    }

    function getRole(){
        $sql = 'SELECT roles.role FROM roles WHERE id = :id'; 
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->bindValue(':id', $this->role_id, PDO::PARAM_INT); 
        $query->execute();
        
        $roleData = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($roleData) {
            return $roleData['role']; 
        } else {
            return null;
        }
    }

    function getRoles(){
        $sql = 'SELECT * FROM roles';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->execute();
        
        $roles = $query->fetchAll(PDO::FETCH_ASSOC);
        
        return $roles;
    }

    public static function findByEmail($email) {
        $sql = 'SELECT * FROM users WHERE email = :email';
        $pdo = connexion();
        $query = $pdo->prepare($sql);
        $query->bindValue(':email', $email, PDO::PARAM_STR);
        $query->execute();
        
        $query->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'User');
        
        $user = $query->fetch();
        return $user;
    }

    function chargePOST(){
        if (isset($_POST['id']) && !empty($_POST['id'])){
            $this->id = intval($_POST['id']);
        }
    
        if(isset($_POST['username'])){
            $this->username = htmlspecialchars($_POST['username']);
        } else {
            $this->username = '';
        }
    
        if(isset($_POST['email'])){
            $this->email = htmlspecialchars($_POST['email']);
        } else {
            $this->email = '';
        } 
    
        if(isset($_POST['password'])){
            $this->password = htmlspecialchars($_POST['password']);
        } else {
            $this->password = '';
        }
    
        if(isset($_POST['date_last_action'])){
            $this->date_last_action = htmlspecialchars($_POST['date_last_action']);
        } else {
            $this->date_last_action = '';
        }
    
        if(isset($_POST['date_last_connexion'])){
            $this->date_last_connexion = htmlspecialchars($_POST['date_last_connexion']);
        } else {
            $this->date_last_connexion = '';
        }
    
        if(isset($_POST['role_id'])){ 
            $this->role_id = intval($_POST['role_id']); 
        } else {
            $this->role_id = ''; 
        }
    }
}