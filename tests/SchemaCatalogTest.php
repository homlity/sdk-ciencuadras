<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Tests;

use Ciencuadras\Sdk\Schema\SchemaCatalog;
use PHPUnit\Framework\TestCase;

final class SchemaCatalogTest extends TestCase
{
    public function testItLoadsOpenApiAndReturnsListingRequiredFields(): void
    {
        $catalog = new SchemaCatalog();

        self::assertSame('1.0.0', $catalog->version());
        self::assertContains('client_id', $catalog->listingCreateRequiredFields());
        self::assertContains('listing_id', $catalog->listingUpdateRequiredFields());
        self::assertContains('status', $catalog->listingStatusRequiredFields());
    }
}
