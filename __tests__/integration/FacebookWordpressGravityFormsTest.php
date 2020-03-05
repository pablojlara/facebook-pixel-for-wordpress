<?php
/*
 * Copyright (C) 2017-present, Facebook, Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace FacebookPixelPlugin\Tests\Integration;

use FacebookPixelPlugin\Integration\FacebookWordpressGravityForms;
use FacebookPixelPlugin\Tests\FacebookWordpressTestBase;
use FacebookPixelPlugin\Core\FacebookServerSideEvent;
use FacebookPixelPlugin\Tests\Mocks\MockGravityFormField;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * All tests in this test class should be run in separate PHP process to
 * make sure tests are isolated.
 * Stop preserving global state from the parent process.
 */
final class FacebookWordpressGravityFormsTest
  extends FacebookWordpressTestBase {

  public function testInjectPixelCode() {
    \WP_Mock::expectFilterAdded(
      'gform_confirmation',
      array(FacebookWordpressGravityForms::class, 'injectLeadEvent'),
      10,
      4);

    FacebookWordpressGravityForms::injectPixelCode();
    $this->assertHooksAdded();
  }

  public function testInjectLeadEventWithoutAdmin() {
    self::mockIsAdmin(false);
    self::mockUseS2S(true);

    $mock_confirm = 'mock_msg';
    $mock_form = $this->createMockForm();
    $mock_entry = $this->createMockEntries();

    $mock_confirm = FacebookWordpressGravityForms::injectLeadEvent(
      $mock_confirm, $mock_form, $mock_entry, true);

    $this->assertRegexp('/script[\s\S]+gravity-forms/', $mock_confirm);

    $tracked_events =
      FacebookServerSideEvent::getInstance()->getTrackedEvents();

    $this->assertCount(1, $tracked_events);

    $event = $tracked_events[0];
    $this->assertEquals('Lead', $event->getEventName());
    $this->assertNotNull($event->getEventTime());
    $this->assertEquals('pika.chu@s2s.com', $event->getUserData()->getEmail());
    $this->assertEquals('Pika', $event->getUserData()->getFirstName());
    $this->assertEquals('Chu', $event->getUserData()->getLastName());
  }

  public function testInjectLeadEventWithoutAdminErrorReadingForm() {
    self::mockIsAdmin(false);
    self::mockUseS2S(true);

    $mock_confirm = 'mock_msg';
    $mock_form = $this->createMockForm();
    $mock_entry = $this->createMockEntries();

    $mock_confirm = FacebookWordpressGravityForms::injectLeadEvent(
      $mock_confirm, $mock_form, $mock_entry, true);

    $this->assertRegexp('/script[\s\S]+gravity-forms/', $mock_confirm);

    $tracked_events =
      FacebookServerSideEvent::getInstance()->getTrackedEvents();

    $this->assertCount(1, $tracked_events);

    $event = $tracked_events[0];
    $this->assertEquals('Lead', $event->getEventName());
    $this->assertNotNull($event->getEventTime());
    $this->assertEquals('pika.chu@s2s.com', $event->getUserData()->getEmail());
    $this->assertEquals('Pika', $event->getUserData()->getFirstName());
    $this->assertEquals('Chu', $event->getUserData()->getLastName());
  }

  public function testInjectLeadEventWithAdmin() {
    self::mockIsAdmin(true);
    self::mockUseS2S(false);

    $mock_confirm = 'mock_msg';
    $mock_confirm = FacebookWordpressGravityForms::injectLeadEvent(
      $mock_confirm, 'mock_form', 'mock_entry', true);
    $this->assertEquals('mock_msg', $mock_confirm);
  }

  private function createMockForm() {
    $email = new MockGravityFormField('email', '1');

    $name = new MockGravityFormField('name', '2');
    $name->addLabel('First', '2.1');
    $name->addLabel('Last', '2.2');

    $fields = array($email, $name);
    return array('fields' => $fields);
  }

  private function createMockEntries() {
    return array(
      '1' => 'pika.chu@s2s.com',
      '2.1' => 'Pika',
      '2.2' => 'Chu'
    );
  }
}
