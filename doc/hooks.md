# Hooks

## List of hooks
- `before_dispatch`: Called before the route is dispatched.
- `after_dispatch`: Called after the route is dispatched.
- `before_view`: Called before the view is rendered.
- `after_view`: Called after the view is rendered.
- `before_component`: Called before a component is rendered.
- `after_component`: Called after a component is rendered.

## Running a hook
To run a hook, use the `run()` method from the `Hook` class where you want want the hook to be executed.   
 For example:
```php
use Framework\Core\Hook;

Hook::run('my_custom_hook', ...$params);
```
You can pass any number of parameters to the hook, and they will be passed to the callback function when it is executed.

## Using a hook

To register a hook, use the `add_hook()` method from the `Hook` class.  
 For example:
```php
use Framework\Core\Hook;

Hook::add('before_dispatch', function() {
    // Code to execute before the route is dispatched
});
```