<?php

namespace Drupal\dgi_actions\Utility;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Psr\Log\LoggerInterface;

class IdentifierUtils {

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config_factory;

  /**
   * Logger.
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param Drupal\Core\Config\ConfigFactory
   *   Config factory.
   * @param Psr\Log\LoggerInterface $logger
   *   Logger.
     */
  public function __construct(
    ConfigFactory $config_factory,
    LoggerInterface $logger
  ) {
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.channel.dgi_actions')
    );
  }

  /**
   * Put this in a Utils File/Class
   *
   * @return Array $config_options | String 'No Identifiers Configured'
   *   Returns list of configured DGI Actions Identifiers or a string indicating identifiers not configured.
   */
  public function getIdentifiers() {
    $configs = $this->configFactory->listAll('dgi_actions.identifier');
    if (!empty($configs)) {
      $config_options = [];
      foreach ($configs as $config_id) {
        $config_options[$config_id] = $this->configFactory->get($config_id)->get('label');
      }
      return $config_options;
    }

    return 'No Identifiers Configured';
  }

  /**
   * Returns associated Identifer, Credentials, and Data Profile config values based on identifier config name. 
   *
   * @param String $identifier
   *   Name of the identifier config to find associated configs.
   * @return array $configs
   *   Array of associated Identifier, Credential, and Data Profile config values.
   */
  public function getAssociatedConfigs($identifier) {
    $configs = [];
    $identifier = $this->configFactory->get($identifier);
    if (!empty($identifier->get())) {
      $creds = $this->configFactory->get('dgi_actions.credentials.'.$identifier->get('identifier_id'));
      $data_profile = $this->configFactory->get('dgi_actions.data_profile.'.$identifier->get('data_profile.id'));

      $configs['identifier'] = $identifier;
      $configs['credentials'] = (!empty($creds)) ? $creds : 'Credentials not Configured';
      $configs['data_profile'] = (!empty($data_profile)) ? $data_profile : 'Data Profile not Configured';

      return $configs;
    }

   return FALSE;
  }

}
