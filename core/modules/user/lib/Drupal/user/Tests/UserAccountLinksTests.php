<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserAccountLinksTests.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests user links in the secondary menu.
 */
class UserAccountLinksTests extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu', 'block');

  public static function getInfo() {
    return array(
      'name' => 'User account links',
      'description' => 'Test user-account links.',
      'group' => 'User'
    );
  }

  /**
   * Tests the secondary menu.
   */
  function testSecondaryMenu() {
    // Create a regular user.
    $user = $this->drupalCreateUser(array());

    // Log in and get the homepage.
    $this->drupalLogin($user);
    $this->drupalGet('<front>');

    // For a logged-in user, expect the secondary menu to have links for "My
    // account" and "Log out".
    $link = $this->xpath('//ul[@id=:menu_id]/li/a[contains(@href, :href) and text()=:text]', array(
      ':menu_id' => 'secondary-menu',
      ':href' => 'user',
      ':text' => 'My account',
    ));
    $this->assertEqual(count($link), 1, 'My account link is in secondary menu.');

    $link = $this->xpath('//ul[@id=:menu_id]/li/a[contains(@href, :href) and text()=:text]', array(
      ':menu_id' => 'secondary-menu',
      ':href' => 'user/logout',
      ':text' => 'Log out',
    ));
    $this->assertEqual(count($link), 1, 'Log out link is in secondary menu.');

    // Log out and get the homepage.
    $this->drupalLogout();
    $this->drupalGet('<front>');

    // For a logged-out user, expect no secondary links.
    $element = $this->xpath('//ul[@id=:menu_id]', array(':menu_id' => 'secondary-menu'));
    $this->assertEqual(count($element), 0, 'No secondary-menu for logged-out users.');
  }

  /**
   * Tests disabling the 'My account' link.
   */
  function testDisabledAccountLink() {
    // Create an admin user and log in.
    $this->drupalLogin($this->drupalCreateUser(array('access administration pages', 'administer menu')));

    // Verify that the 'My account' link is enabled.
    $this->drupalGet('admin/structure/menu/manage/account');
    $this->assertFieldChecked('edit-links-mlid2-hidden', "The 'My account' link is enabled by default.");

    // Disable the 'My account' link.
    $edit = array(
      'links[mlid:2][hidden]' => FALSE,
    );
    $this->drupalPost('admin/structure/menu/manage/account', $edit, t('Save'));

    // Get the homepage.
    $this->drupalGet('<front>');

    // Verify that the 'My account' link does not appear when disabled.
    $link = $this->xpath('//ul[@id=:menu_id]/li/a[contains(@href, :href) and text()=:text]', array(
      ':menu_id' => 'secondary-menu',
      ':href' => 'user',
      ':text' => 'My account',
    ));
    $this->assertEqual(count($link), 0, 'My account link is not in the secondary menu.');
  }

  /**
   * Tests page title is set correctly on user account tabs.
   */
  function testAccountPageTitles() {
    // Default page titles are suffixed with the site name - Drupal.
    $title_suffix = ' | Drupal';

    $this->drupalGet('user');
    $this->assertTitle('Log in' . $title_suffix, "Page title of /user is 'Log in'");

    $this->drupalGet('user/login');
    $this->assertTitle('Log in' . $title_suffix, "Page title of /user/login is 'Log in'");

    $this->drupalGet('user/register');
    $this->assertTitle('Create new account' . $title_suffix, "Page title of /user/register is 'Create new account' for anonymous users.");

    $this->drupalGet('user/password');
    $this->assertTitle('Request new password' . $title_suffix, "Page title of /user/register is 'Request new password' for anonymous users.");

    // Tests the default fallback title.
    $this->drupalGet('user/password/' . $this->randomName());
    $this->assertTitle('User account' . $title_suffix, "Fallback page title for user pages is 'User account' for anonymous users.");

    // Check the page title for registered users is "My Account" in menus.
    $this->drupalLogin($this->drupalCreateUser());
    // After login, the client is redirected to /user.
    $link = $this->xpath('//a[contains(@class, :class)]', array(
        ':class' => 'active-trail',
      )
    );
    $this->assertEqual((string) $link[0], 'My account', "Page title of /user is 'My Account' in menus for registered users");
  }

}
