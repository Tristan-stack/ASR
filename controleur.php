<?php
session_start();

include('include/entity/asr.php');

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
                } else {
                    $user->create();
                    // Rediriger vers la page de connexion après la création de l'utilisateur
                    header('Location: controleur.php?page=user&action=login');
                    exit();
                }
                break; 

                case 'login':
                    $template = 'user/connexion.html.twig';
                    $data = ['action' => 'login'];
                    $errorMessageLog = ""; 
                    if (isset($_POST["submit"])) {
                        if (empty($_POST["username"]) || empty($_POST["password"])) {
                            $errorMessageLog = "Veuillez remplir chaque champ.";
                        } else {
                            $user = new User();
                            $user->chargePOST();
                            // Vérifiez si l'utilisateur existe dans la base de données
                            if ($user = User::authenticate($user->username, $user->password)) {
                                var_dump($user);
                                // Si l'utilisateur existe, stocker son pseudo dans la session et rediriger vers la page d'accueil
                                $_SESSION['username'] = $_POST["username"];
                                $_SESSION['id'] = $user->id; 
                                $_SESSION['email'] = $user->email;
                                $_SESSION['date_last_action'] = $user->date_last_action;
                                $_SESSION['date_last_connexion'] = $user->date_last_connexion;
                                $_SESSION['role'] = $user->role;
                
                                echo "L'ID du compte connecté est : " . $_SESSION['id'];
                                header('Location: controleur.php?page=accueil');
                                
                            } else {
                                $errorMessageLog = "Pseudo ou mot de passe incorrect.";
                            }
                        }
                    }
                    
                    $data = array_merge($data,['errorMessageLog' => $errorMessageLog]);
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
// var_dump($data);
echo $twig->render($template, $data);