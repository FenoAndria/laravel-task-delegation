# CR 04 — Équipes

**Branche** : `feature/teams` · **Période** : 12 → 20 avril 2025

## Objectif

Recréer le cœur de la hiérarchie de Delego : au sein d'une organisation, des équipes avec un **lead** qui organise le travail et des **membres** qui l'exécutent.

## Ce qui a été fait

- **Modèle** : `teams` (nom unique par organisation, description, lead) et `team_user` pour les membres (unicité garantie en base).
- **Page Équipes** : liste des équipes de l'organisation (lead, nombre de membres), création via une fenêtre modale avec choix du lead.
- **Page d'une équipe** : renommer, changer ou retirer le lead, ajouter et retirer des membres, supprimer l'équipe.
- **Droits** :
  - owners et admins de l'organisation : créer, modifier, supprimer les équipes et nommer les leads ;
  - lead : gérer les membres de **son** équipe ;
  - membres : consulter.
- **Règles** :
  - lead et membres doivent appartenir à l'organisation ;
  - un membre promu lead quitte la liste des membres ; le lead ne peut pas être ajouté comme membre ;
  - quitter (ou être retiré d') une organisation retire la personne de ses équipes et de ses postes de lead ;
  - les équipes d'une autre organisation sont introuvables (404).
- **Navigation** : entrée « Teams » dans la barre latérale, et correction d'un bug du starter kit (les liens de la barre latérale lisaient une propriété inexistante).
- **Tests** : 16 nouveaux tests (89 au total).

## Choix techniques

- **Un utilisateur peut diriger plusieurs équipes et être membre d'autres.** La v1 interdisait à un lead d'être membre ailleurs ; cette contrainte venait d'un modèle sans organisations et n'a plus de raison d'être.
- **Ajout direct des membres** plutôt qu'une invitation d'équipe comme en v1 : la personne a déjà accepté de rejoindre l'organisation, une deuxième invitation n'apportait que de la friction.
- **Garde d'organisation générique** (`ensureInOrganization`) dans le contrôleur de base, réutilisable pour toutes les ressources à venir (missions, tâches).
- **Policy dédiée** (`TeamPolicy`) qui distingue ce que peut faire l'organisation de ce que peut faire le lead.

## Limites et suite

- Pas encore d'historique des changements de lead (le journal d'activité viendra plus tard).
- **Prochaine étape** : les **missions**, que l'organisation confie à une équipe, et les **tâches** que le lead en tire et assigne à ses membres.
