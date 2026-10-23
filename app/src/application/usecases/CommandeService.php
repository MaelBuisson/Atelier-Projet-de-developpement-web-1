<?php

namespace nrv\application\usecases;

use DateTimeImmutable;
use nrv\application\dto\CommandeDTO;
use nrv\application\dto\PanierDTO;
use nrv\application\ports\api\CommandeServiceInterface;
use nrv\application\ports\spi\CommandeRepository;
use nrv\application\ports\spi\SoireeRepository;
use nrv\application\ports\spi\TarifRepository;
use nrv\application\ports\spi\UtilisateurRepository;
use nrv\domain\entities\Commande;
use nrv\domain\entities\SoireeDansCommande;
use nrv\domain\exceptions\PanierVide;
use nrv\domain\exceptions\SoireeDansCommandeNotFound;
use nrv\domain\exceptions\CommandeNotFound;
use Ramsey\Uuid\Uuid;

/**
 * Use cases du panier et des commandes.
 */
class CommandeService implements CommandeServiceInterface
{
    // repositories injectés (PHP-DI) : le service ne dépend que des interfaces
    public function __construct(
        private readonly CommandeRepository $commandeRepository,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly SoireeRepository $soireeRepository,
        private readonly TarifRepository $tarifRepository
    ) {
    }

    /**
     * Panier de l'utilisateur. S'il n'en a pas encore, un panier vide est renvoyé
     * (il n'est pas enregistré : il le sera au premier ajout de billets).
     *
     * @throws \nrv\domain\exceptions\UserNotFound si l'utilisateur du token n'existe plus
     */
    public function getPanier(string $idUtilisateur): PanierDTO
    {
        return PanierDTO::fromEntity($this->panierDe($idUtilisateur));
    }

    /**
     * Ajoute des billets au panier. Tout se fait en mémoire, puis un seul save() :
     * si une étape lève une exception, rien n'est enregistré.
     */
    public function ajouterAuPanier(string $idUtilisateur, string $idSoiree, string $nomTarif, int $quantite): PanierDTO
    {
        $commande = $this->panierDe($idUtilisateur);

        // soirée d'abord : pour une soirée inexistante, l'erreur la plus claire est SoireeNotFound
        $soiree = $this->soireeRepository->findById($idSoiree);

        // le prix vient toujours de la base, jamais du client
        $tarif = $this->tarifRepository->findBySoireeNom($idSoiree, $nomTarif);

        // nouvel identifiant de ligne ; si la soirée et le tarif sont déjà dans le panier,
        // ajouterSoiree() ajoute la quantité à la ligne existante et garde son identifiant
        $sds = new SoireeDansCommande(Uuid::uuid4()->toString(), $soiree, $tarif, $quantite);

        $commande->ajouterSoiree($sds);
        $this->commandeRepository->save($commande);

        return PanierDTO::fromEntity($commande);
    }

    /**
     * Retire un billet d'une ligne du panier. La ligne est cherchée uniquement dans le panier
     * de l'utilisateur : la ligne d'un autre utilisateur est introuvable.
     */
    public function supprimerDuPanier(string $idUtilisateur, string $idLigne): PanierDTO
    {
        $commande = $this->commandeRepository->getPanier($idUtilisateur);

        // pas de panier : aucune ligne à retirer (inutile d'en créer un vide)
        if ($commande === null) {
            throw new SoireeDansCommandeNotFound($idLigne);
        }

        $commande->supprimerSoiree($idLigne);

        $this->commandeRepository->save($commande);

        return PanierDTO::fromEntity($commande);
    }

    // panier de l'utilisateur, ou nouveau panier vide (pas encore enregistré) s'il n'en a pas
    private function panierDe(string $idUtilisateur): Commande
    {
        return $this->commandeRepository->getPanier($idUtilisateur)
            ?? new Commande(
                Uuid::uuid4()->toString(),
                new DateTimeImmutable(),
                $this->utilisateurRepository->findById($idUtilisateur)
            );
    }

    /**
     * Le panier devient une commande VALIDEE ; les règles (panier non vide, statut PANIER) sont dans le domaine.
     * Un seul save() après valider() : en cas de refus, rien n'est enregistré.
     */
    public function valider(string $idUtilisateur): CommandeDTO
    {
        $commande = $this->commandeRepository->getPanier($idUtilisateur);

        // pas de panier : pour le client, c'est un panier vide (rien à valider)
        if ($commande === null) {
            throw new PanierVide();
        }

        $commande->valider();

        $this->commandeRepository->save($commande);

        return CommandeDTO::fromEntity($commande);
    }

    /**
     * Commande de l'utilisateur, quel que soit son statut.
     */
    public function getCommande(string $idUtilisateur, string $idCommande): CommandeDTO
    {
        return CommandeDTO::fromEntity($this->commandeDe($idUtilisateur, $idCommande));
    }

    /**
     * Paiement simulé (aucune donnée bancaire) : le domaine passe la commande au statut PAYEE
     * et crée un billet par place ; le repository vérifie les places et enregistre le tout
     * dans une seule transaction. En cas de refus, la commande reste validée.
     */
    public function payer(string $idUtilisateur, string $idCommande): CommandeDTO
    {
        $commande = $this->commandeDe($idUtilisateur, $idCommande);

        $billets = $commande->payer();

        $this->commandeRepository->enregistrerPaiement($commande, $billets);

        return CommandeDTO::fromEntity($commande);
    }

    // commande inexistante ou d'un autre utilisateur : même réponse, pour ne pas révéler qu'elle existe
    private function commandeDe(string $idUtilisateur, string $idCommande): Commande
    {
        $commande = $this->commandeRepository->findById($idCommande);

        if ($commande === null || $commande->getUtilisateur()->getId() !== $idUtilisateur) {
            throw new CommandeNotFound($idCommande);
        }

        return $commande;
    }
}
