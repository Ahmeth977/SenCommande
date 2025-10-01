<?php
session_start();
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON invalide: ' . json_last_error_msg());
        }
        
        if (isset($input['cart']) && isset($input['restaurantId'])) {
            // Nettoyer et valider les données du panier
            $cleanedCart = [];
            foreach ($input['cart'] as $item) {
                $cleanedItem = [
                    'id' => filter_var($item['id'], FILTER_SANITIZE_STRING),
                    'productId' => filter_var($item['productId'], FILTER_VALIDATE_INT),
                    'name' => filter_var($item['name'], FILTER_SANITIZE_STRING),
                    'basePrice' => filter_var($item['basePrice'], FILTER_VALIDATE_FLOAT),
                    'quantity' => filter_var($item['quantity'], FILTER_VALIDATE_INT),
                    'totalPrice' => filter_var($item['totalPrice'], FILTER_VALIDATE_FLOAT),
                    'image' => filter_var($item['image'], FILTER_SANITIZE_URL),
                    'addedAt' => filter_var($item['addedAt'], FILTER_SANITIZE_STRING)
                ];
                
                // Nettoyer les options si elles existent
                if (isset($item['options']) && is_array($item['options'])) {
                    $cleanedItem['options'] = [];
                    foreach ($item['options'] as $option) {
                        $cleanedItem['options'][] = [
                            'id' => filter_var($option['id'], FILTER_SANITIZE_STRING),
                            'name' => filter_var($option['name'], FILTER_SANITIZE_STRING),
                            'price' => filter_var($option['price'], FILTER_VALIDATE_FLOAT)
                        ];
                    }
                }
                
                $cleanedCart[] = $cleanedItem;
            }
            
            $_SESSION['cart'] = $cleanedCart;
            $_SESSION['restaurant_id'] = filter_var($input['restaurantId'], FILTER_VALIDATE_INT);
            
            error_log('Panier sauvegardé: ' . count($_SESSION['cart']) . ' articles');
            
            echo json_encode([
                'success' => true, 
                'message' => 'Panier sauvegardé avec succès',
                'cart_count' => count($_SESSION['cart'])
            ]);
            exit;
        } else {
            throw new Exception('Données manquantes: cart ou restaurantId');
        }
    } catch (Exception $e) {
        error_log('Erreur save_cart: ' . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage(),
            'received_data' => $input
        ]);
        exit;
    }
}

echo json_encode([
    'success' => false, 
    'error' => 'Méthode non autorisée'
]);