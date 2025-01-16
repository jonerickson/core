<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\JsonSchema\Tests\Metadata\Property\Factory;

use ApiPlatform\JsonSchema\Metadata\Property\Factory\SchemaPropertyMetadataFactory;
use ApiPlatform\JsonSchema\Tests\Fixtures\DummyWithCustomOpenApiContext;
use ApiPlatform\JsonSchema\Tests\Fixtures\DummyWithEnum;
use ApiPlatform\JsonSchema\Tests\Fixtures\Enum\IntEnumAsIdentifier;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class SchemaPropertyMetadataFactoryTest extends TestCase
{
    public function testEnum(): void
    {
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $apiProperty = new ApiProperty(builtinTypes: [new Type(builtinType: 'object', nullable: true, class: IntEnumAsIdentifier::class)]);
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithEnum::class, 'intEnumAsIdentifier')->willReturn($apiProperty);
        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithEnum::class, 'intEnumAsIdentifier');
        $this->assertEquals(['type' => ['integer', 'null'], 'enum' => [1, 2, null]], $apiProperty->getSchema());
    }

    public function testWithCustomOpenApiContext(): void
    {
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $apiProperty = new ApiProperty(
            builtinTypes: [new Type(builtinType: 'object', nullable: true, class: IntEnumAsIdentifier::class)],
            openapiContext: ['type' => 'object', 'properties' => ['alpha' => ['type' => 'integer']]],
        );
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithCustomOpenApiContext::class, 'acme')->willReturn($apiProperty);
        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithCustomOpenApiContext::class, 'acme');
        $this->assertEquals([], $apiProperty->getSchema());
    }

    public function testWithCustomOpenApiContextWithoutTypeDefinition(): void
    {
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $apiProperty = new ApiProperty(
            openapiContext: ['description' => 'My description'],
            builtinTypes: [new Type(builtinType: 'bool')],
        );
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithCustomOpenApiContext::class, 'foo')->willReturn($apiProperty);
        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithCustomOpenApiContext::class, 'foo');
        $this->assertEquals([
            'type' => 'boolean',
        ], $apiProperty->getSchema());

        $apiProperty = new ApiProperty(
            openapiContext: ['iris' => 'https://schema.org/Date'],
            builtinTypes: [new Type(builtinType: 'object', class: \DateTimeImmutable::class)],
        );
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithCustomOpenApiContext::class, 'bar')->willReturn($apiProperty);
        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithCustomOpenApiContext::class, 'bar');
        $this->assertEquals([
            'type' => 'string',
            'format' => 'date-time',
        ], $apiProperty->getSchema());
    }
}
