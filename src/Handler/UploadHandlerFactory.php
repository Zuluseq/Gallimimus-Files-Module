<?php

declare(strict_types=1);

namespace GallimimusFilesModule\Handler;

use Psr\Container\ContainerInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;


class UploadHandlerFactory
{
    public function __invoke(ContainerInterface $container) : UploadHandler
    {
		$config = $container->get('config');

        $router   = $container->get(RouterInterface::class);
        $template = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : null;

        return new UploadHandler(
				$template,
				$container->get(AdapterInterface::class),
				$config,
				$router
		);
    }
}
