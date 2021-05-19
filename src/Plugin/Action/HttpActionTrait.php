<?php

namespace Drupal\dgi_actions\Plugin\Action;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Utilities for dealing with HTTP services.
 */
trait HttpActionTrait {

  /**
   * Http Client connection.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Gets the request type.
   *
   * @return string
   *   Request type. (IE. POST, GET, DELETE, etc).
   */
  abstract protected function getRequestType(): string;

  /**
   * Gets the URI end-point for the request.
   *
   * @return string
   *   URI end-point for the request.
   */
  abstract protected function getUri(): string;

  /**
   * Gets the HTTP client service.
   *
   * @return \GuzzleHttp\ClientInterface
   *   The HTTP client to be used for requests.
   */
  protected function getClient(): ClientInterface {
    if (!$this->client) {
      $this->client = \Drupal::service('http_client');
    }
    return $this->client;
  }

  /**
   * Builds the Guzzle HTTP Request.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   *   Thrown by Guzzle when creating an invalid Request.
   *
   * @return \GuzzleHttp\Psr7\Request
   *   The Guzzle HTTP Request Object.
   */
  protected function buildRequest(): RequestInterface {
    $requestType = $this->getRequestType();
    $uri = $this->getUri();
    return new Request($requestType, $uri);
  }

  /**
   * Returns the request param array.
   *
   * @return array
   *   Required params for the applicable service.
   */
  abstract protected function getRequestParams(): array;

  /**
   * Sends the Request and Request Body.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request to be sent.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The Guzzle HTTP Response Object.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   *   Thrown when receiving an HTTP error.
   */
  protected function sendRequest(RequestInterface $request): ResponseInterface {
    $requestParams = $this->getRequestParams();
    return $this->getClient()->send($request, $requestParams);
  }

}
