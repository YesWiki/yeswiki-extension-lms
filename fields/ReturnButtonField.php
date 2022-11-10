<?php

namespace YesWiki\Lms\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Lms\Service\CourseManager;

/**
 * @Field({"boutonretour","returnbutton"})
 */
class ReturnButtonField extends LmsField
{
    /**
     * Display the 'Return' button which permit to come back to the calling page (history back). The button is displayed only
     * in 'view' mode and if the entry is not opened from a modal.
     * Must be declare in the bazar form definition as followed :
     *    'boutonretour*** *** *** *** *** *** *** *** *** ***'
     */


    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->courseManager = $services->get(CourseManager::class);

        // does the entry is viewed inside a modal box ? $moduleModal is true when the page was called in ajax
        $this->moduleModal = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    // Render the show view of the field
    protected function renderStatic($entry)
    {
        $currentModuleTag = $this->getCurrentTag($entry);
        if (is_null($currentModuleTag)) {
            return "";
        }
        // display the button if it's not inside a modal box
        return ($this->moduleModal) ? null : $this->render("@lms/fields/returnbutton.twig", [
            ]);
    }
}
