<?php

/*
 * Tests de PanierDTO : forme du JSON du panier renvoyé au client.
 */

use nrv\application\dto\PanierDTO;
use nrv\domain\entities\Commande;
use nrv\domain\entities\SoireeDansCommande;
use nrv\domain\entities\Tarif;
use nrv\domain\entities\Utilisateur;

/**
 * Panier de test : 3 billets normal et 1 réduit pour la soirée rock.
 */
function panierDeTest(): Commande
{
    $commande = new Commande(
        'commande-1',
        new DateTimeImmutable('2027-06-01 10:00'),
        new Utilisateur('utilisateur-1', 'jean@mail.fr', 'Dupont', 'Jean', 'hash', Utilisateur::ROLE_CLIENT)
    );
    $rock = creerSoiree('soiree-rock', 'Rock Night');
    $commande->ajouterSoiree(new SoireeDansCommande('L1', $rock, new Tarif('normal', 35.0), 3));
    $commande->ajouterSoiree(new SoireeDansCommande('L2', $rock, new Tarif('reduit', 25.0), 1));

    return $commande;
}

it('produit les lignes, le nombre de billets et le total', function () {
    $json = json_decode(json_encode(PanierDTO::fromEntity(panierDeTest())), true);

    expect($json)->toBe([
        'lignes' => [
            [
                'idLigne' => 'L1',
                'idSoiree' => 'soiree-rock',
                'nomSoiree' => 'Rock Night',
                'tarif' => 'normal',
                'quantite' => 3,
                'sousTotal' => 105,
            ],
            [
                'idLigne' => 'L2',
                'idSoiree' => 'soiree-rock',
                'nomSoiree' => 'Rock Night',
                'tarif' => 'reduit',
                'quantite' => 1,
                'sousTotal' => 25,
            ],
        ],
        'nbBillets' => 4,
        'montantTotal' => 130,
    ]);
});

it('renvoie les lignes sous forme de tableau JSON et non d\'objet', function () {
    $json = json_encode(PanierDTO::fromEntity(panierDeTest()));

    expect($json)->toStartWith('{"lignes":[');
});

it("donne l'identifiant de chaque ligne, nécessaire pour la supprimer", function () {
    $dto = PanierDTO::fromEntity(panierDeTest());

    expect(array_column($dto->lignes, 'idLigne'))->toBe(['L1', 'L2']);
});

it('représente un panier vide', function () {
    $vide = new Commande(
        'commande-2',
        new DateTimeImmutable(),
        new Utilisateur('utilisateur-1', 'jean@mail.fr', 'Dupont', 'Jean', 'hash', Utilisateur::ROLE_CLIENT)
    );

    $dto = PanierDTO::fromEntity($vide);

    expect($dto->lignes)->toBe([])
        ->and($dto->nbBillets)->toBe(0)
        ->and($dto->montantTotal)->toBe(0.0);
});

it('arrondit les montants au centime', function () {
    $commande = new Commande(
        'commande-3',
        new DateTimeImmutable(),
        new Utilisateur('utilisateur-1', 'jean@mail.fr', 'Dupont', 'Jean', 'hash', Utilisateur::ROLE_CLIENT)
    );
    $commande->ajouterSoiree(new SoireeDansCommande('L1', creerSoiree('s1'), new Tarif('normal', 12.99), 3));

    $dto = PanierDTO::fromEntity($commande);

    expect($dto->lignes[0]['sousTotal'])->toBe(38.97)
        ->and($dto->montantTotal)->toBe(38.97);
});

it('ne contient aucune donnée sur l\'utilisateur', function () {
    $json = json_encode(PanierDTO::fromEntity(panierDeTest()));

    expect($json)->not->toContain('jean@mail.fr')
        ->and($json)->not->toContain('hash');
});
