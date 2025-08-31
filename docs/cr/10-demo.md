# CR 10 — Démo et connexion en un clic

**Branche** : `feature/demo` · **Période** : 23 → 31 août 2025

## Objectif

Permettre à n'importe qui (recruteur, curieux) de **comprendre Delego en 30 secondes**, sans créer de compte ni saisir de données.

## Ce qui a été fait

- **Organisation de démonstration** « Halcyon Studio » :
  - 9 personnes, 3 équipes (Product Design, Engineering, Marketing) ;
  - 7 missions dans tous les états (en cours, ouverte, en retard, terminée, annulée) ;
  - 22 tâches estimées, dont certaines bloquées ou en retard ;
  - commentaires, journal d'activité cohérent et quelques notifications.
- **Dates relatives au jour du seed** : la démo n'a jamais l'air périmée, et le tableau de bord affiche d'emblée des risques réels (mission en retard, tâches bloquées depuis 3 jours, mission due dans 2 jours…).
- **Connexion en un clic** sur la page de connexion, avec trois personas :
  - **Maya** (owner) confie les missions ;
  - **Tom** (lead Product Design) découpe et assigne ;
  - **Sara** (membre de deux équipes) exécute.
- **Réinitialisation** : commande `php artisan demo:reset`, planifiée chaque nuit à 3 h.
- **Configuration** : `DELEGO_DEMO` et `DELEGO_WEEKLY_CAPACITY_HOURS` dans `.env.example` ; `APP_NAME=Delego`.
- **Tests** : 7 nouveaux tests (152 au total), dont l'accès au tableau de bord pour chaque persona.

## Sécurité

- La connexion en un clic **n'existe pas** hors mode démo (404) et n'accepte que les trois personas connus.
- `demo:reset` **refuse de s'exécuter** si le mode démo est désactivé : impossible d'effacer une base de production par erreur.

## Limites et suite

- Le seeder construit les données directement (sans passer par les actions métier) pour pouvoir antidater l'historique.
- **Prochaine étape** : la refonte complète du design.
