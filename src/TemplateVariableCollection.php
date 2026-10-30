<?php

declare(strict_types=1);

namespace Dzentota\TemplateVariable;

use Dzentota\TemplateVariable\Context\EscapeContext;
use Dzentota\TemplateVariable\Escaper\EscaperInterface;
use IteratorAggregate;
use ArrayAccess;
use Countable;
use Traversable;

/**
 * Collection wrapper that automatically applies TemplateVariable wrapping to each item
 * 
 * This class ensures that when iterating over collections or accessing array elements,
 * each item is automatically wrapped with TemplateVariable for consistent security.
 * 
 * Implements AppSec Manifesto principles:
 * - Rule #1: Context-Specific Escaping at the sink
 * - Rule #3: Preserving Data Validity throughout the application
 */
class TemplateVariableCollection implements IteratorAggregate, ArrayAccess, Countable
{
    public function __construct(
        private readonly array|Traversable $collection,
        private readonly EscapeContext $defaultContext = EscapeContext::HTML,
        private readonly ?EscaperInterface $escaper = null
    ) {}

    /**
     * Iterator that automatically wraps each item with TemplateVariable
     */
    public function getIterator(): Traversable
    {
        foreach ($this->collection as $key => $item) {
            yield $key => TemplateVariable::create($item, $this->defaultContext, $this->escaper);
        }
    }

    /**
     * Array access that automatically wraps accessed items
     */
    public function offsetExists(mixed $offset): bool
    {
        if (is_array($this->collection)) {
            return isset($this->collection[$offset]);
        }
        
        // For Traversable objects, we need to check by iteration
        foreach ($this->collection as $key => $value) {
            if ($key === $offset) {
                return true;
            }
        }
        
        return false;
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (is_array($this->collection)) {
            if (!array_key_exists($offset, $this->collection)) {
                throw new \OutOfBoundsException("Offset {$offset} does not exist");
            }
            return TemplateVariable::create($this->collection[$offset], $this->defaultContext, $this->escaper);
        }
        
        // For Traversable objects
        foreach ($this->collection as $key => $item) {
            if ($key === $offset) {
                return TemplateVariable::create($item, $this->defaultContext, $this->escaper);
            }
        }
        
        throw new \OutOfBoundsException("Offset {$offset} does not exist");
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('TemplateVariableCollection is read-only');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('TemplateVariableCollection is read-only');
    }

    /**
     * Count interface for collections
     */
    public function count(): int
    {
        if (is_array($this->collection)) {
            return count($this->collection);
        }
        
        if ($this->collection instanceof Countable) {
            return $this->collection->count();
        }
        
        // Fallback: iterate to count
        $count = 0;
        foreach ($this->collection as $item) {
            $count++;
        }
        
        return $count;
    }

    /**
     * True when the collection is non-empty (objects are always truthy in `if (...)`).
     */
    public function isTruthy(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Get the raw underlying collection
     */
    public function getRaw(): array|Traversable
    {
        return $this->collection;
    }

    /**
     * Convert collection to array (with wrapped items)
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * String representation (for debugging)
     */
    public function __toString(): string
    {
        $type = is_array($this->collection) ? 'array' : get_class($this->collection);
        $count = $this->count();
        return "TemplateVariableCollection({$type}[{$count}])";
    }
} 