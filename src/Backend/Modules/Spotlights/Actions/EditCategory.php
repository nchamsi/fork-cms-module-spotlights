<?php

namespace Backend\Modules\Spotlights\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionEdit as BackendBaseActionEdit;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Core\Engine\Meta as BackendMeta;
use Backend\Core\Language\Language as BL;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Form\Type\DeleteType;
use Backend\Modules\Spotlights\Engine\Model as BackendSpotlightsModel;

/**
 * This is the edit-action, it will display a form to edit an existing category.
 *
 * @author Wouter Verstuyf <wouter@webflow.be>
 */
class EditCategory extends BackendBaseActionEdit
{
    protected $isGod = false;

    public function execute(): void
    {
        $this->id = $this->getRequest()->query->getInt('id');

        // does the item exists?
        if ($this->id !== null && BackendSpotlightsModel::existsCategory($this->id)) {
            parent::execute();

            $this->getData();
            $this->loadForm();
            $this->validateForm();
            $this->loadDeleteForm();

            $this->parse();
            $this->display();
        } else {
            $this->redirect(BackendModel::createURLForAction('Categories') . '&error=non-existing');
        }
    }

    private function getData(): void
    {
        $this->record = BackendSpotlightsModel::getCategory($this->id);

        $this->isGod = BackendAuthentication::getUser()->isGod();
    }

    private function loadForm(): void
    {
        $templates = BackendSpotlightsModel::getTemplates();

        // create form
        $this->form = new BackendForm('edit_category');
        $this->form->addText('title', $this->record['title'], null, 'form-control title', 'form-control danger title');
        $this->form->addDropdown('template', $templates, $this->record['template']);

        $this->meta = new BackendMeta($this->form, $this->record['meta_id'], 'title', true);
    }

    protected function parse(): void
    {
        parent::parse();

        // assign the data
        $this->template->assign('isGod', $this->isGod);
        $this->template->assign('item', $this->record);

        // delete allowed?
        $this->template->assign(
            'deleteCategoryAllowed',
            BackendSpotlightsModel::deleteCategoryAllowed($this->id)
        );
    }


    /**
     * Validate the form
     *
     * @return  void
     */
    private function validateForm(): void
    {
        // is the form submitted?
        if ($this->form->isSubmitted()) {
            $this->meta->setURLCallback(
                'Backend\Modules\Spotlights\Engine\Model',
                'getURLForCategory',
                array($this->record['id'])
            );

            $this->form->cleanupFields();

            $fields = $this->form->getFields();

            // validate fields
            $this->form->getField('title')->isFilled(BL::err('TitleIsRequired'));
            $this->meta->validate();

            if ($this->form->isCorrect()) {
                // build item
                $item = [];
                $item['id'] = $this->id;
                $item['language'] = BL::getWorkingLanguage();
                $item['meta_id'] = $this->meta->save(true);
                $item['extra_id'] = $this->record['extra_id'];
                $item['title'] = $fields['title']->getValue();

                if($this->isGod) {
                    $item['template'] = $fields['template']->getValue();
                }

                // update the item
                BackendSpotlightsModel::updateCategory($item);

                // everything is saved, so redirect to the overview
                $this->redirect(
                    BackendModel::createURLForAction('Categories') . '&report=edited-category&var=' .
                    rawurlencode($item['title']) . '&highlight=' . $item['id']
                );
            }
        }
    }

    private function loadDeleteForm(): void
    {
        $deleteForm = $this->createForm(
            DeleteType::class,
            ['id' => $this->record['id']],
            ['module' => $this->getModule(), 'action' => 'DeleteCategory']
        );
        $this->template->assign('deleteForm', $deleteForm->createView());
    }
}
