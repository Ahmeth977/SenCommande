<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin()) {
    http_response_code(403);
    exit('Accès interdit');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurantId = (int)$_POST['restaurant_id'];
    $uploadDir = __DIR__.'/../assets/img/restos/logos/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png'];
    if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
        $_SESSION['error'] = "Seuls les formats JPG et PNG sont acceptés";
        header("Location: edit_restaurant.php?id=$restaurantId");
        exit;
    }

    $filename = 'logo_'.$restaurantId.'.jpg';
    $targetPath = $uploadDir.$filename;

    if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
        if ($_FILES['logo']['type'] === 'image/png') {
            $image = imagecreatefrompng($targetPath);
            imagejpeg($image, $targetPath, 90);
            imagedestroy($image);
        }

        $db->prepare("UPDATE restaurants SET logo_url = ? WHERE id = ?")
           ->execute(["assets/img/restos/logos/".$filename, $restaurantId]);
        
        $_SESSION['success'] = "Logo mis à jour avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de l'upload du logo";
    }
    
    header("Location: edit_restaurant.php?id=$restaurantId");
    exit;
}