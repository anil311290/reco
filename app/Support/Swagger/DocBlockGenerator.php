<?php

declare(strict_types=1);

namespace App\Support\Swagger;

use L5Swagger\Generator as BaseGenerator;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;
use OpenApi\Generator as OpenApiGenerator;

/**
 * l5-swagger v11 registers only the attribute factory, so `@OA\*` docblocks in
 * app/Docs are ignored. This restores docblock parsing alongside attributes.
 */
class DocBlockGenerator extends BaseGenerator
{
    protected function setAnalyser(OpenApiGenerator $generator): void
    {
        $analyser = $this->scanOptions[self::SCAN_OPTION_ANALYSER] ?? null;

        if (! empty($analyser)) {
            $generator->setAnalyser($analyser);

            return;
        }

        $generator->setAnalyser(new ReflectionAnalyser([
            new DocBlockAnnotationFactory(),
            new AttributeAnnotationFactory(),
        ]));
    }
}
