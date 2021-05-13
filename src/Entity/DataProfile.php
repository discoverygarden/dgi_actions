<?php

namespace Drupal\dgi_actions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\dgi_actions\Plugin\DataProfileInterface as ProfileInterface;

/**
 * Defines the Data Profile setting entity.
 *
 * @ConfigEntityType(
 *   id = "dgiactions_dataprofile",
 *   label = @Translation("Data Profiles"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\dgi_actions\DataProfileListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dgi_actions\Form\DataProfileForm",
 *       "edit" = "Drupal\dgi_actions\Form\DataProfileForm",
 *       "delete" = "Drupal\dgi_actions\Form\DataProfileDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\dgi_actions\IdentifierHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "data_profile",
 *   admin_permission = "administer dgi_actions",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/dgi_actions/data_profile/add",
 *     "edit-form" = "/admin/config/dgi_actions/data_profile/{dgiactions_dataprofile}/edit",
 *     "delete-form" = "/admin/config/dgi_actions/data_profile/{dgiactions_dataprofile}/delete",
 *     "collection" = "/admin/config/dgi_actions/data_profile"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "entity",
 *     "bundle",
 *     "data_profile",
 *     "data",
 *   }
 * )
 */
class DataProfile extends ConfigEntityBase implements DataProfileInterface {

  /**
   * The Data Profile setting ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Data Profile setting label.
   *
   * @var string
   */
  protected $label;

  /**
   * The entity used in the Data Profile..
   *
   * @var string
   */
  protected $entity;

  /**
   * The bundle used in the Data Profile.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The data profile entity ID being used.
   *
   * @var string
   */
  protected $data_profile;

  /**
   * The Data Profile setting fields.
   *
   * @var array
   */
  protected $data;

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
  public function getDataProfilePlugin(): ?ProfileInterface {
    return \Drupal::service('plugin.manager.data_profile')->createInstance($this->data_profile, $this->data);

  }

  /**
   * {@inheritdoc}
   */
  public function getData(): array {
    // Allow the data profile to modify the data going out before it's returned.
    return $this->getDataProfilePlugin()->modifyData($this->data);
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    $this->data = $data;
  }

}
