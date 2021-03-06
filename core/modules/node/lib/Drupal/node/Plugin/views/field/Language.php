<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\Language.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\node\Plugin\views\field\Node;
use Drupal\Component\Annotation\Plugin;

/**
 * Field handler to translate a language into its readable form.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "node_language",
 *   module = "node"
 * )
 */
class Language extends Node {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['native_language'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['native_language'] = array(
      '#title' => t('Native language'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['native_language'],
      '#description' => t('If enabled, the native name of the language will be displayed'),
    );
  }

  function render($values) {
    // @todo: Drupal Core dropped native language until config translation is
    // ready, see http://drupal.org/node/1616594.
    $value = $this->get_value($values);
    $language = language_load($value);
    $value = $language ? $language->name : '';
    return $this->render_link($value, $values);
  }

}
