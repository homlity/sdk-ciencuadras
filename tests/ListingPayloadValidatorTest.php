<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Tests;

use Ciencuadras\Sdk\Schema\ListingPayloadValidator;
use Ciencuadras\Sdk\Schema\SchemaCatalog;
use PHPUnit\Framework\TestCase;

final class ListingPayloadValidatorTest extends TestCase
{
    public function testCreateValidationPassesForMinimumPayload(): void
    {
        $validator = new ListingPayloadValidator(new SchemaCatalog());

        $validator->validateCreatePayload([
            [
                'external_code' => 'A-1',
                'client_id' => 'df03d199-be5c-4c5c-98f6-849361cb7fae',
                'offer' => 'sell',
                'property_type' => 'house',
                'description' => 'desc',
                'price' => 1,
                'address' => ['address' => 'x'],
                'locations' => ['location_point' => ['latitude' => 1, 'longitude' => 1]],
                'area' => 10,
                'listing_contact' => ['emails' => [], 'phones' => []],
            ],
        ]);

        self::assertTrue(true);
    }

    public function testCreateValidationFailsWhenMissingRequiredField(): void
    {
        $validator = new ListingPayloadValidator(new SchemaCatalog());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field');

        $validator->validateCreatePayload([
            [
                'external_code' => 'A-1',
            ],
        ]);
    }
}
