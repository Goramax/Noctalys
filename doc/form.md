# Making forms in Noctalys

Noctalys provides some helper functions to make forms easier to create and manage.

## Keeping the old input values

When a form is submitted, you can retreive the old value of inputs from the `$_POST` variable.
This is useful when you want to repopulate the form with the old values after a validation error or when the form is submitted.
To do this, you can use the `value()` function, which takes the name of the input as an argument and returns the old value if it exists, or an empty string if it doesn't.
```html
<form method="post">
    <input type="text" name="name" placeholder="Name" value="<?= value('name', 'Default pre-filled value') ?>">
    <input type="submit" value="Submit">
</form>
```

## CSRF token
Noctalys provides a simple CSRF token system to protect your forms from cross-site request forgery attacks.
To use the CSRF token system, you need to include a hidden input field in your form with the CSRF token value. You can generate the CSRF token using the `csrf_token()` function, which returns a unique token for the current session.

**view**
```html
<form method="post">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="submit" value="Submit">
</form>
```

You can also use the `csrf_input()` function, which generates the hidden input field for you.
```html
<form method="post">
    <?= csrf_input() ?>
    <input type="submit" value="Submit">
</form>
```

To validate the CSRF token, you can use the `csrf_check()` function, which takes the token as an argument and returns true if the token is valid, or false if it isn't.

**controller**
```php
class FormController
{
    public function main()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Check if the CSRF token is valid
            if (!csrf_check()) {
                echo "Invalid CSRF token.";
                return;
            }
            // Process the form
        }
        // Render the form view
    }
}

```

Or use the `csrf_check()` function frol the `Form` class, which takes the token as an argument for custom token validation.
```php
use Goramax\NoctalysFramework\Form;

class FormController
{
    public function main()
    {
        // Check if the CSRF token is valid
        if (Form::csrf_check($_POST['csrf_token'])) {
            // Process the form
        } else {
            echo "Invalid CSRF token.";
        }
    }
}
```

## File upload
Noctalys provides a simple file upload system to handle file uploads in your forms.
To use the file upload system, you need to set the `enctype` attribute of your form to `multipart/form-data` and use the `file()` function to get the uploaded file.

**view**
```html
<form method="post" enctype="multipart/form-data">
    <input type="file" name="file">
    <input type="submit" value="Submit">
</form>
```
Then, in your controller, you can use the `upload()` function from the `File` class to handle the file upload.

**controller**
```php
use Goramax\NoctalysFramework\File;
class FormController
{
    public function main()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle the file upload
            $file = File::upload('file', 'public/uploads');
            if ($file) {
                echo "File uploaded successfully: " . $file;
            } else {
                echo "File upload failed.";
            }
        }
        // Render the form view
    }
}
```
The `upload()` function takes four arguments:
- `inputName` : The name of the input field in the form.
- `destination`: The destination directory where the file will be uploaded.
- `allowedExtensions` = the allowed file extensions (default: ['jpg','jpeg','png','svg','webp','pdf'].)
- `maxSize` = The maximum file size in bytes (default: 500000 bytes = 500 KB)
- `canCreateDir` = If true, the function will create the destination directory if it doesn't exist (default: true)
