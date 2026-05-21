# ProcessModuleCleaner

ProcessModuleCleaner is a utility module for ProcessWire that helps administrators keep their `site/modules/` directory and root directory clean. It identifies and allows for the deletion of orphaned or backup module directories (e.g., `.ModuleName`) as well as old ProcessWire core folders and backup files (e.g. `.wire-3.0.xxx`, `.index-3.0.xxx.php`, `htaccess-3.0.xxx.txt`) that remain after core upgrades.

These directories and files are often created by ProcessWire during upgrades or uninstalls as backups and can accumulate over time, wasting disk space.

## Features

- **Module Cleanup**: Scans the modules directory for folders starting with a dot (`.`).
- **Core Cleanup**: Scans the root directory for old core backups (`.wire-*` folders, `.index-*.php` files, and `htaccess-*.txt` files).
- **Disk Space Stats**: Shows the storage size of core backups to see how much space can be freed.
- **Overview**: Lists found folders and files with their last modified date.
- **Cleanup**: Provides an easy-to-use interface to select and permanently delete these items.
- **Safety**: Protected by ProcessWire's permission system (`module-admin`), CSRF tokens, and strict file path/pattern validation.

## Installation

1.  Download or clone this repository into your ProcessWire site/modules directory.
2.  In the ProcessWire admin, go to Modules > Refresh.
3.  Find 'Module Folder Cleaner' in the list (in Process) and click 'Install'.

## Usage

1.  Navigate to **Setup > Module Cleaner** in the ProcessWire admin.
2.  If any orphaned module folders or old core files/folders are found, they will be listed.
3.  Select the items you want to remove and click **Delete Selected**.

![ProcessModuleCleaner Module](https://github.com/markusthomas/ProcessModuleCleaner/blob/main/thumb.png)

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Author

Markus Thomas
