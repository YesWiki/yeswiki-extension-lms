<?php
/**
 * LMS Module class
 */
namespace YesWiki;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class LmsModule
{
    protected $wiki = ''; // give access to the main wiki object
    protected $fields = []; // available field for a module
    protected $status = []; // can be open | to_be_open | closed | not_accessible | unknown
    protected $activities = []; // data about related activities for this module
    protected $duration = []; // time in hour necessary for completing the module

    /**
     * Module constructor
     *
     * @param string $idModule : the id of the module
     * @param object $wiki : the main wiki object
     */
    public function __construct($idModule, $wiki)
    {
        $this->wiki = $wiki;
        $this->fields = $this->loadFields($idModule);
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
        if (empty($this->fields)) {
            $this->fields = $GLOBALS['bazarFiche']->getOne($idModule);
        }
        return $this->fields;
    }
    
    /**
     * Get the contextual parcours according to the Get parameter 'parcours' and the existing parcours. By order :
     *
     *   - if the Get parameter 'parcours' refers to a tag associated to a parcours entry, return it
     *   - else, if there is at least one parcours in the database, return them
     *   - if not, return false
     *
     * @return array The parcours entry
     */
    public function getRelatedParcours()
    {
      $parcoursTag = empty($_REQUEST['parcours']) ? '' : $_REQUEST['parcours'];
      $formId = $this->wiki->config['lms_config']['parcours_form_id'];
      if (!empty($parcoursTag)) {
          $parcoursEntry = $GLOBALS['bazarFiche']->getOne($parcoursTag);
          // TODO : test if module is present
          if ($parcoursEntry && $parcoursEntry['id_typeannonce'] == $formId)
              return [ $parcoursEntry['id_fiche'] => $parcoursEntry];
      } else {
          // TODO : test if module is present
          $entries = $GLOBALS['bazarFiche']->search(['formsIds' => [$formId]]);
          if (!empty($entries)) {
              return $entries;
          }
      }
      return false;
    }

    /**
     * loads all fields informations for the activities related to the module in a cache and returns it
     *
     * @return array activities of this module
     */
    public function getActivities()
    {
        // try to load activities if empty
        if (empty($this->activities)) {
            $idactivities = 'checkboxfiche'.$this->wiki->config['lms_config']['activite_form_id'];
            $activities = empty($this->fields[$idactivities]) ? 
            [] : 
            explode(',', $this->fields[$idactivities]); 
            foreach($activities as $act) {
                $this->activities[$act] = $GLOBALS['bazarFiche']->getOne($act);
            }
        }
        return $this->activities;
    }

    /**
     * calculate duration of the module, in hours, based on activities inside
     *
     * @return string duration in hours
     */
    public function getDuration() {
        // primitive caching system
        if (empty($this->duration)) {
            $time = 0;
            $activities = $this->getActivities();
            foreach ($activities as $act) {
                if (!empty(intval($act['bf_duree']))) {
                    $time = $time + intval($act['bf_duree']);
                }
            }
            $hours = floor($time / 60);
            $minutes = ($time % 60);
            $this->duration = sprintf('%dh%02d', $hours, $minutes);
        }
        return $this->duration;
    }

    /**
     * Check if given module is accessible and open for current user, for given parcours. Admins may navigate throught closed modules
     *
     * @param array $parcours : values of related parcours if available
     * @return string status of this module, in the related parcours
     */
    public function getModuleStatus($parcours = []) {
      // primitive caching system
      if (empty($this->status)) {
          if (empty($parcours)) {
            $this->status = 'unknown'; // if no parcours associated, we cannot check..
          } else {
            if ($this->fields['listeListeOuinonLmsbf_active'] == 'non') {
              $this->status = 'closed';
            } else {
              $d = empty($this->fields['bf_date_ouverture']) ? '' : Carbon::parse($this->fields['bf_date_ouverture']);
              if (!empty($d) && Carbon::now()->lte($d)) {
                $this->status = 'to_be_open';
              } else {
                if ($parcours["listeListeOuinonLmsbf_scenarisation_modules_libres"] == 'non') {
                  $this->status = 'open';
                } else {
                  $mods = explode(',', $parcours['checkboxfiche5002']);
                  // if it's the first module, it is open
                  if (!empty($mods[0]) && $mods[0] == $this->fields['id_fiche']) {
                    $this->status = 'open';
                  } else {
                    // todo : check user progress
                    $this->status = 'not_accessible';
                  }
                }
              }
            }
          }
      }
      return $this->status;
  }

  public function getNextActivity($user) {
    $activities = $this->getActivities();
    return array_key_first($activities);
  }

  /**
   * display given module information as a card
   *
   * @return string html of the module's card
   */
  public function displayCard()
  {
      $title = $this->fields['bf_titre'];
      $escapedTitle = htmlspecialchars($title);
      $description = empty($this->fields['bf_description']) ?
        '' :
        '<div class="description-module">'.$this->wiki->format($this->fields['bf_description']).'</div>';
      $largeur = '400';
      $hauteur = '300';
      $image = empty($this->fields['imagebf_image']) && !is_file('files/'.$this->fields['imagebf_image']) ?
        '' :
        redimensionner_image(
          'files/'.$this->fields['imagebf_image'],
          'cache/'.$largeur.'x'.$hauteur.'-'.$this->fields['imagebf_image'],
          $largeur,
          $hauteur,
          'crop'
        );
        // the consulted parcours entry
        $parcoursEntry = $this->getRelatedParcours();
        $idactivity = $this->getNextActivity($this->wiki->getUserName());
        if ($parcoursEntry['listeListeOuinonLmsbf_scenarisation_modules_libres'] == 'oui') {
          $link = $this->wiki->href(
            'saveprogress',
            $idactivity,
            'parcours='.$parcoursEntry['id_fiche'].'&module='.$this->fields['id_fiche']
          );
        } else {
          $link = $this->wiki->href(
            '',
            $idactivity,
            'parcours='.$parcoursEntry['id_fiche'].'&module='.$this->fields['id_fiche']
          );
        }
        $status = $this->getModuleStatus($parcoursEntry);
        $d = empty($this->fields['bf_date_ouverture']) ? '' : Carbon::parse($this->fields['bf_date_ouverture']);
        switch ($status) {
          case 'unknown':
              $active = _t('LMS_UNKNOWN_STATUS_MODULE');
              break;
          case 'closed':
              $active = _t('LMS_CLOSED_MODULE');
              break;
          case 'open':
              $active = _t('LMS_OPEN_MODULE');
              if (!empty($d)) {
                $active .= ' '._t('LMS_SINCE')
                    .' '.Carbon::now()->locale($GLOBALS['prefered_language'])->DiffForHumans($d, CarbonInterface::DIFF_ABSOLUTE)
                    .' ('.str_replace(' 00:00', '', $d->locale($GLOBALS['prefered_language'])->isoFormat('LLLL')).')';
              }
              break;
          case 'to_be_open':
              $active = _t('LMS_MODULE_WILL_OPEN').' '
              .Carbon::now()->locale($GLOBALS['prefered_language'])->DiffForHumans($d, CarbonInterface::DIFF_ABSOLUTE)
              .' ('.str_replace(' 00:00', '', $d->locale($GLOBALS['prefered_language'])->isoFormat('LLLL')).')';
              break;
          case 'not_accessible':
              $active = _t('LMS_MODULE_NOT_ACCESSIBLE');
              break;
        }
        $activities = $this->getActivities();
        $nbActivities = count($activities);
        $duration = $this->getDuration();
        $adminLinks = $this->wiki->UserIsAdmin() ?
          '<a href="'.$this->wiki->href('edit', $this->fields['id_fiche']).'" class="btn btn-default btn-xs"><i class="fa fa-pencil-alt"></i> '._t('BAZ_MODIFIER').'</a>
           <a href="'.$this->wiki->href('deletepage', $this->fields['id_fiche']).'" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></a>' :
            '';
        $labelActivity = _t('LMS_ACTIVITY').(($nbActivities > 1) ? 's' : '');
        $labelTime = _t('LMS_TIME');
        $classLink = !$this->wiki->UserIsAdmin() && in_array($status, ['closed', 'to_be_open', 'not_accessible', 'unknown']) ? 'disabled' : ' ';
        $labelStart = _t('LMS_BEGIN');
      echo <<<EOF
  <div class="module-card status-$status">
    <div class="module-image">
      <a class="launch-module $classLink" href="$link">
        <img src="$image" alt="Image du module $escapedTitle" />
      </a>
    </div>
    <div class="module-content">
      <h3 class="module-title"><a class="launch-module $classLink" href="$link">$title</a></h3>
      $description
      <small class="module-date"><em>$active.</em></small>
    </div>
    <div class="module-activities">
      <div class="activities-infos">
          <div class="activities-numbers"><strong><i class="fas fa-chalkboard-teacher fa-fw"></i> $labelActivity</strong> : $nbActivities</div>
          <div class="activities-duration"><strong><i class="fas fa-hourglass-half fa-fw"></i> $labelTime</strong> : {$duration}</div>
      </div>
      <div class="activities-action">
        <a href="$link" class="btn btn-primary btn-block launch-module $classLink"><i class="fas fa-play fa-fw"></i> $labelStart</a>
        $adminLinks
      </div>
    </div>
  </div>
EOF;
  }
}
