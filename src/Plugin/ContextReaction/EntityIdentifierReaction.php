<?php

namespace Drupal\dgi_actions\Plugin\ContextReaction;

use Drupal\islandora\PresetReaction\PresetReaction;
use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposes one or more configured dgi_actions actions as a Context reaction.
 */
class EntityIdentifierReaction extends ContextReactionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Action storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $actionStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $action_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->actionStorage = $action_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('action')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Perform a pre-configured identifier action.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(EntityInterface $entity = NULL) {
    $config = $this->getConfiguration();
    $action_ids = $config['actions'];
    foreach ($action_ids as $action_id) {
      $action = $this->actionStorage->load($action_id);
      $action->execute([$entity]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $actions = $this->actionStorage->loadMultiple();
    foreach ($actions as $action) {
      if ($action->getType() === 'entity') {
        $options[$action->id()] = $action->label();
      }
    }
    $config = $this->getConfiguration();

    $form['actions'] = [
      '#title' => $this->t('Entity Actions'),
      '#description' => $this->t('Pre-configured entity actions to execute.  Multiple actions may be selected by shift or ctrl clicking.'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => isset($config['actions']) ? $config['actions'] : '',
      '#size' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration(['actions' => $form_state->getValue('actions')]);
  }

}
