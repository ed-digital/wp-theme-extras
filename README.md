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

```phpo

```
