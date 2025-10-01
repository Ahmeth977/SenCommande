<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification ID produit
if(!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: index.php");
    exit();
}

$productId = (int)$_GET['id'];
$db = connectDB();

if(!$db) {
    die("Erreur de connexion à la base de données");
}

try {
    // Récupération données produit - VERSION CORRIGÉE
    $stmt = $db->prepare("
        SELECT m.*, r.name as restaurant_name, r.id as restaurant_id
        FROM menus m
        JOIN restaurants r ON m.restaurant_id = r.id
        WHERE m.id = ? AND m.is_available = 1
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if(!$product) {
        header("Location: index.php");
        exit();
    }

    // Gestion de l'image du produit - CORRECTION
    $productImage = getProductImage($product['id'], $product['image_url'] ?? null);
    
    // Récupération des produits similaires
    $stmt = $db->prepare("
        SELECT m.id, m.name, m.price, m.image_url
        FROM menus m
        WHERE m.restaurant_id = ? AND m.id != ? AND m.is_available = 1
        LIMIT 4
    ");
    $stmt->execute([$product['restaurant_id'], $productId]);
    $similarProducts = $stmt->fetchAll();

    // Ajout du chemin d'image pour les produits similaires
    foreach ($similarProducts as &$similarProduct) {
        $similarProduct['image'] = getProductImage($similarProduct['id'], $similarProduct['image_url']);
    }
    unset($similarProduct);

} catch (PDOException $e) {
    error_log("Erreur DB product.php: " . $e->getMessage());
    // Message plus informatif
    $errorMessage = DEV_MODE ? "Erreur: " . $e->getMessage() : "Une erreur est survenue lors du chargement du produit.";
    die($errorMessage);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | RestoPlatform</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS perso -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/nav.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    
    <style>
        :root {
            --primary-color:rgb(205, 117, 23);
            --secondary-color: #4ecdc4;
            --dark-color: #292f36;
            --light-color: #f7fff7;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 80px;
        }
        
        .product-hero {
            background-color: #f5f5f5;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .product-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .similar-product-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .similar-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .similar-product-card img {
            height: 150px;
            object-fit: cover;
            width: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.5rem 1.5rem;
        }
        
        .btn-primary:hover {
            background-color:rgb(221, 124, 13);
            border-color:rgb(203, 152, 14);
        }
        
        @media (max-width: 768px) {
            .product-image {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__.'/includes/nav.php'; ?>

    <main class="container mb-5">
        <div class="product-hero">
            <div class="row">
                <!-- Image du produit -->
                <div class="col-md-6 mb-4 mb-md-0">
                <img src="<?= getProductImage($product['id'], $product['image_url']) ?>" 
     class="card-img-top" 
     alt="<?= htmlspecialchars($menu['name']) ?>"
     loading="lazy">
                </div>
                
                <!-- Détails du produit -->
                <div class="col-md-6">
                    <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($product['name']) ?></h1>
                    
                    <div class="d-flex align-items-center mb-3">
                        <span class="text-primary fw-bold fs-3 me-3">
                            <?= number_format($product['price'], 2) ?> €
                        </span>
                        <a href="restaurant.php?id=<?= $product['restaurant_id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-store me-1"></i> <?= htmlspecialchars($product['restaurant_name']) ?>
                        </a>
                    </div>
                    
                    <?php if(!empty($product['category'])): ?>
                    <span class="badge bg-secondary mb-3">
                        <?= htmlspecialchars($product['category']) ?>
                    </span>
                    <?php endif; ?>
                    
                    <p class="lead mb-4"><?= htmlspecialchars($product['description']) ?></p>
                    
                    <div class="d-flex gap-3">
                        <button class="btn btn-primary btn-lg">
                            <i class="fas fa-cart-plus me-2"></i> Ajouter au panier
                            <button class="btn btn-primary btn-lg" id="addToCartBtn" data-product-id="<?= $product['id'] ?>">
    <i class="fas fa-cart-plus me-2"></i> Ajouter au panier (<?= number_format($product['price'], 2) ?> €)
</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="quantity-selector mb-4">
    <label class="form-label">Quantité :</label>
    <div class="input-group" style="width: 150px;">
        <button class="btn btn-outline-secondary quantity-minus" type="button">-</button>
        <input type="number" class="form-control text-center" value="1" min="1" id="quantity">
        <button class="btn btn-outline-secondary quantity-plus" type="button">+</button>
    </div>
</div>
        
        <!-- Produits similaires -->
        <?php if(!empty($similarProducts)): ?>
        <section class="mt-5">
            <h3 class="mb-4">
                <i class="fas fa-utensils me-2 text-primary"></i>
                Autres produits de ce restaurant
            </h3>
            
            <div class="row g-4">
                <?php foreach($similarProducts as $similar): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="product.php?id=<?= $similar['id'] ?>" class="text-decoration-none text-dark">
                        <div class="card similar-product-card h-100">
                            <img src="<?= $similar['image'] ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($similar['name']) ?>"
                                 loading="lazy">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($similar['name']) ?></h5>
                                <p class="card-text text-primary fw-bold">
                                    <?= number_format($similar['price'], 2) ?> €
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <?php include __DIR__.'/includes/footer.php'; ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts personnalisés -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    // Gestion des quantités
    const quantityInput = document.getElementById('quantity');
    const minusBtn = document.querySelector('.quantity-minus');
    const plusBtn = document.querySelector('.quantity-plus');
    
    minusBtn?.addEventListener('click', () => quantityInput.value = Math.max(1, quantityInput.value - 1));
    plusBtn?.addEventListener('click', () => quantityInput.value = parseInt(quantityInput.value) + 1);
    
    // Ajout au panier avec AJAX
    document.getElementById('addToCartBtn')?.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const quantity = document.getElementById('quantity').value;
        
        fetch('/api/cart/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ productId, quantity })
        })
        .then(response => response.json())
        .then(data => {
            // Mise à jour visuelle du panier
            updateCartCounter(data.totalItems);
            showToast('Produit ajouté au panier !');
        });
    });
    
    function updateCartCounter(count) {
        const counter = document.querySelector('.cart-counter');
        if (counter) counter.textContent = count;
    }
    
    function showToast(message) {
        // Implémentez un système de notification toast
    }
});
    </script>
</body>
</html>"