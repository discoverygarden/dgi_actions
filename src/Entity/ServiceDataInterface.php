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
  public function setData(array $data): void;

  /**
   * Gets the data member variable with any state information contained.
   *
   * @return array
   *   Returns the data array from the entity.
   */
  public function getData(): array;

  /**
   * Gets the Service Data Type member variable.
   *
   * @return string|null
   *   Returns the service data type plugin ID if it exists.
   */
  public function getServiceDataType(): ?string;

}
