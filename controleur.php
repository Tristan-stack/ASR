<?php
session_start();


include('include/entity/communes.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require 'vendor/autoload.php'; // Inclure l'autoloader Twig
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);

$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = 'home/home.html.twig'; // Définir le template par défaut à home
$data = []; // initialise tableau vide

if (isset($_GET['action'])) $action = $_GET['action'];
else $action = 'read';

if (isset($_GET['id'])) $id = intval($_GET['id']);
else $id = 0;

if (isset($_GET['page'])) $page = $_GET['page'];
else $page = 'asr';

if (isset($_SESSION['username']) && isset($_SESSION['id'])) {
    echo "Nom d'utilisateur : " . $_SESSION['username'];
    echo " compte n° : " . $_SESSION['id'];
}


switch($page){
    case 'home':
        $template = 'home/home.html.twig';
        $data = [];
        break;
    case 'asr':
        if ($id > 0) {
            $asr = Asr::readOne($id);
            $template = 'asr/asr_show.html.twig';
            $data = ['asr' => $asr];
            break;
        } else {
            $asr = Asr::readAll();
        
            if (isset($_REQUEST['reset'])) {
                $template = 'asr/asr_index.html.twig';
                $data = ['asr' => $asr];
                break;
            }
        
            if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != '') {
                $nom = $_REQUEST['nom'];
                $asr = array_filter($asr, function($item) use ($nom) {
                    return $item->nom == $nom;
                });
                $asr = array_values($asr);
            }
        
            if (isset($_REQUEST['cp']) && $_REQUEST['cp'] != '') {
                $cp = $_REQUEST['cp'];
                $asr = array_filter($asr, function($item) use ($cp) {
                    return $item->cp == $cp;
                });
                $asr = array_values($asr);
            }
        
            if (isset($_REQUEST['order']) && ($_REQUEST['order'] == 'asc' || $_REQUEST['order'] == 'desc')) {
                $order = $_REQUEST['order'];
                usort($asr, function($a, $b) use ($order) {
                    return $order === 'asc' ? strcmp($a->nom, $b->nom) : strcmp($b->nom, $a->nom);
                });
            }
        
            $template = 'asr/asr_index.html.twig';
            $data = ['asr' => $asr];
            break;
        }
        
        break;
    
    case 'user':
        switch($action){
            case 'read':
                if ($id > 0) {
                    $user = User::readOne($id);
                    $template = 'user/user_detail.html.twig';
                    $data = ['user' => $user];
                } else {
                    $users = User::readAll();
                    $template = 'user/view.html.twig';
                    $data = ['users' => $users];
                }
                break;
            
               
            case 'create':
                $template = 'user/inscription.html.twig';
                $data = [];
                $user = new User();
                $user->chargePOST();
                if (User::emailExists($user->email)) {
                    // Gérer le cas où l'email existe déjà
                    echo "Email déjà utilisé.";
                } elseif (User::usernameExists($user->username)) {
                    // Gérer le cas où le username existe déjà
                     echo "Nom d'utilisateur déjà utilisé.";
                } elseif (empty($user->username) || empty($user->email) || empty($user->password)) {
                    // Gérer le cas où l'un des champs est vide
                    echo "Tous les champs sont requis.";
                } elseif (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{6,}/", $user->password)) {
                    // Gérer le cas où le mot de passe ne respecte pas les contraintes
                    echo "Le mot de passe doit contenir au moins une majuscule, un chiffre, un caractère spécial et faire au moins 6 caractères.";
                } else {
                    $user->role = 'Gestionnaire';
                    $user->create();
                    // Rediriger vers la page de connexion après la création de l'utilisateur
                    header('Location: controleur.php?page=user&action=login');
                    exit();
                }
                break; 

            case 'login':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $user = new User();
                    $user->chargePOST();
                    if ($user->login()) {
                        // L'utilisateur est connecté, stocker ses informations dans la session
                        $_SESSION['username'] = $user->username;
                        $_SESSION['id'] = $user->id;
                        // Rediriger vers la page d'accueil après la connexion
                        header('Location: controleur.php?page=home');
                        exit();
                    } else {
                        // Les informations de connexion sont incorrectes
                        echo "Nom d'utilisateur ou mot de passe incorrect.";
                    }
                } else {
                    // Afficher le formulaire de connexion
                    $template = 'user/connexion.html.twig';
                    $data = [];
                }
                break;  
                
            case 'logout':
                session_destroy();
                header('Location: controleur.php?page=home');
                exit();
                break;
            
            case 'delete':
                $id = $_GET['id'];
                User::delete($id);
                header('Location: controleur.php?page=user&action=read');
                exit();
                break;
        }
        break;

    default:
        $template = 'home/home.html.twig';
        $data = [];
        break;
}

$data['session'] = $_SESSION; // Ajoutez cette ligne ici
echo $twig->render($template, $data);