<?php

namespace Drupal\translation_api\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * @file
 *
 * Controller für die Admin-Übersichtsseite.
 */
final class TranslationAdminController extends ControllerBase
{
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Übersicht aller Übersetzungen.
   *
   * @return array<string, mixed>
   */
  public function overview(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('node');
    } catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
      // Loggen und leere Tabelle zurückgeben
      $this->getLogger('translation_api')->error('Node storage not available: @msg', ['@msg' => $e->getMessage()]);
      return [
        '#type'  => 'table',
        '#header'=> ['Titel', 'Key', 'Kategorie', 'Deutsch', 'Englisch', 'Französisch', 'Italienisch'],
        '#rows'  => [],
        '#empty' => 'Node-Speicher nicht verfügbar.',
      ];
    }
    $ids = $storage->getQuery()
      ->accessCheck(true)
      ->condition('type', 'translation_item')
      ->sort('title')
      ->execute();

    $rows = [];
    if (!empty($ids)) {
      $nodes = $storage->loadMultiple($ids);
      foreach ($nodes as $node) {
        /** @var Node $node */
        $rows[] = [
          $node->label(),
          $node->get('field_key')->value ?? '',
          $node->get('field_category')->entity->label() ?? '',
          $node->get('field_de')->value ?? '',
          $node->get('field_en')->value ?? '',
          $node->get('field_fr')->value ?? '',
          $node->get('field_it')->value ?? '',
        ];
      }
    }

    return [
      '#type' => 'table',
      '#header' => [
        'Titel', 'Key', 'Kategorie', 'Deutsch', 'Englisch', 'Französisch', 'Italienisch'
      ],
      '#rows' => $rows,
      '#empty' => 'Keine Übersetzungen gefunden.',
    ];
  }
}
