# ProcessModuleCleaner

ProcessModuleCleaner is a utility module for ProcessWire that helps administrators keep their `site/modules/` directory clean. It identifies and allows for the deletion of orphaned or backup module directories that start with a dot (e.g., `.ModuleName`).

These directories are often created by ProcessWire during module upgrades or uninstalls as backups and can accumulate over time.

## Features

- **Detection**: Scans the modules directory for folders starting with a dot (`.`).
- **Overview**: Lists found folders with their last modified date.
- **Cleanup**: Provides an interface to select and permanently delete these folders.
- **Safety**: Protected by ProcessWire's permission system (`module-admin`) and CSRF tokens.

## Usage

1.  Install the module.
2.  Navigate to **Setup > Module Cleaner** in the ProcessWire admin.
3.  If any orphaned folders are found, they will be listed.
4.  Select the folders you want to remove and click **Delete Selected**.
