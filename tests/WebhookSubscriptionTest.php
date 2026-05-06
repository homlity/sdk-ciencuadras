<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Tests;

use Ciencuadras\Sdk\Webhook\WebhookSubscription;
use PHPUnit\Framework\TestCase;

final class WebhookSubscriptionTest extends TestCase
{
    public function testItBuildsTheExpectedSubscriptionPayload(): void
    {
        self::assertSame(
            ['target' => 'https://example.com/webhooks/ciencuadras'],
            WebhookSubscription::target('https://example.com/webhooks/ciencuadras')
        );
    }

    public function testItRejectsInvalidWebhookUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        WebhookSubscription::target('/relative-path');
    }
}
