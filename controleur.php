<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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


switch($page){
    case 'home':
        $template = 'home/home.html.twig';
        $data = [];
        break;
    case 'asr':
        switch($action){
            case 'read':
                if ($id > 0) {
                    $asr = Asr::readOne($id);
                    $documents = Documents::readDocByCommune($id);
            
                    // Regrouper les documents par catégorie
                    $documentsByCategory = [];
                    foreach ($documents as $document) {
                        $category = Categories::readOne($document->type_doc);
                        $documentsByCategory[$category->label_type_doc][] = $document;
                    }
            
                    $template = 'communes/commune_show.html.twig';
                    $data = ['asr' => $asr, 'documentsByCategory' => $documentsByCategory]; 
                } else {
                    $asr = Asr::readAll();
                
                    if (isset($_REQUEST['reset'])) {
                        $template = 'communes/commune_index.html.twig';
                        $data = ['asr' => $asr];
                    } else {
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
                    }
                }
                break;

            case 'create':
                $template = 'communes/create.html.twig';
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $nom = $_POST['nom'] ?? null;
                    $cp = $_POST['cp'] ?? null;
                    $asr = new Asr(null, $nom, $cp);
                    $asr->chargePOST();
                    if (empty($asr->nom) || empty($asr->cp)) {
                        echo "Tous les champs sont requis.";
                    } else {
                        $asr::create($nom, $cp);
                        header('Location: controleur.php?page=asr&action=read');
                        exit();
                    }
                }
                break;

            case 'update':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $id = $_POST['idt_asr'] ?? null;
                    $nom = $_POST['nom'] ?? null;
                    $cp = $_POST['cp'] ?? null;
                    $asr = new Asr($id, $nom, $cp);
                    if (!empty($asr->nom) && !empty($asr->cp)) {
                        $asr::update($id, $nom, $cp);
                        header('Location: controleur.php?page=asr&action=read');
                        exit();
                    } else {
                        echo "Veuillez remplir tous les champs.";
                    }
                } else {
                    $id = $_GET['id'] ?? null;
                    $asr = Asr::readOne($id);
                    if ($asr) {
                        $template = 'communes/update.html.twig';
                        $data = ['asr' => $asr];
                    } else {
                        echo "La commune avec l'ID spécifié n'existe pas.";
                    }
                }
                break;
            
            case 'delete':
                $id = $_GET['id'];
                Asr::delete($id);
                header('Location: controleur.php?page=home');
                exit();
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
                    // echo "Email déjà utilisé.";
                } elseif (User::usernameExists($user->username)) {
                    // echo "Nom d'utilisateur déjà utilisé.";
                } elseif (empty($user->username) || empty($user->email) || empty($user->password) || empty($user->role_id)) {
                    // echo "Tous les champs sont requis.";
                } elseif (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{6,}/", $user->password)) {
                    // echo "Le mot de passe doit contenir au moins une majuscule, un chiffre, un caractère spécial et faire au moins 6 caractères.";
                } else {
                    $user->create();

                    // Envoi de l'email
                    $mail = new PHPMailer(true);

                    try {
                        //Server settings
                        $mail->isSMTP();                                      
                        $mail->Host = 'in-v3.mailjet.com';                    
                        $mail->SMTPAuth = true;                              
                        $mail->Username = '54b681b6bcb8a430ea3d46fda24c11a0';             
                        $mail->Password = '4906bb776e7f9fa8960f570689394695';         
                        $mail->SMTPSecure = 'tls';                           
                        $mail->Port = 587;                                   

                        //Recipients
                        $mail->setFrom('tristansdea@gmail.com', 'Mailer');
                        $mail->addAddress($user->email, $user->username);     

                        //Content
                        $mail->isHTML(true);                                  
                        $mail->Subject = 'Votre compte est prêt !';
                        $mail->Body    = 'Bonjour, votre compte est prêt à être utiliser. Votre username est : ' . $user->username . ' et votre mot de passe : ' . $user->password . '. Une fois connecté, merci de changer votre mot de passe temporaire par votre mot de passe personnel.';

                        $mail->send();
                        echo 'Message has been sent';
                    } catch (Exception $e) {
                        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
                    }

                    header('Location: controleur.php?page=admin&action=read');
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

                case 'forgot':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $email = $_POST['email'];
                        var_dump($email);
                        $user = User::findByEmail($email);
                        if ($user) {
                            // Envoi de l'email
                            $mail = new PHPMailer(true);
        
                            try {
                                //Server settings
                                $mail->isSMTP();                                      
                                $mail->Host = 'in-v3.mailjet.com';                    
                                $mail->SMTPAuth = true;                               
                                $mail->Username = '54b681b6bcb8a430ea3d46fda24c11a0';            
                                $mail->Password = '4906bb776e7f9fa8960f570689394695';          
                                $mail->SMTPSecure = 'tls';                            
                                $mail->Port = 587;                                   
        
                                //Recipients
                                $mail->setFrom('tristansdea@gmail.com', 'Mailer');
                                $mail->addAddress($user->email, $user->username);     
        
                                //Content
                                $mail->isHTML(true);                                  
                                $mail->Subject = 'Récupération de vos identifiants';
                                $mail->Body    = 'Bonjour, voici vos identifiants : Username : ' . $user->username . ' et Password : ' . $user->password . '.';
        
                                $mail->send();
                                echo 'Message has been sent';
                            } catch (Exception $e) {
                                echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
                            }
                        } else {
                            echo "Aucun utilisateur trouvé avec cette adresse e-mail.";
                        }
                    }
                    break;
                
                
            case 'update':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $user = User::readOne($id);
                    $user->chargePOST();
                    $newValues = [];
                    if (!empty($user->username)) {
                        $newValues['username'] = $user->username;
                        if ($_SESSION['role_id'] !== 1) { 
                            $_SESSION['username'] = $user->username;
                        }
                    }
                    if (!empty($user->email)) {
                        $newValues['email'] = $user->email;
                        if ($_SESSION['role_id'] !== 1) { 
                            $_SESSION['email'] = $user->email;
                        }
                    }
                    if (!empty($user->role_id)) {
                        $newValues['role_id'] = $user->role_id;
                        if ($_SESSION['role_id'] !== 1 ) { 
                            $_SESSION['role_id'] = $user->role_id;
                        }
                    }
                    if (!empty($user->password)) {
                        $newValues['password'] = $user->password;
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
                header('Location: controleur.php?page=admin&action=read');
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
                    // Récupérez uniquement les communes liées au document spécifique
                    $asr = Docrelation::getCommunesByDocument($id);
                    $template = 'documents/document_detail.html.twig';
                    // Passez les communes à la vue
                    $data = ['documents' => $documents, 'asr' => $asr];
                } else {
                    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
                    $action = isset($_POST['action']) ? $_POST['action'] : 'read';
                    
                    if (isset($_POST['date']) && !empty($_POST['date'])) {
                        $year = $_POST['date']; // Supposons que le formulaire envoie juste une année
                        $documents = Documents::readByDate($year);
                        // var_dump($documents);
                    } else {
                        $documents = Documents::readAll();
                    }
                    
                    $totalDocuments = Documents::countAll();
                    $documentsByCategory = [];
                    foreach ($documents as $document) {
                        $categoryLabel = $document->label_type_doc;
                        if ($categoryLabel === null || $categoryLabel === '') {
                            $categoryLabel = 'Uncategorized';
                        }
                        $documentsByCategory[$categoryLabel][] = $document;
                    }
                    // var_dump($documentsByCategory);
                    $allCategories = Categories::readAll();
    
                    $availableDates = Documents::getAvailableDates();
    
                    $template = 'documents/document_index.html.twig';
                    $data = [
                        'documentsByCategory' => $documentsByCategory, 
                        'page' => $page, 
                        'totalDocuments' => $totalDocuments, 
                        'allCategories' => $allCategories,
                        'availableDates' => $availableDates  
                    ];
                }
                break;


            case 'uploadedToday':
                // var_dump($_POST);
                $types = Documents::getAllTypes();
                $asr = Asr::readAll();
                $folder = $_POST['folder'] ?? null; 

                if ($folder === null) {
                    $template = 'documents/create.html.twig'; 

                    if (isset($_POST['titre'], $_POST['link'], $_POST['type_doc'], $_POST['date_doc'])) {
                        $titre = $_POST['titre'];
                        $link = $_POST['link'];
                        $type_doc = strval($_POST['type_doc']); 
                        $date_doc = new DateTime($_POST['date_doc']);
                        $date_formatted = $date_doc->format('Y-m-d'); 
                        $idt_doc = null; 

                        if (!Documents::isPathInDatabase($link)) {
                            $idt_doc = Documents::create($titre, $link, $type_doc, $date_formatted, $idt_doc);
                        }

                        if (isset($_POST['communes']) && is_array($_POST['communes'])) {
                            // var_dump($_POST['communes']);
                            Docrelation::createMultiple($idt_doc, $_POST['communes']);
                        }
                    }
                    $data = ['asr' => $asr];
                } else {
                    // var_dump($_POST);
                    $dir = "C:/wamp64/www/DEPOT/$folder"; 
                    $files = scandir($dir);

                    $uploadedToday = [];

                    foreach ($files as $file) {
                        if ($file == '.' || $file == '..') {
                            continue;
                        }

                        $filePath = $dir . '/' . $file;

                        // Si le chemin du fichier est déjà dans la base de données, passez à l'itération suivante de la boucle
                        if (Documents::isPathInDatabase($filePath)) {
                            continue;
                        }

                        $uploadedToday[] = ['name' => $file, 'path' => $filePath]; 
                    }

                    $template = 'documents/create.html.twig'; 
                    $data = ['uploadedToday' => $uploadedToday, 'types' => $types, 'asr' => $asr, 'folder' => $folder];
                }

                break;

            case 'update' : 
                if (isset($_POST['id'], $_POST['titre'])) {
                    $id = $_POST['id'];
                    $titre = $_POST['titre'];
                    
                    Documents::update($id, $titre);
            
                    // Vérifiez si des communes ont été envoyées avec le formulaire
                    if (isset($_POST['communes']) && is_array($_POST['communes'])) {
                        
                        $existingRelations = Docrelation::getCommunesByDocument($id);
            
                      
                        foreach ($_POST['communes'] as $commune) {
                            if (!in_array($commune, $existingRelations)) {
                                Docrelation::create($id, $commune);
                            }
                        }
                    }
            
                    // Redirigez vers la page de détail du document
                    header('Location: controleur.php?page=documents&action=read&id=' . $id);
                    exit;
                } else {
                   
                    $document = Documents::readOne($id);
                    $communes = Docrelation::getCommunesByDocument($id);
                    $allCommunes = Asr::readAll();
                    $allCommunesForAutocomplete = array_map(function($commune) {
                        return ['label' => $commune->nom, 'value' => $commune->idt_asr];
                    }, $allCommunes);
                            
                    $template = 'documents/update.html.twig';
                    $data = ['document' => $document, 'communes' => $communes, 'allCommunes' => $allCommunesForAutocomplete];
                    
                }
                break;

            case 'delete':
                $id = $_GET['id'];
                Documents::delete($id);
                header('Location: controleur.php?page=documents&action=read');
                exit();
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
                    $idt = $_POST['idt'] ?? null; 
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
                    
                    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
                    if ($contentType === "application/json") {
                        
                        $content = trim(file_get_contents("php://input"));
                        $decoded = json_decode($content, true);

                        
                        if (is_array($decoded)) {
                            $id = $decoded['id'] ?? null; 
                            $label_type_doc = $decoded['label'] ?? null; 

                            if ($id && $label_type_doc) {
                                $categories = new Categories($id, $label_type_doc); 
                                if (!empty($categories->label_type_doc)) {
                                    $categories->update(); 
                                    echo json_encode(['status' => 'success']);
                                    exit();
                                }
                            }
                        }
                    }
                    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
                    exit();
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
    
    case 'notice':
        $template = 'home/notice.html.twig';
        $data = [];
        break;


    default:
        $template = 'home/home.html.twig';
        $data = [];
        break;
}

$data['session'] = $_SESSION; 
echo $twig->render($template, $data);