-- Table des utilisateurs pour le test de login
-- À exécuter dans phpMyAdmin ou via MySQL CLI

CREATE TABLE IF NOT EXISTS `t_utilisateur` (
    `pk_utilisateur`   INT          NOT NULL AUTO_INCREMENT,
    `nom_utilisateur`  VARCHAR(50)  NOT NULL,
    `email`            VARCHAR(255) NOT NULL,
    `mot_de_passe`     VARCHAR(255) NOT NULL,  -- stocke le hash, jamais le mot de passe en clair
    `date_inscription` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`pk_utilisateur`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `nom_utilisateur` (`nom_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
