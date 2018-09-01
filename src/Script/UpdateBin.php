<?php

/**
 * This file is part of the reactphp-symfony-server package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\ReactPhpSymfonyServer\Script;

use Composer\Script\Event;

class UpdateBin
{
    /**
     * @param Event $event
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public static function update(Event $event)
    {
        $baseDir = getcwd();
        $symfonyIndexFile = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php';
        $libraryDir = dirname(__DIR__, 2);

        $symfonyIndexFileObj = new \SplFileObject($symfonyIndexFile);

        if (!$symfonyIndexFileObj->isFile()) {
            throw new \RuntimeException('The file ' . $symfonyIndexFile . ' does not exist. This library cannot be used.');
        }

        $result = '';

        while ($symfonyIndexFileObj->valid()) {
            $line = $symfonyIndexFileObj->fgets();

            if (strpos($line, 'Request::createFromGlobals()') !== false) {
                echo 'Found! Line ', $symfonyIndexFileObj->getCurrentLine();
            }
        }


    }
}