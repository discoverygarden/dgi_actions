<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Psr\Http\Message\ResponseInterface;

/**
 * Utilities for dealing with HTTP services.
 */
trait HttpActionDeleteTrait {

  use HttpActionTrait;

  /**
   * Deletes the identifier from the service.
   */
  protected function delete(): void {
    $this->handleDeleteResponse($this->sendRequest($this->buildRequest()));
  }

  /**
   * Handles the response from the delete request.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The Guzzle HTTP Response Object.
   */
  abstract protected function handleDeleteResponse(ResponseInterface $response): void;

}
