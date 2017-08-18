<?php

namespace Backend\Modules\Spotlights\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionIndex as BackendBaseActionIndex;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Engine\DataGridDatabase as BackendDataGridDatabase;
use Backend\Core\Language\Language as BL;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Modules\Spotlights\Engine\Model as BackendSpotlightsModel;

/**
 * This is the categories-action, it will display the overview of Spotlights categories
 *
 * @author Wouter Verstuyf <wouter@webflow.be>
 */
class Categories extends BackendBaseActionIndex
{
    /**
     * The dataGrids
     *
     * @var array
     */
    protected $dataGrid;

    public function execute(): void
    {
        parent::execute();

        $this->loadDataGrid();

        $this->parse();
        $this->display();
    }


    private function loadDataGrid(): void
    {
        // create datagrid
        $this->dataGrid = new BackendDataGridDatabase(
            BackendSpotlightsModel::QUERY_DATAGRID_BROWSE_CATEGORIES,
            [BL::getWorkingLanguage()]
        );

        // disable paging
        $this->dataGrid->setPaging(false);

        // set column URLs
        $this->dataGrid->setColumnURL(
            'title',
            BackendModel::createURLForAction('EditCategory') . '&amp;id=[id]'
        );

        // enable drag and drop
        $this->dataGrid->enableSequenceByDragAndDrop();

        // our JS needs to know an id, so we can send the new order
        $this->dataGrid->setRowAttributes(['id' => '[id]']);
        $this->dataGrid->setPaging(false);
        $this->dataGrid->setAttributes(array('data-action' => "SequenceCategories"));

        // add edit column
        $this->dataGrid->addColumn(
            'edit',
            null,
            BL::lbl('Edit'),
            BackendModel::createURLForAction('EditCategory') . '&amp;id=[id]',
            BL::lbl('Edit')
        );
    }

    protected function parse(): void
    {
        parent::parse();

        $this->template->assign('dataGrid', $this->dataGrid->getContent());
    }
}
