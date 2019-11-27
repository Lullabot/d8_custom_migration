<?php

namespace Drupal\lullabot_migrate\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LullabotModuleSubscriber.
 *
 * - Disables search, tracker, pathauto before starting the migration.
 * - Restores them after finishing the migration.
 */
class LullabotModuleSubscriber implements EventSubscriberInterface {

  /**
   * Whether search is anabled so we restore it properly.
   *
   * @var bool
   */
  protected $searchStatus;

  /**
   * Search settings before uninstallation, so they can be restored.
   *
   * @var array
   */
  protected $searchSettings;

  /**
   * Whether tracker is enabled, so we restore it properly.
   *
   * @var bool
   */
  protected $trackerStatus;

  /**
   * Tracker settings before uninstallation, so they can be restored.
   *
   * @var array
   */
  protected $trackerSettings;

  /**
   * Whether pathauto is enabled, so we restore it properly.
   *
   * @var bool
   */
  protected $pathautoStatus;

  /**
   * Pathauto settings before uninstallation, so they can be restored.
   *
   * @var array
   */
  protected $pathautoSettings;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

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
    $events[MigrateEvents::PRE_IMPORT][] = ['onMigratePreImport'];
    $events[MigrateEvents::POST_IMPORT][] = ['onMigratePostImport'];
    return $events;
  }

  /**
   * Disables modules before starting.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePreImport(MigrateImportEvent $event) {

    $this->searchStatus = FALSE;
    $this->searchSettings = NULL;
    if ($this->moduleHandler->moduleExists('search')) {
      \Drupal::service('module_installer')->uninstall(['search']);
      $config = $this->entityTypeManager->getStorage('search.settings')->load();
      $this->searchSettings = $config->getOption('search.settings');
      unset($module_data['search']);
    }
    $this->trackerStatus = FALSE;
    $this->trackerSettings = NULL;
    if ($this->moduleHandler->moduleExists('tracker')) {
      $this->trackerStatus = TRUE;
      $config = $this->entityTypeManager->getStorage('tracker.settings')->load();
      $this->trackerSettings = $config->getOption('tracker.settings');
      \Drupal::service('module_installer')->uninstall(['search']);
    }
    $this->pathautoStatus = FALSE;
    $this->pathautoSettings = NULL;
    if ($this->moduleHandler->moduleExists('pathauto')) {
      $this->pathautoStatus = TRUE;
      $config = $this->entityTypeManager->getStorage('pathauto.settings')->load();
      $this->pathautoSettings = $config->getOption('pathauto.settings');
      \Drupal::service('module_installer')->uninstall(['search']);
    }

  }

  /**
   * Enable back whatever was turned off.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePostImport(MigrateImportEvent $event) {

    if ($this->searchStatus == TRUE) {
      \Drupal::service('module_installer')->install(['search']);
      $config = $this->entityTypeManager->getStorage('search.settings')->load();
      $config->setOption('search.settings', $this->searchSettings);
    }
    if ($this->trackerStatus == TRUE) {
      \Drupal::service('module_installer')->install(['tracker']);
      $config = $this->entityTypeManager->getStorage('tracker.settings')->load();
      $config->setOption('tracker.settings', $this->trackerSettings);
    }
    if ($this->pathautoStatus == TRUE) {
      \Drupal::service('module_installer')->install(['pathauto']);
      $config = $this->entityTypeManager->getStorage('pathauto.settings')->load();
      $config->setOption('pathauto.settings', $this->pathautoSettings);
    }

  }

}
