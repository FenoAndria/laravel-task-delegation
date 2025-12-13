# CR 14 — Renommage en `laravel-task-delegation`

**Branche** : `chore/rename` · **Date** : 13 décembre 2025

## Pourquoi

« Delego » est déjà utilisé par d'autres produits. Plutôt qu'une marque commerciale, le projet prend un nom **descriptif et technique**, dans l'esprit des dépôts Laravel open source : `laravel-task-delegation`. Il dit la techno et le sujet en un coup d'œil (aucun dépôt GitHub ne portait ce nom).

## Ce qui a changé

- **Nom affiché** : logo, page d'accueil, connexion, titre des pages, méta-description. Le mot-symbole passe en **JetBrains Mono** : un nom en kebab-case se lit mieux en police technique qu'en serif.
- **E-mails** : sujet d'invitation « You are invited to join … », bouton « Open ».
- **Configuration** : `config/delego.php` devient `config/delegation.php`, et les variables `DELEGO_*` deviennent `DELEGATION_*`.
- **Package Composer** : `fenoandria/laravel-task-delegation`.
- **Démo** : comptes en `@task-delegation.test`.
- **README** et **captures d'écran** mis à jour.

## Migration pour une installation existante

Dans `.env`, renommer les variables `DELEGO_*` en `DELEGATION_*`, puis relancer `php artisan config:clear`.

Les CR précédents gardent le nom de l'époque : ils racontent le projet tel qu'il était.
