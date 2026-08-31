<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddInteractiveBlock;
use App\Mcp\Tools\CreateChapter;
use App\Mcp\Tools\CreateInteractive;
use App\Mcp\Tools\CreateSection;
use App\Mcp\Tools\GetSection;
use App\Mcp\Tools\GetTopic;
use App\Mcp\Tools\ListTopics;
use App\Mcp\Tools\UpdateSection;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('MotionBase')]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
MotionBase is a course authoring platform. Courses are called topics; each topic
has chapters, and each chapter has sections. A section is one page of content.

Every tool acts as the authenticated user and only ever sees that user's own
courses. Start with list_topics, then get_topic for the outline, then
get_section to read a page.

Writing content:
- Section bodies are written as Markdown: ## / ### / #### headings, paragraphs,
  "-" and "1." lists, ``` fenced code, **bold**, *italic*, `code`, [links](url).
- Do NOT open a section with a heading repeating its own title - the title is
  already rendered above the body. Use ### for subheadings inside a page; those
  also fill the "on this page" navigation, so give every section at least one.
- update_section with markdown replaces the entire body and drops interactive,
  quiz and image blocks. Read the section first if it has any.

Interactive graphics are self-contained HTML documents: create_interactive
stores one, add_interactive_block places it in a section.
TXT)]
class MotionBaseServer extends Server
{
    protected array $tools = [
        ListTopics::class,
        GetTopic::class,
        GetSection::class,
        CreateChapter::class,
        CreateSection::class,
        UpdateSection::class,
        CreateInteractive::class,
        AddInteractiveBlock::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
