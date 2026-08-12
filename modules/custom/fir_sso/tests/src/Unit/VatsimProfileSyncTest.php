<?php

declare(strict_types=1);

namespace Drupal\Tests\fir_sso\Unit;

use PHPUnit\Framework\TestCase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\fir_sso\Service\VatsimProfileSync;
use Drupal\user\UserInterface;

/**
 * @group fir_sso
 */
final class VatsimProfileSyncTest extends TestCase {

  private VatsimProfileSync $sync;
  private $storage;

  protected function setUp(): void {
    parent::setUp();

    // Mock the user storage.
    $this->storage = $this->createMock(EntityStorageInterface::class);

    // Mock the entity type manager to return the mocked storage.
    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->method('getStorage')
      ->with('user')
      ->willReturn($this->storage);

    // Instantiate the service with the mocked ETM.
    $this->sync = new VatsimProfileSync($etm);
  }

  private function mockUser(array &$fields, int $id = 1): UserInterface {
    $user = $this->createMock(UserInterface::class);

    $user->method('id')->willReturn($id);

    foreach ($fields as $field => &$value) {
      $user->method('set')
        ->with($field, $this->anything())
        ->willReturnCallback(function ($field, $val) use (&$fields) {
          $fields[$field] = $val;
        });
    }

    $user->method('save')->willReturn(null);

    return $user;
  }

  public function testHappyPathSync(): void {
    $fields = [
      'field_vatsim_cid' => null,
      'field_vatsim_first_name' => null,
      'field_vatsim_last_name' => null,
      'field_vatsim_rating' => null,
      'field_vatsim_region' => null,
      'field_vatsim_division' => null,
      'field_vatsim_subdivision' => null,
    ];

    $user = $this->mockUser($fields);

    // No CID collision.
    $this->storage->method('loadByProperties')
      ->willReturn([]);

    $payload = [
      'cid' => 1234567,
      'name_first' => 'Sav',
      'name_last' => 'Tester',
      'rating' => ['id' => 5],
      'region' => ['id' => 'EMEA'],
      'division' => ['id' => 'UK'],
      'subdivision' => ['id' => 'GBR'],
    ];

    $this->sync->syncFromPayload($payload, $user);

    $this->assertSame(1234567, $fields['field_vatsim_cid']);
    $this->assertSame('Sav', $fields['field_vatsim_first_name']);
    $this->assertSame('Tester', $fields['field_vatsim_last_name']);
    $this->assertSame(5, $fields['field_vatsim_rating']);
    $this->assertSame('EMEA', $fields['field_vatsim_region']);
    $this->assertSame('UK', $fields['field_vatsim_division']);
    $this->assertSame('GBR', $fields['field_vatsim_subdivision']);
  }

  public function testCidCollisionThrows(): void {
    $fields = ['field_vatsim_cid' => null];
    $user = $this->mockUser($fields, id: 2);

    // Simulate another user already having the CID.
    $other = $this->createMock(UserInterface::class);
    $other->method('id')->willReturn(1);

    $this->storage->method('loadByProperties')
      ->willReturn([$other]);

    $payload = ['cid' => 9999999];

    $this->expectException(\RuntimeException::class);
    $this->sync->syncFromPayload($payload, $user);
  }

  public function testRatingOverwrite(): void {
    $fields = [
      'field_vatsim_cid' => 1111111,
      'field_vatsim_rating' => 2,
    ];

    $user = $this->mockUser($fields, id: 1);

    // No collision.
    $this->storage->method('loadByProperties')
      ->willReturn([]);

    $payload = [
      'cid' => 1111111,
      'rating' => ['id' => 7],
    ];

    $this->sync->syncFromPayload($payload, $user);

    $this->assertSame(7, $fields['field_vatsim_rating']);
  }

}
