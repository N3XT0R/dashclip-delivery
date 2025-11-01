<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Enum\MailStatus;
use App\Models\MailLog;
use App\Repository\MailRepository;
use Tests\DatabaseTestCase;

class MailRepositoryTest extends DatabaseTestCase
{
    protected MailRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MailRepository();
    }

    public function testCreateMailLog(): void
    {
        // Arrange
        $attributes = [
            'message_id' => 'test-message-123',
            'to' => 'user@example.com',
            'subject' => 'Test Subject',
            'status' => MailStatus::Sent,
        ];

        // Act
        $log = $this->repository->create($attributes);

        // Assert
        $this->assertInstanceOf(MailLog::class, $log);
        $this->assertDatabaseHas('mail_logs', [
            'message_id' => 'test-message-123',
            'to' => 'user@example.com',
            'status' => MailStatus::Sent->value,
        ]);
    }

    public function testFindMailByInReplyToReturnsCorrectRecord(): void
    {
        // Arrange
        $log = MailLog::factory()->create([
            'message_id' => '<abc123@domain.com>',
        ]);

        // Act
        $found = $this->repository->findMailByInReplyTo('abc123');

        // Assert
        $this->assertNotNull($found);
        $this->assertTrue($found->is($log));
    }

    public function testExistsByMessageIdReturnsTrueIfMailExists(): void
    {
        // Arrange
        MailLog::factory()->create([
            'message_id' => '<existing@domain.com>',
        ]);

        // Act & Assert
        $this->assertTrue($this->repository->existsByMessageId('existing'));
        $this->assertFalse($this->repository->existsByMessageId('nonexistent'));
    }

    public function testUpdateStatusChangesMailStatus(): void
    {
        // Arrange
        $log = MailLog::factory()->create([
            'status' => MailStatus::Sent,
            'replied_at' => null,
        ]);

        // Act
        $updated = $this->repository->updateStatus($log, MailStatus::Replied);

        // Assert
        $this->assertTrue($updated);
        $this->assertDatabaseHas('mail_logs', [
            'id' => $log->getKey(),
            'status' => MailStatus::Replied->value,
        ]);

        $log->refresh();
        $this->assertNotNull($log->replied_at);
    }

    public function testFindMailByInReplyToReturnsNullIfNotFound(): void
    {
        // Act
        $result = $this->repository->findMailByInReplyTo('not-found-id');

        // Assert
        $this->assertNull($result);
    }
}
