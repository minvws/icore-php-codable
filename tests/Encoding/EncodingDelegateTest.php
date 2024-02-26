<?php

declare(strict_types=1);

namespace Encoding;

use MinVWS\Codable\Encoding\EncodableDelegate;
use MinVWS\Codable\Encoding\Encoder;
use MinVWS\Codable\Encoding\EncodingContainer;
use MinVWS\Codable\Encoding\StaticEncodableDelegate;
use MinVWS\Tests\Codable\Shared\Fruit;
use PHPUnit\Framework\TestCase;

class EncodingDelegateTest extends TestCase
{
    private Encoder $encoder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->encoder = new Encoder();
    }

    public function testWithoutDelegateTest(): void
    {
        $this->assertEquals('banana', $this->encoder->encode(Fruit::Banana));
    }

    public function testClosureDelegate(): void
    {
        $this->encoder->getContext()->registerDelegate(
            Fruit::class,
            fn(Fruit $value, EncodingContainer $c) => $c->encodeString($value->name)
        );
        $this->assertEquals('Banana', $this->encoder->encode(Fruit::Banana));
    }

    public function testEncodableDelegate(): void
    {
        $this->encoder->getContext()->registerDelegate(
            Fruit::class,
            new class implements EncodableDelegate
            {
                public function encode(object $value, EncodingContainer $container): void
                {
                    $index = array_search($value, Fruit::cases());
                    assert(is_int($index));
                    $container->encodeInt($index);
                }
            }
        );

        $this->assertEquals(1, $this->encoder->encode(Fruit::Banana));
    }

    public function testStaticEncodableDelegate(): void
    {
        $delegateClass = get_class(
            new class implements StaticEncodableDelegate
            {
                public static function encode(object $object, EncodingContainer $container): void
                {
                    assert($object instanceof Fruit);
                    $container->encode('fruit:' . $object->value);
                }
            }
        );

        $this->encoder->getContext()->registerDelegate(Fruit::class, $delegateClass);
        $this->assertEquals('fruit:banana', $this->encoder->encode(Fruit::Banana));
    }
}
