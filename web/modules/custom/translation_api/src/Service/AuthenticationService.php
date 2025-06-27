<?php

namespace Drupal\translation_api\Service;

use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @file
 *
 * Prüft, ob ein gültiger API-Key in der Anfrage vorhanden ist.
 */
class AuthenticationService
{
  protected RequestStack $requestStack;
  protected Settings $settings;

  public function __construct(RequestStack $requestStack, Settings $settings) {
    $this->requestStack = $requestStack;
    $this->settings = $settings;
  }

  /**
   * Gibt true zurück, wenn der übergebene API-Key korrekt ist.
   */
  public function isAuthorized(): bool {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return false;
    }

    $providedKey = $request->headers->get('X-API-Key');
    $configuredKey = $this->settings->get('translation_api_key');
    return $providedKey === $configuredKey;
  }
}
