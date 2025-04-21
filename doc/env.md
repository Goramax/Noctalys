# Environment files

## Overview

Noctalys uses environment files to manage configuration settings for different environments (e.g., development, production). These files are typically named `.env` and are located in the root directory of your project.

## Creating Environment Files
To create an environment file, simply create a new file in the root directory of your project and name it `.env`. You can create multiple environment files for different environments, such as `.env.development`, `.env.production`, etc.
The framework will automatically load the appropriate environment file based on the current environment.

## Loading Environment Files
The framework automatically loads the environment file based on the current environment. You can set the current environment by defining the `APP_ENV` when starting the application. For example, to set the environment to development, you can use the following command:
```bash
php -S localhost:8000 -t public/ -d APP_ENV=development
```
This will load the `.env.development` file and make the settings available to your application.

## Adding Environment Variables

To add environment variables to your environment file, simply add them in the following format:
```env
VARIABLE_NAME=value
```
For example:
```env
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=secret
DB_NAME=my_database
```

Since Noctalys uses a custom environment loader, you need to use the Env class to access the environment variables in your application. You can do this by using the `Env::get()` method:
```php
use Framework\Core\Env;

// Get the value of the DB_HOST environment variable
$dbHost = Env::get('DB_HOST');
```

You can also define in the config if you also want to put the env variables in getenv() and $_ENV in the config file by setting the extended_compat option to true of the env config:

```json
"env": {
        "extended_compat": true
    },
```