# Custom functions for NoctalysFramework

Noctalys Framework provides a set of custom functions to simplify common tasks. These functions are designed to be easy to use and integrate seamlessly with the framework's features. Below is a list of some of the most commonly used custom functions, along with examples of how to use them.

## General

- `cast_value($value)`: Automatically cast a value to its appropriate type.
- `esc($value)`: Escape a value for safe use in HTML. It is useful to prevent XSS attacks when displaying user-generated content.

## View Helpers

- `render_component($name, $data)`: Render a component in the view.
- `img($name)`: Automatically find and return the path of an image.
- `svg($name, $attributes)`: Generate an SVG tag with optional attributes.
- `file_content($path)`: Retrieve the content of a file.

## Form Helpers
For more information on forms, see the [Form documentation](./form.md).

- `csrf_token`: Generate a CSRF token.
- `csrf_input`: Generate a hidden input field with the CSRF token.
- `csrf_check`: Verify the CSRF token.

- `value($input)`: Retrieve the value of a form input.