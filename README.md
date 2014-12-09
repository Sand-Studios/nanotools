nanotools
=========

A very small PHP tool set. Handles simple routing, templating, classloading, and IoC. Meant as an absolute barebone library, when everything else would be overkill. 

todo
----
- Remove Database -> There are plenty other modules to use
- Remove Session -> No added value
- Refactoring and renaming in layout.
  - Template::render() -> Template::renderWithLayout()
  - Template::renderPartial() -> Template::render()
- Enhancing Container to automatically instantiate and inject dependencies to initializer lambdas:
  - e.g. Container::register('renderer', function($styles) {...code...}) will get $styles injected upon instantiation

- More Docs
- Better Tests
- Demo Project (separate from the lib itself)
- Make a composer package (separate demo from actual lib)

