nanotools
=========

A very small PHP tool set. Handles simple routing, templating, classloading, and IoC. Meant as an absolute barebone library, when everything else would be overkill. 

todo
----
- Refactor Routes to work non-statically
    * Register actions once, fire as many times as needed (with REQUEST_DATA parameter)
    * RREQUEST_DATA is constructed separately (may actually do some lazy loading)
    * Keep static helpers where it makes sense
- Docs & Examples
- Tests
- Demo Project (separate from the lib itself)
- Make a composer package (separate demo from actual lib)

