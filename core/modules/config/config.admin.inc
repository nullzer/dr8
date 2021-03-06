<?php

/**
 * @file
 * Admin page callbacks for the config module.
 */

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;

/**
 * Helper function to construct the storage changes in a configuration synchronization form.
 *
 * @param array $form
 *   The form structure to add to. Passed by reference.
 * @param array $form_state
 *   The current state of the form. Passed by reference.
 * @param Drupal\Core\Config\StorageInterface $source_storage
 *   The source storage to retrieve differences from.
 * @param Drupal\Core\Config\StorageInterface $target_storage
 *   The target storage to compare differences to.
 */
function config_admin_sync_form(array &$form, array &$form_state, StorageInterface $source_storage, StorageInterface $target_storage) {
  $source_list = $source_storage->listAll();
  if (empty($source_list)) {
    $form['no_changes'] = array(
      '#markup' => t('There is no configuration to import.'),
    );
    $form['actions']['#access'] = FALSE;
    return $form;
  }

  $config_changes = config_sync_get_changes($source_storage, $target_storage);
  if (empty($config_changes)) {
    $form['no_changes'] = array(
      '#markup' => t('There are no configuration changes.'),
    );
    return $form;
  }

  // Add the AJAX library to the form for dialog support.
  $form['#attached']['library'][] = array('system', 'drupal.ajax');

  foreach ($config_changes as $config_change_type => $config_files) {
    if (empty($config_files)) {
      continue;
    }

    // @todo A table caption would be more appropriate, but does not have the
    //   visual importance of a heading.
    $form[$config_change_type]['heading'] = array(
      '#theme' => 'html_tag__h3',
      '#tag' => 'h3',
    );
    switch ($config_change_type) {
      case 'create':
        $form[$config_change_type]['heading']['#value'] = format_plural(count($config_files), '@count new', '@count new');
        break;

      case 'change':
        $form[$config_change_type]['heading']['#value'] = format_plural(count($config_files), '@count changed', '@count changed');
        break;

      case 'delete':
        $form[$config_change_type]['heading']['#value'] = format_plural(count($config_files), '@count removed', '@count removed');
        break;
    }
    $form[$config_change_type]['list'] = array(
      '#theme' => 'table',
      '#header' => array('Name', 'Operations'),
    );

    foreach ($config_files as $config_file) {
      $links['view_diff'] = array(
        'title' => t('View differences'),
        'href' => 'admin/config/development/sync/diff/' . $config_file,
        'attributes' => array(
          'class' => array('use-ajax'),
        ),
      );
      $form[$config_change_type]['list']['#rows'][] = array(
        'name' => $config_file,
        'operations' => array(
          'data' => array(
            '#type' => 'operations',
            '#links' => $links,
          ),
        ),
      );
    }
  }
}

/**
 * Form constructor for configuration import form.
 *
 * @see config_admin_import_form_submit()
 * @see config_import()
 */
function config_admin_import_form($form, &$form_state) {
  // Retrieve a list of differences between last known state and active store.
  $source_storage = drupal_container()->get('config.storage.staging');
  $target_storage = drupal_container()->get('config.storage');

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Import all'),
  );

  config_admin_sync_form($form, $form_state, $source_storage, $target_storage);

  return $form;
}

/**
 * Form submission handler for config_admin_import_form().
 */
function config_admin_import_form_submit($form, &$form_state) {
  if (!lock()->lockMayBeAvailable(CONFIG_IMPORT_LOCK)) {
    drupal_set_message(t('Another request may be synchronizing configuration already.'));
  }
  else if (config_import()) {
    // Once a sync completes, we empty the staging directory. This prevents
    // changes from being accidentally overwritten by stray files getting
    // imported later.
    $source_storage = drupal_container()->get('config.storage.staging');
    foreach ($source_storage->listAll() as $name) {
      $source_storage->delete($name);
    }

    drupal_flush_all_caches();

    drupal_set_message(t('The configuration was imported successfully.'));
  }
  else {
    drupal_set_message(t('The import failed due to an error. Any errors have been logged.'), 'error');
  }
}

/**
 * Page callback: Shows diff of specificed configuration file.
 *
 * @param string $config_file
 *   The name of the configuration file.
 *
 * @return string
 *   Table showing a two-way diff between the active and staged configuration.
 */
function config_admin_diff_page($config_file) {
  // Retrieve a list of differences between last known state and active store.
  $source_storage = drupal_container()->get('config.storage.staging');
  $target_storage = drupal_container()->get('config.storage');

  // Add the CSS for the inline diff.
  $output['#attached']['css'][] = drupal_get_path('module', 'system') . '/system.diff.css';

  $diff = config_diff($target_storage, $source_storage, $config_file);
  $formatter = new DrupalDiffFormatter();
  $formatter->show_header = FALSE;

  $variables = array(
    'header' => array(
      array('data' => t('Old'), 'colspan' => '2'),
      array('data' => t('New'), 'colspan' => '2'),
    ),
    'rows' => $formatter->format($diff),
  );

  $output['diff'] = array(
    '#markup' => theme('table', $variables),
  );

  $output['back'] = array(
    '#type' => 'link',
    '#title' => "Back to 'Synchronize configuration' page.",
    '#href' => 'admin/config/development/sync',
  );

  $title = t('View changes of @config_file', array('@config_file' => $config_file));

  // Return AJAX requests as a dialog.
  // @todo: Set up separate content callbacks for the non-JS and dialog versions
  // of this page using the router system. See http://drupal.org/node/1944472. 
  if (Drupal::service('request')->isXmlHttpRequest()) {
    // Add class to the close link.
    $output['back']['#attributes']['class'][] = 'dialog-cancel';

    $dialog_content = drupal_render($output);
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($title, $dialog_content, array('width' => '700')));
    return $response;
  }
  // Otherwise show the page title as an element.
  else {
    $output['title'] = array(
      '#theme' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $title,
      '#weight' => -10,
    );
  }

  return $output;
}
