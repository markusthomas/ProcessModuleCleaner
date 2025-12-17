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
            'title' => 'Module Folder Cleaner',
            'summary' => 'Deletes old module directories (.ModuleName) directly.',
            'href' => 'https://github.com/markusthomas/ProcessModuleCleaner',
            'version' => '001',
            'author' => 'Markus Thomas',
            'license' => 'MIT',
            'icon' => 'trash',
            'permission' => 'module-admin',
            'page' => [
                'name' => 'module-cleaner',
                'parent' => 'setup',
                'title' => 'Module Cleaner'
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
        $hiddenFolders = $this->findHiddenFolders($modulesPath);

        if (empty($hiddenFolders)) {
            return "<div class='uk-alert-success' uk-alert><p>No orphaned module folders found.</p></div>";
        }

        return $this->renderFolderTable($hiddenFolders);
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
            <h3 class='uk-card-title'><i class='fa fa-folder-open-o'></i> Delete Module Folders</h3>

            <form action='{$deleteUrl}' method='POST'>
                <input type='hidden' name='{$tokenName}' value='{$tokenValue}'>

                <table class='uk-table uk-table-divider uk-table-hover uk-table-small uk-table-middle'>
                    <thead>
                        <tr>
                            <th class='uk-table-shrink'>
                                <input class='uk-checkbox' type='checkbox' @change=\"if (\$el.checked) { selectedFolders = " . htmlspecialchars(json_encode(array_column($folders, 'name'))) . " } else { selectedFolders = [] }\">
                            </th>
                            <th>Directory Name</th>
                            <th>Last Modified</th>
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
                    <button type='submit' class='uk-button uk-button-danger' :disabled='selectedFolders.length === 0' onclick=\"return confirm('Are you sure you want to permanently delete the selected folders?')\">
                        <i class='fa fa-trash'></i> Delete Selected (<span x-text='selectedFolders.length'>0</span>)
                    </button>
                </div>
            </form>
        </div>
        <script src='https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js' defer></script>
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
            $this->error("No folders selected.");
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

        $this->message("Successfully deleted $successCount folders.");
        $this->wire('session')->redirect("../");
    }
}