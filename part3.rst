********************************************************************
Part III - Integrating Factories and dependency injection containers
********************************************************************

TODO Show examples of the following options, discuss drawbacks and
benefits

TODO Have a look at https://github.com/gbirke/php-dic-factory, maybe
incorporate it

- Inject only the factory (bad, service locator pattern)
- Inject the public factory services,
- Add method to make factory implement `PSR-11`_ via reflection. Useful
  for reusing already existing factories. TODO research how to integrate
  PSR-11 containers with Symfony DI. See https://stackoverflow.com/q/61687732/130121


.. _PSR-11: https://www.php-fig.org/psr/psr-11/
