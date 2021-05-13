<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

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
          $data[$key] = $this->entity->get($field)->getString();
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
    return $this->sendRequest($request);
  }

  /**
   * Gets the Identifier from the service API response.
   *
   * @param \GuzzleHttp\Psr7\Response $response
   *   Response from the service API.
   *
   * @return string
   *   The identifier returned in the API response.
   */
  abstract protected function getIdentifierFromResponse(Response $response): string;

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
          $response = $this->mint();
          $identifier_uri = $this->getIdentifierFromResponse($response);
          $this->setIdentifierField($identifier_uri);
        }
        else {
          $this->logger->error('Minting failed for @entity: Entity or Configs were not properly set.', ['@entity' => $this->getEntity()->uuid()]);
        }
      }
      catch (UndefinedLinkTemplateException $ulte) {
        $this->logger->warning('Minting failed for @entity: Error retrieving Entity URL: @errorMessage', [
          '@entity' => $this->getEntity()->uuid(),
          '@errorMessage' => $ulte->getMessage(),
        ]);
      }
      catch (\InvalidArgumentException $iae) {
        $this->logger->error('Minting failed for @entity: Configured field not found on Entity: @iae', [
          '@entity' => $this->getEntity()->uuid(),
          '@iae' => $iae->getMessage(),
        ]);
      }
      catch (RequestException $re) {
        $this->logger->error('Minting failed for @entity: Bad Request: @badrequest', [
          '@entity' => $this->getEntity()->uuid(),
          '@badrequest' => $re->getMessage(),
        ]);
      }
      catch (\Exception $e) {
        $this->logger->error('Minting failed for @entity: Error: @exception', [
          '@entity' => $this->getEntity()->uuid(),
          '@exception' => $e->getMessage(),
        ]);
      }
    }
  }

}
