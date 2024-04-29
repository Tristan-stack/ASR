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
            // $documents = Documents::readDocByCommune($id);
    
            // Regrouper les documents par catégorie
            $documentsByCategory = [];
            foreach ($documents as $document) {
                $category = Categories::readOne($document->type_doc);
                $documentsByCategory[$category->label_type_doc][] = $document;
            }
    
            $template = 'communes/commune_show.html.twig';
            $data = ['asr' => $asr, 'documentsByCategory' => $documentsByCategory]; 
            break;
        } else {
            $asr = Asr::readAll();
        
            if (isset($_REQUEST['reset'])) {
                $template = 'communes/commune_index.html.twig';
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
        
            $template = 'communes/commune_index.html.twig';
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
                $user = new User();
                $roles = $user->getRoles(); // Récupérer les rôles
                $data = ['roles' => $roles]; // Passer les rôles à la vue
                $user->chargePOST();
                if (User::emailExists($user->email)) {
                    echo "Email déjà utilisé.";
                } elseif (User::usernameExists($user->username)) {
                    echo "Nom d'utilisateur déjà utilisé.";
                } elseif (empty($user->username) || empty($user->email) || empty($user->password) || empty($user->role_id)) {
                    echo "Tous les champs sont requis.";
                } elseif (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{6,}/", $user->password)) {
                    echo "Le mot de passe doit contenir au moins une majuscule, un chiffre, un caractère spécial et faire au moins 6 caractères.";
                } else {
                    $user->create();
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
                        $_SESSION['role_id'] = $user->role_id;
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
                
                
            case 'update':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $user = User::readOne($id);
                    $user->chargePOST();
                    $newValues = [];
                    if (!empty($user->username)) {
                        $newValues['username'] = $user->username;
                        if ($_SESSION['role_id'] !== 1) { // Check if the current user is not an admin
                            $_SESSION['username'] = $user->username;
                        }
                    }
                    if (!empty($user->email)) {
                        $newValues['email'] = $user->email;
                        if ($_SESSION['role_id'] !== 1) { // Check if the current user is not an admin
                            $_SESSION['email'] = $user->email;
                        }
                    }
                    if (!empty($user->role_id)) {
                        $newValues['role_id'] = $user->role_id;
                        if ($_SESSION['role_id'] !== 1 ) { // Check if the current user is not an admin
                            $_SESSION['role_id'] = $user->role_id;
                        }
                    }
                    if (!empty($newValues)) {
                        User::update($id, $newValues);
                        header('Location: controleur.php?page=user&action=read&id=' . $user->id);
                        exit();
                    }
                } else {
                    $user = User::readOne($id);
                    $roles = $user->getRoles();
                    $template = 'user/update.html.twig';
                    $data = ['user' => $user, 'roles' => $roles];
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

    case 'admin':
        switch($action){
            case 'read':
                $users = User::readAll();
                $user = new User();
                $roles = $user->getRoles(); // Récupérer les rôles
                $template = 'admin/back-office.html.twig';
                $data = ['users' => $users, 'roles' => $roles]; // Passer les rôles à la vue
                break;
            case 'update':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $user = User::readOne($id);
                    $user->chargePOST();
                    $newValues = [];
                    if (!empty($user->username)) {
                        $newValues['username'] = $user->username;
                    }
                    if (!empty($user->email)) {
                        $newValues['email'] = $user->email;
                    }
                    if (!empty($user->role_id)) {
                        $newValues['role_id'] = $user->role_id;
                    }
                    if (!empty($newValues)) {
                        User::update($id, $newValues);
                        header('Location: controleur.php?page=admin&action=read');
                        exit();
                    }
                } else {
                    $user = User::readOne($id);
                    $roles = $user->getRoles();
                    $template = 'admin/update.html.twig';
                    $data = ['user' => $user, 'roles' => $roles];
                }
                break;
        }
        break;

    case 'documents':
        switch($action){
            case 'read':
                if ($id > 0) {
                    $documents = Documents::readOne($id);
                    $communes = Asr::readCommuneByDoc($id); // Utiliser la méthode ici
                    $template = 'documents/document_detail.html.twig';
                    $data = ['documents' => $documents, 'communes' => $communes];
                } else {
                    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
                    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
                    $action = isset($_POST['action']) ? $_POST['action'] : 'read';
                    $documents = Documents::readAll($page, $limit);
                    $documentsByCategory = [];
                    foreach ($documents as $document) {
                        $categoryLabel = $document->label_type_doc;
                        if ($categoryLabel !== null) {
                            $documentsByCategory[$categoryLabel][] = $document;
                        } else {
                            $documentsByCategory['Uncategorized'][] = $document;
                        }
                    }
                    $template = 'documents/document_index.html.twig';
                    $data = ['documentsByCategory' => $documentsByCategory, 'limit' => $limit, 'page' => $page];
                }
                break;
        }
        break;

    case 'categories':
        switch($action){
            
            case 'read':
                if ($id > 0) {
                    $categories = Categories::readOne($id);
                    $documents = $categories->getDocuments();
                    $template = 'categories/show.html.twig';
                    $data = ['categories' => $categories, 'documents' => $documents];
                }else {
                    $categories = Categories::readAll();
                    $template = 'categories/index.html.twig';
                    $data = ['categories' => $categories];
                }
                break;

            case 'create':
                $template = 'categories/create.html.twig';
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $idt = $_POST['idt'] ?? null; // replace 'idt' with the actual name of your input field
                    $label_type_doc = $_POST['label_type_doc'] ?? null;
                    $categories = new Categories($idt, $label_type_doc);
                    $categories->chargePOST();
                    if (empty($categories->label_type_doc)) {
                        echo "Le champ est requis.";
                    } else {
                        $categories->create($label_type_doc);
                        header('Location: controleur.php?page=categories&action=read');
                        exit();
                    }
                }
                break;

            case 'update':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $id = $_POST['idt'] ?? null; // Obtenez l'ID de la catégorie à partir des données POST
                    $label_type_doc = $_POST['label_type_doc'] ?? null; // Obtenez le nouveau label à partir des données POST
                    $categories = new Categories($id, $label_type_doc); // Créez un nouvel objet Categories avec cet ID et ce label
                    if (!empty($categories->label_type_doc)) {
                        $categories->update(); // Mettez à jour la catégorie
                        //var_dump($categories);
                        header('Location: controleur.php?page=categories&action=read');
                        exit();
                    } else {
                        echo "Veuillez remplir tous les champs.";
                    }
                } else {
                    $id = $_GET['id'] ?? null;
                    $categories = Categories::readOne($id);
                    if ($categories) {
                        $template = 'categories/update.html.twig';
                        $data = ['categories' => $categories];
                    } else {
                        echo "La catégorie avec l'ID spécifié n'existe pas.";
                    }
                }
                break;
            
            case 'delete':
                $id = $_GET['id'];
                Categories::delete($id);
                header('Location: controleur.php?page=categories&action=read');
                exit();
                break;
        }
        break;

    default:
        $template = 'home/home.html.twig';
        $data = [];
        break;
}

$data['session'] = $_SESSION; 
echo $twig->render($template, $data);