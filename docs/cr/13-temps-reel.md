# CR 13 — Temps réel

**Branche** : `feature/realtime` · **Période** : 8 → 23 novembre 2025

## Objectif

Que chacun voie le travail avancer **sans recharger la page**. Dans la v1, la messagerie interrogeait le serveur toutes les 2 secondes, via un drapeau global partagé par tous les utilisateurs, si bien que certains rataient des messages.

## Ce qui a été fait

- **Laravel Reverb**, le serveur WebSocket officiel de Laravel, avec Laravel Echo côté Vue (`@laravel/echo-vue`).
- **Page mission en direct** : dès qu'une tâche est créée, assignée ou change de statut, qu'un commentaire est posté ou supprimé, ou que la mission avance, la page se met à jour chez tous ceux qui la regardent.
- **Présence** : « Sara is here too » indique qui d'autre consulte la mission.
- **Notifications en direct** : le compteur de la barre latérale se met à jour dès qu'une notification arrive.
- **`composer dev`** lance aussi Reverb.
- **Tests** : 5 nouveaux tests (171 au total). Autorisation du canal de présence, déclenchement de l'événement par les tâches et les commentaires, diffusion des notifications.

## Vérifié de bout en bout

Deux navigateurs réels (Tom et Sara) sur la même mission, avec Reverb et une file d'attente actifs :

- chacun voit l'autre présent, et la mention disparaît quand l'autre part ;
- le commentaire de Sara apparaît chez Tom **en 3,6 s, sans rechargement** ;
- le compteur de notifications de Tom se rafraîchit ;
- **Reverb arrêté** : commenter fonctionne normalement, sans aucune erreur sur la page.

## Choix techniques

- **Un seul événement léger** (`MissionChanged`, avec l'identifiant de la mission seulement) : la page recharge ses propres données via un *partial reload* Inertia. Pas de duplication de l'état côté client, et les droits sont toujours appliqués par le serveur.
- **Déclenché depuis le journal d'activité et les commentaires** : tout ce qui est journalisé est automatiquement diffusé, sans rien ajouter aux contrôleurs.
- **Diffusion en file d'attente, après la transaction** : un serveur WebSocket en panne ne peut jamais faire échouer l'action d'un utilisateur.
- **Canal de présence autorisé par organisation** (`MissionChannel`) : un utilisateur d'une autre organisation ne peut ni écouter ni apparaître.
- **Optionnel** : Echo n'est configuré que si les variables Reverb existent ; le seeder de démo ne diffuse rien.
- Rechargements regroupés (300 ms) quand plusieurs événements arrivent ensemble.

## Limites et suite

- Les listes (missions, « My tasks », tableau de bord) ne sont pas encore en direct ; seule la page mission et les notifications le sont.
- **Prochaine étape** : README vitrine et mise en ligne.
