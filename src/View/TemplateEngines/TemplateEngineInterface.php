<?php

namespace Goramax\NoctalysFramework\View\TemplateEngines;

interface TemplateEngineInterface
{
    public function process(string $view, array $data = [], string $layout = 'default'): void;
}