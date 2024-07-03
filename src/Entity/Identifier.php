<?php

namespace Drupal\dgi_actions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines the Identifier setting entity.
 *
 * @ConfigEntityType(
 *   id = "dgiactions_identifier",
 *   label = @Translation("Identifiers"),
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
 *   admin_permission = "administer dgi_actions",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/dgi_actions/identifier/add",
 *     "edit-form" = "/admin/config/dgi_actions/identifier/{dgiactions_identifier}/edit",
 *     "delete-form" = "/admin/config/dgi_actions/identifier/{dgiactions_identifier}/delete",
 *     "collection" = "/admin/config/dgi_actions/identifier"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "entity",
 *     "bundle",
 *     "field",
 *     "service_data",
 *     "data_profile",
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
   * The Identifier setting Entity.
   *
   * @var string
   */
  protected $entity;

  /**
   * The Identifier setting Bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The Identifier setting field.
   *
   * @var string
   */
  protected $field;

  /**
   * The Identifier setting Data Profile.
   *
   * @var string
   */
  protected $data_profile;

  /**
   * The Identifier setting Service Data.
   *
   * @var string
   */
  protected $service_data;

  /**
   * {@inheritdoc}
   */
  public function getEntity(): string {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(): string {
    return $this->field;
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceData(): ?ServiceDataInterface {
    return $this->service_data ?
      \Drupal::service('entity_type.manager')->getStorage('dgiactions_servicedata')->load($this->service_data) :
      NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataProfile(): ?DataProfileInterface {
    return $this->data_profile ?
      \Drupal::service('entity_type.manager')->getStorage('dgiactions_dataprofile')->load($this->data_profile) :
      NULL;
  }

  /**
   * Helper; get the entity representing the field of the entity.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   The entity representing the field if it could be found; otherwise, NULL.
   */
  protected function getFieldEntity() : ?FieldDefinitionInterface {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    return $entity_field_manager->getFieldDefinitions($this->getEntity(), $this->getBundle())[$this->getField()] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Add the dependency on the data profile and service data entities if
    // they are here.
    if ($profile_entity = $this->getDataProfile()) {
      $this->addDependency($profile_entity->getConfigDependencyKey(), $profile_entity->getConfigDependencyName());
    }

    if ($service_entity = $this->getServiceData()) {
      $this->addDependency($service_entity->getConfigDependencyKey(), $service_entity->getConfigDependencyName());
    }

    if (($field_entity = $this->getFieldEntity()) && $field_entity instanceof FieldConfigInterface) {
      $this->addDependency($field_entity->getConfigDependencyKey(), $field_entity->getConfigDependencyName());
    }

    return $this;
  }

}
