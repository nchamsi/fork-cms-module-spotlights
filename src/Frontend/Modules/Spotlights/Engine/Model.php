<?php

namespace Frontend\Modules\Spotlights\Engine;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Frontend\Core\Engine\Model as FrontendModel;
use Frontend\Core\Engine\Navigation as FrontendNavigation;

/**
 * In this file we store all generic functions that we will be using in the Spotlights module
 *
 * @author Wouter Verstuyf <info@webflow.be>
 */
class Model
{
    /**
     * Get all items (at least a chunk)
     *
     * @return array
     */
    public static function getAll(): array
    {
        $items = (array) FrontendModel::get('database')->getRecords(
            'SELECT i.*, m.url
             FROM spotlights AS i
             INNER JOIN meta AS m ON i.meta_id = m.id
             WHERE i.language = ?
             AND i.hidden = ?
             ORDER BY i.sequence ASC, i.id DESC',
            [FRONTEND_LANGUAGE, 'N']
        );

        // no results?
        if (empty($items)) {
            return array();
        }

        // add classes
        $pointer = 0;
        foreach($items as &$item) {

            // css classes
            $pointer++;
            $class = array();
            ($pointer%2 == 0) ? $class[] = 'even' : $class[] = 'odd';
            if($pointer%3 == 0) $class[] = 'third';
            $classString = implode(' ', $class);
            $item['class'] = $classString;

            // Get the thumbnail-folders
            $folders = FrontendModel::getThumbnailFolders(FRONTEND_FILES_PATH . '/spotlights/images', true);

            // Create the image-links to the thumbnail folders
            foreach($folders as $folder)
            {
                $item['image_' . $folder['dirname']] = $folder['url'] . '/' . $folder['dirname'] . '/' . $item['image'];
            }

            // links
            $item['data'] = unserialize($item['data']);

            // is there a link given?
            if($item['data']['link'] !== null)
            {
                // set the external option. This allows us to link to external sources
                $external = ($item['data']['link']['type'] == 'external');
                $item['data']['link']['external'] = $external;

                // if this is an internal page, we need to build the url since we have the id
                if(!$external)
                {
                    $extraId = $item['data']['link']['id'];
                    $item['data']['link']['url'] = FrontendNavigation::getURL($extraId);
                }
            }
        }

        // return
        return $items;
    }

    /**
     * Get all items (at least a chunk)
     *
     * @return array
     */
    public static function getAllForCategory(int $categoryId): array
    {
        $items = (array) FrontendModel::get('database')->getRecords(
            'SELECT i.*, m.url
             FROM spotlights AS i
             INNER JOIN meta AS m ON i.meta_id = m.id
             WHERE i.language = ?
             AND i.hidden = ?
             AND i.category_id = ?
             ORDER BY i.sequence ASC, i.id DESC',
            [FRONTEND_LANGUAGE, 'N', $categoryId]
        );

        // no results?
        if (empty($items)) {
            return array();
        }

        // add classes
        $pointer = 0;
        foreach($items as &$item) {

            // css classes
            $pointer++;
            $class = [];
            ($pointer%2 == 0) ? $class[] = 'even' : $class[] = 'odd';
            if($pointer%3 == 0) $class[] = 'third';
            $classString = implode(' ', $class);
            $item['class'] = $classString;

            // Get the thumbnail-folders
            $folders = FrontendModel::getThumbnailFolders(FRONTEND_FILES_PATH . '/spotlights/images', true);

            // Create the image-links to the thumbnail folders
            foreach($folders as $folder)
            {
                $item['image_' . $folder['dirname']] = $folder['url'] . '/' . $folder['dirname'] . '/' . $item['image'];
            }

            // links
            $item['data'] = unserialize($item['data']);

            // is there a link given?
            if($item['data']['link'] !== null)
            {
                // set the external option. This allows us to link to external sources
                $external = ($item['data']['link']['type'] == 'external');
                $item['data']['link']['external'] = $external;

                // if this is an internal page, we need to build the url since we have the id
                if(!$external)
                {
                    $extraId = $item['data']['link']['id'];
                    $item['data']['link']['url'] = FrontendNavigation::getURL($extraId);
                }
            }
        }

        // return
        return $items;
    }

    /**
     * Get category
     *
     * @return array
     */
    public static function getCategory(int $categoryId): array
    {
        return (array) FrontendModel::get('database')->getRecord(
            'SELECT c.*
             FROM spotlights_categories AS c
             WHERE c.language = ?
             AND c.id = ?',
            [FRONTEND_LANGUAGE, $categoryId]
        );
    }

}
