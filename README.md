# Filer

Terminus plugin to open Pantheon Sites using an SFTP Client

Adds a sub-command to 'site' which is called 'filer'. This opens a site in your favorite SFTP Client.

## Supported

[Transmit](https://panic.com/transmit/) (Mac only)

[Cyberduck](https://cyberduck.io/) (Mac and Windows)

[Filezilla](https://filezilla-project.org/) (Mac, Linux and Windows)

[BitKinex](http://www.bitkinex.com/) (Windows only)

[WinSCP](https://winscp.net/) (Windows only)

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

### Shortcut for FileZilla
* `terminus site filezilla --site=companysite-33 --env=dev`
* `terminus site zilla --site=companysite-33 --env=dev`

### Shortcut for BitKinex
* `terminus site bitkinex --site=companysite-33 --env=dev`
* `terminus site bit --site=companysite-33 --env=dev`

### Shortcut for WinSCP
* `terminus site winscp --site=companysite-33 --env=dev`
* `terminus site scp --site=companysite-33 --env=dev`

## Installation
For help installing, see [Terminus's Wiki](https://github.com/pantheon-systems/terminus/wiki/Plugins)

## Help
Run `terminus help site filer` for help.
