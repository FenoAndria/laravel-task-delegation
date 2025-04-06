# CR 03 — Invitations par e-mail

**Branche** : `feature/invitations` · **Période** : 29 mars → 6 avril 2025

## Objectif

Permettre de faire entrer des personnes dans une organisation : jusqu'ici, une organisation ne comptait que son créateur.

## Ce qui a été fait

- **Inviter** : depuis la page Membres, un owner ou un admin saisit une adresse e-mail et un rôle. La personne reçoit un e-mail avec un lien.
- **Cycle de vie** : une invitation est *en attente*, *acceptée*, *refusée* ou *annulée* ; elle expire au bout de 7 jours. Les invitations en attente apparaissent sous la liste des membres, avec **renvoyer** (nouvelle date d'expiration) et **annuler**.
- **Répondre** : le lien ouvre une page publique.
  - Visiteur non connecté : il se connecte ou crée un compte (e-mail prérempli), puis revient automatiquement sur l'invitation.
  - Utilisateur connecté avec la bonne adresse : il accepte (il rejoint l'organisation avec le rôle prévu et y bascule) ou refuse.
  - Utilisateur connecté avec une autre adresse : il ne peut pas répondre.
- **Onboarding** : un nouvel utilisateur voit les invitations qui l'attendent et peut les rejoindre au lieu de créer sa propre organisation.
- **Règles** :
  - seuls owners et admins invitent ; seul un owner peut inviter un owner ;
  - impossible d'inviter un membre existant, ou une personne qui a déjà une invitation en attente (une invitation expirée ne bloque pas) ;
  - une invitation expirée, annulée ou déjà traitée ne peut plus être acceptée ;
  - les invitations d'une autre organisation sont introuvables (404).
- **Tests** : 20 nouveaux tests (73 au total), dont le rendu réel de l'e-mail.

## Choix techniques

- **Jeton aléatoire de 64 caractères** dans l'URL, unique en base et jamais exposé dans les réponses JSON (attribut caché).
- **Vérification de l'adresse** au moment d'accepter : un lien transféré ne permet pas de rejoindre l'organisation avec un autre compte.
- **E-mail en file d'attente** (`ShouldQueue`) : l'envoi ne ralentit pas la requête. En local, `MAIL_MAILER=log` écrit les e-mails dans les logs.
- **Retour après connexion** via l'URL « intended » de Laravel ; l'inscription du starter kit a été adaptée pour la respecter.
- **Error bag dédié** (`invite`) côté Inertia, pour que les erreurs du formulaire d'invitation ne se mélangent pas à celles de la liste des membres.

## Limites et suite

- Pas encore de limite de débit sur l'envoi d'invitations.
- L'utilisateur n'est pas notifié dans l'application (notifications prévues plus tard).
- **Prochaine étape** : les équipes (un lead, des membres) au sein d'une organisation.
