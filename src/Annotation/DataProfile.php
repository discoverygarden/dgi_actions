<?php

namespace Drupal\dgi_actions\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Data profile item annotation object.
 *
 * @see \Drupal\dgi_actions\Plugin\DataProfileManager
 * @see plugin_api
 *
 * @Annotation
 */
class DataProfile extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
