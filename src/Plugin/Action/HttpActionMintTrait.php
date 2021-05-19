<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Psr\Http\Message\ResponseInterface;

/**
 * Utilities for dealing with HTTP services.
 */
trait HttpActionMintTrait {

  use HttpActionTrait;

  /**
   * Mints the identifier to the service.
   *
   * @return string
   *   The identifier that was minted.
   *
   * @throws \Exception
   *   Exceptions that can occur via the request process.
   */
  protected function mint(): string {
    return $this->getIdentifierFromResponse($this->sendRequest($this->buildRequest()));
  }

  /**
   * Extracts the identifier out of the returned Response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response to the HTTP request.
   *
   * @return string
   *   The identifier to be used.
   */
  abstract protected function getIdentifierFromResponse(ResponseInterface $response): string;

}
