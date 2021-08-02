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

$instance = "yolo";

$oTime = microtime(true);

$validator = new \Opis\JsonSchema\Validator();

for ($i = 0; $i < 100000; $i++) {
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

for ($i = 0; $i < 100000; $i++) {
    $evaluator->evaluate($instance, $staticContext);
}

$rTime = microtime(true) - $rTime;

echo 'ROPI (total time): ';
echo $rTime + $sTime;
echo ' (' . round(100 - (100 / $oTime * ($rTime + $sTime)), 2) . '%)';
echo PHP_EOL;
echo 'ROPI (runtime)   : ';
echo $rTime;
echo ' (' . round(100 - (100 / $oTime * $rTime), 2) . '%)';

