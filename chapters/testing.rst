Usage of the factory in tests
=============================

When and how to use the factory in tests? It depends.

.. index:: Unit tests

In **unit tests**, you shouldn't use the factory at all, since those
tests are about the behavior of single *units* (e.g. classes) of your code
and if those units interact with other parts of your program, you use `test doubles`_ to
isolate the `system under test`_ from the rest of the system.

    TODO: Reference unit test example in example code base

Even **integration tests** don't need to use the factory to instantiate
the whole object tree. Instead, you can instantiate the systems under
test that need to interact with each other, but satisfy their other
dependencies with test doubles.

.. index:: Acceptance tests
.. _acceptance_tests:

Your **acceptance tests** test application, using the whole
object tree. You should use the factory for constructing the object tree.
However, you’ll run into problems. Let's have a look at an intentionally
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

Problem 1: The acceptance test is not **isolated**: The test environment
file names are exactly the same in the production and the test system. If
someone accidentally or maliciously runs the tests on the live system, the
production data becomes riddled with test data. We will look at a solution
for this in the chapter :ref:`factory_configuration_and_environments`.

Problem 2: The acceptance test is **brittle**: It needs a storage file to
exist, it has knowledge about low-level data encoding and structure, it
does not reset repository to a known state. The acceptance test knows too
much about the implementation details: It knows that the use case uses the
``JsonTodoRepository`` and knows how ``JsonTodoRepository`` stores its
data. I think it's a good idea to implicitly test which implementation of
the ``TodoRepository`` the factory returns when instantiating the use
case, because the factory itself has no unit tests. But for accessing the
resulting state, the stored data, we should be using the repository, to
abstract the low-level details in the test. 

In our factory, the ``getTodoRepository`` method is intentionally private.
Instead of making it public for the sake of testing, we should split it
instead, into ``UseCaseFactory`` and ``ServiceFactory``.

    TODO show rewritten test example using ServiceFactory and storage
    service.

To keep our separations of concerns small, classes outside of the ``test``
namespace must never use ``ServiceFactory`` methods, only test code uses
the ``ServiceFactory`` to use high-level service interfaces instead of
low-level checks. We will explore this separation of concerns in-depth in
the next chapter, :ref:`factory_and_architecture`.

.. _test doubles: https://www.entropywins.wtf/blog/2016/05/13/5-ways-to-write-better-mocks/
.. _system under test: https://en.wikipedia.org/wiki/System_under_test
