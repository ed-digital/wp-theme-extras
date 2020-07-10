### **addBlock**

Add acf gutenberg blocks. When used inside ACF blocks the `prop` getter retrieves fields from the current block.

> ie. if there was an `Title` acf block then writing `$title = get_field('title');` inside it would be the same as writing `$title = prop('title');`

In the above case it is preferable to use the `prop` getter over `get_field` because then you can still use it as a normal block in other areas.

> ie. `Part()->Title([ 'title' => "My Page Title" ]);`

```php
ED()->addBlock([
  /*
  Path formatted part reference
  eg Part()->Blocks->Test() === "blocks/test"
  */
  'part' => string,
  /* block id */
  'name' => string,
  /* UI title */
  'title' => string,
  /* UI description */
  'description' => string,
  /* UI category */
  'category' => string,
  /* UI icon */
  'icon' => string,
  /* UI search keywords */
  'keywords' => string[],
]);
```

#### **Examples**

```php
ED()->addBlock([
  'part' => 'blocks/image-content', // Renders Part()->Blocks->ImageContent()
  'name' => "image",
  'title' => "My Image",
  'description' => "My image block",
  'category' => "commmon",
  'icon' => 'dashicon',
  'keywords' => ['testimonial', 'quote'],
])
```
