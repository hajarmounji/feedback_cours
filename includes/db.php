<?php
// includes/db.php - Connexion à la base de données
$host = 'sql107.infinityfree.com';
$dbname = 'if0_41847559_feedbackcours';
$user = 'if0_41847559';
$pass = '9UrelQAvkGeZ4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion: " . $e->getMessage());
}
?>