<?php

namespace Drupal\dgi_actions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Service Data setting entities.
 */
interface ServiceDataInterface extends ConfigEntityInterface {

  /**
   * Sets the Data member variable.
   *
   * @param array $data
   */
  public function setData(array $data);

  /**
   * Gets the Data member variable.
   */
  public function getData();

  /**
   * Gets the Service Data Type member variable.
   */
  public function getServiceDataType();
}
