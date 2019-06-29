<?php

declare(strict_types=1);

namespace GallimimusFilesModule\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;

use function time;

class PingHandler implements RequestHandlerInterface
{
	var $a = "x";

	public function __construct($a)
	{
		$this->a = $a;
	}

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        return new JsonResponse(['gallimimus files: ack' => $this->a." xx ".time()]);
    }
}
