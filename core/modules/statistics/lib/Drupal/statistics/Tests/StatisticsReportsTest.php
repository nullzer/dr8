<?php

/**
 * @file
 * Definition of Drupal\statistics\Tests\StatisticsReportsTest.
 */

namespace Drupal\statistics\Tests;

/**
 * Tests that report pages render properly, and that access logging works.
 */
class StatisticsReportsTest extends StatisticsTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Statistics reports tests',
      'description' => 'Tests display of statistics report blocks.',
      'group' => 'Statistics'
    );
  }

  /**
   * Tests the "popular content" block.
   */
  function testPopularContentBlock() {
    // Clear the block cache to load the Statistics module's block definitions.
    $this->container->get('plugin.manager.block')->clearCachedDefinitions();

    // Visit a node to have something show up in the block.
    $node = $this->drupalCreateNode(array('type' => 'page', 'uid' => $this->blocking_user->uid));
    $this->drupalGet('node/' . $node->nid);
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $node->nid;
    $post = http_build_query(array('nid' => $nid));
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics'). '/statistics.php';
    drupal_http_request($stats_path, array('method' => 'POST', 'data' => $post, 'headers' => $headers, 'timeout' => 10000));

    // Configure and save the block.
    $this->drupalPlaceBlock('statistics_popular_block', array('label' => 'Popular content'), array(
      'top_day_num' => 3,
      'top_all_num' => 3,
      'top_last_num' => 3,
    ));

    // Get some page and check if the block is displayed.
    $this->drupalGet('user');
    $this->assertText('Popular content', 'Found the popular content block.');
    $this->assertText("Today's", "Found today's popular content.");
    $this->assertText('All time', 'Found the all time popular content.');
    $this->assertText('Last viewed', 'Found the last viewed popular content.');

    $this->assertRaw(l($node->label(), 'node/' . $node->nid), 'Found link to visited node.');
  }

}
