<?php

namespace YesWiki;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class LmsModule
{
    protected $wiki = ''; // give access to the main wiki object
    protected $fields = []; // available field for a module
    protected $status = []; // can be open | to_be_open | closed | not_accessible | unknown

    public function __construct($wiki)
    {
        $this->wiki = $wiki;
    }
    
    /**
     * loads all fields informations from given module in a cache and returns it
     *
     * @param string $idModule
     * @return array module fields
     */
    public function loadFields($idModule)
    {
        // primitive caching system
        if (empty($this->fields[$idModule])) {
            $this->fields[$idModule] = $GLOBALS['bazarFiche']->getOne($idModule);
        }
        return $this->fields[$idModule];
    }

    /**
     * loads all fields informations for the activities related to a given module in a cache and returns it
     *
     * @param string $idModule
     * @return array activities of this module
     */
    public function getActivities($idModule)
    {
        // primitive caching system
        if (empty($this->fields[$idModule]['activities'])) {
            $this->fields[$idModule]['activities'] = [];
            $module = $this->loadFields($idModule);
            $idactivities = 'checkboxfiche'.$GLOBALS['wiki']->config['lms_config']['activite_form_id'];
            $activities = empty($module[$idactivities]) ? 
            [] : 
            explode(',', $module[$idactivities]); 
            foreach($activities as $act) {
                $this->fields[$idModule]['activities'][$act] = $GLOBALS['bazarFiche']->getOne($act);
            }
        }
        return $this->fields[$idModule]['activities'];
    }

    /**
     * calculate duration of the module, in hours, based on activities inside
     *
     * @param string $idModule
     * @return string duration in hours
     */
    public function getDuration($idModule) {
        // primitive caching system
        if (empty($this->fields[$idModule]['total_duration'])) {
            $time = 0;
            $activities = $this->getActivities($idModule);
            foreach ($activities as $act) {
                if (!empty(intval($act['bf_duree']))) {
                    $time = $time + intval($act['bf_duree']);
                }
            }
            $hours = floor($time / 60);
            $minutes = ($time % 60);
            $this->fields[$idModule]['total_duration'] = sprintf('%dh%02d', $hours, $minutes);
        }
        return $this->fields[$idModule]['total_duration'];
    }

    /**
     * Check if given module is accessible and open for current user, for given parcours. Admins may navigate throught closed modules
     *
     * @param string $idModule
     * @param string $idParcours
     * @return string status of this module
     */
    public function getModuleStatus($idModule, $idParcours = '') {
      // primitive caching system
      if (empty($this->status)) {
          if (empty($idParcours)) {
            $this->status = 'unknown'; // if no parcours associated, we cannot check..
          } else {
            $module = $this->loadFields($idModule);
            if ($this->fields['listeListeOuinonLmsbf_active'] == 'non') {
              $this->status = 'closed';
            } else {
              /*  TODO : trouver informations du participant */
              if ($parcours == '') {

              }
            }
          }
      }
      return $this->status;
  }

  /**
   * display given module information as a card
   *
   * @param string $idModule
   * @return string html of the module's card
   */
  public function displayCard($idModule)
  {
      $module = $this->loadFields($idModule);
      $link = $this->wiki->href('', $idModule);
      $title = $module['bf_titre'];
      $escapedTitle = htmlspecialchars($title);
      $description = empty($module['bf_description']) ?
        '' :
        '<div class="description-module">'.$this->wiki->format($module['bf_description']).'</div>';
      $largeur = '400';
      $hauteur = '300';
      $image = empty($module['imagebf_image']) && !is_file('files/'.$module['imagebf_image']) ?
        '' :
        redimensionner_image(
          'files/'.$module['imagebf_image'],
          'cache/'.$largeur.'x'.$hauteur.'-'.$module['imagebf_image'],
          $largeur,
          $hauteur,
          'crop'
        );
        if ($module['listeListeOuinonLmsbf_active'] == 'oui') {
          if (empty($module['bf_date_ouverture'])) {
            $active =  _t('LMS_OPEN_MODULE');
          } else {
            $d = Carbon::parse($module['bf_date_ouverture']);
            if (Carbon::now()->lte($d)) {
              $active = _t('LMS_MODULE_WILL_OPEN').' '
                .Carbon::now()->locale($GLOBALS['prefered_language'])->DiffForHumans($d, CarbonInterface::DIFF_ABSOLUTE)
                .' ('.str_replace(' 00:00', '', $d->locale($GLOBALS['prefered_language'])->isoFormat('LLLL')).')';
            } else {
              $active = _t('LMS_OPEN_MODULE').' '._t('LMS_SINCE')
                .' '.Carbon::now()->locale($GLOBALS['prefered_language'])->DiffForHumans($d, CarbonInterface::DIFF_ABSOLUTE)
                .' ('.str_replace(' 00:00', '', $d->locale($GLOBALS['prefered_language'])->isoFormat('LLLL')).')';
            }
          }
        } else {
            $active = _t('LMS_CLOSED_MODULE');
        }
        $activities = $this->getActivities($idModule);
        $nbactivities = count($activities);
        $duration = $this->getDuration($idModule);
        $adminlinks = $GLOBALS['wiki']->UserIsAdmin() ?
          '<a href="'.$link.'/edit" class="btn btn-default btn-xs"><i class="fa fa-pencil-alt"></i> '._t('BAZ_MODIFIER').'</a>
            <a href="'.$link.'/deletepage" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></a>' :
            '';
      echo <<<EOF
  <div class="module-card">
    <div class="module-image">
      <a href="$link">
        <img src="$image" alt="Image du module $escapedTitle" />
      </a>
    </div>
    <div class="module-content">
      <h3 class="module-title"><a href="$link">$title</a></h3>
      $description
      <small class="module-date"><em>$active.</em></small>
    </div>
    <div class="module-activities">
      <div class="activities-infos">
          <div class="activities-numbers"><strong>Activités</strong> : $nbactivities</div>
          <div class="activities-duration"><strong>Temps estimé</strong> : {$duration}</div>
      </div>
      <div class="activities-action">
        <a href="$link" class="btn btn-primary btn-block">Commencer</a>
        $adminlinks
      </div>
    </div>
  </div>
EOF;
  }
}
