<?php

namespace Backend\Modules\Spotlights\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Symfony\Component\Filesystem\Filesystem;
use Backend\Core\Engine\Base\ActionDelete as BackendBaseActionDelete;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Form\Type\DeleteType;
use Backend\Modules\Spotlights\Engine\Model as BackendSpotlightsModel;

/**
 * This is the delete-action, it deletes an item
 *
 * @author Wouter Verstuyf <info@webflow.be>
 */
class Delete extends BackendBaseActionDelete
{
    public function execute(): void
    {
        $deleteForm = $this->createForm(
            DeleteType::class,
            null,
            ['module' => $this->getModule(), 'action' => 'Delete']
        );
        $deleteForm->handleRequest($this->getRequest());
        if (!$deleteForm->isSubmitted() || !$deleteForm->isValid()) {
            $this->redirect(BackendModel::createUrlForAction(
                'Index',
                null,
                null,
                ['error' => 'something-went-wrong']
            ));

            return;
        }
        $deleteFormData = $deleteForm->getData();

        $this->id = $deleteFormData['id'];

        // does the item exist
        if ($this->id === 0 || !BackendSpotlightsModel::exists($this->id)) {
            $this->redirect(BackendModel::createUrlForAction('Index', null, null, ['error' => 'non-existing']));

            return;
        }

        $this->record = (array) BackendSpotlightsModel::get($this->id);

        parent::execute();

        // delete the image(s)
        if (!empty($this->record['image'])) {
            $imagePath = FRONTEND_FILES_PATH . '/Spotlights/images';
            $filesystem = new Filesystem();
            $folders = BackendModel::getThumbnailFolders($imagePath, true);

            // loop through the folders and remove the file
            foreach($folders as $folder)
            {
                $filesystem->remove($imagePath . '/' . $folder['dirname'] . '/' . $this->record['image']);
            }
        }

        // delete the record
        BackendSpotlightsModel::delete($this->id);

        // redirect
        $this->redirect(BackendModel::createUrlForAction(
            'Index',
            null,
            null,
            ['report' => 'deleted-category', 'var' => $this->record['title']]
        ));
    }
}
