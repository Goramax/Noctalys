# Validator Class

The `Validator` class in NoctalysFramework provides a fluent, chainable API for validating values with built-in and custom rules. It is designed to simplify input validation in your application.

## Features

- Chainable validation methods
- Built-in rules for common data types and formats
- Support for custom validation methods
- Logical NOT and OR chaining
- Retrieve all results or check if all validations passed

## Usage

### Basic Example

```php
use Goramax\NoctalysFramework\Validator;

$result = Validator::validate($value)
    ->required()
    ->string()
    ->minLength(3)
    ->maxLength(20)
    ->isValid();

if ($result) {
    // Value is valid
} else {
    // Validation failed
}
```

### Getting All Results

```php
$validator = Validator::validate($value)
    ->required()
    ->email();

$results = $validator->getResults(); // ['required' => 1, 'email' => 0]
```

### Using NOT

```php
$isNotEmpty = Validator::validate($value)
    ->not()->empty()
    ->isValid();
```

### OR Chaining

```php
$isValid = Validator::validate($value)
    ->or('email_or_url')
        ->email()
        ->url()
    ->endOr()
    ->isValid();
```

## Built-in Validation Methods

- `string()`: Value is a string
- `empty()`: Value is empty or null
- `required()`: Value is not empty
- `number()`: Value is numeric
- `integer()`: Value is an integer
- `float()`: Value is a float
- `min($min)`: Value is greater than or equal to `$min`
- `max($max)`: Value is less than or equal to `$max`
- `positive()`: Value is positive
- `negative()`: Value is negative
- `email()`: Value is a valid email address
- `url()`: Value is a valid URL
- `boolean()`: Value is boolean
- `minLength($min)`: String length is at least `$min`
- `maxLength($max)`: String length is at most `$max`
- `length($length)`: String length is exactly `$length`
- `date($format)`: Value matches date format (default: `Y-m-d`)
- `json()`: Value is valid JSON
- `ipv4()`: Value is a valid IPv4 address
- `ipv6()`: Value is a valid IPv6 address
- `base64()`: Value is a valid base64 string
- `inArray($allowed)`: Value is in the given array
- `notInArray($notAllowed)`: Value is not in the given array
- `inValues(...$allowed)`: Value equals any of the provided values
- `regex($pattern)`: Value matches the given regular expression

## Custom Validators

You can register your own validation methods:

```php
Validator::registerCustom('is_foo', function($value) {
    return $value === 'foo';
});

$isFoo = Validator::validate('foo')->custom('is_foo')->isValid();
```

## Methods

- `static validate($value): self` — Start a validation chain
- `not(): self` — Invert the next validation result
- `or($groupName = 'or_validation'): self` — Start an OR chain
- `endOr(): self` — End an OR chain
- `custom($name, ...$args): self` — Use a custom validator
- `getResults(): array` — Get all validation results
- `isValid(): bool` — Check if all validations passed
