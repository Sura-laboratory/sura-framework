<?php

declare(strict_types=1);

namespace Sura\Tests\Filesystem;

use PHPUnit\Framework\TestCase;
use Sura\Filesystem\Filesystem;

class FilesystemTest extends TestCase
{
    private string $testDir;
    private string $testFile;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/sura-filesystem-test';
        $this->testFile = $this->testDir . '/test.txt';

        // Очистка перед тестом
        if (is_dir($this->testDir)) {
            Filesystem::delete($this->testDir);
        }
    }

    protected function tearDown(): void
    {
        // Очистка после теста
        if (is_dir($this->testDir)) {
            Filesystem::delete($this->testDir);
        }
    }

    public function testCreateDir(): void
    {
        $result = Filesystem::createDir($this->testDir);
        $this->assertTrue($result);
        $this->assertDirectoryExists($this->testDir);
    }

    public function testCheckFileOrDir(): void
    {
        // Проверка директории
        Filesystem::createDir($this->testDir);
        $this->assertTrue(Filesystem::check($this->testDir));

        // Проверка файла
        file_put_contents($this->testFile, 'test');
        $this->assertTrue(Filesystem::check($this->testFile));

        // Проверка несуществующего
        $this->assertFalse(Filesystem::check($this->testDir . '/not-exists'));
    }

    public function testCopyFile(): void
    {
        Filesystem::createDir($this->testDir);
        $source = $this->testDir . '/source.txt';
        $dest = $this->testDir . '/dest.txt';

        file_put_contents($source, 'copy content');

        $result = Filesystem::copy($source, $dest);
        $this->assertTrue($result);
        $this->assertFileExists($dest);
        $this->assertEquals('copy content', file_get_contents($dest));
    }

    public function testCopyFileToExistingFileShouldFail(): void
    {
        Filesystem::createDir($this->testDir);
        $source = $this->testDir . '/source.txt';
        $dest = $this->testDir . '/dest.txt';

        file_put_contents($source, 'source');
        file_put_contents($dest, 'dest');

        $result = Filesystem::copy($source, $dest);
        $this->assertFalse($result); // Не должен перезаписывать
    }

    public function testDeleteFile(): void
    {
        Filesystem::createDir($this->testDir);
        file_put_contents($this->testFile, 'delete me');

        $this->assertTrue(Filesystem::check($this->testFile));

        $result = Filesystem::delete($this->testFile);
        $this->assertTrue($result);
        $this->assertFalse(is_file($this->testFile));
    }

    public function testDeleteDirectoryRecursively(): void
    {
        $nestedDir = $this->testDir . '/subdir/nested';
        $nestedFile = $nestedDir . '/file.txt';

        mkdir($nestedDir, 0777, true);
        file_put_contents($nestedFile, 'nested content');

        $this->assertDirectoryExists($nestedDir);
        $this->assertFileExists($nestedFile);

        $result = Filesystem::delete($this->testDir);
        $this->assertTrue($result);
        $this->assertFalse(is_dir($this->testDir));
    }

    public function testDirSizeEmptyDir(): void
    {
        Filesystem::createDir($this->testDir);
        $size = Filesystem::dirSize($this->testDir);
        $this->assertEquals(0, $size);
    }

    public function testDirSizeWithFiles(): void
    {
        Filesystem::createDir($this->testDir . '/sub');
        file_put_contents($this->testDir . '/file1.txt', '12345'); // 5 bytes
        file_put_contents($this->testDir . '/sub/file2.txt', '67890'); // 5 bytes

        $size = Filesystem::dirSize($this->testDir);
        $this->assertEquals(10, $size);
    }

    public function testHumanFileSizeZero(): void
    {
        $result = Filesystem::humanFileSize(0);
        $this->assertEquals('0B', $result);
    }

    public function testHumanFileSizeKilobytes(): void
    {
        $result = Filesystem::humanFileSize(2048);
        $this->assertEquals('2.0KB', $result);
    }

    public function testHumanFileSizeMegabytes(): void
    {
        $result = Filesystem::humanFileSize(1_572_864); // ~1.5 MB
        $this->assertEquals('1.5MB', $result);
    }

    public function testHumanFileSizeWithCustomDecimals(): void
    {
        $result = Filesystem::humanFileSize(1_048_576, 2); // 1.00 MB
        $this->assertEquals('1.00MB', $result);
    }

    public function testHumanFileSizeGigabytes(): void
    {
        $result = Filesystem::humanFileSize(3_221_225_472); // 3 GB
        $this->assertEquals('3.0GB', $result);
    }
}