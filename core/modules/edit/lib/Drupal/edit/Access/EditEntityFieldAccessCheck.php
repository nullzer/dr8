<?php

/**
 * @file
 * Contains \Drupal\edit\Access\EditEntityFieldAccessCheck.
 */

namespace Drupal\edit\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\EntityInterface;

/**
 * Access check for editing entity fields.
 */
class EditEntityFieldAccessCheck implements AccessCheckInterface, EditEntityFieldAccessCheckInterface {

  /**
   * Implements AccessCheckInterface::applies().
   */
  public function applies(Route $route) {
    return array_key_exists('_access_edit_entity_field', $route->getRequirements());
  }

  /**
   * Implements AccessCheckInterface::access().
   */
  public function access(Route $route, Request $request) {
    // @todo Request argument validation and object loading should happen
    //   elsewhere in the request processing pipeline:
    //   http://drupal.org/node/1798214.
    $this->validateAndUpcastRequestAttributes($request);

    return $this->accessEditEntityField($request->attributes->get('entity'), $request->attributes->get('field_name'));
  }

  /**
   * Implements EntityFieldAccessCheckInterface::accessEditEntityField().
   */
  public function accessEditEntityField(EntityInterface $entity, $field_name) {
    $entity_type = $entity->entityType();
    // @todo Generalize to all entity types once http://drupal.org/node/1862750
    // is done.
    return ($entity_type == 'node' && node_access('update', $entity) && field_access('edit', $field_name, $entity_type, $entity));
  }

  /**
   * Validates and upcasts request attributes.
   */
  protected function validateAndUpcastRequestAttributes(Request $request) {
    // Load the entity.
    if (!is_object($entity = $request->attributes->get('entity'))) {
      $entity_id = $entity;
      $entity_type = $request->attributes->get('entity_type');
      if (!$entity_type || !entity_get_info($entity_type)) {
        throw new NotFoundHttpException();
      }
      $entity = entity_load($entity_type, $entity_id);
      if (!$entity) {
        throw new NotFoundHttpException();
      }
      $request->attributes->set('entity', $entity);
    }

    // Validate the field name and language.
    $field_name = $request->attributes->get('field_name');
    if (!$field_name || !field_info_instance($entity->entityType(), $field_name, $entity->bundle())) {
      throw new NotFoundHttpException();
    }
    $langcode = $request->attributes->get('langcode');
    if (!$langcode || (field_valid_language($langcode) !== $langcode)) {
      throw new NotFoundHttpException();
    }
  }

}
