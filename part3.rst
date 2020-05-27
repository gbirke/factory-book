********************************************************************
Part III - Integrating Factories and dependency injection containers
********************************************************************

TODO Show examples of the following options, discuss drawbacks and
benefits

TODO Have a look at https://github.com/gbirke/php-dic-factory, maybe
incorporate it

- Inject only the factory (bad, service locator pattern)
- Inject the public factory services via manual configuration of the
  factory in the service definitions
- Autogenerate service definitions from factory via reflection
- Add method to make factory implement `PSR-11`_ via reflection. Useful
  for reusing already existing factories. TODO research how to integrate
  PSR-11 containers with Symfony DI. See https://stackoverflow.com/q/61687732/130121 
  e.g. with a compiler pass for Symfony, see
  https://symfonycasts.com/screencast/symfony-bundle/tags-compiler-pass
  and https://symfony.com/doc/current/service_container/compiler_passes.html) or 
  an extension for Nette DI, see https://doc.nette.org/en/3.0/di-extensions 



.. _PSR-11: https://www.php-fig.org/psr/psr-11/
