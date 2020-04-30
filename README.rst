The PHP Factory Book
====================

A collection of texts about writing SOLID code and using factories and
dependency injection in PHP.

This is a work in progress …

Building with Docker
--------------------

The following command will build the book as HTML::

    docker -it --rm -v $(pwd):/docs sphinxdoc/sphinx make html
