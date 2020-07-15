<?php

namespace Drupal\dgi_actions\Plugin\AbstractIdentifier

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use Drupal\Core\Http\ClientFactory;

abstract class AbstractIdentifier extends ConfigurableActionBase {

  // Expected Data to be provided:
    // Credentials
    // Shoulder/Namespace
    // Mapped Field
    // ContentType - Might not just be NODES
    // Bundle
  abstract function mint();
  abstract function getIdentifier();
  abstract function setCredentials();
}
