<?php

namespace App\Mcp\Annotations;

use Attribute;
use Laravel\Mcp\Server\Tools\Annotations\ToolAnnotation;

/**
 * Marks a tool as non-mutating.
 *
 * Clients group tools by this hint when asking for permission. Without it the
 * read-only tools land in the same bucket as the ones that write, so granting
 * "list my courses" means granting "overwrite a section" too. laravel/mcp
 * ships no annotation for it, but any ToolAnnotation is emitted verbatim.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class IsReadOnly extends ToolAnnotation
{
    public function __construct(public bool $value = true) {}

    public function key(): string
    {
        return 'readOnlyHint';
    }
}
