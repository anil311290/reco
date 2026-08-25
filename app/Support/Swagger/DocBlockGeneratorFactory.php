<?php

declare(strict_types=1);

namespace App\Support\Swagger;

use L5Swagger\ConfigFactory;
use L5Swagger\Generator;
use L5Swagger\GeneratorFactory as BaseGeneratorFactory;
use L5Swagger\SecurityDefinitions;

class DocBlockGeneratorFactory extends BaseGeneratorFactory
{
    public function __construct(private readonly ConfigFactory $configFactory)
    {
        parent::__construct($configFactory);
    }

    public function make(string $documentation): Generator
    {
        $config = $this->configFactory->documentationConfig($documentation);

        return new DocBlockGenerator(
            $config['paths'],
            $config['constants'] ?? [],
            $config['generate_yaml_copy'] ?? false,
            new SecurityDefinitions(
                $config['securityDefinitions']['securitySchemes'] ?? [],
                $config['securityDefinitions']['security'] ?? []
            ),
            $config['scanOptions'] ?? []
        );
    }
}
