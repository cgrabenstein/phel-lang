<?php

declare(strict_types=1);

namespace PhelTest\Integration\Build\Command;

use Gacela\Framework\Bootstrap\GacelaConfig;
use Gacela\Framework\Gacela;
use Phel\Build\Infrastructure\Command\BuildCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

final class BuildCommandTest extends TestCase
{
    private BuildCommand $command;

    public static function setUpBeforeClass(): void
    {
        Gacela::bootstrap(__DIR__, GacelaConfig::withPhpConfigDefault());
    }

    protected function setUp(): void
    {
        $this->command = new BuildCommand();
    }

    /**
     * @runInSeparateProcess
     *
     * @preserveGlobalState disabled
     */
    public function test_build_project(): void
    {
        $this->expectOutputString("This is printed\n");

        $this->command->run(
            new ArrayInput([
                '--no-source-map' => true,
                '--no-cache' => true,
            ]),
            $this->createStub(OutputInterface::class),
        );

        self::assertFileExists(__DIR__ . '/out/phel/core.phel');
        self::assertFileExists(__DIR__ . '/out/phel/core.php');
        self::assertFileExists(__DIR__ . '/out/hello.phel');
        self::assertFileExists(__DIR__ . '/out/hello.php');
    }

    /**
     * @runInSeparateProcess
     *
     * @preserveGlobalState disabled
     *
     * @depends test_build_project
     */
    public function test_build_project_cached(): void
    {
        // Mark file cache invalid by setting the modification time to 0
        touch(__DIR__ . '/out/hello.php', 1);

        $this->expectOutputString("This is printed\n");

        $this->command->run(
            new ArrayInput([
                '--no-source-map' => true,
            ]),
            $this->createStub(OutputInterface::class),
        );

        self::assertFileExists(__DIR__ . '/out/phel/core.phel');
        self::assertFileExists(__DIR__ . '/out/phel/core.php');
        self::assertFileExists(__DIR__ . '/out/hello.phel');
        self::assertFileExists(__DIR__ . '/out/hello.php');
    }
}
