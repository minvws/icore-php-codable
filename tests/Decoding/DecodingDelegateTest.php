<?php

declare(strict_types=1);

namespace MinVWS\Tests\Codable\Decoding;

use ArrayObject;
use MinVWS\Codable\Decoding\DecodableDelegate;
use MinVWS\Codable\Decoding\Decoder;
use MinVWS\Codable\Decoding\DecodingContainer;
use MinVWS\Codable\Decoding\StaticDecodableDelegate;
use MinVWS\Tests\Codable\Shared\Fruit;
use MinVWS\Tests\Codable\Shared\FruitBasket;
use PHPUnit\Framework\TestCase;

class DecodingDelegateTest extends TestCase
{
    private Decoder $decoder;
    private DecodingContainer $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->decoder = new Decoder();
        $this->container = $this->decoder->decode((object)['fruits' => ['orange', 'banana']]);
    }

    public function testWithoutDelegateTest(): void
    {
        $this->assertEquals(
            (object)['fruits' => ['orange', 'banana']],
            $this->container->decodeObject()
        );

        $this->assertEquals(
            new FruitBasket([Fruit::Orange, Fruit::Banana]),
            $this->container->decodeObject(FruitBasket::class)
        );
    }

    public function testClosureDelegate(): void
    {
        $this->decoder->getContext()->registerDelegate(
            ArrayObject::class,
            fn (DecodingContainer $c) => new ArrayObject($c->decodeArray())
        );

        $this->assertEquals(
            new ArrayObject(['orange', 'banana']),
            $this->container->{'fruits'}->decodeObject(ArrayObject::class)
        );
    }

    public function testDecodableDelegate(): void
    {
        $this->decoder->getContext()->registerDelegate(
            ArrayObject::class,
            new class implements DecodableDelegate
            {
                public function decode(string $class, DecodingContainer $container, ?object $object = null): ArrayObject
                {
                    $fruits = $container->decodeArray(Fruit::class);
                    return new ArrayObject($fruits);
                }
            }
        );

        $this->assertEquals(
            new ArrayObject([Fruit::Orange, Fruit::Banana]),
            $this->container->{'fruits'}->decodeObject(ArrayObject::class)
        );
    }

    public function testStaticDecodableDelegate(): void
    {
        $delegateClass = get_class(
            new class implements StaticDecodableDelegate
            {
                public static function decode(
                    string $class,
                    DecodingContainer $container,
                    ?object $object = null
                ): FruitBasket {
                    return new FruitBasket(
                        array_reverse($container->{'fruits'}->decodeArray(Fruit::class))
                    );
                }
            }
        );

        $this->decoder->getContext()->registerDelegate(FruitBasket::class, $delegateClass);

        $this->assertEquals(
            new FruitBasket([Fruit::Banana, Fruit::Orange]),
            $this->container->decodeObject(FruitBasket::class)
        );
    }
}
