<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigSplitEntityForm.
 *
 * @package Drupal\dgi_actions\Form
 */
class DataProfileForm extends EntityForm {

  /**
   * The drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal\Core\Extension\ThemeHandler definition.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   */
  public function __construct(StateInterface $state, ThemeHandlerInterface $themeHandler) {
    $this->state = $state;
    $this->themeHandler = $themeHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $field_map = \Drupal::entityManager()->getfieldMap();

    $options_map = [];
    foreach (array_keys($field_map) as $entity_type) {
      $options_map = array_merge($options_map, $field_map[$entity_type]);
    }

    $pruned_options = [];
    foreach ($options_map as $key => $value) {
      if (strpos($key, 'field_') === 0) {
        $pruned_options[$key] = $key;
      }
    }

    /** @var \Drupal\dgi_actions\Entity\IdentifierInterface $config */
    $config = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $config->label(),
      '#description' => $this->t("Label for the Identifier setting."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dgi_actions\Entity\Identifier::load',
      ],
    ];
    $form['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Field'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($config->get('field')) ?: $this->t('- None -'),
      '#options' => $pruned_options,
      '#description' => $this->t('The entity field that the identifier will be minted into.'),
      '#required' => TRUE,
    ];
    $form['service_data'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#title' => $this->t('Service Data'),
      '#description' => $this->t('Service Data.'),
      '#default_value' => $config->get('service_data'),
    ];
    $form['data_profile'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#title' => $this->t('Data Profile'),
      '#description' => $this->t('Data Profile.'),
      '#default_value' => $config->get('data_profile'),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Describe this Identifier setting. The text will be displayed on the <em>Identifier settings</em> list page.'),
      '#default_value' => $config->get('description'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  /**
   * Filter text input for valid configuration names (including wildcards).
   *
   * @param string|string[] $text
   *   The configuration names, one name per line.
   *
   * @return string[]
   *   The array of configuration names.
   */
  protected function filterConfigNames($text) {
    if (!is_array($text)) {
      $text = explode("\n", $text);
    }

    foreach ($text as &$config_entry) {
      $config_entry = strtolower($config_entry);
    }

    // Filter out illegal characters.
    return array_filter(preg_replace('/[^a-z0-9_\.\-\*]+/', '', $text));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $identifier = $this->entity;
    $status = $identifier->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Identifier setting.', [
          '%label' => $identifier->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Identifier setting.', [
          '%label' => $identifier->label(),
        ]));
    }
    $form_state->setRedirectUrl($identifier->toUrl('collection'));
  }

}
