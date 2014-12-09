nanotools
=========

A very small PHP tool set. Handles simple routing, templating, classloading, and IoC. Meant as an absolute barebone library, when everything else would be overkill. 

todo
----
- Enhance Container to automatically instantiate and inject dependencies to initializer methods:
    * e.g. Container::register('renderer', function($styles) {...code...}) will get $styles injected upon instantiation
- Refactor routes to also work non-statically. Keep the static helpers.
- More Docs
- Better Tests
- Demo Project (separate from the lib itself)
- Make a composer package (separate demo from actual lib)

