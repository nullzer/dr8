<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\Element.
 */

namespace Drupal\Core\Config\Schema;

use Drupal\Core\TypedData\ContextAwareTypedData;

/**
 * Defines a generic configuration element.
 */
abstract class Element extends ContextAwareTypedData {

  /**
   * The configuration value.
   *
   * @var mixed
   */
  protected $value;

  /**
   * Create typed config object.
   */
  protected function parseElement($key, $data, $definition) {
    return config_typed()->create($definition, $data, $key, $this);
  }

}
