<?php

/**
 * @file
 * Definition of Drupal\views\Tests\UI\OverrideDisplaysTest.
 */

namespace Drupal\views\Tests\UI;

/**
 * Tests that displays can be correctly overridden via the user interface.
 */
class OverrideDisplaysTest extends UITestBase {

  public static function getInfo() {
    return array(
      'name' => 'Overridden displays functionality',
      'description' => 'Test that displays can be correctly overridden via the user interface.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests that displays can be overridden via the UI.
   */
  function testOverrideDisplays() {
    // Create a basic view that shows all content, with a page and a block
    // display.
    $view['human_name'] = $this->randomName(16);
    $view['id'] = strtolower($this->randomName(16));
    $view['page[create]'] = 1;
    $view['page[path]'] = $this->randomName(16);
    $view['block[create]'] = 1;
    $view_path = $view['page[path]'];
    $this->drupalPost('admin/structure/views/add', $view, t('Save and edit'));

    // Configure its title. Since the page and block both started off with the
    // same (empty) title in the views wizard, we expect the wizard to have set
    // things up so that they both inherit from the default display, and we
    // therefore only need to change that to have it take effect for both.
    $edit = array();
    $edit['title'] = $original_title = $this->randomName(16);
    $edit['override[dropdown]'] = 'default';
    $this->drupalPost("admin/structure/views/nojs/display/{$view['id']}/page_1/title", $edit, t('Apply'));
    $this->drupalPost("admin/structure/views/view/{$view['id']}/edit/page_1", array(), t('Save'));

    // Add a node that will appear in the view, so that the block will actually
    // be displayed.
    $this->drupalCreateNode();

    // Make sure the title appears in the page.
    $this->drupalGet($view_path);
    $this->assertResponse(200);
    $this->assertText($original_title);

    // Confirm that the view block is available in the block administration UI.
    $this->drupalGet('admin/structure/block/list/block_plugin_ui:' . config('system.theme')->get('default') . '/add');
    $this->assertText('View: ' . $view['human_name']);

    // Place the block.
    $this->drupalPlaceBlock("views_block:{$view['id']}-block_1");

    // Make sure the title appears in the block.
    $this->drupalGet('');
    $this->assertText($original_title);

    // Change the title for the page display only, and make sure that the
    // original title still appears on the page.
    $edit = array();
    $edit['title'] = $new_title = $this->randomName(16);
    $edit['override[dropdown]'] = 'page_1';
    $this->drupalPost("admin/structure/views/nojs/display/{$view['id']}/page_1/title", $edit, t('Apply'));
    $this->drupalPost("admin/structure/views/view/{$view['id']}/edit/page_1", array(), t('Save'));
    $this->drupalGet($view_path);
    $this->assertResponse(200);
    $this->assertText($new_title);
    $this->assertText($original_title);
  }

  /**
   * Tests that the wizard correctly sets up default and overridden displays.
   */
  function testWizardMixedDefaultOverriddenDisplays() {
    // Create a basic view with a page, block, and feed. Give the page and feed
    // identical titles, but give the block a different one, so we expect the
    // page and feed to inherit their titles from the default display, but the
    // block to override it.
    $view['human_name'] = $this->randomName(16);
    $view['id'] = strtolower($this->randomName(16));
    $view['page[create]'] = 1;
    $view['page[title]'] = $this->randomName(16);
    $view['page[path]'] = $this->randomName(16);
    $view['page[feed]'] = 1;
    $view['page[feed_properties][path]'] = $this->randomName(16);
    $view['block[create]'] = 1;
    $view['block[title]'] = $this->randomName(16);
    $this->drupalPost('admin/structure/views/add', $view, t('Save and edit'));


    // Add a node that will appear in the view, so that the block will actually
    // be displayed.
    $this->drupalCreateNode();

    // Make sure that the feed, page and block all start off with the correct
    // titles.
    $this->drupalGet($view['page[path]']);
    $this->assertResponse(200);
    $this->assertText($view['page[title]']);
    $this->assertNoText($view['block[title]']);
    $this->drupalGet($view['page[feed_properties][path]']);
    $this->assertResponse(200);
    $this->assertText($view['page[title]']);
    $this->assertNoText($view['block[title]']);

    // Confirm that the block is available in the block administration UI.
    $this->drupalGet('admin/structure/block/list/block_plugin_ui:' . config('system.theme')->get('default') . '/add');
    $this->assertText('View: ' . $view['human_name']);

    // Place the block.
    $this->drupalPlaceBlock("views_block:{$view['id']}-block_1");
    $this->drupalGet('');
    $this->assertText($view['block[title]']);
    $this->assertNoText($view['page[title]']);

    // Edit the page and change the title. This should automatically change
    // the feed's title also, but not the block.
    $edit = array();
    $edit['title'] = $new_default_title = $this->randomName(16);
    $this->drupalPost("admin/structure/views/nojs/display/{$view['id']}/page_1/title", $edit, t('Apply'));
    $this->drupalPost("admin/structure/views/view/{$view['id']}/edit/page_1", array(), t('Save'));
    $this->drupalGet($view['page[path]']);
    $this->assertResponse(200);
    $this->assertText($new_default_title);
    $this->assertNoText($view['page[title]']);
    $this->drupalGet($view['page[feed_properties][path]']);
    $this->assertResponse(200);
    $this->assertText($new_default_title);
    $this->assertNoText($view['page[title]']);
    $this->assertNoText($view['block[title]']);
    $this->drupalGet('');
    $this->assertText($view['block[title]']);
    $this->assertNoText($new_default_title);
    $this->assertNoText($view['page[title]']);

    // Edit the block and change the title. This should automatically change
    // the block title only, and leave the defaults alone.
    $edit = array();
    $edit['title'] = $new_block_title = $this->randomName(16);
    $this->drupalPost("admin/structure/views/nojs/display/{$view['id']}/block_1/title", $edit, t('Apply'));
    $this->drupalPost("admin/structure/views/view/{$view['id']}/edit/block_1", array(), t('Save'));
    $this->drupalGet($view['page[path]']);
    $this->assertResponse(200);
    $this->assertText($new_default_title);
    $this->drupalGet($view['page[feed_properties][path]']);
    $this->assertResponse(200);
    $this->assertText($new_default_title);
    $this->assertNoText($new_block_title);
    $this->drupalGet('');
    $this->assertText($new_block_title);
    $this->assertNoText($view['block[title]']);
  }

  /**
   * Tests that the revert to all displays select-option works as expected.
   */
  function testRevertAllDisplays() {
    // Create a basic view with a page, block.
    // Because there is both a title on page and block we expect the title on
    // the block be overriden.
    $view['human_name'] = $this->randomName(16);
    $view['id'] = strtolower($this->randomName(16));
    $view['page[create]'] = 1;
    $view['page[title]'] = $this->randomName(16);
    $view['page[path]'] = $this->randomName(16);
    $view['block[create]'] = 1;
    $view['block[title]'] = $this->randomName(16);
    $this->drupalPost('admin/structure/views/add', $view, t('Save and edit'));

    // Revert the title of the block back to the default ones, but submit some
    // new values to be sure that the new value is not stored.
    $edit = array();
    $edit['title'] = $new_block_title = $this->randomName();
    $edit['override[dropdown]'] = 'default_revert';

    $this->drupalPost("admin/structure/views/nojs/display/{$view['id']}/block_1/title", $edit, t('Apply'));
    $this->drupalPost("admin/structure/views/view/{$view['id']}/edit/block_1", array(), t('Save'));
    $this->assertText($view['page[title]']);
  }

}
