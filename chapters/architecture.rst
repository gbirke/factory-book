.. _factory_and_architecture:

Factories and architectural concerns
====================================

.. index:: Domain Driven Design

TODO Explain DDD bounded contexts, propose splitting factory vertically
(bounded contexts) and horizontally (view layer, persistence layer,
logging services, HTTP/web layer, etc).

But how to unify them again? Do we need a ``FactoryFactory``, inching our
code closer and closer to becoming `EnterpriseFizzBuzz`_ or
`SimplePHPEasyPlus`_ ? The answer to this question is in the next chapter,
:ref:`factory_configuration_and_environments`, when we talk about
*environments*.

.. _EnterpriseFizzBuzz: https://github.com/EnterpriseQualityCoding/FizzBuzzEnterpriseEdition
.. _SimplePHPEasyPlus: https://github.com/Herzult/SimplePHPEasyPlus
