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

use Jdomenechb\ReactPhpSymfonyServer\Resolver\MimeTypeResolver;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RequestLoop
{
    private $kernel;

    /** @var ConsoleOutputInterface */
    private $consoleOutput;

    /** @var string */
    private $projectRootPath;

    /** @var MimeTypeResolver */
    private $mimeTypeResolver;

    /**
     * RequestLoop constructor.
     *
     * @param $kernel
     * @param ConsoleOutputInterface $consoleOutput
     * @param $projectRootPath
     * @param MimeTypeResolver $mimeTypeResolver
     */
    public function __construct($kernel, ConsoleOutputInterface $consoleOutput, MimeTypeResolver $mimeTypeResolver, $projectRootPath)
    {
        $this->kernel = $kernel;
        $this->consoleOutput = $consoleOutput;
        $this->mimeTypeResolver = $mimeTypeResolver;
        $this->projectRootPath = $projectRootPath;
    }

    /**
     * Main method to be called by ReactPHP everytime a file is received.
     *
     * @param ServerRequestInterface $request
     *
     * @throws \RuntimeException
     *
     * @return null|Response
     */
    public function request(ServerRequestInterface $request)
    {
        $method = $request->getMethod();
        $headers = $request->getHeaders();
        $content = $request->getBody()->getContents();
        $path = $request->getUri()->getPath();

        $this->consoleOutput->writeln('RPHPS -- ' . $method . ' ' . $path);

        // Check if the file exists in the server to serve it
        if ($response = $this->serveAssets($method, $path)) {
            return $response;
        }

        // Get POST parameters
        $post = [];

        if (
            isset($headers['Content-Type'])
            && (0 === \strpos($headers['Content-Type'][0], 'application/x-www-form-urlencoded'))
            && \in_array(\strtoupper($method), ['POST', 'PUT', 'DELETE', 'PATCH'])
        ) {
            if (!$content && ($parsedBody = $request->getParsedBody())) {
                $post = $parsedBody;
            } else {
                \parse_str($content, $post);
            }
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

        try {
            /** @var \Symfony\Component\HttpFoundation\Response $sfResponse */
            $sfResponse = $this->kernel->handle($sfRequest);

            $this->kernel->terminate($sfRequest, $sfResponse);

            return new Response(
                $sfResponse->getStatusCode(),
                $sfResponse->headers->all(),
                $sfResponse->getContent()
            );
        } catch (HttpException $e) {
            return new Response(
                $e->getStatusCode(),
                [],
                $e->getStatusCode() . ': ' . $e->getMessage()
            );
        } catch (\Throwable $e) {
            $this->consoleOutput->getErrorOutput()->writeln(['ERROR: ' . $e->getMessage(), $e->getTraceAsString()]);

            return new Response(
                500,
                [],
                'Internal server error'
            );
        }
    }

    /**
     * Checks if an asset can be served and creates a ReactPHP response if it can.
     *
     * @param string $method
     * @param $path
     *
     * @return Response|null
     */
    private function serveAssets(string $method, $path)
    {
        if ('GET' !== $method) {
            return;
        }

        $resource = $this->projectRootPath . DIRECTORY_SEPARATOR . 'public'
            . \str_replace('/', DIRECTORY_SEPARATOR, $path);

        if (!\file_exists($resource) || !\is_file($resource)) {
            return;
        }

        $headers = [];

        // Detect Mime-Type
        $mimeType = $this->mimeTypeResolver->fromFileName($resource);

        if ($mimeType) {
            $headers['Content-Type'] = $mimeType;
        }

        return new Response(200, $headers, \file_get_contents($resource));
    }
}
