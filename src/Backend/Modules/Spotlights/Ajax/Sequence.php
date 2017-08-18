<?php

namespace Backend\Modules\Spotlights\Ajax;

use Backend\Core\Engine\Base\AjaxAction as BackendBaseAJAXAction;
use Backend\Core\Language\Language;
use Backend\Modules\Spotlights\Engine\Model as BackendSpotlightsModel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alters the sequence of spotlights items
 *
 * @author Wouter Verstuyf <info@webflow.be>
 */
class Sequence extends BackendBaseAJAXAction
{
    public function execute(): void
    {
        parent::execute();

        $spotlightId = $this->getRequest()->request->getInt('spotlightId');
        $fromCategoryId = $this->getRequest()->request->getInt('fromCategoryId');
        $toCategoryId = $this->getRequest()->request->getInt('toCategoryId');
        $fromCategorySequence = $this->getRequest()->request->get('fromCategorySequence', '');
        $toCategorySequence = $this->getRequest()->request->get('toCategorySequence', '');


        // invalid spotlight id
        if (!BackendSpotlightsModel::exists($spotlightId)) {
            $this->output(Response::HTTP_BAD_REQUEST, null, 'spotlight does not exist');

            return;
        }

        // list ids
        $fromCategorySequence = (array) explode(',', ltrim($fromCategorySequence, ','));
        $toCategorySequence = (array) explode(',', ltrim($toCategorySequence, ','));

        // is the spotlight moved to a new category?
        if ($fromCategoryId != $toCategoryId) {
            $item = [];
            $item['id'] = $spotlightId;
            $item['category_id'] = $toCategoryId;

            BackendSpotlightsModel::update($item);

            // loop id's and set new sequence
            foreach ($toCategorySequence as $i => $id) {
                $item = [];
                $item['id'] = (int) $id;
                $item['sequence'] = $i + 1;

                // update sequence if the item exists
                if (BackendSpotlightsModel::exists($item['id'])) {
                    BackendSpotlightsModel::update($item);
                }
            }
        }

        // loop id's and set new sequence
        foreach ($fromCategorySequence as $i => $id) {
            $item['id'] = (int) $id;
            $item['sequence'] = $i + 1;

            // update sequence if the item exists
            if (BackendSpotlightsModel::exists($item['id'])) {
                BackendSpotlightsModel::update($item);
            }
        }

        // success output
        $this->output(Response::HTTP_OK, null, Language::msg('SequenceSaved'));
    }
}
