<?php
namespace Goramax\NoctalysFramework\Services;

use Exception;

class File
{
    /**
     * Uploads a file from a form submission to the specified destination
     *
     * @param string $inputName The name of the file input field in the form
     * @param string $destination The directory path where the file should be stored
     * @param array $allowedExtensions List of allowed file extensions (default: ['jpg', 'jpeg', 'png', 'svg', 'webp', 'pdf'])
     * @param int $maxSize Maximum allowed file size in bytes (default: 500000)
     * @param bool $canCreateDir Whether to create the destination directory if it doesn't exist (default: true)
     * @param bool $forceInsecure Disable MIME type validation (default: false)
     * @return string|null The sanitized filename if upload was successful, null otherwise
     */
    public static function upload(
        string $inputName,
        string $destination,
        array $allowedExtensions = ['jpg', 'jpeg', 'png', 'svg', 'webp', 'pdf'],
        int $maxSize = 500000,
        bool $canCreateDir = true,
        bool $forceInsecure = false
    ): string|null {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES[$inputName];
        var_dump($_FILES[$inputName]);

        if ($file['error'] !== UPLOAD_ERR_OK) throw new \ErrorException('File upload error: ' . $file['error'], 0, E_USER_ERROR);
        if ($file['size'] > $maxSize) throw new \ErrorException('File size exceeds limit', 0, E_USER_ERROR);

        $originalName = basename($file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $safeName = self::sanitizeName(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;

        if (!in_array($extension, $allowedExtensions)) throw new \ErrorException('Invalid file type, allowed types: ' . implode(', ', $allowedExtensions), 0, E_USER_ERROR);

        if (!$forceInsecure) {
            self::validateMime($file['tmp_name'], $extension);
        }

        if ($canCreateDir && !is_dir($destination)) {
            if (!mkdir($destination, recursive: true)) throw new \ErrorException('Failed to create directory', 0, E_USER_ERROR);
        } elseif (!$canCreateDir && !is_dir($destination)) throw new \ErrorException('Directory does not exist', 0, E_USER_ERROR);

        $target = rtrim($destination, '/') . '/' . $safeName;
        $i = 1;
        while (file_exists($target)) {
            $safeName = self::sanitizeName(pathinfo($originalName, PATHINFO_FILENAME)) . "_$i.$extension";
            $target = rtrim($destination, '/') . '/' . $safeName;
            $i++;
        }

        if (!move_uploaded_file($file['tmp_name'], $target)) throw new \ErrorException('Failed to move file', 0, E_USER_ERROR);

        return $safeName;
    }

    /**
     * Reads the contents of a file
     *
     * @param string $filePath The path to the file to read, relative to the project root
     * @return string|null The contents of the file or null if the file cannot be read
     */
    public static function read(string $filePath): string|null {
        try {
            $path = DIRECTORY . DIRECTORY_SEPARATOR . $filePath;
            if (!file_exists(filename: $path)) return null;
            return file_get_contents($path);
        } catch (Exception) {
            trigger_error("File not found: " . $filePath, E_USER_WARNING);
            return null;
        }
    }

    /**
     * Gets the file names in a directory
     * 
     * @param string $directory The directory to scan
     * @return array An array of file with name and path
     */public static function list(string $path): array {
        try {
            $path = DIRECTORY . DIRECTORY_SEPARATOR . $path;
            if (!file_exists(filename: $path)) return [];
            if (!is_dir($path)) return [];
            $files = [];
            $dir = new \DirectoryIterator($path);
            foreach ($dir as $file) {
                if ($file->isFile()) {
                    $files[] = [
                        'name' => $file->getFilename(),
                        'path' => str_replace(DIRECTORY . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                    ];
                }
            }
            return $files;
        } catch (Exception) {
            trigger_error("Directory not found: " . $path, E_USER_WARNING);
            return [];
        }
    }
    
    /**
     * Deletes a file from the filesystem
     *
     * @param string $filePath The path to the file to delete, relative to the project root
     * @return bool True if the file was successfully deleted, false otherwise
     */
    public static function delete(string $filePath): bool {
        try {
            $path = DIRECTORY . DIRECTORY_SEPARATOR . $filePath;
            if (!file_exists($path)) return false;
            return unlink($path);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Renames a file in the filesystem
     *
     * @param string $filePath The path to the file to rename, relative to the project root
     * @param string $newName The new name for the file (filename only, not path)
     * @return bool True if the file was successfully renamed, false otherwise
     */
    public static function rename(string $filePath, string $newName): bool {
        try {
            $path =  DIRECTORY . DIRECTORY_SEPARATOR . $filePath;
            if (!file_exists($path)) return false;
            $dir = dirname($path);
            return rename($path, $dir . '/' . $newName);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Gets the MIME type of a file
     *
     * @param string $filePath The name of the file
     * @param array $context The context containing the path information
     * @return string|null The MIME type or null on failure
     */
    public static function getMime(string $filePath): string|null {
        try {
            $path = DIRECTORY . DIRECTORY_SEPARATOR . $filePath;
            if (!file_exists($path)) return null;
            return mime_content_type($path);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Gets the size of a file in bytes
     *
     * @param string $filePath The name of the file
     * @param array $context The context containing the path information
     * @return int|null The file size in bytes or null on failure
     */
    public static function size(string $filePath): int|null {
        try {
            $path = DIRECTORY . DIRECTORY_SEPARATOR . $filePath;
            if (!file_exists($path)) return null;
            return filesize($path);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Sanitizes a filename to make it safe for filesystem operations
     *
     * @param string $name The original filename to sanitize
     * @return string The sanitized filename
     */
    public static function sanitizeName(string $name): string {
        return substr(preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name), 0, 100);
    }

    /**
     * Ensures the uploaded file's MIME type matches its extension
     *
     * @param string $tmpPath Path to the temporary uploaded file
     * @param string $extension File extension to validate against
     * @throws \ErrorException on mismatch or unknown extension
     */
    private static function validateMime(string $tmpPath, string $extension): void {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpPath) ?: '';
        // Extract subtype (part after '/'), fallback to empty
        $parts = explode('/', $mimeType, 2);
        $subtype = $parts[1] ?? '';
        // Accept when subtype exactly matches extension or ends with '+extension'
        if ($subtype !== $extension && !str_ends_with($subtype, '+' . $extension)) {
            throw new \ErrorException("MIME type '{$mimeType}' does not match extension '{$extension}'", 0, E_USER_ERROR);
        }
    }
}