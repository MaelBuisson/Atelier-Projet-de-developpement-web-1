DROP TABLE IF EXISTS Billet;
DROP TABLE IF EXISTS SoireeDansCommande;
DROP TABLE IF EXISTS Commande;
DROP TABLE IF EXISTS Tarif;
DROP TABLE IF EXISTS Utilisateur;
DROP TABLE IF EXISTS JouerDansSpectacle;
DROP TABLE IF EXISTS Artiste;
DROP TABLE IF EXISTS Image;
DROP TABLE IF EXISTS Spectacle;
DROP TABLE IF EXISTS Soiree;

CREATE TABLE Soiree (
    idSoiree   INT AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(100) NOT NULL,
    thematique VARCHAR(100) NOT NULL,
    `date`     DATE         NOT NULL,
    horaire    TIME         NOT NULL,
    lieu       VARCHAR(150) NOT NULL,
    nbPlace    INT          NOT NULL
);

CREATE TABLE Spectacle (
    idSpectacle  INT AUTO_INCREMENT PRIMARY KEY,
    titre        VARCHAR(150) NOT NULL,
    description  TEXT,
    horaire      TIME         NOT NULL,
    video        VARCHAR(255),
    styleMusique VARCHAR(50)  NOT NULL,
    idSoiree     INT          NOT NULL,
    FOREIGN KEY (idSoiree) REFERENCES Soiree(idSoiree)
);

CREATE TABLE Image (
    idImage     INT AUTO_INCREMENT PRIMARY KEY,
    url         VARCHAR(255) NOT NULL,
    idSpectacle INT          NOT NULL,
    FOREIGN KEY (idSpectacle) REFERENCES Spectacle(idSpectacle)
);

CREATE TABLE Artiste (
    idArtiste INT AUTO_INCREMENT PRIMARY KEY,
    nom       VARCHAR(100) NOT NULL,
    prenom    VARCHAR(100)
);

CREATE TABLE JouerDansSpectacle (
    idSpectacle INT NOT NULL,
    idArtiste   INT NOT NULL,
    PRIMARY KEY (idSpectacle, idArtiste),
    FOREIGN KEY (idSpectacle) REFERENCES Spectacle(idSpectacle),
    FOREIGN KEY (idArtiste)   REFERENCES Artiste(idArtiste)
);

CREATE TABLE Utilisateur (
    idUtilisateur INT AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(150) NOT NULL UNIQUE,
    nom           VARCHAR(100) NOT NULL,
    prenom        VARCHAR(100) NOT NULL,
    password      VARCHAR(255) NOT NULL,                
    role          INT,
);

CREATE TABLE Tarif (
    nom  VARCHAR(30)  PRIMARY KEY,                       
    prix DECIMAL(6,2) NOT NULL
);

CREATE TABLE Commande (
    idCommande    INT AUTO_INCREMENT PRIMARY KEY,
    dateCreation  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    montantTotal  DECIMAL(8,2)  NOT NULL DEFAULT 0,
    statut        VARCHAR(10)   NOT NULL,
    idUtilisateur INT           NOT NULL,
    FOREIGN KEY (idUtilisateur) REFERENCES Utilisateur(idUtilisateur)
);

CREATE TABLE SoireeDansCommande (
    idCommande INT         NOT NULL,
    idSoiree   INT         NOT NULL,
    nomTarif   VARCHAR(30) NOT NULL,
    quantite   INT         NOT NULL,
    PRIMARY KEY (idCommande, idSoiree, nomTarif),
    FOREIGN KEY (idCommande) REFERENCES Commande(idCommande),
    FOREIGN KEY (idSoiree)   REFERENCES Soiree(idSoiree),
    FOREIGN KEY (nomTarif)   REFERENCES Tarif(nom)
);

CREATE TABLE Billet (
    token      CHAR(64)    PRIMARY KEY,                  
    idSoiree   INT         NOT NULL,
    idCommande INT         NOT NULL,
    nomTarif   VARCHAR(30) NOT NULL,
    FOREIGN KEY (idSoiree)   REFERENCES Soiree(idSoiree),
    FOREIGN KEY (idCommande) REFERENCES Commande(idCommande),
    FOREIGN KEY (nomTarif)   REFERENCES Tarif(nom)
);


