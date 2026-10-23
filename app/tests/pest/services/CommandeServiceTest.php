<?php

/*
 * Tests des use cases du panier et des commandes (CommandeService) : voir son panier,
 * ajouter et retirer des billets, valider le panier, consulter et payer une commande.
 * Les repositories sont remplacés par des fakes : le service ne dépend que des ports SPI.
 */

use nrv\application\dto\CommandeDTO;
use nrv\application\dto\PanierDTO;
use nrv\application\usecases\CommandeService;
use nrv\domain\entities\Commande;
use nrv\domain\entities\CommandeStatus;
use nrv\domain\entities\SoireeDansCommande;
use nrv\domain\entities\Tarif;
use nrv\domain\entities\Utilisateur;
use nrv\domain\exceptions\CommandeNonPayable;
use nrv\domain\exceptions\CommandeNotFound;
use nrv\domain\exceptions\PlacesInsuffisantes;
use nrv\domain\exceptions\PanierVide;
use nrv\domain\exceptions\QuantiteInvalide;
use nrv\domain\exceptions\SoireeDansCommandeNotFound;
use nrv\domain\exceptions\SoireeNotFound;
use nrv\domain\exceptions\TarifNotFound;
use nrv\domain\exceptions\UserNotFound;
use tests\pest\fakes\FakeCommandeRepository;
use tests\pest\fakes\FakeSoireeRepository;
use tests\pest\fakes\FakeTarifRepository;
use tests\pest\fakes\FakeUtilisateurRepository;

beforeEach(function () {
    $this->jean = new Utilisateur('utilisateur-jean', 'jean@mail.fr', 'Dupont', 'Jean', 'hash', Utilisateur::ROLE_CLIENT);
    $this->marie = new Utilisateur('utilisateur-marie', 'marie@mail.fr', 'Martin', 'Marie', 'hash', Utilisateur::ROLE_CLIENT);

    $this->utilisateurs = new FakeUtilisateurRepository();
    $this->utilisateurs->utilisateurs = [$this->jean, $this->marie];

    $rock = creerSoiree('soiree-rock', 'Rock Night');
    $metal = creerSoiree('soiree-metal', 'Metal Night');

    // Jean a un panier de 3 billets ; Marie n'a pas encore de panier
    $panierJean = new Commande('commande-jean', new DateTimeImmutable('2027-06-01 10:00'), $this->jean);
    $panierJean->ajouterSoiree(new SoireeDansCommande('L1', $rock, new Tarif('normal', 35.0), 2));
    $panierJean->ajouterSoiree(new SoireeDansCommande('L2', $rock, new Tarif('reduit', 25.0), 1));

    // les fakes sont gardés dans $this : les tests lisent ensuite ce qui a été enregistré (saveCalls, getPanier)
    $this->commandes = new FakeCommandeRepository([$panierJean]);
    $this->soirees = new FakeSoireeRepository([$metal, $rock]);
    // prix « en base » : le réduit rock vaut 17,5 € ici, alors que la ligne L2 de Jean a été créée à 25 €.
    // Cette différence permet de vérifier que le service prend le prix du repository.
    $this->tarifs = new FakeTarifRepository([
        'soiree-rock' => [new Tarif('normal', 35.0), new Tarif('reduit', 17.5)],
        'soiree-metal' => [new Tarif('normal', 50.0), new Tarif('reduit', 25.0)],
    ]);

    $this->service = new CommandeService($this->commandes, $this->utilisateurs, $this->soirees, $this->tarifs);
});

// ── getPanier ─

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

it("n'enregistre rien quand on consulte le panier", function () {
    // un panier vide renvoyé par getPanier n'est créé en base qu'au premier ajout
    $this->service->getPanier('utilisateur-marie');

    expect($this->commandes->saveCalls)->toBe(0)
        ->and($this->commandes->getPanier('utilisateur-marie'))->toBeNull();
});

// ── ajouterAuPanier ─

it('crée et enregistre le panier au premier ajout', function () {
    $panier = $this->service->ajouterAuPanier('utilisateur-marie', 'soiree-metal', 'normal', 2);

    expect($panier->lignes)->toHaveCount(1)
        ->and($panier->lignes[0]['idSoiree'])->toBe('soiree-metal')
        ->and($panier->lignes[0]['tarif'])->toBe('normal')
        ->and($panier->nbBillets)->toBe(2)
        ->and($panier->montantTotal)->toBe(100.0)
        ->and($this->commandes->saveCalls)->toBe(1)
        ->and($this->commandes->getPanier('utilisateur-marie'))->not->toBeNull();
});

it("ajoute la quantité à la ligne existante pour la même soirée et le même tarif", function () {
    // Jean a déjà 2 billets normal pour la soirée rock (ligne L1)
    $panier = $this->service->ajouterAuPanier('utilisateur-jean', 'soiree-rock', 'normal', 1);

    expect(array_column($panier->lignes, 'idLigne'))->toBe(['L1', 'L2'])
        ->and($panier->lignes[0]['quantite'])->toBe(3)
        ->and($panier->nbBillets)->toBe(4)
        ->and($this->commandes->saveCalls)->toBe(1);
});

it('crée une nouvelle ligne pour une autre soirée', function () {
    $panier = $this->service->ajouterAuPanier('utilisateur-jean', 'soiree-metal', 'reduit', 1);

    expect($panier->lignes)->toHaveCount(3)
        ->and($panier->nbBillets)->toBe(4)
        ->and($panier->montantTotal)->toBe(120.0);
});

it('utilise le prix du tarif enregistré, jamais un autre prix', function () {
    // réduit rock : 17,5 € dans le repository des tarifs
    $panier = $this->service->ajouterAuPanier('utilisateur-marie', 'soiree-rock', 'reduit', 2);

    expect($panier->lignes[0]['sousTotal'])->toBe(35.0)
        ->and($panier->montantTotal)->toBe(35.0);
});

it("lève SoireeNotFound pour une soirée inexistante, sans rien enregistrer", function () {
    expect(fn () => $this->service->ajouterAuPanier('utilisateur-jean', 'soiree-inconnue', 'normal', 1))
        ->toThrow(SoireeNotFound::class);

    expect($this->commandes->saveCalls)->toBe(0);
});

it("lève TarifNotFound pour un tarif inexistant, sans rien enregistrer", function () {
    expect(fn () => $this->service->ajouterAuPanier('utilisateur-jean', 'soiree-rock', 'vip', 1))
        ->toThrow(TarifNotFound::class);

    expect($this->commandes->saveCalls)->toBe(0);
});

it("lève QuantiteInvalide pour une quantité nulle ou négative, sans rien enregistrer", function () {
    expect(fn () => $this->service->ajouterAuPanier('utilisateur-jean', 'soiree-rock', 'normal', 0))
        ->toThrow(QuantiteInvalide::class);
    expect(fn () => $this->service->ajouterAuPanier('utilisateur-jean', 'soiree-rock', 'normal', -3))
        ->toThrow(QuantiteInvalide::class);

    expect($this->commandes->saveCalls)->toBe(0);
});

it("laisse remonter l'erreur de la base de données", function () {
    // panne simulée : le service ne doit pas avaler l'exception ni renvoyer un panier comme si tout allait bien
    $this->commandes->saveThrows = new RuntimeException('base indisponible');

    $this->service->ajouterAuPanier('utilisateur-jean', 'soiree-rock', 'normal', 1);
})->throws(RuntimeException::class, 'base indisponible');

// ── supprimerDuPanier ─

it('retire la ligne qui ne contient plus de billet et enregistre', function () {
    // L2 ne contient qu'un billet : la ligne disparaît
    $panier = $this->service->supprimerDuPanier('utilisateur-jean', 'L2');

    expect(array_column($panier->lignes, 'idLigne'))->toBe(['L1'])
        ->and($panier->nbBillets)->toBe(2)
        ->and($panier->montantTotal)->toBe(70.0)
        ->and($this->commandes->saveCalls)->toBe(1);
});

it("retire un seul billet d'une ligne qui en contient plusieurs", function () {
    // L1 contient 2 billets à 35 € : il en reste un
    $panier = $this->service->supprimerDuPanier('utilisateur-jean', 'L1');

    expect(array_column($panier->lignes, 'idLigne'))->toBe(['L1', 'L2'])
        ->and($panier->lignes[0]['quantite'])->toBe(1)
        ->and($panier->montantTotal)->toBe(60.0);
});

it('lève SoireeDansCommandeNotFound pour une ligne absente du panier', function () {
    expect(fn () => $this->service->supprimerDuPanier('utilisateur-jean', 'ligne-inconnue'))
        ->toThrow(SoireeDansCommandeNotFound::class);

    expect($this->commandes->saveCalls)->toBe(0);
});

it("ne permet pas de retirer la ligne du panier d'un autre utilisateur", function () {
    // Marie connaît l'identifiant L1, mais cette ligne appartient au panier de Jean
    expect(fn () => $this->service->supprimerDuPanier('utilisateur-marie', 'L1'))
        ->toThrow(SoireeDansCommandeNotFound::class);

    $panierJean = $this->commandes->getPanier('utilisateur-jean');

    expect($this->commandes->saveCalls)->toBe(0)
        ->and($panierJean->getSoireesDansCommande()[0]->getQuantite())->toBe(2);
});

// ── valider ─

it('valide le panier : la commande devient VALIDEE et est enregistrée', function () {
    $commande = $this->service->valider('utilisateur-jean');

    expect($commande)->toBeInstanceOf(CommandeDTO::class)
        ->and($commande->id)->toBe('commande-jean')
        ->and($commande->statut)->toBe('VALIDEE')
        ->and(array_column($commande->lignes, 'idLigne'))->toBe(['L1', 'L2'])
        ->and($commande->montantTotal)->toBe(95.0)
        ->and($this->commandes->saveCalls)->toBe(1)
        ->and($this->commandes->findById('commande-jean')->getStatus())->toBe(CommandeStatus::VALIDEE);
});

it("après la validation, l'utilisateur a un nouveau panier vide", function () {
    $this->service->valider('utilisateur-jean');

    expect($this->service->getPanier('utilisateur-jean')->lignes)->toBe([]);
});

it("un ajout après la validation crée un nouveau panier sans toucher la commande validée", function () {
    $this->service->valider('utilisateur-jean');

    $panier = $this->service->ajouterAuPanier('utilisateur-jean', 'soiree-metal', 'normal', 1);
    $validee = $this->commandes->findById('commande-jean');

    expect($panier->lignes)->toHaveCount(1)
        ->and($panier->montantTotal)->toBe(50.0)
        ->and($validee->getSoireesDansCommande())->toHaveCount(2)
        ->and($validee->getMontantTotal())->toBe(95.0);
});

it("lève PanierVide si l'utilisateur n'a pas de panier, sans rien enregistrer", function () {
    expect(fn () => $this->service->valider('utilisateur-marie'))->toThrow(PanierVide::class);

    expect($this->commandes->saveCalls)->toBe(0);
});

it('lève PanierVide si le panier ne contient aucune ligne, sans rien enregistrer', function () {
    // Marie a un panier enregistré mais vide (ses billets ont été retirés)
    $this->commandes->save(new Commande('commande-marie', new DateTimeImmutable(), $this->marie));
    $this->commandes->saveCalls = 0;

    expect(fn () => $this->service->valider('utilisateur-marie'))->toThrow(PanierVide::class);

    expect($this->commandes->saveCalls)->toBe(0)
        ->and($this->commandes->findById('commande-marie')->getStatus())->toBe(CommandeStatus::PANIER);
});

// ── getCommande ─

it("retourne une commande de l'utilisateur, quel que soit son statut", function () {
    $this->service->valider('utilisateur-jean');

    $commande = $this->service->getCommande('utilisateur-jean', 'commande-jean');

    expect($commande->statut)->toBe('VALIDEE')
        ->and($commande->nbBillets)->toBe(3);
});

it('lève CommandeNotFound pour une commande inexistante', function () {
    $this->service->getCommande('utilisateur-jean', 'commande-inconnue');
})->throws(CommandeNotFound::class);

it("lève CommandeNotFound pour la commande d'un autre utilisateur", function () {
    // Marie connaît l'identifiant de la commande de Jean : même réponse que si elle n'existait pas
    $this->service->getCommande('utilisateur-marie', 'commande-jean');
})->throws(CommandeNotFound::class);

// ── payer ─

it('paie une commande validée : statut PAYEE et un billet par place', function () {
    $this->service->valider('utilisateur-jean');

    $commande = $this->service->payer('utilisateur-jean', 'commande-jean');

    expect($commande->statut)->toBe('PAYEE')
        ->and($this->commandes->billets)->toHaveCount(3)
        ->and($this->commandes->findById('commande-jean')->getStatus())->toBe(CommandeStatus::PAYEE);
});

it("lève CommandeNonPayable pour une commande qui n'est pas validée", function () {
    // la commande de Jean est encore un panier
    expect(fn () => $this->service->payer('utilisateur-jean', 'commande-jean'))
        ->toThrow(CommandeNonPayable::class);

    expect($this->commandes->billets)->toBe([]);
});

it('lève CommandeNonPayable pour une commande déjà payée, sans créer de nouveaux billets', function () {
    $this->service->valider('utilisateur-jean');
    $this->service->payer('utilisateur-jean', 'commande-jean');

    expect(fn () => $this->service->payer('utilisateur-jean', 'commande-jean'))
        ->toThrow(CommandeNonPayable::class);

    expect($this->commandes->billets)->toHaveCount(3);
});

it("lève CommandeNotFound pour payer la commande d'un autre utilisateur", function () {
    $this->service->valider('utilisateur-jean');

    expect(fn () => $this->service->payer('utilisateur-marie', 'commande-jean'))
        ->toThrow(CommandeNotFound::class);

    expect($this->commandes->billets)->toBe([]);
});

it("lève PlacesInsuffisantes s'il ne reste pas assez de places, sans enregistrer de billet", function () {
    // Jean demande 3 places pour la soirée rock (2 normal + 1 réduit) : il n'en reste que 2
    $this->service->valider('utilisateur-jean');
    $this->commandes->placesRestantes = ['soiree-rock' => 2];

    expect(fn () => $this->service->payer('utilisateur-jean', 'commande-jean'))
        ->toThrow(PlacesInsuffisantes::class);

    expect($this->commandes->billets)->toBe([])
        ->and($this->commandes->placesRestantes)->toBe(['soiree-rock' => 2]);
});

it('accepte un paiement qui prend exactement les dernières places', function () {
    $this->service->valider('utilisateur-jean');
    $this->commandes->placesRestantes = ['soiree-rock' => 3];

    $this->service->payer('utilisateur-jean', 'commande-jean');

    expect($this->commandes->billets)->toHaveCount(3)
        ->and($this->commandes->placesRestantes)->toBe(['soiree-rock' => 0]);
});
