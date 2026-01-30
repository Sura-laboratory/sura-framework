<?php

// tests/Support/RegistryTest.php

namespace Tests\Support;

use PHPUnit\Framework\TestCase;
use Sura\Support\Registry;

class RegistryTest extends TestCase
{
    protected function setUp(): void
    {
        // Очистка хранилища перед каждым тестом, чтобы изолировать состояние
        $reflection = new \ReflectionClass(Registry::class);
        $property = $reflection->getProperty('store');
        $property->setAccessible(true);
        $property->setValue([]);
    }

    public function testSetStoresValueByKey(): void
    {
        $result = Registry::set('test_key', 'test_value');

        $this->assertSame('test_value', $result);
        $this->assertSame('test_value', Registry::get('test_key'));
    }

    public function testGetReturnsNullForNonExistentKey(): void
    {
        $this->assertNull(Registry::get('non_existent_key'));
    }

    public function testExistsReturnsTrueForKeyThatWasSet(): void
    {
        Registry::set('foo', 'bar');

        $this->assertTrue(Registry::exists('foo'));
    }

    public function testExistsReturnsFalseForNonExistentKey(): void
    {
        $this->assertFalse(Registry::exists('unknown'));
    }

    public function testGetReturnsCorrectValueAfterSet(): void
    {
        Registry::set('number', 42);
        Registry::set('bool', true);
        Registry::set('array', [1, 2, 3]);
        Registry::set('null', null);
        Registry::set('object', new \stdClass());

        $this->assertSame(42, Registry::get('number'));
        $this->assertTrue(Registry::get('bool'));
        $this->assertSame([1, 2, 3], Registry::get('array'));
        $this->assertNull(Registry::get('null')); // null — валидное значение

        $retrievedObject = Registry::get('object');
        $this->assertInstanceOf(\stdClass::class, $retrievedObject);
    }

    public function testSetOverwritesExistingKey(): void
    {
        Registry::set('key', 'old_value');
        $this->assertSame('old_value', Registry::get('key'));

        Registry::set('key', 'new_value');
        $this->assertSame('new_value', Registry::get('key'));
    }

    public function testMultipleKeysAreStoredIndependently(): void
    {
        Registry::set('a', 1);
        Registry::set('b', 2);
        Registry::set('c', 3);

        $this->assertSame(1, Registry::get('a'));
        $this->assertSame(2, Registry::get('b'));
        $this->assertSame(3, Registry::get('c'));
        $this->assertTrue(Registry::exists('a'));
        $this->assertFalse(Registry::exists('d'));
    }

    public function testRegistryHandlesNumericAndSpecialKeys(): void
    {
        Registry::set('0', 'zero');
        Registry::set('123', 123);
        Registry::set('user.name', 'John');
        Registry::set('_temp', 'temp_data');

        $this->assertSame('zero', Registry::get('0'));
        $this->assertSame(123, Registry::get('123'));
        $this->assertSame('John', Registry::get('user.name'));
        $this->assertSame('temp_data', Registry::get('_temp'));
    }

    public function testRegistryCanStoreComplexData(): void
    {
        $data = [
            'name' => 'Alice',
            'settings' => [
                'theme' => 'dark',
                'lang' => 'ru'
            ],
            'active' => true
        ];

        Registry::set('user_data', $data);
        $retrieved = Registry::get('user_data');

        $this->assertEquals($data, $retrieved);
        $this->assertTrue($retrieved['active']);
    }
}