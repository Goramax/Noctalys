# Hooks

## List of hooks
- `before_dispatch`: Called before the route is dispatched.
- `after_dispatch`: Called after the route is dispatched.
- `before_layout`: Called before the layout is rendered, with parameters: `$layout`, `$viewFile`, `$layoutFile`, `$data`.
- `before_layout_{name}`: Called before a specific layout is rendered, with parameters: `$viewFile`, `$layoutFile`, `$data`.
- `after_layout`: Called after the layout is rendered, with parameters: `$layout`, `$viewFile`, `$layoutFile`, `$data`.
- `after_layout_{name}`: Called after a specific layout is rendered, with parameters: `$viewFile`, `$layoutFile`, `$data`.
- `before_view`: Called before the view is rendered, with parameters: `$view`, `$viewFile`, `$layout`, `$data`.
- `before_view_{name}`: Called before a specific view is rendered, with parameters: `$viewFile`, `$layout`, `$data`.
- `after_view`: Called after the view is rendered, with parameters: `$view`, `$viewFile`, `$layout`, `$data`.
- `after_view_{name}`: Called after a specific view is rendered, with parameters: `$viewFile`, `$layout`, `$data`.
- `before_component`: Called before a component is rendered, with parameters: `$component`, `$data`.
- `after_component`: Called after a component is rendered, with parameters: `$component`, `$data`.

## Running a hook
To run a hook, use the `run()` method from the `Hooks` class where you want the hook to be executed.   
For example:
```php
use Goramax\NoctalysFramework\Hooks;

Hooks::run('my_custom_hook', ...$params);
```
You can pass any number of parameters to the hook, and they will be passed to the callback function when it is executed.

## Using a hook

To register a hook, use the `add()` method from the `Hooks` class.  
For example:
```php
use Goramax\NoctalysFramework\Hooks;

Hooks::add('before_dispatch', function() {
    // Code to execute before the route is dispatched
});
```

## Template Engine Hooks

All template engines (NoEngine, TwigEngine, SmartyEngine, LatteEngine) support the same set of hooks that execute in the following order:

1. `before_layout` and `before_layout_{name}`
2. `before_view` and `before_view_{name}`
3. View rendering
4. `after_view` and `after_view_{name}`
5. Layout rendering
6. `after_layout` and `after_layout_{name}`

This consistent approach allows for seamless hook usage regardless of the template engine you choose.

## Example: Adding Analytics Code

Here's an example of using hooks to add analytics code to every page:

```php
use Goramax\NoctalysFramework\Hooks;

Hooks::add('after_view', function($view, $viewFile, $layout, $data) {
    // Log page view to analytics service
    logPageView($view);
});
```

## The `hooks.php` file

You can centralize your hook registrations in the `hooks.php` file at the root of your application (e.g. `/noctalys-app/hooks.php`). This file is automatically loaded by the framework if it exists. Place all your `Hooks::add()` calls here to keep your hook logic organized and separate from your main application code.

### Example:
```php
// noctalys-app/hooks.php
use Goramax\NoctalysFramework\Hooks;

Hooks::add('before_view', function($view, $viewFile, $layout, $data) {
    // Custom logic before any view is rendered
});
```

### Example: Dynamic Layout Name

You can also use hooks to specific layout/view/component names. For example, if you want to execute a hook before rendering a specific layout, you can do it like this:

```php
use Goramax\NoctalysFramework\Hooks;

Hooks::add('before_layout_logged', function($viewFile, $layoutFile, $data) {
    // Check if the user is logged in
    if (!isset($data['user']) || !$data['user']->isLoggedIn()) {
        // Redirect to login page or show an error
        header('Location: /login');
        exit;
    }
});