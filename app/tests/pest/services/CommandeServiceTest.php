<?php

/*
 * Tests du use case « voir son panier » (CommandeService::getPanier).
 * Les repositories sont remplacés par des fakes : le service ne dépend que des ports SPI.
 */

use nrv\application\dto\PanierDTO;
use nrv\application\usecases\CommandeService;
use nrv\domain\entities\Commande;
use nrv\domain\entities\SoireeDansCommande;
use nrv\domain\entities\Tarif;
use nrv\domain\entities\Utilisateur;
use nrv\domain\exceptions\UserNotFound;
use tests\pest\fakes\FakeCommandeRepository;
use tests\pest\fakes\FakeUtilisateurRepository;

beforeEach(function () {
    $this->jean = new Utilisateur('utilisateur-jean', 'jean@mail.fr', 'Dupont', 'Jean', 'hash', Utilisateur::ROLE_CLIENT);
    $this->marie = new Utilisateur('utilisateur-marie', 'marie@mail.fr', 'Martin', 'Marie', 'hash', Utilisateur::ROLE_CLIENT);

    $this->utilisateurs = new FakeUtilisateurRepository();
    $this->utilisateurs->utilisateurs = [$this->jean, $this->marie];

    // Jean a un panier de 3 billets ; Marie n'a pas encore de panier
    $panierJean = new Commande('commande-jean', new DateTimeImmutable('2027-06-01 10:00'), $this->jean);
    $rock = creerSoiree('soiree-rock', 'Rock Night');
    $panierJean->ajouterSoiree(new SoireeDansCommande('L1', $rock, new Tarif('normal', 35.0), 2));
    $panierJean->ajouterSoiree(new SoireeDansCommande('L2', $rock, new Tarif('reduit', 25.0), 1));

    $this->service = new CommandeService(new FakeCommandeRepository([$panierJean]), $this->utilisateurs);
});

it("retourne le panier existant de l'utilisateur", function () {
    $panier = $this->service->getPanier('utilisateur-jean');

    expect($panier)->toBeInstanceOf(PanierDTO::class)
        ->and(array_column($panier->lignes, 'idLigne'))->toBe(['L1', 'L2'])
        ->and($panier->nbBillets)->toBe(3)
        ->and($panier->montantTotal)->toBe(95.0);
});

it("retourne un panier vide à un utilisateur qui n'en a pas encore", function () {
    $panier = $this->service->getPanier('utilisateur-marie');

    expect($panier->lignes)->toBe([])
        ->and($panier->nbBillets)->toBe(0)
        ->and($panier->montantTotal)->toBe(0.0);
});

it("ne renvoie jamais le panier d'un autre utilisateur", function () {
    // Marie n'a pas de panier : elle ne doit pas recevoir celui de Jean
    expect($this->service->getPanier('utilisateur-marie')->lignes)->toBe([]);
});

it("lève UserNotFound si l'utilisateur sans panier n'existe plus", function () {
    $this->service->getPanier('utilisateur-supprime');
})->throws(UserNotFound::class);
