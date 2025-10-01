<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';

// Vérifier si l'utilisateur est connecté
// Vérifier si les informations de livraison existent
if (!isset($_SESSION['delivery_info']) || !isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

// Récupérer les informations
$deliveryInfo = $_SESSION['delivery_info'];
$cart = $_SESSION['cart'];
$restaurantId = $deliveryInfo['restaurant_id'];
$isGuest = isset($deliveryInfo['is_guest']) ? $deliveryInfo['is_guest'] : false;


// Vérifier si le panier existe et n'est pas vide
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Récupérer les informations du restaurant
$restaurantId = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 0;
if ($restaurantId <= 0) {
    header("Location: index.php");
    exit();
}

try {
    $db = connectDB();
    
    // Récupérer les infos du restaurant
    $stmt = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurantId]);
    $restaurant = $stmt->fetch();
    
    if (!$restaurant) {
        header("Location: index.php");
        exit();
    }
    
    // Récupérer les informations utilisateur
    $userId = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Calculer le total du panier
    $subtotal = 0;
    $deliveryFee = 1000; // Frais de livraison fixes
    $cartItems = [];
    
    foreach ($_SESSION['cart'] as $item) {
        $itemPrice = isset($item['basePrice']) ? $item['basePrice'] : (isset($item['price']) ? $item['price'] : 0);
        $itemTotal = $itemPrice * $item['quantity'];
        $subtotal += $itemTotal;
        
        $item['displayPrice'] = $itemPrice;
        $cartItems[] = $item;
    }
    
    $total = $subtotal + $deliveryFee;
    
} catch (PDOException $e) {
    error_log("Erreur checkout: " . $e->getMessage());
    $error = "Une erreur est survenue lors du chargement de la page de paiement.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - <?= htmlspecialchars($restaurant['name']) ?> | RestoPlatform</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4a6cf7;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --wave-color: #0c59d0;
            --orange-money-color: #ff7700;
            --free-money-color: #00a650;
            --visa-color: #1a1f71;
            --error-color: #dc3545;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            padding-top: 80px;
        }
        
        .payment-container {
            max-width: 1000px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .payment-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .payment-body {
            padding: 2rem;
        }
        
        .payment-option-card {
            cursor: pointer;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .payment-option-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        
        .payment-option-card.selected {
            border-color: var(--primary-color);
            background-color: rgba(74, 108, 247, 0.05);
        }
        
        .payment-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .wave-color {
            color: var(--wave-color);
        }
        
        .orange-money-color {
            color: var(--orange-money-color);
        }
        
        .free-money-color {
            color: var(--free-money-color);
        }
        
        .visa-color {
            color: var(--visa-color);
        }
        
        .form-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .form-section.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .btn-payment {
            background: var(--primary-color);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-payment:hover {
            background: #3a5cd8;
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .summary-total {
            font-weight: bold;
            font-size: 1.2rem;
            border-top: 1px solid #dee2e6;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(74, 108, 247, 0.25);
        }
        
        .is-invalid {
            border-color: var(--error-color);
        }
        
        .invalid-feedback {
            display: none;
            color: var(--error-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .was-validated .form-control:invalid ~ .invalid-feedback {
            display: block;
        }
        
        /* Styles pour mobile */
        @media (max-width: 768px) {
            .payment-container {
                margin: 0.5rem;
                border-radius: 10px;
            }
            
            .payment-body {
                padding: 1rem;
            }
            
            .payment-option-card {
                padding: 0.8rem;
                margin-bottom: 0.8rem;
            }
            
            .payment-icon {
                font-size: 1.5rem;
            }
            
            .order-summary {
                padding: 1rem;
            }
        }
        
        /* Animation de chargement */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .simulation-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--secondary-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__.'/includes/nav.php'; ?>
    
    <!-- Overlay de chargement -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="ms-2">Traitement en cours...</div>
    </div>
    
    <div class="payment-container">
        <div class="payment-header">
            <h2><i class="fas fa-lock me-2"></i>Paiement Sécurisé</h2>
            <p class="mb-0">Finalisez votre commande chez <?= htmlspecialchars($restaurant['name']) ?></p>
        </div>
        
        <div class="payment-body">
            <div class="row">
                <div class="col-md-7">
                    <div class="position-relative">
                        <span class="simulation-badge">Mode Simulation</span>
                        <h4 class="mb-4">Choisissez votre mode de paiement</h4>
                    </div>
                    
                    <!-- Options de paiement -->
                    <div class="row mb-4">
                        <div class="col-6 col-md-3 mb-2">
                            <div class="payment-option-card h-100 text-center" data-method="wave">
                                <div class="payment-icon wave-color">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <p class="mb-0 small">Wave</p>
                            </div>
                        </div>
                        
                        <div class="col-6 col-md-3 mb-2">
                            <div class="payment-option-card h-100 text-center" data-method="orange">
                                <div class="payment-icon orange-money-color">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <p class="mb-0 small">Orange Money</p>
                            </div>
                        </div>
                        
                        <div class="col-6 col-md-3 mb-2">
                            <div class="payment-option-card h-100 text-center" data-method="free">
                                <div class="payment-icon free-money-color">
                                    <i class="fas fa-money-check"></i>
                                </div>
                                <p class="mb-0 small">Free Money</p>
                            </div>
                        </div>
                        
                        <div class="col-6 col-md-3 mb-2">
                            <div class="payment-option-card h-100 text-center" data-method="visa">
                                <div class="payment-icon visa-color">
                                    <i class="fab fa-cc-visa"></i>
                                </div>
                                <p class="mb-0 small">Carte Visa</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Formulaires de paiement -->
                    <div class="payment-forms">
                        <!-- Formulaire Wave -->
                        <div class="form-section active" id="wave-form">
                            <h5 class="mb-3"><i class="fas fa-money-bill-wave wave-color me-2"></i>Paiement avec Wave</h5>
                            <form id="wavePaymentForm" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="wave-phone" class="form-label">Numéro de téléphone Wave</label>
                                    <input type="tel" class="form-control" id="wave-phone" 
                                           placeholder="77 123 45 67" value="<?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : '' ?>"
                                           pattern="^(77|78|76|70)[0-9]{7}$" required>
                                    <div class="invalid-feedback">
                                        Veuillez entrer un numéro de téléphone sénégalais valide (ex: 771234567)
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-payment w-100">
                                    <i class="fas fa-lock me-2"></i>Payer avec Wave
                                </button>
                            </form>
                        </div>
                        
                        <!-- Formulaire Orange Money -->
                        <div class="form-section" id="orange-form">
                            <h5 class="mb-3"><i class="fas fa-mobile-alt orange-money-color me-2"></i>Paiement avec Orange Money</h5>
                            <form id="orangePaymentForm" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="orange-phone" class="form-label">Numéro Orange Money</label>
                                    <input type="tel" class="form-control" id="orange-phone" 
                                           placeholder="77 123 45 67" value="<?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : '' ?>"
                                           pattern="^(77|78|76|70)[0-9]{7}$" required>
                                    <div class="invalid-feedback">
                                        Veuillez entrer un numéro de téléphone sénégalais valide (ex: 771234567)
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-payment w-100">
                                    <i class="fas fa-lock me-2"></i>Payer avec Orange Money
                                </button>
                            </form>
                        </div>
                        
                        <!-- Formulaire Free Money -->
                        <div class="form-section" id="free-form">
                            <h5 class="mb-3"><i class="fas fa-money-check free-money-color me-2"></i>Paiement avec Free Money</h5>
                            <form id="freePaymentForm" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="free-phone" class="form-label">Numéro Free Money</label>
                                    <input type="tel" class="form-control" id="free-phone" 
                                           placeholder="77 123 45 67" value="<?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : '' ?>"
                                           pattern="^(77|78|76|70)[0-9]{7}$" required>
                                    <div class="invalid-feedback">
                                        Veuillez entrer un numéro de téléphone sénégalais valide (ex: 771234567)
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-payment w-100">
                                    <i class="fas fa-lock me-2"></i>Payer avec Free Money
                                </button>
                            </form>
                        </div>
                        
                        <!-- Formulaire Visa -->
                        <div class="form-section" id="visa-form">
                            <h5 class="mb-3"><i class="fab fa-cc-visa visa-color me-2"></i>Paiement par Carte Visa</h5>
                            <form id="visaPaymentForm" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="card-number" class="form-label">Numéro de carte</label>
                                    <input type="text" class="form-control" id="card-number" 
                                           placeholder="1234 5678 9012 3456" required
                                           pattern="[0-9\s]{16,19}">
                                    <div class="invalid-feedback">
                                        Veuillez entrer un numéro de carte valide (16 chiffres)
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="card-expiry" class="form-label">Date d'expiration</label>
                                        <input type="text" class="form-control" id="card-expiry" 
                                               placeholder="MM/AA" required
                                               pattern="(0[1-9]|1[0-2])\/([0-9]{2})">
                                        <div class="invalid-feedback">
                                            Veuillez entrer une date d'expiration valide (MM/AA)
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="card-cvv" class="form-label">CVV</label>
                                        <input type="text" class="form-control" id="card-cvv" 
                                               placeholder="123" required
                                               pattern="[0-9]{3}">
                                        <div class="invalid-feedback">
                                            Veuillez entrer un code CVV valide (3 chiffres)
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="card-name" class="form-label">Titulaire de la carte</label>
                                    <input type="text" class="form-control" id="card-name" 
                                           placeholder="John Doe" required>
                                    <div class="invalid-feedback">
                                        Veuillez entrer le nom du titulaire de la carte
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-payment w-100">
                                    <i class="fas fa-lock me-2"></i>Payer avec Visa
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="order-summary">
                        <h4 class="mb-4">Récapitulatif de la commande</h4>
                        
                        <div class="summary-item">
                            <span>Sous-total</span>
                            <span><?= number_format($subtotal, 2) ?> CFA</span>
                        </div>
                        <div class="summary-item">
                            <span>Frais de livraison</span>
                            <span><?= number_format($deliveryFee, 2) ?> CFA</span>
                        </div>
                        
                        <div class="summary-item summary-total">
                            <span>Total</span>
                            <span><?= number_format($total, 2) ?> CFA</span>
                        </div>
                        
                        <div class="mt-4">
    <h5>Détails des articles</h5>
    <?php foreach ($cartItems as $item): ?>
    <div class="d-flex mt-3">
        <div class="flex-shrink-0">
            <img src="<?= isset($item['image']) ? $item['image'] : '/assets/img/default-product.png' ?>" 
                 alt="<?= isset($item['name']) ? htmlspecialchars($item['name']) : 'Produit' ?>" 
                 class="img-fluid rounded" width="60" height="60" style="object-fit: cover;">
        </div>
        <div class="flex-grow-1 ms-3">
            <h6><?= isset($item['name']) ? htmlspecialchars($item['name']) : 'Produit' ?></h6>
            <p class="text-muted mb-0">Quantité: <?= $item['quantity'] ?></p>
            <?php if (!empty($item['options'])): ?>
            <p class="text-muted mb-0 small">
                <?= implode(', ', array_map(function($opt) { 
                    return isset($opt['name']) ? htmlspecialchars($opt['name']) : 'Option'; 
                }, $item['options'])) ?>
            </p>
            <?php endif; ?>
        </div>
        <div class="flex-shrink-0">
            <p class="mb-0"><?= number_format($item['displayPrice'] * $item['quantity'], 2) ?> CFA</p>
        </div>
    </div>
    <?php endforeach; ?>
</div>
                    
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6><i class="fas fa-shield-alt me-2"></i>Paiement sécurisé</h6>
                        <p class="small text-muted mb-0">Toutes vos transactions sont cryptées et sécurisées. Nous ne stockons jamais les informations de votre carte bancaire.</p>
                    </div>
                    
                    <div class="mt-3 p-3 bg-warning bg-opacity-10 rounded">
                        <h6><i class="fas fa-info-circle me-2"></i>Mode simulation</h6>
                        <p class="small text-muted mb-0">Vous êtes en mode simulation. Aucun paiement réel ne sera effectué. Les transactions seront simulées pour tester le processus.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentOptions = document.querySelectorAll('.payment-option-card');
            const formSections = document.querySelectorAll('.form-section');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Sélectionner une option de paiement
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Retirer la sélection précédente
                    paymentOptions.forEach(o => o.classList.remove('selected'));
                    
                    // Ajouter la sélection à l'option choisie
                    this.classList.add('selected');
                    
                    // Masquer tous les formulaires
                    formSections.forEach(form => form.classList.remove('active'));
                    
                    // Afficher le formulaire correspondant
                    const methodType = this.getAttribute('data-method');
                    document.getElementById(`${methodType}-form`).classList.add('active');
                });
            });
            
            // Sélectionner Wave par défaut
            document.querySelector('[data-method="wave"]').click();
            
            // Validation des formulaires
            const forms = document.querySelectorAll('.needs-validation');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    
                    if (!form.checkValidity()) {
                        event.stopPropagation();
                        form.classList.add('was-validated');
                        return;
                    }
                    
                    // Si la validation passe, traiter le paiement
                    const method = form.id.replace('PaymentForm', '');
                    processPayment(method);
                });
            });
            
            // Formatage des inputs de carte
            const cardNumber = document.getElementById('card-number');
            const cardExpiry = document.getElementById('card-expiry');
            const cardCvv = document.getElementById('card-cvv');
            
            if (cardNumber) {
                cardNumber.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 16) value = value.slice(0, 16);
                    
                    // Ajouter un espace tous les 4 chiffres
                    let formattedValue = '';
                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) formattedValue += ' ';
                        formattedValue += value[i];
                    }
                    
                    e.target.value = formattedValue;
                });
            }
            
            if (cardExpiry) {
                cardExpiry.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 4) value = value.slice(0, 4);
                    
                    if (value.length > 2) {
                        e.target.value = value.slice(0, 2) + '/' + value.slice(2);
                    } else {
                        e.target.value = value;
                    }
                });
            }
            
            if (cardCvv) {
                cardCvv.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 3) value = value.slice(0, 3);
                    e.target.value = value;
                });
            }
            
            // Validation en temps réel pour les numéros de téléphone
            const phoneInputs = document.querySelectorAll('input[type="tel"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '');
                    
                    if (this.checkValidity()) {
                        this.classList.remove('is-invalid');
                    } else {
                        this.classList.add('is-invalid');
                    }
                });
            });
        });
        
        function processPayment(method) {
            // Afficher l'indicateur de chargement
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.style.display = 'flex';
            
            // Récupérer les données du formulaire selon la méthode
            let paymentData = {};
            
            switch(method) {
                case 'wave':
                    paymentData.phone = document.getElementById('wave-phone').value.replace(/\D/g, '');
                    break;
                    
                case 'orange':
                    paymentData.phone = document.getElementById('orange-phone').value.replace(/\D/g, '');
                    break;
                    
                case 'free':
                    paymentData.phone = document.getElementById('free-phone').value.replace(/\D/g, '');
                    break;
                    
                case 'visa':
                    paymentData.cardNumber = document.getElementById('card-number').value.replace(/\s/g, '');
                    paymentData.expiry = document.getElementById('card-expiry').value;
                    paymentData.cvv = document.getElementById('card-cvv').value;
                    paymentData.name = document.getElementById('card-name').value;
                    break;
            }
            
            // Envoyer les données au serveur (simulation pour le moment)
            simulatePaymentProcessing(method, paymentData);
        }
        
        function simulatePaymentProcessing(method, paymentData) {
            // Simuler un délai de traitement
            setTimeout(() => {
                // Cacher l'indicateur de chargement
                document.getElementById('loadingOverlay').style.display = 'none';
                
                // Simuler une réponse réussie
                const transactionId = 'SIM_' + method.toUpperCase() + '_' + Date.now();
                
                // Rediriger vers la page de succès
                window.location.href = `payment_success.php?transaction_id=${transactionId}&method=${method}&amount=<?= $total ?>`;
            }, 2000);
        }
        
        // Fonctions pour l'intégration future des API
        function initOrangeMoneyPayment(phone, amount, reference) {
            // À implémenter avec l'API Orange Money
            console.log("Initialisation paiement Orange Money:", phone, amount, reference);
            // Retourner une promesse avec l'URL de redirection
            return Promise.resolve("https://api.orange.com/payment?token=simulated");
        }
        
        function initWavePayment(phone, amount, reference) {
            // À implémenter avec l'API Wave
            console.log("Initialisation paiement Wave:", phone, amount, reference);
            // Retourner une promesse avec l'URL de redirection
            return Promise.resolve("https://wave.com/payment?token=simulated");
        }
        
        function processVisaPayment(cardData, amount, reference) {
            // À implémenter avec l'API de paiement par carte
            console.log("Traitement paiement Visa:", cardData, amount, reference);
            // Retourner une promesse avec le résultat
            return Promise.resolve({ success: true, transactionId: "VISA_" + Date.now() });
        }
        
        function verifyTransaction(transactionId, method) {
            // À implémenter pour vérifier le statut d'une transaction
            console.log("Vérification transaction:", transactionId, method);
            // Retourner une promesse avec le statut
            return Promise.resolve({ status: "completed", verified: true });
        }
    </script>
</body>
</html>