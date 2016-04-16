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

    $app_args = isset($assoc_args['app_args']) ? $assoc_args['app_args'] : '';

    $type = ($app == '' ? 'b' : 'a');
    $app = ($app == '' ? $bundle : $app);

    $env_id = $this->input()->env(array('args' => $assoc_args, 'site' => $site));
    $environment = $site->environments->get($env_id);
    $connection_info = $environment->connectionInfo();

    $connection = $connection_info['sftp_url'];

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
        $connect = '%s %s %s %s %s';
        $redirect = '> /dev/null 2> /dev/null &';
        $command = sprintf($connect, $app, $app_args, $connection, $redirect);
        break;
      case 'WIN':
        $connect = '%s %s %s %s %s';
        $redirect = '> NUL 2> NUL';
        $command = sprintf($connect, $app, $app_args, $connection, $redirect);
        break;
    }

    // Wake the Site
    $environment->wake();

    // Open the Site in app/bundle
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
          break;
      case 'WIN':
        $assoc_args['a'] = CYBERDUCK;
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
}
