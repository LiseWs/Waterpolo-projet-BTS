<?php
/**
 * db.php
 * - Conçu pour XAMPP/MySQL
 * - Vérifie/crée la base de données site_waterpolo et les tables si nécessaire
 * - Fournit $pdo (PDO connecté à la base)
 *
 * Usage:
 *  require_once __DIR__ . '/db.php';
 *  // $pdo est disponible
 *
 * Configuration:
 *  Vous pouvez surcharger les paramètres via les variables d'environnement :
 *  DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME, DB_DEBUG
 */

// Configuration par défaut (XAMPP)
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'site_waterpolo';
$DB_DEBUG = (bool) (getenv('DB_DEBUG') ?: false);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // PDO::ATTR_EMULATE_PREPARES => false, // décommentez si besoin
];

function log_debug($msg) {
    global $DB_DEBUG;
    if ($DB_DEBUG) {
        error_log('[db.php] ' . $msg);
    }
}

/**
 * Connecte au serveur MySQL sans DB (pour créer la DB si besoin)
 */
function connectServerPDO($host, $port, $user, $pass, $options) {
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    return new PDO($dsn, $user, $pass, $options);
}

/**
 * Connecte PDO à la base de données ciblée
 */
function connectDatabasePDO($host, $port, $user, $pass, $dbname, $options) {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    return new PDO($dsn, $user, $pass, $options);
}

try {
    // 1) Connexion au serveur MySQL
    $serverPdo = connectServerPDO($DB_HOST, $DB_PORT, $DB_USER, $DB_PASS, $options);
    log_debug("Connecté au serveur MySQL {$DB_HOST}:{$DB_PORT} en tant que {$DB_USER}");

    // 2) Créer la base si nécessaire
    $createDbSql = "CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '``', $DB_NAME) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $serverPdo->exec($createDbSql);
    log_debug("Vérification/création de la base '{$DB_NAME}' effectuée.");

    // 3) Connexion à la base de données
    $pdo = connectDatabasePDO($DB_HOST, $DB_PORT, $DB_USER, $DB_PASS, $DB_NAME, $options);
    log_debug("Connecté à la base de données '{$DB_NAME}'.");

    // 4) Vérifier si la table sentinel existe (ici 'joueur')
    $stmt = $pdo->query("SHOW TABLES LIKE 'joueur'");
    $exists = $stmt->fetch();

    if (!$exists) {
        log_debug("Table 'joueur' introuvable — création des tables nécessaires.");

        // Créer chaque table séparément pour plus de robustesse
        // Utilisez InnoDB pour supporter les clés étrangères
        $pdo->beginTransaction();
        try {
            // equipe
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS equipe (
                    id_equipe INT AUTO_INCREMENT PRIMARY KEY,
                    nom VARCHAR(120) NOT NULL,
                    short_code VARCHAR(20) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // joueur
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS joueur (
                    id_joueur INT AUTO_INCREMENT PRIMARY KEY,
                    prenom_joueur VARCHAR(120) NOT NULL,
                    nom_joueur VARCHAR(120) DEFAULT NULL,
                    numero_bonnet INT DEFAULT NULL,
                    id_equipe INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_joueur_equipe FOREIGN KEY (id_equipe) REFERENCES equipe (id_equipe) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // matchs
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS matchs (
                    id_match INT AUTO_INCREMENT PRIMARY KEY,
                    date_match DATETIME DEFAULT NULL,
                    equipe1_id INT DEFAULT NULL,
                    equipe2_id INT DEFAULT NULL,
                    score_team1 INT DEFAULT 0,
                    score_team2 INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_matchs_equipe1 FOREIGN KEY (equipe1_id) REFERENCES equipe (id_equipe) ON DELETE SET NULL,
                    CONSTRAINT fk_matchs_equipe2 FOREIGN KEY (equipe2_id) REFERENCES equipe (id_equipe) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // but (goal)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `but` (
                    id_but INT AUTO_INCREMENT PRIMARY KEY,
                    temps VARCHAR(16) DEFAULT NULL, -- format 'MM:SS' ou 'HH:MM:SS'
                    id_joueur INT NOT NULL,
                    id_matchs INT NOT NULL,
                    id_periode TINYINT NOT NULL DEFAULT 1,
                    id_equipe INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_but_joueur FOREIGN KEY (id_joueur) REFERENCES joueur (id_joueur) ON DELETE CASCADE,
                    CONSTRAINT fk_but_match FOREIGN KEY (id_matchs) REFERENCES matchs (id_match) ON DELETE CASCADE,
                    CONSTRAINT fk_but_equipe FOREIGN KEY (id_equipe) REFERENCES equipe (id_equipe) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // evenement (generic events)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS evenement (
                    id_evenement INT AUTO_INCREMENT PRIMARY KEY,
                    type VARCHAR(50) NOT NULL, -- ex: 'but','exclusion','carton','accident','reclamation','temps_mort','possession','entree_sortie'
                    temps VARCHAR(16) DEFAULT NULL,
                    details JSON DEFAULT NULL,
                    id_joueur INT DEFAULT NULL,
                    id_matchs INT DEFAULT NULL,
                    id_periode TINYINT DEFAULT NULL,
                    id_equipe INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_evenement_joueur FOREIGN KEY (id_joueur) REFERENCES joueur (id_joueur) ON DELETE SET NULL,
                    CONSTRAINT fk_evenement_match FOREIGN KEY (id_matchs) REFERENCES matchs (id_match) ON DELETE CASCADE,
                    CONSTRAINT fk_evenement_equipe FOREIGN KEY (id_equipe) REFERENCES equipe (id_equipe) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // users (pour auth/API token)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(120) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) DEFAULT NULL,
                    api_token VARCHAR(255) DEFAULT NULL,
                    role VARCHAR(50) DEFAULT 'operator',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // logs (audit)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS logs (
                    id_log INT AUTO_INCREMENT PRIMARY KEY,
                    action VARCHAR(255) NOT NULL,
                    meta JSON DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Optionnel : insérer quelques données par défaut
            $pdo->exec("
                INSERT INTO equipe (nom, short_code) VALUES
                ('Équipe 1', 'E1'),
                ('Équipe 2', 'E2')
                ON DUPLICATE KEY UPDATE nom=VALUES(nom);
            ");

            $pdo->commit();
            log_debug("Création des tables terminée avec succès.");
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur lors de la création des tables: " . $e->getMessage());
            throw $e;
        }
    } else {
        log_debug("Tables déjà présentes, pas de création nécessaire.");
    }

    // Expose $pdo globalement (déjà défini)
    // Fournir une fonction utilitaire pour récupérer la connexion
    if (!function_exists('getPDO')) {
        function getPDO() {
            global $pdo;
            return $pdo;
        }
    }

} catch (Exception $e) {
    // Échec global - afficher message léger mais logger l'erreur complète
    error_log("db.php - erreur : " . $e->getMessage());
    // Si vous êtes en dev, affichez l'erreur (DB_DEBUG=true). Sinon renvoyez une erreur générique.
    if ($DB_DEBUG) {
        // Affichage lisible pour le développement
        header('Content-Type: text/plain; charset=utf-8');
        echo "Erreur d'accès à la base de données : " . $e->getMessage();
    } else {
        // En production, ne divulguez pas le détail
        http_response_code(500);
        echo json_encode(['error' => 'Erreur de configuration de la base de données. Consultez les logs serveur.']);
    }
    exit;
}