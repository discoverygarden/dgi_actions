<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Basic implementation for minting an identifier.
 */
abstract class MintIdentifier extends IdentifierAction {

  /**
   * Gets the data of the fields provided by the data_profile config.
   *
   * @throws \InvalidArgumentException
   *   If the Entity doesn't have the configured identifier field.
   *
   * @return array
   *   The returned data structured in a key value pair
   *   based on the configured data_profile.
   */
  protected function getFieldData(): array {

    $data = [];
    $data_profile = $this->getIdentifier()->getDataProfile();
    if ($data_profile) {
      foreach ($data_profile->getData() as $key => $field) {
        if ($this->entity->hasField($field)) {
          $entity_field = $this->entity->get($field);
          if ($entity_field->isEmpty()) {
            continue;
          }
          if ($entity_field instanceof EntityReferenceFieldItemList) {
            $data[$key] = $entity_field->entity->label();
          }
          else {
            $data[$key] = $entity_field->getString();
          }
        }
      }
    }
    return $data;
  }

  /**
   * Mints the identifier.
   *
   * @return string
   *   The identifier returned by the minting.
   */
  abstract protected function mint(): string;

  /**
   * Sets the Entity field with the Identifier.
   *
   * @param string $identifier_uri
   *   The identifier formatted as a URL.
   */
  protected function setIdentifierField(string $identifier_uri) {
    if ($identifier_uri) {
      $field = $this->identifier->get('field');
      if (!empty($field) && $this->entity->hasField($field)) {
        $this->entity->set($field, $identifier_uri);
      }
      else {
        $this->logger->error('Error with Entity Identifier field. The identifier was not set to the entity.');
      }
    }
    else {
      $this->logger->error('The identifier is missing and was not set to the entity.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    if ($entity instanceof FieldableEntityInterface) {
      try {
        $this->entity = $entity;
        if ($this->entity && $this->identifier) {
          $this->setIdentifierField($this->mint());
          if (array_key_exists('save_entity', $this->configuration) && $this->configuration['save_entity']) {
            $this->entity->save();
          }
        }
        else {
          $this->logger->error('Minting failed for @type/@id: Entity or Configs were not properly set.', [
            '@type' => $this->getEntity()->getEntityTypeId(),
            '@id' => $this->getEntity()->id(),
          ]);
        }
      }
      catch (UndefinedLinkTemplateException $ulte) {
        $this->logger->warning('Minting failed for @type/@id: Error retrieving Entity URL: @errorMessage', [
          '@type' => $this->getEntity()->getEntityTypeId(),
          '@id' => $this->getEntity()->id(),
          '@errorMessage' => $ulte->getMessage(),
        ]);
      }
      catch (\InvalidArgumentException $iae) {
        $this->logger->error('Minting failed for @type/@id: Configured field not found on Entity: @iae', [
          '@type' => $this->getEntity()->getEntityTypeId(),
          '@id' => $this->getEntity()->id(),
          '@iae' => $iae->getMessage(),
        ]);
      }
      catch (\Exception $e) {
        $this->logger->error('Minting failed for @type/@id: Error: @exception', [
          '@type' => $this->getEntity()->getEntityTypeId(),
          '@id' => $this->getEntity()->id(),
          '@exception' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * Adds the `save_entity` configuration option.
   *
   * This option, when true, will save the entity as part of the action.
   *
   * The default FALSE option is for when the action is triggered
   * as a context reaction.
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'save_entity' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['save_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save Entity'),
      '#default_value' => $this->configuration['save_entity'],
      '#description' => $this->t('Save the entity when populating the identifier field. Do not use if triggering from a hook or context reaction!'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['save_entity'] = $form_state->getValue('save_entity');
  }

}
