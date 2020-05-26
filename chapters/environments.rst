.. _factory_configuration_and_environments:

Injecting services and configuration into the factory
=====================================================

.. index:: Environment

Factories are the ideal place to introduce the concept of an
**environment**. Environment means that we have different runtime
characteristics of our application, each one with a name like
"``production``", "``development`` or "``test``". 

For example, in acceptance tests, we want to use different external
resources (files, database, networked services) in the test environment,
to avoid damage to our production environment or because it's more
convenient. At the moment, the factory is opaque and deterministic,
tailored to create classes for the production environment. We can't
decide what implementations of our interface it creates or and can't
parameterize those implementations.

This chapter shows three solutions for making factories more flexible.

Solution 1: Initialize factory with configuration. 
---------------------------------------------------

.. index:: Configuration

This is for cases where you want to parameterize an implementation, for
example 

* configuring a path for caching
* giving a database connection string (`DSN`_) to a repository class
* setting the current locale 
 
The following example shows the modified ``PersistenceFactory`` with a
parameter for the path to the JSON file. In the use case test we use the
`vfsStream`_ library to provide an in-memory location ("virtual file
stream") to the ``SimpleFileFetcher``. Our test is now independent from
the physical file system, but uses all the classes that the production
environment would use - a true acceptance test. The presenter returned
from the ``ViewFactory`` still writes directly to the output with HTML
embedded in PHP, ``printf`` and ``echo``, which is why we have to capture
the output with `output buffering functions
<https://www.php.net/manual/en/book.outcontrol.php>`_

.. literalinclude:: ../examples/configuration/src/Infrastructure/PersistenceFactory.php
    :lines: 6-

.. literalinclude:: ../examples/configuration/test/UseCases/ShowTodosUseCaseTest.php
    :lines: 10-

When starting out the parameterization, you can add the configuration
values as parameters for the factory constructor. This ensures that they
exists and have the correct type. If the number of parameters becomes too
high, put them into a value object. If you don't want to put your
configuration into value objects in PHP files, this is the point where you
would introduce a configuration format and a reader class that validates
the text file and produces the configuration data.

Should you write unit tests for the factory that check if it passes the right
configuration keys to the instance constructors? In my opinion, you
can omit those tests - you test configuration implicitly in your
acceptance tests. If you want to have 100% coverage and your ``@covers``
annotations for all your tests are strict, then you'll have to write
unit tests for factories when you introduce configuration for them.

You might argue that the parameter for the storage file breaks the nice
abstraction the factory provides - what if our persistence implementation
does not need the path or needs database connection parameters instead?
That is indeed unfortunate, but still does not violate the `Single
Responsibility Principle <SRP>`_ or `DRY Principle <DRY>`_ - the purpose of a
factory is to build defaults - if your defaults change, you'll have to
change the factory (and of course your configuration), but nothing else.
At the moment you'd also have to change all the tests, but later sections
in this chapter will show how you can create a test environment factory
shared by all your classes.

The factory should *not* branch based on the configuration values!
Factories should be logic-free and have a cyclomatic complexity of 1.
We'll see how to instantiate different implementations in the next two
sections.

.. _factories_with_setters:

Solution 2: Factory with setters 
---------------------------------

This is for cases where you want to switch out one or more implementations
in acceptance tests that use the factory. For example:

* Use an ``InMemoryCache`` instead of a file or database cache.
* Use a ``NullLogger`` instead of the default logger.
* Use a different, more structured view layer implementation to avoid
  having to parse the DOM output.
* Switch the default repository implementation with a stub when you're
  testing code paths that don't access the repository or expect the
  repository throwing specific exceptions.

We implement this by introducing nullable private instance variables in the factory and
adding setter methods for them. The getter methods check if the instance variable is
``null`` and create an instance if needed. In the production environment, we
will get those instances by default, in the test environment we can switch
out individual instances.

``TODO code example factory.php with internal state (templating and respository service
stored in private variables
of factory, initialized in a createWithDefault function.``

``TODO code example usecasetest.php that uses factory and switches out the
view with a spy implementation using a setter o the factory``

As you can see, the setters show which services we swap out in the test
code. You should only switch out services if you need to avoid a certain
side effect, you should leave all other services in place to make the
acceptance test as "realistic", i.e. close to the production configuration
as possible. The public interface of the factory makes it easy to see
which services the tests could switch out.

.. index:: Developer Experience (DX)

While setters improve the `developer experience`_, they make the code quality worse:

* They introduce mutable state in an otherwise stateless factory.
* Code that has access to the factory, could potentially switch out
  services.  Developers have to have the discipline to avoid using the
  setters outside of tests or use architectural pattern checking tools
  like `deptrac`_ or `dephpend`_ to avoid those calls.
* Some people consider `exposing state for testing bad practice.
  <https://softwareengineering.stackexchange.com/questions/274937/is-it-bad-practice-to-make-methods-public-solely-for-the-sake-of-unit-testing>`_ 
  You can decide for yourself, if that reasoning applies to object creation in a factory. 
* Static analysis tools like `Scrutinizer`_, `Exacat`_, `phpstan`_ or
  `psalm`_, might not recognize the "initialization guarantee" for the
  nullable private instance variables in the getter methods and mark the
  non-nullable return type of the getter methods as an error.

Solution 3: Specialized factories
---------------------------------

Chapter :ref:`factory_and_architecture` already talked about splitting the
big central factory into specialized factories for each layer of the
application. But how do the factories fit together? Let's have a look at
the ``UseCaseFactory`` that now takes the ``PersistenceFactory`` and
``ViewFactory``:

  TODO Code example that shows the 3 factories and how index.php
  initializes them

Our :file:`index.php` has become longer. Also, what happens if we want to
have different environments, e.g. a development environment with
deactivated cache and a different file name for our
``JsonTodoRepository``? For different initializations, we can use an
``EnvironmentFactory`` that initializes different implementations of
``PersistenceFactory``.  

    TODO Code example EnvironmentFactory with big switch statement (or
    chain of responsibility), returning differently configured
    useCaseFactory instances

For our small application, the ``EnvironmentFactory`` is totally
`overengineered<https://en.wikipedia.org/wiki/Overengineering>`. As long
as you don't have different environments in your application, you can
probably skip something like that. 

You might have noticed that ``EnvironmentFactory`` does not have a branch
for the ``"test"`` environment. This is because our tests use a special
``TestEnvironmentFactory``. Its implementation is similar to our
development environment, but we still need to override specific services
in specific test cases. We could achieve those overrides with subclasses
of our factories that initialize different instances of the services. But
this would lead to an explosion of factory classes. A better way to
achieve injectable services would be to add setter methods for services,
with the pattern shown in the previous section, :ref:`factories_with_setters`. 


    TODO code example PersistenceFactory with setter and
    TestEnvironmentFactory with getter for layer factories
    (with comment that those are only exposed in test). Example
    code of a test using TestEnvironmentFactory and setting something in
    the PersistenceFactory

As a final example of what some might call overengineering in the name of
purity, here is the same example, but implemented with traits instead of setters:

    TODO rewritten code example, using traits. See
    https://gist.github.com/gbirke/7aa39ee5b596b702eacdd0772e8e151c as an
    example

Traits use the fact that a method defined in a trait overrides the method
of the same name in its parent (while still being able to call it with
``parent::``, creating a wrapper). Traits avoid the "combinatoric factory
explosion" problem while still use inheritance to avoid the problem of
factories being stateful.

.. note:: When you're using traits, all factory methods you override must
   have the visibility ``protected``, otherwise you can't override them!

Modularizing factories with callables
-------------------------------------

see https://gist.github.com/gbirke/b84c5b1d8ed92b7f77445d53b66adde9 

.. _vfsStream: https://github.com/bovigo/vfsStream
.. _DRY: https://en.wikipedia.org/wiki/Don%27t_repeat_yourself
.. _SRP: https://en.wikipedia.org/wiki/Single_responsibility_principle
.. _DSN: https://www.php.net/manual/en/pdo.construct.php
.. _developer experience: https://medium.com/@albertcavalcante/what-is-dx-developer-experience-401a0e44a9d9
.. _deptrac: https://github.com/sensiolabs-de/deptrac
.. _dephpend: https://github.com/mihaeu/dephpend
.. _Scrutinizer: https://scrutinizer-ci.com
.. _Exacat: https://www.exakat.io
.. _phpstan: https://phpstan.org
.. _psalm: https://psalm.dev

