<?php

namespace Goramax\NoctalysFramework\TemplateEngines;

interface TemplateEngineInterface
{
    public function process(string $view, array $data = [], string $layout = 'default'): void;
}