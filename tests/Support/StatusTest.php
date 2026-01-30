<?php

// tests/Support/StatusTest.php

namespace Tests\Support;

use PHPUnit\Framework\TestCase;
use Sura\Support\Status;

class StatusTest extends TestCase
{
    /**
     * Проверяет, что все константы статуса определены и имеют правильные значения.
     */
    public function testAllStatusConstantsAreDefinedAndUnique(): void
    {
        $reflection = new \ReflectionClass(Status::class);
        $constants = $reflection->getConstants();

        // Ожидаем 35 констант (от OK=1 до SUBSCRIPTION=35)
        $this->assertCount(35, $constants, 'Должно быть ровно 35 констант статуса.');

        // Проверяем, что значения — это целые числа от 1 до 35 без дубликатов
        $expectedValues = range(1, 35);
        $actualValues = array_values($constants);

        sort($actualValues);

        $this->assertEquals($expectedValues, $actualValues, 'Значения статусов должны быть числами от 1 до 35 без пропусков и дубликатов.');
    }

    /**
     * Проверяет конкретные известные статусы на соответствие значений.
     */
    public function testSpecificStatusesHaveExpectedValues(): void
    {
        $this->assertSame(1, Status::OK);
        $this->assertSame(0, Status::BAD); // Обрати внимание: значение 0
        $this->assertSame(2, Status::LOGGED);
        $this->assertSame(3, Status::BAD_LOGGED);
        $this->assertSame(4, Status::BAD_MAIL);
        $this->assertSame(5, Status::BAD_PASSWORD);
        $this->assertSame(6, Status::PASSWORD_DOESNT_MATCH);
        $this->assertSame(7, Status::BAD_USER);
        $this->assertSame(8, Status::NOT_USER);
        $this->assertSame(9, Status::NOT_VALID);
        $this->assertSame(10, Status::BAD_CODE);
        $this->assertSame(23, Status::BAD_RIGHTS);
        $this->assertSame(35, Status::SUBSCRIPTION);
    }

    /**
     * Проверяет, что нет дублирующихся значений среди констант.
     */
    public function testNoDuplicateStatusValues(): void
    {
        $reflection = new \ReflectionClass(Status::class);
        $constants = $reflection->getConstants();
        $values = array_values($constants);

        $uniqueValues = array_unique($values);
        $duplicates = array_diff_assoc($values, $uniqueValues);

        $this->assertCount(0, $duplicates, 'Не должно быть дублирующихся значений статусов.');
    }
}