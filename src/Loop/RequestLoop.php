<?php

/**
 * This file is part of the reactphp-symfony-server package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\ReactPhpSymfonyServer\Loop;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\HttpFoundation\Request;

class RequestLoop
{
    private $kernel;

    /** @var ConsoleOutputInterface */
    private $consoleOutput;

    /** @var string */
    private $projectRootPath;

    /**
     * RequestLoop constructor.
     * @param $kernel
     * @param ConsoleOutputInterface $consoleOutput
     * @param $projectRootPath
     */
    public function __construct($kernel, ConsoleOutputInterface $consoleOutput, $projectRootPath)
    {
        $this->kernel = $kernel;
        $this->consoleOutput = $consoleOutput;
        $this->projectRootPath = $projectRootPath;
    }

    public function request(ServerRequestInterface $request)
    {
        $method = $request->getMethod();
        $headers = $request->getHeaders();
        $content = $request->getBody()->getContents();
        $path = $request->getUri()->getPath();

        $this->consoleOutput->writeln('RPHPS -- ' . $method . ' ' . $path);


        // Check if the file exists in the server to serve it
        if ($method === 'GET') {
            $resource = $this->projectRootPath . DIRECTORY_SEPARATOR . 'public'
                . str_replace('/', DIRECTORY_SEPARATOR, $path);

            if (file_exists($resource) && is_file($resource)) {
                return new Response(200, [], file_get_contents($resource));
            }
        }

        // Get POST parameters
        $post = [];

        if (
            \in_array(strtoupper($method), ['POST', 'PUT', 'DELETE', 'PATCH'])
            && isset($headers['Content-Type'])
            && (0 === strpos($headers['Content-Type'][0], 'application/x-www-form-urlencoded'))
        ) {
            parse_str($content, $post);
        }

        // Create the Symfony request
        $sfRequest = new Request(
            $request->getQueryParams(),
            $post,
            [],
            [],
            $request->getUploadedFiles(),
            [],
            $content
        );

        $sfRequest->setMethod($method);
        $sfRequest->headers->replace($headers);
        $sfRequest->server->set('REQUEST_URI', $path);

//    if (isset($headers['Host'])) {
//        $sfRequest->server->set('SERVER_NAME', explode(':', $headers['Host'])[0]);
//    }

        try {
            /** @var \Symfony\Component\HttpFoundation\Response $sfResponse */
            $sfResponse = $this->kernel->handle($sfRequest);

            $this->kernel->terminate($sfRequest, $sfResponse);

            return new Response(
                $sfResponse->getStatusCode(),
                $sfResponse->headers->all(),
                $sfResponse->getContent()
            );
        } catch (\Throwable $e) {
            $this->consoleOutput->getErrorOutput()->writeln($e->getMessage(), PHP_EOL, $e->getTraceAsString());
        }
    }
}