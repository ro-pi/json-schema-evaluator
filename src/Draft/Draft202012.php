<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Draft;

use Ropi\JsonSchemaEvaluator\Keyword\Applicator\AdditionalPropertiesKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\AllOfKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\AnyOfKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\ContainsKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\DynamicRefKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\ElseKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\IfKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\ItemsKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\NotKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\OneOfKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\PatternPropertiesKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\PrefixItemsKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\PropertiesKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\PropertyNamesKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\ThenKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\UnevaluatedItemsKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\UnevaluatedPropertiesKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Identifier\AnchorKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Identifier\DynamicAnchorKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\ReservedLocation\CommentKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\ReservedLocation\VocabularyKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\ReservedLocation\DefsKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\DependentSchemasKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Identifier\IdKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Applicator\RefKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Identifier\SchemaKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\ConstKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\ContentEncodingKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\ContentMediaTypeKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\ContentSchemaKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\Meta\DefaultKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\DependentRequiredKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\EnumKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\ExclusiveMaximumKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\ExclusiveMinimumKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\FormatKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\MaxContainsKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\MaximumKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\MaxItemsKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\MaxLengthKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\MaxPropertiesKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\Meta\DeprecatedKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\Meta\DescriptionKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\Meta\ExamplesKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\Meta\ReadOnlyKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\Meta\TitleKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\Meta\WriteOnlyKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\MinContainsKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\MinimumKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\MinItemsKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\MinLengthKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\MinPropertiesKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\MultipleOfKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\PatternKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\RequiredKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\TypeKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Validation\UniqueItemsKeyword;

class Draft202012 extends AbstractDraft
{
    protected const URI = 'https://json-schema.org/draft/2020-12/schema';

    protected const VOCABULARIES = [
        'https://json-schema.org/draft/2020-12/vocab/core' => true,
        'https://json-schema.org/draft/2020-12/vocab/applicator' => true,
        'https://json-schema.org/draft/2020-12/vocab/unevaluated' => true,
        'https://json-schema.org/draft/2020-12/vocab/validation' => true,
        'https://json-schema.org/draft/2020-12/vocab/meta-data' => true,
        'https://json-schema.org/draft/2020-12/vocab/format-annotation' => true,
        'https://json-schema.org/draft/2020-12/vocab/format-assertion' => true,
        'https://json-schema.org/draft/2020-12/vocab/content' => true,
    ];

    public function getUri(): string
    {
        return static::URI;
    }

    public function __construct()
    {
        // Core
        $this->registerKeyword(new SchemaKeyword());
        $this->registerKeyword(new VocabularyKeyword());
        $this->registerKeyword(new IdKeyword());
        $this->registerKeyword(new AnchorKeyword());
        $this->registerKeyword(new DynamicAnchorKeyword());
        $this->registerKeyword(new DefsKeyword());
        $this->registerKeyword(new RefKeyword());
        $this->registerKeyword(new DynamicRefKeyword());
        $this->registerKeyword(new CommentKeyword());

        // Content
        $this->registerKeyword(new ContentEncodingKeyword());
        $this->registerKeyword(new ContentMediaTypeKeyword());
        $this->registerKeyword(new ContentSchemaKeyword());

        // Applicator
        // Keywords for applying sub-schemas to arrays
        $this->registerKeyword(new PrefixItemsKeyword());
        $this->registerKeyword(new ItemsKeyword());
        $this->registerKeyword(new ContainsKeyword());
        // Keywords for applying sub-schemas to objects
        $this->registerKeyword(new PropertiesKeyword());
        $this->registerKeyword(new PatternPropertiesKeyword());
        $this->registerKeyword(new AdditionalPropertiesKeyword());
        $this->registerKeyword(new PropertyNamesKeyword());
        //  Keywords for applying sub-schemas in place
        $this->registerKeyword(new AllOfKeyword());
        $this->registerKeyword(new AnyOfKeyword());
        $this->registerKeyword(new OneOfKeyword());
        $this->registerKeyword(new NotKeyword());
        $this->registerKeyword(new IfKeyword());
        $this->registerKeyword(new ThenKeyword());
        $this->registerKeyword(new ElseKeyword());
        $this->registerKeyword(new DependentSchemasKeyword());

        // Unevaluated (depends on in-place applicators)
        $this->registerKeyword(new UnevaluatedItemsKeyword());
        $this->registerKeyword(new UnevaluatedPropertiesKeyword());

        // Format annotation and/or format assertion
        $this->registerKeyword(new FormatKeyword());

        // Validation
        $this->registerKeyword(new TypeKeyword());
        $this->registerKeyword(new ConstKeyword());
        $this->registerKeyword(new EnumKeyword());
        $this->registerKeyword(new MultipleOfKeyword());
        $this->registerKeyword(new MaximumKeyword());
        $this->registerKeyword(new ExclusiveMaximumKeyword());
        $this->registerKeyword(new MinimumKeyword());
        $this->registerKeyword(new ExclusiveMinimumKeyword());
        $this->registerKeyword(new MaxLengthKeyword());
        $this->registerKeyword(new MinLengthKeyword());
        $this->registerKeyword(new PatternKeyword());
        $this->registerKeyword(new MaxItemsKeyword());
        $this->registerKeyword(new MinItemsKeyword());
        $this->registerKeyword(new UniqueItemsKeyword());
        $this->registerKeyword(new MaxContainsKeyword());
        $this->registerKeyword(new MinContainsKeyword());
        $this->registerKeyword(new MaxPropertiesKeyword());
        $this->registerKeyword(new MinPropertiesKeyword());
        $this->registerKeyword(new RequiredKeyword());
        $this->registerKeyword(new DependentRequiredKeyword());

        // Meta data
        $this->registerKeyword(new TitleKeyword());
        $this->registerKeyword(new DescriptionKeyword());
        $this->registerKeyword(new DefaultKeyword());
        $this->registerKeyword(new DeprecatedKeyword());
        $this->registerKeyword(new ReadOnlyKeyword());
        $this->registerKeyword(new WriteOnlyKeyword());
        $this->registerKeyword(new ExamplesKeyword());
    }
}