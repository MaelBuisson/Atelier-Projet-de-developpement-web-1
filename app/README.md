# API NRV (backend)

API RESTful en PHP 8.5 / Slim 4, construite selon une architecture hexagonale.
Elle retourne du JSON avec des liens HATEOAS et s'exécute dans le conteneur `api.nrv`
(http://localhost:7080). L'installation est décrite dans le [README général](../README.md).

## Routes

| Méthode | Route                                | Réponse |
|---------|--------------------------------------|---------|
| GET     | `/spectacles`                        | liste des spectacles : titre, date, horaire, image, lien vers la soirée |
| GET     | `/spectacles?style=Metal`            | filtrée par style de musique (sans tenir compte des majuscules) |
| GET     | `/spectacles?date=2027-07-09`        | filtrée par date de soirée (format `AAAA-MM-JJ`) |
| GET     | `/soirees`                           | liste des soirées triées par date : nom, thématique, date, horaire, lieu, lien vers le détail |
| GET     | `/soiree/{id}`                       | détail d'une soirée : tarifs, spectacles, artistes |

Les filtres se combinent : `/spectacles?style=Punk&date=2027-07-10`.

### Format des réponses

Chaque réponse contient les données et leurs liens :

```json
{
  "spectacles": [
    {
      "id": "20000000-0000-4000-8000-000000000001",
      "titre": "Rolling Thunder",
      "date": "2027-07-08",
      "horaire": "20:00",
      "image": "https://picsum.photos/seed/nrv-rolling-thunder-1/400/300",
      "idSoiree": "10000000-0000-4000-8000-000000000001",
      "links": { "soiree": { "href": "/soiree/10000000-0000-4000-8000-000000000001" } }
    }
  ],
  "links": { "self": { "href": "/spectacles" } }
}
```

### Codes HTTP

| Code | Cas |
|------|-----|
| 200  | succès (une liste peut être vide si aucun spectacle ne correspond aux filtres) |
| 400  | paramètre invalide : identifiant qui n'est pas un UUID, date mal formée, style trop long |
| 404  | ressource introuvable (soirée inexistante) |
| 500  | erreur interne ; le détail n'est affiché que si `DISPLAY_ERROR_DETAILS=true` |

## Architecture (`src/`)

```
domain/
  entities/        Soiree, Spectacle, Artiste, Image, Tarif
  exceptions/      SoireeNotFound, SpectacleNotFound
application/
  ports/api/       interfaces des services, utilisées par les actions
  ports/spi/       interfaces des repositories, utilisées par les services
  usecases/        SoireeService, SpectacleService
  dto/             objets retournés par les services et sérialisés en JSON
adapters/
  controllers/     actions Slim (une par route) et middlewares (CORS, rendu JSON)
  persistence/     repositories PostgreSQL (PDO, requêtes préparées)
  config/          ContainerConfig : associe chaque port à son implémentation (PHP-DI)
```

Une requête traverse les couches dans cet ordre :
action → port API → use case → port SPI → repository → base,
puis entité → DTO → JSON au retour. Le domaine et les use cases ne dépendent que d'interfaces.

## Configuration

- `config/.env` (copié depuis `config/.env.dist`) : connexion à la base et affichage des erreurs
- `config/bootstrap.php` : création de l'application Slim, middlewares, routes
- `public/index.php` : point d'entrée unique

## Base de données (`sql/`)

- `NRV.schema.sql` : tables PostgreSQL, identifiants UUID, un tarif normal et un tarif réduit par soirée
- `NRV.data.sql` : données de test, 6 soirées du 8 au 10 juillet 2027, 14 spectacles, 15 artistes fictifs

## Tests

Tests unitaires Pest dans `tests/pest/` : domaine (`domaine/`) et use cases (`services/`).
Les repositories sont remplacés par des fakes en mémoire (`fakes/`), sans base de données.

```bash
docker compose exec api.nrv composer test
```
