<?php

/**
 * This find contain several extension methods (also known as macros) to
 * extend the behaviour of the TestResponse object used in Pest.
 * 
 * It contains several methods that interacts with the response as a DOM.
 * You can see all the available methods right below. The rest of the file
 * is the macro implementations.
 */

namespace Illuminate\Testing {
    if (false) {
        /**
         * @method TestResponse assertHasAttribute(string $selector, string $attribute, ?string $expected = null)
         * @method TestResponse assertElementExists(string $selector)
         * @method TestResponse assertAttributeContains(string $selector, string $attribute, string $needle)
         * @method TestResponse assertTextContains(string $selector, string $expectedText)
         * @method string|null getAttribute(string $selector, string $attribute)
         */
        class TestResponse {}
    }
}

// -------------------------------------------------------------------------
// Runtime macro implementations
// -------------------------------------------------------------------------

namespace {
    use Illuminate\Testing\TestResponse;
    use PHPUnit\Framework\Assert;
    use Symfony\Component\CssSelector\CssSelectorConverter;

    if (!function_exists('__tr_css_to_xpath')) {
        function __tr_css_to_xpath(string $selector): string
        {
            if (! class_exists(CssSelectorConverter::class)) {
                test()->markTestSkipped('symfony/css-selector not installed. Install with: composer require --dev symfony/css-selector');
            }
            $conv = new CssSelectorConverter();
            return $conv->toXPath($selector);
        }
    }

    if (!function_exists('__tr_query_nodes')) {
        /** @return array{0:?\DOMNodeList,1:\DOMXPath} [$nodes, $xpath] */
        function __tr_query_nodes(TestResponse $response, string $selector): array
        {
            $html = $response->getContent();
            $dom = new \DOMDocument();
            @ $dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            $query = __tr_css_to_xpath($selector);
            $nodes = $xpath->query($query);
            return [$nodes, $xpath];
        }
    }

    // Core getter
    if (! TestResponse::hasMacro('getAttribute')) {
        TestResponse::macro('getAttribute', function (string $selector, string $attribute): ?string {
            /** @var TestResponse $this */
            [$nodes] = __tr_query_nodes($this, $selector);
            if (! $nodes || $nodes->length === 0) {
                return null;
            }
            $value = $nodes->item(0)?->attributes?->getNamedItem($attribute)?->nodeValue ?? null;
            return $value;
        });
    }

    // Element existence
    if (! TestResponse::hasMacro('assertElementExists')) {
        TestResponse::macro('assertElementExists', function (string $selector) {
            /** @var TestResponse $this */
            [$nodes] = __tr_query_nodes($this, $selector);
            Assert::assertTrue(($nodes?->length ?? 0) > 0, "No nodes match selector [$selector].");
            return $this;
        });
    }

    // Attribute equals
    if (! TestResponse::hasMacro('assertHasAttribute')) {
        TestResponse::macro('assertHasAttribute', function (string $selector, string $attribute, ?string $expected = null) {
            /** @var TestResponse $this */
            $value = $this->getAttribute($selector, $attribute);
            Assert::assertNotNull($value, "Attribute [$attribute] not found on first matched node for [$selector].");
            if ($expected !== null) {
                Assert::assertSame($expected, $value, "Attribute [$attribute] expected [$expected], got [" . ($value ?? 'null') . '].');
            }
            return $this;
        });
    }

    // Attribute contains
    if (! TestResponse::hasMacro('assertAttributeContains')) {
        TestResponse::macro('assertAttributeContains', function (string $selector, string $attribute, string $needle) {
            /** @var TestResponse $this */
            $value = $this->getAttribute($selector, $attribute);
            Assert::assertNotNull($value, "Attribute [$attribute] not found on first matched node for [$selector].");
            Assert::assertStringContainsString($needle, (string) $value, "Attribute [$attribute] does not contain [$needle]. Actual: [" . ($value ?? 'null') . '].');
            return $this;
        });
    }

    // Text contains inside first matched element
    if (! TestResponse::hasMacro('assertTextContains')) {
        TestResponse::macro('assertTextContains', function (string $selector, string $expectedText) {
            /** @var TestResponse $this */
            [$nodes] = __tr_query_nodes($this, $selector);
            Assert::assertTrue(($nodes?->length ?? 0) > 0, "No nodes match selector [$selector].");
            $node = $nodes->item(0);
            $text = '';
            if ($node) {
                $text = $node->textContent ?? '';
                $text = trim(preg_replace('/\s+/', ' ', (string) $text));
            }
            Assert::assertStringContainsString($expectedText, $text, "Expected element [$selector] to contain text [$expectedText]. Actual: [$text].");
            return $this;
        });
    }
}
