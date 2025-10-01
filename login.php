<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';

session_start();

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    redirectBasedOnRole();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'admin';
    
    // Validation basique
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        // Déterminer le rôle en fonction du type d'utilisateur
        $role = ($user_type === 'manager') ? 'restaurateur' : 'admin';
        
        // Vérifier les identifiants dans la base de données
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            // Pour les restaurateurs, récupérer les infos du restaurant
            if ($user['role'] === 'restaurateur') {
                $stmt = $db->prepare("SELECT * FROM restaurants WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $restaurant = $stmt->fetch();
                
                if ($restaurant) {
                    $_SESSION['restaurant_id'] = $restaurant['id'];
                    $_SESSION['restaurant_name'] = $restaurant['name'];
                    header("Location: manager/index.php");
                    exit();
                } else {
                    $error = "Aucun restaurant associé à ce compte";
                }
            } else {
                header("Location: admin/index.php");
                exit();
            }
        } else {
            $error = "Email ou mot de passe incorrect";
        }
    }
}

// Si on arrive ici, c'est qu'il y a une erreur
$_SESSION['login_error'] = $error;
header("Location: index.php");
exit();

// Fonction de redirection basée sur le rôle
function redirectBasedOnRole() {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/index.php");
        exit();
    } else if ($_SESSION['user_role'] === 'restaurateur') {
        header("Location: manager/index.php");
        exit();
    }
    header("Location: index.php");
    exit();
}
?>