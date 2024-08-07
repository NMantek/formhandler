<?php

declare(strict_types=1);

// This file is part of the TYPO3 CMS project. [...]

namespace Typoheads\Formhandler\Http;

use TYPO3\CMS\Core\Http\RequestFactory;

final class TurnstileVerifyRequester {
  private const API_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

  public function __construct(
    private RequestFactory $requestFactory,
  ) {}

  /**
   * @return array<string, mixed>
   */
  public function request(string $turnstilePrivateKey, string $captchaResponse, string $remoteIp): array {
    $response = $this->requestFactory->request(
      self::API_URL,
      'POST',
      [
        'form_params' => [
          'secret' => $turnstilePrivateKey,
          'response' => $captchaResponse,
          'remoteip' => $remoteIp,
        ],
      ],
    );

    if (200 !== $response->getStatusCode()) {
      throw new \RuntimeException(
        'Returned status code is '.$response->getStatusCode(),
      );
    }

    if ('application/json' !== $response->getHeaderLine('Content-Type')) {
      throw new \RuntimeException(
        'The request did not return JSON data',
      );
    }

    $content = $response->getBody()->getContents();

    return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
  }
}
