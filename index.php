<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';

// Fonctions utilitaires
function safeOutput($data, $maxLength = 255) {
    $data = trim($data);
    if (strlen($data) > $maxLength) {
        $data = substr($data, 0, $maxLength);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Récupération des restaurants
try {
    $db = connectDB();
    $stmt = $db->prepare("
        SELECT 
            r.id, r.name, r.description, r.address, r.phone, 
            r.image_url, '' as logo_url, '' as chef_name, 0 as michelin_stars,
            IFNULL(NULL, 14.6928 + (RAND() - 0.5) * 0.1) as lat,
            IFNULL(NULL, -17.4467 + (RAND() - 0.5) * 0.1) as lng,
            COUNT(m.id) as menu_count,
            (SELECT AVG(5) FROM DUAL) as avg_rating
        FROM restaurants r
        LEFT JOIN menus m ON r.id = m.restaurant_id AND m.is_available = 1
        GROUP BY r.id, r.name, r.description, r.address, r.phone, r.image_url
        ORDER BY r.created_at DESC
        LIMIT 12
    ");
    $stmt->execute();
    $restaurants = $stmt->fetchAll();
    
    $cuisines = $db->query("SELECT DISTINCT category FROM menus WHERE category IS NOT NULL LIMIT 10")->fetchAll();
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Erreur DB: " . $e->getMessage());
    $restaurants = [];
    $cuisines = [];
    $db_error = "Désolé, notre service est momentanément indisponible. Veuillez nous excuser pour ce contretemps.";
}
// Après la récupération des restaurants, ajoutez la récupération des menus

    // ... votre code existant pour les restaurants ...
    
// Récupération des menus (2 menus maximum par restaurant)
try {
    $compactMenus = [];
    
    // Récupérer tous les IDs de restaurants
    $restaurantIds = $db->query("SELECT id FROM restaurants")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($restaurantIds as $restaurantId) {
        $stmt = $db->prepare("
            SELECT m.id, m.name, m.price, m.image_url, r.name as restaurant_name, m.updated_at
            FROM menus m
            JOIN restaurants r ON m.restaurant_id = r.id
            WHERE m.restaurant_id = ? AND m.is_available = 1
            ORDER BY RAND()
            LIMIT 2
        ");
        $stmt->execute([$restaurantId]);
        $menus = $stmt->fetchAll();
        
        foreach ($menus as &$menu) {
            // Utiliser la même logique que dans restaurant.php
            $menu['image_url'] = getProductImage($menu['id'], $menu['image_url']);
        }
        
        $compactMenus = array_merge($compactMenus, $menus);
    }
} catch (PDOException $e) {
    error_log("Erreur récupération menus: " . $e->getMessage());
    $compactMenus = [];
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="max-age=86400, public">
    <meta name="description" content="Découvrez une expérience culinaire d'exception avec la livraison à domicile des meilleurs restaurants gastronomiques">
    <title>RestoPremium - Livraison de restaurants gastronomiques</title>
    
    <!-- Preload -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="<?= BASE_URL ?>assets/css/style.css" as="style.css">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="font" crossorigin>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar.css">
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= BASE_URL ?>assets/favicon/favicon-16x16.png">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  
    
    <style>
    :root {
        --primary-color: #ff6b35;
        --secondary-color: #4ECDC4;
        --dark-color: #292F36;
        --light-color: #F7FFF7;
        --accent-color: #EF476F;
        --overlay-dark: rgba(0, 0, 0, 0.7);
        --overlay-light: rgba(0, 0, 0, 0.5);
    }
    
    * {
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Poppins', 'Segoe UI', sans-serif;
        background-color: #f8f9fa;
        padding-top: 80px;
        color: #333;
        line-height: 1.6;
        margin: 0;
        position: relative;
    }
    
    /* Navigation */
    .navbar {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .navbar-brand {
        font-weight: 700;
        color: var(--primary-color) !important;
    }
    
    .nav-link {
        font-weight: 500;
    }
    
    /* Hero Section */
    .hero-section {
        background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
            url('assets/img/acceuil.png') no-repeat center center;
        background-size: cover;
        height: 60vh;
        min-height: 400px;
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
        margin-bottom: 2rem;
    }
    
    .hero-content {
        color: white;
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .hero-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    
    .hero-subtitle {
        font-size: 1.2rem;
        margin-bottom: 2rem;
        font-weight: 300;
    }
    
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        padding: 0.8rem 2rem;
        font-weight: 600;
        border-radius: 6px;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background-color: #e55a2b;
        border-color: #e55a2b;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(229, 90, 43, 0.3);
    }
    
    /* Restaurant Grid - Cards cliquables et taille réduite */
    .resto-grid {
        display: grid;
        grid-template-columns: repeat(1, 1fr);
        gap: 1.5rem;
        padding: 0 1rem;
    }
    
    @media (min-width: 576px) {
        .resto-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (min-width: 768px) {
        .resto-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    @media (min-width: 992px) {
        .resto-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    
    .resto-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        cursor: pointer;
    }
    
    .resto-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    
    .resto-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
        height: 100%;
    }
    
    .resto-media {
        position: relative;
        overflow: hidden;
        height: 180px;
    }
    
    .resto-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .resto-card:hover .resto-img {
        transform: scale(1.05);
    }
    
    .premium-badge {
        position: absolute;
        top: 15px;
        left: 0;
        background: var(--primary-color);
        color: white;
        padding: 0.4rem 1rem;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    
    .resto-body {
        padding: 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .resto-title {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--dark-color);
    }
    
    .resto-description {
        color: #666;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex-grow: 1;
    }
    
    .resto-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
    }
    
    .resto-rating {
        display: flex;
        align-items: center;
        color: #FFC107;
        font-weight: 600;
    }
    
    .resto-rating i {
        margin-right: 0.3rem;
    }
    
    .view-btn {
        background-color: transparent;
        color: var(--primary-color);
        border: 1px solid var(--primary-color);
        padding: 0.4rem 0.8rem;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .view-btn:hover {
        background-color: var(--primary-color);
        color: white;
    }
    
    /* Section Title */
    .section-title {
        text-align: center;
        margin-bottom: 3rem;
        padding: 0 1rem;
    }
    
    .section-title h2 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--dark-color);
    }
    
    .section-title p {
        color: #666;
        font-size: 1.1rem;
    }
    
    /* Footer */
    .luxury-footer {
        background-color: var(--dark-color);
        color: white;
        padding: 3rem 0 2rem;
        margin-top: 4rem;
    }
    
    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }
    
    .footer-logo {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: var(--primary-color);
    }
    
    .footer-links {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .footer-links h4 {
        color: var(--primary-color);
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }
    
    .footer-links a {
        color: #ccc;
        margin-bottom: 0.8rem;
        display: block;
        transition: color 0.3s ease;
        text-decoration: none;
        font-size: 0.9rem;
    }
    
    .footer-links a:hover {
        color: var(--primary-color);
    }
    
    .social-links {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .social-links a {
        color: white;
        font-size: 1.2rem;
        transition: color 0.3s ease;
    }
    
    .social-links a:hover {
        color: var(--primary-color);
    }
    
    .copyright {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 1.5rem;
        text-align: center;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .hero-section {
            height: 50vh;
            min-height: 350px;
        }
        
        .hero-title {
            font-size: 2rem;
        }
        
        .hero-subtitle {
            font-size: 1rem;
        }
        
        .footer-links {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .resto-grid {
            grid-template-columns: 1fr;
        }
        
        .resto-media {
            height: 160px;
        }
        
        .resto-body {
            padding: 1rem;
        }
        
        .resto-title {
            font-size: 1.1rem;
        }
        
        .resto-description {
            font-size: 0.85rem;
        }
    }
     /* Barre de recherche */
       /* Barre de recherche simplifiée */
       .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin-top: 2.5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .search-input {
            flex: 2;
            min-width: 250px;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.5);
        }
        
        .search-select {
            flex: 1;
            min-width: 150px;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }
        
        .search-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.5);
        }
        
        .search-btn {
            flex: 1;
            min-width: 150px;
            padding: 1rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .search-btn:hover {
            background-color: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(229, 90, 43, 0.4);
        }
        /* Suggestions de recherche */
        .search-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            justify-content: center;
            margin-top: 1.5rem;
        }
        
        .suggestion-tag {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .suggestion-tag:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }
        
        /* Animation de flèche pour indiquer le défilement */
        .scroll-indicator {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 2rem;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0) translateX(-50%);
            }
            40% {
                transform: translateY(-20px) translateX(-50%);
            }
            60% {
                transform: translateY(-10px) translateX(-50%);
            }
        }
        
        /* Media Queries pour le responsive */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.8rem;
            }
            
            .hero-subtitle {
                font-size: 1.3rem;
            }
            
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input, .search-select, .search-btn {
                width: 60%;
                flex: none;
            }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                height: auto;
                min-height: 800px;
                padding: 4rem 0;
            }
            
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .btn-primary {
                padding: 0.8rem 2rem;
                font-size: 1rem;
            }
            
            .search-container {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .hero-content {
                padding: 1rem;
            }
            
            .hero-title {
                font-size: 1.8rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
                margin-bottom: 2rem;
            }
            
            .search-title {
                font-size: 1.1rem;
            }
            
            .search-input, .search-select, .search-btn {
                padding: 0.7rem 1.2rem;
            }
        }
        /* Section du slider automatique */
/* Menu déroulant compact - Images agrandies */
.compact-menu-slider {
    padding: 0.8rem 0;
    background: linear-gradient(to right, #f8f9fa, #fff, #f8f9fa);
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    margin: 2rem auto;
    width: 100%;
}

.compact-slider-container {
    position: relative;
    margin: 0 auto;
    overflow: hidden;
}

.compact-slider-track {
    display: flex;
    transition: transform 0.4s ease;
    gap: 0.8rem;
    padding: 0.5rem 0;
}

.compact-slider-item {
    flex: 0 0 auto;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    padding: 0;
    display: flex;
    flex-direction: column;
    min-width: 180px;
    transition: all 0.3s ease;
    border: 1px solid #eee;
    overflow: hidden;
}

.compact-slider-item:hover {
    box-shadow: 0 4px 10px rgba(0,0,0,0.12);
    transform: translateY(-2px);
    border-color: var(--primary-color);
}

.compact-slider-img {
    width: 100%;
    height: 120px; /* Hauteur fixe pour l'image */
    overflow: hidden;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    position: relative;
}

.compact-slider-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.compact-slider-item:hover .compact-slider-img img {
    transform: scale(1.05);
}

.compact-slider-content {
    padding: 0.6rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.compact-slider-title {
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: var(--dark-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.compact-slider-price {
    font-size: 0.85rem;
    color: var(--primary-color);
    font-weight: 700;
    margin-bottom: 0.2rem;
}

.compact-slider-restaurant {
    font-size: 0.7rem;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: auto;
}

.compact-slider-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    border: 1px solid #ddd;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.compact-slider-nav:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.compact-slider-prev {
    left: -15px;
}

.compact-slider-next {
    right: -15px;
}

/* Indicateur de défilement automatique */
.compact-slider-container::after {
    content: "";
    position: absolute;
    bottom: 5px;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 3px;
    background: var(--primary-color);
    border-radius: 2px;
    opacity: 0.7;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 0.4; }
    50% { opacity: 0.8; }
    100% { opacity: 0.4; }
}

/* Style de secours si l'image n'est pas trouvée */
.compact-slider-img:before {
    content: "🍕";
    font-size: 2rem;
    color: #ccc;
    position: absolute;
    z-index: 1;
}

.compact-slider-img img {
    position: relative;
    z-index: 2;
}

/* Responsive */
@media (max-width: 1200px) {
    .compact-slider-container {
        width: 90% !important;
    }
}

@media (max-width: 992px) {
    .compact-slider-container {
        width: 95% !important;
    }
    
    .compact-slider-item {
        min-width: 160px;
    }
    
    .compact-slider-img {
        height: 110px;
    }
}

@media (max-width: 768px) {
    .compact-slider-container {
        width: 100% !important;
        padding: 0 2.5rem;
    }
    
    .compact-slider-item {
        min-width: 140px;
    }
    
    .compact-slider-img {
        height: 100px;
    }
    
    .compact-slider-nav {
        width: 30px;
        height: 30px;
        font-size: 0.8rem;
    }
}

@media (max-width: 576px) {
    .compact-slider-container {
        padding: 0 2.2rem;
    }
    
    .compact-slider-item {
        min-width: 120px;
    }
    
    .compact-slider-img {
        height: 90px;
    }
    
    .compact-slider-content {
        padding: 0.4rem;
    }
    
    .compact-slider-title {
        font-size: 0.75rem;
    }
    
    .compact-slider-price {
        font-size: 0.8rem;
    }
    
    .compact-slider-restaurant {
        font-size: 0.65rem;
    }
    
    .compact-slider-nav {
        width: 28px;
        height: 28px;
        font-size: 0.75rem;
    }
    
    .compact-slider-prev {
        left: -10px;
    }
    
    .compact-slider-next {
        right: -10px;
    }
}
</style>
    </style>
</head>
<body>
<?php include __DIR__.'/includes/nav.php';?>  
<section class="hero-section" aria-labelledby="hero-heading">
        <div class="hero-content">
            <h1 id="hero-heading" class="hero-title">L'Excellence Culinaire à Votre Porte</h1>
            <p class="hero-subtitle">Découvrez une sélection exclusive des restaurants les plus étoilés, maintenant disponibles en livraison à domicile</p>
            <a href="#restaurants" class="btn btn-primary">
                <i class="fas fa-utensils me-2"></i> Découvrir la carte
            </a>
            <div class="search-form">
                <input type="text" class="search-input" placeholder="Entrez votre adresse ou code postal">
                <select class="search-select">
                    <option value="">Rayon de recherche</option>
                    <option value="1">1 km</option>
                    <option value="3">3 km</option>
                    <option value="5">5 km</option>
                    <option value="10">10 km</option>
                </select>
                <button class="search-btn">
                    <i class="fas fa-search me-2"></i> Rechercher
                </button>
            </div>
        </div>


                <div class="search-suggestions">
                    <span class="suggestion-tag">Sud-Stade</span>
                    <span class="suggestion-tag">Som</span>
                    <span class="suggestion-tag">Thiès-Ville</span>
                    
                </div>
            </div>
        </div>
        
        <!-- Indicateur de défilement -->
        <div class="scroll-indicator">
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>
        
        <!-- Indicateur de défilement -->
        <div class="scroll-indicator">
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>
<!-- Menu déroulant compact avec images agrandies -->
<section class="compact-menu-slider" aria-label="Menus populaires">
    <div class="container-fluid px-0">
        <div class="compact-slider-container" style="width: 80%; margin: 0 auto;">
            <div class="compact-slider-track" id="compactSliderTrack">
                <?php if(!empty($compactMenus)): ?>
                    <?php foreach($compactMenus as $menu): ?>
                        <div class="compact-slider-item">
                            <div class="compact-slider-img">
                                <?php 
                                $imageUrl = !empty($menu['image_url']) ? $menu['image_url'] : BASE_URL . 'assets/img/placeholder-food.jpg';
                                // Ajouter un timestamp pour éviter le cache
                                $imageUrl .= (strpos($imageUrl, '?') === false ? '?' : '&') . 'v=' . time();
                                ?>
                                <img src="<?= $imageUrl ?>" 
                                     alt="<?= htmlspecialchars($menu['name']) ?>"
                                     onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/img/placeholder-food.jpg?v=<?= time() ?>'">
                            </div>
                            <div class="compact-slider-content">
                                <div class="compact-slider-title"><?= htmlspecialchars($menu['name']) ?></div>
                                <div class="compact-slider-price"><?= number_format($menu['price'], 2) ?> €</div>
                                <div class="compact-slider-restaurant"><?= htmlspecialchars($menu['restaurant_name']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-3 w-100">
                        <p class="text-muted m-0">Aucun menu disponible pour le moment</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if(!empty($compactMenus) && count($compactMenus) > 3): ?>
                <button class="compact-slider-nav compact-slider-prev" aria-label="Précédent">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="compact-slider-nav compact-slider-next" aria-label="Suivant">
                    <i class="fas fa-chevron-right"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>
</section>
    <!-- Main Content -->
    <main class="container py-4" id="restaurants">
        <div class="section-title">
            <h2>Nos Établissements Partenaires</h2>
            <p class="text-muted">Une sélection des meilleures tables</p>
        </div>
        
        <?php if(isset($db_error)): ?>
            <div class="alert alert-warning text-center">
                <?= safeOutput($db_error) ?>
            </div>
        <?php endif; ?>
        
        <div class="resto-grid">
            <?php if(!empty($restaurants)): ?>
                <?php foreach ($restaurants as $resto): ?>
                    <div class="resto-card">
                        <a href="restaurant.php?id=<?= $resto['id'] ?>" class="resto-card-link">
                            <div class="resto-media">
                                <img src="<?= getRestaurantImage($resto['id'], $resto['image_url']) ?>" 
                                     class="resto-img" 
                                     alt="<?= safeOutput($resto['name']) ?>"
                                     loading="lazy"
                                     onerror="this.onerror=null;this.src='<?= getRestaurantImage(0) ?>'">
                                <div class="premium-badge">PREMIUM</div>
                            </div>
                            <div class="resto-body">
                                <h3 class="resto-title"><?= safeOutput($resto['name']) ?></h3>
                                <p class="resto-description"><?= safeOutput($resto['description']) ?></p>
                                <div class="resto-footer">
                                    <div class="resto-rating">
                                        <i class="fas fa-star"></i>
                                        <span>4.8</span>
                                    </div>
                                    <span class="view-btn">Voir le menu</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        Aucun restaurant premium disponible pour le moment.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php include __DIR__.'/includes/footer.php'; ?>
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scroll pour les ancres
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Animation des cartes au scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = 1;
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.resto-card').forEach(card => {
            card.style.opacity = 0;
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });
    });
    </script>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll pour le bouton
        document.querySelector('.btn-primary').addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({
                    top: target.offsetTop,
                    behavior: 'smooth'
                });
            }
        });
        
        // Fonctionnalité de recherche
        const searchForm = document.querySelector('.search-form');
        const searchInput = document.querySelector('.search-input');
        const searchSelect = document.querySelector('.search-select');
        const searchBtn = document.querySelector('.search-btn');
        const suggestionTags = document.querySelectorAll('.suggestion-tag');
        
        // Gestion de la soumission du formulaire
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
        
        // Gestion du clic sur le bouton de recherche
        searchBtn.addEventListener('click', function() {
            performSearch();
        });
        
        // Gestion des suggestions de recherche
        suggestionTags.forEach(tag => {
            tag.addEventListener('click', function() {
                searchInput.value = this.textContent;
                performSearch();
            });
        });
        
        // Fonction de recherche
        function performSearch() {
            const location = searchInput.value.trim();
            const radius = searchSelect.value;
            
            if (!location) {
                alert('Veuillez entrer une adresse ou un code postal');
                searchInput.focus();
                return;
            }
            
            // Simulation de recherche
            const message = radius ? 
                `Recherche de restaurants près de ${location} dans un rayon de ${radius} km...` :
                `Recherche de restaurants près de ${location}...`;
                
            alert(message);
            // Ici, vous intégreriez normalement votre logique de recherche réelle
        }
        
        // Animation d'apparition des éléments
        document.addEventListener('DOMContentLoaded', function() {
            const heroContent = document.querySelector('.hero-content');
            heroContent.style.opacity = '0';
            heroContent.style.transform = 'translateY(20px)';
            heroContent.style.transition = 'opacity 1s ease, transform 1s ease';
            
            setTimeout(() => {
                heroContent.style.opacity = '1';
                heroContent.style.transform = 'translateY(0)';
            }, 300);
        });
    </script>
    <script>
// Script pour le menu déroulant compact avec images agrandies
document.addEventListener('DOMContentLoaded', function() {
    const compactSliderTrack = document.getElementById('compactSliderTrack');
    const compactPrevBtn = document.querySelector('.compact-slider-prev');
    const compactNextBtn = document.querySelector('.compact-slider-next');
    
    if (!compactSliderTrack) return;
    
    const sliderItems = compactSliderTrack.querySelectorAll('.compact-slider-item');
    if (sliderItems.length <= 3) {
        if (compactPrevBtn) compactPrevBtn.style.display = 'none';
        if (compactNextBtn) compactNextBtn.style.display = 'none';
        return;
    }
    
    let currentIndex = 0;
    let slideInterval;
    const itemsPerSlide = 3;
    
    function updateSliderPosition() {
        const itemWidth = sliderItems[0].offsetWidth + 
                         parseInt(getComputedStyle(compactSliderTrack).gap);
        compactSliderTrack.style.transform = `translateX(-${currentIndex * itemWidth * itemsPerSlide}px)`;
    }
    
    function nextSlide() {
        const maxIndex = Math.ceil(sliderItems.length / itemsPerSlide) - 1;
        
        if (currentIndex >= maxIndex) {
            currentIndex = 0;
        } else {
            currentIndex++;
        }
        
        updateSliderPosition();
        restartAutoSlide();
    }
    
    function prevSlide() {
        const maxIndex = Math.ceil(sliderItems.length / itemsPerSlide) - 1;
        
        if (currentIndex <= 0) {
            currentIndex = maxIndex;
        } else {
            currentIndex--;
        }
        
        updateSliderPosition();
        restartAutoSlide();
    }
    
    function startAutoSlide() {
        slideInterval = setInterval(nextSlide, 5000);
    }
    
    function restartAutoSlide() {
        clearInterval(slideInterval);
        startAutoSlide();
    }
    
    // Événements
    if (compactNextBtn) {
        compactNextBtn.addEventListener('click', nextSlide);
    }
    
    if (compactPrevBtn) {
        compactPrevBtn.addEventListener('click', prevSlide);
    }
    
    // Pause au survol
    compactSliderTrack.addEventListener('mouseenter', () => {
        clearInterval(slideInterval);
    });
    
    compactSliderTrack.addEventListener('mouseleave', startAutoSlide);
    
    // Redimensionnement
    window.addEventListener('resize', updateSliderPosition);
    
    // Démarrer le défilement automatique
    startAutoSlide();
});
</script>
</body>
</html>