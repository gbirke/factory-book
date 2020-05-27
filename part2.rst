****************************************
Part II - Dependency Injection Container
****************************************

A good DIC library is a code generator for factories (because factory
does not need all the config and DSL parsing of the DIC).

TODO: Peek at the dumped container in Symfony, see
https://symfony.com/doc/current/components/dependency_injection/compilation.html#dumping-the-configuration-for-performance

TODO: Slowly migrate to Symfony, integrating/dropping the factories bit by
bit. Layer factories replaced by included service configurations.

* Front Controller is gone, routing is handled by Symfony. 
* We could keep Router, but have a new Symfony-specific implementation. 
* Show Symfony controller and presenter implementation: presenter still gets called the same way in
  the use cases, but where previously it sent HTTP headers or output HTML,
  is now used only as storage for Twig context. Thanks to the clean
  architecture, the changed presenter implementation is fully transparent
  for the use case. The same use case could be used in a Symfony Command
  class with a ConsoleRenderer

TODO: Show autowiring


Benefits of a Dependency Injection Container
============================================

-  More concise language
-  Autowiring for services that don't implement an interface - less code
-  Many Frameworks already use a DIC, because they want to define a
   standardized way to extend the base framework structure -> you don't
   go "against the grain" of the framework
-  Extension/injection points for configuration, plugins, etc. (See
   symfony DIC features: `decoration`_, `compiler passes`_ and
   decoupling with `tagged services`_). Naming those concepts in the DIC
   configuration instead of just "doing" them in the PHP code makes
   those patterns more explicit.
-  Easier to set up test environment with DI config for tests and/or
   testcase implementations that have the DIC integrated
-  PSR-11 - shared standard interface - not really a benefit because
   PSR-11 is so generic, it's useless (no definition of actual
   interoperability).
-  “Inheritance” - configurations for different environments override
   defaults, but leave base dependency graph intact. –> Research
   mergeability of Symfony container and other solutions.
-  `Visualization of dependency graph`_
-  Fewer
-  TODO Check how tests can switch individual services.

Drawbacks of a Dependency Injection Container
=============================================

- new language syntax to learn 
- no type safety when using the ``get`` method of the container, but can be checked with tools 
- Caching/compilation step needed, slower development environment which is
  rebuilt on every request (TODO is that true?)
- Too much “magic” 
- IDE plugins needed for refactoring

TODO: Links to other PHP DIC libraries

Read
https://medium.com/easy-pieces-for-programmers/how-to-trick-oo-programmers-into-loving-functional-programming-7019e1bf9bba
for more info about the benefits of type safety and redundancy of
factories, as opposed to “magic”, key-based injectors.

.. _decoration: https://symfony.com/doc/current/service_container/service_decoration.html
.. _compiler passes: https://symfony.com/doc/current/service_container/compiler_passes.html
.. _tagged services: https://symfony.com/doc/current/service_container/tags.html
.. _Visualization of dependency graph: https://www.orbitale.io/2018/12/04/the-symfony-container-graph.html

