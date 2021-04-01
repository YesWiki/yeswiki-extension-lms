# yeswiki-extension-lms
Permet d'utiliser YesWiki comme une plateforme d'apprentissage (LMS : Learning Management System)

> Attention — Ceci est une extension de YesWiki. Elle ne fait pas partie du cœur officiellement maintenu de YesWiki.

## Installation

  1) Copiez l'extension dans votre dossier tools ou installez-la depuis la page `GererMisesAJour` sur votre YesWiki.
  2) Une fois l'installation automatique terminée sans erreur, taper `/update` à la fin de l'url d'une page. Ceci terminera la mise à jour du module LMS.

_Exemple : `https://www.example.com/?GererMisesAJour/update`_

## Utilisation

  1) Rendez-vous sur la page `Bazar` de votre YesWiki
  2) Ajoutez des activités LMS en ajoutant des fiches au formulaire ID = 1201.
  3) Ajoutez ensuite des modules LMS en ajoutant des fiches au formulaire ID = 1202.
  4) Ajoutez ensuite un parcours LMS en ajoutant un fiche au formulaire ID = 1203.
  5) Notez l'url de ce parcours. Vous pouvez l'indiquer sur votre liste de parcours.
  
_Documentation sur le site [https://yeswiki.net](https://yeswiki.net/?DocumentationExtensionLMS)_

## Fonctionnalité d'import

L'extension permet l'import de parcours depuis d'autres wikis.

Elle s'utilise en ligne de commande uniquement, il vous faut donc un accès en SSH à votre serveur.

Pour l'utiliser, il vous faut:

  1) L'URL vers le wiki depuis lequel importer
  2) Un token d'API de ce wiki

Il est possible de créer un token d'API pour un wiki simplement en ajoutant les lignes suivantes au `wakka.config.php`

```php
  'api_allowed_keys' =>
  [
    'nom-du-token' => 'token-a-garder-secret',
  ],
```

Depuis la racine du wiki, utiliser la commande suivante:
```sh
tools/lms/commands/console lms:import-courses URL-DISTANTE TOKEN
```
Vous serez ensuite guidé interactivement

Il existe d'autres options plus avancées, il est possible d'obtenir leur documentation
```sh
tools/lms/commands/console lms:import-courses -h
```

Il est possible d'importer les vidéos vers une instance peertube, pour cela, il faut que les paramètres suivants soient renseignés dans le `wakka.config.php`
```php
'peertube_url' => 'URL de l\'instance',
'peertube_user' => 'Utilisateur',
'peertube_password' => 'Mot de passe en clair de l\'utilisateur',
'peertube_channel' => 'Chaine qui republie',
```
