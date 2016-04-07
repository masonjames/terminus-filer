<?php

namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Models\Collections\Sites;

/**
 * Say hello to the user
 *
 * @command site
 */
class FilerCommand extends TerminusCommand {
  /**
   * Object constructor
   *
   * @param array $options
   * @return PantheonAliases
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
     parent::__construct($options);
     $this->sites = new Sites();
  }

   /**
   * Connects SFTP Client to the Site
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to Use
   *
   * [--env=<env>]
   * : Environment to clear
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

    $app = $assoc_args['a'];
    $bundle = $assoc_args['b'];
    $app_args = $assoc_args['app_args'];

    $type = ($app == '' ? 'bundle' : 'app');
    $app = ($app == '' ? $bundle : $app);
    $app_args = ($app_args == '' ? $app_args : '--args ' . $app_args);

    $env_id   = $this->input()->env(array('args' => $assoc_args, 'site' => $site));
    $environment = $site->environments->get($env_id);
    $connection_info = $environment->connectionInfo();

    $connection = $connection_info['sftp_url'];

    $this->log()->info('Opening {site} in {app}', array('site' => $site->get('name'), 'app' => $app));

    // Wake the Site
    $environment->wake();

    if($type == 'bundle')
      $type = 'b';
    else
      $type = 'a';

    $connect = 'open \-%s %s %s %s';

    $command = sprintf($connect, $type, $app, $app_args, $connection);
    exec($command);
  }


   /**
   * Connects SFTP Client to the Site using Transmit
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to Use
   *
   * [--env=<env>]
   * : Environment to clear
   *
   * ## EXAMPLES
   *  terminus site transmit --site=test
   *
   * @subcommand transmit
   * @alias panic
   */
   public function transmit($args, $assoc_args) {
     $assoc_args['b'] = 'com.panic.transmit';
     $this->filer($args, $assoc_args);
   }

   /**
   * Connects SFTP Client to the Site using Cyberduck
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to Use
   *
   * [--env=<env>]
   * : Environment to clear
   *
   * ## EXAMPLES
   *  terminus site cyberduck --site=test
   *
   * @subcommand cyberduck
   * @alias duck
   */
   public function cyberduck($args, $assoc_args) {
     $assoc_args['b'] = 'ch.sudo.cyberduck';

     $this->filer($args, $assoc_args);
   }
   
   /**
   * Connects SFTP Client to the Site using FileZilla
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to Use
   *
   * [--env=<env>]
   * : Environment to clear
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
