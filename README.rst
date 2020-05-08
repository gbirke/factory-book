The PHP Factory Book
====================

A collection of texts about writing SOLID code and using factories and
dependency injection in PHP.

This is a work in progress …

Building with Docker
--------------------

The following command will build the book as HTML::

    docker run -it --rm -v $(pwd):/docs sphinxdoc/sphinx make html

If you want to run ``:make html`` inside of Vim, set the ``makeprg`` like
this::

    :set makeprg=docker\ run\ --rm\ -v\ (pwd):/docs\ sphinxdoc/sphinx\ make
