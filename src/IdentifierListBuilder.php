<?php

namespace Drupal\dgi_actions;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Identifier setting entities.
 */
class IdentifierListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Identifier');
    $header['id'] = $this->t('Machine name');
    $header['entity'] = $this->t('Entity');
    $header['bundle'] = $this->t('Bundle');
    $header['field'] = $this->t('Field');
    $header['service_data'] = $this->t('Service Data');
    $header['data_profile'] = $this->t('Data Profile');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['entity'] = $entity->get('entity');
    $row['bundle'] = $entity->get('bundle');
    $row['field'] = $entity->get('field');
    $service_data = $entity->getServiceData();
    $data_profile = $entity->getDataProfile();
    $row['service_data'] = $service_data ? $service_data->toLink(NULL, 'edit-form') : '';
    $row['data_profile'] = $data_profile ? $data_profile->toLink(NULL, 'edit-form') : '';

    return $row + parent::buildRow($entity);
  }

}
