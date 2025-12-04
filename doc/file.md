# File Management in NoctalysFramework

The `File` class in NoctalysFramework provides a set of utility methods for handling file operations such as uploading, reading, listing, deleting, renaming, and retrieving file metadata. These methods are designed to simplify file management tasks while ensuring security and reliability.

## Uploading Files

The `upload()` method allows you to upload files from a form submission to a specified destination directory.

### Parameters
- `inputName` (string): The name of the file input field in the form.
- `destination` (string): The directory path where the file should be stored.
- `allowedExtensions` (array): List of allowed file extensions (default: `['jpg', 'jpeg', 'png', 'svg', 'webp', 'pdf']`).
- `maxSize` (int): Maximum allowed file size in bytes (default: `500000`).
- `canCreateDir` (bool): Whether to create the destination directory if it doesn't exist (default: `true`).

### Returns
- (string|null): The sanitized filename if the upload was successful, or `null` otherwise.

### Example
```php
use Goramax\NoctalysFramework\File;

$file = File::upload('file_input', 'uploads');
if ($file) {
    echo "File uploaded successfully: " . $file;
} else {
    echo "File upload failed.";
}
```

## Reading Files

The `read()` method reads the contents of a file.

### Parameters
- `filePath` (string): The path to the file to read, relative to the project root.

### Returns
- (string|null): The contents of the file, or `null` if the file cannot be read.

### Example
```php
$content = File::read('path/to/file.txt');
if ($content) {
    echo $content;
} else {
    echo "File not found.";
}
```

## Listing Files

The `list()` method retrieves the names and paths of files in a directory.

### Parameters
- `path` (string): The directory to scan.

### Returns
- (array): An array of files with their names and paths.

### Example
```php
$files = File::list('uploads');
foreach ($files as $file) {
    echo $file['name'] . " - " . $file['path'] . "\n";
}
```

## Deleting Files

The `delete()` method deletes a file from the filesystem.

### Parameters
- `filePath` (string): The path to the file to delete, relative to the project root.

### Returns
- (bool): `true` if the file was successfully deleted, `false` otherwise.

### Example
```php
if (File::delete('uploads/file.txt')) {
    echo "File deleted successfully.";
} else {
    echo "Failed to delete file.";
}
```

## Renaming Files

The `rename()` method renames a file in the filesystem.

### Parameters
- `filePath` (string): The path to the file to rename, relative to the project root.
- `newName` (string): The new name for the file (filename only, not path).

### Returns
- (bool): `true` if the file was successfully renamed, `false` otherwise.

### Example
```php
if (File::rename('uploads/old_name.txt', 'new_name.txt')) {
    echo "File renamed successfully.";
} else {
    echo "Failed to rename file.";
}
```

## Retrieving File Metadata

### MIME Type
The `getMime()` method retrieves the MIME type of a file.

#### Parameters
- `filePath` (string): The path to the file.

#### Returns
- (string|null): The MIME type, or `null` on failure.

#### Example
```php
$mime = File::getMime('uploads/file.txt');
if ($mime) {
    echo "MIME type: " . $mime;
} else {
    echo "Failed to retrieve MIME type.";
}
```

### File Size
The `size()` method retrieves the size of a file in bytes.

#### Parameters
- `filePath` (string): The path to the file.

#### Returns
- (int|null): The file size in bytes, or `null` on failure.

#### Example
```php
$size = File::size('uploads/file.txt');
if ($size) {
    echo "File size: " . $size . " bytes.";
} else {
    echo "Failed to retrieve file size.";
}
```

## Sanitizing Filenames

The `sanitizeName()` method sanitizes a filename to make it safe for filesystem operations.

### Parameters
- `name` (string): The original filename to sanitize.

### Returns
- (string): The sanitized filename.

### Example
```php
$safeName = File::sanitizeName('unsafe file name.txt');
echo $safeName; // Outputs: unsafe_file_name.txt
```

