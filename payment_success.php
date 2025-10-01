<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Récupérer les paramètres de l'URL
$transactionId = $_GET['transaction_id'] ?? '';
$method = $_GET['method'] ?? '';
$amount = $_GET['amount'] ?? '';

// Si aucun paramètre, rediriger vers l'accueil
if (empty($transactionId)) {
    header("Location: index.php");
    exit();
}

// Récupérer les informations de l'utilisateur
try {
    $db = connectDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erreur: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Réussi | RestoPlatform</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4a6cf7;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --dark-color: #292F36;
            --light-color: #F7FFF7;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            padding-top: 80px;
            color: #333;
        }
        
        .success-container {
            max-width: 1000px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .success-header {
            background: var(--success-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .success-body {
            padding: 2rem;
        }
        
        .display-1 {
            font-size: 5rem;
        }
        
        .list-group-item {
            border: none;
            padding: 0.75rem 0;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 0.6rem 1.5rem;
        }
        
        .btn-primary:hover {
            background: #3a5cd8;
        }
        
        .btn-outline-secondary {
            border-color: var(--secondary-color);
            color: var(--secondary-color);
        }
        
        .btn-outline-secondary:hover {
            background: var(--secondary-color);
            color: white;
        }
        
        /* Footer styles */
        .luxury-footer {
            background: linear-gradient(135deg, #2c3e50 0%, #1a2530 100%);
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 3rem;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .footer-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #fff;
        }
        
        .footer-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-links h4 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #fff;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            display: inline-block;
        }
        
        .footer-links a {
            display: block;
            color: #ccc;
            margin-bottom: 0.5rem;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #ccc;
            font-size: 0.9rem;
        }
        
        .newsletter-form {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .newsletter-form input {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 4px;
        }
        
        .newsletter-form button {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .success-container {
                margin: 1rem;
                border-radius: 10px;
            }
            
            .success-body {
                padding: 1.5rem;
            }
            
            .display-1 {
                font-size: 4rem;
            }
            
            .footer-links {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation simplifiée -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-utensils me-2"></i>RestoPlatform
            </a>
        </div>
    </nav>

    <div class="success-container">
        <div class="success-header">
            <h1 class="mb-0"><i class="fas fa-check-circle me-2"></i>Paiement Réussi</h1>
        </div>
        
        <div class="success-body">
            <div class="text-center mb-4">
                <div class="display-1 text-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="mt-3">Merci pour votre commande!</h2>
                <p class="lead">Votre paiement a été traité avec succès.</p>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>Détails de la transaction</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Référence:</span>
                            <span class="fw-bold"><?= htmlspecialchars($transactionId) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Méthode:</span>
                            <span class="text-capitalize"><?= htmlspecialchars($method) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Montant:</span>
                            <span class="fw-bold"><?= htmlspecialchars($amount) ?> CFA</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Date:</span>
                            <span><?= date('d/m/Y à H:i') ?></span>
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h5>Prochaines étapes</h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Vous recevrez un email de confirmation sous peu. Votre commande est en cours de préparation.
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>Retour à l'accueil
                        </a>
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>Voir mes commandes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="luxury-footer">
        <div class="footer-content">
            <div class="footer-logo">RestoPremium</div>
            
            <div class="footer-links">
                <div>
                    <h4>Navigation</h4>
                    <a href="index.php">Accueil</a>
                    <a href="restaurants.php">Restaurants</a>
                    <a href="about.php">À propos</a>
                    <a href="contact.php">Contact</a>
                </div>
                
                <div>
                    <h4>Services</h4>
                    <a href="#">Livraison Premium</a>
                    <a href="#">Service Privé</a>
                    <a href="#">Événements</a>
                    <a href="#">Cadeaux</a>
                </div>
                
                <div>
                    <h4>Contact</h4>
                    <a href="#"><i class="fas fa-map-marker-alt me-2"></i>123 Avenue des Champs, Paris</a>
                    <a href="#"><i class="fas fa-phone me-2"></i>+33 1 23 45 67 89</a>
                    <a href="#"><i class="fas fa-envelope me-2"></i>contact@restopremium.com</a>
                </div>
                
                <div>
                    <h4>Newsletter</h4>
                    <p>Abonnez-vous pour recevoir nos offres exclusives.</p>
                    <div class="newsletter-form">
                        <input type="email" placeholder="Votre email">
                        <button type="submit">OK</button>
                    </div>
                </div>
            </div>
            
            <div class="social-links">
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-pinterest"></i></a>
            </div>
            
            <div class="copyright">
                &copy; <?= date('Y') ?> RestoPremium. Tous droits réservés.<br>
                <small>
                    <a href="#" class="text-light">Mentions légales</a> | 
                    <a href="#" class="text-light">Politique de confidentialité</a>
                </small>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>