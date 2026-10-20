<?php
namespace nrv\adapters\controllers\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use nrv\adapters\controllers\middlewares\JsonRenderer;

abstract class AbstractAction
{

    abstract public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface;

    protected function json(ResponseInterface $rs, mixed $data, int $status = 200): ResponseInterface
    {
        return JsonRenderer::render($rs, $status, $data);
    }

    protected function empty(ResponseInterface $rs, int $status = 204): ResponseInterface
    {
        return JsonRenderer::render($rs, $status, null);
    }
}