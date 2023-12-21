# A modern JSON Schema evaluator for PHP

This library is a PHP based implementation for evaluating and validating [JSON Schemas](https://json-schema.org/).
The library can be easily extended with your own keywords and drafts.

## Requirements
* PHP >= 8.1
* ext-bcmath
* ext-mbstring
* ext-fileinfo

## Table of contents
* [Installation](#installation)
* [Supported drafts](#supported-drafts)
* [Basic examples](#basic-examples)
  * [Basic usage](#basic-usage)
  * [Read individual error results](#read-individual-error-results)
  * [Formatting results](#formatting-results)
* [Mutations](#mutations)
  * [Default values](#default-values)
  * [Content decoding](#content-decoding)
* [Advanced examples](#advanced-examples)
  * [Assert content media type](#assert-content-media-type)
  * [Assert format](#assert-format)
  * [Short-circuiting](#short-circuiting)
  * [Big numbers](#big-numbers-interpret-numeric-strings-as-numbers)
  * [Custom keywords](#custom-keywords)

## Installation
The library can be installed from a command line interface by using [composer](https://getcomposer.org/).

## Supported drafts

### Draft 2020-12 ([Core](https://json-schema.org/draft/2020-12/json-schema-core.html) and [Validation](https://json-schema.org/draft/2020-12/json-schema-validation.html))
Passes all tests of [official JSON schema test suite](https://github.com/json-schema-org/JSON-Schema-Test-Suite) except the following optional tests:
* [optional/refOfUnknownKeyword.json](https://github.com/json-schema-org/JSON-Schema-Test-Suite/blob/master/tests/draft2020-12/optional/refOfUnknownKeyword.json):
  This means that you cannot use the <b>$ref</b> keyword to reference schemas that are located inside unknown keywords.
* [optional/ecmascript-regex.json](https://github.com/json-schema-org/JSON-Schema-Test-Suite/blob/master/tests/draft2020-12/optional/ecmascript-regex.json):
  This means that the specifics of Ecmascript regular expressions are not respected. Instead, regular expressions are evaluated as PERL regular expressions.

```
composer require ropi/json-schema-evaluator
```
## Basic examples
### Basic usage
```php
$schema = json_decode('{
    "type": "string",
    "maxLength": 5
}');

$evaluator = new \Ropi\JsonSchemaEvaluator\JsonSchemaEvaluator();

// Each JSON Schema must be statically analyzed once.
$staticEvaluationContext = $evaluator->evaluateStatic($schema, new \Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig(
    defaultDraft: new \Ropi\JsonSchemaEvaluator\Draft\Draft202012()
));

$instance1 = "hello";
$evaluator->evaluate($instance1, $staticEvaluationContext); // Returns true

$instance2 = "helloworld";
$evaluator->evaluate($instance2, $staticEvaluationContext); // Returns false
```

### Read individual error results
```php
$valid = $evaluator->evaluate(
    instance: $instance2,
    staticEvaluationContext: $staticEvaluationContext,
    results: $results
);

foreach ($results as $result) {
    /** @var $result \Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult */
    if ($result->error) {
        echo "Error keyword location: '{$result->keywordLocation}'\n";
        echo "Error instance location: '{$result->instanceLocation}'\n";
        echo "Error message: {$result->error}\n";
    }
}
```
Output of above example:
```
Error keyword location: '/maxLength'
Error instance location: ''
Error message: At most 5 characters are allowed, but there are 10.
```
### Formatting results
In the following example, the results are formatted as [Basic Output Structure](https://json-schema.org/draft/2020-12/json-schema-core#name-basic).
```php
$formattedResults = (new \Ropi\JsonSchemaEvaluator\Output\BasicOutput($valid, $results))->format();
echo json_encode($formattedResults, JSON_PRETTY_PRINT);
```
Output of above example: 
```json 
{
    "valid": false,
    "errors": [
        {
            "valid": false,
            "keywordLocation": "\/maxLength",
            "instanceLocation": "",
            "keywordName": "maxLength",
            "error": "At most 5 characters are allowed, but there are 10",
            "errorMeta": null
        }
    ]
}
```
## Mutations
### Default values
If a default value is defined with the [default keyword](https://json-schema.org/draft/2020-12/json-schema-validation#name-default), it can be automatically applied during evaluation.
```php
$schema = json_decode('{
    "type": "object",
    "required": ["lastname"],
    "properties": {
        "firstname": {
            "default": "n/a"
        }
    }
}');

$evaluator = new \Ropi\JsonSchemaEvaluator\JsonSchemaEvaluator();

$staticEvaluationContext = $evaluator->evaluateStatic($schema, new \Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig(
    defaultDraft: new \Ropi\JsonSchemaEvaluator\Draft\Draft202012(
        evaluateMutations: true
    )
));

$instance = (object) [
    'lastname' => 'Gauss'
];

$evaluator->evaluate($instance, $staticEvaluationContext);

echo $instance->firstname; // Prints "n/a"
```

### Content decoding
If encoded content is defined with the [contentEncoding keyword](https://json-schema.org/draft/2020-12/json-schema-validation#name-contentencoding), it can be automatically decoded during evaluation.
```php
$schema = json_decode('{
    "contentMediaType": "application/json",
    "contentEncoding": "base64"
}');

$evaluator = new \Ropi\JsonSchemaEvaluator\JsonSchemaEvaluator();

$staticEvaluationContext = $evaluator->evaluateStatic($schema, new \Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig(
    defaultDraft: new \Ropi\JsonSchemaEvaluator\Draft\Draft202012(
        evaluateMutations: true
    )
));

$instance = 'eyJmb28iOiAiYmFyIn0K'; // Base64 encoded JSON '{"foo": "bar"}'

$evaluator->evaluate($instance, $staticEvaluationContext); // Returns true

echo $instance; // Prints '{"foo": "bar"}'
```
## Advanced examples
### Assert content media type
If content media type is defined with the [contentMediaType keyword](https://json-schema.org/draft/2020-12/json-schema-validation#name-contentmediatype), it can be respected during evaluation.
```php
$schema = json_decode('{
    "contentMediaType": "application/json"
}');

$evaluator = new \Ropi\JsonSchemaEvaluator\JsonSchemaEvaluator();

$staticEvaluationContext = $evaluator->evaluateStatic($schema, new \Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig(
    defaultDraft: new \Ropi\JsonSchemaEvaluator\Draft\Draft202012(
        assertContentMediaTypeEncoding: true
    )
));

$instance = '{"foo": "bar"}';
$evaluator->evaluate($instance, $staticEvaluationContext); // Returns true

$instance2 = 'invalidJSON';
$evaluator->evaluate($instance2, $staticEvaluationContext); // Returns false
```

### Assert format
If format is defined with the [format keyword](https://json-schema.org/draft/2020-12/json-schema-validation#name-format-annotation-vocabular), it can be respected during evaluation. 
```php
$schema = json_decode('{
    "format": "email"
}');

$evaluator = new \Ropi\JsonSchemaEvaluator\JsonSchemaEvaluator();

$staticEvaluationContext = $evaluator->evaluateStatic($schema, new \Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig(
    defaultDraft: new \Ropi\JsonSchemaEvaluator\Draft\Draft202012(
        assertFormat: true
    )
));

$instance = 'test@example.com';
$evaluator->evaluate($instance, $staticEvaluationContext, $runtimeEvaluationConfig); // Returns true

$instance2 = 'invalidEmail';
$evaluator->evaluate($instance2, $staticEvaluationContext, $runtimeEvaluationConfig); // Returns false
```

### Short-circuiting
By default, all keywords are evaluated, even if the first keyword validation fails.
If short circuiting is activated, the evaluation stops at the first negative validation result.
```php
$config = new \Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig(
    defaultDraft: new \Ropi\JsonSchemaEvaluator\Draft\Draft202012(
        shortCircuit: true
    )
);
```

### Big numbers (interpret numeric strings as numbers)
```php
$schema = json_decode('{
    "type": "integer"
}');

$evaluator = new \Ropi\JsonSchemaEvaluator\JsonSchemaEvaluator();

$staticEvaluationContext = $evaluator->evaluateStatic($schema, new \Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig(
    defaultDraft: new \Ropi\JsonSchemaEvaluator\Draft\Draft202012(
        acceptNumericStrings: true
    )
));

$instance = json_decode('6565650699413464649797946464646464649797979', false, 512, JSON_BIGINT_AS_STRING);
$evaluator->evaluate($instance, $staticEvaluationContext); // Returns true
```

### Custom keywords
```php
$schema = json_decode('{
    "md5Hash": "098f6bcd4621d373cade4e832627b4f6"
}');

class Md5HashKeyword extends \Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword implements \Ropi\JsonSchemaEvaluator\Keyword\RuntimeKeywordInterface
{
    public function getName() : string
    {
        return "md5Hash";
    }

    public function evaluate(mixed $keywordValue, \Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext $context): ?\Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult
    {
        $instance = $context->getCurrentInstance();
        if (!is_string($instance)) {
            // Ignore keyword, because instance is not a string
            return null;
        }

        $result = $context->createResultForKeyword($this);

        if (md5($instance) !== $keywordValue) {
            $result->invalidate('MD5 hash of "' . $instance . '" does not match ' . $keywordValue);
        }

        return $result;
    }
}

$draft = new \Ropi\JsonSchemaEvaluator\Draft\Draft202012();
$draft->registerKeyword(new Md5HashKeyword(), 'https://example.tld/draft/2022-03/vocab/md5'); // Register keyword with custom vocabulary

$evaluator = new \Ropi\JsonSchemaEvaluator\JsonSchemaEvaluator();

$staticEvaluationContext = $evaluator->evaluateStatic($schema, new \Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig(
    defaultDraft: $draft
));

$instance = 'test';
$evaluator->evaluate($instance, $staticEvaluationContext); // Returns true, because md5 hash matches

$instance = 'hello';
$evaluator->evaluate($instance, $staticEvaluationContext); // Returns false, because md5 hash does not match
```
