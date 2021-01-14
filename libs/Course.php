<?php

namespace YesWiki\Lms;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Lms\CourseStructure;
use YesWiki\Wiki;

class Course extends CourseStructure
{
    // the next fiels are lazy loaded : don't use direct access to them, call the getters instead
    protected $modules; // modules of the course
    protected $duration; // estimated time to complete the module, it's an integer counting the number of minutes

    /**
     * Get the modules of the course
     *
     * @return Module[] the course modules
     */
    public function getModules()
    {
        // lazy loading
        if (is_null($this->modules)) {
            $modulesTagsId = 'checkboxfiche' . $this->config->get('lms_config')['module_form_id'] . 'bf_modules';
            $this->modules = empty($this->getField($modulesTagsId)) ?
                [] :
                array_map(
                    function ($moduleTag) {
                        return new Module($this->config, $this->entryManager, $moduleTag);
                    },
                    explode(',', $this->getField($modulesTagsId))
                );
        }
        return $this->modules;
    }

    /**
     * Check if the course has the module with the given tag
     * @param $moduleTag the module tag to search
     * @return bool true is found, else otherwise
     */
    public function hasModule($moduleTag): bool
    {
        foreach ($this->getModules() as $module) {
            if ($module->getTag() == $moduleTag) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the previous module of the module with the given tag
     * @param $moduleTag the tag which specified the module
     * @return Module|null return null if the module specified is not found or is the first one, otherwise return
     * the previous module in the course modules
     */
    public function getPreviousModule($moduleTag): ?Module
    {
        $foundIndex = false;
        foreach ($this->getModules() as $index => $module) {
            if ($module->getTag() == $moduleTag) {
                $foundIndex = $index;
            }
        }
        return ($foundIndex === false || $foundIndex === 0) ?
            null
            : $this->getModules()[$foundIndex - 1];
    }

    /**
     * Get the next module of the module with the given tag
     * @param $moduleTag the tag which specified the module
     * @return Module|null return null if the module specified is not found or is the last one, otherwise return
     * the next module in the course modules
     */
    public function getNextModule($moduleTag): ?Module
    {
        $foundIndex = false;
        foreach ($this->getModules() as $index => $module) {
            if ($module->getTag() == $moduleTag) {
                $foundIndex = $index;
            }
        }
        return ($foundIndex === false || $foundIndex === count($this->getModules()) -1) ?
            null
            : $this->getModules()[$foundIndex + 1];
    }

    /**
     * Get the tag of the course's first module
     * @return string|null return null if the module list is empty, otherwise the tag of the first module
     */
    public function getFirstModuleTag(): ?string
    {
        return !empty($this->getModules()) ?
            $this->getModules()[array_key_first($this->getModules())]->getTag()
            : null;
    }

    /**
     * Get the tag of the course's last module
     * @return string|null return null if the module list is empty, otherwise the tag of the last module
     */
    public function getLastModuleTag(): ?string
    {
        return !empty($this->getModules()) ?
            $this->getModules()[array_key_last($this->getModules())]->getTag()
            : null;
    }

    /**
     * Get the subset array of the modules from $fromModuleTag to $toModuleTag
     * @param string $fromModuleTag the tag for the first element of the resulting array
     * @param string $toModuleTag the tag of the last element of the resulting array
     * @return array the subset array. If $fromModuleTag is not found, the array will be empty and if $toModuleTag is not
     * found, the last element will be the last module
     */
    public function getModulesBetween(string $fromModuleTag, string $toModuleTag)
    {
        $subset = [];
        foreach ($this->getModules() as $module) {
            if ($module->getTag() == $fromModuleTag || !empty($subset)) {
                $subset[] = $module;
            }
            if ($module->getTag() == $toModuleTag) {
                return $subset;
            }
        }
        return $subset;
    }
    /**
     * Get the duration of a course by adding the duration of all its modules
     * @return int the duration in minutes
     */
    public function getDuration(): int
    {
        // lazy loading
        if (is_null($this->duration)) {
            $count = 0;
            foreach ($this->getModules() as $module) {
                $count = $count + $module->getDuration();
            }
            $this->duration = $count;
        }
        return $this->duration;
    }

    /**
     * Getter for 'bf_description' of the course entry
     * @return string the course description or null if not defined
     */
    public function getDescription(): ?string
    {
        return $this->getField('bf_description');
    }

    /**
     * Check if the included modules are scripted
     * @return boolean the answer or if no value defined, return false by default
     */
    public function isModuleScripted(): ?bool
    {
        return ($this->getField('listeListeOuinonLmsbf_scenarisation_modules') == 'oui');
    }

    /**
     * Check if the included activities are scripted
     * @return boolean the answer of this condition or if no value defined, return false by default
     */
    public function isActivityScripted(): ?bool
    {
        return ($this->getField('listeListeOuinonLmsbf_scenarisation_activites') == 'oui');
    }
}
