<?php

/*
 * Tests de l'entité Commande (le panier) et de ses lignes SoireeDansCommande :
 * fusion des lignes identiques, total, suppression, et changements de statut.
 */

use nrv\domain\entities\Commande;
use nrv\domain\entities\CommandeStatus;
use nrv\domain\entities\SoireeDansCommande;
use nrv\domain\entities\Tarif;
use nrv\domain\entities\Utilisateur;
use nrv\domain\exceptions\CommandeNonModifiable;
use nrv\domain\exceptions\CommandeNonPayable;
use nrv\domain\exceptions\CommandeNonValidable;
use nrv\domain\exceptions\PanierVide;
use nrv\domain\exceptions\QuantiteInvalide;
use nrv\domain\exceptions\SoireeDansCommandeNotFound;

beforeEach(function () {
    $this->commande = new Commande(
        'commande-1',
        new DateTimeImmutable('2027-06-01 10:00'),
        new Utilisateur('utilisateur-1', 'jean@mail.fr', 'Dupont', 'Jean', 'hash', Utilisateur::ROLE_CLIENT)
    );

    $this->rock = creerSoiree('soiree-rock', 'Rock Night');
    $this->jazz = creerSoiree('soiree-jazz', 'Jazz Club');
    $this->normal = new Tarif('normal', 35.0);
    $this->reduit = new Tarif('reduit', 25.0);
});

/**
 * Ligne de panier de test ; comme le fera le service, chaque ajout reçoit un nouvel identifiant.
 */
function lignePanier(string $id, $soiree, Tarif $tarif, int $quantite): SoireeDansCommande
{
    return new SoireeDansCommande($id, $soiree, $tarif, $quantite);
}

/**
 * Résumé lisible des lignes : ['soirée/tarif' => quantité].
 *
 * @return array<string, int>
 */
function resumeLignes(Commande $commande): array
{
    $resume = [];
    foreach ($commande->getSoireesDansCommande() as $ligne) {
        $resume[$ligne->getSoiree()->getId() . '/' . $ligne->getTarif()->getNom()] = $ligne->getQuantite();
    }
    return $resume;
}

// ── nouvelle commande ─

it('commence comme un panier vide', function () {
    expect($this->commande->getStatus())->toBe(CommandeStatus::PANIER)
        ->and($this->commande->getSoireesDansCommande())->toBe([])
        ->and($this->commande->getMontantTotal())->toBe(0.0);
});

// ── ajout de lignes ─

it('calcule le montant d\'une ligne : prix × quantité', function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 2));

    expect($this->commande->getMontantTotal())->toBe(70.0);
});

it('fusionne deux ajouts de la même soirée au même tarif', function () {
    // 2 puis 2 billets : la quantité ajoutée doit être celle de la nouvelle ligne, pas toujours 1
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 2));
    $this->commande->ajouterSoiree(lignePanier('L2', $this->rock, $this->normal, 2));

    $lignes = $this->commande->getSoireesDansCommande();

    expect($lignes)->toHaveCount(1)
        ->and($lignes[0]->getQuantite())->toBe(4)
        // la ligne déjà présente est conservée, avec son identifiant
        ->and($lignes[0]->getIdLigne())->toBe('L1')
        ->and($this->commande->getMontantTotal())->toBe(140.0);
});

it('garde deux lignes pour deux tarifs de la même soirée', function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 3));
    $this->commande->ajouterSoiree(lignePanier('L2', $this->rock, $this->reduit, 1));

    expect(resumeLignes($this->commande))->toBe([
        'soiree-rock/normal' => 3,
        'soiree-rock/reduit' => 1,
    ]);
});

it('garde deux lignes pour deux soirées au même tarif', function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 1));
    $this->commande->ajouterSoiree(lignePanier('L2', $this->jazz, $this->normal, 1));

    expect(resumeLignes($this->commande))->toBe([
        'soiree-rock/normal' => 1,
        'soiree-jazz/normal' => 1,
    ]);
});

it('additionne toutes les lignes dans le total', function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 3)); // 105
    $this->commande->ajouterSoiree(lignePanier('L2', $this->rock, $this->reduit, 1)); //  25
    $this->commande->ajouterSoiree(lignePanier('L3', $this->jazz, new Tarif('normal', 20.0), 2)); //  40

    expect($this->commande->getMontantTotal())->toBe(170.0);
});

it('retourne les lignes sous forme de liste (0, 1, 2…) pour le JSON', function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 1));
    $this->commande->ajouterSoiree(lignePanier('L2', $this->rock, $this->reduit, 1));

    expect(array_is_list($this->commande->getSoireesDansCommande()))->toBeTrue();
});

it('refuse une ligne de quantité nulle ou négative', function (int $quantite) {
    lignePanier('L1', $this->rock, $this->normal, $quantite);
})->with([0, -1])->throws(QuantiteInvalide::class, 'La quantité doit être supérieure ou égale à 1');

// ── suppression ─

it('retire un billet de la ligne à chaque suppression', function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 3));

    $this->commande->supprimerSoiree('L1');

    expect(resumeLignes($this->commande))->toBe(['soiree-rock/normal' => 2])
        ->and($this->commande->getMontantTotal())->toBe(70.0);
});

it('supprime la ligne quand son dernier billet est retiré', function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 1));
    $this->commande->ajouterSoiree(lignePanier('L2', $this->rock, $this->reduit, 1));

    $this->commande->supprimerSoiree('L1');

    expect(resumeLignes($this->commande))->toBe(['soiree-rock/reduit' => 1])
        ->and($this->commande->getMontantTotal())->toBe(25.0);
});

it('lève SoireeDansCommandeNotFound pour une ligne absente du panier', function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 1));

    $this->commande->supprimerSoiree('ligne-inconnue');
})->throws(SoireeDansCommandeNotFound::class, 'Ligne de commande introuvable : ligne-inconnue');

it("n'émet aucun avertissement PHP en ajoutant ou supprimant des lignes", function () {
    // un « Warning: Undefined array key » s'écrirait dans la réponse JSON de l'API
    set_error_handler(fn (int $niveau, string $message) => throw new ErrorException($message, 0, $niveau));
    try {
        $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 1));
        $this->commande->ajouterSoiree(lignePanier('L2', $this->rock, $this->normal, 1));
        $this->commande->supprimerSoiree('L1');
        expect(fn () => $this->commande->supprimerSoiree('ligne-inconnue'))
            ->toThrow(SoireeDansCommandeNotFound::class);
    } finally {
        restore_error_handler();
    }
});

// ── statuts : panier → validée → payée ─

it('passe de panier à validée puis à payée', function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 1));

    $this->commande->valider();
    expect($this->commande->getStatus())->toBe(CommandeStatus::VALIDEE);

    $this->commande->payer();
    expect($this->commande->getStatus())->toBe(CommandeStatus::PAYEE);
});

it("refuse un ajout une fois la commande validée", function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 2));
    $this->commande->valider();

    $this->commande->ajouterSoiree(lignePanier('L2', $this->rock, $this->reduit, 1));
})->throws(CommandeNonModifiable::class, 'statut actuel : VALIDEE');

it("refuse une suppression une fois la commande validée", function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 2));
    $this->commande->valider();

    $this->commande->supprimerSoiree('L1');
})->throws(CommandeNonModifiable::class);

it('refuse de valider deux fois la même commande', function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 2));
    $this->commande->valider();

    $this->commande->valider();
})->throws(CommandeNonValidable::class);

it("refuse le paiement d'une commande qui n'est pas validée", function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 1));

    $this->commande->payer();
})->throws(CommandeNonPayable::class, 'statut actuel : PANIER');

it('refuse de payer deux fois la même commande', function () {
    $this->commande->ajouterSoiree(lignePanier('L1', $this->rock, $this->normal, 1));
    $this->commande->valider();
    $this->commande->payer();

    $this->commande->payer();
})->throws(CommandeNonPayable::class, 'statut actuel : PAYEE');

it('donne le code HTTP adapté à chaque exception du panier', function () {
    $statut = CommandeStatus::VALIDEE;

    expect((new CommandeNonModifiable($statut))->getCode())->toBe(409)
        ->and((new CommandeNonValidable($statut))->getCode())->toBe(409)
        ->and((new CommandeNonPayable($statut))->getCode())->toBe(409)
        ->and((new PanierVide())->getCode())->toBe(409)
        ->and((new QuantiteInvalide(0))->getCode())->toBe(400)
        ->and((new SoireeDansCommandeNotFound('L1'))->getCode())->toBe(404);
});

it('refuse la validation d\'un panier vide', function () {
    $this->commande->valider();
})->throws(PanierVide::class);

it('l\'état reste panier après le refus', function () {
    expect(fn () => $this->commande->valider())->toThrow(PanierVide::class);

    expect($this->commande->getStatus())->toBe(CommandeStatus::PANIER);
});

// ── reconstruction depuis la base (lignes passées au constructeur) ─

it('reconstruit une commande validée avec ses lignes sans passer par ajouterSoiree', function () {
    // ajouterSoiree() refuserait ces lignes sur une commande VALIDEE : le constructeur les accepte
    $commande = new Commande(
        'commande-2',
        new DateTimeImmutable('2027-06-01 10:00'),
        new Utilisateur('utilisateur-1', 'jean@mail.fr', 'Dupont', 'Jean', 'hash', Utilisateur::ROLE_CLIENT),
        CommandeStatus::VALIDEE,
        [lignePanier('L1', $this->rock, $this->normal, 2), lignePanier('L2', $this->jazz, $this->reduit, 1)]
    );

    expect($commande->getStatus())->toBe(CommandeStatus::VALIDEE)
        ->and(array_map(fn ($l) => $l->getIdLigne(), $commande->getSoireesDansCommande()))->toBe(['L1', 'L2'])
        ->and($commande->getMontantTotal())->toBe(95.0);
});

it('garde une commande reconstruite non modifiable', function () {
    $commande = new Commande(
        'commande-2',
        new DateTimeImmutable('2027-06-01 10:00'),
        new Utilisateur('utilisateur-1', 'jean@mail.fr', 'Dupont', 'Jean', 'hash', Utilisateur::ROLE_CLIENT),
        CommandeStatus::VALIDEE,
        [lignePanier('L1', $this->rock, $this->normal, 2)]
    );

    $commande->supprimerSoiree('L1');
})->throws(CommandeNonModifiable::class);
