<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système de Gestion de Restaurants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
            --accent-color: #D4AF37;
            --light-bg: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, var(--light-bg) 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .card-dashboard {
            transition: transform 0.3s;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-dashboard:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .bg-restaurant {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .bg-menu {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .bg-order {
            background: linear-gradient(135deg, #f12711 0%, #f5af19 100%);
            color: white;
        }
        
        .bg-user {
            background: linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%);
            color: white;
        }
        
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .welcome-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-utensils me-2"></i>FoodManager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-1"></i>Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">
                            <i class="fas fa-envelope me-1"></i>Contact
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- En-tête de bienvenue -->
    <div class="welcome-header text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">Système de Gestion de Restaurants</h1>
            <p class="lead">Solution complète pour la gestion de vos restaurants, menus, commandes et paiements</p>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="container mb-5">
        <div class="row">
            <div class="col-md-8 mx-auto text-center mb-5">
                <h2 class="mb-4">Fonctionnalités principales</h2>
                <p class="lead">Notre système offre une interface complète pour gérer tous les aspects de votre restaurant</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card card-dashboard bg-restaurant text-white h-100">
                    <div class="card-body text-center">
                        <div class="card-icon mb-3">
                            <i class="fas fa-utensils fa-3x"></i>
                        </div>
                        <h4 class="card-title">Gestion des Restaurants</h4>
                        <p class="card-text">Créez et gérez plusieurs restaurants avec leurs informations détaillées</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card card-dashboard bg-menu text-white h-100">
                    <div class="card-body text-center">
                        <div class="card-icon mb-3">
                            <i class="fas fa-list fa-3x"></i>
                        </div>
                        <h4 class="card-title">Gestion des Menus</h4>
                        <p class="card-text">Ajoutez et modifiez vos menus avec images, descriptions et prix</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card card-dashboard bg-order text-white h-100">
                    <div class="card-body text-center">
                        <div class="card-icon mb-3">
                            <i class="fas fa-shopping-cart fa-3x"></i>
                        </div>
                        <h4 class="card-title">Suivi des Commandes</h4>
                        <p class="card-text">Recevez et gérez les commandes en temps réel avec suivi des statuts</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card card-dashboard bg-user text-white h-100">
                    <div class="card-body text-center">
                        <div class="card-icon mb-3">
                            <i class="fas fa-chart-line fa-3x"></i>
                        </div>
                        <h4 class="card-title">Analyses et Statistiques</h4>
                        <p class="card-text">Suivez les performances de votre restaurant avec des données détaillées</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card card-dashboard h-100">
                    <div class="card-body text-center">
                        <div class="card-icon mb-3">
                            <i class="fas fa-money-bill-wave fa-3x text-success"></i>
                        </div>
                        <h4 class="card-title">Gestion des Paiements</h4>
                        <p class="card-text">Suivez les paiements et générez des factures pour vos clients</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-5">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h3 class="card-title mb-4">Prêt à optimiser la gestion de votre restaurant?</h3>
                        <p class="card-text mb-4">Connectez-vous à votre espace administrateur ou gestionnaire pour commencer</p>
                        <button class="btn btn-primary btn-lg me-3" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                        </button>
                        <button class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-question-circle me-2"></i>Guide d'utilisation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de connexion -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Connexion au système</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse email</label>
                            <input type="email" class="form-control" id="email" placeholder="nom@exemple.com">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" placeholder="Votre mot de passe">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Se souvenir de moi</label>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                            </button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <p class="mb-0">Type de compte:</p>
                        <div class="btn-group mt-2" role="group">
                            <button type="button" class="btn btn-outline-primary active">Administrateur</button>
                            <button type="button" class="btn btn-outline-secondary">Gestionnaire</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pied de page -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>FoodManager</h5>
                    <p>Solution complète de gestion de restaurants</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; 2023 FoodManager. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script pour basculer entre admin et gestionnaire
        document.addEventListener('DOMContentLoaded', function() {
            const adminBtn = document.querySelector('.btn-group .btn:nth-child(1)');
            const managerBtn = document.querySelector('.btn-group .btn:nth-child(2)');
            
            adminBtn.addEventListener('click', function() {
                this.classList.add('active');
                managerBtn.classList.remove('active');
            });
            
            managerBtn.addEventListener('click', function() {
                this.classList.add('active');
                adminBtn.classList.remove('active');
            });
        });
    </script>
</body>
</html>