<?php
namespace Drupal\lullabot_migrate\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateMapDeleteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Class PostMigrationSubscriber.
 *
 * - Create menus after the last node migration that holds values needed to
 *   create the menu links.
 * - Delete all media entities after a rollback.
 *
 * @package Drupal\lullabot_migrate
 * @see https://www.drupal.org/node/2544874
 */
class PostMigrationSubscriber implements EventSubscriberInterface {

  /**
   * Get subscribed events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    // Skip during early bootstrap like re-installs.
    if (!class_exists('Drupal\migrate\Event\MigrateEvents')) {
      return [];
    }
    $events[MigrateEvents::POST_IMPORT][] = ['onMigratePostImport'];
    $events[MigrateEvents::MAP_DELETE][] = ['onMigrateMapDelete'];
    return $events;
  }

  /**
   * Check for our import id and run our process.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePostImport(MigrateImportEvent $event) {

    // After the show content type has been migrated, the nodes that comprise
    // the menu tree and the field_parent default values will exist, so
    // at this point we can create the menu tree and set default values for
    // the field_parent field.
    if ($event->getMigration()->getBaseId() == 'd7_node_show') {
      //$this->createMenu();
      //$this->setParents();
    }

  }

  /**
   * Check for our delete id and run our process.
   *
   * This event runs when deleting any row in any table, so repeats numerous
   * times for any given table.
   *
   * @param \Drupal\migrate\Event\MigrateMapDeleteEvent $event
   *   The delete event object.
   */
  public function onMigrateMapDelete(MigrateMapDeleteEvent $event) {
    // Map object dispatching the event.
    $map = $event->getMap();
    $count = $map->importedCount();
    // The qualified table name will include a prefix, so use strpos instead
    // of checking for equality.
    if (strpos($map->getQualifiedMapTableName(), 'migrate_map_d7_file') !== FALSE) {
      // The count is a way to tell when the last row in the table has been
      // deleted. The count never gets to zero!
      $count = $map->importedCount();
      if ($count == 1) {
        // Delete all media entities.
        // Media entities are not deleted automatically when you rollback, this
        // will remove all media entities when re-running the migration.
        $media_helper = \Drupal::service('lullabot_migrate_media.media');
        $media_helper->deleteAll(TRUE);
      }
    }
  }

  /**
   * Create parent default values.
   *
   * This can't be done until the parent nodes have been created.
   */
  private function setParents() {

    // Log it.
    \Drupal::logger('lullabot_migrate')->notice(
      sprintf('Running setParents() in "%s".', basename(__FILE__, '.php'))
    );

    // Array of content types to update with the slug of the desired parent.
    $types = [
      'announcements' => 'articles',
      'article' => 'articles',
      'external' => 'articles',
      'show' => 'podcasts',
      'bio' => 'about',
      'other_bio' => 'about',
      'case_study' => 'our-work',
      'webinar' => 'webinars',
      'white_paper' => 'white-papers',
      'series' => 'articles',
      'topic' => 'topics',
    ];
    $controller = \Drupal::entityManager()->getStorage('node');
    foreach ($types as $type => $parent_slug) {
      if ($config = \Drupal\field\Entity\FieldConfig::loadByName('node', $type, 'field_parent')) {
        // Get the parent node.
        $query = \Drupal::entityQuery('node')
          ->condition('type', ['section', 'show', 'article'], 'IN')
          ->condition('field_slug.value', $parent_slug);
        if ($ids = $query->execute()) {
          $nodes = $controller->loadMultiple($ids);
          $node = array_shift($nodes);
          // Get the parent's uuid.
          $uuid = $node->uuid();
          $config->set('default_value', [['target_uuid' => $uuid]]);
          $config->save();
        }
      }
    }
  }

  /**
   * Create menu items.
   *
   * This can't be done until the parent nodes have been created.
   */
  private function createMenu() {

    // Log it.
    \Drupal::logger('lullabot_migrate')->notice(
      sprintf('Running createMenu() in "%s".', basename(__FILE__, '.php'))
    );

    $menu_tree = [
      'main' => [
        'our-work' => NULL,
        'services' => [
          'strategy' => NULL,
          'design' => NULL,
          'development' => NULL,
        ],
        'about' => NULL,
        'articles' => NULL,
        'podcasts' => [
          'drupalizeme-podcast' => NULL,
          'insert-content-here' => NULL,
          'hacking-culture' => NULL,
        ],
        'contact' => NULL,
      ],
      'secondary' => [
        'jobs' => [
          'benefits' => NULL,
        ],
        'clients' => NULL,
      ],
      'footer' => [
        'terms' => NULL,
        'privacy-policy' => NULL,
        'jobs' => NULL,
        'contact' => NULL,
      ],
    ];

    $menu_handler = \Drupal::service('plugin.manager.menu.link');
    $controller = \Drupal::entityManager()->getStorage('node');
    $weight = 0;
    foreach ($menu_tree as $menu_name => $values) {
      // Delete the existing links in the menu we want to rebuild
      $menu_handler->deleteLinksInMenu($menu_name);
      foreach ($values as $slug => $value) {
        $query = \Drupal::entityQuery('node')
          ->condition('type', ['section', 'show', 'article'], 'IN')
          ->condition('field_slug.value', $slug);
        if ($ids = $query->execute()) {
          $nodes = $controller->loadMultiple($ids);
          $node = array_shift($nodes);
          $menu_link = MenuLinkContent::create([
            'title' => $this->getTitle($node),
            'link' => ['uri' => 'entity:node/' . $node->id()],
            'menu_name' => $menu_name,
            'expanded' => TRUE,
            'weight' => $weight,
          ]);
          $menu_link->save();
          $weight++;
        }
        if (is_array($value)) {
          foreach ($value as $slug2 => $value2) {
            $query = \Drupal::entityQuery('node')
              ->condition('type', ['section', 'show', 'article'], 'IN')
              ->condition('field_slug.value', $slug2);
            if ($ids = $query->execute()) {
              $nodes = $controller->loadMultiple($ids);
              $node = array_shift($nodes);
              $menu_link2 = MenuLinkContent::create([
                'title' => $this->getTitle($node),
                'link' => ['uri' => 'entity:node/' . $node->id()],
                'menu_name' => $menu_name,
                'expanded' => TRUE,
                'parent' => $menu_link->getPluginId(),
                'weight' => $weight,
              ]);
              $menu_link2->save();
              $weight++;
              if (is_array($value2)) {
                foreach ($value3 as $slug3 => $value4) {
                  $query = \Drupal::entityQuery('node')
                    ->condition('type', ['section', 'show', 'article'], 'IN')
                    ->condition('field_slug.value', $slug3);
                  if ($ids = $query->execute()) {
                    $nodes = $controller->loadMultiple($ids);
                    $node = array_shift($nodes);
                    $menu_link3 = MenuLinkContent::create([
                      'title' => $this->getTitle($node),
                      'link' => ['uri' => 'entity:node/' . $node->id()],
                      'menu_name' => $menu_name,
                      'expanded' => TRUE,
                      'parent' => $menu_link2->getPluginId(),
                      'weight' => $weight,
                    ]);
                    $menu_link3->save();
                    $weight++;
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Get menu titles, alter if necessary.
   */
  function getTitle($node) {
    $title = $node->title->value;
    switch ($title) {
      case 'Our Work':
        return 'Work';

      case 'Articles':
        return 'Blog';

      default:
        return $title;
    }
  }
}
