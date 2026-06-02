-- ============================================================
-- BASE DE DONNÉES : site_waterpolo (version complète)
-- Compatible avec l'import de feuilles de match officielles
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `site_waterpolo`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE `site_waterpolo`;

-- ============================================================
-- TABLES DE RÉFÉRENCE
-- ============================================================

DROP TABLE IF EXISTS `saison`;
CREATE TABLE `saison` (
  `id_saison`  INT AUTO_INCREMENT PRIMARY KEY,
  `saison`     YEAR NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `niveau`;
CREATE TABLE `niveau` (
  `id_niveau` INT AUTO_INCREMENT PRIMARY KEY,
  `niveau`    VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `championnat`;
CREATE TABLE `championnat` (
  `id_championnat`  INT AUTO_INCREMENT PRIMARY KEY,
  `nom_championnat` VARCHAR(100) NOT NULL,
  `id_saison`       INT NOT NULL,
  `id_niveau`       INT NOT NULL,
  FOREIGN KEY (`id_saison`) REFERENCES `saison`(`id_saison`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_niveau`) REFERENCES `niveau`(`id_niveau`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `structure`;
CREATE TABLE `structure` (
  `id_structure`    INT AUTO_INCREMENT PRIMARY KEY,
  `nom_structure`   VARCHAR(100) NOT NULL,
  `lieu_structure`  VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ÉQUIPES
-- ============================================================

DROP TABLE IF EXISTS `equipe`;
CREATE TABLE `equipe` (
  `id_equipe`   INT AUTO_INCREMENT PRIMARY KEY,
  `nom_equipe`  VARCHAR(100) NOT NULL UNIQUE,
  `logo_equipe` VARCHAR(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- JOUEURS
-- ============================================================

DROP TABLE IF EXISTS `joueur`;
CREATE TABLE `joueur` (
  `id_joueur`        INT AUTO_INCREMENT PRIMARY KEY,
  `iuf`              VARCHAR(20)  NOT NULL,
  `nom_joueur`       VARCHAR(100) NOT NULL,
  -- prenom / nationalite : complétés par l'entraîneur via le site (absents de la feuille de match)
  `prenom`           VARCHAR(100) DEFAULT NULL,
  `nationalite`      VARCHAR(50)  DEFAULT NULL,
  `annee_naissance`  YEAR,
  `id_equipe`        INT          NOT NULL,
  UNIQUE KEY `uq_iuf` (`iuf`),
  FOREIGN KEY (`id_equipe`) REFERENCES `equipe`(`id_equipe`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- OFFICIELS : arbitres, délégués, secrétaires, chronos, juges
-- ============================================================

DROP TABLE IF EXISTS `officiel`;
CREATE TABLE `officiel` (
  `id_officiel`   INT AUTO_INCREMENT PRIMARY KEY,
  `nom_prenom`    VARCHAR(100) NOT NULL,
  `iuf`           VARCHAR(20)  DEFAULT NULL,
  -- role : ARBITRE | SECRETAIRE | CHRONO | JUGE_BUT | DELEGUE_FFN
  `role`          ENUM('ARBITRE','SECRETAIRE','CHRONO','JUGE_BUT','DELEGUE_FFN') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- MATCH
-- ============================================================

DROP TABLE IF EXISTS `matchs`;
CREATE TABLE `matchs` (
  `id_matchs`           INT AUTO_INCREMENT PRIMARY KEY,
  `date_matchs`         DATE NOT NULL,
  `heure_matchs`        TIME NOT NULL,
  `id_equipe_domicile`  INT  NOT NULL,
  `id_equipe_visiteur`  INT  NOT NULL,
  `id_championnat`      INT  NOT NULL,
  `id_structure`        INT  NOT NULL,
  -- Résultat final
  `score_domicile`      INT  DEFAULT 0,
  `score_visiteur`      INT  DEFAULT 0,
  FOREIGN KEY (`id_equipe_domicile`) REFERENCES `equipe`(`id_equipe`)       ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_equipe_visiteur`) REFERENCES `equipe`(`id_equipe`)       ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_championnat`)     REFERENCES `championnat`(`id_championnat`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_structure`)       REFERENCES `structure`(`id_structure`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- OFFICIELS AFFECTÉS À UN MATCH
-- ============================================================

DROP TABLE IF EXISTS `match_officiel`;
CREATE TABLE `match_officiel` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `id_matchs`     INT NOT NULL,
  `id_officiel`   INT NOT NULL,
  FOREIGN KEY (`id_matchs`)   REFERENCES `matchs`(`id_matchs`)     ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_officiel`) REFERENCES `officiel`(`id_officiel`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DÉLÉGUÉS DE CLUB (par match)
-- ============================================================

DROP TABLE IF EXISTS `delegue_club`;
CREATE TABLE `delegue_club` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `id_matchs`     INT          NOT NULL,
  `nom_prenom`    VARCHAR(100) NOT NULL,
  -- couleur_bonnet : B (blanc/domicile) ou N (noir/visiteur)
  `couleur`       CHAR(1)      NOT NULL,
  FOREIGN KEY (`id_matchs`) REFERENCES `matchs`(`id_matchs`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- STAFF D'ÉQUIPE PAR MATCH
-- ============================================================

DROP TABLE IF EXISTS `staff_match`;
CREATE TABLE `staff_match` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `id_matchs`    INT          NOT NULL,
  `id_equipe`    INT          NOT NULL,
  `nom_prenom`   VARCHAR(100) NOT NULL,
  -- role : ENTRAINEUR | ADJOINT | SUPPLEANT
  `role`         ENUM('ENTRAINEUR','ADJOINT','SUPPLEANT') NOT NULL,
  FOREIGN KEY (`id_matchs`) REFERENCES `matchs`(`id_matchs`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_equipe`) REFERENCES `equipe`(`id_equipe`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- FEUILLE DE MATCH : participation d'un joueur à un match
-- ============================================================

DROP TABLE IF EXISTS `participation`;
CREATE TABLE `participation` (
  `id_participation`  INT AUTO_INCREMENT PRIMARY KEY,
  `id_matchs`         INT NOT NULL,
  `id_joueur`         INT NOT NULL,
  `numero_bonnet`     INT NOT NULL,
  -- exclu = joueur exclu définitivement (croix dans la colonne X)
  `exclu`             TINYINT(1) DEFAULT 0,
  `buts`              INT DEFAULT 0,
  UNIQUE KEY `uq_match_joueur` (`id_matchs`, `id_joueur`),
  FOREIGN KEY (`id_matchs`) REFERENCES `matchs`(`id_matchs`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_joueur`) REFERENCES `joueur`(`id_joueur`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PÉRIODES
-- ============================================================

DROP TABLE IF EXISTS `periode`;
CREATE TABLE `periode` (
  `id_periode`   INT AUTO_INCREMENT PRIMARY KEY,
  `num_periode`  INT NOT NULL,
  `id_matchs`    INT NOT NULL,
  -- scores de fin de période
  `score_B`      INT DEFAULT 0,
  `score_N`      INT DEFAULT 0,
  UNIQUE KEY `uq_periode_match` (`id_matchs`, `num_periode`),
  FOREIGN KEY (`id_matchs`) REFERENCES `matchs`(`id_matchs`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TEMPS MORTS PAR ÉQUIPE ET PÉRIODE
-- ============================================================

DROP TABLE IF EXISTS `temps_mort`;
CREATE TABLE `temps_mort` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `id_matchs`    INT    NOT NULL,
  `id_equipe`    INT    NOT NULL,
  `num_periode`  INT    NOT NULL,
  -- nombre de temps morts pris dans cette période (0 ou 1 par équipe/période)
  `nb`           INT    DEFAULT 0,
  FOREIGN KEY (`id_matchs`) REFERENCES `matchs`(`id_matchs`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_equipe`) REFERENCES `equipe`(`id_equipe`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- EXCLUSIONS PAR JOUEUR ET PÉRIODE (3 colonnes Code/Période)
-- ============================================================

DROP TABLE IF EXISTS `exclusion_periode`;
CREATE TABLE `exclusion_periode` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `id_matchs`       INT         NOT NULL,
  `id_joueur`       INT         NOT NULL,
  `num_occurrence`  INT         NOT NULL,   -- 1, 2 ou 3
  `code`            VARCHAR(10) DEFAULT NULL,
  `num_periode`     INT         DEFAULT NULL,
  FOREIGN KEY (`id_matchs`) REFERENCES `matchs`(`id_matchs`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_joueur`) REFERENCES `joueur`(`id_joueur`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- CHRONO GÉNÉRAL : tous les événements du match
-- ============================================================

DROP TABLE IF EXISTS `evenement`;
CREATE TABLE `evenement` (
  `id_evenement`  INT AUTO_INCREMENT PRIMARY KEY,
  `id_matchs`     INT         NOT NULL,
  `id_periode`    INT         NOT NULL,
  -- temps restant dans la période (ex: '07:58')
  `temps`         VARCHAR(10) NOT NULL,
  -- couleur_bonnet joueur impliqué : B ou N (NULL si non applicable)
  `couleur`       CHAR(1)     DEFAULT NULL,
  -- numéro de bonnet du joueur impliqué
  `numero_bonnet` INT         DEFAULT NULL,
  -- BUT | FAUTE | PENALTY | EXCLUSION | TEMPS_MORT | EDA | EDAP | CJE | CR | ACCIDENT
  `code_action`   VARCHAR(20) NOT NULL,
  -- score après l'événement (ex: '1-0')
  `score`         VARCHAR(10) DEFAULT NULL,
  FOREIGN KEY (`id_matchs`)  REFERENCES `matchs`(`id_matchs`)   ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_periode`) REFERENCES `periode`(`id_periode`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
