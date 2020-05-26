Version 0.5 - Improving the front controller
============================================

.. index:: callable

A different implementation of the front controller shows how to follow
the `open-closed-principle`_ by passing in a map between route IDs and
`callables`_. As we’ll see later, this implementation has some flaws, so
it's called ``NaiveMappingFrontController``

.. code:: php

   class NaiveMappingFrontController {
     private $usecaseMappings;
     private $router;

     public function __construct( array $usecaseMappings, Router $router )
     {
       $this->usecaseMappings = $usecaseMappings;
       $this->router = $router;
     }

     public function run( string $url ): void {
       $routeId = $this->router->getRouteFromUrl( $url );
       $route = $this->usecaseMappings[$routeId] ?? $this->usecaseMappings['index'];
       call_user_func( $route );
     }
   }

We have removed the branching logic (``if`` or ``case`` statements) for
individual routes. The remaining "branch" in the front controller is for
determining the default route in case the URL does not exist in our
mapping. Let's see how to set up the mapping in ``index.php``. It has
three flaws, can you find them?

.. code:: php

   // index.php
   $factory = new WebUseCaseFactory();
   // A map of string => callable (object instance and method name)
   $mappings = [
     'index' => [ $factory->newShowTodoUsecase(), 'showTodos' ],
     'add_todo' => [ $factory->newAddTodoUsecase(), 'addTodo' ],
     'toggle_todo' => [ $factory->newToggleTodoUsecase(), 'toggleTodo' ],
   ];
   (new NaiveMappingFrontController( $mappings, $factory->getRouter() ) )
     ->run($_SERVER['REQUEST_URI'])


.. index:: delayed instantiation

The first flaw is that the mapping instantiates all use cases and their
dependencies for every request. That wastes memory and processing time,
because each use case class gets a new instance of its dependencies. Let
this example be a warning to you - be aware of this anti-pattern and
when using a factory, look out if you’re instantiating classes you don't
need. One advantage of using factories is **delayed instantiation**,
creating instances only when needed. 

.. index:: callable

The second flaw is inherent in `PHP object callables`_: you have to
specify the method names as strings, which will break your code when you
do automated refactoring in the IDE.

The third flaw breaks the functionality of the code: We forgot to pass
the input parameters to the use case actions!

Version 0.5.1 - Fixed and Improved Front Controller
===================================================

A better way to set up the mapping is using anonymous functions as
callables:

.. code:: php

   // index.php
   $mappings = [
     'index' => function( WebUseCaseFactory $factory ) {
         $factory->newShowTodoUsecase()->showTodos();
     },
     'add_todo' => function( WebUseCaseFactory $factory ) {
       $factory->newAddTodoUsecase()
           ->addTodo( (string) filter_input( INPUT_POST, 'new_todo' ) );
     },
     'toggle_todo' => function( WebUseCaseFactory $factory ) {
       $factory->newToggleTodoUsecase()->toggleTodo(
         (int) filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT, [ 'options' => [ 'default' => -1 ] ] )
       );
     },
   ];

Wrapping the factory method calls in anonymous functions defers the call
to the use case factory method, until the point where the front
controller calls the anonymous function. We also got rid of the global
``$factory`` variable.

For the new mapping to work, the ``MappingFrontController`` gets the
factory as a dependency and passes it as a parameter when calling
``call_user_func``.

.. code:: php

   class MappingFrontController {
     private $factory;
     private $usecaseMappings;

     public function __construct( array $usecaseMappings, WebUseCaseFactory $factory ) {
         $this->usecaseMappings = $usecaseMappings
         $this->factory = $factory;
     }

     public function run( string $url ): void {
       $routeId = $this->factory->getRouter()->getRouteFromUrl( $url );
       $route = $this->usecaseMappings[$routeId] ?? $this->usecaseMappings['index'];
       call_user_func( $route, $this->factory );
     }
   }

The next refinement of the routing architecture would be to write small
classes with a common interface instead of writing anonymous functions.
You can then write unit tests for those classes and find better ways to
inject the HTTP environment into them. By then, you would have written
your own framework and your own implementation of a *controller* in the
`Model-View-Controller`_ architectural pattern. But all those
refinements would give us no new insights into dependency injection, so
we stop here with refining our web stack.

TODO Side Note: Factories as a better implementation of the singleton pattern
-----------------------------------------------------------------------------


class singletons are bad for testability and violation of SRP (obejct
creation vs methods)

Singleton behavior itself is not a bad thing, it helps to save memory
and make sure that when different services depend on the same interface,
all state changes of one service instantly propagate to other services.

``Code Example: Show PHP class implementation for storage class``

factory can do singleton behavior (also called “shared objects” in
Symfony)

``code example: factory method with static variable``.


.. _SOLIDs: https://en.wikipedia.org/wiki/SOLID
.. _open-closed-principle: https://en.wikipedia.org/wiki/Open–closed_principle
.. _callables: https://php.net/manual/en/language.types.callable.php
.. _PHP object callables: https://php.net/manual/en/language.types.callable.php
.. _Model-View-Controller: https://en.wikipedia.org/wiki/Model–view–controller

