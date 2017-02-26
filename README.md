Laravel Photoable package
=========================

## Installation

Add next code to your composer.json file and write composer update.

```shell
    "require": {
        "hscomp/photoable": "dev-master"
    },
```

To use this package, add its service provider to your `config/app.php` providers array.
```php
    'providers' => [
        // ... 
        Hscomp\Photoable\PhotoableServiceProvider::class,
    ],
```

## Usage
The ServiceProvider will add a new command `routes:javascript` to your `artisan` commands.
By default, this command will generate a `routes.js` file on your `resources/assets/js` folder. This contains all **named** routes in your app.
That's it! You're all set to go. 

```shell
$ php artisan routes:javascript
```

> **Lazy Tip** If you use elixir (or any js task manager), set up a watcher that runs this command whenever your php routes change.

## Arguments

| Name     | Default     | Description     |
| -------- |:-----------:| --------------- |
| **name** | *routes.js* | Output filename |

## Options

| Name           | Default               | Description     |
| -------------- |:---------------------:| --------------- |
| **path**       | *resources/assets/js* | Where to save the generated filename, relative to the base path. (ie. "public/assets" folder if you don't plan to mix it.) |
| **middleware** | *null*                | If you want only some routes to be available on JS, you can use a middleware (like js-routable) to select only those |
| **object**     | *Router*              | If you want to choose your own global JS object (to avoid collision) |
| **prefix**     | *null*                | If you want to a path to prefix to all your routes |

## Javascript usage

By default, the command will generate a `routes.js` file on your `resources/assets/js` folder, so you can use elixir:

```js
elixir(function(mix){
    mix.scripts([
        'routes.js',
        'app.js'
    ]);
});
```

You may generate the routes file in your public folder instead...

```shell
php artisan routes:javascript --path public/js
```
...then include it in your views:

```html
<script src="/js/routes.js" type="text/javascript">
```

In any case, you'll have a `Routes` object on your global scope.

Examples:

```javascript
// Usage: Router.route(route_name, params)
Router.route('users.show', {id: 1}) // returns http://dommain.tld/users/1

// If you assign parameters that are not present on the URI, they will get appended as a query string:
Router.route('users.show', {id: 1, name: 'John', order: 'asc'}) // returns http://dommain.tld/users/1?name=John&order=asc
```

## Contributing

This project uses `phpunit` for php testing and `jasmine` for JS testing.
Check available grunt tasks in the `Gruntfile.js` file.

## Found a bug?
Please, let us know! Send a pull request (better) or create an issue. 
Questions? Ask! We will respond to all issues.

## Inspiration
Although no code was copied, this package is greatly inspired by [FOSJsRoutingBundle](https://github.com/FriendsOfSymfony/FOSJsRoutingBundle) for Symfony.

## License
This package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
