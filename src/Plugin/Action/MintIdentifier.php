<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\rules\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Basic implementation for minting an identifier.
 */
abstract class MintIdentifier extends IdentifierAction {

  /**
   * Gets the data of the fields provided by the data_profile config.
   *
   * @throws Drupal\rules\Exception\InvalidArgumentException
   *   If the Entity doesn't have the configured identifier field.
   *
   * @return array
   *   The returned data structured in a key value pair
   *   based on the configured data_profile.
   */
  protected function getFieldData() {
    $data = [];
    if ($this->getConfigs()['data_profile']) {
      foreach ($this->getConfigs()['data_profile']->get('data') as $key => $value) {
        if (is_numeric($key) && $this->getEntity()->hasField($value['source_field'])) {
          $data[$value['key']] = $this->getEntity()->get($value['source_field'])->getString();
        }
      }
    }

    return $data;
  }

  /**
   * Mints the identifier to the service.
   *
   * @return mixed
   *   The request response returned by the service.
   */
  protected function mint() {
    $request = $this->buildRequest();
    $response = $this->sendRequest($request);

    return $response;
  }

  /**
   * Gets the Identifier from the service API response.
   *
   * @param mixed $response
   *   Response from the service API.
   *
   * @return string
   *   The identifier returned in the API response.
   */
  abstract protected function getIdentifierFromResponse($response);

  /**
   * Sets the Entity field with the Identifier.
   *
   * @param string $identifier
   *   The identifier formatted as a URL.
   */
  protected function setIdentifierField(string $identifier) {
    if ($identifier) {
      $field = $this->getConfigs()['identifier']->get('field');
      if (!empty($field) && $this->getEntity()->hasField($field)) {
        $this->getEntity()->set($field, $identifier);
        $this->getEntity()->save();
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
  public function execute($entity = NULL) {
    if ($entity instanceof FieldableEntityInterface) {
      try {
        $this->setEntity($entity);
        $this->setConfigs($this->utils->getAssociatedConfigs($this->configuration['identifier_type']));
        if ($this->getEntity() && $this->getConfigs()) {
          $response = $this->mint();
          $this->handleResponse($response);
        }
        else {
          $this->logger->error('Entity or Configs were not properly set.');
        }
      }
      catch (UndefinedLinkTemplateException $ulte) {
        $this->logger->warning('Error retrieving Entity URL: @errorMessage', ['@errorMessage' => $ulte->getMessage()]);
      }
      catch (InvalidArgumentException $iae) {
        $this->logger->error('Configured field not found on Entity: @iae', ['@iae' => $iae->getMessage()]);
      }
      catch (RequestException $re) {
        $this->logger->error('Bad Request: @badrequest', ['@badrequest' => $re->getMessage()]);
      }
      catch (BadResponseException $bre) {
        $this->logger->error('Error in response from service: @response', ['@response' => $bre->getMessage()]);
      }
    }
  }

}
