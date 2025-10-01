<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/auth.php';

if (!isset($_SESSION['user_id']) || !isAdmin()) {
    http_response_code(403);
    exit('Accès interdit');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $restaurantId = (int)$_POST['restaurant_id'];
        
        // Vérifier que le restaurant existe
        $stmt = $db->prepare("SELECT id FROM restaurants WHERE id = ?");
        $stmt->execute([$restaurantId]);
        if (!$stmt->fetch()) {
            throw new Exception("Restaurant introuvable");
        }

        $uploadDir = __DIR__.'/../assets/img/restos/logos/';
        
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception("Impossible de créer le dossier");
            }
        }

        if (!isset($_FILES['logo'])) {
            throw new Exception("Aucun fichier uploadé");
        }

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'jpg'];
        $fileType = $_FILES['logo']['type'];
        
        if (!array_key_exists($fileType, $allowedTypes)) {
            throw new Exception("Format non supporté");
        }

        $filename = 'logo_'.$restaurantId.'.'.$allowedTypes[$fileType];
        $targetPath = $uploadDir.$filename;

        // Supprimer l'ancien logo si existe
        $oldLogo = $db->prepare("SELECT logo_url FROM restaurants WHERE id = ?");
        $oldLogo->execute([$restaurantId]);
        $oldLogoPath = $oldLogo->fetchColumn();
        
        if ($oldLogoPath && file_exists(__DIR__.'/../'.$oldLogoPath)) {
            unlink(__DIR__.'/../'.$oldLogoPath);
        }

        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
            throw new Exception("Échec de l'upload");
        }

        // Conversion pour les PNG
        if ($fileType === 'image/png') {
            $image = imagecreatefrompng($targetPath);
            imagejpeg($image, $targetPath, 90);
            imagedestroy($image);
        }

        $relativePath = 'assets/img/restos/logos/'.$filename;
        $db->prepare("UPDATE restaurants SET logo_url = ? WHERE id = ?")
           ->execute([$relativePath, $restaurantId]);
        
        $_SESSION['success'] = "Logo mis à jour avec succès";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur: ".$e->getMessage();
        error_log("Upload logo error: ".$e->getMessage());
    }
    
    header("Location: edit_restaurant.php?id=$restaurantId");
    exit;
}