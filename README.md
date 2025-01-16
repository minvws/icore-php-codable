# Codable (minvws/codable)

Codable allows you to convert types into and out of an external representation (for example JSON).

It is inspired by Swift's [Encoding/Decoding/Serialization](https://developer.apple.com/documentation/swift/encoding-decoding-and-serialization)
library, but includes some unique features like delegates.

## Features

- Decoding of JSON (or other serialization formats) to PHP objects and types.
- Encoding of PHP objects and types to JSON (or other serialization formats).
- Customize encoding/decoding of objects using PHP attributes.
- Customize encoding/decoding of objects in class.
- Customize encoding/decoding of objects in a delegate class.
- Serialization format agnostic.

## Prerequisites

- PHP >= 8.2
- Composer

## Installation

Install the package through composer. Since this is currently a private package, you must
enable the repository in your `composer.json` file:

```json
{
    "repositories": {
        "minvws/codable": {
            "type": "vcs",
            "url": "git@github.com:minvws/icore-php-codable"
        }
    }
}
```

After that, you can install the package:

```shell
composer require minvws/codable
```

## Usage

### Decoding

There are several ways in which you can use Codable to decode, for example, a JSON snippet:

- Using standard PHP types, similar to `json_decode`, but more strict if you want.
- Using property attributes inside your classes.
- By implementing a static `decode` method inside your class.
- By implementing a `decode` method in a delegate class.

We will use the following JSON snippet to have a look at these different approaches.

```json
{
    "firstName": "John",
    "surname": "Doe",
    "birthDate" : "1994-04-01",
    "preferences": {
      "favoriteFruit": "banana",
      "dislikedFruits": [
        "apple"
      ],
      "favoriteVegetable": "Tomato",
      "dislikedVegetables": [
        "Lettuce",
        "Spinach"
      ]
    }
}
```

#### Decoding using standard PHP types

To decode the JSON snippet to standard PHP types you can simply call the `decode` method on the `JSONDecoder` and call
the `decode` method on the resulting `DecodingContainer` to convert the entire JSON structure to their default PHP
types:

```php
$decoder = new JSONDecoder();
$container = $decoder->decode($json);
$person = $container->decode();
$firstName = $person->firstName;
$dislikedVegetables = $person->preferences->dislikedVegetables;
echo "$firstName doesn't like " . implode(',', $dislikedVegetables) . "\n";
```

But if you want to do that, you could just as well use a simple `json_decode` call.

One of the major benefits of using Codable is that you can be more strict in what types you expect. We could
rewrite the code as follows:

```php
$decoder = new JSONDecoder();
$person = $decoder->decode($json);
$firstName = $person->firstName->decodeString();
$dislikedVegetables = $person->{'preferences'}->{'dislikedVegetables'}->decodeArray('string');
echo "$firstName doesn't like " . implode(',', $dislikedVegetables) . "\n";
```

Although we need a few more method calls the code now automatically throws an exception if the structure of, or types
used in, the JSON is not as we expected.

#### Decoding using property attributes

To make our life a little easier, and use auto-completion in our IDE, we can decode the JSON in our own types. Let's
start by creating some enums for the fruits and vegetables:

```php
enum Fruit: string
{
    case Apple = 'apple';
    case Banana = 'banana';
    case Orange = 'orange';
}

enum Vegetable
{
    case Lettuce;
    case Spinach;
    case Tomato;
}
```

Now let's create classes for storing a person and their fruit and vegetable preferences:

```php
readonly class Preferences implements Decodable
{
    use DecodableSupport;
    
    public function __construct(
        public Fruit $favoriteFruit,
        #[CodableArray(elementType: Fruit::class) public array $dislikedFruits,
        public Vegetable $favoriteVegetable,
        #[CodableArray(elementType: Vegetable::class) public array $dislikedVegetables
    ) {  
    }
}

readonly class Person implements Decodable
{
    use DecodableSupport;
    
    public function __construct(
        public string $firstName,
        #[CodableName('surName')] public string $lastName,
        #[CodableDateTime('Y-m-d')] public ?DateTimeInterface $birthDate,
        public Preferences $preferences
    ) {
    }
}
```

As you can see our classes implement the `Decodable` interface. This lets Codable know that you want to decode the
object yourself. We use the `DecodableSupport` trait so that we don't have to write the decoding code ourselves.
Codable uses reflection to determine field names, types etc. It also checks if it needs to inject values using the
constructor or if it can simply assign the values to object properties (even `private` and `protected` properties
are supported).

Unfortunately PHP doesn't let you statically type arrays, but by using the `CodableArray` attribute we can let
Codable know what types to expect for the array's elements.

The `CodableName` attribute allows us to use a different name for our class property than what is used in the JSON. We
set an expected date/time format for the birthdate using the `CodableDateTime` attribute, although Codable is
just as happy to simply let PHP's DateTime classes determine if they can parse a given date. We can also make fields
optional, in which case a null value will be assigned if the field is missing or contains a null value in the JSON.

Backed enumerations are decoded using their backed value. Enumerations that are not backed by an integer or string value
are decoded based on their name.

To decode the JSON from before we can simply use the following code:

```php
$decoder = new JSONDecoder();
$container = $decoder->decode($json);
$person = $container->decode(Person::class);
$firstName = $person->firstName;
$dislikedVegetables = $person->preferences->dislikedVegetables;
echo "$firstName doesn't like " . implode(',', array_map(fn ($v) => $v->name, $dislikedVegetables)) . "\n";
```

This looks a lot like our initial code snippet, but this time all the objects and values are of our own types and are
statically checked during the decoding process. We also get auto-completion and type checking when writing this
code in an IDE.

#### Implementing the static `decode` method in your class

Sometimes you might want some more control over the decoding process. In that case you can implement the `Decodable`
interface yourself:

```php
final readonly class Person implements Decodable
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?DateTimeInterface $birthDate,
        public Preferences $preferences
    ) {
    }
    
    public static function decode(DecodingContainer $container, ?self $object = null): self
    {
        return new self(
            $container->{'firstName'}->decodeString(),
            $container->{'lastName'}->decodeString(),
            $container->{'birthDate'}->decodeDateTimeIfPresent('Y-m-d'),
            $container->{'preferences'}->decodeObject(Preferences::class)
        );
    }
}
```

The `$object` argument is only used when trying to decode in an existing object. As the `Person` class is read-only
that wouldn't make any sense, so we simply ignore it.

In the sample code above we use the `decodeDateTimeIfPresent` method for the birthdate field. This method checks if
a field exists and has a non-null value before trying to decode to a `DateTime` object. The `DecodingContainer` contains
a `decode<type>`, `decode<type>IfExists` and `decode<type>IfPresent` method for all basic PHP types. The `decode<type>`
variant always expects the field to be there with a non-null value, the `decode<type>IfExists` variant allows the field
to not exist in the JSON, but if it does exist it needs to contain a non-null value.

#### Implementing the `decode` method in a delegate class

Sometimes your code needs to interface with a library you didn't write yourself and contains types you want to decode
into or sometimes you want decode different pieces of JSON to the same type. To make this possible you can choose to
write a delegate class. Your delegate class can either implement the `DecodableDelegate` or
`StaticDecodableDelegate` interface with either a non-static or static `decode` method. Let's look at an example:

```php
readonly class PersonDecodableDelegate implements DecodableDelegate
{
    public function decode(string $class, DecodingContainer $container, ?self $object = null): self
    {
        return new Person(
            $container->{'firstName'}->decodeString(),
            $container->{'lastName'}->decodeString(),
            $container->{'birthDate'}->decodeDateTimeIfPresent('Y-m-d'),
            $container->{'preferences'}->decodeObject(Preferences::class)
        );
    }
}
```

To use this delegate we need to register it in the `DecodingContext`:

```php
$decoder = new JSONDecoder();
$decoder->getContext()->registerDelegate(Person::class, new PersonDecodableDelegate());
$container = $decoder->decode($json);
$person = $container->decode(Person::class);
```

This even works if your class has its own `Decodable` implementation and also works multiple levels deep in the decoding hierarchy.

To register a `StaticDecodableDelegate` you can simply register its class. You can even register a `callable` as a
delegate in which case it will receive the `DecodingContainer` and optional existing instance as its arguments.

### Encoding

Codable also supports encoding of your custom types to JSON (or other serialization formats). There are several
ways to implement this:

- Let Codable map PHP types to JSON types, similar to `json_encode`.
- By implementing the `JsonSerializable` interface.
- Using property attributes inside your classes.
- By implementing an `encode` method inside your class.
- By implementing an `encode` method in a delegate class.

#### Let Codable map PHP types to JSON types

This is the easiest, but also the least flexible, way of encoding your objects:

```php
$person = new Person(...);
$encoder = new JSONEncoder();
echo $encoder->encode($person);
```

This works similar to how PHP's `json_encode` would encode your types, with the most notable exception that
DateTime objects will be encoded to an ISO-8601 date/time string. This also means for your objects that only public
properties will be encoded.

#### Implementing JsonSerializable

If your class implements the `JsonSerializable` interface this will be respected by Codable and the output
of the `jsonSerialize` method will be used for encoding your object. However this is merely meant as a compatibility
layer and as such should only be used for classes you don't control or that have an existing proved implementation.

#### Encoding using property attributes

Just like for decoding, you can add PHP attributes to give Codable hints for encoding your classes. To do so, we can
simply implement the `Encodable` interface and use the `EncodableSupport` trait in our existing `Person` class from
earlier:

```php
readonly class Person implements Decodable, Encodable
{
    use DecodableSupport;
    use EncodableSupport;
    
    // ...
}
```

If you want to use your class for both encoding and decoding purposes you can also rewrite this to:

```php
readonly class Person implements Codable
{
    use CodableSupport;
    
    // ...
}
```

You can use the same attributes as mentioned earlier, but as Codable also has access to your `private` and
`protected` properties there is an additional attribute that might come in handy; `CodableIgnore`. This attribute lets
you control wetter a property should be ignored when encoding, decoding or both.

When you don't want any `private` or `protected` property to be encoded you can replace the `shouldEncodeProperty` as
follows:

```php
readonly class Person implements Codable
{
    use CodableSupport {
        shouldEncodeProperty as baseShouldEncodeProperty;
    }
    
    protected function shouldEncodeProperty(ReflectionCodableProperty $property, EncodingContainer $container): bool
    {
        return $property->isPublic() && $this->baseShouldEncodeProperty($property, $container);
    }
    
    // ...
}
```

Other useful attribute are `CodableCallbacks`, which lets you override the encoding (and/or decoding) behavior of a
certain property, and the `CodableModes` attribute, which lets you only encode (or decode) a property for certain usage
scenario's (for example only encode to the database, but not for API output).

#### Implementing the `encode` method in your class

If you want full control over the encoding process you can also choose to implement your own `encode` method:

```php
final readonly class Person implements Encodable
{
    // ...
    
    public function encode(EncodingContainer $container): void
    {
        $container->{'firstName'} = $this->firstName;
        $container->{'lastName'} = $this->lastName;
        $container->{'birthDate'}->encodeDateTime($this->birthDate, 'Y-m-d');
        $container->{'preferences'} = $this->preferences;
    }
}
```

This way you can even choose to encode nested objects inside the owner class instead of delegating it to the
respective class.

If you assign values to the container Codable will automatically try to determine the best way to encode the value.
But you can also choose to explicitly encode to a certain type using one of the `encode<type>` methods.

### Implementing the `encode` method in a delegate class

Sometimes your code needs to interface with a library you didn't write yourself and contains types you want to encode.
To make this possible you can choose to write a delegate class. Your delegate class can either implement the 
`EncodableDelegate` or `StaticEncodableDelegate` interface with either a non-static or static `encode` method. 
Let's look at an example:

```php
readonly class PersonEncodableDelegate implements EncodableDelegate
{
    public function encode(object $value, EncodingContainer $container): void
    {
        $container->{'firstName'} = $value->firstName;
        $container->{'lastName'} = $value->lastName;
        $container->{'birthDate'}->encodeDateTime($value->birthDate, 'Y-m-d');
        $container->{'preferences'} = $value->preferences;
    }
}
```

To use this delegate we need to register it in the `EncodingContext`:

```php
$person = new Person(...);
$encoder = new JSONEncoder();
$encoder->getContext()->registerDelegate(Person::class, new PersonEncodableDelegate());
$json = $encoder->encode($person);
```

This even works if your class has its own `Encodable` implementation and also works multiple levels deep in the
encoding hierarchy.

To register a `StaticEncodableDelegate` you can simply register its class. You can even register a `callable` as a
delegate in which case it will receive the object and `EncodingContainer as its arguments.

Delegates are a great way of isolating the responsibility of encoding (and decoding) logic. One possible downside
however could be that the delegate won't have access to your object's internal (private/protected) state.

## Contributing

If you encounter any issues or have suggestions for improvements, please feel free to open an issue or submit a pull
request on the GitHub repository of this package.

## License

This repository follows the [REUSE Specfication v3.2](https://reuse.software/spec-3.2/). The code is available under the
EUPL-1.2 license, but the fonts and images are not. Please see [LICENSES/](./LICENSES), [REUSE.toml](./REUSE.toml) and 
the individual `*.license` files (if any) for copyright and license information.

## Part of iCore

This package is part of the iCore project.
