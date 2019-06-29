<?php

declare(strict_types=1);

namespace GallimimusFilesModule\Handler;

use Psr\Container\ContainerInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;


class FileHandlerFactory
{
    public function __invoke(ContainerInterface $container) : FileHandler
    {
		$config = $container->get('config');

        $router   = $container->get(RouterInterface::class);
        $template = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : null;

//$template = $container->get(TemplateRendererInterface::class);

        return new FileHandler(
				$template,
				$container->get(AdapterInterface::class),
				$config,
				$router
		);
    }
}
