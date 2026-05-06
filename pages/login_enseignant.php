<?php
// pages/login_enseignant.php - Connexion Espace Enseignant
require_once __DIR__ . '/../config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'fonctions.php';

if (isEnseignantConnecte()) {
    redirect('dashboard_enseignant.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "⚠️ Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM enseignant WHERE email = ?");
        $stmt->execute([$email]);
        $enseignant = $stmt->fetch();

        if ($enseignant) {
            if (verifyPassword($password, $enseignant['mot_de_passe'])) {
                session_regenerate_id(true);
                
                $_SESSION['id_enseignant'] = $enseignant['id_enseignant'];
                $_SESSION['nom'] = $enseignant['nom'];
                $_SESSION['prenom'] = $enseignant['prenom'];
                $_SESSION['email'] = $enseignant['email'];
                $_SESSION['departement'] = $enseignant['departement'];
                $_SESSION['numero'] = $enseignant['numero'];
                $_SESSION['role'] = 'enseignant';
                
                redirect('dashboard_enseignant.php');
            } else {
                $error = "⚠️ Mot de passe incorrect.";
                error_log("Tentative de connexion échouée pour l'email enseignant: $email");
            }
        } else {
            $error = "⚠️ Aucun compte enseignant trouvé avec cet email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Espace Enseignant | <?= SITE_NAME ?></title>
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
            max-width: 450px;
            overflow: hidden;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            position: relative;
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
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: #1e3c72;
            outline: none;
            box-shadow: 0 0 0 3px rgba(30,60,114,0.1);
        }

        .form-group i {
            position: absolute;
            right: 15px;
            top: 42px;
            color: #aaa;
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
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30,60,114,0.4);
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
            margin: 0 10px;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-chalkboard-teacher"></i>
            <h2>Espace Enseignant</h2>
            <p>Connectez-vous à votre espace professionnel</p>
        </div>

        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Adresse Email Professionnelle</label>
                    <input type="email" name="email" placeholder="enseignant@edu.uiz.ac.ma" required>
                    <i class="fas fa-envelope"></i>
                </div>

                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                    <i class="fas fa-lock"></i>
                </div>

                <button type="submit" class="btn-login">
                    Se connecter <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                </button>
            </form>

            <div class="footer-links">
                <a href="../index.php"><i class="fas fa-home"></i> Accueil</a>
            </div>
        </div>
    </div>
</body>
</html>