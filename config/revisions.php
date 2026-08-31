<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Revision History
    |--------------------------------------------------------------------------
    |
    | How much version history is kept per topic, chapter and section.
    |
    */

    // Revisions retained per record; older ones are pruned.
    'max' => env('REVISIONS_MAX', 30),

    /*
     | The editor autosaves every few seconds. Without a coalescing window a
     | single writing session would fill the whole history and prune away the
     | versions an author actually wants to go back to. Consecutive edits by
     | the same user inside this window share one restore point.
     |
     | Set to 0 to record every single save.
     */
    'coalesce_minutes' => env('REVISIONS_COALESCE_MINUTES', 5),
];
