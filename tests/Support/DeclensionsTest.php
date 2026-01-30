<?php

declare(strict_types=1);

namespace Tests\Support;


use PHPUnit\Framework\TestCase;
use Sura\Support\Declensions;

class DeclensionsTest extends TestCase
{
    private Declensions $declensions;

    protected function setUp(): void
    {
        // Пример склонений для типа 'message' (сообщения)
        $this->declensions = new Declensions([
            'message' => [
                '',           // [0] — заглушка (не используется)
                'сообщение',  // [1] — 1
                'сообщения',  // [2] — 2, 3, 4
                'сообщений',  // [3] — 5–9, 0, 11–19
            ],
            'comment' => [
                '',          // не используется
                'комментарий',
                'комментария',
                'комментариев',
            ],
        ]);
    }

    /**
     * @dataProvider declensionDataProvider
     */
    public function testMakeWord(int $num, string $type, string $expected): void
    {
        $result = $this->declensions->makeWord($num, $type);
        $this->assertEquals($expected, $result);
    }

    public function declensionDataProvider(): array
    {
        return [
            // Тесты для типа 'message'
            [1, 'message', 'сообщение'],
            [2, 'message', 'сообщения'],
            [3, 'message', 'сообщения'],
            [4, 'message', 'сообщения'],
            [5, 'message', 'сообщений'],
            [10, 'message', 'сообщений'],
            [11, 'message', 'сообщений'], // исключение: 11–19 → форма 3
            [12, 'message', 'сообщений'],
            [21, 'message', 'сообщение'], // 21 % 10 = 1 → форма 1
            [22, 'message', 'сообщения'],
            [25, 'message', 'сообщений'],

            // Тесты для типа 'comment'
            [1, 'comment', 'комментарий'],
            [2, 'comment', 'комментария'],
            [3, 'comment', 'комментария'],
            [4, 'comment', 'комментария'],
            [5, 'comment', 'комментариев'],
            [10, 'comment', 'комментариев'],
            [11, 'comment', 'комментариев'],
            [12, 'comment', 'комментариев'],
            [21, 'comment', 'комментарий'],
            [22, 'comment', 'комментария'],
            [35, 'comment', 'комментариев'],

            // Проверка несуществующего типа
            [5, 'unknown', ''],
        ];
    }

    public function testReturnsEmptyStringForUnknownType(): void
    {
        $result = $this->declensions->makeWord(10, 'unknown');
        $this->assertEmpty($result);
    }
}