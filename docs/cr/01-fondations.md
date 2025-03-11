# CR 01 — Fondations techniques

**Branche** : `feature/foundation` · **Période** : 8 → 11 mars 2025

## Objectif

Repartir sur une base moderne et outillée pour la réécriture de Team Flow, désormais **Delego**, sans rien perdre de l'ancienne version.

## Ce qui a été fait

- **Ancienne version préservée** : le code v1 (Laravel 10 API + SPA Vue/Vuex) reste accessible via le tag `v1-2024`.
- **Nouvelle base** : starter kit officiel Laravel 12 Vue (Inertia 2, Vue 3, TypeScript, Tailwind, shadcn-vue), avec authentification complète (inscription, connexion, mot de passe oublié, vérification d'e-mail, paramètres du profil).
- **Tests** : Pest, 27 tests verts sur l'authentification et les paramètres.
- **Analyse statique** : Larastan au niveau 6, sans erreur.
- **Style de code** : Pint (PHP), Prettier et ESLint (front), appliqués à tout le code.
- **Intégration continue** : un workflow GitHub Actions unique : qualité (Pint, Larastan, Prettier, ESLint) et tests sur PHP 8.3 et 8.4.
- **Scripts** : `composer test`, `composer analyse`, `composer lint`, `npm run lint:check`.
- **README** réécrit pour Delego.

## Choix techniques

- **Inertia plutôt qu'une API REST + SPA** : plus de stores Vuex, de tokens dans le localStorage ni de couche « services » dupliquée. Le routage, l'authentification et la validation restent côté Laravel.
- **Reconstruction plutôt que migration** : l'écart Laravel 10 → 12 et le changement d'architecture front rendaient une migration plus coûteuse qu'un nouveau départ ; la logique métier v1 sert de spécification.
- **SQLite en local et en CI** : zéro configuration pour démarrer ; la base de production sera choisie au déploiement.

## Limites et suite

- Larastan reste au niveau 6 : le niveau 8 remonte des cas de nullabilité dans le code du starter kit, à traiter plus tard.
- Le contrôle de types (`vue-tsc`) n'est pas encore en CI : les composants du kit seront réécrits avec la refonte du design.
- **Prochaine étape** : organisations (multi-tenant) et rôles par organisation.
