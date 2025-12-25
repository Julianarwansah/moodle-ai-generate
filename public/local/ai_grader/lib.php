<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Inject the AI Grader JS into the quiz manual grading page.
 */
function local_ai_grader_before_footer()
{
    global $PAGE;

    // Check if we are on the comments page or review page
    if (
        $PAGE->url->compare(new moodle_url('/mod/quiz/comment.php'), URL_MATCH_BASE) ||
        $PAGE->url->compare(new moodle_url('/mod/quiz/review.php'), URL_MATCH_BASE)
    ) {
        // Load the AMD module
        $PAGE->requires->js_call_amd('local_ai_grader/grader', 'init');
    }
}
