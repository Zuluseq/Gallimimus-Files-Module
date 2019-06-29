<?php

declare(strict_types=1);

namespace GallimimusFilesModule;

/**
 * The configuration provider for the GallimimusFilesModule module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies() : array
    {
        return [
            'invokables' => [
				Zend\Expressive\Template\TemplateRendererInterface::class => Zend\Expressive\Plates\PlatesRenderer::class
            ],
            'factories'  => [
				Handler\FileHandler::class => Handler\FileHandlerFactory::class,
				Handler\PingHandler::class => Handler\PingHandlerFactory::class
            ],
        ];
    }

    /**
     * Returns the templates configuration
     */
    public function getTemplates() : array
    {
        return [
            'paths' => [
                'gallimimus-files-module'    => [__DIR__ . '/../templates/'],
            ],
        ];
    }
}
