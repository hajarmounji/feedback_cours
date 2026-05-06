<?php
// ============================================
// DASHBOARD ENSEIGNANT - STOCKAGE PDF EN BINAIRE (LONGBLOB)
// ============================================
require_once __DIR__ . '/../config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'fonctions.php';

// Redirection si non connecté
if (!isEnseignantConnecte()) {
    redirect('login_enseignant.php');
}

// Récupération des variables de session
$id_enseignant = $_SESSION['id_enseignant'];
$prenom = $_SESSION['prenom'] ?? 'Enseignant';
$nom = $_SESSION['nom'] ?? '';
$email = $_SESSION['email'] ?? '';
$departement = $_SESSION['departement'] ?? 'Département';


// 3. Vérification de sécurité : l'enseignant doit être connecté
if (!isset($_SESSION['id_enseignant']) || empty($_SESSION['id_enseignant'])) {
    header("Location: login_enseignant.php");
    exit();
}


// ============================================
// TRAITEMENT DES FORMULAIRES (CRUD COURS)
// ============================================

// ➕ AJOUTER UN COURS
if (isset($_POST['add_course'])) {
    $nom_cours = trim($_POST['nom_cours']);
    $module = trim($_POST['module']);
    $titre = trim($_POST['titre']);
    $contenu_text = trim($_POST['contenu_text']);
    
    $pdfBinary = null;
    
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === 0) {
        // Vérification MIME réelle (sécurité)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['pdf_file']['tmp_name']);
        finfo_close($finfo);
        
        if ($mime === 'application/pdf' && $_FILES['pdf_file']['size'] <= 100 * 1024 * 1024) { // Max 10 Mo
            // Lecture du fichier PDF en binaire
            $pdfBinary = file_get_contents($_FILES['pdf_file']['tmp_name']);
        } else {
            $error = "⚠️ Format invalide ou fichier trop volumineux (max 10 Mo).";
            echo "<script>alert('$error'); window.history.back();</script>";
            exit();
        }
    }
    
    try {
        // Insertion en base : Contenu = binaire PDF OU texte description
        $stmt = $pdo->prepare("INSERT INTO cours (nom_Cours, module, titre, contenu, date_cours, id_enseignant) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt->execute([$nom_cours, $module, $titre, $pdfBinary ?? $contenu_text, $id_enseignant]);
        
        redirect('dashboard_enseignant.php?success=course_added');
        exit();
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'max_allowed_packet') !== false) {
            echo "<script>alert('❌ Fichier trop gros pour MySQL. Augmentez max_allowed_packet à 64M dans my.ini'); window.history.back();</script>";
        } else {
            echo "<script>alert('❌ Erreur : " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        }
        exit();
    }
}

// ✏️ MODIFIER UN COURS
if (isset($_POST['edit_course'])) {
    $id_cours = (int)$_POST['id_cours'];
    $nom_cours = trim($_POST['nom_cours']);
    $module = trim($_POST['module']);
    $titre = trim($_POST['titre']);
    $contenu_text = trim($_POST['contenu_text']);
    
    $pdfBinary = null;
    $updateWithBinary = false;
    
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === 0) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['pdf_file']['tmp_name']);
        finfo_close($finfo);
        
        if ($mime === 'application/pdf' && $_FILES['pdf_file']['size'] <= 10 * 1024 * 1024) {
            $pdfBinary = file_get_contents($_FILES['pdf_file']['tmp_name']);
            $updateWithBinary = true;
        } else {
            $error = "⚠️ Format invalide ou fichier trop volumineux (max 10 Mo).";
            echo "<script>alert('$error'); window.history.back();</script>";
            exit();
        }
    }
    
    try {
        if ($updateWithBinary) {
            $stmt = $pdo->prepare("UPDATE cours SET nom_cours=?, module=?, titre=?, contenu=? WHERE id_cours=? AND id_enseignant=?");
            $stmt->execute([$nom_cours, $module, $titre, $pdfBinary, $id_cours, $id_enseignant]);
        } elseif (!empty($contenu_text)) {
            $stmt = $pdo->prepare("UPDATE cours SET nom_Cours=?, module=?, titre=?, contenu=? WHERE id_cours=? AND id_enseignant=?");
            $stmt->execute([$nom_cours, $module, $titre, $contenu_text, $id_cours, $id_enseignant]);
        } else {
            $stmt = $pdo->prepare("UPDATE cours SET nom_Cours=?, module=?, titre=? WHERE id_cours=? AND id_enseignant=?");
            $stmt->execute([$nom_cours, $module, $titre, $id_cours, $id_enseignant]);
        }
        
        redirect('dashboard_enseignant.php?success=course_updated');
        exit();
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'max_allowed_packet') !== false) {
            echo "<script>alert('❌ Fichier trop gros pour MySQL. Augmentez max_allowed_packet à 64M dans my.ini'); window.history.back();</script>";
        } else {
            echo "<script>alert('❌ Erreur : " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        }
        exit();
    }
}

// 🗑️ SUPPRIMER UN COURS
if (isset($_POST['delete_course'])) {
    $id_cours = (int)$_POST['id_cours'];
    
    // Supprimer le cours (le BLOB sera automatiquement supprimé)
    $pdo->prepare("DELETE FROM cours WHERE id_cours = ? AND id_enseignant=?")->execute([$id_cours, $id_enseignant]);
    
    redirect('dashboard_enseignant.php?success=course_deleted');
    exit();
}
// Modification du mot de passe enseignant
if (isset($_POST['update_password_teacher'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    $stmt = $pdo->prepare("SELECT mot_de_passe FROM enseignant WHERE id_enseignant = ?");
    $stmt->execute([$id_enseignant]);
    $hash = $stmt->fetchColumn();
    
    if (password_verify($current, $hash)) {
        if (strlen($new) < 6) {
            $error = "⚠️ Le nouveau mot de passe doit contenir au moins 6 caractères.";
        } elseif ($new !== $confirm) {
            $error = "⚠️ Les mots de passe ne correspondent pas.";
        } else {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE enseignant SET mot_de_passe = ? WHERE id_enseignant = ?");
            $stmt->execute([$new_hash, $id_enseignant]);
            $success = "✅ Mot de passe modifié avec succès !";
        }
    } else {
        $error = "⚠️ Mot de passe actuel incorrect.";
    }
}


// ============================================
// RÉCUPÉRATION DES DONNÉES POUR AFFICHAGE
// ============================================

$mes_cours = [];
$tous_feedbacks = [];

try {
    // Récupérer mes cours avec statistiques
    // ⚠️ On ne sélectionne PAS la colonne Contenu pour éviter de charger les PDF en mémoire
    $stmt = $pdo->prepare("
        SELECT c.id_cours, c.nom_cours, c.module, c.titre, c.date_cours, c.id_enseignant,
               COALESCE((SELECT COUNT(*) FROM feedback f WHERE f.id_cours = c.id_cours), 0) as nb_feedbacks,
               COALESCE((SELECT AVG(note) FROM feedback f WHERE f.id_cours = c.id_cours), 0) as avg_rating,
               CASE WHEN c.Contenu IS NOT NULL AND LENGTH(c.contenu) > 500 THEN 1 ELSE 0 END as has_pdf
        FROM cours c 
        WHERE c.id_enseignant = ? 
        ORDER BY c.date_cours DESC
    ");
    $stmt->execute([$id_enseignant]);
    $mes_cours = $stmt->fetchAll();

    // Récupérer tous les feedbacks sur mes cours
    $stmt = $pdo->prepare("
        SELECT f.*, c.nom_cours, c.titre, 
               i.nom as etudiant_nom, i.prenom as etudiant_prenom
        FROM feedback f
        INNER JOIN cours c ON f.id_cours = c.id_cours
        INNER JOIN etudiant i ON f.id_etudiant = i.id_etudiant
        WHERE c.id_enseignant = ?
        ORDER BY f.date_feedback DESC
    ");
    $stmt->execute([$id_enseignant]);
    $tous_feedbacks = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur SQL dashboard: " . $e->getMessage());
}

// Calcul des statistiques globales
$total_cours = count($mes_cours);
$total_feedbacks = count($tous_feedbacks);
$avg_general = 0;
if ($total_feedbacks > 0) {
    $sum = 0;
    foreach($tous_feedbacks as $fb) {
        $sum += (float)($fb['note'] ?? 0);
    }
    $avg_general = $sum / $total_feedbacks;
}

// Préparation des données pour le graphique Chart.js
$chart_labels = [];
$chart_feedbacks = [];
$chart_ratings = [];

foreach ($mes_cours as $cours) {
    $chart_labels[] = $cours['nom_cours'];
    $chart_feedbacks[] = (int)($cours['nb_feedbacks'] ?? 0);
    $chart_ratings[] = round((float)($cours['avg_rating'] ?? 0), 1);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Enseignant - EduPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ===== RESET & BASE ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f5f7fa; display: flex; min-height: 100vh; color: #333; }
        
        /* ===== SIDEBAR GAUCHE ===== */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            position: fixed; left: 0; top: 0; height: 100vh;
            overflow-y: auto; transition: all 0.3s ease;
            z-index: 1000; box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-logo { font-size: 24px; font-weight: bold; margin-bottom: 5px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .sidebar-user { display: flex; align-items: center; gap: 12px; margin-top: 20px; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 10px; }
        .sidebar-avatar { width: 40px; height: 40px; border-radius: 50%; background: white; color: #1e3c72; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; flex-shrink: 0; }
        .sidebar-user-info { overflow: hidden; text-align: left; }
        .sidebar-user-name { font-weight: 600; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-user-role { font-size: 11px; opacity: 0.9; }
        .sidebar-menu { padding: 15px 0; }
        .menu-item {
            padding: 14px 25px; display: flex; align-items: center; gap: 12px;
            color: rgba(255,255,255,0.85); text-decoration: none; transition: all 0.2s;
            cursor: pointer; border-left: 4px solid transparent; background: none; border: none;
            width: 100%; text-align: left; font-size: 14px;
        }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.15); color: white; border-left-color: #4facfe; }
        .menu-item i { font-size: 18px; width: 22px; text-align: center; }
        .menu-badge { margin-left: auto; background: #4facfe; color: white; padding: 3px 9px; border-radius: 10px; font-size: 11px; font-weight: 600; }
        .sidebar-footer { position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-btn {
            width: 100%; padding: 11px; background: rgba(255,255,255,0.15); color: white;
            border: 1px solid rgba(255,255,255,0.25); border-radius: 8px; cursor: pointer;
            font-size: 13px; font-weight: 600; transition: all 0.2s; display: flex;
            align-items: center; justify-content: center; gap: 8px; text-decoration: none;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.25); }
        
        /* ===== CONTENU PRINCIPAL ===== */
        .main-content { flex: 1; margin-left: 260px; transition: margin-left 0.3s ease; }
        .top-bar {
            background: white; padding: 18px 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 100;
        }
        .page-title { font-size: 22px; color: #2c3e50; font-weight: 600; }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; cursor: pointer; color: #1e3c72; }
        .container { padding: 25px 30px; max-width: 1400px; margin: 0 auto; width: 100%; }
        .section { display: none; animation: fadeIn 0.3s ease; }
        .section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* ===== STATS CARDS ===== */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 28px; }
        .stat-card {
            background: white; border-radius: 14px; padding: 22px; box-shadow: 0 3px 12px rgba(0,0,0,0.07);
            display: flex; align-items: center; gap: 18px; transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon {
            width: 55px; height: 55px; border-radius: 12px; display: flex;
            align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;
        }
        .stat-icon.blue { background: #e3f2fd; color: #1e3c72; }
        .stat-icon.green { background: #e8f5e9; color: #2e7d32; }
        .stat-icon.orange { background: #fff3e0; color: #ef6c00; }
        .stat-icon.purple { background: #f3e5f5; color: #7b1fa2; }
        .stat-info h3 { font-size: 26px; color: #2c3e50; margin-bottom: 3px; font-weight: 700; }
        .stat-info p { color: #7f8c8d; font-size: 13px; }
        
        /* ===== BOUTONS ===== */
        .btn {
            padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer;
            font-weight: 600; font-size: 13px; transition: all 0.2s; display: inline-flex;
            align-items: center; gap: 7px; text-decoration: none;
        }
        .btn-primary { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(30,60,114,0.35); }
        .btn-danger { background: #ef5350; color: white; }
        .btn-danger:hover { background: #e53935; }
        .btn-warning { background: #ffa726; color: white; }
        .btn-warning:hover { background: #fb8c00; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        /* ===== CARDS & TABLES ===== */
        .card {
            background: white; border-radius: 14px; padding: 24px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.07); margin-bottom: 22px;
        }
        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #f0f2f5;
            flex-wrap: wrap; gap: 12px;
        }
        .card-title { font-size: 19px; color: #2c3e50; font-weight: 600; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 13px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #555; font-size: 13px; text-transform: uppercase; letter-spacing: 0.3px; }
        tr:hover { background: #fafbfc; }
        
        /* ===== COURSE & FEEDBACK ITEMS ===== */
        .course-item {
            background: #fafbfc; border-radius: 10px; padding: 18px; margin-bottom: 12px;
            border-left: 4px solid #1e3c72; transition: box-shadow 0.2s;
        }
        .course-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .course-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; flex-wrap: wrap; gap: 10px; }
        .course-title { font-size: 17px; font-weight: 600; color: #2c3e50; }
        .course-module { background: #1e3c72; color: white; padding: 4px 11px; border-radius: 14px; font-size: 11px; font-weight: 500; }
        .course-meta { display: flex; gap: 18px; margin: 8px 0; font-size: 13px; color: #666; flex-wrap: wrap; }
        .course-meta i { margin-right: 5px; color: #1e3c72; width: 16px; }
        .feedback-item { background: #fafbfc; border-radius: 10px; padding: 16px; margin-bottom: 10px; }
        .feedback-header { display: flex; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px; }
        .feedback-author { font-weight: 600; color: #2c3e50; font-size: 14px; }
        .feedback-rating { color: #ffa726; font-size: 15px; font-weight: 600; }
        .feedback-content { color: #555; line-height: 1.6; font-size: 14px; }
        .feedback-date { font-size: 12px; color: #999; margin-top: 6px; display: flex; align-items: center; gap: 4px; }
        
        /* ===== BADGES ===== */
        .badge { display: inline-block; padding: 4px 10px; border-radius: 11px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #c8e6c9; color: #2e7d32; }
        .badge-info { background: #e3f2fd; color: #1e3c72; }
        .badge-pdf { background: #ffebee; color: #c62828; }
        
        /* ===== MODAL ===== */
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 2000; align-items: center;
            justify-content: center; padding: 20px;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white; border-radius: 16px; max-width: 550px; width: 100%;
            max-height: 90vh; overflow-y: auto; animation: slideUp 0.25s ease;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298); color: white;
            padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 10;
        }
        .modal-header h2 { font-size: 19px; font-weight: 600; }
        .close-modal { background: none; border: none; color: white; font-size: 26px; cursor: pointer; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.2s; }
        .close-modal:hover { background: rgba(255,255,255,0.2); }
        .modal-body { padding: 24px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 7px; color: #444; font-weight: 500; font-size: 13px; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 11px 13px; border: 2px solid #e0e4e8; border-radius: 8px;
            font-size: 14px; transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none; border-color: #1e3c72; box-shadow: 0 0 0 3px rgba(30,60,114,0.1);
        }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .alert {
            padding: 13px 20px; border-radius: 9px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px; font-size: 14px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .chart-container { position: relative; height: 280px; margin-top: 15px; }
        .empty-state { text-align: center; padding: 50px 20px; color: #888; }
        .empty-state i { font-size: 55px; margin-bottom: 18px; color: #ddd; }
        .empty-state h3 { color: #555; margin-bottom: 8px; }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 240px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
            .stats-grid { grid-template-columns: 1fr; }
            .container { padding: 18px; }
            .form-grid { grid-template-columns: 1fr; }
            .card-header { flex-direction: column; align-items: flex-start; }
            .course-header { flex-direction: column; align-items: flex-start; }
        }
        
    </style>
</head>
<body>

    <!-- ========== SIDEBAR GAUCHE ========== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>EduPro</span>
            </div>
            <div class="sidebar-user">
                <div class="sidebar-avatar"><?= strtoupper(substr($prenom, 0, 1)) ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= htmlspecialchars($prenom . ' ' . $nom) ?></div>
                    <div class="sidebar-user-role"><?= htmlspecialchars($departement) ?></div>
                </div>
            </div>
        </div>
        
        <nav class="sidebar-menu">
            <button class="menu-item active" onclick="showSection('dashboard', this)">
                <i class="fas fa-chart-line"></i><span>Tableau de bord</span>
            </button>
            <button class="menu-item" onclick="showSection('courses', this)">
                <i class="fas fa-book"></i><span>Mes Cours</span>
            </button>
            <button class="menu-item" onclick="showSection('feedbacks', this)">
                <i class="fas fa-comments"></i><span>Feedbacks</span>
                <?php if($total_feedbacks > 0): ?>
                    <span class="menu-badge"><?= $total_feedbacks ?></span>
                <?php endif; ?>
            </button>
            <button class="menu-item" onclick="showSection('profile', this)">
    <i class="fas fa-user"></i><span>Mon Profil</span>
</button>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <!-- ========== CONTENU PRINCIPAL ========== -->
    <main class="main-content">
        <div class="top-bar">
            <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h1 class="page-title" id="page-title">Tableau de bord</h1>
            <div style="width: 40px;"></div>
        </div>

        <div class="container">
            
            <?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success" id="successMessage">
        <i class="fas fa-check-circle"></i>
        <?php
        $msgs = [
            'course_added' => 'Cours ajouté avec succès !',
            'course_updated' => 'Cours modifié avec succès !',
            'course_deleted' => 'Cours supprimé avec succès !'
        ];
        echo $msgs[$_GET['success']] ?? 'Opération réussie !';
        ?>
    </div>
    <script>
        setTimeout(function() {
            var msg = document.getElementById('successMessage');
            if(msg) {
                msg.style.transition = 'opacity 0.5s';
                msg.style.opacity = '0';
                setTimeout(function() {
                    msg.style.display = 'none';
                }, 500);
            }
        }, 1500);
    </script>
<?php endif; ?>

            <!-- ========== SECTION DASHBOARD ========== -->
            <div id="dashboard" class="section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-book"></i></div>
                        <div class="stat-info"><h3><?= $total_cours ?></h3><p>Cours publiés</p></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-comments"></i></div>
                        <div class="stat-info"><h3><?= $total_feedbacks ?></h3><p>Feedbacks reçus</p></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="fas fa-star"></i></div>
                        <div class="stat-info"><h3><?= number_format($avg_general, 1) ?>/5</h3><p>Note moyenne</p></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                        <div class="stat-info"><h3><?= $total_feedbacks > 0 ? count(array_unique(array_column($tous_feedbacks, 'id_etudiant'))) : 0 ?></h3><p>Étudiants actifs</p></div>
                    </div>
                </div>

                <!-- Graphique -->
                <div class="card">
                    <div class="card-header"><h3 class="card-title">📈 Statistiques des cours</h3></div>
                    <?php if(!empty($chart_labels)): ?>
                        <div class="chart-container"><canvas id="coursesChart"></canvas></div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-bar"></i>
                            <h3>Aucune donnée</h3>
                            <p>Publiez des cours pour voir les statistiques</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Derniers cours -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📚 Derniers cours publiés</h3>
                        <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Nouveau cours</button>
                    </div>
                    <?php if(!empty($mes_cours)): ?>
                        <?php foreach(array_slice($mes_cours, 0, 3) as $c): ?>
                        <div class="course-item">
                            <div class="course-header">
                                <div>
                                    <div class="course-title"><?= htmlspecialchars($c['nom_cours']) ?></div>
                                    <span class="course-module"><?= htmlspecialchars($c['module']) ?></span>
                                    <?php if(!empty($c['has_pdf'])): ?>
                                        <span class="badge badge-pdf" style="margin-left:8px;"><i class="fas fa-file-pdf"></i> PDF</span>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:22px;font-weight:bold;color:#1e3c72;"><?= (int)($c['nb_feedbacks'] ?? 0) ?></div>
                                    <div style="font-size:11px;color:#666;">feedbacks</div>
                                </div>
                            </div>
                            <div class="course-meta">
                                <span><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($c['date_cours'])) ?></span>
                                <span><i class="fas fa-star"></i> <?= number_format((float)($c['avg_rating'] ?? 0), 1) ?>/5</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>Aucun cours</h3>
                            <p>Cliquez sur "Nouveau cours" pour commencer</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========== SECTION COURS ========== -->
            <div id="courses" class="section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📚 Gestion des cours</h3>
                        <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Ajouter un cours</button>
                    </div>
                    
                    <?php if(!empty($mes_cours)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cours</th><th>Module</th><th>Date</th>
                                    <th>PDF</th><th>Feedbacks</th><th>Note</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($mes_cours as $c): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($c['nom_cours']) ?></strong><br>
                                        <small style="color:#666;"><?= htmlspecialchars($c['titre']) ?></small>
                                    </td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($c['module']) ?></span></td>
                                    <td><?= date('d/m/Y', strtotime($c['date_cours'])) ?></td>
                                    <td>
                                        <?php if(!empty($c['has_pdf'])): ?>
                                            <a href="telecharger.php?id=<?= $c['id_cours'] ?>" target="_blank" class="badge badge-pdf" style="text-decoration:none;">
                                                <i class="fas fa-download"></i> Voir
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#999;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int)($c['nb_feedbacks'] ?? 0) ?></td>
                                    <td>
                                        <?php 
                                        $rating = (float)($c['avg_rating'] ?? 0);
                                        if($rating > 0): 
                                            echo str_repeat('★', round($rating)) . str_repeat('☆', 5-round($rating)) . ' ';
                                            echo number_format($rating, 1);
                                        else:
                                            echo '<span style="color:#999;">-</span>';
                                        endif;
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" onclick='editCourse(<?= json_encode($c, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $c['id_cours'] ?>)"><i class="fas fa-trash"></i></button>
                                        <form id="del-form-<?= $c['id_cours'] ?>" method="POST" style="display:none;">
                                            <input type="hidden" name="delete_course" value="1">
                                            <input type="hidden" name="id_cours" value="<?= $c['id_cours'] ?>">
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Aucun cours</h3>
                        <p>Cliquez sur "Ajouter un cours" pour créer votre premier cours</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
          <!-- ========== SECTION PROFIL ENSEIGNANT ========== -->
<div id="profile" class="section">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-circle"></i> Mon Profil</h3>
        </div>
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #1e3c72, #2a5298); display: flex; align-items: center; justify-content: center; color: white; font-size: 32px;">
                <?= strtoupper(substr($prenom, 0, 1)) ?>
            </div>
            <div>
                <h3 style="font-size: 20px; margin-bottom: 5px;"><?= htmlspecialchars($prenom . ' ' . $nom) ?></h3>
                <p style="color: #666;"><?= htmlspecialchars($departement) ?></p>
                <p style="color: #666; font-size: 13px;">Email: <?= htmlspecialchars($email) ?></p>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="stats-grid" style="margin-bottom: 25px;">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-book"></i></div>
                <div class="stat-info"><h3><?= $total_cours ?></h3><p>Cours publiés</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-comments"></i></div>
                <div class="stat-info"><h3><?= $total_feedbacks ?></h3><p>Feedbacks reçus</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-star"></i></div>
                <div class="stat-info"><h3><?= number_format($avg_general, 1) ?>/5</h3><p>Note moyenne</p></div>
            </div>
        </div>
    </div>

    <!-- Formulaire changement mot de passe -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-lock"></i> Modifier mon mot de passe</h3>
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Mot de passe actuel</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Nouveau mot de passe (min. 6 caractères)</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>
            <div class="form-group">
                <label>Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>
            <button type="submit" name="update_password_teacher" class="btn btn-primary">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </form>
    </div>
</div>
            <!-- ========== SECTION FEEDBACKS ========== -->
            <div id="feedbacks" class="section">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">💬 Feedbacks des étudiants</h3></div>
                    
                    <?php if(!empty($tous_feedbacks)): ?>
                        <?php foreach($tous_feedbacks as $fb): ?>
                        <div class="feedback-item">
                            <div class="feedback-header">
                                <div>
                                    <span class="feedback-author">
                                        <i class="fas fa-user-graduate"></i> 
                                        <?= htmlspecialchars(($fb['etudiant_prenom'] ?? '') . ' ' . ($fb['etudiant_nom'] ?? '')) ?>
                                    </span>
                                    <span style="color:#666;margin-left:10px;font-size:13px;">
                                        sur <strong><?= htmlspecialchars($fb['nom_cours']) ?></strong>
                                    </span>
                                </div>
                                <div class="feedback-rating">
                                    <?= str_repeat('★', (int)$fb['note']) ?> <?= $fb['note'] ?>/5
                                </div>
                            </div>
                            <div class="feedback-content"><?= nl2br(htmlspecialchars($fb['commentaire'] ?? '')) ?></div>
                            <div class="feedback-date">
                                <i class="fas fa-clock"></i> <?= date('d/m/Y à H:i', strtotime($fb['date_feedback'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-comment-slash"></i>
                        <h3>Aucun feedback</h3>
                        <p>Les avis des étudiants apparaîtront ici lorsqu'ils en laisseront</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- ========== MODAL AJOUTER COURS ========== -->
    <div id="modalAdd" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Ajouter un cours</h2>
                <button class="close-modal" onclick="closeModal('modalAdd')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                           <label>Nom du cours *</label>
                           <input type="text" name="nom_cours" required placeholder="Ex: Algorithmique">
                        </div>
              
                         <div class="form-group">
                            <label>Module *</label>
                            <input type="text" name="module" required placeholder="Ex: Algoritmique">
                         </div>
                    </div>
                    <div class="form-group">
                        <label>Titre *</label>
                        <input type="text" name="titre" required placeholder="Ex: Chapitre 1 - Introduction">
                    </div>
                    <div class="form-group">
                        <label>Description (optionnel si PDF fourni)</label>
                        <textarea name="contenu_text" rows="4" placeholder="Description détaillée du cours..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Fichier PDF *</label>
                        <input type="file" name="pdf_file" accept=".pdf" required>
                        <small style="color:#888;display:block;margin-top:5px;">Max 10MB • Format PDF uniquement • Stocké en base de données</small>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:25px;">
                        <button type="button" class="btn" style="background:#e0e4e8;color:#333;" onclick="closeModal('modalAdd')">Annuler</button>
                        <button type="submit" name="add_course" class="btn btn-primary"><i class="fas fa-save"></i> Publier le cours</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== MODAL MODIFIER COURS ========== -->
    <div id="modalEdit" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Modifier le cours</h2>
                <button class="close-modal" onclick="closeModal('modalEdit')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_cours" id="edit_id">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nom du cours *</label>
                            <input type="text" name="nom_cours" id="edit_nom" required>
                        </div>
                        <div class="form-group">
                            <label>Module *</label>
                            <input type="text" name="module" id="edit_mod" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Titre *</label>
                        <input type="text" name="titre" id="edit_titre" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="contenu_text" id="edit_cont" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Nouveau PDF (laisser vide pour garder l'actuel)</label>
                        <input type="file" name="pdf_file" accept=".pdf">
                        <small style="color:#888;display:block;margin-top:5px;">Max 10MB • Remplacera le PDF actuel</small>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:25px;">
                        <button type="button" class="btn" style="background:#e0e4e8;color:#333;" onclick="closeModal('modalEdit')">Annuler</button>
                        <button type="submit" name="edit_course" class="btn btn-primary"><i class="fas fa-save"></i> Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== JAVASCRIPT ========== -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Navigation entre sections
        window.showSection = function(sectionName, btn) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
            
            const section = document.getElementById(sectionName);
            if(section) section.classList.add('active');
            if(btn) btn.classList.add('active');
            
            const titles = {
                'dashboard': 'Tableau de bord',
                'courses': 'Mes Cours', 
                'feedbacks': 'Feedbacks',
                'profile': 'Mon Profil'
            };
            document.getElementById('page-title').textContent = titles[sectionName] || 'Dashboard';
            
            if(window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        };

        // Toggle sidebar mobile
        window.toggleSidebar = function() {
            document.getElementById('sidebar').classList.toggle('active');
        };

        // Modals
        window.openAddModal = function() { 
            document.getElementById('modalAdd').classList.add('active'); 
        };
        
        window.closeModal = function(id) { 
            document.getElementById(id).classList.remove('active'); 
        };
        
        window.editCourse = function(c) {
            try {
                document.getElementById('edit_id').value = c.id_cours || '';
                document.getElementById('edit_nom').value = c.nom_cours || '';
                document.getElementById('edit_mod').value = c.module || '';
                document.getElementById('edit_titre').value = c.titre || '';
                document.getElementById('edit_cont').value = ''; // On ne peut pas pré-remplir le PDF
                document.getElementById('modalEdit').classList.add('active');
            } catch(e) {
                console.error('Erreur editCourse:', e);
                alert('Erreur lors de l\'édition du cours');
            }
        };

        // Confirmation suppression
        window.confirmDelete = function(id) {
            if(confirm('⚠️ Êtes-vous sûr de vouloir supprimer ce cours ?\nCette action est irréversible.')) {
                document.getElementById('del-form-' + id).submit();
            }
        };

        // Fermer modal en cliquant dehors
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if(e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Fermer avec Échap
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
            }
        });

        // Chart.js - Graphique des statistiques
        <?php if(!empty($chart_labels)): ?>
        const chartCtx = document.getElementById('coursesChart');
        if(chartCtx) {
            new Chart(chartCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chart_labels, JSON_HEX_APOS) ?>,
                    datasets: [
                        {
                            label: 'Nombre de feedbacks',
                            data: <?= json_encode($chart_feedbacks) ?>,
                            backgroundColor: 'rgba(30, 60, 114, 0.75)',
                            borderColor: '#1e3c72',
                            borderWidth: 2,
                            borderRadius: 6,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Note moyenne /5',
                            data: <?= json_encode($chart_ratings) ?>,
                            type: 'line',
                            borderColor: '#ffa726',
                            backgroundColor: 'rgba(255, 167, 38, 0.15)',
                            borderWidth: 3,
                            pointBackgroundColor: '#ffa726',
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { font: { size: 12 } } }
                    },
                    scales: {
                        y: { 
                            type: 'linear', display: true, position: 'left', beginAtZero: true,
                            title: { display: true, text: 'Feedbacks', font: { size: 12 } },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        y1: { 
                            type: 'linear', display: true, position: 'right', beginAtZero: true, max: 5,
                            title: { display: true, text: 'Note /5', font: { size: 12 } },
                            grid: { drawOnChartArea: false }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
        <?php endif; ?>
        
    });
    </script>
</body>
</html>