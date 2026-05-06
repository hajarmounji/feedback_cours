<?php
// config.php - Configuration globale du projet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'projetweb');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration du site
define('SITE_NAME', 'Plateforme Éducative');
define('SITE_URL', 'http://localhost/feedback_cours_MGSI/');

// Configuration des chemins
define('ROOT_PATH', dirname(__FILE__) . '/');
define('PAGES_PATH', ROOT_PATH . 'pages/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');

// Timezone
date_default_timezone_set('Africa/Tunis');

// Activation des erreurs (désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>