## SleepingOwl BladeExtended

SleepingOwl BladeExtended is a simple library, adding `bd-foreach`, `bd-inner-foreach`, `bd-if` and `bd-class` attribute directives support to your blade templates.

##### Create multiple `li` elements, but ignore item with name "_dev"

```html
 <ul>
 	<li bd-foreach="$items as $item" bd-if="$item->name !== '_dev'">
 		<a href="#">{{{ $item->name }}}</a>
 	</li>
 </ul>
```

##### Using bd-inner-foreach you can create multiple element for each array item

```html
 <ul bd-inner-foreach="$items as $i => $item">
 	<li>{{{ $i }}}</li>
 	<li>{{{ $item }}}</li>
 </ul>
```

##### Add class to element by condition

*Note: Conditional classes will be added to existing ones or create class attribute if it doesnt exist.*

```html
 <div class="my-class" bd-class="$condition ? 'class-to-add', $condition2 ? 'second-class-to-add'">
 	…
 </div>
```

## Installation

 1. Require this package in your composer.json and run composer update (or run `composer require sleeping-owl/blade-extended:1.x` directly):

		"sleeping-owl/blade-extended": "1.*"

 2. After composer update, add service providers to the `app/config/app.php`

	    'SleepingOwl\BladeExtended\BladeExtendedServiceProvider',

 3. All done! Now you can use new directives in your blade templates.
 
## Documentation

Documentation can be found at [blade-extended documentation](http://sleeping-owl-blade-extended.gopagoda.com).

## Copyright and License

Admin was written by Sleeping Owl for the Laravel framework and is released under the MIT License. See the LICENSE file for details.
