<?php

namespace Drupal\dgi_actions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;

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
 *     "dataprofile",
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
   * The Data Profile setting description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * The Data Profile setting entity.
   *
   * @var string
   */
  protected $entity;

  /**
   * The Data Profile setting bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The Data Profile setting dataprofile.
   *
   * @var string
   */
  protected $dataprofile;

  /**
   * The Data Profile setting fields.
   *
   * @var array
   */
  protected $data;

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
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataprofile() {
    return $this->dataprofile;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    $this->data = $data;
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
