# CR 11 — Refonte du design « Encre & signal »

**Branche** : `feature/design` · **Période** : 6 → 27 septembre 2025

## Objectif

Donner à Delego une identité visuelle propre, crédible et mémorable, loin du starter kit et des interfaces « générées » (dégradés violets, police Inter, ombres douces partout, cartes identiques).

## Démarche

1. **Recherche** : références de design actuelles (le skill *frontend-design* d'Anthropic, les listes de « marqueurs d'interface IA » à éviter, les tendances SaaS 2025-2026 orientées *calm design*).
2. **Trois directions** proposées : « Encre & signal » (éditoriale), « Atelier » (douce, arrondie), « Salle de contrôle » (sombre, dense). **Choix : Encre & signal**, rare parmi les outils de gestion de projet.
3. **Vérification visuelle** de chaque page par captures automatisées (clair, sombre, mobile 390 px), et contrôle des erreurs console.

## Ce qui a été fait

- **Migration Tailwind CSS 4** : configuration en CSS, plugin Vite officiel, `tw-animate-css`, `tailwind-merge` 3, classes renommées migrées par l'outil officiel.
- **Design system** (tokens dans `resources/css/app.css`) :
  - fond papier chaud `#FAF8F5`, texte encre `#1C1A17`, filets `#E7E2DA` ;
  - **un seul accent**, vermillon `#E5502B`, réservé à la marque et à ce qui demande de l'attention (retards, blocages, non-lus) ;
  - couleurs sémantiques discrètes : succès, info, avertissement ;
  - **aucune ombre** : la hiérarchie passe par les filets et la typographie ;
  - thème sombre « charbon chaud » complet.
- **Typographie** auto-hébergée (aucun CDN externe) : *Instrument Serif* pour les titres, *Instrument Sans* pour l'interface, *JetBrains Mono* pour les chiffres et les dates.
- **Identité** : logo « deux relais en passage de témoin » (encre et vermillon), favicon SVG, méta-description.
- **Pages** :
  - **accueil** : promesse « Missions flow down. *Progress flows up.* », chaîne de délégation en 3 temps, aperçu construit avec les vrais composants (risques, charge), appel vers la démo ;
  - **authentification** en deux colonnes, panneau encre portant la promesse ;
  - **tableau de bord** éditorial : date, salutation en serif, indicateurs en chiffres mono séparés par des filets ;
  - titres en serif, libellés en petites capitales, statuts en pastilles discrètes, dates courtes (« 24 Nov ») ;
  - fil d'activité replié au-delà de 8 éléments, et ordre corrigé pour les événements de la même seconde.

## Limites et suite

- Les listes déroulantes natives restent utilisées pour changer de statut et d'assigné ; un kanban en glisser-déposer viendrait les remplacer.
- Les pages de paramètres et d'équipe gardent une mise en page simple.
- **Prochaine étape** : README vitrine.
