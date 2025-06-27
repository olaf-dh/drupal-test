<?php

namespace Drupal\translation_api\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\translation_api\Service\AuthenticationService;
use LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\translation_api\Service\TranslationService;

/**
 * @file
 * REST-Controller für die Übersetzungs-API.
 *
 * Liefert Übersetzungen im JSON-Format
 */
final class TranslationApiController extends ControllerBase {
  protected TranslationService $translation;
  protected AuthenticationService $auth;

  public function __construct(TranslationService $translation, AuthenticationService $auth) {
    $this->translation = $translation;
    $this->auth = $auth;
  }

  /**
   * {@inheritdoc}
   *
   * @throws LogicException
   */
  public static function create(ContainerInterface $container): self
  {
    $translation = $container->get('translation_api.translation_service');
    $auth = $container->get('translation_api.auth');

    if (!$translation instanceof TranslationService) {
      throw new LogicException('Service is not instance of TranslationService');
    }

    if (!$auth instanceof AuthenticationService) {
      throw new LogicException('translation_api.auth mis-configured.');
    }

    return new self($translation, $auth);
  }

  /**
   * Gibt alle Übersetzungen zurück (optimal nach Kategorie gefilter)
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getAll(Request $request): CacheableJsonResponse {
    if (!$this->auth->isAuthorized()) {
      $response = new CacheableJsonResponse(
        ['error' => 'Not authorized'],
        403
      );
      // nie cachen
      $response->addCacheableDependency(
        (new CacheableMetadata())->setCacheMaxAge(0)
      );
      return $response;
    }

    /** @var string|null $category */
    $category = $request->query->get('category');
    $data = $this->translation->getAllTranslations($category);

    $response = new CacheableJsonResponse($data);

    // Cache-Metadaten anhängen
    $response->addCacheableDependency(
      $this->translation->getResponseCacheable()
    );

    return $response;
  }

  /**
   * Liefert genau eine Übersetsung anhand des Schlüssels
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getByKey(string $key): CacheableJsonResponse {
    if (!$this->auth->isAuthorized()) {
      $response = new CacheableJsonResponse(
        ['error' => 'Not authorized'],
        403
      );
      // nie cachen
      $response->addCacheableDependency(
        (new CacheableMetadata())->setCacheMaxAge(0)
      );
      return $response;
    }

    $item = $this->translation->getTranslationByKey($key);

    // leere Antwort, wenn nicht gefunden
    $response = new CacheableJsonResponse(['items' => $item ? [$item] : []]);

    // ► Metadaten anhängen
    if ($item) {
      $response->addCacheableDependency(
        $this->translation->getResponseCacheable()
      );
    } else {
      // 404 leer – auch nicht cachen
      $response->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
    }
    return $response;
  }
}
