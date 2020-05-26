Version 0.1, Refactoring my first PHP application
=================================================

TODO start with spaghetti code version as 0.1. Mention that it's easy to
write, but may not be easy to read. Mention origins of PHP, this is how
an application was meant to be written in 1997.

.. literalinclude:: ../examples/01_my_first_todo_app/index.php

There is not much error checking on the storage side (what happens if the
disk is full, the data file has the wrong permissions) and
error handling in the logic (e.g. preventing adding duplicate TODOs). We
might add some of that over the course of this book, but the focus of
this book is how to create well-structured code that's easy to change, not
secure or fault-tolerant code.

The web application structure is "old school" - the code for
each "page" of the application is one file. To get from one
functionality to the other, we use the different file names in links
(``href`` attributes), as the ``action`` attribute of forms and in HTTP
redirect headers. This mapping of files ("pages" or "actions") to URLs is
intuitive for beginners, for example for people who are accustomed to writing HTML files.


TODO introduce `SOLID`_ code (esp. "single
responsibility", "DRY" and "dependency inversion" principles)

TODO point out increased number of classes, predict that they will
increase even more. Some people consider `the fear of adding
classes <https://sahandsaba.com/nine-anti-patterns-every-programmer-should-be-aware-of-with-examples.html#fear-of-adding-classes>`_ an anti-pattern.

    TODO code examples of presenter and persistence here

TODO Explain why repository and ``FileReader`` are separate: repository is for
structure of data and access, ``FileReader`` is for filesystem access.
Separation of concerns, ease of testability, etc.

.. _autoload:

.. index:: autoloading

TODO mention autoloading, but don't go too deep, just explain that we can
use classes and their code will automatically be loaded.

You’ll “compose”
those classes, injecting low-level services into high-level business
logic code. “Single responsibility” also means that classes that depend
on services won't instantiate those services with a call to ``new``.
Instead, there will be a central point in your application where you
instantiate all the classes and their dependencies. This book is an 
in-depth look at two different implementations of such a
central point - a `Factory`_ and a Dependency Injection Container.

TODO Introduce `clean architecture`_.  that the storage and presentation
layer are abstract interfaces that have different concrete
implementations. Benefit: Software stays easy to change. We'll see those
benefits in the following chapters.  Another Benefit: The software is
easier to reason about, because you have clear areas of responsibility:
Want two switch out the storage? View, controllers and business logic (use
cases) won't be affected. Want to use command line interface instead of
rendered HTML? Wrap the use cases in a command line script and add a
console presenter.  Want to add a new feature to your domain? start with
the business logic, add the data to the storage layer, display the feature
in the view layer.

.. _SOLID: https://en.wikipedia.org/wiki/SOLID
.. _Factory: https://en.wikipedia.org/wiki/Factory_%28object-oriented_programming%29
.. _clean architecture: https://8thlight.com/blog/uncle-bob/2012/08/13/the-clean-architecture.html

