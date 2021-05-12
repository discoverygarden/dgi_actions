<?php

namespace Drupal\dgi_actions\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Data Profile plugins.
 */
interface DataProfileInterface extends PluginInspectionInterface, ConfigurableInterface, DependentPluginInterface, PluginFormInterface {
}
