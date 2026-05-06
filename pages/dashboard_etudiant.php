<?php
// pages/dashboard_etudiant.php - Dashboard étudiant
require_once __DIR__ . '/../config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'fonctions.php';

if (!isEtudiantConnecte()) {
    redirect('login.php');
}

$id_etudiant = $_SESSION['id_etudiant'];
$prenom = $_SESSION['prenom'] ?? 'Étudiant';
$nom = $_SESSION['nom'] ?? '';
$filiere = $_SESSION['filiere'] ?? '';
$annee = $_SESSION['annee'] ?? '';
$photo_profil = $_SESSION['photo_profil'] ?? '';

// Modification du mot de passe
if (isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT mot_de_passe FROM etudiant WHERE id_etudiant = ?");
    $stmt->execute([$id_etudiant]);
    $user = $stmt->fetch();
    
    if ($user && verifyPassword($current_pass, $user['mot_de_passe'])) {
        if (strlen($new_pass) < 6) {
            $error = "⚠️ Le nouveau mot de passe doit contenir au moins 6 caractères.";
        } elseif ($new_pass !== $confirm_pass) {
            $error = "⚠️ Les mots de passe ne correspondent pas.";
        } else {
            $hashed_pass = hashPassword($new_pass);
            $stmt = $pdo->prepare("UPDATE etudiant SET mot_de_passe = ? WHERE id_etudiant = ?");
            $stmt->execute([$hashed_pass, $id_etudiant]);
            $success = "✅ Mot de passe modifié avec succès !";
        }
    } else {
        $error = "⚠️ Mot de passe actuel incorrect.";
    }
}

// Ajouter un feedback
if (isset($_POST['add_feedback'])) {
    $id_cours = (int)$_POST['id_cours'];
    $note = (int)$_POST['note'];
    $commentaire = trim($_POST['commentaire'] ?? '');
    
    if ($note < 1 || $note > 5) {
        $error = "⚠️ La note doit être entre 1 et 5.";
    } elseif (empty($commentaire)) {
        $error = "⚠️ Veuillez écrire un commentaire.";
    } else {
        $stmt = $pdo->prepare("SELECT id_cours FROM cours WHERE id_cours = ?");
        $stmt->execute([$id_cours]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("SELECT id_feedback FROM feedback WHERE id_etudiant = ? AND id_cours = ?");
            $stmt->execute([$id_etudiant, $id_cours]);
            
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE feedback SET commentaire = ?, note = ?, date_feedback = NOW() WHERE id_etudiant = ? AND id_cours = ?");
                $stmt->execute([$commentaire, $note, $id_etudiant, $id_cours]);
                $success = "✅ Feedback mis à jour !";
            } else {
                $stmt = $pdo->prepare("INSERT INTO feedback (commentaire, note, id_etudiant, id_cours, date_feedback) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$commentaire, $note, $id_etudiant, $id_cours]);
                $success = "✅ Feedback ajouté !";
            }
        } else {
            $error = "⚠️ Cours introuvable.";
        }
    }
}

// Supprimer un feedback
if (isset($_POST['delete_feedback'])) {
    $id_cours = (int)$_POST['id_cours'];
    
    $stmt = $pdo->prepare("DELETE FROM feedback WHERE id_etudiant = ? AND id_cours = ?");
    $stmt->execute([$id_etudiant, $id_cours]);
    $success = "✅ Votre commentaire a été supprimé !";
    
    header("Location: dashboard_etudiant.php");
    exit();
}

// Récupérer tous les cours
$stmt = $pdo->prepare("
    SELECT c.*, 
           e.nom as prof_nom, e.prenom as prof_prenom, e.departement,
           (SELECT commentaire FROM feedback WHERE id_etudiant = ? AND id_cours = c.id_cours LIMIT 1) as mon_commentaire,
           (SELECT note FROM feedback WHERE id_etudiant = ? AND id_cours = c.id_cours LIMIT 1) as ma_note,
           (SELECT COUNT(*) FROM feedback WHERE id_cours = c.id_cours) as total_feedbacks,
           (SELECT AVG(note) FROM feedback WHERE id_cours = c.id_cours) as note_moyenne
    FROM cours c
    LEFT JOIN enseignant e ON c.id_enseignant = e.id_enseignant
    ORDER BY c.date_cours DESC
");
$stmt->execute([$id_etudiant, $id_etudiant]);
$cours_list = $stmt->fetchAll();

// Statistiques personnelles
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM feedback WHERE id_etudiant = ?");
$stmt->execute([$id_etudiant]);
$stats = $stmt->fetch();
$total_feedbacks_given = $stats['total'] ?? 0;

// Fonction utilitaire : détecter si le contenu est un PDF
function isPdfContent($data) {
    if (!$data || !is_string($data) || strlen($data) < 5) return false;
    $header = substr($data, 0, 5);
    return $header === '%PDF-' || strpos($data, '%PDF') === 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace - Étudiant | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; }
        body { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); color: #1e293b; min-height: 100vh; }
        
        /* SIDEBAR */
        .sidebar { width: 260px; background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%); color: white; position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; transition: all 0.3s ease; z-index: 1000; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-logo { font-size: 24px; font-weight: bold; margin-bottom: 5px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .sidebar-user { display: flex; align-items: center; gap: 12px; margin-top: 20px; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 10px; }
        .sidebar-avatar { width: 40px; height: 40px; border-radius: 50%; background: white; color: #1e3c72; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; flex-shrink: 0; overflow: hidden; }
        .sidebar-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .sidebar-user-info { overflow: hidden; text-align: left; }
        .sidebar-user-name { font-weight: 600; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-user-role { font-size: 11px; opacity: 0.9; }
        .sidebar-menu { padding: 15px 0; }
        .menu-item { padding: 14px 25px; display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.85); text-decoration: none; transition: all 0.2s; cursor: pointer; border-left: 4px solid transparent; background: none; border: none; width: 100%; text-align: left; font-size: 14px; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.15); color: white; border-left-color: #4facfe; }
        .menu-item i { font-size: 18px; width: 22px; text-align: center; }
        .menu-badge { margin-left: auto; background: #4facfe; color: white; padding: 3px 9px; border-radius: 10px; font-size: 11px; font-weight: 600; }
        .sidebar-footer { position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-btn { width: 100%; padding: 11px; background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.25); border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
        .logout-btn:hover { background: rgba(255,255,255,0.25); }
        
        /* CONTENU PRINCIPAL */
        .main-content { flex: 1; margin-left: 260px; transition: margin-left 0.3s ease; }
        .top-bar { background: white; padding: 18px 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .page-title { font-size: 22px; color: #2c3e50; font-weight: 600; }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; cursor: pointer; color: #1e3c72; }
        .container { padding: 25px 30px; max-width: 800px; margin: 0 auto; width: 100%; }
        .section { display: none; animation: fadeIn 0.3s ease; }
        .section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* STATS CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 28px; }
        .stat-card { background: white; border-radius: 14px; padding: 22px; box-shadow: 0 3px 12px rgba(0,0,0,0.07); display: flex; align-items: center; gap: 18px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { width: 55px; height: 55px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
        .stat-icon.blue { background: #e3f2fd; color: #1e3c72; }
        .stat-icon.green { background: #e8f5e9; color: #2e7d32; }
        .stat-icon.orange { background: #fff3e0; color: #ef6c00; }
        .stat-info h3 { font-size: 26px; color: #2c3e50; margin-bottom: 3px; font-weight: 700; }
        .stat-info p { color: #7f8c8d; font-size: 13px; }
        
        /* BOUTONS */
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.2s; display: inline-flex; align-items: center; gap: 7px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(30,60,114,0.35); }
        .btn-submit { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; border: none; padding: 10px 24px; border-radius: 25px; font-weight: 600; font-size: 13px; cursor: pointer; }
        
        /* CARDS & FORMS */
        .card { background: white; border-radius: 14px; padding: 24px; box-shadow: 0 3px 12px rgba(0,0,0,0.07); margin-bottom: 22px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #f0f2f5; flex-wrap: wrap; gap: 12px; }
        .card-title { font-size: 19px; color: #2c3e50; font-weight: 600; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 7px; color: #444; font-weight: 500; font-size: 13px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 11px 13px; border: 2px solid #e0e4e8; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #1e3c72; box-shadow: 0 0 0 3px rgba(30,60,114,0.1); }
        .alert { padding: 13px 20px; border-radius: 9px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        
        /* COURSE CARD */
        .course-card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; transition: all 0.3s ease; margin-bottom: 24px; position: relative; }
        .course-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(30,60,114,0.15); }
        .course-header { padding: 16px 20px; background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .course-prof { display: flex; align-items: center; gap: 12px; }
        .prof-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; }
        .prof-info .name { font-weight: 700; font-size: 14px; color: #1e293b; }
        .prof-info .dept { color: #64748b; font-size: 12px; margin-top: 2px; }
        .course-badge { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .course-title { padding: 18px 20px 12px; font-size: 17px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #f1f5f9; }
        .course-body { padding: 16px 20px; font-size: 14px; line-height: 1.7; color: #475569; }
        .course-meta { padding: 14px 20px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #64748b; }
        .course-stats { display: flex; align-items: center; gap: 16px; }
        .feedback-form { padding: 18px 20px 20px; display: flex; flex-direction: column; gap: 14px; }
        .feedback-textarea { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 14px; min-height: 80px; }
        .stars-container { display: flex; gap: 4px; direction: rtl; }
        .stars-container input { display: none; }
        .stars-container label { font-size: 24px; color: #cbd5e1; cursor: pointer; transition: color 0.15s ease; }
        .stars-container label:hover, .stars-container label:hover ~ label, .stars-container input:checked ~ label { color: #f59e0b; }
        .empty-state { text-align: center; padding: 50px 20px; color: #64748b; background: white; border-radius: 14px; }
        .empty-state i { font-size: 48px; margin-bottom: 16px; color: #cbd5e1; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 240px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
            .container { padding: 18px; }
        }
        
        /* Bouton PDF */
        .btn-pdf-small { background: #ef4444; color: white; padding: 5px 12px; border-radius: 20px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn-pdf-small:hover { background: #dc2626; transform: translateY(-1px); }
        
        /* PDF Viewer */
        .pdf-viewer { width: 100%; height: 400px; border: 1px solid #e2e8f0; border-radius: 8px; margin-top: 10px; }
        
        /* Layout avec calendrier */
        .dashboard-wrapper { display: flex; gap: 30px; padding: 25px 30px; }
        .main-content-area { flex: 2; min-width: 0; }
        .sidebar-calendar { flex: 1; min-width: 280px; }
        .calendar-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .calendar-header { text-align: center; margin-bottom: 20px; }
        .calendar-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
        .calendar-nav button { background: #1e3c72; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; }
        #calendar { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; text-align: center; }
        .weekday { font-size: 12px; font-weight: bold; padding: 8px 0; }
        .calendar-day { padding: 8px 0; border-radius: 8px; cursor: pointer; }
        .calendar-day:hover { background: #e3f2fd; }
        .calendar-day.today { background: #1e3c72; color: white; }
        .calendar-day.other-month { color: #ccc; }
        
        @media (max-width: 992px) {
            .dashboard-wrapper { flex-direction: column; }
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-graduation-cap"></i>
                <span>EduPro</span>
            </div>
            <div class="sidebar-user">
                <div class="sidebar-avatar">
                    <?php if(!empty($photo_profil) && file_exists(__DIR__ . '/../' . $photo_profil)): ?>
                        <img src="../<?= htmlspecialchars($photo_profil) ?>" alt="Photo">
                    <?php else: ?>
                        <?= strtoupper(substr($prenom, 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= htmlspecialchars($prenom . ' ' . $nom) ?></div>
                    <div class="sidebar-user-role"><?= htmlspecialchars($filiere) ?> • Année <?= htmlspecialchars($annee) ?></div>
                </div>
            </div>
        </div>
        
        <nav class="sidebar-menu">
            <button class="menu-item active" onclick="showSection('feed', this)">
                <i class="fas fa-home"></i><span>Accueil</span>
            </button>
            <button class="menu-item" onclick="showSection('profile', this)">
                <i class="fas fa-user"></i><span>Mon Profil</span>
            </button>
            <button class="menu-item" onclick="showSection('my-feedbacks', this)">
                <i class="fas fa-comments"></i><span>Mes Feedbacks</span>
                <?php if($total_feedbacks_given > 0): ?>
                    <span class="menu-badge"><?= $total_feedbacks_given ?></span>
                <?php endif; ?>
            </button>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h1 class="page-title" id="page-title">Mes Cours</h1>
            <div style="width: 40px;"></div>
        </div>

        <div class="dashboard-wrapper">
          <div class="main-content-area">
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if(!empty($success)): ?>
                <div class="alert alert-success" id="successMessage">
                     <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
                <script>
                    setTimeout(function() {
                        var msg = document.getElementById('successMessage');
                        if(msg) msg.style.display = 'none';
                    }, 1500);
                </script>
                <?php unset($success); ?>
            <?php endif; ?>

            <!-- SECTION PROFIL -->
            <section id="profile" class="section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user"></i> Mon Profil</h3>
                    </div>
                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px;">
                        <div class="sidebar-avatar" style="width: 80px; height: 80px; font-size: 32px;">
                            <?php if(!empty($photo_profil) && file_exists(__DIR__ . '/../' . $photo_profil)): ?>
                                <img src="../<?= htmlspecialchars($photo_profil) ?>" alt="Photo" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <?= strtoupper(substr($prenom, 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h3 style="font-size: 20px; margin-bottom: 5px;"><?= htmlspecialchars($prenom . ' ' . $nom) ?></h3>
                            <p style="color: #666;"><?= htmlspecialchars($filiere) ?> • Année <?= htmlspecialchars($annee) ?></p>
                            <p style="color: #666; font-size: 13px;">CNE: <?= htmlspecialchars($_SESSION['cne'] ?? '') ?></p>
                        </div>
                    </div>
                    
                    <div class="stats-grid" style="margin-bottom: 25px;">
                        <div class="stat-card">
                            <div class="stat-icon green"><i class="fas fa-comments"></i></div>
                            <div class="stat-info"><h3><?= $total_feedbacks_given ?></h3><p>Feedbacks donnés</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon blue"><i class="fas fa-book"></i></div>
                            <div class="stat-info"><h3><?= count($cours_list) ?></h3><p>Cours disponibles</p></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-lock"></i> Modifier mon mot de passe</h3>
                    </div>
                    <form method="POST">
                        <div class="form-group">
                            <label>Mot de passe actuel</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label>Nouveau mot de passe (min. 6 caractères)</label>
                            <input type="password" name="new_password" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Confirmer le nouveau mot de passe</label>
                            <input type="password" name="confirm_password" required minlength="6">
                        </div>
                        <button type="submit" name="update_password" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </form>
                </div>
            </section>

            <!-- SECTION FEED (COURS) -->
            <section id="feed" class="section active">
                
                <?php if(empty($cours_list)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Aucun cours disponible</h3>
                        <p>Les cours publiés par vos enseignants apparaîtront ici</p>
                    </div>
                <?php else: ?>
                    <?php foreach($cours_list as $cours): 
                        $a_feedback = !empty($cours['mon_commentaire']);
                        $ma_note = $cours['ma_note'] ?? 0;
                        
                        // Détection PDF
                        $contenu = $cours['contenu'] ?? null;
                        $is_pdf = isPdfContent($contenu);
                        $has_content = !empty($contenu);
                    ?>
                    <div class="course-card">
                        <div class="course-header">
                            <div class="course-prof">
                                <div class="prof-avatar"><?= strtoupper(substr($cours['prof_prenom'] ?? 'P', 0, 1)) ?></div>
                                <div class="prof-info">
                                    <div class="name"><?= htmlspecialchars(($cours['prof_prenom'] ?? '') . ' ' . ($cours['prof_nom'] ?? '')) ?></div>
                                    <div class="dept"><?= htmlspecialchars($cours['departement'] ?? '') ?></div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <?php if($is_pdf): ?>
                                <a href="telecharger.php?id=<?= $cours['id_cours'] ?>" target="_blank" class="btn-pdf-small">
                                    <i class="fas fa-file-pdf"></i> Télécharger PDF
                                </a>
                                <?php endif; ?>
                                <span class="course-badge"><?= htmlspecialchars($cours['module']) ?></span>
                            </div>
                        </div>

                        <h3 class="course-title"><?= htmlspecialchars($cours['titre']) ?></h3>

                        <!-- Contenu du cours : gestion LONGBLOB PDF -->
                        <div class="course-body">
                            <?php if($is_pdf): ?>
                                <!-- Affichage pour fichier PDF -->
                                <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 10px; margin-bottom: 15px;">
                                    <i class="fas fa-file-pdf" style="font-size: 48px; color: #ef4444; margin-bottom: 10px;"></i>
                                    <p style="color: #475569; margin-bottom: 15px;">
                                        <strong>Document PDF disponible</strong><br>
                                        <small>Cliquez sur le bouton ci-dessus pour le télécharger</small>
                                    </p>
                                    
                                    <!-- Optionnel : aperçu intégré -->
                                    <details style="margin-top: 10px;">
                                        <summary style="cursor: pointer; color: #1e3c72; font-weight: 500;">
                                            <i class="fas fa-eye"></i> Voir un aperçu
                                        </summary>
                                        <embed 
                                            src="telecharger.php?id=<?= $cours['id_cours'] ?>#view=FitH" 
                                            type="application/pdf" 
                                            class="pdf-viewer"
                                        />
                                    </details>
                                </div>
                                
                            <?php elseif($has_content): ?>
                                <!-- Affichage pour texte simple -->
                                <?= nl2br(htmlspecialchars($contenu)) ?>
                                
                            <?php else: ?>
                                <!-- Aucun contenu -->
                                <p style="color: #64748b; font-style: italic;">
                                    <i class="fas fa-info-circle"></i> Aucune description ou document disponible pour ce cours.
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="course-meta">
                            <span><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($cours['date_cours'])) ?></span>
                            <div class="course-stats">
                                <span><i class="fas fa-star"></i> <?= number_format($cours['note_moyenne'] ?? 0, 1) ?>/5</span>
                                <span><i class="fas fa-comment"></i> <?= (int)$cours['total_feedbacks'] ?> avis</span>
                            </div>
                        </div>

                        <form method="POST" class="feedback-form">
                            <input type="hidden" name="id_cours" value="<?= $cours['id_cours'] ?>">
                            
                            <textarea class="feedback-textarea" name="commentaire" placeholder="Qu'avez-vous pensé de ce cours ? Votre avis aide les autres étudiants..." required><?= htmlspecialchars($cours['mon_commentaire'] ?? '') ?></textarea>
                            
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                                <div class="stars-container">
                                    <input type="radio" name="note" id="n5-<?= $cours['id_cours'] ?>" value="5" <?= $ma_note==5?'checked':'' ?>>
                                    <label for="n5-<?= $cours['id_cours'] ?>">★</label>
                                    <input type="radio" name="note" id="n4-<?= $cours['id_cours'] ?>" value="4" <?= $ma_note==4?'checked':'' ?>>
                                    <label for="n4-<?= $cours['id_cours'] ?>">★</label>
                                    <input type="radio" name="note" id="n3-<?= $cours['id_cours'] ?>" value="3" <?= $ma_note==3?'checked':'' ?>>
                                    <label for="n3-<?= $cours['id_cours'] ?>">★</label>
                                    <input type="radio" name="note" id="n2-<?= $cours['id_cours'] ?>" value="2" <?= $ma_note==2?'checked':'' ?>>
                                    <label for="n2-<?= $cours['id_cours'] ?>">★</label>
                                    <input type="radio" name="note" id="n1-<?= $cours['id_cours'] ?>" value="1" <?= $ma_note==1?'checked':'' ?>>
                                    <label for="n1-<?= $cours['id_cours'] ?>">★</label>
                                </div>
                                <div style="display:flex; gap:10px;">
                                    <button type="submit" name="add_feedback" class="btn-submit">
                                        <i class="fas fa-paper-plane"></i>
                                        <?= $a_feedback ? 'Mettre à jour' : 'Publier mon avis' ?>
                                    </button>
    
                                    <?php if($a_feedback): ?>
                                    <button type="submit" name="delete_feedback" class="btn-submit" style="background:#ef5350;" onclick="return confirm('Supprimer votre commentaire ?')">
                                     <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- SECTION MES FEEDBACKS -->
            <section id="my-feedbacks" class="section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-comments"></i> Mes Feedbacks</h3>
                    </div>
                    
                    <?php
                    $stmt_my_fb = $pdo->prepare("
                        SELECT f.*, c.titre as nom_cours, c.module
                        FROM feedback f
                        JOIN cours c ON f.id_cours = c.id_cours
                        WHERE f.id_etudiant = ?
                        ORDER BY f.date_feedback DESC
                    ");
                    $stmt_my_fb->execute([$id_etudiant]);
                    $my_feedbacks_list = $stmt_my_fb->fetchAll();
                    ?>
                    
                    <?php if(empty($my_feedbacks_list)): ?>
                        <div class="empty-state">
                            <i class="fas fa-comment-slash"></i>
                            <h3>Aucun feedback encore</h3>
                            <p>Retournez sur l'accueil pour donner votre premier avis sur un cours</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($my_feedbacks_list as $fb): ?>
                        <div style="background: #fafbfc; border-radius: 10px; padding: 15px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <strong style="color: #1e3c72;"><?= htmlspecialchars($fb['nom_cours']) ?></strong>
                                <span style="background:#1e3c72; color:white; padding:2px 10px; border-radius:12px; font-size:11px;"><?= htmlspecialchars($fb['module']) ?></span>
                            </div>
                            <div style="font-size: 14px; color: #333; line-height: 1.5; margin-bottom: 10px;"><?= nl2br(htmlspecialchars($fb['commentaire'])) ?></div>
                            <div style="display: flex; gap: 15px; font-size: 12px;">
                                <span style="color: #f59e0b;"><?= str_repeat('★', (int)$fb['note']) ?> <?= $fb['note'] ?>/5</span>
                                <span style="color: #999;"><?= date('d/m/Y à H:i', strtotime($fb['date_feedback'])) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

        </div>
        
        <!-- Calendrier à droite -->
        <div class="sidebar-calendar">
            <div class="calendar-card">
                <div class="calendar-header">
                    <h3><i class="fas fa-calendar-alt"></i> Calendrier</h3>
                    <div class="calendar-nav">
                        <button id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                        <span id="monthYear"></span>
                        <button id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div id="calendar"></div>
            </div>
        </div>
    </div>
    </main>

    <script>
        // Calendrier
        (function() {
            let currentDate = new Date();
            
            function renderCalendar() {
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth();
                
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                const startDayOfWeek = firstDay.getDay();
                const daysInMonth = lastDay.getDate();
                
                const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                                   'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                document.getElementById('monthYear').textContent = `${monthNames[month]} ${year}`;
                
                let calendarHtml = '';
                const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
                weekDays.forEach(day => {
                    calendarHtml += `<div class="weekday">${day}</div>`;
                });
                
                let startOffset = startDayOfWeek === 0 ? 6 : startDayOfWeek - 1;
                
                const prevMonthLastDay = new Date(year, month, 0).getDate();
                for (let i = startOffset - 1; i >= 0; i--) {
                    calendarHtml += `<div class="calendar-day other-month">${prevMonthLastDay - i}</div>`;
                }
                
                const today = new Date();
                const isCurrentMonth = today.getMonth() === month && today.getFullYear() === year;
                
                for (let day = 1; day <= daysInMonth; day++) {
                    const isToday = isCurrentMonth && day === today.getDate();
                    calendarHtml += `<div class="calendar-day ${isToday ? 'today' : ''}">${day}</div>`;
                }
                
                const totalDisplayed = startOffset + daysInMonth;
                for (let day = 1; day <= 42 - totalDisplayed; day++) {
                    calendarHtml += `<div class="calendar-day other-month">${day}</div>`;
                }
                
                document.getElementById('calendar').innerHTML = calendarHtml;
            }
            
            document.getElementById('prevMonth').addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar();
            });
            
            document.getElementById('nextMonth').addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar();
            });
            
            renderCalendar();
        })();

        // Navigation entre sections
        function showSection(sectionName, btn) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
            
            const section = document.getElementById(sectionName);
            if(section) section.classList.add('active');
            if(btn) btn.classList.add('active');
            
            const titles = {
                'feed': 'Mes Cours',
                'profile': 'Mon Profil', 
                'my-feedbacks': 'Mes Feedbacks'
            };
            document.getElementById('page-title').textContent = titles[sectionName] || 'Dashboard';
            
            const calendar = document.querySelector('.sidebar-calendar');
            if(calendar) {
                calendar.style.display = (sectionName === 'feed' || sectionName === 'my-feedbacks') ? 'block' : 'none';
            }
            
            if(window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            showSection('feed', document.querySelector('.menu-item.active'));
        });
    </script>
</body>
</html>