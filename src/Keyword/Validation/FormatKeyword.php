<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Validation;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class FormatKeyword extends AbstractKeyword implements StaticKeywordInterface
{
    protected const PATTERN_URI_TEMPLATE = <<<'REGEX'
/^((([\x{21}\x{23}\x{24}\x{26}\x{28}-\x{3B}\x{3D}\x{3F}-\x{5B}\x{5D}\x{5F}\x{61}-\x{7A}\x{7E}]|([\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}])|([\x{E000}-\x{F8FF}\x{F0000}-\x{FFFFD}\x{100000}-\x{10FFFD}])|(%[A-F0-9][A-F0-9])))|(\{(((\+|#)|(\.|\/|;|\?|&)|(\=|,|\!|@|\|)))?([A-Z_0-9]|%[0-9A-F][0-9A-F])((\.)?([A-Z_0-9]|%[0-9A-F][0-9A-F]))*((\:[1-9][0-9]{0,3}|\*))?(,([A-Z_0-9]|%[0-9A-F][0-9A-F])((\.)?([A-Z_0-9]|%[0-9A-F][0-9A-F]))*((\:[1-9][0-9]{0,3}|\*))?)*\}))*$/iu
REGEX;

    protected const PATTERN_DURATION = <<<'REGEX'
/^(P((([0-9]+D|[0-9]+M([0-9]+D)?|[0-9]+Y([0-9]+M([0-9]+D)?)?)((T([0-9]+H([0-9]+M([0-9]+S)?)?|[0-9]+M([0-9]+S)?|[0-9]+S)))?)|(T([0-9]+H([0-9]+M([0-9]+S)?)?|[0-9]+M([0-9]+S)?|[0-9]+S))|[0-9]+W))$/
REGEX;

    protected const PATTERN_DURATION_WEEKS = <<<'REGEX'
/^P[0-9]+W$/
REGEX;

    protected const PATTERN_URI_SCHEME = <<<'REGEX'
/^[a-z][a-z0-9\+-\.]*$/
REGEX;

    protected const PATTERN_URI_PATH_SEGMENT = <<<'REGEX'
/^([a-z0-9\-\._~]|%[0-9a-f][0-9a-f]|[!\$&\(\)*+,;=']|[:@]')+$/i
REGEX;

    protected const PATTERN_URI_FRAGMENT = <<<'REGEX'
/^([a-z0-9\-\._~]|%[0-9a-f][0-9a-f]|[!\$&\(\)*+,;=']|[:@\/?]')+$/i
REGEX;

    protected const PATTERN_URN = <<<'REGEX'
/^urn:[a-z0-9][a-z0-9-]{0,31}:[a-z0-9\(\)+,\-.:=@;\$_!*'%\/?#]+$/
REGEX;

    protected const PATTERN_UUID = <<<'REGEX'
/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i
REGEX;

    protected const PATTERN_PHONE = <<<'REGEX'
/^(\+([0-9]|((\-|\.|\(|\)))?)*[0-9]([0-9]|((\-|\.|\(|\)))?)*(;([-A-Za-z0-9]+)(\=((\[|\]|\/|\:|&|\+|\$)|([A-Za-z0-9]|(\-|_|\.|\!|~|\*|'|\(|\)))|%[0-9A-Fa-f][0-9A-Fa-f])+)?|;ext\=([0-9]|((\-|\.|\(|\)))?)+|;isub\=((;|\/|\?|\:|@|&|\=|\+|\$|,)|([A-Za-z0-9]|(\-|_|\.|\!|~|\*|'|\(|\)))|%[0-9A-Fa-f][0-9A-Fa-f])+)*|(([0-9A-Fa-f]|\*|#|((\-|\.|\(|\)))?)*([0-9A-Fa-f]|\*|#)([0-9A-Fa-f]|\*|#|((\-|\.|\(|\)))?)*)(;([-A-Za-z0-9]+)(\=((\[|\]|\/|\:|&|\+|\$)|([A-Za-z0-9]|(\-|_|\.|\!|~|\*|'|\(|\)))|%[0-9A-Fa-f][0-9A-Fa-f])+)?|;ext\=([0-9]|((\-|\.|\(|\)))?)+|;isub\=((;|\/|\?|\:|@|&|\=|\+|\$|,)|([A-Za-z0-9]|(\-|_|\.|\!|~|\*|'|\(|\)))|%[0-9A-Fa-f][0-9A-Fa-f])+)*;phone\-context\=((([A-Za-z0-9]|[A-Za-z0-9][-A-Za-z0-9]*[A-Za-z0-9])\.)*([A-Za-z]|[A-Za-z][-A-Za-z0-9]*[A-Za-z0-9])(\.)?|\+([0-9]|((\-|\.|\(|\)))?)*[0-9]([0-9]|((\-|\.|\(|\)))?)*)(;([-A-Za-z0-9]+)(\=((\[|\]|\/|\:|&|\+|\$)|([A-Za-z0-9]|(\-|_|\.|\!|~|\*|'|\(|\)))|%[0-9A-Fa-f][0-9A-Fa-f])+)?|;ext\=([0-9]|((\-|\.|\(|\)))?)+|;isub\=((;|\/|\?|\:|@|&|\=|\+|\$|,)|([A-Za-z0-9]|(\-|_|\.|\!|~|\*|'|\(|\)))|%[0-9A-Fa-f][0-9A-Fa-f])+)*)$/
REGEX;

    protected const IDN_DISALLOWED_CHARS = [
        "\u{0640}", # ARABIC TATWEEL
        "\u{07FA}", # NKO LAJANYALAN
        "\u{302E}", # HANGUL SINGLE DOT TONE MARK
        "\u{302F}", # HANGUL DOUBLE DOT TONE MARK
        "\u{3031}", # VERTICAL KANA REPEAT MARK
        "\u{3032}", # VERTICAL KANA REPEAT WITH VOICED SOUND MARK
        "\u{3033}", # VERTICAL KANA REPEAT MARK UPPER HALF
        "\u{3034}", # VERTICAL KANA REPEAT WITH VOICED SOUND MARK UPPER HA
        "\u{3035}", # VERTICAL KANA REPEAT MARK LOWER HALF
        "\u{303B}", # VERTICAL IDEOGRAPHIC ITERATION MARK
    ];

    protected const IDN_CONTEXTUAL_RULE_PATTERNS = [
        "\u{00B7}" => '/l\x{00B7}l/u', # MIDDLE DOT
        "\u{0375}" => '/\x{0375}\p{Greek}/u', # GREEK LOWER NUMERAL SIGN (KERAIA)
        "\u{05f3}" => '/\p{Hebrew}\x{05f3}/u', # HEBREW PUNCTUATION GERESH
        "\u{05f4}" => '/\p{Hebrew}\x{05f4}/u', # HEBREW PUNCTUATION GERSHAYIM
        "\u{30fb}" => '/[\p{Katakana}\p{Hiragana}\p{Han}]/u', # KATAKANA MIDDLE DOT
    ];

    /**
     * @throws StaticKeywordAnalysisException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_string($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a string',
                $this,
                $context
            );
        }
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instance = $context->getInstance();
        if (!is_string($instance)) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        $valid = match ($keywordValue) {
            'email' => $this->evaluateEmail($context),
            'idn-email' => $this->evaluateIdnEmail($context),
            'regex' => $this->evaluateRegex($context),
            'ipv4' => $this->evaluateIpv4($context),
            'ipv6' => $this->evaluateIpv6($context),
            'idn-hostname' => $this->evaluateIdnHostname($context),
            'hostname' => $this->evaluateHostname($context),
            'date' => $this->evaluateDate($context),
            'date-time' => $this->evaluateDateTime($context),
            'time' => $this->evaluateTime($context),
            'json-pointer' => $this->evaluateJsonPointer($context),
            'relative-json-pointer' => $this->evaluateRelativeJsonPointer($context),
            'iri' => $this->evaluateIri($context),
            'iri-reference' => $this->evaluateIriReference($context),
            'uri' => $this->evaluateUri($context),
            'uri-reference' => $this->evaluateUriReference($context),
            'uri-template' => $this->evaluateUriTemplate($context),
            'uuid' => $this->evaluateUuid($context),
            'duration' => $this->evaluateDuration($context),
            default => true
        };

        $result->setAnnotation($valid);

        if ($context->getConfig()->getAssertFormat() && !$valid) {
            $result->setError(
                $instance
                . ' is not a valid '
                . $keywordValue
            );
        }

        return $result;
    }

    protected function evaluateEmail(RuntimeEvaluationContext $context): bool
    {
        return $this->checkEmail($context->getInstance());
    }

    protected function evaluateIdnEmail(RuntimeEvaluationContext $context): bool
    {
        return $this->checkIdnEmail($context->getInstance());
    }

    protected function evaluateRegex(RuntimeEvaluationContext $context): bool
    {
        return $this->checkRegexPattern($context->getInstance());
    }

    protected function evaluateIpv4(RuntimeEvaluationContext $context): bool
    {
        return $this->checkIpv4($context->getInstance());
    }

    protected function evaluateIpv6(RuntimeEvaluationContext $context): bool
    {
        return $this->checkIpv6($context->getInstance());
    }

    protected function evaluateIdnHostname(RuntimeEvaluationContext $context): bool
    {
        return $this->checkIdn($context->getInstance());
    }

    protected function evaluateHostname(RuntimeEvaluationContext $context): bool
    {
        return $this->checkHostname($context->getInstance());
    }

    protected function evaluateDate(RuntimeEvaluationContext $context): bool
    {
        return $this->checkRfc3339Date($context->getInstance());
    }

    protected function evaluateDateTime(RuntimeEvaluationContext $context): bool
    {
        return $this->checkRfc3339Date(substr($context->getInstance(), 0, 10))
                && $this->checkRfc3339Time(substr($context->getInstance(), 11));
    }

    protected function evaluateTime(RuntimeEvaluationContext $context): bool
    {
        return $this->checkRfc3339Time($context->getInstance());
    }

    protected function evaluateJsonPointer(RuntimeEvaluationContext $context): bool
    {
        return $this->checkJsonPointer($context->getInstance(), true);
    }

    protected function evaluateRelativeJsonPointer(RuntimeEvaluationContext $context): bool
    {
        return $this->checkJsonPointer($context->getInstance(), false);
    }

    protected function evaluateIri(RuntimeEvaluationContext $context): bool
    {
        return $this->checkUri($context->getInstance(), true, true);
    }

    protected function evaluateIriReference(RuntimeEvaluationContext $context): bool
    {
        return $this->checkUri($context->getInstance(), false, true);
    }

    protected function evaluateUri(RuntimeEvaluationContext $context): bool
    {
        return $this->checkUri($context->getInstance(), true);
    }

    protected function evaluateUriReference(RuntimeEvaluationContext $context): bool
    {
        return $this->checkUri($context->getInstance(), false);
    }

    protected function evaluateUriTemplate(RuntimeEvaluationContext $context): bool
    {
        return $this->checkUriTemplate($context->getInstance());
    }

    protected function evaluateUuid(RuntimeEvaluationContext $context): bool
    {
        return $this->checkUuid($context->getInstance());
    }

    protected function evaluateDuration(RuntimeEvaluationContext $context): bool
    {
        return $this->checkDuration($context->getInstance());
    }

    protected function checkUriTemplate(string $uriTemplate): bool
    {
        return preg_match(static::PATTERN_URI_TEMPLATE, $uriTemplate) === 1;
    }

    protected function checkJsonPointer(string $jsonPointer, bool $absolute): bool
    {
        if ($absolute) {
            if ($jsonPointer === '') {
                return true;
            }

            if (!str_starts_with($jsonPointer, '/')) {
                return false;
            }
        } else {
            if (str_starts_with($jsonPointer, '/')) {
                return false;
            }

            [$firstSegment] = explode('/', $jsonPointer, 2);

            if (intval($firstSegment) < 0) {
                return false;
            }

            if (str_starts_with($firstSegment, '0') && strlen($firstSegment) >= 2) {
                $charAfter = substr($firstSegment, 1, 1);
                if ($charAfter !== '#') {
                    return false;
                }
            }

            if (preg_match('/[^0-9]#/', $firstSegment) === 1) {
                return false;
            }
        }

        $tildeOffset = 0;
        while (($tildePosition = strpos($jsonPointer, '~', $tildeOffset)) !== false) {
            $charAfter = @substr($jsonPointer, $tildePosition + 1, 1);
            if ($charAfter !== '0' && $charAfter !== '1') {
                return false;
            }

            $tildeOffset = $tildePosition + 1;
        }

        return true;
    }

    protected function checkIpv4(string $ipv4): bool
    {
        return filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    protected function checkIpv6(string $ipv6): bool
    {
        return filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    protected function checkRegexPattern(string $regexPattern): bool
    {
        return is_int(@preg_match('{' . $regexPattern . '}u', ''));
    }

    protected function checkRfc3339Date(string $rfcDate): bool
    {
        if (strlen($rfcDate) !== 10) {
            return false;
        }

        $parts = explode('-', $rfcDate);
        if (count($parts) !== 3) {
            return false;
        }

        [$year, $month, $day] = $parts;

        if (strlen($year) !== 4 || !ctype_digit($year)) {
            return false;
        }

        if (strlen($month) !== 2 || !ctype_digit($month)) {
            return false;
        }

        if (strlen($day) !== 2 || !ctype_digit($day)) {
            return false;
        }

        return checkdate((int) $month, (int) $day, (int) $year);
    }

    protected function checkRfc3339Time(string $rfcTime): bool
    {
        if (preg_match('{[+\-Zz]}', $rfcTime, $matches) === 0) {
            // No offset sign
            return false;
        }

        $offsetSign = $matches[0];
        $parts = explode($offsetSign, $rfcTime, 2);
        $timeParts = explode(':', $parts[0], 3);

        if (count($timeParts) !== 3) {
            return false;
        }

        [$hours, $minutes, $secondsPart] = $timeParts;

        if (strlen($hours) !== 2 || !ctype_digit($hours) || $hours > 23) {
            return false;
        }

        if (strlen($minutes) !== 2 || !ctype_digit($minutes) || $minutes > 59) {
            return false;
        }

        $secondParts = explode('.', $secondsPart, 2);
        $seconds = $secondParts[0];

        if (strlen($seconds) !== 2 || !ctype_digit($seconds) || $seconds > 60) {
            return false;
        }

        $microseconds = $secondParts[1] ?? null;

        if ($microseconds !== null && !ctype_digit($microseconds)) {
            return false;
        }

        $offset = $parts[1] ?? null;

        if ($offsetSign === 'Z' || $offsetSign === 'z') {
            if ($offset) {
                // Offset not allowed if zero offset defined
                return false;
            }

            if ($seconds === '60' && ($hours !== '23' || $minutes !== '59')) {
                // Invalid leap second
                return false;
            }
        } else if ($offset) {
            $offsetParts = explode(':', $parts[1], 2);
            if (count($offsetParts) !== 2) {
                return false;
            }

            [$offsetHours, $offsetMinutes] = $offsetParts;

            if (strlen($offsetHours) !== 2 || $offsetHours > 23) {
                return false;
            }

            if (strlen($offsetMinutes) !== 2 || $offsetMinutes > 59) {
                return false;
            }

            if ($offsetSign === '-') {
                $normalizedHours = ((int) $hours + (int) $offsetHours) % 24;
                $normalizedMinutes = ((int) $minutes + (int) $offsetMinutes) % 60;
            } else {
                $normalizedHours = abs((int) $hours - (int) $offsetHours) % 24;
                $normalizedMinutes = abs((int) $minutes - (int) $offsetMinutes) % 60;
            }

            if ($seconds === '60' && ($normalizedHours !== 23 || $normalizedMinutes !== 59)) {
                // Invalid leap second
                return false;
            }
        }

        return true;
    }

    protected function checkDuration(string $duration): bool
    {
        if (preg_match(static::PATTERN_DURATION, $duration) !== 1) {
            return false;
        }

        if (str_contains($duration, 'W') && preg_match(static::PATTERN_DURATION_WEEKS, $duration) !== 1) {
            // weeks cannot be combined with other units
            return false;
        }

        return true;
    }

    protected function checkIdn(string $idn): bool
    {
        foreach (static::IDN_DISALLOWED_CHARS as $disallowedChar) {
            if (str_contains($idn, $disallowedChar)) {
                return false;
            }
        }

        foreach (static::IDN_CONTEXTUAL_RULE_PATTERNS as $codePoint => $rulePattern) {
            if (!str_contains($idn, $codePoint)) {
                continue;
            }

            if (preg_match($rulePattern, $idn) === 0) {
                return false;
            }
        }

        $punycoded = idn_to_ascii(
            $idn,
            IDNA_CHECK_CONTEXTJ | IDNA_CHECK_BIDI | IDNA_USE_STD3_RULES | IDNA_NONTRANSITIONAL_TO_ASCII,
            INTL_IDNA_VARIANT_UTS46,
            $idnaInfo
        );

        if ($idnaInfo['errors']) {
            return false;
        }

        return $this->checkHostname($punycoded);
    }

    protected function checkHostname(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return false;
        }

        if (str_ends_with($host, '-')) {
            return false;
        }

        return true;
    }

    protected function checkIdnEmail(string $idnEmail): bool
    {
        if (!str_contains($idnEmail, '@')) {
            return false;
        }

        [$localPart, $domainPart] = explode('@', $idnEmail);

        if (!$this->checkIdn($domainPart)) {
            return false;
        }

        $punycoded = idn_to_ascii($localPart) . '@' . idn_to_ascii($domainPart);

        return $this->checkEmail($punycoded);
    }

    protected function checkEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function checkPhone(string $phone): bool
    {
        return preg_match(static::PATTERN_PHONE, $phone) === 1;
    }

    protected function checkUuid(string $uuid): bool
    {
        if (strlen($uuid) !== 36) {
            return false;
        }

        return preg_match(static::PATTERN_UUID, $uuid) === 1;
    }

    protected function checkUrn(string $urn): bool
    {
        return preg_match(static::PATTERN_URN, $urn) === 1;
    }

    protected function checkUri(string $uri, bool $absolute, bool $international = false): bool
    {
        $scheme = parse_url($uri, PHP_URL_SCHEME);

        if ($scheme && !str_starts_with($uri, $scheme . ':/')) {
            // Normalize all URIs with pattern scheme:host to scheme://host to avoid wrong behavior of parse_url function
            $uriNormalized = $scheme . '://' . substr($uri, strlen($scheme) + 1);
        } else {
            $uriNormalized = $uri;
        }

        $uriComponents = parse_url($uriNormalized);

        if (!is_array($uriComponents) || !$uriComponents) {
            return false;
        }

        if ($international) {
            foreach ($uriComponents as $key => $value) {
                $uriComponents[$key] = idn_to_ascii((string) $value);
            }
        }

        $scheme = strtolower($uriComponents['scheme'] ?? '');
        $host = $uriComponents['host'] ?? '';
        $path = $uriComponents['path'] ?? '';
        $fragment = $uriComponents['fragment'] ?? '';

        if ($scheme === 'mailto') {
            if (!$this->checkEmail(substr($uri, 7))) {
                return false;
            }
        } else if ($scheme === 'tel') {
            if (!$this->checkPhone(substr($uri, 4))) {
                return false;
            }
        } else if ($scheme === 'urn') {
            if (!$this->checkUrn($uri)) {
                return false;
            }
        } else {
            if ($absolute && (!$scheme || !$host)) {
                return false;
            }

            if ($scheme && preg_match(static::PATTERN_URI_SCHEME, $scheme) === 0) {
                return false;
            }

            if ($host) {
                if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
                    // Remove brackets from ipv6
                    $host = substr($host, 1, -1);
                }

                if (!$this->checkHostname($host)) {
                    return false;
                }
            }

            if ($path && $path !== '/') {
                $pathSegments = explode('/', $path);

                if (str_starts_with($path, '/')) {
                    array_shift($pathSegments);
                }

                if (str_ends_with($path, '/')) {
                    array_pop($pathSegments);
                }

                foreach ($pathSegments as $pathSegment) {
                    if (preg_match(static::PATTERN_URI_PATH_SEGMENT, $pathSegment) === 0) {
                        return false;
                    }
                }
            }
        }

        if ($fragment && preg_match(static::PATTERN_URI_FRAGMENT, $fragment) === 0) {
            return false;
        }

        return true;
    }
}