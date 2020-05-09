Version 0.1, Refactoring my first PHP application
=================================================

TODO start with spaghetti code version as 0.1. Mention that it's easy to
write, but may not be easy to read. Mention origins of PHP, this is how
an application was meant to be written in 1997.

    TODO code example of spaghetti code here

TODO introduce `SOLID`_ code (esp. “single
responsibility” and “dependency inversion” principles)
TODO point out increased number of classes, predict that they will
increase even more., 

    TODO code examples of presenter and persistence here

You’ll “compose”
those classes, injecting low-level services into high-level business
logic code. “Single responsibility” also means that classes that depend
on services won't instantiate those services with a call to ``new``.
Instead, there will be a central point in your application where you
instantiate all the classes and their dependencies. This book is an 
in-depth look at two different implementations of such a
central point - a `Factory`_ and a Dependency Injection Container.

TODO Introduce `clean architecture`_. 
that the storage and presentation layer are abstract interfaces that
have different concrete implementations. Benefit: Software stays easy to
change. We'll see those benefits in the following chapters

.. _SOLID: https://en.wikipedia.org/wiki/SOLID
.. _Factory: https://en.wikipedia.org/wiki/Factory_%28object-oriented_programming%29
.. _clean architecture: https://8thlight.com/blog/uncle-bob/2012/08/13/the-clean-architecture.html

