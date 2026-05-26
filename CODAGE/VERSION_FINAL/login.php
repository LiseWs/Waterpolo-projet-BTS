<?php
// 1. Toujours démarrer la session en tout premier, sans aucun espace avant
session_start();

$error = "";

// 2. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Simple système de connexion avec identifiants fixes
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['user'] = $username;
        header('Location: upload_match.php');
        exit;
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link href="css/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            background: #f0f4f8;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        h1 {
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.5rem;
        }

        /* Formulaire de connexion centré */
        .login-form {
            background-color: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box; /* Évite que l'input dépasse du cadre */
        }

        input[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: #d9534f;
            background: #f2dede;
            border: 1px solid #ebccd1;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .menu {
            margin-top: 30px;
        }

        .menu ul {
            list-style: none;
            padding: 0;
        }

        .bouton a {
            display: block;
            margin: 8px 0;
            padding: 12px 20px;
            background-color: orange;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            width: 250px;
            text-align: center;
            font-weight: bold;
            transition: background 0.3s;
        }

        .bouton a:hover {
            background-color: darkorange;
            color: white;
        }
    </style>
</head>
<body>

    <div class="login-form">
        <h1>Connexion à l'administration</h1>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="username">Nom d'utilisateur :</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" required>

            <input type="submit" value="Se connecter">
        </form>
    </div>

    <nav class="menu">
        <ul>
            <li class="bouton"><a href="index1.php">Résultat</a></li>
            <li class="bouton"><a href="Meilleur_buteurs.php">Meilleurs buteurs</a></li>
            <li class="bouton"><a href="règle_water-polo.php">Réglement</a></li>
            <li class="bouton"><a href="affichage_feuille_match.php">Feuille de Match</a></li>
        </ul>
    </nav>

</body>
</html>