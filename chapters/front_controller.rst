Version 0.4 - Integrate a front controller with the factory
===========================================================

Let's get rid of the different files and put the decision logic - which
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
new route - a violation of `SOLID`_\ s `Open-closed-principle`_. We will
improve that in the next section.

If we want to use ``index.php`` with the front controller as the sole
entry point of our application, then we need a different implementation
of the ``Router`` class, that no longer compares file names, but uses
URL parameters instead. Thanks to the clean architecture, this change is
totally transparent to the rest of the code, the only place where we
need to change code is the ``getRouter`` method in the
``WebUseCaseFactory`` and ``index.php``:

.. code:: php

   // index.php
   (new FrontController( new WebUseCaseFactory() ) )->run( $_SERVER['REQUEST_URI'] );

Have a look at the example repository if you want to go into more
detail.

.. _Front Controller: https://en.wikipedia.org/wiki/Front_controller
.. _SOLID: https://en.wikipedia.org/wiki/SOLID
.. _Open-closed-principle: https://en.wikipedia.org/wiki/Open–closed_principle
