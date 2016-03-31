# Filer

Terminus Plugin to open Pantheon SFTP Connection in your SFTP Clients

Adds a sub-command to 'site' which is called 'filer'. This opens a site in your favorite SFTP Client. Currently at the moment Panic's Transmit and Cyberduck have been built in as shortcuts.

*This plugin will currently only work on OS X*

## Examples
### Reference Application Name
* `terminus site filer --site=companysite-33 --env=dev --a=transmit`

### Reference Application Bundle Name
* `terminus site filer --site=companysite-33 --env=dev --b=com.panic.transmit`

### Shortcut for Panic's Transmit
* `terminus site transmit --site=companysite-33 --env=dev`
* `terminus site panic --site=companysite-33 --env=dev`

### Shortcut for Cyberduck
* `terminus site cyberduck --site=companysite-33 --env=dev`
* `terminus site duck --site=companysite-33 --env=dev`

## Installation
For help installing, see [Terminus's Wiki](https://github.com/pantheon-systems/terminus/wiki/Plugins)

## Help
Run `terminus help site filer` for help.
