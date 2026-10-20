# Client web NRV

Interface du festival en HTML, SCSS et JavaScript, servie par le conteneur `web.nrv`
(http://localhost:7081). L'installation est décrite dans le [README général](../README.md).

## Pages

| Page          | Contenu |
|---------------|---------|
| `index.html`  | programme : liste des spectacles, filtres par style et par date |
| `soiree.html` | détail d'une soirée : tarifs et spectacles |

## Organisation

```
scss/
  style.scss        feuille principale
  _variables.scss   couleurs et espacements
  _mixins.scss      points de rupture tablet-up (768px) et desktop-up (1024px), approche mobile first
css/
  style.css         CSS compilé, à ne pas modifier à la main
js/
  script.js         navigation et filtres
```

## Compiler le SCSS

Le CSS est généré à partir des fichiers SCSS avec [Dart Sass](https://sass-lang.com/dart-sass/) :

```bash
npx sass web/scss/style.scss web/css/style.css
```

Ajouter `--watch` pour recompiler à chaque modification.

## Utilisation de l'API

L'API tourne sur http://localhost:7080 (routes détaillées dans [app/README.md](../app/README.md)).

- Liste : `fetch('http://localhost:7080/spectacles')`, les données sont dans `data.spectacles`
- Filtres : `fetch('http://localhost:7080/spectacles?' + new URLSearchParams({ style, date }))`
- Détail : chaque spectacle de la liste contient `links.soiree.href` ;
  la page `soiree.html?id=…` appelle `fetch('http://localhost:7080/soirees/' + id)`
  et lit la soirée dans `data.soiree`

L'API autorise les requêtes venant du client (en-têtes CORS).
