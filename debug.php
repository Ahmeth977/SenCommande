<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Test du serveur - Si vous voyez ce message, PHP fonctionne.<br>";

// Test de connexion MySQL
$host = "sql213.infinityfree.com";
$username = "if0_40040844";
$password = "votre_mot_de_passe_mysql"; // Remplacez par le vrai
$dbname = "if0_40040844_resto_plateform";

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        echo "Erreur MySQL: " . $conn->connect_error;
    } else {
        echo "Connexion MySQL réussie!";
    }
} catch(Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
