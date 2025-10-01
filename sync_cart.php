<?php
session_start();
require_once __DIR__.'/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['cart']) || !isset($input['restaurant_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// Stocker le panier en session
$_SESSION['cart'] = $input['cart'];
$_SESSION['restaurant_id'] = (int)$input['restaurant_id'];

echo json_encode(['success' => true, 'message' => 'Panier synchronisé']);