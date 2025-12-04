<?php

namespace Goramax\NoctalysFramework\Services;

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
            default => throw new \ErrorException('Invalid asset type: ' . $type, 0, E_USER_ERROR),
        };

        $manifestFile = $publicFolder . 'manifest.json';
        if (!file_exists($manifestFile)) {
            throw new \ErrorException('Manifest file not found: ' . $manifestFile . ' please run the build command to generate it', 0, E_USER_ERROR);
        }
        
        $manifest = json_decode(file_get_contents($manifestFile), true);
        $assetKey = $name . '.' . $type;
        

        if (isset($manifest[$assetKey])) {
            return $publicPath . $manifest[$assetKey];
        }
        
        trigger_error('Asset not found in manifest: ' . $name, E_USER_WARNING);
        return '';
    }
}