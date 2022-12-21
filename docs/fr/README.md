# Extension LMS (Learning Management System)

C'est une extension [YesWiki](https://yeswiki.net) qui permet d'utiliser YesWiki comme une plateforme d'apprentissage (LMS : Learning Management System).

!> Attention — Ceci est une extension de YesWiki. Elle ne fait pas partie du cœur officiellement maintenu de YesWiki.

## Installation

  1) Copiez l'extension dans votre dossier tools ou installez-la depuis la page [`GererMisesAJour`](?GererMisesAJour ':ignore') sur votre YesWiki.
  2) Une fois l'installation automatique terminée sans erreur, bien cliquer sur "Finaliser la mise à jour", ou sinon taper [`/update`](?GererMisesAJour/update ':ignore') à la fin de l'url d'une page. Ceci terminera la mise à jour du module LMS.

_Exemple : `https://www.example.com/?GererMisesAJour/update`_

## Utilisation

  1) Rendez-vous sur la page [`BazaR`](?BazaR ':ignore') de votre YesWiki
  2) Ajoutez des activités LMS en ajoutant des fiches au formulaire ID = 1201.
  3) Ajoutez ensuite des modules LMS en ajoutant des fiches au formulaire ID = 1202.
  4) Ajoutez ensuite un parcours LMS en ajoutant un fiche au formulaire ID = 1203.
  5) Notez l'url de ce parcours. Vous pouvez l'indiquer sur votre liste de parcours.

## Fonctionnalité d'import pour les administrateurices système

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

Depuis la racine du wiki, utiliser la commande suivante :
*(en étant identifié avec le bon utilisateur ou en prefixant les commandes avec par exemple pour le user www-data `sudo -u www-data `)*

```sh
php tools/lms/commands/console lms:import-courses URL-DISTANTE TOKEN
```

Vous serez ensuite guidé interactivement

Il existe d'autres options plus avancées, il est possible d'obtenir leur documentation

```sh
php tools/lms/commands/console lms:import-courses -h
```

### Importation des vidéos vers une instance peertube

Il est possible d'importer les vidéos vers une instance peertube, pour cela, il faut que les paramètres suivants soient renseignés dans le `wakka.config.php`

```php
'peertube_url' => 'URL de l\'instance',
'peertube_user' => 'Utilisateur',
'peertube_password' => 'Mot de passe en clair de l\'utilisateur',
'peertube_channel' => 'Chaine qui republie',
```

## Configuration avancée des commentaires

L'extension LMS peut être utilisée avec des commentaires. Plusieurs types de commentaires sont possibles :

|**Type**|**Usage**|
|:-|:-|
|_vide_|usage des commentaires de YesWiki|
|`yeswiki`|usage des commentaires de YesWiki|
|`discourse`|_nom réservé pour des applications futures, pas encore implémentées, => usage des commentaires de YesWiki_|
|`external_humhub`|utilisation du logiciel [HumHub](https://www.humhub.com) en mode externe|
|`embedded_humhub`|utilisation du logiciel [HumHub](https://www.humhub.com) en mode embarqué|

Cette configuration se fait en modifiant le paramètre `comments_handler` dans la page [`GererConfig`](?GererConfig ':ignore') de votre wiki, dans la rubrique `Droits d'accès`.

### Configuration des commentaires avec [HumHub](https://www.humhub.com)

Pour pouvoir utiliser les commentaires avec [HumHub](https://www.humhub.com), il est nécessaire d'ajouter un lien vers une librairie dédiée javascript : <https://gitlab.com/cuzy/humhub-modules-external-websites>.

Dans le cas de l'usage du mode `embedded_humhub`, une personnalisation du template `fiche-1201.tpl.html` est nécessaire en recopiant le fichier `tools/lms/templates/bazar/fiche-1201.tpl.html` dans le dossier `custom/templates/bazar/fiche-1201.tpl.html` et en y ajoutant les liens vers le javascript.

La documentation de cette librairie est accessible ici : <https://gitlab.com/cuzy/humhub-modules-external-websites/-/tree/master/docs#external-websites>.

?> TODO : ajouter un exemple de code à personnaliser dans `fiche-1201.tpl.html`