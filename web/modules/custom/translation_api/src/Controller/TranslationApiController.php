<?php

namespace Drupal\translation_api\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\translation_api\Service\TranslationService;

class TranslationApiController extends ControllerBase {
  protected TranslationService $translationService;

  public function __construct(TranslationService $translationService) {
    $this->translationService = $translationService;
  }

  /**
   * @throws Exception
   */
  public static function create(ContainerInterface $container): TranslationApiController|static
  {
    $service = $container->get('translation_api.translation_service');
    if (!$service instanceof TranslationService) {
      throw new Exception('Service is not instance of TranslationService');
    }

    return new static($service);
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getAll(Request $request): JsonResponse {
    $category = $request->query->get('category');
    $data = $this->translationService->getAllTranslations($category);
    return new JsonResponse(['items' => $data]);
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getByKey(string $key): JsonResponse {
    $item = $this->translationService->getTranslationByKey($key);
    return new JsonResponse(['items' => $item ? [$item] : []]);
  }
}
