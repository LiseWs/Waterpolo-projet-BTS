USE site_waterpolo;

CREATE TABLE IF NOT EXISTS evenement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_match INT NOT NULL,
    id_joueur INT,
    id_equipe INT NOT NULL,
    type_evenement VARCHAR(50) NOT NULL,
    details VARCHAR(255),
    temps_chrono VARCHAR(10),
    horodatage DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 