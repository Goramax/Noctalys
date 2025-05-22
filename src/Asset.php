<?php

namespace Goramax\NoctalysFramework;

use Goramax\NoctalysFramework\ErrorHandler;

class Asset
{
    /**
     * Get the path to the asset
     * @param string $type
     * @param string $name
     * get the path to the minified asset with hash
     * get the asset from public/assets folder
     * @return string
     */
    public static function getPath(string $type, string $name): string
    {
        $publicPath = '/public/assets/';
        $publicFolder = DIRECTORY . $publicPath;
        $folder = '';

        match ($type) {
            'css' => $folder = 'styles',
            'js' => $folder = 'js',
            default => ErrorHandler::fatal('Invalid asset type: ' . $type),
        };

        $manifestFile = $publicFolder . 'manifest.json';
        if (!file_exists($manifestFile)) {
            ErrorHandler::fatal('Manifest file not found: ' . $manifestFile . ' please run the build command to generate it');
        }
        
        $manifest = json_decode(file_get_contents($manifestFile), true);
        $assetKey = $name . '.' . $type;
        

        if (isset($manifest[$assetKey])) {
            return $publicPath . $manifest[$assetKey];
        }
        
        ErrorHandler::warning('Asset not found in manifest: ' . $name, 'warning');
        return $publicPath . $folder . '/' . $name;
        }
}