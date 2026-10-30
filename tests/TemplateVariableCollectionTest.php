<?php

declare(strict_types=1);

namespace Tests;

use Dzentota\TemplateVariable\TemplateVariable;
use Dzentota\TemplateVariable\TemplateVariableCollection;
use Dzentota\TemplateVariable\Context\EscapeContext;
use PHPUnit\Framework\TestCase;

class TemplateVariableCollectionTest extends TestCase
{
    public function testArrayCreatesCollection(): void
    {
        $data = ['<script>alert(1)</script>', 'safe text', 42];
        $collection = TemplateVariable::create($data);
        
        $this->assertInstanceOf(TemplateVariableCollection::class, $collection);
        $this->assertEquals(3, count($collection));
    }
    
    public function testIteratorWrapsItems(): void
    {
        $data = ['<script>alert(1)</script>', 'safe text'];
        $collection = TemplateVariable::create($data);
        
        $results = [];
        foreach ($collection as $key => $item) {
            $this->assertInstanceOf(TemplateVariable::class, $item);
            $results[$key] = (string) $item;
        }
        
        $this->assertEquals([
            0 => '&lt;script&gt;alert(1)&lt;/script&gt;',
            1 => 'safe text'
        ], $results);
    }
    
    public function testArrayAccessWrapsItems(): void
    {
        $data = ['<script>alert(1)</script>', 'safe text'];
        $collection = TemplateVariable::create($data);
        
        $first = $collection[0];
        $second = $collection[1];
        
        $this->assertInstanceOf(TemplateVariable::class, $first);
        $this->assertInstanceOf(TemplateVariable::class, $second);
        $this->assertEquals('&lt;script&gt;alert(1)&lt;/script&gt;', (string) $first);
        $this->assertEquals('safe text', (string) $second);
    }
    
    public function testArrayAccessBounds(): void
    {
        $data = ['test'];
        $collection = TemplateVariable::create($data);
        
        $this->assertTrue(isset($collection[0]));
        $this->assertFalse(isset($collection[1]));
        
        $this->expectException(\OutOfBoundsException::class);
        $collection[1];
    }
    
    public function testCollectionIsReadOnly(): void
    {
        $data = ['test'];
        $collection = TemplateVariable::create($data);
        
        $this->expectException(\BadMethodCallException::class);
        $collection[0] = 'new value';
    }
    
    public function testCollectionUnsetThrows(): void
    {
        $data = ['test'];
        $collection = TemplateVariable::create($data);
        
        $this->expectException(\BadMethodCallException::class);
        unset($collection[0]);
    }
    
    public function testNestedArraysCreateNestedCollections(): void
    {
        $data = [
            ['<script>alert(1)</script>', 'safe1'],
            ['<img onerror="alert(2)">', 'safe2']
        ];
        $collection = TemplateVariable::create($data);
        
        $nestedCollection = $collection[0];
        $this->assertInstanceOf(TemplateVariableCollection::class, $nestedCollection);
        
        $item = $nestedCollection[0];
        $this->assertInstanceOf(TemplateVariable::class, $item);
        $this->assertEquals('&lt;script&gt;alert(1)&lt;/script&gt;', (string) $item);
    }
    
    public function testCustomEscapeContext(): void
    {
        $data = ['<script>alert(1)</script>'];
        $collection = TemplateVariable::create($data, EscapeContext::JAVASCRIPT);
        
        $item = $collection[0];
        $this->assertInstanceOf(TemplateVariable::class, $item);
        
        // Should use JavaScript escaping as default
        $result = (string) $item;
        $this->assertStringContainsString('\\u003C', $result);
    }
    
    public function testObjectsInCollection(): void
    {
        // Create mock objects with potential XSS
        $user1 = new class {
            public function __toString(): string {
                return '<script>alert("user1")</script>';
            }
        };
        
        $user2 = new class {
            public function __toString(): string {
                return 'Safe User 2';
            }
        };
        
        $data = [$user1, $user2];
        $collection = TemplateVariable::create($data);
        
        $wrappedUser1 = $collection[0];
        $wrappedUser2 = $collection[1];
        
        $this->assertInstanceOf(TemplateVariable::class, $wrappedUser1);
        $this->assertInstanceOf(TemplateVariable::class, $wrappedUser2);
        
        $this->assertEquals('&lt;script&gt;alert(&quot;user1&quot;)&lt;/script&gt;', (string) $wrappedUser1);
        $this->assertEquals('Safe User 2', (string) $wrappedUser2);
    }
    
    public function testTraversableObjects(): void
    {
        // Create a Traversable object
        $traversable = new \ArrayIterator(['<script>alert(1)</script>', 'safe text']);
        $collection = TemplateVariable::create($traversable);
        
        $this->assertInstanceOf(TemplateVariableCollection::class, $collection);
        
        $results = [];
        foreach ($collection as $key => $item) {
            $this->assertInstanceOf(TemplateVariable::class, $item);
            $results[$key] = (string) $item;
        }
        
        $this->assertEquals([
            0 => '&lt;script&gt;alert(1)&lt;/script&gt;',
            1 => 'safe text'
        ], $results);
    }
    
    public function testGetRaw(): void
    {
        $originalData = ['<script>alert(1)</script>', 'safe text'];
        $collection = TemplateVariable::create($originalData);
        
        $this->assertEquals($originalData, $collection->getRaw());
    }
    
    public function testToArray(): void
    {
        $data = ['<script>alert(1)</script>', 'safe text'];
        $collection = TemplateVariable::create($data);
        
        $array = $collection->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(2, count($array));
        $this->assertInstanceOf(TemplateVariable::class, $array[0]);
        $this->assertInstanceOf(TemplateVariable::class, $array[1]);
        $this->assertEquals('&lt;script&gt;alert(1)&lt;/script&gt;', (string) $array[0]);
    }
    
    public function testStringRepresentation(): void
    {
        $data = ['item1', 'item2', 'item3'];
        $collection = TemplateVariable::create($data);
        
        $string = (string) $collection;
        $this->assertStringContainsString('TemplateVariableCollection', $string);
        $this->assertStringContainsString('array', $string);
        $this->assertStringContainsString('[3]', $string);
    }
    
    public function testCountableInterface(): void
    {
        $data = ['a', 'b', 'c', 'd'];
        $collection = TemplateVariable::create($data);
        
        $this->assertEquals(4, count($collection));
        $this->assertEquals(4, $collection->count());
    }
    
    public function testEmptyArray(): void
    {
        $data = [];
        $collection = TemplateVariable::create($data);
        
        $this->assertInstanceOf(TemplateVariableCollection::class, $collection);
        $this->assertEquals(0, count($collection));
        
        $items = [];
        foreach ($collection as $item) {
            $items[] = $item;
        }
        
        $this->assertEquals([], $items);
    }
    
    public function testScalarValuesInCollection(): void
    {
        $data = [42, true, 3.14, null, 'string'];
        $collection = TemplateVariable::create($data);
        
        $results = [];
        foreach ($collection as $key => $item) {
            if ($item === null || is_bool($item)) {
                // bool and null values are returned as-is by create()
                $results[$key] = $item;
            } else {
                $this->assertInstanceOf(TemplateVariable::class, $item);
                $results[$key] = (string) $item;
            }
        }
        
        $this->assertEquals([
            0 => '42',
            1 => true,
            2 => '3.14',
            3 => null,
            4 => 'string'
        ], $results);
    }
    
    public function testContextPropagation(): void
    {
        $data = ['<script>alert(1)</script>'];
        $collection = TemplateVariable::create($data, EscapeContext::JAVASCRIPT);
        
        // The collection should pass the context to each wrapped item
        $item = $collection[0];
        $this->assertInstanceOf(TemplateVariable::class, $item);
        
        // Default string conversion should use the JavaScript context
        $result = (string) $item;
        $this->assertStringContainsString('\\u003C', $result);
    }
} 