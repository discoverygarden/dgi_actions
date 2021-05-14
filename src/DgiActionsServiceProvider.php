<?php

namespace Drupal\dgi_actions;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the context repository service.
 */
class DgiActionsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('context.repository');
    $definition->setClass('Drupal\dgi_actions\ResettableContextRepository');
  }

}
