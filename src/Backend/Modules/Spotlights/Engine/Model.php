<?php

namespace Backend\Modules\Spotlights\Engine;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Symfony\Component\Finder\Finder;
use Common\ModuleExtraType;
use Common\Uri as CommonUri;
use Backend\Core\Language\Language as BL;
use Backend\Core\Engine\Model as BackendModel;

/**
 * In this file we store all generic functions that we will be using in the Spotlights module
 *
 * @author Wouter Verstuyf <info@webflow.be>
 */
class Model
{
    const QUERY_DATAGRID_BROWSE =
        'SELECT i.id, i.title, i.category_id, i.sequence
         FROM spotlights AS i
         WHERE i.language = ?
         AND i.category_id = ?
         ORDER BY i.sequence';

    const QUERY_DATAGRID_BROWSE_CATEGORIES =
        'SELECT i.id, i.title, COUNT(p.id) AS num_items
         FROM spotlights_categories AS i
         LEFT OUTER JOIN spotlights AS p ON i.id = p.category_id AND p.language = i.language
         WHERE i.language = ?
         GROUP BY i.id
         ORDER BY i.sequence';

    /**
     * Delete a spotlight
     *
     * @param int $id
     */
    public static function delete(int $id): void
    {
        BackendModel::get('database')->delete('spotlights', 'id = ?', [$id]);
    }

    /**
     * Deletes a category
     *
     * @param int $id
     */
    public static function deleteCategory(int $id): void
    {
        $id = (int) $id;
        $db = BackendModel::getContainer()->get('database');

        // get item
        $item = self::getCategory($id);

        if (!empty($item)) {
            // delete meta
            $db->delete('meta', 'id = ?', [$item['meta_id']]);

            // delete extra
            $db->delete('modules_extras', 'id = ?', [$item['extra_id']]);

            // delete category
            $db->delete('spotlights_categories', 'id = ?', [$id]);

            // update category for the posts that might be in this category
            $db->update('spotlights', array('category_id' => null), 'category_id = ?', [$id]);
        }
    }

    /**
     * Checks if it is allowed to delete the a category
     *
     * @param int $id
     *
     * @return bool
     */
    public static function deleteCategoryAllowed(int $id): bool
    {
        return !(bool) BackendModel::getContainer()->get('database')->getVar(
            'SELECT 1
             FROM spotlights AS i
             WHERE i.id = ? AND i.language = ?
             LIMIT 1',
            [$id, BL::getWorkingLanguage()]
        );
    }

    /**
     * Checks if a certain item exists
     *
     * @param int $id
     *
     * @return bool
     */
    public static function exists(int $id): bool
    {
        return (bool) BackendModel::get('database')->getVar(
            'SELECT 1
             FROM spotlights AS i
             WHERE i.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /**
     * Checks if a category exists
     *
     * @param int $id
     *
     * @return bool
     */
    public static function existsCategory(int $id): bool
    {
        return (bool) BackendModel::getContainer()->get('database')->getVar(
            'SELECT 1
             FROM spotlights_categories AS i
             WHERE i.id = ? AND i.language = ?
             LIMIT 1',
            [$id, BL::getWorkingLanguage()]
        );
    }

    /**
     * Get a spotlight by id
     *
     * @param int $id
     *
     * @return array
     */
    public static function get(int $id): array
    {
        return (array) BackendModel::get('database')->getRecord(
            'SELECT i.*
             FROM spotlights AS i
             WHERE i.id = ?',
            [$id]
        );
    }

    /**
     * Get all categories
     *
     * @param bool $includeCount Include the count?
     *
     * @return array
     */
    public static function getCategories(bool $includeCount = false): array
    {
        $db = BackendModel::getContainer()->get('database');

        if ($includeCount) {
            return (array) $db->getPairs(
                'SELECT i.id, CONCAT(i.title, " (", COUNT(p.category_id) ,")") AS title
                 FROM spotlights_categories AS i
                 LEFT OUTER JOIN spotlights AS p ON i.id = p.category_id AND i.language = p.language
                 WHERE i.language = ?
                 GROUP BY i.id',
                [BL::getWorkingLanguage()]
            );
        }

        return (array) $db->getPairs(
            'SELECT i.id, i.title
             FROM spotlights_categories AS i
             WHERE i.language = ?',
            [BL::getWorkingLanguage()]
        );
    }

    /**
     * Get all category names for dropdown
     *
     * @return  array
     */
    public static function getCategoriesForDropdown(): array
    {
        return (array) BackendModel::getContainer()->get('database')->getPairs(
            'SELECT i.id, i.title
            FROM spotlights_categories AS i
            WHERE i.language = ?
            ORDER BY i.sequence ASC',
            [BL::getWorkingLanguage()]
        );
    }

    /**
     * Get all data for a given id
     *
     * @param int $id The id of the category to fetch.
     *
     * @return array
     */
    public static function getCategory(int $id): array
    {
        return (array) BackendModel::getContainer()->get('database')->getRecord(
            'SELECT i.*
             FROM spotlights_categories AS i
             WHERE i.id = ? AND i.language = ?',
            [$id, BL::getWorkingLanguage()]
        );
    }

    /**
     * Get internal links for dropdown
     *
     * @return array
     */
    public static function getInternalLinks(): array
    {
        return (array) BackendModel::getContainer()->get('database')->getPairs(
            'SELECT p.id AS value, p.title
             FROM pages AS p
             WHERE p.status = ? AND p.hidden = ? AND p.language = ?',
            ['active', 'N', BL::getWorkingLanguage()]
        );
    }

    /**
     * Get the maximum Spotlights sequence.
     *
     * @return int
     */
    public static function getMaximumSequence(): int
    {
        return (int) BackendModel::get('database')->getVar(
            'SELECT MAX(i.sequence)
             FROM spotlights AS i'
        );
    }

    /**
     * Get the max sequence id for category
     *
     * @return  int
     */
    public static function getMaximumCategorySequence(): int
    {
        return (int) BackendModel::getContainer()->get('database')->getVar(
            'SELECT MAX(i.sequence)
            FROM spotlights_categories AS i'
        );
    }

    /**
     * Get templates.
     *
     * @return array
     */
    public static function getTemplates()
    {
        $templates = [];
        $theme = BackendModel::get('fork.settings')->get('Core', 'theme', 'Fork');
        $finder = new Finder();
        $finder->name('Template*.html.twig');
        $finder->in(FRONTEND_MODULES_PATH . '/Spotlights/Layout/Widgets');

        // if there is a custom theme we should include the templates there also
        if ($theme !== 'Core') {
            $path = FRONTEND_PATH . '/Themes/' . $theme . '/Modules/Spotlights/Layout/Widgets';
            if (is_dir($path)) {
                $finder->in($path);
            }
        }

        foreach ($finder->files() as $file) {
            $templates[] = $file->getBasename();
        }

        $templates = array_unique($templates);

        return array_combine($templates, $templates);
    }

    /**
     * Retrieve the unique URL for an item
     *
     * @param string $url
     * @param int $id
     *
     * @return string
     */
    public static function getURL(string $url, int $id = null): string
    {
        $url = CommonUri::getUrl((string) $url);
        $db = BackendModel::get('database');

        if ($id === null) {
            $urlExists = (bool) $db->getVar(
                'SELECT 1
                 FROM spotlights AS i
                 INNER JOIN meta AS m ON i.meta_id = m.id
                 WHERE i.language = ? AND m.url = ?
                 LIMIT 1',
                [BL::getWorkingLanguage(), $url]
            );
        } else {
            $urlExists = (bool) $db->getVar(
                'SELECT 1
                 FROM spotlights AS i
                 INNER JOIN meta AS m ON i.meta_id = m.id
                 WHERE i.language = ? AND m.url = ? AND i.id != ?
                 LIMIT 1',
                [BL::getWorkingLanguage(), $url, $id]
            );
        }

        if ($urlExists) {
            $url = BackendModel::addNumber($url);
            return self::getURL($url, $id);
        }

        return $url;
    }


    /**
     * Retrieve the unique URL for a category
     *
     * @param string $url
     * @param int $id
     *
     * @return string
     */
    public static function getURLForCategory(string $url, int $id = null): string
    {
        $url = CommonUri::getUrl((string) $url);
        $db = BackendModel::get('database');

        // new category
        if ($id === null) {
            // already exists
            if ((bool) $db->getVar(
                'SELECT 1
                 FROM spotlights_categories AS i
                 INNER JOIN meta AS m ON i.meta_id = m.id
                 WHERE i.language = ? AND m.url = ?
                 LIMIT 1',
                [BL::getWorkingLanguage(), $url]
            )
            ) {
                $url = BackendModel::addNumber($url);

                return self::getURLForCategory($url);
            }
        } else {
            // current category should be excluded
            if ((bool) $db->getVar(
                'SELECT 1
                 FROM spotlights_categories AS i
                 INNER JOIN meta AS m ON i.meta_id = m.id
                 WHERE i.language = ? AND m.url = ? AND i.id != ?
                 LIMIT 1',
                [BL::getWorkingLanguage(), $url, $id]
            )
            ) {
                $url = BackendModel::addNumber($url);

                return self::getURLForCategory($url, $id);
            }
        }

        return $url;
    }

    /**
     * Insert an item in the database
     *
     * @param array $item
     * @return int
     */
    public static function insert(array $item): int
    {
        $item['created_on'] = BackendModel::getUTCDate();
        $item['edited_on'] = BackendModel::getUTCDate();

        return (int) BackendModel::get('database')->insert('spotlights', $item);
    }

    /**
     * Inserts a new category into the database
     *
     * @param array $item The data for the category to insert.
     * @param array $meta The metadata for the category to insert.
     *
     * @return int
     */
    public static function insertCategory(array $item, array $meta = null): int
    {
        $db = BackendModel::getContainer()->get('database');

        // insert the meta if possible
        if ($meta !== null) {
            $item['meta_id'] = $db->insert('meta', $meta);
        }

        // insert extra
        $item['extra_id'] = BackendModel::insertExtra(
            ModuleExtraType::widget(),
            'Spotlights',
            'SpotlightsCategory'
        );

        $item['id'] = $db->insert('spotlights_categories', $item);

        // update extra (item id is now known)
        BackendModel::updateExtra(
            $item['extra_id'],
            'data',
            [
                'category_id' => $item['id'],
                'extra_label' => \SpoonFilter::ucfirst(BL::lbl('SpotlightsFromCategory')) . ': ' . $item['title'],
                'language' => $item['language'],
                'edit_url' => BackendModel::createUrlForAction(
                    'EditCategory',
                    'Spotlights',
                    $item['language']
                ) . '&id=' . $item['id'],
            ]
        );

        return (int) $item['id'];
    }

    /**
     * Re-generate the thumbnails
     */
    public static function reGenerateThumbnails(string $path, string $sourceFile, string $horizontalCropPosition = 'center', string $verticalCropPosition = 'middle'): void
    {
        // get folder listing
        $folders = BackendModel::getThumbnailFolders($path);
        $filename = basename($sourceFile);

        // loop folders
        foreach ($folders as $folder) {
            // generate the thumbnail
            $thumbnail = new \SpoonThumbnail($path . '/source/' . $sourceFile, $folder['width'], $folder['height']);
            $thumbnail->setAllowEnlargement(true);
            $thumbnail->setCropPosition($horizontalCropPosition, $verticalCropPosition);

            // if the width & height are specified we should ignore the aspect ratio
            if ($folder['width'] !== null && $folder['height'] !== null) {
                $thumbnail->setForceOriginalAspectRatio(false);
            }
            $thumbnail->parseToFile($folder['path'] . '/' . $filename);
        }
    }

    /**
     * Rotate image
     */
    public static function rotateImage(string $path, string $sourceFile, int $degrees = 90): void
    {
        // File and rotation
        $rotateFilename = $path . '/source/' . $sourceFile; // PATH
        $filename = basename($sourceFile);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION ));

        if($extension == 'png'){
           header('Content-type: image/png');
           $source = imagecreatefrompng($rotateFilename);
           $bgColor = imagecolorallocatealpha($source, 255, 255, 255, 127);
           // Rotate
           $rotate = imagerotate($source, $degrees, $bgColor);
           imagesavealpha($rotate, true);
           imagepng($rotate,$rotateFilename);
        }

        if($extension == 'jpg'){
           header('Content-type: image/jpeg');
           $source = imagecreatefromjpeg($rotateFilename);
           // Rotate
           $rotate = imagerotate($source, $degrees, 0);
           imagejpeg($rotate,$rotateFilename);
        }

        // regenerate thumbnails
        self::reGenerateThumbnails($path, $sourceFile);

        // Free the memory
        imagedestroy($source);
        imagedestroy($rotate);
    }

    /**
     * Updates an item
     *
     * @param array $item
     */
    public static function update(array $item): void
    {
        $item['edited_on'] = BackendModel::getUTCDate();

        BackendModel::get('database')->update(
            'spotlights',
            $item,
            'id = ?',
            [(int) $item['id']]
        );
    }

    /**
     * Update an existing category
     *
     * @param array       $item The new data.
     */
    public static function updateCategory(array $item): void
    {
        $db = BackendModel::getContainer()->get('database');

        // update the category
        $db->update('spotlights_categories', $item, 'id = ?', [(int) $item['id']]);

        // update extra
        BackendModel::updateExtra(
            $item['extra_id'],
            'data',
            [
                'category_id' => $item['id'],
                'extra_label' => ucfirst(BL::lbl('SpotlightsFromCategory')) . ': ' . $item['title'],
                'language' => $item['language'],
                'edit_url' => BackendModel::createUrlForAction('EditCategory') . '&id=' . $item['id'],
            ]
        );
    }

    /**
     * Update a category sequence
     *
     * @param array       $item The new data.
     */
    public static function updateCategorySequence(array $item): void
    {
        BackendModel::getContainer()->get('database')->update(
            'spotlights_categories',
            $item,
            'id = ?',
            [(int) $item['id']]
        );
    }
}
