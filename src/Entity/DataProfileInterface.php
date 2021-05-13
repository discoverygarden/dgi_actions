<?php

namespace Drupal\dgi_actions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Data Profile setting entities.
 */
interface DataProfileInterface extends ConfigEntityInterface {

  /**
   * Gets the set Entity type.
   *
   * @return string
   *   Returns entity type.
   */
  public function getEntity(): string;

  /**
   * Gets the set Bundle type.
   *
   * @return string
   *   Returns bundle type.
   */
  public function getBundle(): string;

  /**
   * Gets the set Data Profile.
   *
   * @return string|null
   *   Returns the data profile plugin ID if it exists.
   */
  public function getDataProfile(): ?string;

  /**
   * Gets the set Data.
   *
   * @return array
   *   Returns data array.
   */
  public function getData(): array;

  /**
   * Sets the set Data.
   *
   * @param array $data
   *   Data array to be written to the config.
   */
  public function setData(array $data);

}
