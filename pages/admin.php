<?php
// pages/admin.php - Panneau d'administration
require_once __DIR__ . '/../config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'fonctions.php';
// Vérification de l'authentification admin
if (!isAdminConnecte()) {
    redirect('login_admin.php');
}




// CRUD Étudiants
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'save_student') {
        $id = $_POST['id'];
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $cne = $_POST['cne'];
        $email = $_POST['email'];
        $filiere = $_POST['filiere'];
        $annee = $_POST['annee'];
        $pass = $_POST['mot_de_passe'];
 
        if (empty($id)) {
            $hashed_pass = hashPassword($pass);
            $sql = "INSERT INTO etudiant (nom, prenom, cne, email, mot_de_passe, filiere, annee) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$nom, $prenom, $cne, $email, $hashed_pass, $filiere, $annee]);
        } else {
            if (!empty($pass)) {
                $hashed_pass = hashPassword($pass);
                $sql = "UPDATE etudiant SET nom=?, prenom=?, cne=?, email=?, mot_de_passe=?, filiere=?, annee=? WHERE id_etudiant=?";
                $pdo->prepare($sql)->execute([$nom, $prenom, $cne, $email, $hashed_pass, $filiere, $annee, $id]);
            } else {
                $sql = "UPDATE etudiant SET nom=?, prenom=?, cne=?, email=?, filiere=?, annee=? WHERE id_etudiant=?";
                $pdo->prepare($sql)->execute([$nom, $prenom, $cne, $email, $filiere, $annee, $id]);
            }
        }
        header("Location: admin.php?success=student_saved");
        exit();
    }

    if ($action == 'save_teacher') {
        $id = $_POST['id'];
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $numero = $_POST['numero'];
        $email = $_POST['email'];
        $departement = $_POST['departement'];
        $pass = $_POST['mot_de_passe'];

        if (empty($id)) {
            $hashed_pass = hashPassword($pass);
            $sql = "INSERT INTO enseignant (nom, prenom, numero, email, mot_de_passe, departement) VALUES (?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$nom, $prenom, $numero, $email, $hashed_pass, $departement]);
        } else {
            if (!empty($pass)) {
                $hashed_pass = hashPassword($pass);
                $sql = "UPDATE enseignant SET nom=?, prenom=?, numero=?, email=?, mot_de_passe=?, departement=? WHERE id_enseignant=?";
                $pdo->prepare($sql)->execute([$nom, $prenom, $numero, $email, $hashed_pass, $departement, $id]);
            } else {
                $sql = "UPDATE enseignant SET nom=?, prenom=?, numero=?, email=?, departement=? WHERE id_enseignant=?";
                $pdo->prepare($sql)->execute([$nom, $prenom, $numero, $email, $departement, $id]);
            }
        }
        header("Location: admin.php?success=teacher_saved");
        exit();
    }

    if ($action == 'delete') {
        $table = $_POST['table'];
        $id_val = $_POST['item_id'];
        if ($table == 'etudiant') {
            $pdo->prepare("DELETE FROM etudiant WHERE id_etudiant = ?")->execute([$id_val]);
        } else {
            $pdo->prepare("DELETE FROM enseignant WHERE id_enseignant = ?")->execute([$id_val]);
        }
        header("Location: admin.php?success=deleted");
        exit();
    }
}

// Récupération des données
$students = $pdo->query("SELECT * FROM etudiant ORDER BY id_etudiant DESC")->fetchAll();
$teachers = $pdo->query("SELECT * FROM enseignant ORDER BY id_enseignant DESC")->fetchAll();

$total_students = count($students);
$total_teachers = count($teachers);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Gestion Scolaire</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f5f7fa; display: flex; min-height: 100vh; }
        
        .sidebar { width: 260px; background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%); color: white; position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; z-index: 1000; }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-logo { font-size: 24px; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .sidebar-menu { padding: 20px 0; }
        .menu-item { padding: 15px 25px; display: flex; align-items: center; gap: 15px; color: rgba(255,255,255,0.8); text-decoration: none; cursor: pointer; border-left: 4px solid transparent; background: none; border: none; width: 100%; text-align: left; font-size: 15px; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); color: white; border-left-color: #4facfe; }
        .menu-item i { font-size: 20px; width: 25px; }
        .menu-badge { margin-left: auto; background: #4facfe; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
        .sidebar-footer { position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-btn { width: 100%; padding: 12px; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; }
        
        .main-content { flex: 1; margin-left: 260px; }
        .top-bar { background: white; padding: 20px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .page-title { font-size: 24px; color: #333; font-weight: 600; }
        .menu-toggle { display: none; background: none; border: none; font-size: 24px; cursor: pointer; color: #1e3c72; }
        .container { padding: 30px; }
        .section { display: none; }
        .section.active { display: block; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 20px; }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; }
        .stat-icon.green { background: #e8f5e9; color: #2e7d32; }
        .stat-icon.blue { background: #e3f2fd; color: #1e3c72; }
        .stat-info h3 { font-size: 28px; color: #333; margin-bottom: 5px; }
        .stat-info p { color: #666; font-size: 14px; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-danger { background: #ef5350; color: white; }
        .btn-warning { background: #ffa726; color: white; }
        
        .card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; }
        .card-title { font-size: 20px; color: #333; font-weight: bold; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f5f7fa; font-weight: 600; color: #555; }
        tr:hover { background: #f9f9f9; }
        
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #c8e6c9; color: #2e7d32; }
        .badge-info { background: #e3f2fd; color: #1e3c72; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 15px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-size: 20px; }
        .close-modal { background: none; border: none; color: white; font-size: 28px; cursor: pointer; }
        .modal-body { padding: 25px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; color: #555; font-weight: 500; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #1e3c72; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #c8e6c9; color: #2e7d32; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
            .stats-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo"><i class="fas fa-university"></i> ADMIN</div>
        </div>
        <nav class="sidebar-menu">
            <button class="menu-item active" onclick="showSection('dashboard')"><i class="fas fa-home"></i><span>Accueil</span></button>
            <button class="menu-item" onclick="showSection('students')"><i class="fas fa-user-graduate"></i><span>Étudiants</span><span class="menu-badge"><?= $total_students ?></span></button>
            <button class="menu-item" onclick="showSection('teachers')"><i class="fas fa-chalkboard-teacher"></i><span>Enseignants</span><span class="menu-badge"><?= $total_teachers ?></span></button>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h1 class="page-title" id="page-title">Tableau de bord</h1>
            <div style="width: 40px;"></div>
        </div>

        <div class="container">
            
            <?php if(isset($_GET['success'])): ?>
                 <div class="alert alert-success" id="successMessage">
                    <i class="fas fa-check-circle"></i> Opération réussie !
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

            <div id="dashboard" class="section active">
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-user-graduate"></i></div><div class="stat-info"><h3><?= $total_students ?></h3><p>Étudiants inscrits</p></div></div>
                    <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-chalkboard-teacher"></i></div><div class="stat-info"><h3><?= $total_teachers ?></h3><p>Enseignants</p></div></div>
                    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-users"></i></div><div class="stat-info"><h3><?= $total_students + $total_teachers ?></h3><p>Total utilisateurs</p></div></div>
                </div>
                <div class="card">
                    <div class="card-header"><h3 class="card-title">📋 Accès rapide</h3></div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <button class="btn btn-primary" onclick="showSection('students')"><i class="fas fa-user-graduate"></i> Gérer les étudiants</button>
                        <button class="btn btn-primary" onclick="showSection('teachers')"><i class="fas fa-chalkboard-teacher"></i> Gérer les enseignants</button>
                    </div>
                </div>
            </div>

            <div id="students" class="section">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">📚 Liste des Étudiants</h3><button class="btn btn-primary" onclick="openModal('modal-student')"><i class="fas fa-plus"></i> Ajouter</button></div>
                    <?php if(!empty($students)): ?>
                    <div class="table-container">
                        <table>
                            <thead><tr><th>ID</th><th>Nom</th><th>Prénom</th><th>CNE</th><th>Email</th><th>Filière</th><th>Année</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($students as $s): ?>
                                <tr>
                                    <td><?= $s['id_etudiant'] ?></td>
                                    <td><?= htmlspecialchars($s['nom']) ?></td>
                                    <td><?= htmlspecialchars($s['prenom']) ?></td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($s['cne']) ?></span></td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td><?= htmlspecialchars($s['filiere']) ?></td>
                                    <td><?= htmlspecialchars($s['annee']) ?></td>
                                    <td>
                                        <button class="btn btn-warning" onclick='editItem("student", <?= json_encode($s, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' style="padding:5px 10px;"><i class="fas fa-edit"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?');">
                                            <input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="etudiant"><input type="hidden" name="item_id" value="<?= $s['id_etudiant'] ?>">
                                            <button type="submit" class="btn btn-danger" style="padding:5px 10px;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fas fa-inbox"></i><h3>Aucun étudiant</h3></div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="teachers" class="section">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">👨‍🏫 Liste des Enseignants</h3><button class="btn btn-primary" onclick="openModal('modal-teacher')"><i class="fas fa-plus"></i> Ajouter</button></div>
                    <?php if(!empty($teachers)): ?>
                    <div class="table-container">
                        <table>
                            <thead><tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Numéro</th><th>Email</th><th>Département</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($teachers as $t): ?>
                                <tr>
                                    <td><?= $t['id_enseignant'] ?></td>
                                    <td><?= htmlspecialchars($t['nom']) ?></td>
                                    <td><?= htmlspecialchars($t['prenom']) ?></td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($t['numero']) ?></span></td>
                                    <td><?= htmlspecialchars($t['email']) ?></td>
                                    <td><?= htmlspecialchars($t['departement']) ?></td>
                                    <td>
                                        <button class="btn btn-warning" onclick='editItem("teacher", <?= json_encode($t, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' style="padding:5px 10px;"><i class="fas fa-edit"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?');">
                                            <input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="enseignant"><input type="hidden" name="item_id" value="<?= $t['id_enseignant'] ?>">
                                            <button type="submit" class="btn btn-danger" style="padding:5px 10px;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fas fa-inbox"></i><h3>Aucun enseignant</h3></div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- MODAL ÉTUDIANT -->
    <div id="modal-student" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2><i class="fas fa-plus"></i> Ajouter un étudiant</h2><button class="close-modal" onclick="closeModal('modal-student')">&times;</button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_student"><input type="hidden" name="id" id="s_id">
                    <div class="form-grid"><div class="form-group"><label>Nom *</label><input type="text" name="nom" id="s_nom" required></div><div class="form-group"><label>Prénom *</label><input type="text" name="prenom" id="s_prenom" required></div></div>
                    <div class="form-grid"><div class="form-group"><label>CNE *</label><input type="text" name="cne" id="s_cne" required></div><div class="form-group"><label>Année *</label><select name="annee" id="s_annee" required><option value="1">1ère année</option><option value="2">2ème année</option><option value="3">3ème année</option></select></div></div>
                    <div class="form-group"><label>Email *</label><input type="email" name="email" id="s_email" required></div>
                    <div class="form-group"><label>Mot de passe</label><input type="password" name="mot_de_passe" id="s_pass" placeholder="Requis si nouveau"></div>
                    <div class="form-group"><label>Filière *</label><input type="text" name="filiere" id="s_filiere" required></div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;"><button type="button" class="btn" style="background:#e0e0e0;" onclick="closeModal('modal-student')">Annuler</button><button type="submit" class="btn btn-primary">Enregistrer</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL ENSEIGNANT -->
    <div id="modal-teacher" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2><i class="fas fa-plus"></i> Ajouter un enseignant</h2><button class="close-modal" onclick="closeModal('modal-teacher')">&times;</button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_teacher"><input type="hidden" name="id" id="t_id">
                    <div class="form-grid"><div class="form-group"><label>Nom *</label><input type="text" name="nom" id="t_nom" required></div><div class="form-group"><label>Prénom *</label><input type="text" name="prenom" id="t_prenom" required></div></div>
                    <div class="form-group"><label>Numéro *</label><input type="text" name="numero" id="t_num" required></div>
                    <div class="form-group"><label>Email *</label><input type="email" name="email" id="t_email" required></div>
                    <div class="form-group"><label>Mot de passe</label><input type="password" name="mot_de_passe" id="t_pass" placeholder="Requis si nouveau"></div>
                    <div class="form-group"><label>Département *</label><input type="text" name="departement" id="t_dept" required></div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;"><button type="button" class="btn" style="background:#e0e0e0;" onclick="closeModal('modal-teacher')">Annuler</button><button type="submit" class="btn btn-primary">Enregistrer</button></div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showSection(sectionName) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
            document.getElementById(sectionName).classList.add('active');
            document.querySelector(`.menu-item[onclick="showSection('${sectionName}')"]`).classList.add('active');
            const titles = {'dashboard':'Tableau de bord','students':'Étudiants','teachers':'Enseignants'};
            document.getElementById('page-title').textContent = titles[sectionName];
            if(window.innerWidth <= 768) document.getElementById('sidebar').classList.remove('active');
        }
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); }
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        
        function editItem(type, data) {
            if(type === 'student') {
                openModal('modal-student');
                document.getElementById('s_id').value = data.id_etudiant || '';
                document.getElementById('s_nom').value = data.nom || '';
                document.getElementById('s_prenom').value = data.prenom || '';
                document.getElementById('s_cne').value = data.cne || '';
                document.getElementById('s_email').value = data.email || '';
                document.getElementById('s_filiere').value = data.filiere || '';
                document.getElementById('s_annee').value = data.annee || '1';
                document.getElementById('s_pass').value = '';
                document.querySelector('#modal-student .modal-header h2').innerHTML = '<i class="fas fa-edit"></i> Modifier étudiant';
            } else {
                openModal('modal-teacher');
                document.getElementById('t_id').value = data.id_enseignant || '';
                document.getElementById('t_nom').value = data.nom || '';
                document.getElementById('t_prenom').value = data.prenom || '';
                document.getElementById('t_num').value = data.numero || '';
                document.getElementById('t_email').value = data.email || '';
                document.getElementById('t_dept').value = data.departement || '';
                document.getElementById('t_pass').value = '';
                document.querySelector('#modal-teacher .modal-header h2').innerHTML = '<i class="fas fa-edit"></i> Modifier enseignant';
            }
        }
        
        window.onclick = e => { if(e.target.classList.contains('modal')) e.target.classList.remove('active'); }
        document.addEventListener('keydown', e => { if(e.key === 'Escape') document.querySelectorAll('.modal.active').forEach(m => closeModal(m.id)); });
    </script>
</body>
</html>