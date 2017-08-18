<?php

namespace Backend\Modules\Spotlights\Ajax;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\AjaxAction as BackendBaseAJAXAction;
use Backend\Modules\Spotlights\Engine\Model as BackendSpotlightsModel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reorder categories
 *
 * @author Wouter Verstuyf <info@webflow.be>
 */
class SequenceCategories extends BackendBaseAJAXAction
{
    public function execute(): void
    {
        parent::execute();

        // get parameters
        $newIdSequence = trim($this->getRequest()->request->get('new_id_sequence', ''));

        // list id
        $ids = (array) explode(',', rtrim($newIdSequence, ','));

        // loop id's and set new sequence
        foreach ($ids as $i => $id) {
            // define category
            $category = BackendSpotlightsModel::getCategory((int) $id);

            // update sequence
            if (!empty($category)) {
                // change sequence
                $category['sequence'] = $i + 1;

                // update category
                BackendSpotlightsModel::updateCategorySequence($category);
            }
        }

        // success output
        $this->output(Response::HTTP_OK, null, 'sequence updated');
    }
}
