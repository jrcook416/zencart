<?php
/**
 *
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: torvista 2026 Mar 14 Modified in v2.2.1 $
 */
namespace Zencart\LanguageLoader;

use Zencart\FileSystem\FileSystem;

/**
 * @since ZC v1.5.8
 */
class CatalogFilesLanguageLoader extends FilesLanguageLoader
{
    /**
     * @since ZC v1.5.8
     */
    public function loadInitialLanguageDefines($mainLoader)
    {
        $this->mainLoader = $mainLoader;
        $this->loadLanguageExtraDefinitions();
        $this->loadMainLanguageFiles();
    }

    /**
     * @since ZC v1.5.8
     */
    public function loadLanguageForView(): void
    {
        if (defined('NO_LANGUAGE_SUBSTRING_MATCH') && in_array($this->currentPage, NO_LANGUAGE_SUBSTRING_MATCH)) {
            $files_to_match = $this->currentPage;
        } else {
            $files_to_match = $this->currentPage . '(.*)';
        }

        // -----
        // If this template inherits from a parent template (see DIR_WS_TEMPLATE_PARENT), load
        // the parent's per-page files first so the active template's own per-page files (loaded
        // below) still take precedence.
        //
        if (defined('DIR_WS_TEMPLATE_PARENT') && DIR_WS_TEMPLATE_PARENT !== '') {
            $parentTemplateDir = basename(rtrim(DIR_WS_TEMPLATE_PARENT, '/'));
            $directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $parentTemplateDir;
            $files = $this->fileSystem->listFilesFromDirectoryAlphaSorted($directory, '~^' . $files_to_match  . '\.php$~i');
            foreach ($files as $file) {
                $this->loadFileDefineFile($directory . '/' . $file);
            }
        }

        $directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $this->templateDir;
        $files = $this->fileSystem->listFilesFromDirectoryAlphaSorted($directory, '~^' . $files_to_match  . '\.php$~i');
        foreach ($files as $file) {
            $this->loadFileDefineFile($directory . '/' . $file);
        }

        $directory = DIR_WS_LANGUAGES . $_SESSION['language'];
        $files = $this->fileSystem->listFilesFromDirectoryAlphaSorted($directory, '~^' . $files_to_match  . '\.php$~i');
        foreach ($files as $file) {
            $this->loadFileDefineFile($directory . '/' . $file);
        }
    }

    /**
     * @since ZC v1.5.8
     */
    protected function loadMainLanguageFiles(): void
    {
        $extraFiles = [
            FILENAME_EMAIL_EXTRAS,
            FILENAME_HEADER,
            FILENAME_BUTTON_NAMES,
            FILENAME_ICON_NAMES,
            FILENAME_OTHER_IMAGES_NAMES,
            FILENAME_CREDIT_CARDS,
            FILENAME_WHOS_ONLINE,
            FILENAME_META_TAGS,
        ];

        // -----
        // If this template inherits from a parent template (see DIR_WS_TEMPLATE_PARENT), load
        // the parent's main template-language file and 'other' per-template files first, so the
        // active template's own files (loaded below) still take precedence.
        //
        $parentTemplateDir = (defined('DIR_WS_TEMPLATE_PARENT') && DIR_WS_TEMPLATE_PARENT !== '') ? basename(rtrim(DIR_WS_TEMPLATE_PARENT, '/')) : '';
        if ($parentTemplateDir !== '') {
            $this->loadFileDefineFile(DIR_WS_LANGUAGES . $parentTemplateDir . '/' . $_SESSION['language'] . '.php');
        }

        $this->loadFileDefineFile(DIR_WS_LANGUAGES . $this->templateDir . '/' . $_SESSION['language'] . '.php');
        $this->loadFileDefineFile(DIR_WS_LANGUAGES . $_SESSION['language'] . '.php');
        foreach ($extraFiles as $file) {
            $file = basename($file, '.php') . '.php';
            $this->loadExtraLanguageFiles(DIR_WS_LANGUAGES, $_SESSION['language'], $file);
        }
    }

    /**
     * @since ZC v1.5.8
     */
    protected function loadLanguageExtraDefinitions(): void
    {
        $extraDefsDir = DIR_WS_LANGUAGES . $_SESSION['language'] . '/extra_definitions';
        $extraDefsDirTpl = $extraDefsDir . '/' . $this->templateDir;
        $extraDefs = $this->fileSystem->listFilesFromDirectoryAlphaSorted($extraDefsDir);
        $extraDefsTpl = $this->fileSystem->listFilesFromDirectoryAlphaSorted($extraDefsDirTpl);

        $folderList = [
            $extraDefsDir => $extraDefs,
        ];

        // -----
        // If this template declares a parent template (see DIR_WS_TEMPLATE_PARENT, set from
        // $template_parent in template_info.php), also pick up that parent's extra_definitions
        // files for the current session language, so a child template doesn't need to duplicate
        // them. These are loaded before the child template's own extra_definitions, below, so the
        // child's own files still take precedence.
        //
        if (defined('DIR_WS_TEMPLATE_PARENT') && DIR_WS_TEMPLATE_PARENT !== '') {
            $parentTemplateDir = basename(rtrim(DIR_WS_TEMPLATE_PARENT, '/'));
            $extraDefsDirParent = $extraDefsDir . '/' . $parentTemplateDir;
            $folderList[$extraDefsDirParent] = $this->fileSystem->listFilesFromDirectoryAlphaSorted($extraDefsDirParent);
        }

        $folderList[$extraDefsDirTpl] = $extraDefsTpl;

        $foundList = [];
        foreach ($folderList as $folder => $entries) {
            foreach ($entries as $entry) {
                $foundList[$entry] = $folder;
            }
        }

        foreach ($foundList as $file => $directory) {
            $this->loadFileDefineFile($directory . '/' . $file);
        }
    }
}
