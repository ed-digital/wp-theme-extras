# **ED. Wordpress Plugin**

## **API**

### **addTemplate**

```php
ED()->addTemplate(
  $name: string,
  $labelOrConfig: string | [
    "label" => string,
    "supports" => string[],
    "gutenberg" => boolean
    "part" => Part()->Template->Test
  ]
);
```

#### **Examples**

Adds a template "my-template" that uses `Part()->Templates->MyTemplate`

```php
ED()->addTemplate("my-template", "My Template");
```

You can configure what the template can support

```php
ED()->addTemplate("my-template", [
  "label" => "My Template",
  "gutenberg" => false,
  "supports" => ["title"]
])
```

You can also change what part the template will run

```php
ED()->addTemplate("my-template", [
  "label" => "My amazing template",
  "part" => Part()->Templates->Test
]);
```

---

### **addFunctionRoute**

```php

```

---

### **addBlock**

Add acf gutenberg blocks

```php
ED()->addTemplate(
  $name: string,
  $labelOrConfig: string | [
    "label" => string,
    "supports" => string[],
    "gutenberg" => boolean
    "part" => Part()->Template->Test
  ]
);
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
