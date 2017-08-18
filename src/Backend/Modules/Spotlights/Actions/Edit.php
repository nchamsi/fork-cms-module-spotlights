<?php

namespace Backend\Modules\Spotlights\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Symfony\Component\Filesystem\Filesystem;
use Backend\Core\Engine\Base\ActionEdit as BackendBaseActionEdit;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Core\Engine\Meta as BackendMeta;
use Backend\Core\Language\Language as BL;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Form\Type\DeleteType;
use Backend\Modules\Spotlights\Engine\Model as BackendSpotlightsModel;

/**
 * This is the edit-action, it will display a form with the item data to edit
 *
 * @author Wouter Verstuyf <info@webflow.be>
 */
class Edit extends BackendBaseActionEdit
{
    public function execute(): void
    {
        $this->id = $this->getRequest()->query->getInt('id');

        // does the item exist?
        if ($this->id !== 0 && BackendSpotlightsModel::exists($this->id)) {
            parent::execute();

            $this->getData();
            $this->loadForm();
            $this->validateForm();
            $this->loadDeleteForm();

            $this->parse();
            $this->display();
        } else {
            $this->redirect(BackendModel::createUrlForAction('Index') . '&error=non-existing');
        }
    }

    private function getData(): void
    {
        $this->record = BackendSpotlightsModel::get($this->id);
        $this->record['data'] = unserialize($this->record['data']);
        $this->record['link'] = $this->record['data']['link'];
    }

    protected function loadForm(): void
    {
        // get values for the form
        $categories = BackendSpotlightsModel::getCategoriesForDropdown();
        $rbtHiddenValues = [
            ['label' => BL::lbl('Hidden'), 'value' => 1],
            ['label' => BL::lbl('Published'), 'value' => 0],
        ];
        $internalLinks = BackendSpotlightsModel::getInternalLinks();
        $internalLink = ($this->record['link']['type'] == 'internal') ? $this->record['link']['id'] : '';
        $externalLink = ($this->record['link']['type'] == 'external') ? $this->record['link']['url'] : '';

        // create form
        $this->form = new BackendForm('edit');
        $this->form->addText('title', $this->record['title'], null, 'form-control title', 'form-control danger title');
        $this->form->addEditor('text', $this->record['text']);
        $this->form->addRadioButton('hidden', $rbtHiddenValues, $this->record['hidden']);
        $this->form->addText('link_title', $this->record['link']['title']);
        $this->form->addCheckbox('external_link', ($this->record['link']['type'] == 'external'));
        $this->form->addText('external_url', $externalLink);
        $this->form->addDropdown('categories', $categories, $this->record['category_id']);
        $this->form->addDropdown('internal_url', $internalLinks, $internalLink,
            false,
            'chzn-select'
        )->setDefaultElement('');

        $this->form->addImage('image');
        $this->form->addCheckbox('delete_image');
        $this->form->addCheckbox("rotate_90");
        $this->form->addCheckbox("rotate_180");
        $this->form->addCheckbox("rotate_270");

        // meta
        $this->meta = new BackendMeta($this->form, $this->record['meta_id'], 'title', true);
    }

    protected function parse(): void
    {
        parent::parse();

        // get url
        $url = BackendModel::getURLForBlock($this->url->getModule(), 'Detail');
        $url404 = BackendModel::getURL(404);
        if ($url404 != $url) {
            $this->template->assign('detailURL', SITE_URL . $url);
        }

        $this->template->assign('item', $this->record);
    }

    protected function validateForm(): void
    {
        if ($this->form->isSubmitted()) {
            $this->meta->setUrlCallBack('Backend\Modules\Spotlights\Engine\Model', 'getUrl', [$this->record['id']]);

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
                $item = [];
                $item['id'] = $this->id;
                $item['language'] = BL::getWorkingLanguage();
                $item['category_id'] = $fields['categories']->getValue();
                $item['title'] = $fields['title']->getValue();
                $item['text'] = $fields['text']->getValue();
                $item['hidden'] = $fields['hidden']->getValue();
                $item['meta_id'] = $this->meta->save(true);

                // the extra data
                $data = ['link' => null];

                // link
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
                        'url' => $fields['external_url']->getValue(),
                        'title' => $fields['link_title']->getValue()
                    );
                }
                $item['data'] = serialize($data);

                // image
                $item['image'] = $this->record['image'];
                $imagePath = FRONTEND_FILES_PATH . '/Spotlights/images';
                $filesystem = new Filesystem();
                $filesystem->mkdir([$imagePath . '/source']);

                // delete the image
                if ($fields['delete_image']->isChecked()) {
                    // get the folders
                    $folders = BackendModel::getThumbnailFolders($imagePath, true);

                    // loop through the folders and remove the file
                    foreach($folders as $folder)
                    {
                        $filesystem->remove($imagePath . '/' . $folder['dirname'] . '/' . $item['image']);
                    }

                    // reset the db value
                    $item['image'] = null;
                }

                // new image given?
                if ($fields['image']->isFilled()) {
                    // delete old image
                    if(!empty($item['image'])) {
                        $folders = BackendModel::getThumbnailFolders($imagePath, true);
                        foreach($folders as $folder)
                        {
                            $filesystem->remove($imagePath . '/' . $folder['dirname'] . '/' . $item['image']);
                        }
                    }

                    // add new image
                    $item['image'] = $this->meta->getURL() .
                                        '-' . BL::getWorkingLanguage() .
                                        '-' . time() .
                                        '.' . $fields['image']->getExtension();

                    // upload the image & generate thumbnails
                    $fields['image']->generateThumbnails($imagePath, $item['image']);
                }

                // rotate image
                if($fields["rotate_90"]->isChecked())
                {
                    BackendSpotlightsModel::rotateImage($imagePath, $item['image'], 90);
                }
                if($fields["rotate_180"]->isChecked())
                {
                    BackendSpotlightsModel::rotateImage($imagePath, $item['image'], 180);
                }
                if($fields["rotate_270"]->isChecked())
                {
                    BackendSpotlightsModel::rotateImage($imagePath, $item['image'], 270);
                }

                // update the item
                BackendSpotlightsModel::update($item);
                $item['id'] = $this->id;

                $this->redirect(
                    BackendModel::createURLForAction('Index') . '&report=edited&highlight=row-' . $item['id']
                );
            }
        }
    }

    private function loadDeleteForm(): void
    {
        $deleteForm = $this->createForm(
            DeleteType::class,
            ['id' => $this->record['id']],
            ['module' => $this->getModule(), 'action' => 'Delete']
        );
        $this->template->assign('deleteForm', $deleteForm->createView());
    }
}
