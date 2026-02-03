-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le :  mar. 18 juin 2024 à 14:38
-- Version du serveur :  8.0.18
-- Version de PHP :  7.4.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `site_waterpolo`
--
CREATE DATABASE IF NOT EXISTS `site_waterpolo` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `site_waterpolo`;

-- --------------------------------------------------------

--
-- Structure de la table `arbitre`
--

DROP TABLE IF EXISTS `arbitre`;
CREATE TABLE IF NOT EXISTS `arbitre` (
  `id_arbitre` int(11) NOT NULL AUTO_INCREMENT,
  `nom_arbitre` varchar(25) NOT NULL,
  `prenom_arbitre` varchar(25) NOT NULL,
  PRIMARY KEY (`id_arbitre`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `arbitre`
--

INSERT INTO `arbitre` (`id_arbitre`, `nom_arbitre`, `prenom_arbitre`) VALUES
(1, 'CARRY', 'Philipe');

-- --------------------------------------------------------

--
-- Structure de la table `but`
--

DROP TABLE IF EXISTS `but`;
CREATE TABLE IF NOT EXISTS `but` (
  `id_but` int(11) NOT NULL AUTO_INCREMENT,
  `temps` time NOT NULL,
  `id_joueur` int(11) NOT NULL,
  `id_matchs` int(11) NOT NULL,
  `id_periode` int(11) NOT NULL,
  `id_equipe` int(11) NOT NULL,
  PRIMARY KEY (`id_but`),
  KEY `id_periode` (`id_periode`),
  KEY `id_joueur` (`id_joueur`),
  KEY `id_equipe` (`id_equipe`),
  KEY `id_matchs` (`id_matchs`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `but`
--

INSERT INTO `but` (`id_but`, `temps`, `id_joueur`, `id_matchs`, `id_periode`, `id_equipe`) VALUES
(1, '00:06:13', 1, 3, 1, 1),
(2, '00:03:13', 2, 3, 2, 1),
(6, '00:04:05', 3, 3, 4, 2),
(8, '00:05:04', 1, 3, 3, 1),
(9, '00:06:17', 4, 3, 2, 2),
(10, '00:04:24', 1, 3, 3, 1),
(11, '00:01:09', 3, 3, 4, 2),
(12, '00:04:09', 2, 3, 2, 1),
(13, '00:05:22', 6, 6, 2, 3),
(14, '00:03:08', 5, 5, 2, 3);

-- --------------------------------------------------------

--
-- Structure de la table `championnat`
--

DROP TABLE IF EXISTS `championnat`;
CREATE TABLE IF NOT EXISTS `championnat` (
  `id_championnat` int(11) NOT NULL AUTO_INCREMENT,
  `nom_championnat` varchar(50) NOT NULL,
  `id_saison` int(11) NOT NULL,
  `id_niveau` int(11) NOT NULL,
  PRIMARY KEY (`id_championnat`),
  KEY `id_saison` (`id_saison`),
  KEY `id_niveau` (`id_niveau`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `championnat`
--

INSERT INTO `championnat` (`id_championnat`, `nom_championnat`, `id_saison`, `id_niveau`) VALUES
(1, 'FF NATATION - ELITE Masculine', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `equipe`
--

DROP TABLE IF EXISTS `equipe`;
CREATE TABLE IF NOT EXISTS `equipe` (
  `id_equipe` int(11) NOT NULL AUTO_INCREMENT,
  `nom_equipe` varchar(25) NOT NULL,
  `logo_equipe` varchar(100) NOT NULL,
  PRIMARY KEY (`id_equipe`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `equipe`
--

INSERT INTO `equipe` (`id_equipe`, `nom_equipe`, `logo_equipe`) VALUES
(1, 'Lion', 'images/logo_Lion.jpg'),
(2, 'Aigle royal', 'images/logo_Aigle_Royal.png'),
(3, 'Team Panther', 'images/logo_Team_Panther.jpg'),
(4, 'Wolf', 'images/logo_Wolf.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `joueur`
--

DROP TABLE IF EXISTS `joueur`;
CREATE TABLE IF NOT EXISTS `joueur` (
  `id_joueur` int(11) NOT NULL AUTO_INCREMENT,
  `nom_joueur` varchar(25) NOT NULL,
  `prenom_joueur` varchar(25) NOT NULL,
  `annee_naissance` year(4) NOT NULL,
  `numero_bonnet` int(11) NOT NULL,
  `numero_licence` int(11) NOT NULL,
  `id_equipe` int(11) NOT NULL,
  PRIMARY KEY (`id_joueur`),
  KEY `id_equipe` (`id_equipe`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `joueur`
--

INSERT INTO `joueur` (`id_joueur`, `nom_joueur`, `prenom_joueur`, `annee_naissance`, `numero_bonnet`, `numero_licence`, `id_equipe`) VALUES
(1, 'Frezouls', 'Sébastien', 2004, 1, 487392, 1),
(2, 'Lafeuille', 'Jean', 2000, 2, 12748493, 1),
(3, 'Reaper', 'Franck', 1998, 1, 24879914, 2),
(4, 'Poirier', 'Thiery', 2001, 2, 13048824, 2),
(5, 'Jasper', 'Karim', 1999, 1, 124525, 3),
(6, 'Rotermond', 'Adrien', 2000, 2, 764567, 3),
(7, 'Morer', 'Quentin', 1996, 1, 234244, 4),
(8, 'Fournier', 'Lorie', 1995, 2, 903239, 4);

-- --------------------------------------------------------

--
-- Structure de la table `matchs`
--

DROP TABLE IF EXISTS `matchs`;
CREATE TABLE IF NOT EXISTS `matchs` (
  `id_matchs` int(11) NOT NULL AUTO_INCREMENT,
  `date_matchs` date NOT NULL,
  `heure_matchs` time NOT NULL,
  `id_equipe_domicile` int(11) NOT NULL,
  `id_equipe_visiteur` int(11) NOT NULL,
  `id_championnat` int(11) NOT NULL,
  `id_structure` int(11) NOT NULL,
  `id_arbitre` int(11) NOT NULL,
  PRIMARY KEY (`id_matchs`),
  KEY `id_equipe_domicile` (`id_equipe_domicile`),
  KEY `id_equipe_visiteur` (`id_equipe_visiteur`),
  KEY `id_championnat` (`id_championnat`),
  KEY `id_structure` (`id_structure`),
  KEY `id_arbitre` (`id_arbitre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `matchs`
--

INSERT INTO `matchs` (`id_matchs`, `date_matchs`, `heure_matchs`, `id_equipe_domicile`, `id_equipe_visiteur`, `id_championnat`, `id_structure`, `id_arbitre`) VALUES
(3, '2024-05-15', '16:00:00', 1, 2, 1, 1, 1),
(4, '2024-05-18', '16:00:00', 2, 1, 1, 1, 1),
(5, '2024-06-11', '20:30:00', 1, 3, 1, 1, 1),
(6, '2024-06-14', '19:00:00', 4, 3, 1, 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `niveau`
--

DROP TABLE IF EXISTS `niveau`;
CREATE TABLE IF NOT EXISTS `niveau` (
  `id_niveau` int(11) NOT NULL AUTO_INCREMENT,
  `niveau` varchar(50) NOT NULL,
  PRIMARY KEY (`id_niveau`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `niveau`
--

INSERT INTO `niveau` (`id_niveau`, `niveau`) VALUES
(1, 'FF Natation');

-- --------------------------------------------------------

--
-- Structure de la table `periode`
--

DROP TABLE IF EXISTS `periode`;
CREATE TABLE IF NOT EXISTS `periode` (
  `id_periode` int(11) NOT NULL AUTO_INCREMENT,
  `num_periode` int(11) NOT NULL,
  `id_matchs` int(11) NOT NULL,
  PRIMARY KEY (`id_periode`),
  KEY `id_match` (`id_matchs`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `periode`
--

INSERT INTO `periode` (`id_periode`, `num_periode`, `id_matchs`) VALUES
(1, 1, 3),
(2, 2, 3),
(3, 3, 3),
(4, 4, 3);

-- --------------------------------------------------------

--
-- Structure de la table `saison`
--

DROP TABLE IF EXISTS `saison`;
CREATE TABLE IF NOT EXISTS `saison` (
  `id_saison` int(11) NOT NULL AUTO_INCREMENT,
  `saison` year(4) NOT NULL,
  PRIMARY KEY (`id_saison`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `saison`
--

INSERT INTO `saison` (`id_saison`, `saison`) VALUES
(1, 2024);

-- --------------------------------------------------------

--
-- Structure de la table `structure`
--

DROP TABLE IF EXISTS `structure`;
CREATE TABLE IF NOT EXISTS `structure` (
  `id_structure` int(11) NOT NULL AUTO_INCREMENT,
  `nom_structure` varchar(50) NOT NULL,
  `lieu_structure` varchar(100) NOT NULL,
  PRIMARY KEY (`id_structure`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `structure`
--

INSERT INTO `structure` (`id_structure`, `nom_structure`, `lieu_structure`) VALUES
(1, 'OCCITANIE (DEP)', 'Centre Nautique Paul Boyrie, Tarbes');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `but`
--
ALTER TABLE `but`
  ADD CONSTRAINT `liaison equipe but` FOREIGN KEY (`id_equipe`) REFERENCES `equipe` (`id_equipe`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `liaison joueur` FOREIGN KEY (`id_joueur`) REFERENCES `joueur` (`id_joueur`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `liaison matchs but` FOREIGN KEY (`id_matchs`) REFERENCES `matchs` (`id_matchs`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `liaison periode` FOREIGN KEY (`id_periode`) REFERENCES `periode` (`id_periode`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `championnat`
--
ALTER TABLE `championnat`
  ADD CONSTRAINT `liaison niveau` FOREIGN KEY (`id_niveau`) REFERENCES `niveau` (`id_niveau`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `liaison saison` FOREIGN KEY (`id_saison`) REFERENCES `saison` (`id_saison`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `joueur`
--
ALTER TABLE `joueur`
  ADD CONSTRAINT `liaison equipe` FOREIGN KEY (`id_equipe`) REFERENCES `equipe` (`id_equipe`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `matchs`
--
ALTER TABLE `matchs`
  ADD CONSTRAINT `liaison arbitre` FOREIGN KEY (`id_arbitre`) REFERENCES `arbitre` (`id_arbitre`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `liaison championnat` FOREIGN KEY (`id_championnat`) REFERENCES `championnat` (`id_championnat`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `liaison equipe domicile` FOREIGN KEY (`id_equipe_domicile`) REFERENCES `equipe` (`id_equipe`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `liaison equipe visiteur` FOREIGN KEY (`id_equipe_visiteur`) REFERENCES `equipe` (`id_equipe`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `liaison structure` FOREIGN KEY (`id_structure`) REFERENCES `structure` (`id_structure`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `periode`
--
ALTER TABLE `periode`
  ADD CONSTRAINT `liaison matchs periode` FOREIGN KEY (`id_matchs`) REFERENCES `matchs` (`id_matchs`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
