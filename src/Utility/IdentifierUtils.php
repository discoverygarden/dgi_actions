<?php

namespace Drupal\dgi_actions\Utility;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Psr\Log\LoggerInterface;

/**
 * DGI Actions Identifier Utils.
 */
class IdentifierUtils {

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   * @param \Psr\Log\LoggerInterface $logger
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
   * Gets configured dgi_actions Identifier configs.
   *
   * @return array
   *   Returns list of configured DGI Actions Identifiers.
   */
  public function getIdentifiers() {
    $configs = $this->configFactory->listAll('dgi_actions.identifier');
    $config_options = [];
    if (!empty($configs)) {
      foreach ($configs as $config_id) {
        $config_options[$config_id] = $this->configFactory->get($config_id)->get('label');
      }
    }

    return $config_options;
  }

  /**
   * Returns associated identifier configs.
   *
   * Returns the associated Identifer, Service Data, and
   * Data Profile config values based on identifier config name.
   *
   * @param string $identifier
   *   Name of the identifier config to find associated configs.
   *
   * @return array
   *   Array of associated Identifier, Service Data,
   *   and Data Profile config values.
   */
  public function getAssociatedConfigs($identifier) {
    $configs = [];
    $identifier = $this->configFactory->get($identifier);
    if (!empty($identifier->get())) {
      $service_data = $this->configFactory->get('dgi_actions.service_data.' . $identifier->get('service_data.id'));
      $data_profile = $this->configFactory->get('dgi_actions.data_profile.' . $identifier->get('data_profile.id'));

      $configs['identifier'] = $identifier;
      $configs['service_data'] = (!empty($service_data)) ? $service_data : [];
      $configs['data_profile'] = (!empty($data_profile)) ? $data_profile : [];

    }

    return $configs;
  }

}
