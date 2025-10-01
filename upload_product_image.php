<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Accès interdit');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)$_POST['product_id'];
    $uploadDir = __DIR__.'/../assets/img/products/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png'];
    if (!in_array($_FILES['image']['type'], $allowedTypes)) {
        die('Type de fichier non autorisé');
    }

    $filename = 'product_'.$productId.'.jpg';
    $targetPath = $uploadDir.$filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        if ($_FILES['image']['type'] === 'image/png') {
            $image = imagecreatefrompng($targetPath);
            imagejpeg($image, $targetPath, 90);
            imagedestroy($image);
        }

        $db->prepare("UPDATE menus SET image_path = ? WHERE id = ?")
           ->execute(["assets/img/products/".$filename, $productId]);
        
        header('Location: product.php?id='.$productId);
        exit;
    } else {
        die('Erreur lors de l\'upload');
    }
}