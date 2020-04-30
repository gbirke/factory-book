— title: Factories and Dependency Injection Containers in PHP subtitle:
Building classes from the ground up tags: - PHP Design Patterns OOP
architecture clean architecture factory dependency injection date:
‘2018-06-14’ categories: - wikimedia todo: - Move this to a separate
repo (using https://github.com/newren/git-filter-repo/ to remove
everything but this file), convert md to rst, set up sphinx. Publish as
github-pages. The resulting site will become a book with several
chapters: First, a “Tour” from a legacy web app to Symfony (With a
docker container to run it). Then other, testing and module-related
topics, e.g. “modularizing factories (while still keeping them
standalone)”, see
https://gist.github.com/gbirke/b84c5b1d8ed92b7f77445d53b66adde9 Also
incorporate https://github.com/gbirke/php-dic-factory - Rework the
writing style and make it consistent: - Decide on how to address the
reader - you, we, don’t address at all? - Decide on a style - is this a
tutorial to follow along? Probably not. More like a “see me code, learn
from example”. Explain the style. - Target group: novices (to learn
basics and patterns), advanced users (to leran some architectural basics
and peek behind the curtain of DIC). - Extract example code for all the
iterations into a repository with the different versions. Maybe add more
explanation there. - Move “Factories and testing” to a separate article
to keep the original intent and flow of this already-long article. — If
you’re writing `SOLID`_ code (in particular when adhering to the “single
responsibility” and “dependency inversion” principles), you’ll end up
with lots of classes, where each class does one thing. You’ll “compose”
those classes, injecting low-level services into high-level business
logic code. “Single responsibility” also means that classes that depend
on services won’t instantiate those services with a call to ``new``.
Instead, there will be a central point in your application where you
instantiate all the classes and their dependencies. In this article, I
will have a an in-depth look at two different implementations of such a
central point - a `Factory`_ and a Dependency Injection Container.

As example classes, I will use components of a “TODO List” application
that follows the principles of the `clean architecture`_. That means
that the storage and presentation layer are abstract interfaces that
have different concrete implementations. Over the course of this
article, the structure and architecture of the application will become
more and more refined. In the first part of this text I will use
implementations that don’t rely on any libraries - using HTML with some
PHP sprinkled in for presentation and a JSON file for storing the data.
Basically, a minimal, self-written “framework”, that lacks error
handling but shows the principal architecture of a web application. In
the second part I will transition this architecture to a Symfony
application. The focus of this article is not on the concrete
implementation of the use cases, storage or presentation, the focus is
on how we can wire those building blocks together in better ways.

Part I - Factories
------------------

Version 0.1, individual files
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

For the first iteration, we’re looking at an existing code base, that
does not have a central Factory or Dependency Injection Container, but
where the code follows SOLID principles.

The web application structure is slightly “old school” - the code for
each “page” of the application is one file. To get from one
functionality to the other, we use the different file names in links
(``href`` attributes), as the ``action`` attribute of forms and in HTTP
redirect headers. In my experience, this mapping of files to URLs is
intuitive for beginners, for example for people who are transitioning
from writing HTML files.

In the knowledge that our URL schema might change later, we encapsulate
the mapping between URLs and functionality in a ``Router`` interface.
Our first implementation maps unique route ids to URL paths:

.. code:: php

   interface Router {
       public function getUrl( string $routeId ): string;
       public function getRouteFromUrl( string $url): string;
   }

   class MultipleFileRouter implements Router {
     private $routeMap;
     public function __construct( array $routeMap ) {
       $this->routeMap = $routeMap;
     }

     public function getUrl( string $routeId ): string {
       return $this->routeMap[$routeId];
     }

     public function getRouteFromUrl( string $url): string {
       return array_flip( $this->routeMap )[$url];
     }
   }

Let’s look at the entry point of the application, ``index.php``, which
shows the TODO items:

.. code:: php

   // index.php
   $usecase = new ShowTodoItems(
     new JsonTodoRepository( new SimpleFileFetcher(), 'todos.json' ),
     new WebTemplatePresenter(
       new PhpTemplate( 'todos' ),
       [
         'router' => new MultipleFileRouter( [
           'add_todo' => 'add.php',
           'toggle_todo' => 'toggle.php',
           ] )
       ]
     )
   );
   $usecase->showTodos();

As you can see, the biggest part of the file is the initialization of
the use case with its storage component, ``JsonTodoRepository``, and its
output component ``WebTemplatePresenter``. ``JsonTodoRepository`` needs
to know where to find its JSON file and depends on ``FileFetcher`` to
read the file. ``HtmlTemplatePresenter`` gets a template class and the
Router class, so templates can generate URLs.

Let’s look at the PHP files for the other routes:

.. code:: php

   // add.php
   $usecase = new AddTodoItem(
     new JsonTodoRepository( new SimpleFileFetcher(), 'todos.json' ),
     new RedirectPresenter( 'index', new MultipleFileRouter( [ 'index' => 'index.php' ] ) )
   );
   $usecase->addTodo( (string) filter_input( INPUT_POST, 'new_todo' ) );

.. code:: php

   // toggle.php
   $usecase = new ToggleTodoItem(
     new JsonTodoRepository( new SimpleFileFetcher(), 'todos.json' ),
     new RedirectPresenter( 'index', new MultipleFileRouter( [ 'index' => 'index.php' ] ) )
   );
   $usecase->toggleTodo( (int) filter_input(
       INPUT_POST,
       'id',
       FILTER_VALIDATE_INT,
       [ 'options' => [ 'default' => -1 ] ]
   ) );

You can now see the drawbacks of this application structure:

-  We have to repeat the setup the of the use cases dependencies,
   leading to duplicated code.
-  When the setup changes, e.g. changing the file name of the storage,
   you need to touch all the files.
-  We create different instances of ``Router``, with different
   parameters. While that’s memory-efficient in the short term, in the
   long run we don’t have a central point where developers can look up
   all the ID => URL mappings in one place.
-  You could say that the files violate `SOLID`_\ s `Single
   Responsibility Principle`_ because each file has two reasons to
   change - for the setup of the use cases and the call of the use cases
   action.

Version 0.2 - Using a factory
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

We now have refactored the code and put all the class creation logic
into one factory class:

.. code:: php

   // WebUseCaseFactory.php
   class WebUseCaseFactory {

     public function newShowTodoUsecase(): ShowTodoItems {
       return new ShowTodoItems(
         $this->newTodoRepository(),
         $this->newWebPresenter( 'todos' )
       );
     }

     public function newAddTodoUsecase(): ShowTodoItems {
       return new AddTodoItem(
         $this->newTodoRepository(),
         $this->newRedirectPresenter( 'index' )
       );
     }

     public function newToggleTodoUsecase(): ShowTodoItems {
       return new ToggleTodoItem(
         $this->newTodoRepository(),
         $this->newRedirectPresenter( 'index' )
       );
     }

     private function newTodoRepository(): TodoRepository {
       return new JsonTodoRepository( $this->newFileFetcher(), 'todos.json' );
     }

     private function newFileFetcher(): FileFetcher {
       return new SimpleFileFetcher();
     }

     private function newWebPresenter( string $templateName ): Presenter {
       return new WebTemplatePresenter(
         new PhpTemplate( $templateName ),
         $this->getRoutes()
       );
     }

     private function newRedirectPresenter( string $targetRoute ): Presenter {
       return new RedirectPresenter( $targetRoute, $this->getRouter() );
     }

     private function getRouter(): Router {
       return new MultipleFileRouter( [
           'index' => 'index.php',
           'add_todo' => 'add.php',
           'toggle_todo' => 'toggle.php',
       ] );
     }

   }

You can see that we encapsulated all initialization in methods. We have
made all factory methods except for the ones for use cases private,
forcing the outer code to call the use case methods and not instances of
their services. The factory methods have an interface return type, not a
concrete implementation. If at a later point we want to switch out the
storage method or the templating system, we only need to change one
place in the code. Using the interface instead of the concrete
implementation as a return type ensures we’re adhering to `SOLID`_\ s
`Liskovs Substition Principle`_.

The name of the class, ``WebUseCaseFactory``, is a hint at the `design
pattern`_ the class implements - a `Factory`_. Factory classes consist
of methods that return new object instances. When thinking of the
`Single Responsibility Principle`_, its responsibility is instantiation.

Ideally, the factory has a `cyclomatic complexity`_ of 1, which means
that there are no branching conditions or loops in it. If you adhere to
that rule, you won’t need to write a unit test for the factory. Your
integration tests and acceptance tests will check if the factory returns
the right implementations of the interfaces. If your class assembly is
more complex or involves conditionals, you would use an instance of a
separate `Builder`_ class in the factory, that you can test separately.

Let’s have a look how the individual files look now:

.. code:: php

   // index.php
   ( new WebUseCaseFactory() )
     ->newShowTodoUsecase()
     ->showTodos();

.. code:: php

   // add.php
   ( new WebUseCaseFactory() )
     ->newAddTodoUsecase()
     ->addTodo( (string) filter_input( INPUT_POST, 'new_todo' ) );

.. code:: php

   // toggle.php
   ( new WebUseCaseFactory() )
   ->newToggleTodoUsecase()
   ->toggleTodo( (int) filter_input(
       INPUT_POST,
       'id',
       FILTER_VALIDATE_INT,
       [ 'options' => [ 'default' => -1 ] ]
   ) );

They are much shorter now and don’t need local variables any more.

Version 0.3 - Integrate a front controller with the factory
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Let’s get rid of the different files and put the decision logic - which
use case action to call - into a class, the `Front Controller`_.

.. code:: php

   class FrontController {
     private $usecaseFactory;

     public function __construct( WebUseCaseFactory $factory )
     {
       $this->useCaseFactory = $factory;
     }

     public function run( string $url ): void {
       switch( $this->useCaseFactory->getRouter()->getRouteFromUrl( $url ) ) {
         case 'add_todo':
           $this->useCaseFactory
             ->newAddTodoUsecase()
             ->addTodo( (string) filter_input( INPUT_POST, 'new_todo' ) );
           return;
         case 'toggle_todo':
           $this->useCaseFactory
             ->newToogleTodoUsecase()
             ->toggleTodo( (int) filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT, [ 'options' => [ 'default' => -1 ] ] ) );
           return;
         default:
           $this->useCaseFactory
             ->newShowTodoUsecase()
             ->showTodos();
       }
     }
   }

Using ``case`` statements makes every case explicit and readable, but it
also means we have to add code to the class whenever we want to handle a
new route - a violation of `SOLIDs`_ `Open-closed-principle`_. We will
improve that in the next section.

If we want to use ``index.php`` with the front controller as the sole
entry point of our application, then we need a different implementation
of the ``Router`` class, that no longer compares file names, but uses
URL parameters instead. Thanks to the clean architeture, this change is
totally transparent to the rest of the code, the only place where we
need to change code is the ``getRouter`` method in the
``WebUseCaseFactory`` and ``index.php``:

.. code:: php

   // index.php
   (new FrontController( new WebUseCaseFactory() ) )->run( $_SERVER['REQUEST_URI'] );

Have a look at the example repository if you want to go into more
detail.

Version 0.4 - Improving the front controller
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A different implementation of the front controller shows how to follow
the `open-closed-principle`_ by passing in a map between route IDs and
`callables`_. As we’ll see later, this implementation has some flaws, so
it’s called ``NaiveMappingFrontController``

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
individual routes. The remaining “branch” in the front controller is for
determining the default route in case the URL does not exist in our
mapping. Let’s see how to set up the mapping in ``index.php``. It has
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

The first flaw is that the mapping instantiates all use cases and their
dependencies for every request! That wastes memory and processing time,
because each use case class gets a new instance of its dependencies. Let
this example be a warning to you - be aware of this anti-pattern and
when using a factory, look out if you’re instantiating classes you don’t
need. One advantage of using factories is **delayed instantiation**,
creating instances only when needed.

The second flaw is inherent in `PHP object callables`_: you have to
specify the method names as strings, which will break your code when you
do automated refactoring in the IDE.

The third flaw breaks the functionality of the code: We forgot to pass
the input parameters to the use case actions!

Version 0.4.1 - Fixed and Improved Front Controller
---------------------------------------------------

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
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

class singletons are bad for testability and violation of SRP (obejct
creation vs methods)

Singleton behavior itself is not a bad thing, it helps to save memory
and make sure that when different services depend on the same interface,
all state changes of one service instantly propagate to other services.

``Code Example: Show PHP class implementation for storage class``

factory can do singleton behavior (also called “shared objects” in
Symfony)

``code example: factory method with static variable``.

Usage of the factory in tests
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

So, how to use the factory in tests? It depends.

In **unit tests**, you shouldn’t use the factory at all, since those
tests are about the behavior of single *units* (classes) of your code
and if those units interact with other parts, you use `test doubles`_ to
isolate the `system under test`_ from the rest of the system.

TODO: Reference unit test example in example code base

Even **integration tests** don’t need to use the factory to instantiate
the whole object tree. Instead, you can instantiate the systems under
test that need to interact with each other, but satisfy their other
dependencies with test doubles.

Your **acceptance tests** need to test application using the whole
object tree. You can use the factory for constructing the object tree.
However, you’ll run into problems. Let’s have a look at an intentionally
bad acceptance test that simulates a user adding a new to-to item by
going to a URL. There are at least two problems in the test. Can you
spot them?

.. code:: php

   public function testRouteStoresNewTodo()
   {
       $_POST['add_todo'] = 'test item';
       (new MappingFrontController( $this->loadMappings(), new WebUseCaseFactory() ) )
       ->run( 'http://example.com?action=add');

       $storage = json_decode( file_get_contents( 'todos.json' ), true );

       $this->assertContains( [ 'name' => 'test item', 'done' => false ], $storage );
   }

   private function loadMappings() {
     static $mappings;
     if ( !$mappings ) {
       include __DIR__ . '/../mappings.php';
     }
     return $mappings;
   }

Problem 1: The test environmment file names are exactly the same in the
production and the test system. If someone accidentally or maliciously
runs the tests on the live system, the production data becomes riddled
with test data. The acceptance test is not **isolated**

Problem 2: The acceptance test is **brittle**: It needs storage file to
exist, it has knowledge about low-level data encoding and structure, it
does not reset repository to a known state. The acceptance test knows
too much about the implementation details (both of the repository
implementation and what implementation is used). might be a good thing
to indirectly test if the factory instantiates the right
implementations, but that’s a small consolation. We should be using the
repository, but that’s not accessible at the moment.

Solution: Split Factory into UseCaseFactory and ServiceFactory.
Production code must never use serviceFactory directly (for better
encapsulation), only test code uses the ServiceFactory to use high-level
service interfaces instead of low-level checks.

TODO show rewritten test example using ServiceFactory and storage
service.

Injecting services and configuration into the factory
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In acceptance tests, you need to isolate some of the external resources
(files, database, networked services) if you don’t want to damage the
data of your production environment. At the moment, the factory is
opaque and deterministic, we can’t decide what implementations it
creates. How to change that?

Solution 1: Initialize factory with configuration. Easy for simple
resources like stream urls.
``TODO Example with FileReader and vfsStream.``

Remember to use a builder or separate, tested factory to keep the main
factory logic-free (cyclomatic complexity of 1). Drawback: Additional
logic that has to be tested.

Solution 2: Add setters to the factory so that tests can swap
implementations. There must be a default, otherwise environments need to
initialize, which should be a job of the factory (SRP). Question: What
should be the default?

code example with internal state (services stored in private variables
of factory, initialized in factory function *not* in constructor).

Call order now becomes important - the services have to be injected
before they are first requested Usage: Different environments
(testing/prod, web/console). Use sparingly, as it lengthens the entry
point code and is counter to the purpose of the factory. Try to
initialize the more common case (i.e. non-testing). Benefit: Public
interface & encapsulation make clear which services can be switched out.
Alternative: Environment-specific factories? FactoryFactory?

Questionable Pattern/Antipattern: Optional services + LogicException

Benefits of a factory
~~~~~~~~~~~~~~~~~~~~~

-  Excplicit (at the cost of being lengthy, might violate some class
   size rules)
-  Type-Safe
-  Minimal public interface
-  No “polluting” the global name space with local variables that are
   used for clarity or or building things
-  Injecting/replacing services for Edge-To-Edge testing
-  Code as configuration - if you can read PHP, you can understand
   what’s going on.
-  Deferred initialization

Part II Dependency Injection Container
--------------------------------------

A good DIC library is a code generator for factories (because factory
does not need all the config and DSL parsing of the DIC).

TODO: Peek at the dumped container in Symfony, see
https://symfony.com/doc/current/components/dependency_injection/compilation.html#dumping-the-configuration-for-performance

TODO: What happened to our code? \* Front Controller is gone, routing is
handled by Symfony. \* We could keep Router, but have a new
Symfony-specific implementation. \* Show Symfony controller and
presenter implementation: presenter still gets called the same way in
the use cases, but where previously it sent HTTP headers or output HTML,
is now used only as storage for Twig context. Thanks to the clean
architecture, the changed presenter implementation is fully transparent
for the use case. The same use case could be used in a Symfony Command
class with a ConsoleRenderer

TODO: Show autowiring

Benefits of a Dependency Injection Container
--------------------------------------------

-  More concise language
-  Autowiring for services that don’t implement an interface - less code
-  Many Frameworks already use a DIC, because they want to define a
   standardized way to extend the base framework structure -> you don’t
   go “against the grain” of the framework
-  Extension/injection points for configuration, plugins, etc. (See
   symfony DIC features: `decoration`_, `compiler passes`_ and
   decoupling with `tagged services`_). Naming those concepts in the DIC
   configuration instead of jsut “doing” them in the PHP code makes
   those patterns more explicit.
-  Easier to set up test environment with DI config for tests and/or
   testcase implementations that have the DIC integrated
-  PSR-11 - shared standard interface
-  “Inheritance” - configurations for different environments override
   defaults, but leave base dependency graph intact. –> Research
   mergeability of Symfony container and other solutions.
-  `Visualization of dependency graph`_

Drawbacks of a Dependency Injection Container
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

new language syntax to learn no type safety when using the container
directly, needs to be checked with tools Caching/compilation needed
injection vulnerabilities if services are public Too much “magic” IDE
plugins needed for refactoring

TODO: Links to PHP DIC libraries

Read
https://medium.com/easy-pieces-for-programmers/how-to-trick-oo-programmers-into-loving-functional-programming-7019e1bf9bba
for more info about teh benefits of type safety and redunancy of
factories, as opposed to “magic”, key-based injectors.

Part III - Integrating Factories and dependency injection containers
--------------------------------------------------------------------

Options: - Inject only the factory - Inject the public factory services,
- Encapsulate DIC inside the factory (seems weird, but can be useful for
not binding to DIC implementation (weak argument, why not use PSR-11))

Conclusion
----------

Factories and DIC are not mutually exclusive and can be integrated -
pass the framework DIC to the Factory for your use cases, to instantiate
Adapters implement the use case service interfaces and that are thin
wrappers around framework services. If you have many classes without
interface and parameterization, you can use a DIC with autowiring inside
your factory to keep your factory code shorter, while still presenting a
type-safe minimal API to the outside.

.. _SOLID: https://en.wikipedia.org/wiki/SOLID
.. _Factory: https://en.wikipedia.org/wiki/Factory_%28object-oriented_programming%29
.. _clean architecture: https://8thlight.com/blog/uncle-bob/2012/08/13/the-clean-architecture.html
.. _Single Responsibility Principle: https://en.wikipedia.org/wiki/Single_responsibility_principle
.. _Liskovs Substition Principle: https://en.wikipedia.org/wiki/Liskov_substitution_principle
.. _design pattern: https://en.wikipedia.org/wiki/Software_design_pattern
.. _cyclomatic complexity: https://en.wikipedia.org/wiki/Cyclomatic_complexity
.. _Builder: https://en.wikipedia.org/wiki/Builder_pattern
.. _Front Controller: https://en.wikipedia.org/wiki/Front_controller
.. _SOLIDs: https://en.wikipedia.org/wiki/SOLID
.. _Open-closed-principle: https://en.wikipedia.org/wiki/Open–closed_principle
.. _open-closed-principle: https://en.wikipedia.org/wiki/Open–closed_principle
.. _callables: https://php.net/manual/en/language.types.callable.php
.. _PHP object callables: https://php.net/manual/en/language.types.callable.php
.. _Model-View-Controller: https://en.wikipedia.org/wiki/Model–view–controller
.. _test doubles: https://www.entropywins.wtf/blog/2016/05/13/5-ways-to-write-better-mocks/
.. _system under test: https://en.wikipedia.org/wiki/System_under_test
.. _decoration: https://symfony.com/doc/current/service_container/service_decoration.html
.. _compiler passes: https://symfony.com/doc/current/service_container/compiler_passes.html
.. _tagged services: https://symfony.com/doc/current/service_container/tags.html
.. _Visualization of dependency graph: https://www.orbitale.io/2018/12/04/the-symfony-container-graph.html
