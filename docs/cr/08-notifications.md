# CR 08 — Notifications

**Branche** : `feature/notifications` · **Période** : 5 → 13 juillet 2025

## Objectif

Prévenir les bonnes personnes quand du travail arrive ou qu'un problème survient, au lieu de compter sur elles pour aller vérifier.

## Ce qui a été fait

- **Déclencheurs** :

  | Événement | Destinataire | Canal |
  |---|---|---|
  | Mission confiée à une équipe (ou réaffectée) | Lead de l'équipe | Application + e-mail |
  | Tâche assignée ou réassignée | Assigné | Application + e-mail |
  | Tâche bloquée | Lead de l'équipe | Application |
  | Mission terminée | Créateur de la mission | Application |
  | Nouveau commentaire | Créateur, lead et assignés de la mission | Application |

- **L'auteur d'une action n'est jamais notifié** de sa propre action ; les doublons sont écartés.
- **Page Notifications** : liste des 50 dernières, non lues mises en avant ; un clic marque comme lu et ouvre la mission ; « Mark all as read ».
- **Compteur de non-lues** dans la barre latérale.
- **Par organisation** : liste et compteur ne concernent que l'organisation courante.
- **Tests** : 11 nouveaux tests (135 au total), dont le rendu réel des e-mails et l'impossibilité d'ouvrir la notification d'un autre utilisateur.

## Choix techniques

- **Notifications natives de Laravel** (canal `database` + `mail`) au lieu des trois tables quasi identiques de la v1 (`admin_notifications`, `team_notifications`, `member_notifications`), dont la colonne `read` n'était jamais utilisée.
- **Une classe de base** (`AppNotification`) : chaque notification ne décrit que son message (titre, détail, lien) ; le même message alimente l'application et l'e-mail.
- **En file d'attente** (`ShouldQueue`) : aucun envoi ne ralentit l'action de l'utilisateur.
- **Un utilitaire** (`Notify::send`) qui filtre l'auteur et les destinataires absents ou en double, pour que chaque point d'envoi tienne en une ligne.

## Limites et suite

- Pas encore de préférences de notification par utilisateur, ni de résumé quotidien.
- Les notifications ne s'affichent pas en temps réel : le compteur se met à jour à la navigation. Le temps réel viendra avec Reverb.
- **Prochaine étape** : la **délégation intelligente** (charge d'équipe, assignation suggérée, détection des risques).
