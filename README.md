# NRV : billetterie du festival Nancy Rock Vibration

SAE « Atelier-projet de développement web 1 » (BUT 3 Informatique DWM, IUT Nancy-Charlemagne).

Application de vente de billets en ligne : programme du festival, détail des soirées,
filtrage par date et par style, panier et billets.

## Auteurs

- Leandre Aimé--Cabocel
- Walid Bouaoukel
- Mael Buisson

| Partie | Dossier | Technologies | Documentation |
|--------|---------|--------------|---------------|
| API (backend) | [`app/`](app/) | PHP 8.5, Slim 4, PHP-DI, PostgreSQL 18, Pest | [app/README.md](app/README.md) |
| Client web | [`web/`](web/) | HTML, SCSS, JavaScript (`fetch`) | [web/README.md](web/README.md) |

## Installation

Prérequis : Docker et Docker Compose.

1. Créer les deux fichiers de configuration à partir des modèles, avec **les mêmes identifiants** :

   ```bash
   cp nrv.env.dist nrv.env
   cp app/config/.env.dist app/config/.env
   ```

   - `nrv.env` : `POSTGRES_USER`, `POSTGRES_PASSWORD` (base PostgreSQL)
   - `app/config/.env` : `USERNAME`, `PASSWORD` (connexion de l'API à la base),
     `DISPLAY_ERROR_DETAILS=true` en développement

2. Lancer les conteneurs :

   ```bash
   docker compose up -d --build
   ```

   Au premier démarrage, `composer install` est exécuté et la base est créée à partir de
   `app/sql/NRV.schema.sql` puis remplie avec `app/sql/NRV.data.sql`.

3. Pour recréer la base après une modification des fichiers SQL :

   ```bash
   docker compose down -v
   docker compose up -d
   ```

## Services

| Service   | URL                     | Rôle                                         |
|-----------|-------------------------|----------------------------------------------|
| `api.nrv` | http://localhost:7080   | API RESTful (JSON)                           |
| `web.nrv` | http://localhost:7081   | Client web                                   |
| `adminer` | http://localhost:8880   | Administration de la base (serveur `nrv.db`) |
| `nrv.db`  | localhost:5532          | PostgreSQL                                   |

## Organisation du dépôt

```
app/                  API PHP/Slim (voir app/README.md)
web/                  client HTML/SCSS/JS (voir web/README.md)
build/                Dockerfile de l'image PHP
docker-compose.yml    conteneurs api.nrv, nrv.db, web.nrv, adminer
nrv.env.dist          modèle de configuration de la base
```

Branches : le travail se fait sur des branches `feature/…`, fusionnées dans `main`.
