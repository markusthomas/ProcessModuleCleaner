# ProcessModuleCleaner

ProcessModuleCleaner is a utility module for ProcessWire that helps administrators keep their `site/modules/` directory clean. It identifies and allows for the deletion of orphaned or backup module directories that start with a dot (e.g., `.ModuleName`).

These directories are often created by ProcessWire during module upgrades or uninstalls as backups and can accumulate over time.

## Features

- **Detection**: Scans the modules directory for folders starting with a dot (`.`).
- **Overview**: Lists found folders with their last modified date.
- **Cleanup**: Provides an interface to select and permanently delete these folders.
- **Safety**: Protected by ProcessWire's permission system (`module-admin`) and CSRF tokens.

## Installation

1.  Download or clone this repository into your ProcessWire site/modules directory.
2.  In the ProcessWire admin, go to Modules > Refresh.
3.  Find 'Module Folder Cleaner' in the list (in Process) and click 'Install'.

## Usage

1.  Navigate to **Setup > Module Cleaner** in the ProcessWire admin.
2.  If any orphaned folders are found, they will be listed.
3.  Select the folders you want to remove and click **Delete Selected**.

![ProcessModuleCleaner Module](https://github.com/markusthomas/ProcessModuleCleaner/blob/main/thumb.png)

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Author

Markus Thomas
