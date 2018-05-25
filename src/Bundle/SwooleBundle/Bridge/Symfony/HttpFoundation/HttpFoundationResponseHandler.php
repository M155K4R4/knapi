<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation;

use RuntimeException;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class HttpFoundationResponseHandler implements HttpFoundationResponseHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(HttpFoundationResponse $httpFoundationResponse, SwooleResponse $swooleResponse): void
    {
        if ($httpFoundationResponse instanceof StreamedResponse) {
            throw new RuntimeException(\sprintf('HttpFoundation "StreamedResponse" response object is not yet supported'));
        }

        foreach ($httpFoundationResponse->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            /** @var array $values */
            foreach ($values as $value) {
                $swooleResponse->header($name, (string) $value);
            }
        }

        $swooleResponse->status($httpFoundationResponse->getStatusCode());

        if ($httpFoundationResponse instanceof BinaryFileResponse) {
            $swooleResponse->sendfile($httpFoundationResponse->getFile()->getFilename());
        } else {
            $swooleResponse->end($httpFoundationResponse->getContent());
        }
    }
}