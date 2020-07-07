<?php
/**
 * Specific action for Les Jardiniers du Nous
 * Write a presentation box for an exercice
 * TODO Make more generic
 *
 *  The action must have 5 attributes : 'titre', 'duree', 'materiel', 'type', 'intention'
 *
 * @category YesWiki
 * @category JdN
 * @author   Adrien Cheype <adrien.cheype@gmail.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

// get the parameters
$titre = $this->GetParameter('titre');
$duree = $this->GetParameter('duree');
$materiel = $this->GetParameter('materiel');
$type = $this->GetParameter('type');
$intention = $this->GetParameter('intention');

$box = '<div class="cartoucheexo">' .
    $this->Format('=====' . $titre . '=====
{{grid}}
{{col size="1"}}
""<img src="https://gp.jardiniersdunous.org/files/pictoduree_vignette.png">""
{{end elem="col"}}
{{col size="3"}}
' . $duree . ' 
{{end elem="col"}}
{{col size="1"}}
""<img src="https://gp.jardiniersdunous.org/files/pictomatos_vignette.png">""
{{end elem="col"}}
{{col size="7"}}
' . $materiel . '
{{end elem="col"}}
{{end elem="grid"}}
{{grid}}
{{col size="1"}}
""<img src="https://gp.jardiniersdunous.org/files/pictotypeexo_vignette.png">""
{{end elem="col"}}
{{col size="3"}}
' . $type . '
{{end elem="col"}}
{{col size="1"}}
""<img src="https://gp.jardiniersdunous.org/files/pictointention_vignette.png">""
{{end elem="col"}}
{{col size="7"}}
' . $intention . '
{{end elem="col"}}
{{end elem="grid"}}')
    . '</div>';
echo $box;
