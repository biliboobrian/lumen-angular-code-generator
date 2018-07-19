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
### Scaffolding from DB

You need a database connection setup in your project.

Build CRUD models and controllers with various commands (List of available commands will be updated with development progress):

```shell
  bilibo:lumen:ctrl      Generate CRUD controller for a table name.
  bilibo:lumen:ctrls     Generate CRUD controllers for all tables.
  bilibo:lumen:model     Generate Eloquent model according to table passed in argument.
  bilibo:lumen:models    Generate Eloquent models for all tables.
```

Default configuration is the following ( you can override them with -c option in command line to provide another config.php file):

```php
    'lumen_model_namespace'       	=> 'App\Models',
    'lumen_ctrl_namespace'       	=> 'App\Http\Controllers',
    'base_class_lumen_model_name' 	=> \biliboobrian\lumenAngularCodeGenerator\Model\MicroServiceExtendModel::class,
    'base_class_lumen_ctrl_name' 	=> \biliboobrian\lumenAngularCodeGenerator\Controller\CrudExtendController::class,
    'lumen_model_output_path'     	=> app_path() . '/Models',
    'lumen_ctrl_output_path'      	=> app_path() . '/Http/Controllers',
    'no_timestamps'   				=> null,
    'date_format'     				=> null,
	'connection'      				=> null,
	'add_route'      				=> null,
	'add_cache'      				=> null,
```

Use command help for more infos

```shell
$ php artisan bilibo:lumen:ctrl -h
```
Generation of controllers and models extend lushdigital/microservice-crud.

### Usage of Generated Controllers and models

By default all controllers provide a set of applicable routes what you can add to your routes/web.php:



```php
//for full tables retrieve
$router->get(   '/model',                 'AlternativeController@index');

//for paginated tables retrieve
$router->get(   '/model',                 'AlternativeController@get');


$router->get(   '/model/{id}/{relation}', 'AlternativeController@getRelationList');
$router->get(   '/model/{id}',            'AlternativeController@show');
$router->post(  '/model',                 'AlternativeController@store');
$router->put(   '/model/{id}',            'AlternativeController@update');
$router->delete('/model/{id}',            'AlternativeController@destroy');
```



## License
[MIT](https://choosealicense.com/licenses/mit/)
