# CR 05 — Missions

**Branche** : `feature/missions` · **Période** : 3 → 11 mai 2025

## Objectif

Donner corps à la délégation : l'organisation **confie une mission à une équipe**. C'est l'équivalent des « tâches » créées par l'admin dans la v1, renommées pour mieux traduire l'idée de délégation.

## Ce qui a été fait

- **Modèle** : une mission appartient à une équipe (et à son organisation), avec titre, description, **priorité** (low, normal, high, urgent), échéance facultative et **statut** (open, in progress, completed, cancelled).
- **Page Missions** :
  - filtre par statut (actives par défaut, terminées, annulées, toutes) et par équipe, conservé dans l'URL ;
  - tri par urgence puis par échéance ;
  - missions en retard signalées ;
  - création dans une fenêtre modale.
- **Page d'une mission** : détails (équipe, lead, échéance, créateur), modification, **annulation** et **réouverture**, suppression.
- **Droits** : owners et admins gèrent les missions ; tous les membres de l'organisation peuvent les consulter.
- **Règles** :
  - une mission ne peut être confiée qu'à une équipe de la même organisation ;
  - seule une mission active peut être annulée, seule une mission inactive peut être rouverte ;
  - les missions d'une autre organisation sont introuvables (404).
- **Composants partagés** : badge de statut, libellé de priorité, champs de formulaire de mission (création et modification).
- **Tests** : 9 nouveaux tests (98 au total).

## Choix techniques

- **Enums PHP natives** (`MissionStatus`, `Priority`) avec libellés et poids de tri, au lieu des chaînes dupliquées partout dans la v1.
- **Filtres dans l'URL** (`?status=completed&team=3`) : une vue filtrée peut être partagée ou mise en favori.
- **Retard calculé** (`isOverdue`) plutôt que stocké : toujours juste, sans tâche planifiée.

## Limites et suite

- Le passage à « in progress » et la **complétion** d'une mission dépendront de ses tâches : ce sera la prochaine fonctionnalité.
- Pas encore de pagination : à ajouter quand les volumes le justifieront.
- **Prochaine étape** : les **tâches** (découpage de la mission par le lead, assignation, workflow de statuts) et la page « Mes tâches ».
