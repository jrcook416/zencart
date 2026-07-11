<?php
/**
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 29 Modified in v2.2.0 $
 */
namespace Zencart\LanguageLoader;

use Zencart\FileSystem\FileSystem;

/**
 * @since ZC v1.5.8
 */
class FilesLanguageLoader extends BaseLanguageLoader
{
    protected $mainLoader;

    /**
     * @since ZC v1.5.8
     */
    public function loadExtraLanguageFiles(string $rootPath, string $language, string $fileName, string $extraPath = ''): void
    {
        if ($this->mainLoader->hasLanguageFile($rootPath, $language, $fileName, $extraPath .  '/' . $this->templateDir)) {
            $this->loadFileDefineFile($rootPath . $language . $extraPath . '/' . $this->templateDir . '/' . $fileName);
            return;
        }

        // -----
        // If the active template has no override of its own but inherits from a parent template
        // (see DIR_WS_TEMPLATE_PARENT), use the parent template's file instead of falling straight
        // through to the base (non-template) file.
        //
        if (defined('DIR_WS_TEMPLATE_PARENT') && DIR_WS_TEMPLATE_PARENT !== '') {
            $parentTemplateDir = basename(rtrim(DIR_WS_TEMPLATE_PARENT, '/'));
            if ($this->mainLoader->hasLanguageFile($rootPath, $language, $fileName, $extraPath . '/' . $parentTemplateDir)) {
                $this->loadFileDefineFile($rootPath . $language . $extraPath . '/' . $parentTemplateDir . '/' . $fileName);
                return;
            }
        }

        $this->loadFileDefineFile($rootPath . $language . $extraPath . '/' . $fileName);
    }

    /**
     * @since ZC v2.1.0
     */
    public function loadModuleLanguageFile(string $fileName, string $module_type): bool
    {
        $rootPath = DIR_FS_CATALOG . DIR_WS_LANGUAGES . $_SESSION['language'];
        if ($module_type !== '') {
            $module_type .= '/';
        }
        $extraPath = '/modules/' . $module_type;

        if ($this->loadFileDefineFile($rootPath . $extraPath . $this->templateDir . '/' . $fileName) === true) {
            return true;
        }

        // -----
        // If the active template has no module-language override of its own but inherits from a
        // parent template (see DIR_WS_TEMPLATE_PARENT), try the parent template's override next.
        //
        if (defined('DIR_WS_TEMPLATE_PARENT') && DIR_WS_TEMPLATE_PARENT !== '') {
            $parentTemplateDir = basename(rtrim(DIR_WS_TEMPLATE_PARENT, '/'));
            if ($this->loadFileDefineFile($rootPath . $extraPath . $parentTemplateDir . '/' . $fileName) === true) {
                return true;
            }
        }

        return $this->loadFileDefineFile($rootPath . $extraPath . $fileName);
    }

    /**
     * @since ZC v1.5.8
     */
    protected function loadFileDefineFile(string $defineFile): bool
    {
        $pathInfo = pathinfo(($defineFile));
        if (preg_match('~^lang\.~i', $pathInfo['basename'])) {
            return false;
        }
        if (!is_file($defineFile)) {
            return false;
        }
        if ($this->mainLoader->isFileAlreadyLoaded($defineFile)) {
            return false;
        }
        $this->mainLoader->addLanguageFilesLoaded('legacy', $defineFile);
        include_once $defineFile;
        return true;
    }
}
