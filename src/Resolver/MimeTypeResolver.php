<?php

declare(strict_types=1);

/**
 * This file is part of the reactphp-symfony-server package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\ReactPhpSymfonyServer\Resolver;

interface MimeTypeResolver
{
    /**
     * @param string $filename
     *
     * @return string|null
     */
    public function fromFileName(string $filename): ?string;
}
