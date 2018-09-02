# ReactPHP Symfony Server

This library wraps a ReactPHP server over a Symfony project installation.

## Installation (using composer)

```bash
composer require jdomenechb/reactphp-symfony-server
```

After running this command, be sure to define an `APP_PORT` environment variable prior to running the server. If the Symfony project uses DotEnv in an specific environment, it will take the value from the `.env` file.

## Usage

From the root of your project, run:

```bash
vendor/php/reactphp-server
```

The server will inform of the port it is running.

## FAQ

**Q. How does it work?**

The library takes the `public/index.php` file from the project root, and reads it until the point the Symfony kernel is created. Then, from this point, it takes control in order to provide the ReactPHP server.

Once executed, requests are made directly to the server and translated to Symfony requests, which are dealt then by the Symfony Kernel. The Kernel provides afterwards a response, which is translated back to a response the ReactPHP server can understand.

**Q. Does it serve static assets too?**

Yes, the library has basic support for serving static assets, like images, CSS ans JS under the `public` folder of your project.

However, you might consider using another web server (like Nginx or Apache) to serve them, as they are more dedicated to this matter. This library focus mainly on serving PHP requests.

**Q. Can I use another web server after I install this library?**

Yes, the library is prepared to not modify at all your project, so you can run another server if needed in top of your code.

**Q. Will it work with all kind of Symfony installations?**

Unfortunately, there is no way to assure that. The library has been tested with a `public/index.php` file from a Symfony 4 skeleton, and the more a `public/index.php` has been modified, the most likely is the library to fail.

For this reason, I strongly encourage you to contribute to the project in whatever mean you can.

**Q. Can I develop or modify code while using the server?**

If you do so, you might encounter that your changes are not being displayed after refreshing the page. This is due to the fact that, once a class has been loaded in memory during server execution, it will remain unchanged in the app memory.

In order to se your changes, you should restart the server.


## Contributing

Feel free to contribute to the project by submitting pull requests, or by opening issues you might find. I am eager to receive your feedback and improve the quality and features of the library.