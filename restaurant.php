<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';


// Activer l'affichage des erreurs pour le debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification ID restaurant
if(!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: index.php");
    exit();
}

$restoId = (int)$_GET['id'];
$db = connectDB();
$isGuest = false;
if (!isset($_SESSION['user_id'])) {
    // Créer un identifiant de session invité si nécessaire
    if (!isset($_SESSION['guest_id'])) {
        $_SESSION['guest_id'] = 'guest_' . uniqid() . '_' . time();
    }
    $isGuest = true;
}

if (!$db) {
    die("Erreur de connexion à la base de données");
}

try {
    // Récupération des informations du restaurant avec les notes
    $stmt = $db->prepare("
        SELECT r.*, 
               AVG(rev.rating) as avg_rating, 
               COUNT(rev.id) as review_count
        FROM restaurants r 
        LEFT JOIN reviews rev ON r.id = rev.restaurant_id 
        WHERE r.id = ?
        GROUP BY r.id
    ");
    $stmt->execute([$restoId]);
    $restaurant = $stmt->fetch();
    
    if(!$restaurant) {
        header("Location: index.php");
        exit();
    }

    // Récupération des menus (sans la colonne is_popular)
    $stmt = $db->prepare("
        SELECT m.id, m.name, m.description, m.price, m.category, m.image_url
        FROM menus m 
        WHERE m.restaurant_id = ? AND m.is_available = 1 
        ORDER BY m.category, m.name
    ");
    
    $stmt->execute([$restoId]);
    $menus = $stmt->fetchAll();
    
    // Organiser par catégories
    $categories = [];
    
    foreach($menus as $menu) {
        $category = $menu['category'] ?? 'Non classé';
        
        if (!isset($categories[$category])) {
            $categories[$category] = [
                'name' => $category,
                'items' => [],
                'count' => 0
            ];
        }
        
        $categories[$category]['items'][] = $menu;
        $categories[$category]['count']++;
    }

} catch (PDOException $e) {
    error_log("Erreur DB restaurant.php: " . $e->getMessage());
    $errorMessage = DEV_MODE ? $e->getMessage() : "Une erreur est survenue lors du chargement du restaurant.";
    die($errorMessage);
}

// Définir les horaires d'ouverture par défaut
$defaultOpeningHours = [
    ['day' => 'Lundi', 'hours' => '11:00 - 22:00'],
    ['day' => 'Mardi', 'hours' => '11:00 - 22:00'],
    ['day' => 'Mercredi', 'hours' => '11:00 - 22:00'],
    ['day' => 'Jeudi', 'hours' => '11:00 - 22:00'],
    ['day' => 'Vendredi', 'hours' => '11:00 - 23:00'],
    ['day' => 'Samedi', 'hours' => '11:00 - 23:00'],
    ['day' => 'Dimanche', 'hours' => '11:00 - 21:00']
];

// Vérifier si les horaires sont stockés dans la table restaurants
$openingHours = $defaultOpeningHours;
if (!empty($restaurant['opening_hours'])) {
    $openingHours = json_decode($restaurant['opening_hours'], true) ?? $defaultOpeningHours;
}
 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['name']) ?> | RestoPlatform</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS perso -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/nav.css">
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
    overflow-x: hidden;
}

/* Bannière publicitaire */
.ad-banner {
    width: 100%;
    margin: 0;
    position: relative;
    z-index: 1;
    height: 300px;
    overflow: hidden;
}

.ad-banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Section informations restaurant avec image */
.restaurant-info-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 0;
    overflow: hidden;
    margin: -70px auto 2rem auto;
    position: relative;
    z-index: 2;
    width: 95%;
}

.restaurant-image-container {
    padding: 0;
    height: 100%;
}

.restaurant-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.restaurant-details {
    padding: 1.5rem;
}

.restaurant-name {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--dark-color);
}

.restaurant-description {
    color: #666;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.restaurant-contact {
    margin-bottom: 1rem;
}

.contact-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.contact-item i {
    margin-right: 10px;
    color: var(--primary-color);
    width: 18px;
    text-align: center;
}

/* Badge ouvert/fermé */
.open-status, .closed-status {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    margin-top: 0.8rem;
    font-size: 0.9rem;
}

.open-status {
    background-color: #28a745;
    color: white;
}

.closed-status {
    background-color: #dc3545;
    color: white;
}

/* CARTES DE PRODUITS */
.detail-list-box {
    margin-bottom: 1rem;
}

.detail-list {
    display: flex;
    margin-bottom: 0.8rem;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    align-items: flex-start;
}

.detail-list:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.detail-list-img {
    flex: 0 0 90px;
    margin-right: 1rem;
}

.detail-list-img .list-img {
    width: 90px;
    height: 90px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.detail-list-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.detail-list-img img:hover {
    transform: scale(1.05);
}

.detail-list-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.detail-list-text {
    flex: 1;
    min-height: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    width: 100%;
}

.detail-list-text h3 {
    font-size: 1.1rem;
    color: #333;
    font-weight: 600;
    line-height: 1.3;
    margin: 0 0 0.4rem 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.detail-list-text p {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.4rem;
    line-height: 1.4;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.detail-list-text strong {
    font-size: 1.1rem;
    color: var(--primary-color);
    font-weight: 700;
    margin-top: auto;
}

.add-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 0.5rem;
}

.add-btn button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    font-weight: 500;
}

.add-btn button:hover {
    background-color: #e55a2b;
    transform: translateY(-2px);
    box-shadow: 0 2px 6px rgba(229, 90, 43, 0.3);
}

.cust {
    font-size: 0.8rem;
    color: #666;
    margin-left: 0.5rem;
}

/* Navigation par catégories */
.category-nav {
    position: sticky;
    top: 80px;
    z-index: 1020;
    background: white;
    padding: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 10px;
    margin-bottom: 1.5rem;
    overflow-x: auto;
    white-space: nowrap;
}

.category-nav .d-flex {
    flex-wrap: nowrap;
    gap: 0.5rem;
}

.category-nav .btn {
    font-size: 0.85rem;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.category-nav .btn:hover {
    transform: translateY(-2px);
}

/* Panier fixe à droite pour ordinateur */
@media (min-width: 992px) {
    .your-cart-main {
        position: fixed;
        top: 150px;
        right: 20px;
        width: 320px;
        max-height: calc(100vh - 200px);
        overflow-y: auto;
        z-index: 1000;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        padding: 1.5rem;
    }
    
    .menu-col {
        width: calc(100% - 350px);
        padding-right: 370px;
    }
}

/* Ajustements pour les écrans moyens */
@media (max-width: 1200px) and (min-width: 992px) {
    .your-cart-main {
        width: 280px;
        right: 15px;
    }
    
    .menu-col {
        width: calc(100% - 300px);
        padding-right: 320px;
    }
}

/* Styles pour tablettes et mobiles */
@media (max-width: 992px) {
    .your-cart-main {
        position: fixed;
        background: white !important; /* Force le fond blanc */
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15) !important;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100% !important;
        max-width: 100% !important;
        height: auto;
        max-height: 70vh;
        overflow-y: auto;
        border-radius: 15px 15px 0 0 !important;
        z-index: 1050;
        transform: translateY(100%);
        transition: transform 0.3s ease-out;
        margin-bottom: 0;
    }
    
    .your-cart-main.cart-open {
        transform: translateY(0);
    }
    
    .menu-col {
        width: 100%;
        padding-right: 15px;
    }
}

.your-cart-title {
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
}

.your-cart-title h3 {
    font-size: 1.2rem;
    margin-bottom: 0.3rem;
    display: flex;
    align-items: center;
    color: var(--dark-color);
}

.your-cart-title h3 i {
    margin-right: 0.5rem;
    color: var(--primary-color);
}

.your-cart-title h6 {
    font-size: 0.9rem;
    color: #666;
}

.cart-empty {
    text-align: center;
    padding: 2rem 1rem;
}

.cart-empty img {
    max-width: 120px;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.cart-empty h6 {
    font-size: 0.9rem;
    color: #666;
    line-height: 1.4;
}

/* Étoiles de notation */
.rating-stars {
    color: #FFC107;
    font-size: 1rem;
    letter-spacing: 1px;
}

/* Onglets */
.resttabs {
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: 5px;
}

.resttabs a {
    flex-shrink: 0;
}

.resttabs button {
    padding: 0.6rem 1rem;
    border: none;
    background: #f8f9fa;
    border-radius: 6px 6px 0 0;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.resttabs a.active button {
    background: var(--primary-color);
    color: white;
}

/* Horaires */
.opening-hours {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
}

.opening-hours h3 {
    margin-bottom: 1rem;
    color: var(--dark-color);
    font-size: 1.1rem;
    display: flex;
    align-items: center;
}

.opening-hours h3 i {
    margin-right: 0.5rem;
    color: var(--primary-color);
}

.opening-hours ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.opening-hours li {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f1f1;
    font-size: 0.9rem;
}

.opening-hours li:last-child {
    border-bottom: none;
}

/* Styles pour la modal */
#productModal .modal-dialog {
    max-width: 900px;
    margin: 1rem;
}

#productModal .modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

#productModal .modal-header {
    background: var(--primary-color);
    color: white;
    border-bottom: none;
    border-radius: 15px 15px 0 0;
    padding: 1rem;
}

#productModal .modal-header .btn-close {
    filter: invert(1);
    opacity: 0.8;
    padding: 0.5rem;
    margin: -0.5rem -0.5rem -0.5rem auto;
}

#productModal .modal-header .btn-close:hover {
    opacity: 1;
}

#productModal .modal-body {
    padding: 1.5rem;
}

#productModal .modal-footer {
    border-top: 1px solid #eee;
    padding: 1rem;
    border-radius: 0 0 15px 15px;
}

#modalProductImage {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

#modalProductName {
    color: var(--dark-color);
    font-weight: 700;
    margin-bottom: 0.5rem;
    font-size: 1.3rem;
}

#modalProductDescription {
    font-size: 0.9rem;
    margin-bottom: 1rem;
    line-height: 1.4;
}

#modalProductPrice {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0.5rem 0;
    color: var(--primary-color);
}

.customization-options {
    border-top: 1px solid #eee;
    padding-top: 1rem;
    margin-top: 1rem;
}

.customization-options h5 {
    color: var(--dark-color);
    margin-bottom: 0.8rem;
    font-weight: 600;
    font-size: 1rem;
}

.form-check {
    padding-left: 1.8rem;
    margin-bottom: 0.6rem;
}

.form-check-input {
    width: 1.1em;
    height: 1.1em;
    margin-top: 0.15em;
    margin-left: -0.8rem;
}

.form-check-label {
    font-size: 0.9rem;
    color: #555;
    line-height: 1.3;
}

.quantity-selector .form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.quantity-selector .input-group {
    width: 130px;
}

.quantity-selector .input-group .btn {
    padding: 0.4rem 0.6rem;
}

.quantity-selector input {
    font-weight: bold;
    text-align: center;
    padding: 0.4rem;
}

/* Animation d'ouverture de modal */
.modal.fade .modal-dialog {
    transform: translate(0, -50px);
    transition: transform 0.3s ease-out;
}

.modal.show .modal-dialog {
    transform: translate(0, 0);
}

/* Bouton pour ouvrir/fermer le panier sur mobile */
.cart-toggle-btn {
    display: none;
}

@media (max-width: 992px) {
    .cart-toggle-btn {
        display: flex;
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1060;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        border: none;
        cursor: pointer;
    }
}

/* Overlay pour le panier mobile */
.cart-overlay {
    display: none;
}

@media (max-width: 992px) {
    .cart-open ~ .cart-overlay {
        display: block;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
    }
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.8rem 0;
    border-bottom: 1px solid #eee;
}

.cart-item-info {
    flex: 1;
}

.cart-item-name {
    font-weight: 500;
    margin-bottom: 0.2rem;
}

.cart-item-options {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.2rem;
}

.cart-item-price {
    color: var(--primary-color);
    font-weight: 600;
}

.cart-item-quantity {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-btn {
    width: 25px;
    height: 25px;
    border-radius: 50%;
    background: #f1f1f1;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    cursor: pointer;
}

.quantity-btn:hover {
    background: #e5e5e5;
}

.remove-item {
    color: #dc3545;
    background: none;
    border: none;
    cursor: pointer;
    margin-left: 0.5rem;
}

.remove-item:hover {
    color: #c82333;
}

/* Badge pour le nombre d'articles */
.cart-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--accent-color);
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: bold;
}

/* Texte sous l'icône */
.cart-toggle-text {
    font-size: 0.7rem;
    margin-top: 4px;
    font-weight: 500;
}

.restaurant-detail-row {
    margin-left: 0;
    margin-right: 0;
    width: 100%;
}

/* Style pour la description complète dans la modal */
.product-description-full {
    font-size: 0.95rem;
    color: #555;
    line-height: 1.6;
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 5px;
    border-left: 3px solid var(--primary-color);
}

/* Scrollbar personnalisée pour la description */
.product-description-full::-webkit-scrollbar {
    width: 6px;
}

.product-description-full::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.product-description-full::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 3px;
}

.product-description-full::-webkit-scrollbar-thumb:hover {
    background: #e55a2b;
}

/* RESPONSIVE DESIGN CORRIGÉ */
@media (max-width: 992px) {
    .detail-list-img {
        flex: 0 0 80px;
        margin-right: 0.8rem;
    }
    
    .detail-list-img .list-img {
        width: 80px;
        height: 80px;
    }
    
    .detail-list-text p {
        -webkit-line-clamp: 2;
    }
}

@media (max-width: 768px) {
    .detail-list {
        padding: 0.8rem;
    }
    
    .detail-list-img {
        flex: 0 0 70px;
        margin-right: 0.8rem;
    }
    
    .detail-list-img .list-img {
        width: 70px;
        height: 70px;
    }
    
    .detail-list-text h3 {
        font-size: 1rem;
    }
    
    .detail-list-text p {
        font-size: 0.85rem;
    }
    
    .detail-list-text strong {
        font-size: 1rem;
    }
    
    .add-btn button {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }
    
    .cust {
        font-size: 0.75rem;
    }
}

@media (max-width: 576px) {
    .detail-list {
        padding: 0.7rem;
    }
    
    .detail-list-img {
        flex: 0 0 60px;
        margin-right: 0.7rem;
    }
    
    .detail-list-img .list-img {
        width: 60px;
        height: 60px;
    }
    
    .detail-list-text h3 {
        font-size: 0.95rem;
        margin-bottom: 0.3rem;
    }
    
    .detail-list-text p {
        font-size: 0.8rem;
        -webkit-line-clamp: 2;
    }
    
    .detail-list-text strong {
        font-size: 0.9rem;
    }
    
    .add-btn {
        flex-direction: row;
        align-items: center;
        margin-top: 0.5rem;
    }
    
    .add-btn button {
        padding: 0.35rem 0.7rem;
        font-size: 0.8rem;
    }
    
    .cust {
        font-size: 0.7rem;
        margin-left: 0.5rem;
    }
}

@media (max-width: 400px) {
    .detail-list {
        padding: 0.8rem;
    }
    
    .detail-list-img {
        flex: 0 0 50px;
        margin-right: 0.6rem;
    }
    
    .detail-list-img .list-img {
        width: 50px;
        height: 50px;
    }
    
    .detail-list-text h3 {
        font-size: 0.9rem;
    }
    
    .detail-list-text p {
        font-size: 0.75rem;
        -webkit-line-clamp: 2;
    }
    
    .detail-list-text strong {
        font-size: 0.85rem;
    }
    
    .add-btn button {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
    }
    
    .cust {
        font-size: 0.65rem;
    }
    
    .restaurant-name {
        font-size: 1.2rem !important;
    }
    
    .restaurant-description {
        font-size: 0.85rem !important;
    }
    
    .detail-list-text h3 {
        font-size: 1rem !important;
    }
    
    .add-btn button {
        padding: 0.4rem 0.8rem !important;
        font-size: 0.8rem !important;
    }
}

/* Correction de l'overflow horizontal */
body, html {
    overflow-x: hidden;
    width: 100%;
}
.navbar form,
.navbar .fa-shopping-cart {
    display: none !important;
}

.navbar {
    background: rgba(33, 37, 41, 0.98) !important;
    backdrop-filter: blur(10px);
}
/* Désactiver complètement l'effet de scroll */
.navbar, .navbar.scrolled {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%) !important;
    backdrop-filter: none !important;
    padding: 0.5rem 0 !important;
    transition: none !important;
}

    </style>
</style>
</head>
<body>
    <?php include __DIR__.'/includes/nav.php'; ?>
    
    <!-- Bannière publicitaire agrandie -->
    <div class="ad-banner">
    <img src="/assets/img/ad-banner.png" alt="Publicité" class="img-fluid">
    </div>

    <main class="container mb-5">
        <!-- Section informations restaurant avec image réduite -->
        <div class="restaurant-info-section">
            <div class="row g-0">
                <!-- Image du restaurant (réduite) -->
                <div class="col-md-5 restaurant-image-container">
                    <img src="<?= $restaurant['image_url'] ?>" alt="<?= htmlspecialchars($restaurant['name']) ?>" class="restaurant-image">
                </div>
                
                <!-- Informations du restaurant -->
                <div class="col-md-7 restaurant-details">
                    <h1 class="restaurant-name"><?= htmlspecialchars($restaurant['name']) ?></h1>
                    <p class="restaurant-description"><?= htmlspecialchars($restaurant['description']) ?></p>
                    
                    <div class="restaurant-contact">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <a href="http://maps.google.com/?q=<?= urlencode($restaurant['address']) ?>" target="_blank" class="text-decoration-underline">
                                <?= htmlspecialchars($restaurant['address']) ?>
                            </a>
                        </div>
                        
                        <?php if(!empty($restaurant['phone'])): ?>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?= htmlspecialchars($restaurant['phone']) ?>">
                                <?= htmlspecialchars($restaurant['phone']) ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($restaurant['email'])): ?>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?= htmlspecialchars($restaurant['email']) ?>">
                                <?= htmlspecialchars($restaurant['email']) ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Note et avis -->
                    <?php if($restaurant['avg_rating']): ?>
                    <div class="mb-3">
                        <div class="rating-stars fs-5">
                            <?php
                            $fullStars = floor($restaurant['avg_rating']);
                            $halfStar = ($restaurant['avg_rating'] - $fullStars) >= 0.5;
                            $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                            
                            for ($i = 0; $i < $fullStars; $i++) {
                                echo '<i class="fas fa-star"></i>';
                            }
                            
                            if ($halfStar) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            }
                            
                            for ($i = 0; $i < $emptyStars; $i++) {
                                echo '<i class="far fa-star"></i>';
                            }
                            ?>
                            <span class="ms-2">(<?= $restaurant['review_count'] ?> avis)</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Statut ouvert/fermé (déterminé par l'heure actuelle) -->
                    <?php
                    $currentHour = (int)date('H');
                    $isOpen = ($currentHour >= 11 && $currentHour < 22); // Exemple: ouvert de 11h à 22h
                    ?>
                    <div>
                        <span class="<?= $isOpen ? 'badge bg-success' : 'badge bg-danger' ?> px-3 py-2 fs-6">
                            <i class="fas fa-store me-1"></i> <?= $isOpen ? 'OUVERT MAINTENANT' : 'FERMÉ' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Onglets de navigation -->
        <div class="row">
            <div class="col-lg-12">
                <div class="resttabs">
                    <a href="#" class="active" id="menu_link"><button class="btn res-menu">Commande En ligne</button></a>
                    <a href="#" id="overview_link"><button class="btn overview_link">À propos</button></a>
                    <a href="#" id="review_link"><button class="btn res-review">Avis et notes</button></a>
                </div>
            </div>
        </div>
        
        <!-- Contenu des onglets -->
        <div class="row restaurant-detail-row">
            <!-- Colonne des produits -->
            <main class="col-sm-12 col-md-8 menu-col" id="menu" style="display: block;">
                <!-- Navigation par catégories -->
                <?php if(!empty($categories)): ?>
                <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 p-3 category-nav">
                    <div class="container-fluid">
                        <span class="navbar-brand fw-bold">Catégories :</span>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach($categories as $category): ?>
                            <a class="btn btn-sm btn-outline-dark" href="#<?= urlencode($category['name']) ?>">
                                <?= htmlspecialchars($category['name']) ?> (<?= $category['count'] ?>)
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </nav>
                <?php endif; ?>

                <!-- ... code précédent ... -->

<!-- Menus par catégorie -->

    <!-- Menus par catégorie -->
<?php if(!empty($categories)): ?>
    <?php foreach($categories as $category): ?>
    <section id="<?= urlencode($category['name']) ?>" class="mb-5">
        <h2 class="mb-4">
            <i class="fas fa-tag me-2 text-primary"></i>
            <?= htmlspecialchars($category['name']) ?>
        </h2>
        
        <div class="detail-list-box-main">
        <?php foreach($category['items'] as $menu): ?>
<div class="detail-list-box">
    <div class="detail-list">
        <div class="detail-list-img">
            <div class="list-img">
                <img src="<?= getProductImage($menu['id'], $menu['image_url']) ?>" 
                     alt="<?= htmlspecialchars($menu['name']) ?>"
                     loading="lazy"
                     onerror="this.onerror=null;this.src='<?= getProductImage(0, 'products', null) ?>'">
            </div>
        </div>
        <div class="detail-list-content">
            <div class="detail-list-text">
                <h3><?= htmlspecialchars($menu['name']) ?></h3>
                <!-- TEXTE TRONQUÉ COMME DANS L'ADMIN -->
              <?php  $maxLength = 100; // Par défaut
if (preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $_SERVER['HTTP_USER_AGENT'])) {
    $maxLength = 80; // Plus court sur mobile
}
?>

<p><?= htmlspecialchars(substr($menu['description'], 0, $maxLength)) ?><?= strlen($menu['description']) > $maxLength ? '...' : '' ?></p>
                <strong><?= number_format($menu['price'], 2) ?> CFA</strong>
            </div>
            <div class="add-btn">
                <button type="button" class="btn-add" 
                        data-id="<?= $menu['id'] ?>"
                        data-name="<?= htmlspecialchars($menu['name']) ?>"
                        data-description="<?= htmlspecialchars($menu['description']) ?>"
                        data-price="<?= $menu['price'] ?>"
                        data-image="<?= getProductImage($menu['id'], $menu['image_url']) ?>">
                    <i class="fas fa-eye me-1"></i>Voir détails
                </button>
                <span class="cust"><?= rand(5, 20) ?> commandes</span>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
<?php endif; ?>
    

<!-- ... suite du code ... -->
        <!-- ... suite du code ... -->
           
            <!-- Colonne du panier -->
            
            <div class="col-sm-12 col-md-4 your_cart-c" id="your_cart">
            <div class="your-cart-main">
    <div class="your-cart-title">
        <h3><i class="fas fa-shopping-cart"></i>Votre panier</h3>
        <h6 id="cart-items-count">0 Articles</h6>
    </div>
    
    <div id="cart-empty" class="cart-empty text-center">
        <img src="assets/img/panier.png" alt="Panier vide">
        <h6>Votre panier est vide. <br> Veuillez ajouter quelques plats pour continuer.</h6>
    </div>
    
    <div id="cart-items-container" style="display: none;">
        <div class="cart-items-list mb-3"></div>
        
        <div class="cart-summary">
            <div class="d-flex justify-content-between mb-2">
                <span>Sous-total:</span>
                <span id="cart-subtotal">0 CFA</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Frais de livraison:</span>
                <span id="cart-delivery">1000 CFA</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                <span>Total:</span>
                <span id="cart-total">0 CFA</span>
            </div>
            
            <button id="continue-button" class="btn btn-primary w-100 py-2" onclick="proceedToCheckout()">
                Continuer la commande
            </button>
        </div>
    </div>
    
    <div class="min_order_txt mt-3">
        <p>La commande minimum doit être de 500 FCFA pour éviter des frais de livraison supplémentaires</p>
    </div>
</div>
            <!-- Onglet À propos -->
<div class="col-sm-12" id="overview" style="display: none;">
    <div class="detail-list-box-main">
        <div class="heading-title">
            <h2>À propos du restaurant</h2>
        </div>
        
        <div class="mb-4">
            <?= nl2br(htmlspecialchars($restaurant['description'])) ?>
        </div>
        
        <!-- Horaires d'ouverture -->
        <div class="opening-hours">
            <h3><i class="fas fa-clock me-2"></i>Horaires d'ouverture</h3>
            <ul>
                <?php foreach($openingHours as $hour): ?>
                <li>
                    <span><?= $hour['day'] ?></span>
                    <span><?= $hour['hours'] ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Informations de contact -->
        <div class="opening-hours">
            <h3><i class="fas fa-info-circle me-2"></i>Informations</h3>
            <ul>
                <li>
                    <span><i class="fas fa-map-marker-alt me-2"></i>Adresse</span>
                    <span><?= htmlspecialchars($restaurant['address']) ?></span>
                </li>
                <?php if(!empty($restaurant['phone'])): ?>
                <li>
                    <span><i class="fas fa-phone me-2"></i>Téléphone</span>
                    <span><?= htmlspecialchars($restaurant['phone']) ?></span>
                </li>
                <?php endif; ?>
                <?php if(!empty($restaurant['email'])): ?>
                <li>
                    <span><i class="fas fa-envelope me-2"></i>Email</span>
                    <span><?= htmlspecialchars($restaurant['email']) ?></span>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- Onglet Avis et notes -->
<div class="col-sm-12" id="review" style="display: none;">
    <div class="detail-list-box-main">
        <div class="heading-title">
            <h2>Avis et notes</h2>
        </div>
        
        <!-- Résumé des notes -->
        <div class="opening-hours mb-4">
            <div class="row">
                <div class="col-md-6">
                    <h3>Note moyenne</h3>
                    <div class="display-4 text-primary fw-bold">
                        <?= number_format($restaurant['avg_rating'] ?? 0, 1) ?>/5
                    </div>
                    <div class="rating-stars mb-2">
                        <?php
                        $avgRating = $restaurant['avg_rating'] ?? 0;
                        $fullStars = floor($avgRating);
                        $halfStar = ($avgRating - $fullStars) >= 0.5;
                        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                        
                        for ($i = 0; $i < $fullStars; $i++) {
                            echo '<i class="fas fa-star"></i>';
                        }
                        
                        if ($halfStar) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        }
                        
                        for ($i = 0; $i < $emptyStars; $i++) {
                            echo '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>
                    <p>Basé sur <?= $restaurant['review_count'] ?? 0 ?> avis</p>
                </div>

                <div class="col-md-6">
                    <h3>Évaluez ce restaurant</h3>
                    <p>Partagez votre expérience avec les autres clients</p>
                    <a href="add-review.php?restaurant_id=<?= $restoId ?>" class="btn btn-primary">
                        <i class="fas fa-pen me-2"></i>Écrire un avis
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Liste des avis -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            La fonctionnalité d'affichage des avis sera implémentée prochainement.
       </div>
       </div>
       </div>

<!-- Modal pour les détails du produit -->
<!-- Modal pour les détails du produit -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel">Détails du produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <img id="modalProductImage" src="" alt="" class="img-fluid rounded">
                    </div>
                    <div class="col-md-6">
                        <h3 id="modalProductName"></h3>
                        
                        <!-- DESCRIPTION COMPLÈTE ICI -->
                        <div id="modalProductDescription" class="product-description-full mb-3"></div>
                        
                        <h4 id="modalProductPrice" class="text-primary"></h4>
                        
                        <!-- Options personnalisables -->
                        <div class="customization-options mt-4">
                            <h5>Personnalisation</h5>
                            <div id="customizationContainer">
                                <!-- Les options seront ajoutées dynamiquement ici -->
                            </div>
                        </div>
                        
                        <!-- Quantité -->
                        <div class="quantity-selector mt-4">
                            <label for="productQuantity" class="form-label">Quantité:</label>
                            <div class="input-group" style="width: 150px;">
                                <button class="btn btn-outline-secondary" type="button" onclick="decreaseQuantity()">-</button>
                                <input type="number" class="form-control text-center" id="productQuantity" value="1" min="1" max="10">
                                <button class="btn btn-outline-secondary" type="button" onclick="increaseQuantity()">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="addToCartFromModal()">
                    <i class="fas fa-cart-plus me-2"></i>Ajouter au panier
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Ajouter ce bouton dans le body, avant la fermeture -->
<button class="cart-toggle-btn" id="cartToggleBtn">
    <span class="cart-badge" id="cartBadge">0</span>
    <i class="fas fa-shopping-cart"></i>
    <span class="cart-toggle-text">Panier</span>
</button>
<div class="cart-overlay" id="cartOverlay"></div>
</div>
                    
</main>
<?php include __DIR__.'/includes/footer.php'; ?>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts personnalisés -->
    <script>
function showTab(tabName) {
    console.log('Showing tab:', tabName);
    
    // Cacher tous les onglets
    document.getElementById('menu').style.display = 'none';
    document.getElementById('overview').style.display = 'none';
    document.getElementById('review').style.display = 'none';
    document.getElementById('your_cart').style.display = 'none';
    
    // Afficher l'onglet sélectionné
    const selectedTab = document.getElementById(tabName);
    if (selectedTab) {
        selectedTab.style.display = 'block';
        console.log('Tab displayed:', tabName);
    }
    
    // Afficher le panier seulement pour l'onglet menu
    if (tabName === 'menu') {
        document.getElementById('your_cart').style.display = 'block';
    }
    
    // Mettre à jour la navigation
    document.querySelectorAll('.resttabs a').forEach(link => {
        link.classList.remove('active');
    });
    
    // Trouver le bon lien à activer
    const activeLink = document.getElementById(tabName + '_link');
    if (activeLink) {
        activeLink.classList.add('active');
    }
    
    // Scroll to top pour une meilleure expérience
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Initialiser les onglets au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Afficher l'onglet menu par défaut
    showTab('menu');
    
    // Ajouter les écouteurs d'événements
    document.getElementById('menu_link').addEventListener('click', function(e) {
        e.preventDefault();
        showTab('menu');
    });

    document.getElementById('overview_link').addEventListener('click', function(e) {
        e.preventDefault();
        showTab('overview');
    });

    document.getElementById('review_link').addEventListener('click', function(e) {
        e.preventDefault();
        showTab('review');
    });
});

   </script>
   <script>
// Variables pour stocker les infos du produit courant
let currentProduct = null;

// Afficher les détails du produit dans la modal
// Afficher les détails du produit dans la modal
function showProductDetails(id, name, description, price, imageUrl) {
    currentProduct = {
        id: id,
        name: name,
        description: description, // DESCRIPTION COMPLÈTE
        price: parseFloat(price),
        imageUrl: imageUrl
    };
    
    // Mettre à jour le contenu de la modal
    document.getElementById('modalProductImage').src = imageUrl;
    document.getElementById('modalProductImage').alt = name;
    document.getElementById('modalProductName').textContent = name;
    
    // AFFICHER LA DESCRIPTION COMPLÈTE
    document.getElementById('modalProductDescription').innerHTML = 
        description.replace(/\n/g, '<br>'); // Conserver les sauts de ligne
    
    document.getElementById('modalProductPrice').textContent = parseFloat(price).toFixed(2) + ' CFA';
    document.getElementById('productQuantity').value = 1;
    
    // Charger les options de personnalisation
    loadCustomizationOptions(id);
    
    // Afficher la modal avec Bootstrap
    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
    productModal.show();
}
// Charger les options de personnalisation
function loadCustomizationOptions(productId) {
    const container = document.getElementById('customizationContainer');
    container.innerHTML = '<div class="text-center my-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div><p class="mt-2">Chargement des options...</p></div>';
    
    // Simulation d'un appel AJAX pour récupérer les options
    setTimeout(() => {
        container.innerHTML = `
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="option-extra-cheese" data-price="500">
                <label class="form-check-label" for="option-extra-cheese">
                    Fromage supplémentaire (+500 CFA)
                </label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="option-no-onions" data-price="0">
                <label class="form-check-label" for="option-no-onions">
                    Sans oignons
                </label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="option-spicy" data-price="300">
                <label class="form-check-label" for="option-spicy">
                    Sauce piquante (+300 CFA)
                </label>
            </div>
        `;
        
        // Ajouter les écouteurs d'événements pour mettre à jour le prix
        document.querySelectorAll('#customizationContainer input').forEach(input => {
            input.addEventListener('change', updateModalPrice);
        });
    }, 800);
}

// Mettre à jour le prix dans la modal en fonction des options
function updateModalPrice() {
    if (!currentProduct) return;
    
    let totalPrice = currentProduct.price;
    const quantity = parseInt(document.getElementById('productQuantity').value);
    
    // Ajouter le prix des options sélectionnées
    document.querySelectorAll('#customizationContainer input:checked').forEach(input => {
        totalPrice += parseFloat(input.getAttribute('data-price') || 0);
    });
    
    document.getElementById('modalProductPrice').textContent = (totalPrice * quantity).toFixed(2) + ' CFA';
}

// Gestion de la quantité
function increaseQuantity() {
    const input = document.getElementById('productQuantity');
    if (input.value < 10) {
        input.value = parseInt(input.value) + 1;
        updateModalPrice();
    }
}

function decreaseQuantity() {
    const input = document.getElementById('productQuantity');
    if (input.value > 1) {
        input.value = parseInt(input.value) - 1;
        updateModalPrice();
    }
}
// Ajouter au panier depuis la modal
function addToCartFromModal() {
    if (!currentProduct) return;
    
    const quantity = parseInt(document.getElementById('productQuantity').value);
    
    // Récupérer les options sélectionnées
    const selectedOptions = [];
    let optionsPrice = 0;
    
    document.querySelectorAll('#customizationContainer input:checked').forEach(input => {
        const optionPrice = parseFloat(input.getAttribute('data-price') || 0);
        selectedOptions.push({
            id: input.id,
            name: input.nextElementSibling.textContent,
            price: optionPrice
        });
        optionsPrice += optionPrice;
    });
    
    // Calculer le prix total avec options
    const totalPrice = (currentProduct.price + optionsPrice) * quantity;
    
    // Ajouter au panier avec TOUS les paramètres nécessaires
    addToCart(
        currentProduct.id, 
        quantity, 
        selectedOptions, 
        totalPrice,
        currentProduct.name, // Ajout du nom
        currentProduct.imageUrl, // Ajout de l'image
        currentProduct.price // Ajout du prix de base
    );
    
    // Fermer la modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
    modal.hide();
    
    // Afficher une notification
    showNotification('Produit ajouté au panier!');
}

// Fonction pour ajouter au panier
// Fonction pour ajouter au panier
function addToCart(productId, quantity, options = [], totalPrice, productName, productImage, basePrice) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Créer un identifiant unique basé sur le produit et ses options
    const optionsSignature = options.map(opt => opt.id).sort().join('-');
    const cartItemId = `${productId}-${optionsSignature}`;
    
    const existingItemIndex = cart.findIndex(item => item.id === cartItemId);
    
    if (existingItemIndex !== -1) {
        cart[existingItemIndex].quantity += quantity;
        cart[existingItemIndex].totalPrice = cart[existingItemIndex].basePrice * cart[existingItemIndex].quantity;
        
        // Ajouter le prix des options
        cart[existingItemIndex].options.forEach(option => {
            cart[existingItemIndex].totalPrice += option.price * cart[existingItemIndex].quantity;
        });
    } else {
        cart.push({
            id: cartItemId,
            productId: productId,
            name: productName, // Stocker le nom
            basePrice: basePrice, // Stocker le prix de base
            options: options,
            quantity: quantity,
            totalPrice: totalPrice,
            image: productImage, // Stocker l'image
            addedAt: new Date().toISOString()
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartDisplay();
    showNotification('Produit ajouté au panier!');
}
// Fonction pour synchroniser le panier avec le serveur
function syncCartWithServer() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    if (cart.length === 0) return;
    
    fetch('sync_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cart: cart,
            restaurant_id: <?= $restoId ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Panier synchronisé avec le serveur');
        }
    })
    .catch(error => {
        console.error('Erreur synchronisation panier:', error);
    });
}

// Synchroniser le panier au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    syncCartWithServer();
    updateCartDisplay();
});
// Fonction pour afficher une notification
function showNotification(message) {
    // Créer l'élément de notification s'il n'existe pas
    if (!document.getElementById('notification')) {
        const notification = document.createElement('div');
        notification.id = 'notification';
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 20px;
            background: #28a745;
            color: white;
            border-radius: 5px;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        document.body.appendChild(notification);
    }
    
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.style.opacity = '1';
    
    // Cacher la notification après 3 secondes
    setTimeout(() => {
        notification.style.opacity = '0';
    }, 3000);
}

// Initialiser après le chargement du document
document.addEventListener('DOMContentLoaded', function() {
    updateCartDisplay();
    
    // Déléguer les événements pour tous les boutons "Voir détails"
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-add') || e.target.closest('.btn-add')) {
            const button = e.target.classList.contains('btn-add') ? e.target : e.target.closest('.btn-add');
            const productId = button.getAttribute('data-id');
            const productName = button.getAttribute('data-name');
            const productDescription = button.getAttribute('data-description');
            const productPrice = button.getAttribute('data-price');
            const productImage = button.getAttribute('data-image');
            
            showProductDetails(
                parseInt(productId), 
                productName, 
                productDescription, 
                parseFloat(productPrice), 
                productImage
            );
        }
    });
});
// Fonction pour afficher une notification toast
function showToast(message, type = 'success') {
    // Créer le toast s'il n'existe pas
    if (!document.getElementById('toastContainer')) {
        const toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(toastContainer);
    }
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show`;
    toast.style.cssText = `
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        margin-bottom: 0;
    `;
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.getElementById('toastContainer').appendChild(toast);
    
    // Supprimer le toast après 3 secondes
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}

// Modifier la fonction showNotification pour utiliser le toast
function showNotification(message) {
    showToast(message, 'success');
}
// Fonction pour mettre à jour l'affichage du panier
function updateCartDisplay() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const subtotal = cart.reduce((sum, item) => sum + item.totalPrice, 0);
    
    // Mettre à jour le compteur d'articles
    document.getElementById('cart-items-count').textContent = `${totalItems} Articles`;
    
    if (totalItems > 0) {
        // Masquer le message de panier vide
        document.getElementById('cart-empty').style.display = 'none';
        document.getElementById('cart-items-container').style.display = 'block';
        
        // Afficher les articles du panier
        const cartItemsContainer = document.querySelector('.cart-items-list');
        cartItemsContainer.innerHTML = '';
        
        cart.forEach(item => {
            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            cartItem.dataset.id = item.id;
            
            // Formater les options sélectionnées
            const optionsText = item.options.length > 0 
                ? item.options.map(opt => opt.name).join(', ') 
                : '';
            
            cartItem.innerHTML = `
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name} x${item.quantity}</div>
                    ${optionsText ? `<div class="cart-item-options">${optionsText}</div>` : ''}
                    <div class="cart-item-price">${item.totalPrice.toFixed(2)} CFA</div>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn" onclick="changeQuantity('${item.id}', -1)">-</button>
                    <span>${item.quantity}</span>
                    <button class="quantity-btn" onclick="changeQuantity('${item.id}', 1)">+</button>
                    <button class="remove-item" onclick="removeFromCart('${item.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            cartItemsContainer.appendChild(cartItem);
        });
        
        // Calculer les frais de livraison (gratuits à partir de 500 CFA)
        const deliveryFee = 1000;
        const total = subtotal + deliveryFee;
        
        // Mettre à jour les totaux
        document.getElementById('cart-subtotal').textContent = `${subtotal.toFixed(2)} CFA`;
        document.getElementById('cart-delivery').textContent = `${deliveryFee.toFixed(2)} CFA`;
        document.getElementById('cart-total').textContent = `${total.toFixed(2)} CFA`;
        
        // Mettre à jour le message de commande minimum
        const minOrderText = document.querySelector('.min_order_txt p');
if (subtotal < 500) {
    const remaining = (500 - subtotal).toFixed(2);
    minOrderText.innerHTML = `Ajoutez encore <strong>${remaining} CFA</strong> pour éviter des frais de livraison supplémentaires`;
} else {
    minOrderText.textContent = 'Félicitations! Vous évitez les frais de livraison supplémentaires';
}
    } else {
        // Afficher le message de panier vide
        document.getElementById('cart-empty').style.display = 'block';
        document.getElementById('cart-items-container').style.display = 'none';
        
        // Réinitialiser le message de commande minimum
        document.querySelector('.min_order_txt p').textContent = 
            'La commande minimum doit être de 500 FCFA pour éviter des frais de livraison supplémentaires';
    }
}

// Fonction pour modifier la quantité d'un article
function changeQuantity(itemId, change) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const itemIndex = cart.findIndex(item => item.id === itemId);
    
    if (itemIndex !== -1) {
        const newQuantity = cart[itemIndex].quantity + change;
        
        if (newQuantity <= 0) {
            // Supprimer l'article si la quantité devient 0
            cart.splice(itemIndex, 1);
        } else {
            // Mettre à jour la quantité et le prix total
            cart[itemIndex].quantity = newQuantity;
            cart[itemIndex].totalPrice = cart[itemIndex].basePrice * newQuantity;
            
            // Ajouter le prix des options
            cart[itemIndex].options.forEach(option => {
                cart[itemIndex].totalPrice += option.price * newQuantity;
            });
        }
        
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartDisplay();
        showNotification('Panier mis à jour');
    }
}

// Fonction pour supprimer un article du panier
function removeFromCart(itemId) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart = cart.filter(item => item.id !== itemId);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartDisplay();
    showNotification('Article supprimé du panier');
}

// Fonction pour le bouton "Continuer"
// Fonction pour le bouton "Continuer"
// Fonction pour le bouton "Continuer"
// Fonction pour le bouton "Continuer"
// Fonction pour le bouton "Continuer" (version alternative)
function proceedToCheckout() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    if (cart.length === 0) {
        showToast('Votre panier est vide', 'warning');
        return;
    }
    
    // Afficher un indicateur de chargement
    const continueButton = document.getElementById('continue-button');
    const originalText = continueButton.innerHTML;
    continueButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement...';
    continueButton.disabled = true;
    
    // Créer un formulaire pour envoyer les données
    const formData = new FormData();
    formData.append('action', 'save_cart');
    formData.append('cart_data', JSON.stringify(cart));
    formData.append('restaurant_id', <?= $restoId ?>);
    
    // Envoyer les données via POST standard
    fetch('<?= $_SERVER['PHP_SELF'] ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Redirection vers la livraison...', 'success');
            setTimeout(() => {
                window.location.href = 'delivery_info.php?restaurant_id=<?= $restoId ?>';
            }, 1000);
        } else {
            throw new Error(data.error || 'Erreur inconnue');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showToast('Erreur: ' + error.message, 'error');
        continueButton.innerHTML = originalText;
        continueButton.disabled = false;
    });
}
// Appeler cette fonction pour vérifier l'état du panier
document.addEventListener('DOMContentLoaded', function() {
    debugCart();
    
    // Vérifier aussi le bouton continue
    const continueButton = document.getElementById('continue-button');
    if (continueButton) {
        continueButton.addEventListener('click', function(e) {
            console.log('Bouton continuer cliqué');
            debugCart();
        });
    }
});

// Fonction pour fermer le panier

// ===== FONCTIONS DE GESTION DU PANIER =====

// Fonction pour fermer le panier
function closeCart() {
    const cartElement = document.querySelector('.your-cart-main');
    const cartToggleBtn = document.getElementById('cartToggleBtn');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartElement) {
        cartElement.classList.remove('cart-open');
        
        if (cartToggleBtn) {
            const icon = cartToggleBtn.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-shopping-cart');
            }
            const text = cartToggleBtn.querySelector('.cart-toggle-text');
            if (text) {
                text.textContent = 'Panier';
            }
        }
        
        if (cartOverlay) {
            cartOverlay.style.display = 'none';
        }
    }
}

// Fonction pour ouvrir le panier
function openCart() {
    const cartElement = document.querySelector('.your-cart-main');
    const cartToggleBtn = document.getElementById('cartToggleBtn');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartElement) {
        cartElement.classList.add('cart-open');
        
        if (cartToggleBtn) {
            const icon = cartToggleBtn.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-shopping-cart');
                icon.classList.add('fa-times');
            }
            const text = cartToggleBtn.querySelector('.cart-toggle-text');
            if (text) {
                text.textContent = 'Fermer';
            }
        }
        
        if (cartOverlay) {
            cartOverlay.style.display = 'block';
        }
    }
}

// Vérifier si un clic est en dehors du panier
function setupClickOutsideToClose() {
    document.addEventListener('click', function(e) {
        const cartElement = document.querySelector('.your-cart-main');
        const cartToggleBtn = document.getElementById('cartToggleBtn');
        
        // Si le panier est ouvert et qu'on clique en dehors du panier et du bouton toggle
        if (cartElement && cartElement.classList.contains('cart-open') && 
            !cartElement.contains(e.target) && 
            (!cartToggleBtn || !cartToggleBtn.contains(e.target))) {
            closeCart();
        }
    });
}

// Configuration du bouton de toggle du panier
function setupCartToggle() {
    const cartToggleBtn = document.getElementById('cartToggleBtn');
    const cartElement = document.querySelector('.your-cart-main');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartToggleBtn && cartElement) {
        // Supprimer les anciens écouteurs pour éviter les doublons
        cartToggleBtn.replaceWith(cartToggleBtn.cloneNode(true));
        const newCartToggleBtn = document.getElementById('cartToggleBtn');
        
        newCartToggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            if (cartElement.classList.contains('cart-open')) {
                closeCart();
            } else {
                openCart();
            }
        });
        
        // Empêcher la fermeture lorsqu'on clique dans le panier
        cartElement.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Fermer le panier en cliquant sur l'overlay
        if (cartOverlay) {
            cartOverlay.addEventListener('click', closeCart);
        }
    }
}

// Mettre à jour le badge du panier
function updateCartBadge() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartBadge = document.getElementById('cartBadge');
    
    if (cartBadge) {
        if (totalItems > 0) {
            cartBadge.textContent = totalItems;
            cartBadge.style.display = 'flex';
        } else {
            cartBadge.style.display = 'none';
        }
    }
}

// ===== INITIALISATION =====
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le toggle du panier
    setupCartToggle();
    
    // Configurer la fermeture en cliquant à l'extérieur
    setupClickOutsideToClose();
    
    // Mettre à jour le badge du panier
    updateCartBadge();
    
    // Fermer le panier sur les grands écrans
    if (window.innerWidth > 992) {
        closeCart();
    }
    
    // Adapter le panier lors du redimensionnement
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            closeCart();
        }
    });
    
    // Mettre à jour l'affichage du panier
    updateCartDisplay();
});

// ===== FONCTIONS EXISTANTES (à conserver) =====

// Vos fonctions existantes comme showProductDetails, addToCart, updateCartDisplay, etc.
// doivent être conservées ici...
// REMPLACER le code problématique par ceci :
// Supprimer les deux blocs dupliqués et garder UNE SEULE version :

// Sauvegarder la fonction originale
const originalUpdateCartDisplay = window.updateCartDisplay;

// Surcharger la fonction
window.updateCartDisplay = function() {
    if (originalUpdateCartDisplay) {
        originalUpdateCartDisplay.apply(this, arguments);
    }
    updateCartBadge();
};
// Forcer le recalcul du layout après le chargement
window.addEventListener('load', function() {
    document.querySelectorAll('.detail-list-text p').forEach(p => {
        // Force le navigateur à recalculer le layout
        p.style.display = 'none';
        p.offsetHeight; // Trigger reflow
        p.style.display = '-webkit-box';
    });
});
// Fonction pour le bouton "Continuer"
// Fonction pour le bouton "Continuer"
// Fonction simplifiée pour le bouton "Continuer"
function proceedToCheckout() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    if (cart.length === 0) {
        showToast('Votre panier est vide', 'warning');
        return;
    }
    
    // Sauvegarder le panier dans le stockage session pour le récupérer après redirection
    sessionStorage.setItem('pending_cart', JSON.stringify(cart));
    sessionStorage.setItem('pending_restaurant_id', <?= $restoId ?>);
    
    // Rediriger directement
    window.location.href = 'delivery_info.php?restaurant_id=<?= $restoId ?>';
}
</script>

<script>
// Forcer le recalcul du layout pour corriger le texte
document.addEventListener('DOMContentLoaded', function() {
    // Réappliquer les styles pour forcer le rendu correct
    setTimeout(function() {
        const textElements = document.querySelectorAll('.detail-list-text p');
        textElements.forEach(function(el) {
            el.style.display = 'none';
            void el.offsetHeight; // Force reflow
            el.style.display = '-webkit-box';
        });
    }, 100);
});

// Solution alternative si le problème persiste
function fixTextOverflow() {
    document.querySelectorAll('.detail-list').forEach(card => {
        const textElement = card.querySelector('.detail-list-text p');
        if (textElement.scrollHeight > textElement.offsetHeight) {
            // Ajouter une classe si le texte dépasse
            card.classList.add('text-overflowing');
        }
    });
}

// Exécuter après le chargement complet
window.addEventListener('load', fixTextOverflow);
</script>
<script>
// CORRECTION DÉFINITIVE DU TEXTE
function enforceTextLimits() {
    const descriptions = document.querySelectorAll('.product-description');
    
    descriptions.forEach(desc => {
        // Réinitialiser
        desc.style.display = 'block';
        desc.style.webkitLineClamp = '';
        desc.style.maxHeight = '';
        
        // Forcer le recalcul
        void desc.offsetHeight;
        
        // Si le texte dépasse la hauteur maximale, appliquer l'ellipsis
        if (desc.scrollHeight > desc.offsetHeight) {
            desc.style.display = '-webkit-box';
            desc.style.webkitLineClamp = '3';
            desc.style.overflow = 'hidden';
            
            // Ajustement pour mobile
            if (window.innerWidth <= 576) {
                desc.style.webkitLineClamp = '2';
                desc.style.maxHeight = '2.8em';
            }
        }
    });
}

// Exécuter immédiatement et après le chargement
document.addEventListener('DOMContentLoaded', enforceTextLimits);
window.addEventListener('resize', enforceTextLimits);

// Réexécuter après un délai pour s'assurer que tout est chargé
setTimeout(enforceTextLimits, 100);
setTimeout(enforceTextLimits, 500);
setTimeout(enforceTextLimits, 1000);
</script>
<script>
// Détection de la prise en charge de line-clamp
function supportsLineClamp() {
    const testElement = document.createElement('div');
    testElement.style.webkitLineClamp = '2';
    return testElement.style.webkitLineClamp !== undefined;
}

// Appliquer line-clamp seulement si supporté
function applyLineClampIfSupported() {
    if (supportsLineClamp()) {
        const descriptions = document.querySelectorAll('.detail-list-text p');
        descriptions.forEach(desc => {
            desc.style.display = '-webkit-box';
            desc.style.webkitLineClamp = '3';
            desc.style.webkitBoxOrient = 'vertical';
            desc.style.overflow = 'hidden';
            desc.style.maxHeight = 'none';
            
            // Enlever le gradient pour les navigateurs modernes
            desc.style.position = 'static';
        });
        
        // Supprimer les pseudo-éléments after
        const style = document.createElement('style');
        style.textContent = `
            .detail-list-text p::after {
                display: none !important;
            }
        `;
        document.head.appendChild(style);
    }
}

// Exécuter au chargement
document.addEventListener('DOMContentLoaded', applyLineClampIfSupported);
</script>
<script>
// Garantir que les descriptions sont correctement limitées
document.addEventListener('DOMContentLoaded', function() {
    // Attendre que les images soient chargées
    setTimeout(function() {
        const descriptions = document.querySelectorAll('.detail-list-text p');
        
        descriptions.forEach(function(desc) {
            // Vérifier si le texte dépasse
            if (desc.scrollHeight > desc.offsetHeight) {
                // S'assurer que les styles sont appliqués
                desc.style.display = '-webkit-box';
                desc.style.webkitLineClamp = '3';
                desc.style.webkitBoxOrient = 'vertical';
                desc.style.overflow = 'hidden';
                
                // Pour les navigateurs qui ne supportent pas line-clamp
                if (!('webkitLineClamp' in desc.style)) {
                    desc.classList.add('fallback');
                }
            }
        });
    }, 500);
});

// Réappliquer après le redimensionnement
window.addEventListener('resize', function() {
    const descriptions = document.querySelectorAll('.detail-list-text p');
    descriptions.forEach(function(desc) {
        desc.style.display = 'block';
        
        // Forcer le recalcul
        void desc.offsetWidth;
        
        desc.style.display = '-webkit-box';
    });
});
</script>
</body>
</html>