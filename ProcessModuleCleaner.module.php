<?php
namespace ProcessWire;

/**
 * ProcessModuleCleaner
 *
 * Lists and deletes module directories starting with a dot.
 */

class ProcessModuleCleaner extends Process implements Module
{

    /**
     * Returns the module information.
     *
     * @return array
     */
    public static function getModuleInfo()
    {
        return [
            'title' => __('Module Folder Cleaner'),
            'summary' => __('Deletes old module directories (.ModuleName) directly.'),
            'href' => 'https://github.com/markusthomas/ProcessModuleCleaner',
            'version' => '020',
            'author' => 'Markus Thomas',
            'license' => 'MIT',
            'icon' => 'trash',
            'permission' => 'module-admin',
            'page' => [
                'name' => 'module-cleaner',
                'parent' => 'setup',
                'title' => __('Module Cleaner')
            ],
            'requires' => 'ProcessWire>=3.0.0'
        ];
    }

    /**
     * Main execution method.
     * Lists orphaned module folders or shows a success message if none exist.
     *
     * @return string HTML output
     */
    public function ___execute()
    {
        $modulesPath = $this->wire('config')->paths->siteModules;
        $rootPath = $this->wire('config')->paths->root;

        $hiddenFolders = $this->findHiddenFolders($modulesPath);
        $coreCleanables = $this->findCoreCleanables($rootPath);

        if (empty($hiddenFolders) && empty($coreCleanables)) {
            return "<div class='uk-alert-success' uk-alert><p>" . $this->_('No cleanup candidates found. Your system is clean!') . "</p></div>";
        }

        $out = "";
        if (!empty($hiddenFolders)) {
            $out .= $this->renderFolderTable($hiddenFolders);
        }

        if (!empty($coreCleanables)) {
            if (!empty($out)) {
                $out .= "<div class='uk-margin-large-top'></div>";
            }
            $out .= $this->renderCoreTable($coreCleanables);
        }

        $out .= "<script src='https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js' defer></script>";

        return $out;
    }

    /**
     * Scans the modules directory for folders starting with a dot.
     *
     * @param string $path The path to scan
     * @return array Array of folder information (name, modified date)
     */
    protected function findHiddenFolders($path)
    {
        $folders = [];
        if (!is_dir($path))
            return $folders;
        $dir = new \DirectoryIterator($path);
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $name = $fileinfo->getFilename();
                if (strpos($name, '.') === 0) {
                    $folders[] = [
                        'name' => $name,
                        'modified' => date("d.m.Y H:i", $fileinfo->getMTime())
                    ];
                }
            }
        }
        return $folders;
    }

    /**
     * Renders the HTML table for the found folders.
     * Includes AlpineJS for checkbox handling.
     *
     * @param array $folders List of folders
     * @return string HTML output
     */
    protected function renderFolderTable($folders)
    {
        $deleteUrl = $this->wire('page')->url . "delete/";
        $tokenName = $this->wire('session')->CSRF->getTokenName();
        $tokenValue = $this->wire('session')->CSRF->getTokenValue();

        $out = "
        <div class='uk-card uk-card-default uk-card-body' x-data='{ selectedFolders: [] }'>
            <h3 class='uk-card-title'><i class='fa fa-folder-open-o'></i> " . $this->_('Delete Module Folders') . "</h3>

            <form action='{$deleteUrl}' method='POST'>
                <input type='hidden' name='{$tokenName}' value='{$tokenValue}'>

                <table class='uk-table uk-table-divider uk-table-hover uk-table-small uk-table-middle'>
                    <thead>
                        <tr>
                            <th class='uk-table-shrink'>
                                <input class='uk-checkbox' type='checkbox' @change=\"if (\$el.checked) { selectedFolders = " . htmlspecialchars(json_encode(array_column($folders, 'name'))) . " } else { selectedFolders = [] }\">
                            </th>
                            <th>" . $this->_('Directory Name') . "</th>
                            <th>" . $this->_('Last Modified') . "</th>
                        </tr>
                    </thead>
                    <tbody>
        ";

        foreach ($folders as $folder) {
            $name = htmlspecialchars($folder['name']);
            $out .= "
                <tr>
                    <td><input class='uk-checkbox' type='checkbox' name='folders[]' value='{$name}' x-model='selectedFolders'></td>
                    <td><span class='uk-text-danger uk-text-bold font-mono'>{$name}</span></td>
                    <td class='uk-text-muted uk-text-small'>{$folder['modified']}</td>
                </tr>";
        }

        $out .= "
                    </tbody>
                </table>

                <div class='uk-margin-top'>
                    <button type='submit' class='uk-button uk-button-danger' :disabled='selectedFolders.length === 0' onclick=\"return confirm('" . $this->_('Are you sure you want to permanently delete the selected folders?') . "')\">
                        <i class='fa fa-trash'></i> " . $this->_('Delete Selected') . " (<span x-text='selectedFolders.length'>0</span>)
                    </button>
                </div>
            </form>
        </div>
        ";

        return $out;
    }

    /**
     * Handles the deletion of selected folders.
     * Validates CSRF token and permissions.
     *
     * @return void Redirects back to the main page
     */
    public function ___executeDelete()
    {
        $this->wire('session')->CSRF->validate();
        $folders = $this->wire('input')->post->array('folders');

        // Fallback für direkte Post-Daten
        if (empty($folders) && isset($_POST['folders']))
            $folders = $_POST['folders'];

        $modulesPath = $this->wire('config')->paths->siteModules;
        $successCount = 0;

        if (empty($folders)) {
            $this->error($this->_("No folders selected."));
            $this->wire('session')->redirect("../");
        }

        foreach ($folders as $folderName) {
            $folderName = trim($folderName);
            if (strpos($folderName, '.') === 0 && strpos($folderName, '/') === false && strpos($folderName, '\\') === false) {
                $fullPath = $modulesPath . $folderName;
                if (is_dir($fullPath) && $this->wire('files')->rmdir($fullPath, true)) {
                    $successCount++;
                }
            }
        }

        $this->message(sprintf($this->_("Successfully deleted %d folders."), $successCount));
        $this->wire('session')->redirect("../");
    }

    /**
     * Scans the root directory for old core backups.
     *
     * @param string $path The path to scan
     * @return array Array of core items
     */
    protected function findCoreCleanables($path)
    {
        $items = [];
        if (!is_dir($path)) {
            return $items;
        }
        $dir = new \DirectoryIterator($path);
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDot()) {
                continue;
            }
            $name = $fileinfo->getFilename();
            $isDir = $fileinfo->isDir();
            $match = false;
            $type = '';

            if ($isDir) {
                if (strpos($name, '.wire-') === 0) {
                    $match = true;
                    $type = 'dir';
                }
            } else {
                if (strpos($name, '.index-') === 0 && substr($name, -4) === '.php') {
                    $match = true;
                    $type = 'file';
                } elseif (strpos($name, 'htaccess-') === 0 && substr($name, -4) === '.txt') {
                    $match = true;
                    $type = 'file';
                }
            }

            if ($match) {
                $items[] = [
                    'name' => $name,
                    'type' => $type,
                    'modified' => date("d.m.Y H:i", $fileinfo->getMTime()),
                    'size' => $isDir ? $this->getDirSize($fileinfo->getPathname()) : $fileinfo->getSize()
                ];
            }
        }
        return $items;
    }

    /**
     * Recursively calculates the size of a directory.
     *
     * @param string $path
     * @return int Size in bytes
     */
    protected function getDirSize($path)
    {
        $size = 0;
        if (!is_dir($path)) return 0;
        try {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($files as $file) {
                $size += $file->getSize();
            }
        } catch (\Exception $e) {
            // Fallback
        }
        return $size;
    }

    /**
     * Formats bytes into a human-readable string.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Renders the HTML table for the found core files and folders.
     *
     * @param array $items List of core items
     * @return string HTML output
     */
    protected function renderCoreTable($items)
    {
        $deleteUrl = $this->wire('page')->url . "delete-core/";
        $tokenName = $this->wire('session')->CSRF->getTokenName();
        $tokenValue = $this->wire('session')->CSRF->getTokenValue();

        $out = "
        <div class='uk-card uk-card-default uk-card-body' x-data='{ selectedItems: [] }'>
            <h3 class='uk-card-title'><i class='fa fa-hdd-o'></i> " . $this->_('Delete ProcessWire Core Backups') . "</h3>

            <form action='{$deleteUrl}' method='POST'>
                <input type='hidden' name='{$tokenName}' value='{$tokenValue}'>

                <table class='uk-table uk-table-divider uk-table-hover uk-table-small uk-table-middle'>
                    <thead>
                        <tr>
                            <th class='uk-table-shrink'>
                                <input class='uk-checkbox' type='checkbox' @change=\"if (\$el.checked) { selectedItems = " . htmlspecialchars(json_encode(array_column($items, 'name'))) . " } else { selectedItems = [] }\">
                            </th>
                            <th>" . $this->_('Item Name') . "</th>
                            <th>" . $this->_('Type') . "</th>
                            <th>" . $this->_('Size') . "</th>
                            <th>" . $this->_('Last Modified') . "</th>
                        </tr>
                    </thead>
                    <tbody>
        ";

        foreach ($items as $item) {
            $name = htmlspecialchars($item['name']);
            $typeLabel = $item['type'] === 'dir' ? $this->_('Folder') : $this->_('File');
            $typeColor = $item['type'] === 'dir' ? 'uk-text-warning' : 'uk-text-primary';
            $sizeLabel = $this->formatBytes($item['size']);
            
            $out .= "
                <tr>
                    <td><input class='uk-checkbox' type='checkbox' name='core_items[]' value='{$name}' x-model='selectedItems'></td>
                    <td><span class='uk-text-danger uk-text-bold font-mono'>{$name}</span></td>
                    <td><span class='{$typeColor}'>{$typeLabel}</span></td>
                    <td class='uk-text-small'>{$sizeLabel}</td>
                    <td class='uk-text-muted uk-text-small'>{$item['modified']}</td>
                </tr>";
        }

        $out .= "
                    </tbody>
                </table>

                <div class='uk-margin-top'>
                    <button type='submit' class='uk-button uk-button-danger' :disabled='selectedItems.length === 0' onclick=\"return confirm('" . $this->_('Are you sure you want to permanently delete the selected core items?') . "')\">
                        <i class='fa fa-trash'></i> " . $this->_('Delete Selected') . " (<span x-text='selectedItems.length'>0</span>)
                    </button>
                </div>
            </form>
        </div>
        ";

        return $out;
    }

    /**
     * Handles the deletion of selected Core items.
     * Validates CSRF token and permissions.
     *
     * @return void Redirects back to the main page
     */
    public function ___executeDeleteCore()
    {
        $this->wire('session')->CSRF->validate();
        $items = $this->wire('input')->post->array('core_items');

        if (empty($items) && isset($_POST['core_items'])) {
            $items = $_POST['core_items'];
        }

        $rootPath = $this->wire('config')->paths->root;
        $successCount = 0;

        if (empty($items)) {
            $this->error($this->_("No core items selected."));
            $this->wire('session')->redirect("../");
        }

        foreach ($items as $name) {
            $name = trim($name);
            if (strpos($name, '/') !== false || strpos($name, '\\') !== false) {
                continue;
            }

            $fullPath = $rootPath . $name;
            if (!file_exists($fullPath)) {
                continue;
            }

            $isValid = false;
            $isDir = is_dir($fullPath);

            if ($isDir) {
                if (strpos($name, '.wire-') === 0) {
                    $isValid = true;
                }
            } else {
                if (strpos($name, '.index-') === 0 && substr($name, -4) === '.php') {
                    $isValid = true;
                } elseif (strpos($name, 'htaccess-') === 0 && substr($name, -4) === '.txt') {
                    $isValid = true;
                }
            }

            if ($isValid) {
                if ($isDir) {
                    if ($this->wire('files')->rmdir($fullPath, true)) {
                        $successCount++;
                    }
                } else {
                    if ($this->wire('files')->unlink($fullPath)) {
                        $successCount++;
                    }
                }
            }
        }

        $this->message(sprintf($this->_("Successfully deleted %d Core files/folders."), $successCount));
        $this->wire('session')->redirect("../");
    }
}