<?php

namespace Drupal\dgi_actions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Identifier setting entity.
 *
 * @ConfigEntityType(
 *   id = "identifiers_identifier",
 *   label = @Translation("Identifier"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\dgi_actions\IdentifierListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dgi_actions\Form\IdentifierForm",
 *       "edit" = "Drupal\dgi_actions\Form\IdentifierForm",
 *       "delete" = "Drupal\dgi_actions\Form\IdentifierDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\dgi_actions\IdentifierHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "identifier",
 *   admin_permission = "administer configuration split",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/dgi_actions/identifier/add",
 *     "edit-form" = "/admin/config/dgi_actions/identifier/{identifiers_identifier}/edit",
 *     "delete-form" = "/admin/config/dgi_actions/identifier/{identifiers_identifier}/delete",
 *     "collection" = "/admin/config/dgi_actions/identifier"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "field",
 *   }
 * )
 */
class Identifier extends ConfigEntityBase implements IdentifierInterface {

  /**
   * The Identifier setting ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Identifier setting label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Identifier setting description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * The Identifier setting field.
   *
   * @var string
   */
  protected $field;

  /**
   * The weight of the identifier..
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * Gets the Description value.
   *
   * @return string|null
   *  Returns the description variable.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  protected function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);
    // Clear the config_filter plugin cache.
    \Drupal::service('plugin.manager.config_filter')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type, array $entities) {
    parent::invalidateTagsOnDelete($entity_type, $entities);
    // Clear the config_filter plugin cache.
    \Drupal::service('plugin.manager.config_filter')->clearCachedDefinitions();
  }

}
