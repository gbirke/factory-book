Version 0.2, adding a router
============================

After the refactoring, we still doesn't have a central Factory or Dependency Injection Container, but
where the code follows SOLID principles.

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

Let's look at the entry point of the application, ``index.php``, which
shows the TODO items:

.. code:: php

   // index.php
   require_once 'autoload.php';
   $usecase = new ShowTodoItems(
     new JsonTodoRepository( new SimpleFileReader( 'todos.json' ) ),
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

Let's look at the PHP files for the other routes:

.. code:: php

   // add.php
   require_once 'autoload.php';
   $usecase = new AddTodoItem(
     new JsonTodoRepository( new SimpleFileReader( 'todos.json' ) ),
     new RedirectPresenter( 'index', new MultipleFileRouter( [ 'index' => 'index.php' ] ) )
   );
   $usecase->addTodo( (string) filter_input( INPUT_POST, 'new_todo' ) );

.. code:: php

   // toggle.php
   require_once 'autoload.php';
   $usecase = new ToggleTodoItem(
     new JsonTodoRepository( new SimpleFileReader( 'todos.json' ) ),
     new RedirectPresenter( 'index', new MultipleFileRouter( [ 'index' => 'index.php' ] ) )
   );
   $usecase->toggleTodo( (int) filter_input(
       INPUT_POST,
       'id',
       FILTER_VALIDATE_INT,
       [ 'options' => [ 'default' => -1 ] ]
   ) );

You can now see the drawbacks of this application structure:

-  We have to repeat the setup of :ref:`autoloading<autoload>` and the use cases dependencies,
   leading to duplicated code, violating `SOLID`_\ s `Don't repeat
   yourself (DRY) principle <DRY>`_. 
-  When the setup changes, e.g. changing the file name of the storage,
   you need to touch all the files.
-  We create different instances of ``Router``, with different
   parameters. While that's memory-efficient in the short term, in the
   long run we don't have a central point where developers can look up
   all the ID => URL mappings in one place.
-  You could say that the files violate `SOLID`_\ s `Single
   Responsibility Principle`_ because each file has two reasons to
   change - for the setup of the use cases and the call of the use cases
   action.

.. _SOLID: https://en.wikipedia.org/wiki/SOLID
.. _Single Responsibility Principle: https://en.wikipedia.org/wiki/Single_responsibility_principle
.. _DRY: https://en.wikipedia.org/wiki/Don%27t_repeat_yourself
