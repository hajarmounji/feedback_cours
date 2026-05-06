<?php
// pages/login_admin.php - Connexion Administrateur
require_once __DIR__ . '/../config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'fonctions.php';

// Redirection si déjà connecté
if (isAdminConnecte()) {
    redirect('admin.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($identifiant) || empty($password)) {
        $error = "⚠️ Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM administrateur WHERE identifiant = ? OR email = ?");
        $stmt->execute([$identifiant, $identifiant]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['mot_de_passe'])) {
            session_regenerate_id(true);
            
            $_SESSION['id_administrateur'] = $admin['id_administrateur'];
            $_SESSION['admin_identifiant'] = $admin['identifiant'];
            $_SESSION['admin_nom'] = $admin['nom'];
            $_SESSION['admin_prenom'] = $admin['prenom'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin'] = true;
            
            redirect('admin.php');
        } else {
            $error = "⚠️ Identifiant ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #2a5298 0%, #f0f1f4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .login-header i {
            font-size: 60px;
            margin-bottom: 15px;
        }
        .login-header h2 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .login-body {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 15px;
        }
        .form-group input:focus {
            border-color: #1e3c72;
            outline: none;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-login:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        .footer-links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .footer-links a {
            color: #1e3c72;
            text-decoration: none;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-user-cog"></i>
            <h2>Administrateur</h2>
            <p>Connectez-vous à votre espace</p>
        </div>
        <div class="login-body">
            <?php if(!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Identifiant </label>
                    <div style="position: relative;">
                      <input type="text" name="identifiant" placeholder="Votre login" required>
                      <i class="fas fa-user" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #aaa;"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <div style="position: relative;">
                         <input type="password" name="password" placeholder="••••••••" required>
                         <i class="fas fa-lock" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #aaa;"></i>
                    </div>
                     <div style="text-align: center; margin-top: 10px;">
                         <button type="submit" class="btn-login">
                          Se connecter <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                         </button>
                     </div>
                </div>
            </form>
            <div class="footer-links">
                <a href="../index.php"><i class="fas fa-home"></i> Accueil</a>
            </div>
        </div>
    </div>
</body>
</html>