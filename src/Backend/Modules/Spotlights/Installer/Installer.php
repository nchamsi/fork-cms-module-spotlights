<?php

namespace Backend\Modules\Spotlights\Installer;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Installer\ModuleInstaller;
use Common\ModuleExtraType;

/**
 * Installer for the Spotlights module
 *
 * @author Wouter Verstuyf <info@webflow.be>
 */
class Installer extends ModuleInstaller
{
    public function install(): void
    {
        $this->addModule('Spotlights');
        $this->importSQL(__DIR__ . '/Data/install.sql');
        $this->importLocale(__DIR__ . '/Data/locale.xml');
        $this->configureBackendNavigation();
        $this->configureBackendRights();
        $this->configureFrontendExtras();
    }

    private function configureBackendNavigation(): void
    {
        // Set navigation for "Modules"
        $navigationModulesId = $this->setNavigation(null, 'Modules');
        $navigationSpotlightsId = $this->setNavigation($navigationModulesId, $this->getModule());
        $this->setNavigation(
            $navigationSpotlightsId,
            'Spotlights',
            'spotlights/index',
            ['spotlights/add', 'spotlights/edit']
        );
        $this->setNavigation(
            $navigationSpotlightsId,
            'Categories',
            'spotlights/categories',
            ['spotlights/add_category', 'spotlights/edit_category']
        );
    }

    private function configureBackendRights(): void
    {
        $this->setModuleRights(1, $this->getModule());

        // Configure backend rights for entities
        $this->setActionRights(1, $this->getModule(), 'Add');
        $this->setActionRights(1, $this->getModule(), 'AddCategory');
        $this->setActionRights(1, $this->getModule(), 'Categories');
        $this->setActionRights(1, $this->getModule(), 'Edit');
        $this->setActionRights(1, $this->getModule(), 'EditCategory');
        $this->setActionRights(1, $this->getModule(), 'Delete');
        $this->setActionRights(1, $this->getModule(), 'DeleteCategory');
        $this->setActionRights(1, $this->getModule(), 'Index');
        $this->setActionRights(1, $this->getModule(), 'Sequence');
        $this->setActionRights(1, $this->getModule(), 'SequenceCategories');
    }

    private function configureFrontendExtras(): void
    {
        $this->insertExtra($this->getModule(), ModuleExtraType::widget(), 'SpotlightsList', 'SpotlightsList');
    }
}
