<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument\Nid.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Component\Annotation\Plugin;

/**
 * Argument handler to accept a node id.
 *
 * @Plugin(
 *   id = "node_nid",
 *   module = "node"
 * )
 */
class Nid extends Numeric {

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  function title_query() {
    $titles = array();

    $nodes = node_load_multiple($this->value);
    foreach ($nodes as $node) {
      $titles[] = check_plain($node->label());
    }
    return $titles;
  }

}
