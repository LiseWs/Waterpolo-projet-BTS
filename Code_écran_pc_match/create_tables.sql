USE site_waterpolo;

-- Table des équipes
CREATE TABLE IF NOT EXISTS equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des joueurs
CREATE TABLE IF NOT EXISTS joueurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenom_joueur VARCHAR(100) NOT NULL,
    id_equipe INT,
    FOREIGN KEY (id_equipe) REFERENCES equipes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des matchs
CREATE TABLE IF NOT EXISTS matchs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_equipe1 INT,
    id_equipe2 INT,
    date_match DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_equipe1) REFERENCES equipes(id),
    FOREIGN KEY (id_equipe2) REFERENCES equipes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des événements
CREATE TABLE IF NOT EXISTS evenement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_match INT NOT NULL,
    id_joueur INT,
    id_equipe INT NOT NULL,
    type_evenement VARCHAR(50) NOT NULL,
    details VARCHAR(255),
    temps_chrono VARCHAR(10),
    horodatage DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_match) REFERENCES matchs(id),
    FOREIGN KEY (id_joueur) REFERENCES joueurs(id),
    FOREIGN KEY (id_equipe) REFERENCES equipes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 