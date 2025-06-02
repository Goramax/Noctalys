# Connecting a Noctalys app to a Database

Noctalys provides a simple way to connect to a database using the `Db` class. This class allows you to perform basic CRUD operations and manage your database connections easily and securely.

## Configuration

To connect to a database, you need to configure the database connection settings in the `.env` file. The following settings are required:

```js
DB_HOST="localhost"
DB_USER="root"
DB_PASSWORD="secret"
DB_NAME="my_database"
DB_PORT=3306
DB_DRIVER="mysql"
```
You can also set the `DB_DRIVER` to `mysql`, `pgsql`, `sqlsrv` or `sqlite` depending on the type of database you are using. The default is `mysql`.

## Sending Queries

To send queries to the database, you can use the `Db` class. The `Db` class provides a simple interface for executing SQL queries and retrieving results.

### Example

```php 
use Goramax\NoctalysFramework\Db;
// Gets the post where id = 1
$post = Db::sql('SELECT * FROM posts WHERE id = :id', ['id' => 1]);
```
The `sql()` method takes two parameters: the SQL query and an optional array of parameters to bind to the query. The method returns the result of the query as an associative array.
Be sure to use the `:param` syntax for binding parameters in your SQL queries. This helps prevent SQL injection attacks and ensures that your queries are safe and secure.