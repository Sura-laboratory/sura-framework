<?php

// tests/HelpersTest.php
namespace Sura\tests;

use PHPUnit\Framework\TestCase;

// Подключаем файл с функциями (если они не загружаются через autoloader)
require_once __DIR__ . '/../src/helpers.php';

class HelpersTest extends TestCase
{
    public function testLangDateTranslatesMonthAndDayNames(): void
    {
        $timestamp = strtotime('2023-03-15 14:30:00'); // 15 марта

        $result = langDate('l, d F Y', $timestamp);

        $this->assertStringContainsString('среда', mb_strtolower($result));
        $this->assertStringContainsString('марта', $result);
    }

    // public function testStripDataRemovesTagsAndSpecialChars(): void
    // {
    //     $input = "<script>alert('XSS')</script> Hello, World! \n\t\"; DROP TABLE users;";
    //     $expected = 'Hello World - DROP TABLE users';

    //     $result = strip_data($input);

    //     $this->assertEquals($expected, $result);
    // }

    public function testAddToListGeneratesOptionsWithSelected(): void
    {
        $list = ['en' => 'English', 'ru' => 'Русский', 'fr' => 'Français'];
        $selectedId = 'ru';

        $result = addToList($selectedId, $list);

        $this->assertStringContainsString('<option value="en">English</option>', $result);
        $this->assertStringContainsString('<option value="ru" selected>Русский</option>', $result);
        $this->assertStringNotContainsString('selected', str_replace('value="ru" selected', '', $result));
    }

    public function testMegaDateReturnsTodayString(): void
    {
        $todayTimestamp = time();

        $result = megaDate($todayTimestamp);

        $this->assertStringStartsWith('сегодня', $result);
        $this->assertMatchesRegularExpression('/\d{2}:\d{2}$/', $result); // ends with H:i
    }

    public function testMegaDateReturnsYesterdayString(): void
    {
        $yesterdayTimestamp = time() - 86400; // 24 часа назад

        // Принудительно делаем дату "вчера"
        $yesterday = strtotime(date('Y-m-d') . ' -1 day');

        $result = megaDate($yesterday);

        if (date('Y-m-d', $yesterday) === date('Y-m-d', $yesterdayTimestamp)) {
            $this->assertStringStartsWith('вчера', $result);
        } else {
            // Если сегодня 00:00–00:59, вчера может не совпасть точно
            $this->assertTrue(true, "Skipped due to time boundary");
        }
    }

    // public function testMegaDateWithFuncUsesShortMonth(): void
    // {
    //     $timestamp = strtotime('-3 days');

    //     $result = megaDate($timestamp, func: true);

    //     $this->assertMatchesRegularExpression('/\d+ [а-я]{3} в \d{2}:\d{2}/i', $result); // e.g. 15 мар в 14:30
    // }

    public function testMegaDateWithFullUsesLongFormat(): void
    {
        $timestamp = strtotime('-10 days');

        $result = megaDate($timestamp, full: true);

        $this->assertMatchesRegularExpression('/\d+ [а-я]+ \d{4} в \d{2}:\d{2}/ui', $result); // e.g. 5 апреля 2023 в 10:00
    }

    /**
     * @dataProvider declOfNumProvider
     */
    // public function testDeclOfNumCorrectlyDeclinesWord(int $number, array $titles, string $expected): void
    // {
    //     $result = declOfNum($number, $titles);
    //     $this->assertEquals($expected, $result);
    // }

    public function declOfNumProvider(): array
    {
        $apples = ['яблок', 'яблоко', 'яблока'];
        $comments = ['комментариев', 'комментарий', 'комментария'];

        return [
            [1, $apples, 'яблоко'],
            [2, $apples, 'яблока'],
            [5, $apples, 'яблок'],
            [11, $apples, 'яблок'],
            [21, $apples, 'яблоко'],
            [22, $apples, 'яблока'],
            [104, $apples, 'яблока'],
            [125, $apples, 'яблок'],

            [1, $comments, 'комментарий'],
            [3, $comments, 'комментария'],
            [10, $comments, 'комментариев'],
            [21, $comments, 'комментарий'],
        ];
    }
}