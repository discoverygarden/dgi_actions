<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;

/**
 * Basic implementation for minting an identifier.
 *
 * @Action(
 *   id = "mint_identifier_record",
 *   label = @Translation("Mint Identifier"),
 *   type = "entity"
 * )
 */
abstract class MintIdentifier extends IdentifierAction {

  /**
   * Gets the External URL of the Entity.
   *
   * @param EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   Entitiy's external URL as a string.
   */
  protected function getExternalUrl(EntityInterface $entity = NULL) {
    try {
      if ($entity) {
        return $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
      }
    }
    catch (UndefinedLinkTemplateException $le) {
      throw $le;
    }
  }

  /**
   * Gets the data of the fields provided by the data_profile config.
   *
   * @param EntityInterface $entity
   *   The Entity.
   *
   * @return array
   *   The returned data structured in a key value pair
   *   based on the data_profile.
   */
  protected function getFieldData(EntityInterface $entity) {
    if ($entity && $this->configs && $entity instanceof FieldableEntityInterface) {
      $data = [];
      foreach ($this->configs['data_profile']->get() as $key => $value) {
        if (is_numeric($key) && $entity->hasField($value['source_field'])) {
          $data[$value['key']] = $entity->get($value['source_field'])->getString();
        }
      }

      return $data;
    }

    $this->logger->error('Field Data could not be acquired because of missing Entity or Configs.');
  }

  /**
   * Constructs the body data into what the format the minting service expects.
   *
   * @param EntityInterface $entity
   *   The Entity.
   * @param mixed $data
   *   The data that's to be built for the service.
   *
   * @return mixed
   *   Returns the request body formatted to the minting service specifications.
   */
  abstract protected function buildRequestBody(EntityInterface $entity, $data = NULL);

  /**
   * Builds the Guzzle HTTP Request.
   *
   * @return Request
   *   The Guzzle HTTP Request Object.
   */
  abstract public function buildRequest();

  /**
   * Sends the Request and Request Body.
   *
   * @param Request $request
   *   The Guzzle HTTP Request Object.
   * @param mixed $requestBody
   *   The request body structured how the API service expects.
   *
   * @return Response
   *   The Guzzle HTTP Response Object.
   */
  abstract public function sendRequest(Request $request, $requestBody);

  /**
   * Mints the identifier to the service.
   *
   * @param EntityInterface $entity
   *   The entity that is being minted.
   *
   * @return mixed
   *   The request response returned by the service.
   */
  public function mint(EntityInterface $entity) {
    try {
      $fieldData = $this->getFieldData($entity);
      $requestBody = $this->buildRequestBody($entity, $fieldData);
      $request = $this->buildRequest();
      $response = $this->sendRequest($request, $requestBody);

      return $response;
    }
    catch (UndefinedLinkTemplateException $ulte) {
      throw $ulte;
    }
    catch (RequestException $re) {
      throw $re;
    }
    catch (BadResponseException $bre) {
      throw $bre;
    }
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
   * @param EntityInterface $entity
   *   The entity.
   * @param string $identifier
   *   The identifier formatted as a URL.
   */
  protected function setIdentifierField(EntityInterface $entity, string $identifier) {
    if ($identifier && $this->configs) {
      $field = $this->configs['credentials']->get('field');
      if (!empty($field) && $entity->hasField($field)) {
        $entity->set($field, $identifier);
        $entity->save();
      }
      else {
        $this->logger->error('Error with Entity Identifier field.');
      }
    }
    else {
      $this->logger->error('Identifier or Configs are not set.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity) {
      try {
        $response = $this->mint($entity);
        $identifier = $this->getIdentifierFromResponse($response);
        $this->setIdentifierField($entity, $identifier);
      }
      catch (UndefinedLinkTemplateException $ulte) {
        $this->logger->warning('Error retrieving Entity URL: @errorMessage', ['@errorMessage' => $ulte->getMessage()]);
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
