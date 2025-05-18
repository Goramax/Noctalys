<?php

namespace Goramax\NoctalysFramework;

class Validator
{
    private mixed $value;
    private array $results = [];
    private static array $customValidators = [];
    private bool $invertNext = false;
    private bool $orMode = false;
    private bool $orResult = false;
    private string $orGroupName = '';

    /**
     * Private constructor to force using validate()
     */
    private function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * Static method to initialize validation
     */
    public static function validate(mixed $value): self
    {
        return new self($value);
    }

    /**
     * Register a custom validation method
     */
    public static function registerCustom(string $name, callable $callback): void
    {
        self::$customValidators[$name] = $callback;
    }

    /**
     * Register a validation result
     */
    private function addResult(string $name, bool $result): self
    {
        $finalResult = $this->invertNext ? !$result : $result;
        
        if ($this->orMode) {
            $this->orResult = $this->orResult || $finalResult;
            // Ne pas écrire dans $results ici, seulement à la fin du groupe OR
        } else {
            $this->results[$name] = $finalResult ? 1 : 0;
        }
        
        $this->invertNext = false;
        return $this;
    }
    
    /**
     * Inverts the result of the next validation
     */
    public function not(): self
    {
        $this->invertNext = true;
        return $this;
    }
    
    /**
     * Starts an OR validation chain
     * 
     * @param string $groupName Name to use for the validation result
     */
    public function startOr(string $groupName = 'or_validation'): self
    {
        $this->orMode = true;
        $this->orResult = false;
        $this->orGroupName = $groupName;
        return $this;
    }
    
    /**
     * Ends an OR validation chain
     */
    public function endOr(): self
    {
        if ($this->orMode) {
            $this->results[$this->orGroupName] = $this->orResult ? 1 : 0;
            $this->orMode = false;
            $this->orResult = false;
        }
        return $this;
    }

    /**
     * Check if the value is a string
     */
    public function string(): self
    {
        return $this->addResult('string', is_string($this->value));
    }

    /**
     * Check if the value is empty or null
     */
    public function empty(): self
    {
        return $this->addResult('empty', empty($this->value));
    }

    /**
     * Check if a string is not empty
     */
    public function required(): self
    {
        return $this->addResult('required', !empty($this->value));
    }

    /**
     * Check if the string is a number
     */
    public function number(): self
    {
        return $this->addResult('number', is_numeric($this->value));
    }

    /**
     * Check if the value is an integer
     */
    public function integer(): self
    {
        return $this->addResult('integer', filter_var($this->value, FILTER_VALIDATE_INT) !== false);
    }

    /**
     * Check if the value is a floating point number
     */
    public function float(): self
    {
        return $this->addResult('float', filter_var($this->value, FILTER_VALIDATE_FLOAT) !== false);
    }

    /**
     * check minimum value
     */
    public function min(float $min): self
    {
        return $this->addResult('min', $this->value >= $min);
    }

    /**
     * check maximum value
     */
    public function max(float $max): self
    {
        return $this->addResult('max', $this->value <= $max);
    }

    /**
     * Check if the value is positive
     */
    public function positive(): self
    {
        return $this->addResult('positive', $this->value > 0);
    }

    /**
     * Check if the value is negative
     */
    public function negative(): self
    {
        return $this->addResult('negative', $this->value < 0);
    }

    /**
     * Check if the value is a valid email
     */
    public function email(): self
    {
        return $this->addResult('email', filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false);
    }

    /**
     * Check if the string is a URL
     */
    public function url(): self
    {
        return $this->addResult('url', filter_var($this->value, FILTER_VALIDATE_URL) !== false);
    }

    /**
     * Check if the value is boolean
     */
    public function boolean(): self
    {
        return $this->addResult('boolean', is_bool(filter_var($this->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)));
    }

    /**
     * Check minimum length
     */
    public function minLength(int $min): self
    {
        return $this->addResult('minLength', strlen((string)$this->value) >= $min);
    }

    /**
     * Check maximum length
     */
    public function maxLength(int $max): self
    {
        return $this->addResult('maxLength', strlen((string)$this->value) <= $max);
    }

    /**
     * Check value exact length
     */
    public function length(int $length): self
    {
        return $this->addResult('length', strlen((string)$this->value) === $length);
    }

    /**
     * Check if the value is a valid date
     */
    public function date(string $format = 'Y-m-d'): self
    {
        $d = \DateTime::createFromFormat($format, $this->value);
        return $this->addResult('date', $d && $d->format($format) === $this->value);
    }

    /**
     * Check if the value is a valid json
     */
    public function json(): self
    {
        json_decode($this->value);
        return $this->addResult('json', json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Check if the value is a valid ipv4 address
     */
    public function ipv4(): self
    {
        return $this->addResult('ipv4', filter_var($this->value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false);
    }

    /**
     * Check if the value is a valid ipv6 address
     */
    public function ipv6(): self
    {
        return $this->addResult('ipv6', filter_var($this->value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false);
    }

    /**
     * Check if the value is a valid base64 string
     */
    public function base64(): self
    {
        return $this->addResult('base64', base64_encode(base64_decode($this->value, true)) === $this->value);
    }

    /**
     * Check if the value is in an array
     */
    public function inArray(array $allowed): self
    {
        return $this->addResult('inArray', in_array($this->value, $allowed));
    }

    /**
     * Check if the value is not in an array
     */
    public function notInArray(array $notAllowed): self
    {
        return $this->addResult('notInArray', !in_array($this->value, $notAllowed));
    }

    /**
     * Check if the value equals any of the provided parameters
     * @param mixed ...$allowed
     */
    public function inValues(...$allowed): self
    {
        foreach ($allowed as $value) {
            if ($this->value === $value) {
                return $this->addResult('inValues', true);
            }
        }
        return $this->addResult('inValues', false);
    }

    /**
     * Check if the value matches a regular expression
     */
    public function regex(string $pattern): self
    {
        return $this->addResult('regex', preg_match($pattern, $this->value) === 1);
    }

    /**
     * Dynamic call for custom validation methods
     */
    public function custom(string $name, ...$args): self
    {
        if (isset(self::$customValidators[$name])) {
            $result = call_user_func_array(self::$customValidators[$name], array_merge([$this->value], $args));
            return $this->addResult($name, (bool)$result);
        }
        $this->addResult($name, false);
        return $this;
    }

    /**
     * Return the validation results
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Check if all validations passed
     */
    public function isValid(): bool
    {
        return !in_array(0, $this->results, true);
    }
}
