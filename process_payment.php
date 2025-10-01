<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';

// Vérifier si la requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// Valider les données requises
$requiredFields = ['method', 'data', 'restaurantId', 'total'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Champ manquant: ' . $field]);
        exit;
    }
}

try {
    $db = connectDB();
    
    // Enregistrer la transaction en base de données
    $stmt = $db->prepare("
        INSERT INTO transactions 
        (user_id, restaurant_id, payment_method, amount, status, transaction_data, created_at) 
        VALUES (?, ?, ?, ?, 'pending', ?, NOW())
    ");
    
    $transactionData = json_encode([
        'payment_data' => $input['data'],
        'cart' => $input['cart'] ?? []
    ]);
    
    $stmt->execute([
        $_SESSION['user_id'],
        $input['restaurantId'],
        $input['method'],
        $input['total'],
        $transactionData
    ]);
    
    $transactionId = $db->lastInsertId();
    
    // SIMULATION: Pour le moment, on simule un paiement réussi
    // Plus tard, vous intégrerez les vraies API ici
    
    $status = 'completed';
    $externalTransactionId = 'SIM_' . strtoupper($input['method']) . '_' . $transactionId;
    
    // Mettre à jour la transaction
    $stmt = $db->prepare("
        UPDATE transactions 
        SET status = ?, external_transaction_id = ?, completed_at = NOW() 
        WHERE id = ?
    ");
    
    $stmt->execute([$status, $externalTransactionId, $transactionId]);
    
    // Vider le panier
    unset($_SESSION['cart']);
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'transaction_id' => $externalTransactionId,
        'message' => 'Paiement simulé avec succès'
    ]);
    
} catch (Exception $e) {
    error_log("Erreur traitement paiement: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur interne du serveur']);
}