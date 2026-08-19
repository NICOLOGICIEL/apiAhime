# Ahime API (Lumen)

API REST regénérée à partir de l'analyse de la base `c0ahi1745.sql` et des
appels réseau du client Flutter (`lib/app/data/services/api_service.dart`,
`lib/config/my_config.dart`). Elle remplace le contrat historique du
backend (`data_action: ReqExec` + une chaîne SQL brute envoyée par le
client) par des endpoints REST paramétrés : le serveur ne construit plus
jamais de SQL à partir d'une entrée client, ce qui élimine le risque
d'injection qui existait côté serveur avec l'ancien contrat.

> ⚠️ Le client Flutter actuel (`api_service.dart`, `my_config.dart`)
> continue d'appeler l'ancien backend `ahime-ci.com`. Pour utiliser cette
> API, il faudra adapter ces fichiers afin qu'ils appellent les routes
> ci-dessous au lieu d'envoyer du SQL — ce n'est pas encore fait.

## Installation

```bash
cd api
composer install
cp .env.example .env
```

Importer la base existante (le dossier `api/` ne contient pas de
migrations : le schéma vient du dump déjà présent à la racine du dépôt) :

```bash
mysql -u root -p -e "CREATE DATABASE c0ahi1745 CHARACTER SET utf8mb4"
mysql -u root -p c0ahi1745 < ../c0ahi1745.sql
```

Renseigner `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` dans `.env`, puis
démarrer le serveur de développement :

```bash
php -S localhost:8000 -t public
```

> Pas d'`APP_KEY` à générer ni d'`artisan` : l'API n'utilise ni chiffrement
> ni commande console personnalisée.

## État de vérification

Ce backend a été testé de bout en bout (pas seulement relu) : `composer
install` réel, base SQLite de test peuplée avec le même schéma que
`c0ahi1745.sql`, serveur Lumen démarré, et chaque endpoint interrogé en
HTTP (recherche + filtres, détail, 404, pagination, validation 422,
POST de notation). Plusieurs bugs révélés uniquement par cette exécution
réelle ont été corrigés : `abort_if()`/`abort_unless()`/`now()`
n'existent pas dans Lumen (ce sont des helpers Laravel Foundation), les
routes `transports`/`compagnies` doivent nommer leurs paramètres comme
les méthodes des contrôleurs (`ligneId`, `departId`, `compagnieId`), et
les identifiants numériques dans les routes sont contraints à `\d+` pour
éviter un 500 sur un ID non numérique.

## Convention de pagination

Les listes de recherche acceptent `offset` et `limit` (par défaut 0 et 30,
maximum 100 par page) — équivalent du couple `xL1`/`xL2` utilisé côté
Flutter pour le scroll infini. Réponse :

```json
{
  "data": [ ... ],
  "meta": { "total": 123, "offset": 0, "limit": 30 }
}
```

## Endpoints

### Hôtels
Reprend les filtres de `myReq()`.

| Méthode | Route | Description |
|---|---|---|
| GET | `/api/hotels` | Recherche paginée. Filtres : `nom_etab`, `ville`, `commune`, `quartier`, `prix_max`, `nbr_etoile`, `wifi`, `piscine`, `spa`, `bar`, `ventilateur`, `climatiseur`, `est_residence`, `est_hotel` |
| GET | `/api/hotels/{id}` | Détail d'un hôtel (jointure `hotel` + `commoditehotel`). `{id}` numérique, sinon 404 |
| GET | `/api/hotels/{id}/images` | Galerie photo (table `imagehotel`) |

### Artisans
Reprend les filtres de `myReqArtisant()`.

| Méthode | Route | Description |
|---|---|---|
| GET | `/api/artisans` | Recherche paginée. Filtres : `metier`, `categorie`, `ville`, `commune`, `quartier`, `note_min` |
| GET | `/api/artisans/{id}` | Détail d'un artisan (jointure `artisant` + `metier` + `categoriemetier`) |
| GET | `/api/artisans/{id}/notations` | Avis/commentaires (table `notation`) |
| POST | `/api/artisans/{id}/notations` | Ajoute un avis : `nom_utilisateur`, `titre`, `note` (1-5), `commentaire` |

### Transport
Reprend `myReqTransport()`, `myReqHoraire()` et `myReqEscale()`.

| Méthode | Route | Description |
|---|---|---|
| GET | `/api/transports` | Recherche paginée de lignes. Filtres : `ville_depart`, `ville_arrivee`, `compagnie` |
| GET | `/api/transports/{ligneId}/horaires` | Départs d'une ligne (`depart` + `commoditetransport`), `{ligneId}` = `IDLIGNETRANSPORT` |
| GET | `/api/transports/departs/{departId}/escales` | Escales d'un départ, `{departId}` = `IDDEPART` |
| GET | `/api/compagnies/{compagnieId}/notations` | Avis sur une compagnie |
| POST | `/api/compagnies/{compagnieId}/notations` | Ajoute un avis sur une compagnie |

### Listes de référence (menus déroulants)
Remplacent les appels `ReqMultiExec` de `getdataAll()` / `getdataVille()`.

| Méthode | Route | Description |
|---|---|---|
| GET | `/api/villes` | Liste des villes (table `ville`) |
| GET | `/api/metiers` | Liste des métiers (table `metier`) |
| GET | `/api/categories` | Liste des catégories de métier |
| GET | `/api/compagnies` | Liste des compagnies de transport (`IDCOMPAGNIE`, `Nom`) |

## Déploiement (hébergement mutualisé LWS)

L'appli est prévue pour vivre dans un sous-dossier `api/` à la racine du
domaine (ex: `www/api/` sur LWS), avec les routes déjà préfixées `api/`
côté Lumen (`bootstrap/app.php`) — d'où `APP_URL=https://ahime-ci.com/api`
dans `.env`. Deux fichiers `.htaccess` gèrent la réécriture d'URL requise
par Apache :

- `api/.htaccess` : renvoie toute requête vers `public/` (nécessaire car le
  manager LWS ne permet en général pas de pointer la racine du domaine
  directement sur un sous-dossier `public`).
- `api/public/.htaccess` : contrôleur frontal Lumen standard (toute requête
  qui n'est ni un fichier ni un dossier réel part vers `index.php`).

Si vous déployez plutôt sur un sous-domaine dédié dont vous pouvez pointer
la racine directement sur `public/` (ex: `api.ahime-ci.com` → dossier
`public`), supprimez `api/.htaccess` et retirez le `/api` final de
`APP_URL`.

Étapes :

1. **PHP** : dans le manager LWS, sélectionner PHP 8.1+ pour le domaine
   (extensions `pdo_mysql`, `mbstring`, `openssl` — activées par défaut).
2. **Dépendances** : LWS ne propose pas toujours Composer en SSH sur les
   formules d'entrée de gamme. Le plus fiable est de générer `vendor/` en
   local puis de l'uploader :

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

   Si un accès SSH est disponible, `composer install --no-dev
   --optimize-autoloader` peut être lancé directement sur le serveur après
   un `git clone`/upload du code source (sans `vendor/`).
3. **Upload** : envoyer tout le dossier `api/` (avec `vendor/`) par
   FTP/SSH dans `www/api/` — `.env` n'est jamais committé (voir
   `.gitignore`), il doit être déposé manuellement sur le serveur.
4. **`.env` de production** : `APP_ENV=production`, `APP_DEBUG=false`,
   `APP_KEY` renseigné, et les identifiants MySQL fournis par LWS
   (host généralement `127.0.0.1`, base/utilisateur au format
   `xxxx000000`). Déjà en place dans `.env` local — à recopier tel quel
   sur le serveur.
5. **Permissions** : `storage/framework/{cache,sessions,views}` et
   `storage/logs` doivent être inscriptibles par le process PHP (775 si le
   défaut FTP ne suffit pas).
6. **Vérification** : `https://ahime-ci.com/api/` doit renvoyer
   `{"name":"Ahime API","status":"ok"}`, puis tester un endpoint réel
   (`/api/villes`) et un POST (`/api/artisans/{id}/notations`).

## Notes sur le périmètre

- Seules les tables réellement utilisées par le client Flutter ont été
  modélisées (`hotel`, `commoditehotel`, `imagehotel`, `artisant`,
  `metier`, `categoriemetier`, `compagnie`, `lignetransport`, `depart`,
  `commoditetransport`, `escale`, `ville`, `notation`). La base contient
  d'autres tables (immobilier, back-office `GPU_*`, comptes utilisateurs,
  abonnements...) non exposées ici car aucun écran actuel de
  l'application ne les appelle.
- Les endpoints de notation (`/artisans/{id}/notations`,
  `/compagnies/{id}/notations`) sont fournis car la table `notation`
  existe et qu'un écran de commentaires existe déjà côté Flutter
  (`page_artisancommentaire.dart`) — mais cet écran est aujourd'hui une
  maquette statique (contenu factice, aucun appel réseau), donc ces
  routes ne sont pas encore consommées.
- Aucune authentification n'est mise en place (l'app actuelle n'en a pas
  non plus). À ajouter avant toute mise en production si les endpoints
  d'écriture (`POST .../notations`) sont exposés publiquement.
