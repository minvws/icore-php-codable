<?php

namespace MinVWS\Tests\Codable\Shared;

use DateTimeInterface;
use MinVWS\Codable\Coding\Codable;
use MinVWS\Codable\Coding\CodableSupport;
use MinVWS\Codable\Decoding\DecodingContainer;
use MinVWS\Codable\Encoding\EncodingContainer;
use MinVWS\Codable\Encoding\EncodingContext;
use MinVWS\Codable\Reflection\Attributes\CodableArray;
use MinVWS\Codable\Reflection\Attributes\CodableArrayObject;
use MinVWS\Codable\Reflection\Attributes\CodableCallbacks;
use MinVWS\Codable\Reflection\Attributes\CodableDateTime;
use MinVWS\Codable\Reflection\Attributes\CodableIgnore;
use MinVWS\Codable\Reflection\Attributes\CodableModes;
use MinVWS\Codable\Reflection\Attributes\CodableName;

class Person implements Codable
{
    use CodableSupport;

    #[CodableDateTime(format: 'Y-m-d')]
    public ?DateTimeInterface $birthDate = null;

    public ?Fruit $favoriteFruit = null;

    #[CodableArray(elementType: Fruit::class)]
    private array $dislikedFruits = [];

    #[CodableIgnore]
    private array $dislikedVegetables = [];

    #[CodableCallbacks(encode: [self::class, 'encodeCountry'], decode: [self::class, 'decodeCountry'])]
    public ?string $country = null;

    /**
     * @var Collection<string>
     */
    #[CodableArrayObject(factory: [Collection::class, 'forArray'], elementType: 'string')]
    public Collection $notes;

    public function __construct(
        public readonly string $firstName,
        public readonly ?string $infix,
        #[CodableName('surname')] public readonly string $lastName,
    ) {
        $this->notes = new Collection();
    }


    /**
     * @phpstan-ignore-next-line
     */
    private function encodeCountry(EncodingContainer $container): void
    {
        $container->{'address'}->{'country'}->encode($this->country);
    }

    /**
     * @phpstan-ignore-next-line
     */
    private static function decodeCountry(DecodingContainer $container, ?object $object): mixed
    {
        return $container->{'address'}->{'country'}->decodeStringIfPresent();
    }

    public function resetDislikedFruits(): void
    {
        $this->dislikedFruits = [];
    }

    public function addDislikedFruit(Fruit $fruit): void
    {
        $this->dislikedFruits[] = $fruit;
    }

    /**
     * @return array<Fruit>
     */
    public function getDislikedFruits(): array
    {
        return $this->dislikedFruits;
    }

    /**
     * @return array<Vegetable>
     */
    public function getDislikedVegetables(): array
    {
        return $this->dislikedVegetables;
    }

    public function resetDislikedVegetables(): void
    {
        $this->dislikedVegetables = [];
    }

    public function addDislikedVegetable(Vegetable $vegetable): void
    {
        $this->dislikedVegetables[] = $vegetable;
    }
}
