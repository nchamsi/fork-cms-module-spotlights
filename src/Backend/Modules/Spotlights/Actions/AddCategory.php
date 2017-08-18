<?php

namespace Backend\Modules\Spotlights\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionAdd as BackendBaseActionAdd;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Core\Language\Language as BL;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Meta as BackendMeta;
use Backend\Modules\Spotlights\Engine\Model as BackendSpotlightsModel;

/**
 * This is the add-action, it will display a form to create a new category
 *
 * @author Wouter Verstuyf <wouter@webflow.be>
 */
class AddCategory extends BackendBaseActionAdd
{
    protected $isGod = false;

    public function execute(): void
    {
        parent::execute();

        $this->getData();
        $this->loadForm();
        $this->validateForm();

        $this->parse();
        $this->display();
    }

    private function getData(): void
    {
        $this->isGod = BackendAuthentication::getUser()->isGod();
    }

    private function loadForm(): void
    {
        $templates = BackendSpotlightsModel::getTemplates();

        // create form
        $this->form = new BackendForm('add_category');
        $this->form->addText('title', null, null, 'form-control title', 'form-control danger title');
        $this->form->addDropdown('template', $templates);

        $this->meta = new BackendMeta($this->form, null, 'title', true);
    }

    protected function parse(): void
    {
        parent::parse();

        $this->template->assign('isGod', $this->isGod);
    }

    private function validateForm(): void
    {
        if ($this->form->isSubmitted()) {
            $this->meta->setURLCallback('Backend\Modules\Spotlights\Engine\Model', 'getURLForCategory');

            // cleanup the submitted fields, ignore fields that were added by hackers
            $this->form->cleanupFields();

            // validate fields
            $this->form->getField('title')->isFilled(BL::err('TitleIsRequired'));

            // validate meta
            $this->meta->validate();

            // no errors?
            if ($this->form->isCorrect()) {
                $fields = $this->form->getFields();

                // build item
                $item = [];
                $item['title'] = $fields['title']->getValue();
                $item['language'] = BL::getWorkingLanguage();
                $item['meta_id'] = $this->meta->save();
                $item['sequence'] = BackendSpotlightsModel::getMaximumCategorySequence() + 1;

                // save the data
                $item['id'] = BackendSpotlightsModel::insertCategory($item);

                // everything is saved, so redirect to the overview
                $this->redirect(
                    BackendModel::createURLForAction('Categories') .
                    '&report=added-category&var=' .
                    urlencode($item['title']) .
                    '&highlight=' .
                    $item['id']
                );
            }
        }
    }
}
