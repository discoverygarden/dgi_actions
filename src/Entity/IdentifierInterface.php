<?php

namespace Drupal\dgi_actions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Identifier setting entities.
 */
interface IdentifierInterface extends ConfigEntityInterface {

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
   * Gets the set Field.
   *
   * @return string
   *   Returns field type.
   */
  public function getField(): string;

  /**
   * Gets the set Service Data ID.
   *
   * @return string
   *   Returns Service Data ID type.
   */
  public function getServiceData(): string;

  /**
   * Gets the set Data Profile ID.
   *
   * @return string
   *   Returns Data Profile ID type.
   */
  public function getDataProfile(): string;

}
