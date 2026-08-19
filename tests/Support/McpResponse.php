<?php

namespace NewDebugBar\Tests\Support;

use Illuminate\Testing\Fluent\AssertableJson;
use ReflectionClass;

final class McpResponse
{
    public static function structuredContent(mixed $response): array
    {
        $content = [];

        if (method_exists($response, 'assertStructuredContent')) {
            $response->assertStructuredContent(function (AssertableJson $json) use (&$content): void {
                $content = $json->toArray();
                $json->etc();
            });

            return $content;
        }

        $property = (new ReflectionClass($response))->getProperty('response');
        $payload = $property->getValue($response)->toArray();

        return $payload['result']['structuredContent']
            ?? json_decode($payload['result']['content'][0]['text'], true, flags: JSON_THROW_ON_ERROR);
    }
}
