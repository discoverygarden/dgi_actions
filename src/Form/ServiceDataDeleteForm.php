<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete Service Data setting entities.
 */
class ServiceDataDeleteForm extends EntityConfirmFormBase {

  /**
   * The Drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ServiceDataDeleteForm {
    return new static(
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('entity.dgiactions_servicedata.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete any state that may have been here as well.
    $this->state->delete("dgi_actions.service_data.{$this->entity->id()}");
    $this->entity->delete();

    $this->messenger()->addStatus(
      $this->t('content @type: deleted @label.',
        [
          '@type' => $this->entity->bundle(),
          '@label' => $this->entity->label(),
        ]
        )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
