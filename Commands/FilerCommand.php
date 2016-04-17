<?php

namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Collections\Sites;
use Terminus\Utils;

// Get environment variables, if available
$bitkinex = getenv('TERMINUS_FILER_BITKINEX_CMD');
$cyberduck = getenv('TERMINUS_FILER_CYBERDUCK_CMD');
$filezilla = getenv('TERMINUS_FILER_FILEZILLA_CMD');
$winscp = getenv('TERMINUS_FILER_WINSCP_CMD');
// Operating system specific checks
define('OS', strtoupper(substr(PHP_OS, 0, 3)));
switch (OS) {
  case 'DAR':
  case 'LIN':
    if (!$filezilla) {
      $filezilla = 'filezilla';
    }
    define('FILEZILLA', $filezilla);
    define('SUPPORTED_APPS', serialize(array(
      '',
      FILEZILLA,
    )));
      break;
  case 'WIN':
    $program_files = 'Program Files';
    $arch = getenv('PROCESSOR_ARCHITECTURE');
    if ($arch == 'x86') {
      $program_files = 'Program Files (x86)';
    }
    if (!$bitkinex) {
      $bitkinex = "C:\\{$program_files}\\BitKinex\\bitkinex.exe";
    }
    if (!$cyberduck) {
      $cyberduck = "C:\\{$program_files}\\Cyberduck\\Cyberduck.exe";
    }
    if (!$filezilla) {
      $filezilla = "C:\\{$program_files}\\FileZilla FTP Client\\filezilla.exe";
    }
    if (!$winscp) {
      $winscp = "C:\\{$program_files}\\WinSCP\\WinSCP.exe";
    }
    define('BITKINEX',  "\"$bitkinex\" browse");
    define('CYBERDUCK', "\"$cyberduck\"");
    define('FILEZILLA', "\"$filezilla\"");
    define('WINSCP',    "\"$winscp\"");
    define('SUPPORTED_APPS', serialize(array(
      '',
      BITKINEX,
      CYBERDUCK,
      FILEZILLA,
      WINSCP,
    )));
      break;
  default:
    $this->failure('Operating system not supported.');
}

/**
 * Opens the Site using an SFTP Client
 *
 * @command site
 */
class FilerCommand extends TerminusCommand {
  /**
   * Object constructor
   *
   * @param array $options
   * @return FilerCommand
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
     parent::__construct($options);
     $this->sites = new Sites();
  }

  /**
   * Opens the Site using an SFTP Client
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to Use
   *
   * [--env=<env>]
   * : Environment
   *
   * [--a=<app>]
   * : Application to Open (optional)
   *
   * [--b=<bundle>]
   * : Bundle Identifier (optional)
   *
   * [--p=<true|false>]
   * : Whether to persist the connection
   *
   * ## EXAMPLES
   *  terminus site filer --site=test
   *
   * @subcommand filer
   * @alias file
   */
  public function filer($args, $assoc_args) {
    $site = $this->sites->get(
      $this->input()->siteName(array('args' => $assoc_args))
    );

    $app = isset($assoc_args['a']) ? $assoc_args['a'] : '';
    $supported_apps = unserialize(SUPPORTED_APPS);
    if (!in_array($app, $supported_apps)) {
      $this->failure('App not supported.');
    }

    $supported_bundles = array(
      '',
      'com.panic.transmit',
      'ch.sudo.cyberduck',
    );
    $bundle = isset($assoc_args['b']) ? $assoc_args['b'] : '';
    if (!in_array($bundle, $supported_bundles)) {
      $this->failure('Bundle not supported.');
    }

    $persist = isset($assoc_args['p']) ? $assoc_args['p'] : false;

    $app_args = isset($assoc_args['app_args']) ? $assoc_args['app_args'] : '';

    $type = ($app == '' ? 'b' : 'a');
    $app = ($app == '' ? $bundle : $app);

    $env = $this->input()->env(array('args' => $assoc_args, 'site' => $site));
    $environment = $site->environments->get($env);
    $connection_info = $environment->connectionInfo();

    if ($persist) {
      $name = $env . '-' . $site->get('name');
      $id = substr(md5($name), 0, 8) . '-' . $site->get('id');
      $connection_info['id'] = $id;
      $connection_info['domain'] = $name . '.pantheon.io';
      $connection_info['timestamp'] = time();
      if (stripos($app, 'cyberduck')) {
        switch (OS) {
          case 'DAR':
            $bookmark_file = getenv('HOME') . '/Library/Application Support/Cyberduck/' . $id . '.duck';
              break;
          case 'WIN':
            $bookmark_file = getenv('HOMEPATH') . '\\AppData\\Roaming\\Cyberduck\\Bookmarks\\' . $id . '.duck';
              break;
          default:
            $this->failure('Operating system not supported.');
        }
        $bookmark_xml = $this->getBookmarkXml($connection_info);
        if ($this->writeXml($bookmark_file, $bookmark_xml)) {
          $connection = $bookmark_file;
        }
      }
    } else {
      $connection = $connection_info['sftp_url'];
    }

    $this->log()->info('Opening {site} in {app}', array('site' => $site->get('name'), 'app' => $app));

    // Operating system specific checks
    switch (OS) {
      case 'DAR':
        if ($app_args) {
          $app_args = "--args $app_args";
        }
        $connect = 'open \-%s %s %s %s %s';
        $redirect = '> /dev/null 2> /dev/null &';
        $command = sprintf($connect, $type, $app, $app_args, $connection, $redirect);
        break;
      case 'LIN';
        $connect = '%s %s %s %s';
        $redirect = '> /dev/null 2> /dev/null &';
        $command = sprintf($connect, $app, $app_args, $connection, $redirect);
        break;
      case 'WIN':
        $connect = 'start /b %s %s %s';
        $command = sprintf($connect, $app, $app_args, $connection);
        break;
    }

    // Wake the Site
    $environment->wake();

    // Open the Site in app/bundle
echo "$command\n";
    exec($command);
  }

  /**
   * Opens the Site using Transmit SFTP Client
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to Use
   *
   * [--env=<env>]
   * : Environment
   *
   * ## EXAMPLES
   *  terminus site transmit --site=test
   *
   * @subcommand transmit
   * @alias panic
   */
  public function transmit($args, $assoc_args) {
    if (OS != 'DAR') {
      $this->failure('Operating system not supported.');
    }
    $assoc_args['b'] = 'com.panic.transmit';
    $this->filer($args, $assoc_args);
  }

  /**
   * Opens the Site using Cyberduck SFTP Client
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to Use
   *
   * [--env=<env>]
   * : Environment
   *
   * ## EXAMPLES
   *  terminus site cyberduck --site=test
   *
   * @subcommand cyberduck
   * @alias duck
   */
  public function cyberduck($args, $assoc_args) {
    switch (OS) {
      case 'DAR':
        $assoc_args['b'] = 'ch.sudo.cyberduck';
        $assoc_args['p'] = true;
          break;
      case 'WIN':
        $assoc_args['a'] = CYBERDUCK;
        $assoc_args['p'] = true;
          break;
      case 'LIN':
      default:
        $this->failure('Operating system not supported.');
    }
    $this->filer($args, $assoc_args);
  }

  /**
   * Opens the Site using FileZilla SFTP Client
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to Use
   *
   * [--env=<env>]
   * : Environment
   *
   * ## EXAMPLES
   *  terminus site filezilla --site=test
   *
   * @subcommand filezilla
   * @alias zilla
   */
  public function filezilla($args, $assoc_args) {
    $assoc_args['a'] = FILEZILLA;
    $assoc_args['app_args'] = '-l ask';
    $this->filer($args, $assoc_args);
  }

  /**
   * Opens the Site using BitKinex SFTP Client
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to Use
   *
   * [--env=<env>]
   * : Environment
   *
   * ## EXAMPLES
   *  terminus site bitkinex --site=test
   *
   * @subcommand bitkinex
   * @alias bit
   */
  public function bitkinex($args, $assoc_args) {
    if (!Utils\isWindows()) {
      $this->failure('Operating system not supported.');
    }
    $assoc_args['a'] = BITKINEX;
    $this->filer($args, $assoc_args);
  }

  /**
   * Opens the Site using WinSCP SFTP Client
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to Use
   *
   * [--env=<env>]
   * : Environment
   *
   * ## EXAMPLES
   *  terminus site winscp --site=test
   *
   * @subcommand winscp
   * @alias scp
   */
  public function winscp($args, $assoc_args) {
    if (!Utils\isWindows()) {
      $this->failure('Operating system not supported.');
    }
    $assoc_args['a'] = WINSCP;
    $this->filer($args, $assoc_args);
  }

  /**
   * XML for Cyberduck bookmark file
   *
   * @param array Connection information
   * @return string XML bookmark file content
   */
  private function getBookmarkXml($ci) {
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
  <key>Protocol</key>
  <string>sftp</string>
  <key>Nickname</key>
  <string>{$ci['domain']}</string>
  <key>UUID</key>
  <string>{$ci['id']}</string>
  <key>Hostname</key>
  <string>{$ci['sftp_host']}</string>
  <key>Port</key>
  <string>{$ci['git_port']}</string>
  <key>Username</key>
  <string>{$ci['sftp_username']}</string>
  <key>Path</key>
  <string></string>
  <key>Access Timestamp</key>
  <string>{$ci['timestamp']}</string>
</dict>
</plist>
XML;
  }

  /**
   * Write the XML to the configuration file
   *
   * @param string $file XML configuration file
   * @param string $data XML configuration data
   * @return bool True if writing to the file was successful
   */
  private function writeXml($file, $data) {
    try {
      $handle = fopen($file, "w");
      fwrite($handle, $data);
      fclose($handle);
    } catch (Exception $e) {
      $this->failure($e->getMessage());
      return false;
    }
    return true;
  }
}
