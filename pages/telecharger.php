<?php
// pages/telecharger.php - Téléchargement PDF
require_once __DIR__ . '/../config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'fonctions.php';

// Vérifier que l'utilisateur est connecté
if (!isEtudiantConnecte() && !isEnseignantConnecte()) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("ID de cours invalide.");
}

try {
    $stmt = $pdo->prepare("SELECT contenu, nom_cours FROM cours WHERE id_cours = ?");
    $stmt->execute([$id]);
    $cours = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cours) {
        die("Cours non trouvé.");
    }

    if (empty($cours['contenu'])) {
        die("Ce cours ne contient pas de fichier PDF.");
    }

    // Nettoyer le nom du fichier
    $nom_fichier = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cours['nom_cours']);
    $nom_fichier = trim($nom_fichier, '_') . '.pdf';

    // Envoyer les en-têtes HTTP
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $nom_fichier . '"');
    header('Content-Length: ' . strlen($cours['contenu']));
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');

    // Afficher le contenu PDF
    echo $cours['contenu'];
    exit();

} catch (PDOException $e) {
    error_log("Erreur téléchargement : " . $e->getMessage());
    die("Erreur lors de l'accès à la base de données.");
}
?>