# CR 12 — Assistant IA de découpage (optionnel)

**Branche** : `feature/ai-breakdown` · **Période** : 4 → 19 octobre 2025

## Objectif

Aider le lead au moment le plus coûteux de la délégation, **transformer une mission en tâches**, sans rendre Delego dépendant d'une IA payante.

## Ce qui a été fait

- **Bouton « Draft tasks with the assistant »** sur la page mission, visible seulement pour le lead (ou un owner/admin), sur une mission active, et si l'IA est configurée.
- **Proposition à valider, jamais créée d'office** : l'assistant propose 3 à 8 tâches estimées ; le lead modifie les titres et les heures, décoche ce qu'il ne veut pas, puis ajoute. Chaque tâche ajoutée est journalisée dans l'activité.
- **Contexte envoyé** : titre, équipe, description, échéance et tâches existantes (pour éviter les doublons). Aucune donnée personnelle.
- **Robustesse** :
  - réponse attendue en JSON, mais extraite même si le modèle l'entoure de texte ou de balises de code ;
  - titres vides et doublons écartés, estimations bornées entre 1 et 40 h, 8 tâches maximum ;
  - panne, délai dépassé (30 s par défaut) ou réponse inexploitable → message clair, et l'ajout manuel reste disponible.
- **Quota** par organisation (10 demandes par heure par défaut) pour maîtriser l'usage d'une offre gratuite sur la démo publique.
- **Tests** : 12 nouveaux tests (164 au total), avec l'API simulée : aucun appel réseau dans la suite de tests.

## Choix techniques

- **Toute API compatible OpenAI** (`/chat/completions`) : changer de fournisseur ne demande que trois variables (`DELEGO_AI_BASE_URL`, `DELEGO_AI_API_KEY`, `DELEGO_AI_MODEL`). Par défaut, l'URL pointe vers OpenRouter, qui propose des modèles gratuits.
- **Aucune dépendance ajoutée** : le client HTTP de Laravel suffit, ce qui garde la couche IA lisible et facile à remplacer.
- **Ollama écarté** : trop lent sur une machine modeste (8 Go de RAM, 2 cœurs) et impossible à héberger gratuitement pour la démo.
- **Free-first** : sans clé, rien ne change ; toutes les autres aides (charge, suggestions, risques) restent des règles déterministes.

## Mise en route

1. Créer une clé gratuite chez un fournisseur compatible OpenAI.
2. Choisir un modèle gratuit (sur OpenRouter, ceux suffixés `:free`).
3. Renseigner `DELEGO_AI_API_KEY` et `DELEGO_AI_MODEL` dans `.env`.

## Limites et suite

- Les tâches proposées ne sont pas assignées : l'assignation reste au lead, aidé par les suggestions de charge.
- Pas encore de génération de résumé de mission ni de rapport hebdomadaire.
- **Prochaine étape** : README vitrine et mise en ligne.

## Mise à jour — modèles de secours (25 octobre 2025)

Premier essai avec le vrai fournisseur : les modèles gratuits les plus demandés répondent souvent **429 (saturé)**, alors que d'autres modèles gratuits restent disponibles et donnent de bons découpages en 3 à 10 secondes.

- `DELEGO_AI_MODEL` accepte désormais **une liste de modèles séparés par des virgules** ; en cas de saturation (429) ou d'erreur du fournisseur (5xx), le client passe au suivant.
- Une clé refusée (401) ou une requête invalide ne déclenche pas de bascule : le problème serait le même avec tous les modèles.
- 2 nouveaux tests (166 au total). Vérifié en conditions réelles : premier modèle saturé, bascule automatique, proposition obtenue en 8 secondes.
