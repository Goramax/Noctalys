# **Creating Pages in Noctalys**

Noctalys provides a structured way to create pages using the **MVC (Model-View-Controller)** pattern. This ensures a clean separation between **logic (Controllers)** and **presentation (Views)**.

## **Page Structure**

Each page in Noctalys follows a **consistent folder structure** inside the `src/Frontend/pages/` directory:

```none
src/
└── Frontend/
    ├── pages/
    │   ├── index/ (main page)
    │   │   ├── index.controller.php
    │   │   ├── index.view.php
    │   │   ├── index.js (optional)
    │   ├── about/
    │   │   ├── about.controller.php
    │   │   ├── about.view.php
    │   │   ├── about.js (optional)
    │   │   ├── about.css (optional)
    ├── layouts/
    │   ├── default.php
    ├── components/
    │   ├── example.php
```

Each page has:

- A **Controller**  : Handles logic, loads data, and renders the view.
- A **View** :Displays the page content.
- An optional **JS** and **CSS** file.

## Creating a New Page

Inside `src/Frontend/pages/`, create a new folder with the **page name**:

### Create the Controller

`src/Frontend/pages/page_name/page_name.controller.php`

```php
<?php

use Noctalys\Framework\View;

class PageNameController {
    public function main() {  // Default method
        $data = [
            'title' => 'Example !',
        ];
        View::render(page='page_name', data=$data, layout='default');
    }
}
```

The `main()` method is called **automatically** by the router.

### Create the View

`src/Frontend/pages/page_name/page_name.view.php`

```php
<h1><?= $title ?></h1>
```

The View displays the content passed by the Controller.

### Optional JS and CSS

`src/Frontend/pages/page_name/page_name.js` and `src/Frontend/pages/page_name/page_name.css` will be auto loaded into the page from the layout.