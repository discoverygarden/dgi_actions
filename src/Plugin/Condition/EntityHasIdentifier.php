<?php

namespace Drupal\dgi_actions\Plugin\Condition;

use Drupal\node\Plugin\Condition\NodeType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\islandora\IslandoraUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a condition to check an Entity for an existing persistent identifier.
 *
 * @Condition(
 *   id = "dgi_actions_entity_has_persistent_identifier",
 *   label = @Translation("Entity persistent identifier heck"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", required = TRUE, label = @Translation("Entity")),
 *   }
 * )
 */
class MediaParentNodeHasBundle extends NodeType {

  /**
   * Islandora utils.
   *
   * @var Drupal\islandora\IslandoraUtils
   */
  protected $utils;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\islandora\IslandoraUtils $utils
   *   An instance of the IslandoraUtils service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_type_storage
   *   The entity storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, IslandoraUtils $utils, EntityStorageInterface $node_type_storage) {
    parent::__construct($node_type_storage, $configuration, $plugin_id, $plugin_definition);
    $this->utils = $utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('islandora.utils'),
      $container->get('entity.manager')->getStorage('node_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Is 'this' the entity, context, or thing activiating the condition?
      // Have to get the expected identifier field somehow.
    /* What I tried to write.
    $entity_identifier = $this->get('field_ark_identifier')->getValue();
    if ($entity_identifier) {
      return parent::evaluate();
    }*/

    // Original Implementation.
    /*
    $media = $this->getContextValue('media');
    if (!$media) {
      return FALSE;
    }

    $node = $this->utils->getParentNode($media);
    if (!$node) {
      return FALSE;
    }
    $this->setContextValue('node', $node);

    return parent::evaluate();*/
  }

}
