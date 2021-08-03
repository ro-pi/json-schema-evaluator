<?php

require_once __DIR__ . '/vendor/autoload.php';

$schema = json_decode('{
    "type": "object",
    "required": ["firstname", "lastname", "addresses"],
    "properties": {
        "gender": {
            "enum": ["m", "w", "d"]
        },
        "id": {
            "type": "integer",
            "minimum": 1,
            "maximum": 999999
        },
        "firstname": {
            "type": "string",
            "minLength": 2,
            "maxLength": 50
        },
        "lastname": {
            "type": "string",
            "minLength": 2,
            "maxLength": 50
        },
        "addresses": {
            "type": "array",
            "minItems": 1,
            "maxItems": 30,
            "prefixItems": [
                {
                    "$ref": "#/properties/addresses/items"
                }
            ],
            "items": {
                "type": "object",
                "required": ["street", "zip", "city"],
                "properties": {
                    "street": {
                        "type": "string",
                        "minLength": 2,
                        "maxLength": 50
                    },
                    "zip": {
                        "type": "string",
                        "minLength": 2,
                        "maxLength": 50
                    },
                    "city": {
                        "type": "string",
                        "minLength": 2,
                        "maxLength": 50
                    }
                }
            }
        }
    }
}');

$instance = json_decode('{
    "firstname": "Robert",
    "lastname": "Piplica",
    "id": 13,
    "gender": "m",
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

$oTime = microtime(true);

$validator = new \Opis\JsonSchema\Validator();

for ($i = 0; $i < 30000; $i++) {
    $validator->validate($instance, $schema);
}

$oTime = microtime(true) - $oTime;

echo 'OPIS (total time): ';
echo $oTime;
echo PHP_EOL;



$sTime = microtime(true);
$evaluator = new \Ropi\JsonSchemaEvaluator\JsonSchemaEvaluator();

$staticContext = $evaluator->evaluateStatic($schema, new \Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig(
    new \Ropi\JsonSchemaEvaluator\Draft\Draft202012()
));

$sTime = microtime(true) - $sTime;
$rTime = microtime(true);
for ($i = 0; $i < 30000; $i++) {
    $evaluator->evaluate($instance, $staticContext);
}

$rTime = microtime(true) - $rTime;

echo 'ROPI (total time): ';
echo $rTime + $sTime;
echo ' (' . round($oTime / ($rTime + $sTime), 2) . ' times faster)';
echo PHP_EOL;
echo 'ROPI (runtime)   : ';
echo $rTime;
echo ' (' . round($oTime / $rTime, 2) . ' times faster)';

