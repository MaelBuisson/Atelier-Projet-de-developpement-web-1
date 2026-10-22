-- Schéma PostgreSQL de la billetterie NRV
-- identifiants : UUID générés par la base (gen_random_uuid, natif depuis PostgreSQL 13)
-- nommage : snake_case (PostgreSQL convertit en minuscules les identifiants non quotés)

DROP TABLE IF EXISTS billet CASCADE;
DROP TABLE IF EXISTS soiree_dans_commande CASCADE;
DROP TABLE IF EXISTS commande CASCADE;
DROP TABLE IF EXISTS tarif CASCADE;
DROP TABLE IF EXISTS utilisateur CASCADE;
DROP TABLE IF EXISTS jouer_dans_spectacle CASCADE;
DROP TABLE IF EXISTS artiste CASCADE;
DROP TABLE IF EXISTS image CASCADE;
DROP TABLE IF EXISTS spectacle CASCADE;
DROP TABLE IF EXISTS soiree CASCADE;

CREATE TABLE soiree (
    id_soiree  UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    nom        VARCHAR(100) NOT NULL,
    thematique VARCHAR(100) NOT NULL,
    date       DATE         NOT NULL,
    horaire    TIME         NOT NULL,
    lieu       VARCHAR(150) NOT NULL,
    nb_place   INT          NOT NULL CHECK (nb_place > 0)
);

CREATE TABLE spectacle (
    id_spectacle  UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    titre         VARCHAR(150) NOT NULL,
    description   TEXT,
    horaire       TIME         NOT NULL,
    video         VARCHAR(255),
    style_musique VARCHAR(50)  NOT NULL,
    id_soiree     UUID         NOT NULL REFERENCES soiree (id_soiree)
);

CREATE TABLE image (
    id_image     UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    url          VARCHAR(255) NOT NULL,
    id_spectacle UUID         NOT NULL REFERENCES spectacle (id_spectacle)
);

CREATE TABLE artiste (
    id_artiste UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    nom        VARCHAR(100) NOT NULL,
    prenom     VARCHAR(100)
);

CREATE TABLE jouer_dans_spectacle (
    id_spectacle UUID NOT NULL REFERENCES spectacle (id_spectacle),
    id_artiste   UUID NOT NULL REFERENCES artiste (id_artiste),
    PRIMARY KEY (id_spectacle, id_artiste)
);

CREATE TABLE utilisateur (
    id_utilisateur UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    email          VARCHAR(150) NOT NULL UNIQUE,
    nom            VARCHAR(100) NOT NULL,
    prenom         VARCHAR(100) NOT NULL,
    password       VARCHAR(255) NOT NULL,
    role           INT          NOT NULL DEFAULT 1
);

CREATE TABLE tarif (
    id_soiree      UUID          NOT NULL REFERENCES soiree (id_soiree),
    nom            VARCHAR(30)   NOT NULL CHECK (nom IN ('normal', 'reduit')),
    prix           NUMERIC(6, 2) NOT NULL CHECK (prix >= 0),
    PRIMARY KEY (id_soiree, nom)
);

CREATE TABLE commande (
    id_commande    UUID          PRIMARY KEY DEFAULT gen_random_uuid(),
    date_creation  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    montant_total  NUMERIC(8, 2) NOT NULL DEFAULT 0,
    statut         VARCHAR(10)   NOT NULL,
    id_utilisateur UUID          NOT NULL REFERENCES utilisateur (id_utilisateur)
);

-- ligne de commande (du panier) : identifiant propre pour pouvoir la supprimer,
-- et une seule ligne par soirée et par tarif dans une commande (UNIQUE)
CREATE TABLE soiree_dans_commande (
    id_lignes   UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    id_commande UUID        NOT NULL REFERENCES commande (id_commande),
    id_soiree   UUID        NOT NULL REFERENCES soiree (id_soiree),
    nom_tarif   VARCHAR(30) NOT NULL,
    quantite    INT         NOT NULL CHECK (quantite > 0),
    UNIQUE (id_commande, id_soiree, nom_tarif),
    FOREIGN KEY (id_soiree, nom_tarif) REFERENCES tarif (id_soiree, nom)
);

-- token : chaîne aléatoire générée par l'application, ex. bin2hex(random_bytes(32))
CREATE TABLE billet (
    token       CHAR(64)    PRIMARY KEY,
    id_soiree   UUID        NOT NULL REFERENCES soiree (id_soiree),
    id_commande UUID        NOT NULL REFERENCES commande (id_commande),
    nom_tarif   VARCHAR(30) NOT NULL,
    FOREIGN KEY (id_soiree, nom_tarif) REFERENCES tarif (id_soiree, nom)
);
