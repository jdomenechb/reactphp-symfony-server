<?php

/**
 * This file is part of the reactphp-symfony-server package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\ReactPhpSymfonyServer\Setup;

use Symfony\Component\Console\Output\ConsoleOutputInterface;

class ServerSetup
{
    /** @var ConsoleOutputInterface */
    private $consoleOutput;

    /** @var string */
    private $projectRootPath;

    /** @var string */
    private $tmpFile;

    /**
     * ServerSetup constructor.
     *
     * @param string                 $projectRootPath
     * @param string                 $tmpFile
     * @param ConsoleOutputInterface $consoleOutput
     */
    public function __construct(ConsoleOutputInterface $consoleOutput, string $projectRootPath, string $tmpFile)
    {
        $this->projectRootPath = $projectRootPath;
        $this->tmpFile = $tmpFile;
        $this->consoleOutput = $consoleOutput;
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     *
     * @return string
     */
    public function startup(): string
    {
        $this->checkIndexFileExists();

        $this->consoleOutput->writeln('Starting...');

        if (\is_file($this->tmpFile)) {
            \unlink($this->tmpFile);
        }

        $this->createTemporalFile();

        return $this->tmpFile;
    }

    /**
     * Returns the path to the Symfony index file.
     *
     * @return string
     */
    private function getIndexFilePath(): string
    {
        return $this->projectRootPath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php';
    }

    /**
     * Checks that the Symfony index file exists.
     *
     * @throws \RuntimeException
     */
    private function checkIndexFileExists()
    {
        $symfonyIndexFileInfoObj = new \SplFileInfo($this->getIndexFilePath());

        if (!$symfonyIndexFileInfoObj->isFile()) {
            throw new \RuntimeException('The file ' . $symfonyIndexFileInfoObj->getPath() . ' does not exist');
        }
    }

    /**
     * Creates the temporal file from the Symfony index file.
     *
     * @throws \RuntimeException
     * @throws \LogicException
     */
    private function createTemporalFile()
    {
        $symfonyIndexFileObj = new \SplFileObject($this->getIndexFilePath());
        $result = '';

        while ($symfonyIndexFileObj->valid()) {
            $line = $symfonyIndexFileObj->fgets();

            // Do not include the require vendor/autoload line
            if (\preg_match('#^require .*vendor/autoload#', $line)) {
                continue;
            }

            $line = \str_replace('__DIR__', '$projectRootPath . \'/public\'', $line);

            $result .= $line;

            // Stop after the kernel has been created
            if (\preg_match('#\\$kernel\\s*=\\s*#', $line)) {
                break;
            }
        }

        \file_put_contents($this->tmpFile, $result);
    }
}
