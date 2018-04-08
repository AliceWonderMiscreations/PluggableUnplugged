PluggableUnplugged
==================

A replacement for the WordPress pluggable.php file.

I do not like the fact that any function in the WordPress `pluggable.php` file
can be replaced by any plugin. I see that as a recipe for disaster.

The purpose of this class is to provide stable methods that plugins can use
instead of the functions in pluggable.php so that they always know what the
code is that they are calling.

Some methods will also be defined that are not in the `pluggable.php` class
that I believe are useful to WordPress plugin developers.

Basically this is a collection of functions.

Two class will exist. One for static methods and one for methods that require
the class be instantiated.
