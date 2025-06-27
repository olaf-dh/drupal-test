<?php

namespace Drupal\translation_api\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\node\NodeInterface;

/**
 * @file
 *
 * Liefert Übersetzungsdaten im API-Format.
 *
 * Verantwortlichkeiten:
 *  - Abfrage von Translation-Nodes
 *  - Caching der Ergebnisse (Cache-Tags: node:{nid}, translation_item_list)
 *  - Aufbereitung in die JSON-Struktur für die REST-Endpunkte
 */
class TranslationService {
  protected EntityTypeManagerInterface $entityTypeManager;
  protected CacheBackendInterface $cache;
  protected CacheableMetadata $responseCacheable;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, CacheBackendInterface $cache) {
    $this->entityTypeManager = $entityTypeManager;
    $this->cache = $cache;

    // Basis-Metadaten: globales Tag + Max-Age
    $this->responseCacheable = (new CacheableMetadata())
      ->setCacheTags(['translation_item_list'])
      ->setCacheMaxAge(900);
  }

  public function getResponseCacheable(): CacheableMetadata {
    return $this->responseCacheable;
  }

  /**
   * Liefert alle Übersetzungen, optional gefiltert nach Kategorie.
   *
   * @return array<int, array<string, mixed>>
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getAllTranslations(?string $category = null): array {
    $cid = 'translation_api_all_' . ($category ?? 'all');

    // cache Treffer?
    if ($cache = $this->cache->get($cid)) {
      /** @var array<int, array<string, mixed>> $data */
      $data = $cache->data;

      return $data;
    }

    // Daten aufbauen
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'translation_item')
      ->accessCheck(true);

    if ($category) {
      // Taxonomie-Term per Namen
      $query->condition('field_category.entity.name', $category);
    }

    $node_ids = $query->execute();
    $nodes = $storage->loadMultiple($node_ids);

    $items = [];
    $tags  = ['translation_item_list'];  // Basis-Tag für "alle"
    foreach ($nodes as $node) {
      /** @var Node $node */
      $items[] = $this->buildItem($node);
      $tags[]  = 'node:' . $node->id();  // ein Tag pro Node
    }

    // Cache schreiben mit Tags
    $this->cache->set($cid, $items, CacheBackendInterface::CACHE_PERMANENT, $tags);

    return $items;
  }

  /**
   * Liefert genau eine Übersetzung anhand des Schlüssels.
   *
   * @return array<string, mixed>|null
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getTranslationByKey(string $key): ?array {
    $cid = 'translation_api_item_' . $key;
    if ($cache = $this->cache->get($cid)) {
      /** @var array<string, mixed> $data */
      $data = $cache->data;
      return $data;
    }

    $storage = $this->entityTypeManager->getStorage('node');

    $node_ids = $storage->getQuery()
      ->accessCheck(false)
      ->condition('type', 'translation_item')
      ->condition('field_key.value', $key)
      ->range(0, 1)
      ->execute();

    if (empty($node_ids)) {
      return null; // kein Treffer
    }

    /** @var Node $node */
    $node = $storage->load(reset($node_ids));
    $item = $this->buildItem($node);

    // Einzel-Cache mit spezifischem Tag
    $this->cache->set(
      $cid,
      $item,
      CacheBackendInterface::CACHE_PERMANENT,
      ['node:' . $node->id()]
    );

    return $item;
  }

  /**
   * Hilfsfunktion: baut die Item-Struktur
   *
   * @param NodeInterface|null $node
   *
   * @return array<string, mixed>
   */
  protected function buildItem(?NodeInterface $node): array {
    if (!$node) {
      return [];
    }

    // jede Node als zusätzliche Abhängigkeit anhängen
    $this->responseCacheable
      ->addCacheableDependency(CacheableMetadata::createFromObject($node));

    return [
      'key' => $node->get('field_key')->getString(),
      'category' => $node->get('field_category')->getEntity()->label() ?? null,
      'translations' => [
        'de' => $node->get('field_de')->getString(),
        'en' => $node->get('field_en')->getString(),
        'fr' => $node->get('field_fr')->getString(),
        'it' => $node->get('field_it')->getString(),
      ],
    ];
  }
}
