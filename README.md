# lumen-angular-code-generator

!! WORK IN PROGRESS FOR FIRST RELEASE!!

Model, controller provider and service generator for lumen 5.6 and angular 6 from DB schema.

## Installation

Until the first stable release comes out, please use the prefer-source composer flag.

`composer install biliboobrian/lumen-angular-code-generator --prefer-source`

Modify your bootstrap/app.php providers to add generators to Artisan.


```php
$app->register(biliboobrian\lumenAngularCodeGenerator\Provider\GeneratorServiceProvider::class);
```
## Usage

You need a database connection setup in your project.

Build CRUD models and controllers with various commands (List of available commands will be updated with development progress):

```shell
  bilibo:lumen:ctrl      Generate CRUD controller for a table name.
  bilibo:lumen:ctrls     Generate CRUD controllers for all tables.
  bilibo:lumen:model     Generate Eloquent model according to table passed in argument.
  bilibo:lumen:models    Generate Eloquent models for all tables.
```

Use command help for more infos

```shell
$ php artisan bilibo:lumen:ctrl -h
```
Generation of controllers and models extend lushdigital/microservice-crud.


## License
[MIT](https://choosealicense.com/licenses/mit/)
