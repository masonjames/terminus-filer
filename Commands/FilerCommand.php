<?php

namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Collections\Sites;

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

    $supported_apps = array(
      '',
      'filezilla',
    );
    $app = isset($assoc_args['a']) ? $assoc_args['a'] : '';
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
    $app_args = ($app_args == '' ? $app_args : '--args ' . $app_args);

    $env_id = $this->input()->env(array('args' => $assoc_args, 'site' => $site));
    $environment = $site->environments->get($env_id);
    $connection_info = $environment->connectionInfo();

    $connection = $connection_info['sftp_url'];

    $this->log()->info('Opening {site} in {app}', array('site' => $site->get('name'), 'app' => $app));

    // Operating system specific checks.
    $os = strtoupper(substr(PHP_OS, 0, 3));
    switch ($os) {
      case 'DAR':
        $connect = 'open \-%s %s %s %s';
        $command = sprintf($connect, $type, $app, $app_args, $connection);
        break;
      case 'LIN';
        $connect = '%s %s';
        $command = sprintf($connect, $app, $connection);
        break;
      case 'WIN':
        $app = "\"C:\\Program Files (x86)\\FileZilla FTP Client\\{$app}\"";
        $connect = '%s %s';
        $command = sprintf($connect, $app, $connection);
        break;
      default:
        $this->failure('Operating system not supported.');
    }

    // Wake the Site.
    $environment->wake();

    // Open the Site in app/bundle.
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
     $os = strtoupper(substr(PHP_OS, 0, 3));
     if ($os == 'DAR') {
       $assoc_args['b'] = 'com.panic.transmit';
       $this->filer($args, $assoc_args);
     }
     else {
       $this->failure('Operating system not supported.');
     }
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
     $os = strtoupper(substr(PHP_OS, 0, 3));
     if ($os == 'DAR') {
       $assoc_args['b'] = 'ch.sudo.cyberduck';
       $this->filer($args, $assoc_args);
     }
     else {
       $this->failure('Operating system not supported.');
     }
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
     $assoc_args['a'] = 'filezilla';
     $assoc_args['app_args'] = '-l ask';
     $this->filer($args, $assoc_args);
   }
}
