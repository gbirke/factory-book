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



Running the PHP examples with Docker
------------------------------------

All the examples should look and behave identical, this is mostly for
user testing during development and for showing the reader how different
code can produce the same result::

    docker run -it --rm -v $(pwd)/examples:/app -w /app -p 8000:8000 php:7.4-alpine php -S 0.0.0.0:8000 -t .

Will run the example overview on http://localhost:8000/


