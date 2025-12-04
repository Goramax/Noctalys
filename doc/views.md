# Using Views, Layouts & Components

## Views

### Using Views
Noctalys uses a **simple templating system** to create views. Views are stored in the `src/Frontend/pages/{page_name}` directory and are named `page_name.view.php`.
Views are PHP files that can contain HTML, CSS, and JavaScript code. They can also include PHP code to display dynamic content.
Views are rendered using the `View::render()` method, which takes the following parameters:
- `page`: The name of the page to render (without the `.view.php` extension).
- `data`: An associative array of data to pass to the view
- `layout`: The name of the layout to use (optional). If not specified, the default layout will be used.

### Creating a View
You can create a new view by creating a new PHP file in the `src/Frontend/pages/{page_name}` directory. The new view must be named with `.view.php` extension.


## Layouts

### Using Layouts
Layouts are templates that define the overall structure of a page. They can include headers, footers, and other common elements that should be present on multiple pages.
Layouts are stored by defaukt in the `src/Frontend/layouts/` directory. The default layout is `default.php`, which can be customized as needed from the [configuration file](./config.md).
Layouts can be used in the controller by specifying the layout name when rendering a view. For example, to use the `default` layout, you would call:

```php
View::render(page='page_name', data=$data, layout='default');
```
### Creating a Layout

You can create a new layout by creating a new PHP file in the `src/Frontend/layouts/` directory and must be named with `.layout.php` extension.
The new layout must echo the `$_view` variable, which contains the rendered view content. For example, to create a new layout called `custom.layout.php`, you would create a file named `custom.layout.php` in the `src/Frontend/layouts/` directory with the following content:

```html
<?php render_component('head') ?>
<header>
    <h1>
        <?= isset($page_title) && $page_title 
            ? $page_title : "Noctalys" ?>
    </h1>
</header>
<main class="container">
    <?= $_view ?>
</main>
<footer>
    <p>This is the footer</p>
</footer>
```
You can pass data to the layout in the same way as you would with a view, in the controller with the `View::render()` method.

## Components

### Using Components
Components are reusable pieces of code that can be included in views and layouts. They are stored in the `src/Frontend/components/` directory and can be used to create common UI elements, such as headers, footers, and navigation menus.
Components are created as PHP files and can be included in views and layouts using the `render_component()` function. For example, to include a component called `header.php`, you would use the following code:

```php
<?php render_component('header', $data) ?>
```
The `render_component()` function takes the name of the component as an argument and includes the corresponding PHP file from the `src/Frontend/components/` directory.

### Creating a Component

You can create a new component by creating a new PHP file in the `src/Frontend/components/` directory. The new component must be named with `.component.php` extension.
The new component can contain any HTML and PHP code that you want to include in your views and layouts. For example, to create a new component called `header.component.php`, you would create a file named `header.component.php` in the `src/Frontend/components/` directory with the following content:

```html
<header>
    <h1>
        <?= isset($page_title) && $page_title 
            ? $page_title : "Noctalys" ?>
    </h1>
    <nav>
        <ul>
            <li><a href="/">Home</a></li>
            <li><a href="/about">About</a></li>
        </ul>
    </nav>
</header>
```
The component can then be included in any view or layout using the `render_component()` function.

### Passing Data to Components
You can pass data to components in the same way as you would with views and layouts, by passing an associative array as the second argument to the `render_component()` function.
