<?php

namespace Frontend\Modules\Spotlights\Widgets;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Frontend\Core\Engine\Base\Widget as FrontendBaseWidget;
use Frontend\Modules\Spotlights\Engine\Model as FrontendSpotlightsModel;

/**
 * This is a widget with a list of the spotlights
 *
 * @author Wouter Verstuyf <wouter@webflow.be>
 */
class SpotlightsList extends FrontendBaseWidget
{
    public function execute(): void
    {
        parent::execute();

        $this->loadTemplate();
        $this->getData();
        $this->parse();
    }

    private function getData(): void
    {
        $this->items = FrontendSpotlightsModel::getAll();
    }

    private function parse(): void
    {
        $this->template->assign('widgetSpotlightsList', $this->items);
    }
}
