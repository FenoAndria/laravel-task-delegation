# CR 06 — Tâches et workflow

**Branche** : `feature/tasks` · **Période** : 17 → 31 mai 2025

## Objectif

Boucler la chaîne de délégation : le lead **découpe la mission en tâches**, les **assigne** à son équipe, les membres les font avancer, et la mission se termine quand le travail est fait.

## Ce qui a été fait

- **Modèle** : une tâche appartient à une mission, avec assigné, échéance, estimation en heures, ordre d'affichage et motif de blocage.
- **Workflow de statuts explicite** (machine à états dans l'enum `TaskStatus`) :

  | Depuis | Vers |
  |---|---|
  | À faire | En cours, Bloquée, Annulée |
  | En cours | À faire, Bloquée, Terminée, Annulée |
  | Bloquée | À faire, En cours, Annulée |
  | Terminée | En cours (réouverture) |
  | Annulée | À faire |

- **Règles métier** :
  - seuls le lead de l'équipe et les owners/admins créent, modifient, assignent et suppriment les tâches ;
  - une tâche ne peut être assignée qu'au lead ou à un membre de l'équipe ;
  - l'assigné fait avancer **sa** tâche ; bloquer une tâche exige d'en donner la raison, effacée au déblocage ;
  - la **première tâche démarrée fait passer la mission « en cours »** ;
  - la mission ne peut être **terminée** (par le lead ou un manager) que si au moins une tâche est terminée et toutes les autres terminées ou annulées ;
  - une mission fermée gèle ses tâches.
- **Page mission** : barre de progression, ajout rapide de tâche (titre, assigné, échéance), réassignation, changement de statut limité aux transitions permises, bouton « Complete mission » quand c'est possible.
- **Page « My tasks »** : tout ce qui est assigné à l'utilisateur dans l'organisation courante, groupé (en cours, à faire, bloquées, terminées depuis 14 jours), avec lien vers la mission.
- **Liste des missions** : progression (tâches terminées / total) calculée en une requête.
- **Tests** : 17 nouveaux tests (115 au total).

## Bugs de la v1 corrigés par conception

- v1 : une tâche **sans aucune sous-tâche** pouvait être clôturée → désormais au moins une tâche terminée est exigée.
- v1 : une seule sous-tâche **annulée bloquait** la clôture → les tâches annulées sont ignorées.
- v1 : aucun contrôle des transitions (retour de « In Progress » à « Todo » sans règle) → workflow explicite et testé.

## Choix techniques

- **Transitions portées par l'enum** et renvoyées au front pour chaque tâche selon les droits du visiteur : l'interface ne propose jamais une action que le serveur refuserait.
- **Présentation partagée** (`TaskPresenter`) entre la page mission et « My tasks ».
- **Requêtes maîtrisées** : progression via `withCount`, mission rattachée aux tâches déjà chargées pour éviter les requêtes N+1 lors des vérifications de droits.

## Limites et suite

- Le changement de statut passe par une liste déroulante et le motif de blocage par une fenêtre native : le **kanban en glisser-déposer** et de vraies modales arriveront avec la refonte de l'interface.
- L'estimation en heures est saisie mais pas encore exploitée : elle alimentera la **jauge de charge** de la délégation intelligente.
- **Prochaine étape** : commentaires, journal d'activité et notifications.
