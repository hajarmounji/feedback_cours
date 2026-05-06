<?php
// index.php - Page d'accueil
require_once 'config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'fonctions.php';

$page_title = 'Accueil';

// Redirections si déjà connecté
if (isEtudiantConnecte()) {
    redirect('dashboard_etudiant.php');
}
if (isEnseignantConnecte()) {
    redirect('dashboard_enseignant.php');
}

require_once INCLUDES_PATH . 'header.php';
?>

<div class="hero">
    <div class="container">
        <div class="hero-content">
            <i class="fas fa-graduation-cap hero-icon"></i>
            <h1><?= SITE_NAME ?></h1>
            <p class="school-subtitle">ENSIASD - Taroudant</p>
            <p>Apprentissage • Partage • Excellence</p>
        </div>
    </div>
</div>

<div class="container">
    <div class="cards-grid">
        <div class="card-access">
            <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
            <h3>Espace Étudiant</h3>
            <p>Consultez vos cours, donnez votre avis, suivez votre progression.</p>
            <a href="pages/login.php" class="btn btn-primary">Accéder →</a>
        </div>
        
        <div class="card-access">
            <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <h3>Espace Enseignant</h3>
            <p>Publiez vos cours, gérez vos modules, consultez les feedbacks.</p>
            <a href="pages/login_enseignant.php" class="btn btn-primary">Accéder →</a>
        </div>
        <div class="card-access">
           <div class="card-icon"><i class="fas fa-user-cog"></i></div>
           <h3>Administrateur</h3>
           <p>Gérez les utilisateurs et l'ensemble de la plateforme.</p>
           <a href="pages/login_admin.php" class="btn btn-primary">Accéder →</a>
        </div>
        
    </div>
</div>

<style>
.hero {
    background: url('images/fond-ensiasd.png') center/100% no-repeat;
    background-position: center 45%;
    padding: 80px 0;
    text-align: center;
    color: white;
    position: relative;
}

.hero h1, .hero p {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

/* Assombrit l'image pour que le texte soit lisible */
.hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1;
}

.hero .container {
    position: relative;
    z-index: 2;
}
@keyframes bounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }


.hero-content {
    position: relative;
    max-width: 800px;
    margin: 0 auto;
}

.school-subtitle {
    font-size: 14px;
    opacity: 0.8;
    margin-top: -5px;
    margin-bottom: 10px;
}

/* Contenu centré */
.hero-centered {
    text-align: center;
}

.hero-icon { 
    font-size: 70px; 
    margin-bottom: 20px; 
    animation: bounce 2s infinite; 
}

@keyframes bounce { 
    0%,100% { transform: translateY(0); } 
    50% { transform: translateY(-10px); } 
}

.hero h1 { 
    font-size: 48px; 
    margin-bottom: 15px; 
}

.school-name {
    font-size: 14px;
    opacity: 0.85;
    margin: 15px 0 10px;
    letter-spacing: 1px;
}

.hero-tagline {
    font-size: 18px;
    opacity: 0.9;
    margin-top: 15px;
}

/* Responsive */
@media (max-width: 768px) {
    .hero { padding: 50px 0; }
    .hero h1 { font-size: 32px; }
    .logo-top-right { position: static; margin-bottom: 20px; }
    .hero-logo-small { height: 40px; }
    .hero-icon { font-size: 50px; }
}
.cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; padding: 60px 0; }
.card-access { background: white; border-radius: 20px; padding: 40px 30px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1); transition: transform 0.3s; }
.card-access:hover { transform: translateY(-8px); }
.card-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #1e3c72, #2a5298); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 32px; color: white; }
.card-access h3 { font-size: 22px; margin-bottom: 15px; }
.card-access p { color: #666; margin-bottom: 25px; }
@media (max-width: 768px) { .hero h1 { font-size: 32px; } .hero p { font-size: 16px; } }
</style>
<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h4><i class="fas fa-graduation-cap"></i> <?= SITE_NAME ?></h4>
                <p>Plateforme éducative dédiée à l'apprentissage et au partage de connaissances.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-github"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Liens rapides</h4>
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="pages/login.php">Espace Étudiant</a></li>
                    <li><a href="pages/login_enseignant.php">Espace Enseignant</a></li>
                    <li><a href="pages/login_admin.php">Administration</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contact</h4>
                <ul>
                    <li><i class="fas fa-envelope"></i>EduPro@gmail.com </li>
                    <li><i class="fas fa-phone"></i>Tel: +212 525-971682</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Tous droits réservés.</p>
            <p>Réalisé par <strong>Hajar Mounji | Rime El Mouatez</strong> - ENSIASD</p>
        </div>
    </div>
</footer>

<style>
/* Footer Styles */
.site-footer {
    background: url('images/fond-ensiasd.png') center/cover no-repeat;
    color: #fff;
    margin-top: 60px;
    padding: 50px 0 20px;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.footer-section h4 {
    font-size: 18px;
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 10px;
}

.footer-section h4:after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 40px;
    height: 3px;
    background: #4facfe;
    border-radius: 2px;
}

.footer-section p {
    line-height: 1.6;
    opacity: 0.9;
    font-size: 14px;
}

.footer-section ul {
    list-style: none;
    padding: 0;
}

.footer-section ul li {
    margin-bottom: 10px;
}

.footer-section ul li a {
    color: #fff;
    text-decoration: none;
    opacity: 0.8;
    transition: all 0.3s;
}

.footer-section ul li a:hover {
    opacity: 1;
    padding-left: 5px;
    color: #4facfe;
}

.footer-section ul li i {
    width: 25px;
    margin-right: 5px;
    color: #4facfe;
}

.social-links {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.social-links a {
    width: 35px;
    height: 35px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    text-decoration: none;
    transition: all 0.3s;
}

.social-links a:hover {
    background: #4facfe;
    transform: translateY(-3px);
}

.footer-bottom {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    font-size: 13px;
    opacity: 0.8;
}

.footer-bottom p {
    margin: 5px 0;
}

.site-footer {
    background: url('images/fond-ensiasd-footer.png') center ;
    background-position: center 40%;
    background-size: 100% ;
    color: #fff;
    margin-top: 60px;
    padding: 50px 0 20px;
    position: relative;
    box-shadow: 0 -5px 15px rgba(0,0,0,0.3);
}

.site-footer::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 0;
}

.site-footer .container {
    position: relative;
    z-index: 1;
}

/* Empêche les éléments enfants de répéter l'image */
.site-footer .container * {
    background: none !important;
}


</style>
<?php require_once INCLUDES_PATH . 'footer.php'; ?>