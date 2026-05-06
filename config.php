<?php
// config.php - Configuration globale du projet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔹 Configuration de la base de données - InfinityFree
define('DB_HOST', 'sql107.infinityfree.com');
define('DB_NAME', 'if0_41847559_feedbackcours');
define('DB_USER', 'if0_41847559');
define('DB_PASS', '9UrelQAvkGeZ4');

// 🔹 Configuration du site - Domaine réel
define('SITE_NAME', 'Plateforme Éducative');
define('SITE_URL', 'https://feedback1cours.free.nf/');

// Configuration des chemins
define('ROOT_PATH', dirname(__FILE__) . '/');
define('PAGES_PATH', ROOT_PATH . 'pages/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');

// Timezone
date_default_timezone_set('Africa/Tunis');

// 🔹 Production : désactiver l'affichage des erreurs
error_reporting(0);
ini_set('display_errors', 0);
?>