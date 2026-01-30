<?php

declare(strict_types=1);

namespace Sura\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Sura\Http\Response;

class ResponseTest extends TestCase
{
    protected function setUp(): void
    {
        // Очищаем заголовки и буфер вывода перед каждым тестом
        if (headers_sent()) {
            $this->fail('Headers already sent');
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        // Сброс заголовков (не физически, но проверяем только то, что header() был вызван)
    }

    public function testJsonSendsContentTypeHeader(): void
    {
        // Включаем буферизацию заголовков
        $this->expectOutputRegex('/^{"message":"test"}$/');

        $response = new Response();
        ob_start();
        try {
            $response->_e_json(['message' => 'test']);
        } finally {
            ob_end_clean();
        }

        $headers = xdebug_get_headers();
        $this->assertContains(
            'Content-Type: application/json; charset=utf-8',
            $headers,
            'Content-Type header should be set to application/json; charset=utf-8'
        );
    }

    public function testJsonEncodesValueCorrectly(): void
    {
        $data = ['name' => 'Андрей', 'items' => [1, 2, '/path/to/file']];

        $expectedJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response = new Response();
        ob_start();
        try {
            $response->_e_json($data);
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertJsonStringEqualsJsonString($expectedJson, $output);
    }

    public function testJsonOutputIsSentToBrowser(): void
    {
        $response = new Response();
        ob_start();
        try {
            $response->_e_json(['success' => true]);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(['success' => true], $decoded);
    }

    public function testJsonThrowsExceptionOnUnencodableData(): void
    {
        // Ресурсы нельзя закодировать в JSON
        $resource = fopen('php://temp', 'r');
        register_shutdown_function(fn() => fclose($resource));

        $response = new Response();
        ob_start();

        $this->expectException(\JsonException::class);
        try {
            $response->_e_json($resource);
        } finally {
            ob_end_clean();
        }
    }

    public function testJsonHandlesNestedArraysAndObjects(): void
    {
        $data = [
            'user' => [
                'id' => 1,
                'name' => 'Alice',
                'active' => true,
                'tags' => ['dev', 'php'],
                'meta' => (object)['since' => '2023']
            ]
        ];

        $response = new Response();
        ob_start();
        try {
            $response->_e_json($data);
            $output = ob_get_clean();
        } finally {
            ob_end_clean();
        }

        $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($data['user']['name'], $decoded['user']['name']);
        $this->assertEquals($data['user']['tags'], $decoded['user']['tags']);
        $this->assertIsObject(json_decode($output)->user->meta);
    }

    public function testJsonEscapesNothingDueToFlags(): void
    {
        $data = ['path' => 'https://example.com/api/v1', 'text' => 'Привет, "мир"!'];

        $response = new Response();
        ob_start();
        try {
            $response->_e_json($data);
            $output = ob_get_clean();
        } finally {
            ob_end_clean();
        }

        $this->assertStringNotContainsString('\\/', $output, 'JSON should not escape slashes');
        $this->assertStringContainsString('Привет', $output, 'Cyrillic should not be escaped');
        $this->assertStringContainsString('https://example.com/api/v1', $output);
    }
}