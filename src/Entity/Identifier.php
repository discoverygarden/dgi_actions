<?php

namespace Drupal\dgi_actions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

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
  public function getServiceData(): ServiceDataInterface {
    return \Drupal::service('entity_type.manager')->getStorage('dgiactions_servicedata')->load($this->service_data);
  }

  /**
   * {@inheritdoc}
   */
  public function getDataProfile(): DataProfileInterface {
    return \Drupal::service('entity_type.manager')->getStorage('dgiactions_dataprofile')->load($this->data_profile);
  }

}
