<?php

namespace tests\pest\fakes;

use nrv\application\ports\spi\UtilisateurRepository;
use nrv\domain\entities\Utilisateur;
use nrv\domain\exceptions\UserNotFound;
use Override;

class FakeUtilisateurRepository implements UtilisateurRepository
{
    /** @var Utilisateur[] */
    public array $utilisateurs = [];
    /** @var string[] */
    public array $emailsCherches = [];
    public int $saveCalls = 0;
    public ?\Throwable $saveThrows = null;

    public function findByEmail(string $email): ?Utilisateur
    {
        $this->emailsCherches[] = $email;
        foreach ($this->utilisateurs as $u) {
            if ($u->getEmail() === $email) {
                return $u;
            }
        }
        return null;
    }

    public function save(Utilisateur $utilisateur): void
    {
        $this->saveCalls++;
        if ($this->saveThrows !== null) {
            throw $this->saveThrows;
        }
        $this->utilisateurs[] = $utilisateur;
    }

    // même contrat que PgUtilisateurRepository : UserNotFound si l'identifiant est inconnu
    #[Override]
    public function findById(string $id): Utilisateur
    {   
        foreach ($this->utilisateurs as $u) {
            if ($u->getId() === $id) {
                return $u;
            }
        }
        throw new UserNotFound($id);
    }
}