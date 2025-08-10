# CR 09 — Délégation intelligente (sans IA)

**Branche** : `feature/delegation-insights` · **Période** : 26 juillet → 10 août 2025

## Objectif

Faire de Delego plus qu'un gestionnaire de tâches : aider le lead à **répartir** le travail et l'organisation à **voir venir les problèmes**, avec des règles simples et explicables, sans dépendre d'une IA.

## Ce qui a été fait

- **Jauge de charge** : pour chaque personne, heures de travail ouvert (estimation de chaque tâche, 3 h par défaut si non estimée) rapportées à une capacité hebdomadaire (30 h par défaut, configurable). Niveaux : *disponible* (< 50 %), *équilibré*, *surchargé* (> 100 %). La charge est calculée **sur toute l'organisation**, pas seulement l'équipe : quelqu'un de surchargé ailleurs n'est pas disponible ici.
- **Assignation suggérée** : sur la page mission, le lead voit les 3 personnes les plus indiquées, avec la **raison** (« 4h of open work (13% of capacity) · already on this mission »). Un clic présélectionne l'assigné.
  - Score : taux de charge, forte pénalité par tâche en retard, léger bonus si la personne travaille déjà sur la mission.
- **Détection des risques** (règles) :

  | Risque | Niveau |
  |---|---|
  | Mission en retard | Élevé |
  | Mission due sous 3 jours avec moins de 75 % des tâches terminées | Moyen |
  | Mission dont l'équipe n'a pas de lead | Moyen |
  | Tâche bloquée depuis plus de 2 jours | Moyen |
  | Tâche en retard | Moyen |
  | Membre surchargé | Moyen |
  | Tâches sans assigné dans une mission due sous 7 jours | Faible |

- **Tableau de bord** (remplace le placeholder du starter kit) : 4 indicateurs, « Needs attention » (risques triés par gravité, cliquables), charge de chacun, missions en cours avec progression.
- **Page équipe** : charge de chaque personne. **Page mission** : bandeau des risques de la mission.
- **Tests** : 10 nouveaux tests (145 au total).
- **Bug trouvé par les tests** : la page mission chargeait l'équipe sans son `organization_id`, ce qui faisait planter le calcul de charge. Corrigé, ainsi qu'un chargement similaire et fragile dans « My tasks ».

## Choix techniques

- **Explicable avant d'être « intelligent »** : chaque suggestion et chaque risque dit pourquoi. Un lead peut contester une règle ; il ne peut pas contester une boîte noire.
- **Domaine isolé** dans `app/Delegation` (`WorkloadCalculator`, `AssigneeSuggester`, `RiskDetector`), indépendant des contrôleurs et testé directement.
- **Seuils dans `config/delego.php`** : capacité, estimation par défaut, délais des règles.
- **Peu de requêtes** : charge calculée en une requête groupée pour toute une liste de personnes.

## Limites et suite

- Capacité identique pour tous : pas encore de temps partiel ni de congés.
- La date de blocage est approximée par la dernière modification de la tâche.
- **Prochaine étape** : des données de démonstration réalistes et la connexion en un clic, pour montrer tout ça sans configuration.
