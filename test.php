<?php

require_once __DIR__ . '/vendor/autoload.php';

$schema = json_decode('{
    "type": "object",
    "properties": {
        "firstname": {
            "type": "string"
        },
        "lastname": {
            "type": "string"
        },
        "addresses": {
            "type": "array",
            "items": {
                "type": "object",
                "properties": {
                    "street": {
                        "type": "string"
                    },
                    "zip": {
                        "type": "string"
                    },
                    "city": {
                        "type": "string"
                    }
                }
            }
        }
    }
}');

$schema = json_decode('{
    "minLength": 3
}');

$instance = json_decode('{
    "firstname": "Robert",
    "lastname": "Piplica",
    "addresses": [
        {
            "street": "Münchener Str. 29a",
            "zip": "82131",
            "city": "Gauting"
        },
        {
            "street": "Julius-Haerlin-Str. 7",
            "zip": "82131",
            "city": "Gauting"
        }
    ]
}');

$evaluator = new \Ropi\JsonSchemaEvaluator\JsonSchemaEvaluator();

$staticContext = $evaluator->evaluateStatic($schema, new \Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig(
    new \Ropi\JsonSchemaEvaluator\Draft\Draft202012()
));

$time = microtime(true);

for ($i = 0; $i < 30000; $i++) {
    $evaluator->evaluate($instance, $staticContext);
}

echo microtime(true) - $time;
echo PHP_EOL;



$time = microtime(true);

$validator = new \Opis\JsonSchema\Validator();

for ($i = 0; $i < 30000; $i++) {
    $validator->validate($instance, $schema);
}

echo microtime(true) - $time;

