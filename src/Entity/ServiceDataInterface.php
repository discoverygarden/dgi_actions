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
   *   Data array to be written to the config.
   */
  public function setData(array $data);

  /**
   * Gets the Data member variable.
   *
   * @return array
   *   Returns the data array from the config.
   */
  public function getData();

  /**
   * Gets the Service Data Type member variable.
   *
   * @return string
   *   Returns the service data type config id.
   */
  public function getServiceDataType();

}
