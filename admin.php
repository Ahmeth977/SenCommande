<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';

// Vérification de l'authentification admin
session_start();
if (!isset($_SESSION['user_id']) {
    header("Location: login.php");
    exit();
}

// Vérification du rôle admin
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Gestion des actions
$action = $_GET['action'] ?? '';
$restaurantId = $_GET['id'] ?? 0;

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_restaurant'])) {
        // Ajout d'un nouveau restaurant
        $name = $_POST['name'];
        $description = $_POST['description'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        
        // Gestion de l'upload d'image
        $imageUrl = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__.'/assets/img/restaurants/';
            $fileName = uniqid().'_'.basename($_FILES['image']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $imageUrl = BASE_URL.'assets/img/restaurants/'.$fileName;
            }
        }
        
        $stmt = $db->prepare("INSERT INTO restaurants (user_id, name, description, address, phone, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $name, $description, $address, $phone, $imageUrl]);
        
        header("Location: admin.php?success=restaurant_added");
        exit();
    }
    elseif (isset($_POST['update_restaurant'])) {
        // Mise à jour d'un restaurant existant
        $name = $_POST['name'];
        $description = $_POST['description'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        
        // Gestion de l'upload d'image
        $imageUrl = $_POST['current_image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__.'/assets/img/restaurants/';
            $fileName = uniqid().'_'.basename($_FILES['image']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                // Supprimer l'ancienne image si elle existe
                if ($imageUrl) {
                    $oldImage = parse_url($imageUrl, PHP_URL_PATH);
                    $oldImagePath = __DIR__.$oldImage;
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $imageUrl = BASE_URL.'assets/img/restaurants/'.$fileName;
            }
        }
        
        $stmt = $db->prepare("UPDATE restaurants SET name = ?, description = ?, address = ?, phone = ?, image_url = ? WHERE id = ?");
        $stmt->execute([$name, $description, $address, $phone, $imageUrl, $restaurantId]);
        
        header("Location: admin.php?success=restaurant_updated");
        exit();
    }
    elseif (isset($_POST['add_product'])) {
        // Ajout d'un nouveau produit
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category = $_POST['category'];
        $restaurantId = $_POST['restaurant_id'];
        
        // Gestion de l'upload d'image
        $imageUrl = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__.'/assets/img/products/';
            $fileName = uniqid().'_'.basename($_FILES['image']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $imageUrl = BASE_URL.'assets/img/products/'.$fileName;
            }
        }
        
        $stmt = $db->prepare("INSERT INTO menus (restaurant_id, name, description, price, category, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$restaurantId, $name, $description, $price, $category, $imageUrl]);
        
        header("Location: admin.php?action=manage_products&id=$restaurantId&success=product_added");
        exit();
    }
    elseif (isset($_POST['update_product'])) {
        // Mise à jour d'un produit existant
        $productId = $_POST['product_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category = $_POST['category'];
        
        // Gestion de l'upload d'image
        $imageUrl = $_POST['current_image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__.'/assets/img/products/';
            $fileName = uniqid().'_'.basename($_FILES['image']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                // Supprimer l'ancienne image si elle existe
                if ($imageUrl) {
                    $oldImage = parse_url($imageUrl, PHP_URL_PATH);
                    $oldImagePath = __DIR__.$oldImage;
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $imageUrl = BASE_URL.'assets/img/products/'.$fileName;
            }
        }
        
        $stmt = $db->prepare("UPDATE menus SET name = ?, description = ?, price = ?, category = ?, image_url = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $category, $imageUrl, $productId]);
        
        header("Location: admin.php?action=manage_products&id=$restaurantId&success=product_updated");
        exit();
    }
    elseif (isset($_POST['delete_product'])) {
        // Suppression d'un produit
        $productId = $_POST['product_id'];
        $restaurantId = $_POST['restaurant_id'];
        
        // Récupérer l'image pour la supprimer
        $stmt = $db->prepare("SELECT image_url FROM menus WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if ($product && $product['image_url']) {
            $imagePath = __DIR__.parse_url($product['image_url'], PHP_URL_PATH);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM menus WHERE id = ?");
        $stmt->execute([$productId]);
        
        header("Location: admin.php?action=manage_products&id=$restaurantId&success=product_deleted");
        exit();
    }
}

// Récupération des données
$restaurants = $db->query("SELECT * FROM restaurants ORDER BY name")->fetchAll();

if ($action === 'edit_restaurant' && $restaurantId) {
    $stmt = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurantId]);
    $restaurant = $stmt->fetch();
}

if ($action === 'manage_products' && $restaurantId) {
    $stmt = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurantId]);
    $restaurant = $stmt->fetch();
    
    $products = $db->prepare("SELECT * FROM menus WHERE restaurant_id = ? ORDER BY category, name");
    $products->execute([$restaurantId]);
    $products = $products->fetchAll();
}

if ($action === 'edit_product' && isset($_GET['product_id'])) {
    $productId = $_GET['product_id'];
    $stmt = $db->prepare("SELECT * FROM menus WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - YonniMa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --topbar-height: 60px;
            --primary-color: #D4AF37;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: var(--topbar-height);
        }
        
        .admin-sidebar {
            position: fixed;
            top: var(--topbar-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--topbar-height));
            background: var(--dark-color);
            color: white;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            border-left: 3px solid transparent;
        }
        
        .admin-sidebar .nav-link:hover, 
        .admin-sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left: 3px solid var(--primary-color);
        }
        
        .admin-sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        .admin-main {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }
        
        .admin-topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--topbar-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1001;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
        
        .admin-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .resto-img-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .product-img-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .btn-gold {
            background-color: var(--primary-color);
            color: #333;
            border: none;
        }
        
        .btn-gold:hover {
            background-color: #c9a227;
            color: #333;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table th {
            font-weight: 600;
            border-top: none;
        }
        
        .table td, .table th {
            vertical-align: middle;
        }
        
        @media (max-width: 992px) {
            .admin-sidebar {
                left: calc(-1 * var(--sidebar-width));
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .sidebar-toggled .admin-sidebar {
                left: 0;
            }
            
            .sidebar-toggled .admin-main {
                margin-left: var(--sidebar-width);
            }
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            display: block;
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="admin-topbar bg-white shadow-sm">
        <button class="btn btn-sm d-lg-none" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="ms-auto d-flex align-items-center">
            <span class="me-3"><?= $_SESSION['username'] ?? 'Admin' ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header p-3 d-flex align-items-center">
            <h5 class="mb-0">Administration</h5>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= ($action === '' || $action === 'list_restaurants') ? 'active' : '' ?>" href="admin.php">
                    <i class="fas fa-utensils"></i> Restaurants
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $action === 'add_restaurant' ? 'active' : '' ?>" href="admin.php?action=add_restaurant">
                    <i class="fas fa-plus-circle"></i> Ajouter un restaurant
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $action === 'manage_products' ? 'active' : '' ?>" href="admin.php?action=manage_products">
                    <i class="fas fa-list"></i> Gérer les produits
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="admin-main" id="adminMain">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                switch ($_GET['success']) {
                    case 'restaurant_added':
                        echo "Restaurant ajouté avec succès!";
                        break;
                    case 'restaurant_updated':
                        echo "Restaurant mis à jour avec succès!";
                        break;
                    case 'product_added':
                        echo "Produit ajouté avec succès!";
                        break;
                    case 'product_updated':
                        echo "Produit mis à jour avec succès!";
                        break;
                    case 'product_deleted':
                        echo "Produit supprimé avec succès!";
                        break;
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Liste des restaurants -->
        <?php if ($action === '' || $action === 'list_restaurants'): ?>
            <div class="card admin-card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des restaurants</h5>
                    <a href="admin.php?action=add_restaurant" class="btn btn-sm btn-gold">
                        <i class="fas fa-plus me-1"></i> Ajouter
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Nom</th>
                                    <th>Adresse</th>
                                    <th>Téléphone</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($restaurants as $resto): ?>
                                <tr>
                                    <td>
                                        <?php if ($resto['image_url']): ?>
                                            <img src="<?= $resto['image_url'] ?>" class="resto-img-thumb" alt="<?= htmlspecialchars($resto['name']) ?>">
                                        <?php else: ?>
                                            <div class="resto-img-thumb bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-utensils text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($resto['name']) ?></td>
                                    <td><?= htmlspecialchars($resto['address']) ?></td>
                                    <td><?= htmlspecialchars($resto['phone']) ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="admin.php?action=edit_restaurant&id=<?= $resto['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="admin.php?action=manage_products&id=<?= $resto['id'] ?>" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-list"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Formulaire d'ajout/modification de restaurant -->
        <?php if ($action === 'add_restaurant' || $action === 'edit_restaurant'): ?>
            <div class="card admin-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <?= ($action === 'add_restaurant') ? 'Ajouter un restaurant' : 'Modifier le restaurant' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($action === 'edit_restaurant'): ?>
                            <input type="hidden" name="current_image" value="<?= $restaurant['image_url'] ?? '' ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du restaurant</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= $restaurant['name'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required><?= $restaurant['description'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?= $restaurant['address'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= $restaurant['phone'] ?? '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Image du restaurant</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            
                            <?php if ($action === 'edit_restaurant' && !empty($restaurant['image_url'])): ?>
                                <img src="<?= $restaurant['image_url'] ?>" class="preview-image mt-2" id="currentImagePreview">
                            <?php else: ?>
                                <img src="" class="preview-image mt-2 d-none" id="imagePreview">
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="admin.php" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" name="<?= ($action === 'add_restaurant') ? 'add_restaurant' : 'update_restaurant' ?>" 
                                    class="btn btn-gold">
                                <?= ($action === 'add_restaurant') ? 'Ajouter' : 'Mettre à jour' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Gestion des produits -->
        <?php if ($action === 'manage_products' && isset($restaurant)): ?>
            <div class="card admin-card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Produits de <?= htmlspecialchars($restaurant['name']) ?></h5>
                    <a href="admin.php?action=add_product&id=<?= $restaurant['id'] ?>" class="btn btn-sm btn-gold">
                        <i class="fas fa-plus me-1"></i> Ajouter un produit
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($products)): ?>
                        <div class="alert alert-info">
                            Aucun produit disponible pour ce restaurant.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Nom</th>
                                        <th>Description</th>
                                        <th>Prix</th>
                                        <th>Catégorie</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if ($product['image_url']): ?>
                                                <img src="<?= $product['image_url'] ?>" class="product-img-thumb" alt="<?= htmlspecialchars($product['name']) ?>">
                                            <?php else: ?>
                                                <div class="product-img-thumb bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</td>
                                        <td><?= number_format($product['price'], 2) ?> €</td>
                                        <td><?= htmlspecialchars($product['category']) ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="admin.php?action=edit_product&id=<?= $restaurant['id'] ?>&product_id=<?= $product['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit?');">
                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                    <input type="hidden" name="restaurant_id" value="<?= $restaurant['id'] ?>">
                                                    <button type="submit" name="delete_product" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Formulaire d'ajout/modification de produit -->
        <?php if (($action === 'add_product' || $action === 'edit_product') && isset($restaurant)): ?>
            <div class="card admin-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <?= ($action === 'add_product') ? 'Ajouter un produit' : 'Modifier le produit' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="restaurant_id" value="<?= $restaurant['id'] ?>">
                        <?php if ($action === 'edit_product'): ?>
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="current_image" value="<?= $product['image_url'] ?? '' ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du produit</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= $product['name'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required><?= $product['description'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Prix (€)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" 
                                       value="<?= $product['price'] ?? '' ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Catégorie</label>
                                <input type="text" class="form-control" id="category" name="category" 
                                       value="<?= $product['category'] ?? '' ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Image du produit</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            
                            <?php if ($action === 'edit_product' && !empty($product['image_url'])): ?>
                                <img src="<?= $product['image_url'] ?>" class="preview-image mt-2" id="currentImagePreview">
                            <?php else: ?>
                                <img src="" class="preview-image mt-2 d-none" id="imagePreview">
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="admin.php?action=manage_products&id=<?= $restaurant['id'] ?>" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" name="<?= ($action === 'add_product') ? 'add_product' : 'update_product' ?>" 
                                    class="btn btn-gold">
                                <?= ($action === 'add_product') ? 'Ajouter' : 'Mettre à jour' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.body.classList.toggle('sidebar-toggled');
        });
        
        // Image preview for uploads
        document.getElementById('image')?.addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview') || document.getElementById('currentImagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(event) {
                    preview.src = event.target.result;
                    preview.classList.remove('d-none');
                }
                
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>