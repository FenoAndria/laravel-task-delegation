# CR 02 — Organisations (multi-tenant)

**Branche** : `feature/organizations` · **Période** : 15 → 23 mars 2025

## Objectif

Faire de Delego un SaaS multi-organisation : chaque organisation regroupe ses équipes, ses missions et ses membres, isolés des autres organisations.

## Ce qui a été fait

- **Modèle de données** : `organizations`, table pivot `organization_user` (rôle par membre, unicité organisation/utilisateur garantie en base) et `users.current_organization_id`.
- **Rôles par organisation** (enum `OrganizationRole`) : *owner*, *admin*, *member*. Owners et admins pilotent l'organisation (équivalent de l'ancien « Admin » global de la v1). Le rôle de lead sera porté par les équipes.
- **Onboarding** : un nouvel utilisateur crée sa première organisation avant d'accéder à l'application ; il en devient owner.
- **Sélecteur d'organisation** dans la barre latérale : passage d'une organisation à l'autre, création d'une nouvelle.
- **Paramètres de l'organisation** : renommage, réservé aux owners et admins.
- **Gestion des membres** : liste, changement de rôle, retrait, et possibilité de quitter une organisation.
- **Règles métier** :
  - seul un owner peut accorder ou retirer le rôle d'owner ;
  - un admin ne peut pas retirer un owner ;
  - une organisation garde toujours au moins un owner (ni rétrogradation ni départ du dernier owner) ;
  - si l'organisation courante n'est plus accessible (membre retiré), l'utilisateur bascule automatiquement sur une autre de ses organisations, sinon vers l'onboarding.
- **Tests** : 26 nouveaux tests Pest (53 au total), dont l'isolation : impossible de basculer vers, modifier ou gérer les membres d'une organisation dont on ne fait pas partie (403/404).

## Choix techniques

- **Actions** (`CreateOrganization`, `ChangeMemberRole`, `RemoveMember`) plutôt que des services fourre-tout : une classe par cas d'usage, chacune dans une transaction.
- **Organisation courante stockée sur l'utilisateur** (et non en session) : elle est conservée d'un appareil et d'une connexion à l'autre.
- **Middleware `organization`** qui garantit une organisation valide avant toute page métier, et contrôleurs qui ne lisent que l'organisation courante : aucune route n'accepte d'identifiant d'organisation arbitraire, sauf le sélecteur, protégé par une policy.
- **Contexte partagé avec Inertia** (`auth.organization`, `auth.organizations`) en props paresseuses, typées côté TypeScript.

## Limites et suite

- On ne peut pas encore ajouter de membres : ce sera l'objet des **invitations par e-mail** (fonctionnalité suivante).
- La suppression d'une organisation n'est pas encore proposée.
- L'interface reprend les composants du starter kit ; la refonte visuelle viendra dans une fonctionnalité dédiée.
- **Prochaine étape** : invitations par e-mail, puis équipes (lead et membres).
