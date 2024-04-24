<?php

function connexion()
{
  $pdo = new PDO('mysql:host=localhost;dbname=t_gerber_sdea;charset=utf8', 'root', '');
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

  if ($pdo) {
    return $pdo;
  } else {
    echo '<p>Erreur de connexion</p>';
    exit;
  }
}

// function testConnexion() {
//     $pdo = connexion();
//     // var_dump($pdo);
//     if ($pdo) {
//         try {
//             $result = $pdo->query('SELECT * FROM asr');
//             if ($result) {
//                 echo 'La connexion à la base de données a réussi.<br>';
//                 // while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
//                 //     echo 'Nom : ' . $row['nom'] . ', Code Postal : ' . $row['cp'] . '<br>';
//                 // }
//             } else {
//                 echo 'La connexion à la base de données a échoué.';
//             }
//         } catch (PDOException $e) {
//             echo 'Erreur : ' . $e->getMessage();
//         }
//     }
// }


// testConnexion();