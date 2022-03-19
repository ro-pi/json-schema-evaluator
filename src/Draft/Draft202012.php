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
    public const VOCABULARY_CORE = 'https://json-schema.org/draft/2020-12/vocab/core';
    public const VOCABULARY_APPLICATOR = 'https://json-schema.org/draft/2020-12/vocab/applicator';
    public const VOCABULARY_UNEVALUATED = 'https://json-schema.org/draft/2020-12/vocab/unevaluated';
    public const VOCABULARY_VALIDATION = 'https://json-schema.org/draft/2020-12/vocab/validation';
    public const VOCABULARY_META_DATA = 'https://json-schema.org/draft/2020-12/vocab/meta-data';
    public const VOCABULARY_FORMAT_ANNOTATION = 'https://json-schema.org/draft/2020-12/vocab/format-annotation';
    public const VOCABULARY_FORMAT_ASSERTION = 'https://json-schema.org/draft/2020-12/vocab/format-assertion';
    public const VOCABULARY_CONTENT = 'https://json-schema.org/draft/2020-12/vocab/content';

    protected array $vocabularies = [
        self::VOCABULARY_CORE => true,
        self::VOCABULARY_APPLICATOR => true,
        self::VOCABULARY_UNEVALUATED => true,
        self::VOCABULARY_VALIDATION => true,
        self::VOCABULARY_META_DATA => true,
        self::VOCABULARY_FORMAT_ANNOTATION => true,
        self::VOCABULARY_FORMAT_ASSERTION => false,
        self::VOCABULARY_CONTENT => true,
    ];

    /**
     * @throws Exception\UnsupportedVocabularyException
     */
    public function __construct(
        string $uri = 'https://json-schema.org/draft/2020-12/schema',
        bool $assertFormat = false,
        bool $assertContentMediaTypeEncoding = false,
        bool $evaluateMutations = false,
        bool $acceptNumericStrings = false,
        bool $shortCircuit = false
    ) {
        parent::__construct(
            $uri,
            $assertFormat,
            $assertContentMediaTypeEncoding,
            $evaluateMutations,
            $acceptNumericStrings,
            $shortCircuit
        );

        if ($assertFormat) {
            /** @noinspection PhpUnhandledExceptionInspection  */
            $this->enableVocabulary(self::VOCABULARY_FORMAT_ASSERTION);
        }

        $this->registerKeywords();
    }

    public function assertFormat(): bool
    {
        /** @noinspection PhpUnhandledExceptionInspection  */
        return $this->vocabularyEnabled(self::VOCABULARY_FORMAT_ASSERTION);
    }

    /**
     * @throws Exception\UnsupportedVocabularyException
     */
    protected function registerKeywords(): void
    {
        // Core
        if ($this->vocabularyEnabled(self::VOCABULARY_CORE)) {
            $this->registerKeyword(new SchemaKeyword(1000), self::VOCABULARY_CORE);
            $this->registerKeyword(new VocabularyKeyword(2000), self::VOCABULARY_CORE);
            $this->registerKeyword(new IdKeyword(3000), self::VOCABULARY_CORE);
            $this->registerKeyword(new AnchorKeyword(4000), self::VOCABULARY_CORE);
            $this->registerKeyword(new DynamicAnchorKeyword(5000), self::VOCABULARY_CORE);
            $this->registerKeyword(new DefsKeyword(6000), self::VOCABULARY_CORE);
            $this->registerKeyword(new RefKeyword(7000), self::VOCABULARY_CORE);
            $this->registerKeyword(new DynamicRefKeyword(8000), self::VOCABULARY_CORE);
            $this->registerKeyword(new CommentKeyword(9000), self::VOCABULARY_CORE);
        } else {
            $this->unregisterKeywordByVocabulary(self::VOCABULARY_CORE);
        }

        // Content
        if ($this->vocabularyEnabled(self::VOCABULARY_CONTENT)) {
            $this->registerKeyword(new ContentEncodingKeyword(10000), self::VOCABULARY_CONTENT);
            $this->registerKeyword(new ContentMediaTypeKeyword(11000), self::VOCABULARY_CONTENT);
            $this->registerKeyword(new ContentSchemaKeyword(12000), self::VOCABULARY_CONTENT);
        } else {
            $this->unregisterKeywordByVocabulary(self::VOCABULARY_CONTENT);
        }

        // Applicator
        if ($this->vocabularyEnabled(self::VOCABULARY_APPLICATOR)) {
            // Keywords for applying sub-schemas to arrays
            $this->registerKeyword(new PrefixItemsKeyword(13000), self::VOCABULARY_APPLICATOR);
            $this->registerKeyword(new ItemsKeyword(14000), self::VOCABULARY_APPLICATOR);
            $this->registerKeyword(new ContainsKeyword(15000), self::VOCABULARY_APPLICATOR);

            // Keywords for applying sub-schemas to objects
            $this->registerKeyword(new PropertiesKeyword(16000), self::VOCABULARY_APPLICATOR);
            $this->registerKeyword(new PatternPropertiesKeyword(17000), self::VOCABULARY_APPLICATOR);
            $this->registerKeyword(new AdditionalPropertiesKeyword(18000), self::VOCABULARY_APPLICATOR);
            $this->registerKeyword(new PropertyNamesKeyword(19000), self::VOCABULARY_APPLICATOR);

            //  Keywords for applying sub-schemas in place
            $this->registerKeyword(new AllOfKeyword(20000), self::VOCABULARY_APPLICATOR);
            $this->registerKeyword(new AnyOfKeyword(21000), self::VOCABULARY_APPLICATOR);
            $this->registerKeyword(new OneOfKeyword(22000), self::VOCABULARY_APPLICATOR);
            $this->registerKeyword(new NotKeyword(23000), self::VOCABULARY_APPLICATOR);
            $this->registerKeyword(new IfKeyword(24000), self::VOCABULARY_APPLICATOR);
            $this->registerKeyword(new ThenKeyword(25000), self::VOCABULARY_APPLICATOR);
            $this->registerKeyword(new ElseKeyword(26000), self::VOCABULARY_APPLICATOR);
            $this->registerKeyword(new DependentSchemasKeyword(27000), self::VOCABULARY_APPLICATOR);
        } else {
            $this->unregisterKeywordByVocabulary(self::VOCABULARY_APPLICATOR);
        }

        // Unevaluated (depends on in-place applicators)
        if ($this->vocabularyEnabled(self::VOCABULARY_UNEVALUATED)) {
            $this->registerKeyword(new UnevaluatedItemsKeyword(28000), self::VOCABULARY_UNEVALUATED);
            $this->registerKeyword(new UnevaluatedPropertiesKeyword(29000), self::VOCABULARY_UNEVALUATED);
        } else {
            $this->unregisterKeywordByVocabulary(self::VOCABULARY_UNEVALUATED);
        }

        // Format annotation
        if ($this->vocabularyEnabled(self::VOCABULARY_FORMAT_ANNOTATION)) {
            $this->registerKeyword(new FormatKeyword(30000), self::VOCABULARY_FORMAT_ANNOTATION);
        } else {
            $this->unregisterKeywordByVocabulary(self::VOCABULARY_FORMAT_ANNOTATION);
        }

        // Validation
        if ($this->vocabularyEnabled(self::VOCABULARY_VALIDATION)) {
            $this->registerKeyword(new TypeKeyword(31000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new ConstKeyword(32000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new EnumKeyword(33000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new MultipleOfKeyword(34000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new MaximumKeyword(35000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new ExclusiveMaximumKeyword(36000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new MinimumKeyword(37000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new ExclusiveMinimumKeyword(38000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new MaxLengthKeyword(39000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new MinLengthKeyword(40000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new PatternKeyword(41000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new MaxItemsKeyword(42000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new MinItemsKeyword(43000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new UniqueItemsKeyword(44000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new MaxContainsKeyword(45000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new MinContainsKeyword(46000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new MaxPropertiesKeyword(47000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new MinPropertiesKeyword(48000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new RequiredKeyword(49000), self::VOCABULARY_VALIDATION);
            $this->registerKeyword(new DependentRequiredKeyword(50000), self::VOCABULARY_VALIDATION);
        } else {
            $this->unregisterKeywordByVocabulary(self::VOCABULARY_VALIDATION);
        }

        // Meta data
        if ($this->vocabularyEnabled(self::VOCABULARY_META_DATA)) {
            $this->registerKeyword(new TitleKeyword(51000), self::VOCABULARY_META_DATA);
            $this->registerKeyword(new DescriptionKeyword(52000), self::VOCABULARY_META_DATA);
            $this->registerKeyword(new DefaultKeyword(53000), self::VOCABULARY_META_DATA);
            $this->registerKeyword(new DeprecatedKeyword(54000), self::VOCABULARY_META_DATA);
            $this->registerKeyword(new ReadOnlyKeyword(55000), self::VOCABULARY_META_DATA);
            $this->registerKeyword(new WriteOnlyKeyword(56000), self::VOCABULARY_META_DATA);
            $this->registerKeyword(new ExamplesKeyword(57000), self::VOCABULARY_META_DATA);
        } else {
            $this->unregisterKeywordByVocabulary(self::VOCABULARY_META_DATA);
        }
    }
}