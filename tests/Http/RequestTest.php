<?php

declare(strict_types=1);

namespace Sura\Tests\Http;

use PHPUnit\Framework\TestCase;
use Sura\Http\Request;

class RequestTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        $this->request = new Request();
        // Очистка глобальных данных перед каждым тестом
        $_POST = [];
        $_GET = [];
    }

    // public function testFilterPostPriority(): void
    // {
    //     $_POST['test'] = '  <script>alert(1)</script>Test\nString  ';
    //     $_GET['test'] = 'Should not be used';

    //     $result = $this->request->filter('test', 50, true);

    //     $this->assertEquals('&lt;script&gt;alert(1)&lt;/script&gt;Test<br>String', $result);
    // }

    // public function testFilterGetFallback(): void
    // {
    //     $_GET['test'] = '  GET Value\nWith Newline  ';

    //     $result = $this->request->filter('test', 30, false);

    //     $this->assertEquals('GET Value<br>With Newline', $result);
    // }

    public function testFilterEmptySourceReturnsEmptyString(): void
    {
        $result = $this->request->filter('');
        $this->assertSame('', $result);
    }

    public function testFilterArrayInGetReturnsEmptyString(): void
    {
        $_GET['test'] = ['array', 'value'];

        $result = $this->request->filter('test');
        $this->assertSame('', $result);
    }

    public function testFilterNotSetReturnsEmptyString(): void
    {
        $result = $this->request->filter('not_exists');
        $this->assertSame('', $result);
    }

    // public function testTextFilterBasic(): void
    // {
    //     $input = "  <b>Test</b>\nInput with \"quotes\"  ";
    //     $result = $this->request->textFilter($input, 100, true);

    //     $this->assertEquals('Test<br>Input with &quot;quotes&quot;', $result);
    // }

    public function testTextFilterNoStripTags(): void
    {
        $input = '<b>Keep tags</b>';
        $result = $this->request->textFilter($input, 50, false);

        $this->assertEquals('&lt;b&gt;Keep tags&lt;/b&gt;', $result);
    }

    // public function testTextFilterTrimsAndStripsSlashes(): void
    // {
    //     $input = "  \\'Dangerous\\' \r\nnewline  ";
    //     $result = $this->request->textFilter($input, 100, true);

    //     $this->assertEquals('&#039;Dangerous&#039; <br>newline', $result);
    // }

    public function testIntFromPost(): void
    {
        $_POST['number'] = '42';
        $result = $this->request->int('number', -1);

        $this->assertSame(42, $result);
    }

    public function testIntFromGet(): void
    {
        $_GET['number'] = '123';
        $result = $this->request->int('number', -1);

        $this->assertSame(123, $result);
    }

    public function testIntNotSetReturnsDefault(): void
    {
        $result = $this->request->int('missing', 404);
        $this->assertSame(404, $result);
    }

    public function testIntInvalidValueReturnsZero(): void
    {
        $_POST['number'] = 'abc';
        $result = $this->request->int('number');

        $this->assertSame(0, $result);
    }

    public function testCheckAjaxTrueWhenPostHasAjaxYes(): void
    {
        $_POST['ajax'] = 'yes';
        $result = $this->request->checkAjax();

        $this->assertTrue($result);
    }

    public function testCheckAjaxFalseWhenAjaxNotYes(): void
    {
        $_POST['ajax'] = 'no';
        $result = $this->request->checkAjax();

        $this->assertFalse($result);
    }

    public function testCheckAjaxFalseWhenAjaxNotSet(): void
    {
        $result = $this->request->checkAjax();

        $this->assertFalse($result);
    }

    public function testCheckAjaxFalseWhenEmptyPost(): void
    {
        $_POST = [];
        $result = $this->request->checkAjax();

        $this->assertFalse($result);
    }
}