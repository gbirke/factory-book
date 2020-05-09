Version 0.3 - Using a factory
=============================

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
that rule, you won't need to write a unit test for the factory. Your
integration tests and acceptance tests will check if the factory returns
the right implementations of the interfaces. If your class assembly is
more complex or involves conditionals, you would use an instance of a
separate `Builder`_ class in the factory, that you can test separately.

Let's have a look how the individual files look now:

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

Factories are declarative code
------------------------------

TODO explain how the code becomes declarative instead of imperative: You
don't prescribe "first build this, then build this, finally build this"
but let the call order happen in the order it's necessary.

TODO Explain that Circular dependencies will lead to infinite function call loops.

.. _SOLID: https://en.wikipedia.org/wiki/SOLID
.. _Factory: https://en.wikipedia.org/wiki/Factory_%28object-oriented_programming%29
.. _Liskovs Substition Principle: https://en.wikipedia.org/wiki/Liskov_substitution_principle
.. _Single Responsibility Principle: https://en.wikipedia.org/wiki/Single_responsibility_principle
.. _design pattern: https://en.wikipedia.org/wiki/Software_design_pattern
.. _cyclomatic complexity: https://en.wikipedia.org/wiki/Cyclomatic_complexity
.. _Builder: https://en.wikipedia.org/wiki/Builder_pattern
.. _Front Controller: https://en.wikipedia.org/wiki/Front_controller

