<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\ArgumentDateTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests the core date argument handlers.
 *
 * @see \Drupal\views\Plugin\views\argument\Date
 */
class ArgumentDateTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_argument_date');

  /**
   * Modules to enable.
   *
   * @todo Remove the node dependency once the handlers are moved to views.
   *
   * @var array
   */
  public static $modules = array('node', 'user');

  /**
   * Stores the column map for this testCase.
   *
   * @var array
   */
  protected $columnMap = array(
    'id' => 'id',
  );

  public static function getInfo() {
    return array(
      'name' => 'Argument: Date',
      'description' => 'Tests the core date argument handler.',
      'group' => 'Views Handlers',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->installSchema('user', 'role_permission');
  }


  /**
   * Overrides \Drupal\views\Tests\ViewUnitTestBase::viewsData().
   */
  public function viewsData() {
    $data = parent::viewsData();

    $date_plugins = array(
      'node_created_fulldate',
      'node_created_day',
      'node_created_month',
      'node_created_week',
      'node_created_year',
      'node_created_year_month',
    );
    foreach ($date_plugins as $plugin_id) {
      $data['views_test_data'][$plugin_id] = $data['views_test_data']['created'];
      $data['views_test_data'][$plugin_id]['real field'] = 'created';
      $data['views_test_data'][$plugin_id]['argument']['id'] = $plugin_id;
    }
    return $data;
  }

  /**
   * Tests the CreatedFullDate handler.
   *
   * @see \Drupal\node\Plugin\views\argument\CreatedFullDate
   */
  public function testCreatedFullDateHandler() {
    $view = views_get_view('test_argument_date');
    $view->setDisplay('default');
    $this->executeView($view, array('20000102'));
    $expected = array();
    $expected[] = array('id' => 2);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('default');
    $this->executeView($view, array('20000101'));
    $expected = array();
    $expected[] = array('id' => 1);
    $expected[] = array('id' => 3);
    $expected[] = array('id' => 4);
    $expected[] = array('id' => 5);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('default');
    $this->executeView($view, array('20001023'));
    $expected = array();
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();
  }

  /**
   * Tests the Day handler.
   *
   * @see \Drupal\node\Plugin\views\argument\CreatedDay
   */
  public function testDayHandler() {
    $view = views_get_view('test_argument_date');
    $view->setDisplay('embed_1');
    $this->executeView($view, array('02'));
    $expected = array();
    $expected[] = array('id' => 2);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_1');
    $this->executeView($view, array('01'));
    $expected = array();
    $expected[] = array('id' => 1);
    $expected[] = array('id' => 3);
    $expected[] = array('id' => 4);
    $expected[] = array('id' => 5);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_1');
    $this->executeView($view, array('23'));
    $expected = array();
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
  }

  /**
   * Tests the Month handler.
   *
   * @see \Drupal\node\Plugin\views\argument\CreatedMonth
   */
  public function testMonthHandler() {
    $view = views_get_view('test_argument_date');
    $view->setDisplay('embed_2');
    $this->executeView($view, array('01'));
    $expected = array();
    $expected[] = array('id' => 1);
    $expected[] = array('id' => 2);
    $expected[] = array('id' => 3);
    $expected[] = array('id' => 4);
    $expected[] = array('id' => 5);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_2');
    $this->executeView($view, array('23'));
    $expected = array();
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
  }

  /**
   * Tests the Week handler.
   *
   * @see \Drupal\node\Plugin\views\argument\CreatedWeek
   */
  public function testWeekHandler() {
    $this->container->get('database')->update('views_test_data')
      ->fields(array('created' => gmmktime(0, 0, 0, 1, 1, 2000)))
      ->condition('id', 3)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(array('created' => gmmktime(0, 0, 0, 1, 10, 2000)))
      ->condition('id', 4)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(array('created' => gmmktime(0, 0, 0, 2, 1, 2000)))
      ->condition('id', 5)
      ->execute();

    $view = views_get_view('test_argument_date');
    $view->setDisplay('embed_3');
    // The first jan 2000 was still in the last week of the previous year.
    $this->executeView($view, array(52));
    $expected = array();
    $expected[] = array('id' => 1);
    $expected[] = array('id' => 2);
    $expected[] = array('id' => 3);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_3');
    $this->executeView($view, array('02'));
    $expected = array();
    $expected[] = array('id' => 4);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_3');
    $this->executeView($view, array('05'));
    $expected = array();
    $expected[] = array('id' => 5);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_3');
    $this->executeView($view, array('23'));
    $expected = array();
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
  }

  /**
   * Tests the Year handler.
   *
   * @see \Drupal\node\Plugin\views\argument\CreatedYear
   */
  public function testYearHandler() {
    $this->container->get('database')->update('views_test_data')
      ->fields(array('created' => gmmktime(0, 0, 0, 1, 1, 2001)))
      ->condition('id', 3)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(array('created' => gmmktime(0, 0, 0, 1, 1, 2002)))
      ->condition('id', 4)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(array('created' => gmmktime(0, 0, 0, 1, 1, 2002)))
      ->condition('id', 5)
      ->execute();

    $view = views_get_view('test_argument_date');
    $view->setDisplay('embed_4');
    $this->executeView($view, array('2000'));
    $expected = array();
    $expected[] = array('id' => 1);
    $expected[] = array('id' => 2);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_4');
    $this->executeView($view, array('2001'));
    $expected = array();
    $expected[] = array('id' => 3);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_4');
    $this->executeView($view, array('2002'));
    $expected = array();
    $expected[] = array('id' => 4);
    $expected[] = array('id' => 5);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_4');
    $this->executeView($view, array('23'));
    $expected = array();
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
  }

  /**
   * Tests the YearMonth handler.
   *
   * @see \Drupal\node\Plugin\views\argument\CreatedYearMonth
   */
  public function testYearMonthHandler() {
    $this->container->get('database')->update('views_test_data')
      ->fields(array('created' => gmmktime(0, 0, 0, 1, 1, 2001)))
      ->condition('id', 3)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(array('created' => gmmktime(0, 0, 0, 4, 1, 2001)))
      ->condition('id', 4)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(array('created' => gmmktime(0, 0, 0, 4, 1, 2001)))
      ->condition('id', 5)
      ->execute();

    $view = views_get_view('test_argument_date');
    $view->setDisplay('embed_5');
    $this->executeView($view, array('200001'));
    $expected = array();
    $expected[] = array('id' => 1);
    $expected[] = array('id' => 2);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_5');
    $this->executeView($view, array('200101'));
    $expected = array();
    $expected[] = array('id' => 3);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_5');
    $this->executeView($view, array('200104'));
    $expected = array();
    $expected[] = array('id' => 4);
    $expected[] = array('id' => 5);
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_5');
    $this->executeView($view, array('23'));
    $expected = array();
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
  }
}
