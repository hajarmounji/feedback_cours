<?php
// includes/fonctions.php - Fonctions utilitaires

/** Vérifie si l'utilisateur est connecté en tant qu'étudiant */
function isEtudiantConnecte() {
    return isset($_SESSION['id_etudiant']) && !empty($_SESSION['id_etudiant']);
}

/** Vérifie si l'utilisateur est connecté en tant qu'enseignant */
function isEnseignantConnecte() {
    return isset($_SESSION['id_enseignant']) && !empty($_SESSION['id_enseignant']);
}

/** Vérifie si l'utilisateur est connecté en tant qu'admin */
/*function isAdminConnecte() {
    return isset($_SESSION['id_administrateur']) && !empty($_SESSION['id_administrateur']);
}

/** Redirige vers une page */
function redirect($page) {
    header("Location: " . SITE_URL . "pages/" . $page);
    exit();
}

/** Sécurise une sortie HTML */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/** Formate une date */
function formatDate($date) {
    return date('d/m/Y à H:i', strtotime($date));
}

/** Calcule la note moyenne avec étoiles */
function getStars($rating) {
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat('★', $full) . str_repeat('½', $half) . str_repeat('☆', $empty);
}

/** Hashage de mot de passe */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/** Vérification de mot de passe */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/** Crée un nouvel utilisateur avec mot de passe hashé */
function createUser($table, $data) {
    global $pdo;
    
    if (isset($data['mot_de_passe'])) {
        $data['mot_de_passe'] = hashPassword($data['mot_de_passe']);
    }
    
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $stmt = $pdo->prepare("INSERT INTO $table ($columns) VALUES ($placeholders)");
    return $stmt->execute($data) ? $pdo->lastInsertId() : false;
}
/** Vérifie si l'utilisateur est connecté en tant qu'admin */
function isAdminConnecte() {
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}
?>