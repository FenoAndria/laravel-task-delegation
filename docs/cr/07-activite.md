# CR 07 — Activité et commentaires

**Branche** : `feature/activity` · **Période** : 14 → 22 juin 2025

## Objectif

Donner à chaque mission un **fil** qui raconte ce qui s'est passé et permet d'en discuter, au même endroit que le travail. Dans la v1, la messagerie vivait à part, sur une page dédiée, rafraîchie toutes les 2 secondes par un polling global défaillant.

## Ce qui a été fait

- **Journal d'activité** : chaque événement important est enregistré avec son auteur :
  - mission créée, démarrée, terminée, annulée, rouverte ;
  - tâche ajoutée, assignée ou désassignée, changée de statut (avec le motif en cas de blocage), supprimée.
- **Commentaires** sur les missions : tout membre de l'organisation peut participer ; l'auteur ou un owner/admin peut supprimer un commentaire.
- **Fil unique** sur la page mission : commentaires et activité mêlés dans l'ordre chronologique, avec heures relatives (« 3 hours ago »).
- **Tests** : 9 nouveaux tests (124 au total), dont la séquence complète des événements d'une mission et le rendu des phrases.

## Choix techniques

- **Phrases reconstruites depuis des propriétés figées** (titre de la tâche, nom de l'assigné, motif) : une tâche renommée ou supprimée reste lisible dans l'historique.
- **Enregistrement explicite** dans les actions et contrôleurs plutôt que via des observers Eloquent : on sait exactement quel acteur et quel événement métier sont journalisés, sans bruit technique.
- **Timeline construite côté serveur** (`TimelinePresenter`), limitée aux 100 derniers éléments.
- **Formatage du temps** avec l'API native `Intl.RelativeTimeFormat` : aucune dépendance ajoutée (la v1 embarquait dayjs pour ça).

## Limites et suite

- Pas encore de @mentions ni de notifications : le fil se consulte, il ne prévient pas. C'est l'objet de la fonctionnalité suivante.
- Le fil n'est pas encore en temps réel (rechargement à l'action).
- **Prochaine étape** : notifications dans l'application et par e-mail.
