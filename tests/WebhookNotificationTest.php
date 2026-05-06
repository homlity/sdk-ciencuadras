<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Tests;

use Ciencuadras\Sdk\Exception\WebhookException;
use Ciencuadras\Sdk\Webhook\WebhookNotification;
use PHPUnit\Framework\TestCase;

final class WebhookNotificationTest extends TestCase
{
    public function testItValidatesHeadersAndExtractsListingStatusUpdates(): void
    {
        $notification = WebhookNotification::fromRequest(
            rawBody: json_encode([
                'task' => [
                    'id' => 'cf8c6d13-56aa-4252-9fa6-247982c53de5',
                    'event' => 'LISTING_STATUS',
                    'status' => 'COMPLETED',
                    'content' => [
                        [
                            'status' => 'COMPLETED',
                            'listing_id' => '07bcf513-d39a-42ff-8370-f42d39cd9494',
                            'fr_property_id' => 10002073,
                            'external_code' => 'INT-0001',
                        ],
                    ],
                    'messages' => [
                        'listings' => [
                            [
                                'listing_id' => '07bcf513-d39a-42ff-8370-f42d39cd9494',
                                'external_code' => 'INT-0001',
                                'error' => [
                                    'message' => 'ignored because the item completed',
                                ],
                            ],
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            headers: [
                'HUB.ID' => 'hub-123',
                'VERIFY-TOKEN' => 'token-456',
            ]
        );

        self::assertTrue($notification->isAuthorized('hub-123', 'token-456'));
        self::assertTrue($notification->isListingStatusEvent());
        self::assertSame('COMPLETED', $notification->taskStatus());
        self::assertSame([
            [
                'listing_id' => '07bcf513-d39a-42ff-8370-f42d39cd9494',
                'external_code' => 'INT-0001',
                'fr_property_id' => 10002073,
                'processing_status' => 'COMPLETED',
                'multimedia' => [],
                'error' => ['message' => 'ignored because the item completed'],
            ],
        ], $notification->listingStatusUpdates());
    }

    public function testItThrowsWhenWebhookHeadersDoNotMatchExpectedValues(): void
    {
        $notification = WebhookNotification::fromRequest(
            rawBody: json_encode(['task' => ['event' => 'LISTING_STATUS']], JSON_THROW_ON_ERROR),
            headers: [
                'HUB.ID' => 'hub-123',
                'VERIFY-TOKEN' => 'token-456',
            ]
        );

        $this->expectException(WebhookException::class);

        $notification->assertAuthorized('hub-123', 'bad-token');
    }

    public function testItRejectsInvalidJsonBodies(): void
    {
        $this->expectException(WebhookException::class);

        WebhookNotification::fromRequest('{invalid-json}', []);
    }
}
