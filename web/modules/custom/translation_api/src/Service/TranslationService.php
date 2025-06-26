<?php

namespace Drupal\translation_api\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;

class TranslationService {
  protected EntityTypeManagerInterface $entityTypeManager;
  protected CacheBackendInterface $cache;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, CacheBackendInterface $cache) {
    $this->entityTypeManager = $entityTypeManager;
    $this->cache = $cache;
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getAllTranslations(?string $category = null): array {
    $cid = 'translation_api_all_' . ($category ?? 'all');

    // cache hit
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    // build data
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'translation_item')
      ->accessCheck(true);

    if ($category) {
      // taxonomy term per name
      $query->condition('field_category.entity.name', $category);
    }

    $node_ids = $query->execute();
    $nodes = $storage->loadMultiple($node_ids);

    $items = [];
    $tags  = ['translation_item_list'];  // basic tag for "all"
    foreach ($nodes as $node) {
      /** @var Node $node */
      $items[] = $this->buildItem($node);
      $tags[]  = 'node:' . $node->id();  // one tag per node
    }

    // write cache with tags
    $this->cache->set($cid, $items, CacheBackendInterface::CACHE_PERMANENT, $tags);

    return $items;
  }

  /**
   * Delivers a single translation item per key
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getTranslationByKey(string $key): ?array {
    $cid = 'translation_api_item_' . $key;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $storage = $this->entityTypeManager->getStorage('node');

    // accessCheck(false) = for guests also
    $node_ids = $storage->getQuery()
      ->accessCheck(false)
      ->condition('type', 'translation_item')
      ->condition('field_key.value', $key)
      ->range(0, 1)
      ->execute();

    if (empty($node_ids)) {
      return null; // no hits
    }

    /** @var Node $node */
    $node = $storage->load(reset($node_ids));
    $item = $this->buildItem($node);

    // Single-Cache with node:{nid}-tag → Core invalidated automatic
    $this->cache->set(
      $cid,
      $item,
      CacheBackendInterface::CACHE_PERMANENT,
      ['node:' . $node->id()]
    );

    return $item;
  }

  protected function buildItem(Node $node): array {
    return [
      'key' => $node->get('field_key')->value,
      'category' => $node->get('field_category')->entity?->label() ?? null,
      'translations' => [
        'de' => $node->get('field_de')->value,
        'en' => $node->get('field_en')->value,
        'fr' => $node->get('field_fr')->value,
        'it' => $node->get('field_it')->value,
      ],
    ];
  }
}
