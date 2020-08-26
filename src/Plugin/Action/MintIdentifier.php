<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\IdentifierAction;
use Drupal\dgi_actions\Plugin\Action\mintIdentifier;
use Drupal\Core\Form\FormStateInterface;
use Exception;

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
   *  The entity.
   * @return String
   *  Entitiy's external URL as a string.
   */
  protected function getExternalURL($entity = NULL){
    if ($entity) {
      return $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
    }
  }

  /**
   * Gets the data of the fields provided by the data_profile config.
   *
   * @param EntityInterface $entity
   *  The Entity.
   * @param Array $configs
   *  The configured configs for the identifier.
   * @return Array $data
   *  The returned data structured in a key value pair based on the data_profile.
   * @throws Exception $e
   *  Throws an Exception if $entity or $configs are NULL.
   */
  protected function getFieldData($entity = null, $configs) {
    if ($entity && $configs) {
      $data = [];
      foreach ($configs['data_profile']->get() as $key => $value) {
        if(is_numeric($key)) {
          $data[$value['key']] = $entity->get($value['source_field'])->getString(); //getvalue() and deal with array
        }
      }

      return $data;
    }

    throw new Exception('Field Data could not be acquired because of missing Entity or Configs.');
  }

  /**
   * Constructs the body data into what the format the minting service expects.
   *
   * @param EntityInterface $entity
   *  The Entity.
   * @param mixed $data
   *  The data that's to be built for the service.
   * @param Array $configs
   *  An array of the identifier's configs.
   * @return Mixed
   *  Returns the request body formatted to the minting service specifications.
   */
  abstract protected function buildRequestBody($entity, $data = null, $configs);

  abstract public function buildRequest($configs);

  abstract public function sendRequest($request, $requestBody, $configs);

  /**
   * Mints the identifier to the service.
   *
   * @param EntityInterface $entity
   *  The entity that is being minted.
   * @param mixed $requestBody
   *  The request body formatted as expected for the service.
   * @param Array $configs
   *  The array of identifier configs.
   * @return mixed $response
   *  The request response returned by the service.
   */
  public function mint($requestBody, $configs) {
    $request = $this->buildRequest($configs);
    $response = $this->sendRequest($request, $requestBody, $configs);
    return $response;
  }

  /**
   * Gets the Identifier from the service API response.
   *
   * @param mixed $response
   *  Response from the service API.
   * @param Array $configs
   *  The array of identifier configs.
   */
  abstract protected function getIdentifier($response, $configs);

  /**
   * Sets the Entity field with the Identifier.
   * @param EntityInterface $entity
   *  The entity.
   * @param String $identifier
   *  The identifier formatted as a URL.
   * @param Array $configs
   *  The Array of Configs.
   */
  protected function setIdentifierField($entity, $identifier, $configs) {
    $entity->set($configs['credentials']->get('field'), $identifier);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity) {
      try {
        $configs = $this->utils->getAssociatedConfigs($this->configuration['identifier_type']);
        $fieldData = $this->getFieldData($entity, $configs);
        $requestBody = $this->buildRequestBody($entity, $fieldData, $configs);
        $response = $this->mint($requestBody, $configs);
        $identifier = $this->getIdentifier($response, $configs);
        $this->setIdentifierField($entity, $identifier, $configs);
      }
      catch (Exception $e) {
        $this->logger->error('Issue while executing Mint Identifier action: @e', ['@e' => $e->getMessage()]);
      }
    }
  }

}
