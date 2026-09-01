<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddInteractiveBlock;
use App\Mcp\Tools\CreateChapter;
use App\Mcp\Tools\CreateInteractive;
use App\Mcp\Tools\CreateSection;
use App\Mcp\Tools\GetSection;
use App\Mcp\Tools\GetTopic;
use App\Mcp\Tools\ListBlockTypes;
use App\Mcp\Tools\ListTopics;
use App\Mcp\Tools\UpdateSection;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Icon;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('MotionBase')]
// Without these the client has nothing to go on and falls back to guessing at
// the domain, which is how the old Laravel favicon kept showing up. Relative
// paths are resolved against APP_URL by the package.
#[Icon('/favicon.svg', 'image/svg+xml', ['any'])]
#[Icon('/favicon-96x96.png', 'image/png', ['96x96'])]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
MotionBase is a course authoring platform. Courses are called topics; each topic
has chapters, and each chapter has sections. A section is one page of content.

Every tool acts as the authenticated user and only ever sees that user's own
courses. Start with list_topics, then get_topic for the outline, then
get_section to read a page.

# Block types

A section is a list of Editor.js blocks. Five of them are written as Markdown
through create_section and update_section:

  header      ## / ### / ####   Only levels 2-4 exist. Do NOT open a section
                                with a heading repeating its own title - the
                                title is already rendered above the body. Use
                                ### for subheadings; they also fill the page's
                                table of contents, so give every section one.
  paragraph   plain text        **bold**, *italic*, `code`, [links](url).
  list        - item / 1. item  Unordered and ordered.
  code        ``` fences        Put the language after the fence: ```css
  table       | a | b |         GFM pipe table. The first row is the header and
              | - | - |         the separator row is required. Cells may
              | 1 | 2 |         contain **bold**, *italic* and `code`.

The remaining blocks cannot be produced from Markdown:

  interactive   Self-contained HTML graphic. create_interactive stores one,
                add_interactive_block places it in a section.
  alert         Coloured callout (info / warning / danger / neutral).
  quiz          Multiple choice questions with an answer key.
  image         Uploaded picture with a caption.
  youtube       Embedded video.
  lottie        Lottie animation, optionally with a state machine.

get_section renders those six as "> [...]" placeholders and lists them under
rich_blocks. They can be read but not written, with interactive as the one
exception. Creating or editing the other five is done in the web editor.

# Overwriting

update_section with markdown replaces the ENTIRE body. Anything that is not one
of the five Markdown-backed types is lost, and the tool reports what it removed
in dropped_rich_blocks. Read the section first if rich_blocks is not empty, and
re-add interactive graphics afterwards with add_interactive_block.
TXT)]
class MotionBaseServer extends Server
{
    protected array $tools = [
        ListTopics::class,
        ListBlockTypes::class,
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
