# LMS extension (Learning Management System)

A [YesWiki](https://yeswiki.net) extension turning YesWiki into a learning management
system.

!> Careful — this is a YesWiki extension. It is not part of the officially maintained
YesWiki core.

## Install

1. Copy the extension into your tools folder, or install it from the
   [`GererMisesAJour`](?GererMisesAJour ':ignore') page of your YesWiki.
2. Once the automatic install finishes without error, click "Finalise the update", or
   else type [`/update`](?GererMisesAJour/update ':ignore') at the end of a page URL.
   That completes the LMS module update.

_Example: `https://www.example.com/?GererMisesAJour/update`_

## Usage

1. Go to the [`BazaR`](?BazaR ':ignore') page of your YesWiki.
2. Add LMS activities by adding entries to form ID = 1201.
3. Then add LMS modules by adding entries to form ID = 1202.
4. Then add an LMS course by adding an entry to form ID = 1203.
5. Note that course's URL. You can list it on your course index.

## Import feature for system administrators

The extension can import courses from other wikis.

It is command line only, so you need SSH access to your server.

You need:

1. The URL of the wiki to import from.
2. An API token for that wiki.

An API token is created by adding these lines to `wakka.config.php`:

```php
  'api_allowed_keys' =>
  [
    'token-name' => 'token-to-keep-secret',
  ],
```

From the wiki root, run:
_(logged in as the right user, or prefixing the command, for instance with `sudo -u
www-data ` for the www-data user)_

```sh
php tools/lms/commands/console lms:import-courses REMOTE-URL TOKEN
```

The command then guides you interactively.

More advanced options exist, and their documentation is available with:

```sh
php tools/lms/commands/console lms:import-courses -h
```

### Importing videos to a peertube instance

Videos can be imported to a peertube instance, provided these settings are filled in
`wakka.config.php`:

```php
'peertube_url' => 'instance URL',
'peertube_user' => 'user',
'peertube_password' => 'that user\'s plain text password',
'peertube_channel' => 'republishing channel',
```

## Advanced comment configuration

The LMS extension can be used with comments. Several kinds are possible:

|**Type**|**Use**|
|:-|:-|
|_empty_|YesWiki comments|
|`yeswiki`|YesWiki comments|
|`discourse`|_name reserved for future use, not implemented yet, falls back to YesWiki comments_|
|`external_humhub`|[HumHub](https://www.humhub.com) in external mode|
|`embedded_humhub`|[HumHub](https://www.humhub.com) in embedded mode|

This is set through the `comments_handler` parameter on the
[`GererConfig`](?GererConfig ':ignore') page of your wiki, under `Access rights`.

### Configuring comments with [HumHub](https://www.humhub.com)

Using comments with [HumHub](https://www.humhub.com) requires a link to a dedicated
javascript library: <https://gitlab.com/cuzy/humhub-modules-external-websites>.

In `embedded_humhub` mode, the `fiche-1201.tpl.html` template has to be customised by
copying `tools/lms/templates/bazar/fiche-1201.tpl.html` into
`custom/templates/bazar/fiche-1201.tpl.html` and adding the javascript links to it.

That library's documentation is here:
<https://gitlab.com/cuzy/humhub-modules-external-websites/-/tree/master/docs#external-websites>.

?> TODO: add a sample snippet to customise in `fiche-1201.tpl.html`
