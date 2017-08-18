<?php

namespace Frontend\Modules\Spotlights\Widgets;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Frontend\Core\Engine\Base\Widget as FrontendBaseWidget;
use Frontend\Core\Engine\Theme as FrontendTheme;
use Frontend\Modules\Spotlights\Engine\Model as FrontendSpotlightsModel;

/**
 * This is a widget with a list of the spotlights for a category
 *
 * @author Wouter Verstuyf <wouter@webflow.be>
 */
class SpotlightsCategory extends FrontendBaseWidget
{
    public function execute(): void
    {
        parent::execute();

        $this->getData();
        $template = $this->assignTemplate();
        $this->loadTemplate($template);
        $this->parse();
    }

    private function assignTemplate(): string
    {
        $template = FrontendTheme::getPath(FRONTEND_MODULES_PATH . '/Spotlights/Layout/Widgets/TemplateDefault.html.twig');

        if (!empty($this->category) && !empty($this->category['template'])) {
            try {
                $template = FrontendTheme::getPath(
                    FRONTEND_MODULES_PATH . '/Spotlights/Layout/Widgets/' . $this->category['template']
                );
            } catch (FrontendException $e) {
                // do nothing
            }
        }

        return $template;
    }

    private function getData(): void
    {
        $this->category = FrontendSpotlightsModel::getCategory($this->data['category_id']);
        $this->items = FrontendSpotlightsModel::getAllForCategory($this->data['category_id']);
    }

    private function parse(): void
    {
        $this->template->assign('widgetSpotlightsCategory', $this->category);
        $this->template->assign('widgetSpotlightsList', $this->items);
    }
}
