<?php

namespace Backend\Modules\Spotlights\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionAdd as BackendBaseActionAdd;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Core\Language\Language as BL;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Meta as BackendMeta;
use Backend\Modules\Spotlights\Engine\Model as BackendSpotlightsModel;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This is the add-action, it will display a form to create a new spotlight
 *
 * @author Wouter Verstuyf <info@webflow.be>
 */
class Add extends BackendBaseActionAdd
{
    /**
     * @var array
     */
    protected $categories = array();

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
        // get categories
        $this->categories = BackendSpotlightsModel::getCategoriesForDropdown();

        if (empty($this->categories)) {
            $this->redirect(BackendModel::createURLForAction('AddCategory'));
        }
    }

    protected function loadForm(): void
    {
        // get values for the form
        $rbtHiddenValues = [
            ['label' => BL::lbl('Hidden'), 'value' => 1],
            ['label' => BL::lbl('Published'), 'value' => 0],
        ];
        $internalLinks = BackendSpotlightsModel::getInternalLinks();

        // create form
        $this->form = new BackendForm('add');

        $this->form->addText('title', null, null, 'form-control title', 'form-control danger title');
        $this->form->addEditor('text');
        $this->form->addText('link', null, null);
        $this->form->addDropdown('categories', $this->categories);
        $this->form->addImage('image');
        $this->form->addRadiobutton('hidden', $rbtHiddenValues, 0);
        $this->form->addText('link_title');
        $this->form->addCheckbox('external_link');
        $this->form->addText('external_url');
        $this->form->addDropdown('internal_url', $internalLinks, '',
            false,
            'chzn-select'
        )->setDefaultElement('');

        // meta
        $this->meta = new BackendMeta($this->form, null, 'title', true);

    }

    protected function parse(): void
    {
        parent::parse();

        // get url
        $url = BackendModel::getURLForBlock($this->url->getModule(), 'Detail');
        $url404 = BackendModel::getURL(404);
        if ($url404 != $url) {
            $this->tpl->assign('detailURL', SITE_URL . $url);
        }
        $this->record['url'] = $this->meta->getURL();

    }

    protected function validateForm(): void
    {
        if ($this->form->isSubmitted()) {
            $this->form->cleanupFields();

            // validation
            $fields = $this->form->getFields();
            $fields['title']->isFilled(BL::err('FieldIsRequired'));

            // validate external url
            if ($fields['external_link']->isChecked()) {
                if ($fields['external_url']->isFilled(BL::err('FieldIsRequired'))) {
                    if ($fields['external_url']->isURL(BL::err('InvalidURL')));
                }
            }

            // validate meta
            $this->meta->validate();

            if ($this->form->isCorrect()) {
                // build the item
                $item = [];
                $item['language'] = BL::getWorkingLanguage();
                $item['category_id'] = $fields['categories']->getValue();
                $item['title'] = $fields['title']->getValue();
                $item['text'] = $fields['text']->getValue();
                $item['hidden'] = $fields['hidden']->getValue();
                $item['sequence'] = BackendSpotlightsModel::getMaximumSequence() + 1;

                $item['meta_id'] = $this->meta->save();

                // the extra data
                $data = array('link' => null);

                // links
                if($fields['internal_url']->isFilled())
                {
                    $data['link'] = array(
                        'type' => 'internal',
                        'id' => $fields['internal_url']->getValue(),
                        'title' => $fields['link_title']->getValue()
                    );
                }

                // external links
                if($fields['external_link']->getChecked())
                {
                    $data['link'] = array(
                        'type' => 'external',
                        'url' => $fields['external_link']->getValue(),
                        'title' => $fields['link_title']->getValue()
                    );
                }

                $item['data'] = serialize($data);

                // the image path
                $imagePath = FRONTEND_FILES_PATH . '/Spotlights/images';

                // create folders if needed
                $filesystem = new Filesystem();
                $filesystem->mkdir(array($imagePath . '/source'));

                // image provided?
                if ($fields['image']->isFilled()) {
                    // build the image name
                    $item['image'] = $this->meta->getURL()
                        . '-' . BL::getWorkingLanguage()
                        . '-' . time()
                        . '.' . $fields['image']->getExtension();

                    // upload the image & generate thumbnails
                    $fields['image']->generateThumbnails($imagePath, $item['image']);
                }

                // save data
                $item['id'] = BackendSpotlightsModel::insert($item);

                $this->redirect(
                    BackendModel::createURLForAction('Index') . '&report=added&highlight=row-' . $item['id']
                );
            }
        }
    }
}
