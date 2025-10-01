<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Méthode non autorisée. Redirection vers index.php");
    header("Location: index.php");
    exit();
}

// Vérifier si le panier existe
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    error_log("Panier vide. Redirection vers cart.php");
    header("Location: cart.php");
    exit();
}

// Récupérer et valider les données du formulaire
$restaurantId = isset($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : 0;
$firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$building = isset($_POST['building']) ? trim($_POST['building']) : '';
$apartment = isset($_POST['apartment']) ? trim($_POST['apartment']) : '';
$city = isset($_POST['city']) ? trim($_POST['city']) : '';
$deliveryInstructions = isset($_POST['delivery_instructions']) ? trim($_POST['delivery_instructions']) : '';
$saveAddress = isset($_POST['save_address']) ? true : false;
$isGuest = !isset($_SESSION['user_id']);

// Validation des données obligatoires
if (empty($firstName) || empty($lastName) || !$email || empty($phone) || 
    empty($address) || empty($city) || $restaurantId <= 0) {
    
    $_SESSION['error'] = "Veuillez remplir tous les champs obligatoires.";
    error_log("Champs obligatoires manquants. Redirection vers delivery_info.php");
    header("Location: delivery_info.php?restaurant_id=" . $restaurantId);
    exit();
}

try {
    $db = connectDB();
    
    // Enregistrer les informations de livraison dans la session
    $_SESSION['delivery_info'] = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'building' => $building,
        'apartment' => $apartment,
        'city' => $city,
        'delivery_instructions' => $deliveryInstructions,
        'restaurant_id' => $restaurantId,
        'is_guest' => $isGuest
    ];
    
    // Si l'utilisateur est connecté et veut enregistrer l'adresse
    if (!$isGuest && $saveAddress) {
        $userId = $_SESSION['user_id'];
        
        // Mettre à jour les informations de base de l'utilisateur
        $stmt = $db->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, email = ?, phone = ?
            WHERE id = ?
        ");
        $stmt->execute([$firstName, $lastName, $email, $phone, $userId]);
        
        // Vérifier si la table user_addresses existe, sinon la créer
        $tableExists = $db->query("SHOW TABLES LIKE 'user_addresses'")->rowCount() > 0;
        
        if (!$tableExists) {
            // Créer la table si elle n'existe pas
            $db->exec("
                CREATE TABLE user_addresses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    address VARCHAR(255) NOT NULL,
                    building VARCHAR(100),
                    apartment VARCHAR(100),
                    city VARCHAR(100) NOT NULL,
                    delivery_instructions TEXT,
                    is_primary BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
        }
        
        // Vérifier si l'utilisateur a déjà une adresse enregistrée
        $stmt = $db->prepare("SELECT id FROM user_addresses WHERE user_id = ? AND is_primary = 1");
        $stmt->execute([$userId]);
        $existingAddress = $stmt->fetch();
        
        if ($existingAddress) {
            // Mettre à jour l'adresse existante
            $stmt = $db->prepare("
                UPDATE user_addresses 
                SET address = ?, building = ?, apartment = ?, city = ?, delivery_instructions = ?
                WHERE id = ?
            ");
            $stmt->execute([$address, $building, $apartment, $city, $deliveryInstructions, $existingAddress['id']]);
        } else {
            // Créer une nouvelle adresse
            $stmt = $db->prepare("
                INSERT INTO user_addresses (user_id, address, building, apartment, city, delivery_instructions, is_primary)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$userId, $address, $building, $apartment, $city, $deliveryInstructions]);
        }
    }
    
    // Rediriger vers la page de paiement
    header("Location: checkout.php?restaurant_id=" . $restaurantId);
    exit();
    
} catch (PDOException $e) {
    error_log("Erreur process_delivery: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors du traitement de vos informations: " . $e->getMessage();
    header("Location: delivery_info.php?restaurant_id=" . $restaurantId);
    exit();
} catch (Exception $e) {
    error_log("Erreur générale process_delivery: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur inattendue est survenue.";
    header("Location: delivery_info.php?restaurant_id=" . $restaurantId);
    exit();
}