.. _important-1788479881:

====================================================================
Important: Category ViewHelpers tolerate a missing rendering context
====================================================================

Description
===========

`FGTCLB\\CategoryTypes\\ViewHelpers\\Be\\CategoryViewHelper::render()` and
`FGTCLB\\CategoryTypes\\ViewHelpers\\Form\\AbstractSelectViewHelper::render()`
dereferenced `$this->renderingContext` unconditionally, although Fluid types
the property `RenderingContextInterface|null` on
`TYPO3Fluid\\Fluid\\Core\\ViewHelper\\AbstractViewHelper`. Either view helper
called from outside a regular render cycle - a unit test instantiating it
directly, or any other caller that skips `ViewHelperInvoker` - raised a fatal
error on the first `$this->renderingContext->getVariableProvider()` or
`getViewHelperVariableContainer()` call.

Both `render()` methods now guard with
`!($this->renderingContext instanceof RenderingContextInterface)` and return
an empty string when the rendering context is absent, the same outcome an
empty tag content already produces. `AbstractSelectViewHelper::render()` also
stops caching `getViewHelperVariableContainer()` in a local variable and asks
the rendering context for it at each of its four call sites instead, so the
guard covers every use rather than only the first.

Impact
======

`CategoryViewHelper` and `AbstractSelectViewHelper` (and its subclasses, for
example the select and filter select view helpers of this extension) return
`''` instead of raising a fatal error when rendered without a rendering
context.

Affected Installations
======================

None. No project template renders these view helpers outside Fluid's normal
compile/render cycle, where the rendering context is always present.

Migration
=========

No configuration change is required.

.. index:: Fluid, PHP-API, NotScanned
