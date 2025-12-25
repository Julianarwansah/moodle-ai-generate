<?php
namespace local_ai_grader;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/filelib.php");
require_once("$CFG->dirroot/question/engine/lib.php");

class external extends \external_api
{

    public static function grade_essay_parameters()
    {
        return new \external_function_parameters([
            'question_text' => new \external_value(PARAM_RAW, 'The question text'),
            'student_response' => new \external_value(PARAM_RAW, 'The student response'),
            'max_mark' => new \external_value(PARAM_FLOAT, 'The maximum mark'),
            'attemptid' => new \external_value(PARAM_INT, 'The quiz attempt ID', VALUE_DEFAULT, 0),
            'slot' => new \external_value(PARAM_INT, 'The question slot number', VALUE_DEFAULT, 0)
        ]);
    }

    public static function grade_essay($question_text, $student_response, $max_mark, $attemptid = 0, $slot = 0)
    {
        // Parameter validation
        $params = self::validate_parameters(self::grade_essay_parameters(), [
            'question_text' => $question_text,
            'student_response' => $student_response,
            'max_mark' => $max_mark,
            'attemptid' => $attemptid,
            'slot' => $slot
        ]);

        $quiz_grading_criteria = '';
        $questionid = 0;

        // Fetch Quiz Description and Question ID if attemptid and slot are provided
        if (!empty($params['attemptid']) && !empty($params['slot'])) {
            global $DB;
            $attempt = $DB->get_record('quiz_attempts', ['id' => $params['attemptid']]);
            if ($attempt) {
                $quiz = $DB->get_record('quiz', ['id' => $attempt->quiz]);
                if ($quiz && !empty($quiz->intro)) {
                    // Strip tags to avoid HTML clutter in prompt, or keep them if rich text is useful for AI
                    $quiz_grading_criteria = strip_tags($quiz->intro);
                }

                // Get Question ID
                try {
                    $quba = \question_engine::load_questions_usage_by_activity($attempt->uniqueid);
                    $qa = $quba->get_question_attempt($params['slot']);
                    $question = $qa->get_question();
                    $questionid = $question->id;
                } catch (\Exception $e) {
                    error_log('AI GRADER: Should have found question but failed: ' . $e->getMessage());
                }
            }
        }


        $apikey = get_config('local_ai_grader', 'apikey');
        if (empty($apikey)) {
            throw new \moodle_exception('apikey_missing', 'local_ai_grader');
        }

        $prompt_template = get_config('local_ai_grader', 'prompt_template');
        if (empty($prompt_template)) {
            // Fallback default if somehow not set
            $prompt_template = "You are an expert grader. Please grade the following student answer... (fallback)";
        }

        // Replace placeholders
        $prompt = str_replace(
            ['{question}', '{answer}', '{maxmark}'],
            [$params['question_text'], $params['student_response'], $params['max_mark']],
            $prompt_template
        );

        if (!empty($quiz_grading_criteria)) {
            $prompt .= "\n\nIMPORTANT: Use the following grading criteria/rules defined by the lecturer:\n" . $quiz_grading_criteria;
        }


        // Using gemini-2.5-flash as requested by user
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apikey;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                // INCREASED LIMIT: 8192 to allow for "Thinking" process + JSON response
                'maxOutputTokens' => 8192,
            ]
        ];

        $curl = new \curl();
        $options = [
            'CURLOPT_HTTPHEADER' => ['Content-Type: application/json'],
            'CURLOPT_SSL_VERIFYPEER' => false, // DEBUG: Disable SSL verification for local dev
            'CURLOPT_TIMEOUT' => 30 // Ensure we don't hang forever
        ];

        error_log('AI GRADER: Sending request to ' . $url);
        error_log('AI GRADER: Payload: ' . json_encode($data));

        $response_json = $curl->post($url, json_encode($data), $options);

        error_log('AI GRADER: Response Raw: ' . $response_json);
        error_log('AI GRADER: Curl Error: ' . $curl->get_errno() . ' - ' . $curl->error);

        $response = json_decode($response_json, true);

        if (isset($response['error'])) {
            error_log('AI GRADER: API Error: ' . json_encode($response['error']));
            throw new \moodle_exception('api_error', 'local_ai_grader', '', $response['error']['message']);
        }

        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            error_log('AI GRADER: Invalid Response Structure: ' . print_r($response, true));
            throw new \moodle_exception('api_invalid_response', 'local_ai_grader');
        }

        $ai_text = $response['candidates'][0]['content']['parts'][0]['text'];

        // Clean up markdown code blocks if present to just get the JSON
        $ai_text = preg_replace('/^```json\s*/', '', $ai_text);
        $ai_text = preg_replace('/^```\s*/', '', $ai_text);
        $ai_text = preg_replace('/\s*```$/', '', $ai_text);

        $result = json_decode($ai_text, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($result['mark']) || !isset($result['comment'])) {
            // If strictly invalid JSON, return a fallback. 
            throw new \moodle_exception('api_invalid_response', 'local_ai_grader', '', 'Invalid JSON from AI');
        }



        // Save to logs
        if ($questionid > 0 && !empty($params['attemptid'])) {
            global $DB;
            $log = new \stdClass();
            $log->attemptid = $params['attemptid'];
            $log->questionid = $questionid;
            $log->ai_mark = (float) $result['mark'];
            $log->ai_comment = $result['comment'];
            $log->timecreated = time();
            $DB->insert_record('local_ai_grader_logs', $log);
        }

        return [
            'mark' => (float) $result['mark'],
            'comment' => $result['comment']
        ];
    }

    public static function get_logs_parameters()
    {
        return new \external_function_parameters([
            'attemptid' => new \external_value(PARAM_INT, 'The quiz attempt ID'),
            'slot' => new \external_value(PARAM_INT, 'The question slot number', VALUE_DEFAULT, 0)
        ]);
    }

    public static function get_logs($attemptid, $slot = 0)
    {
        global $DB, $USER;
        $params = self::validate_parameters(self::get_logs_parameters(), [
            'attemptid' => $attemptid,
            'slot' => $slot
        ]);

        $attempt = $DB->get_record('quiz_attempts', ['id' => $params['attemptid']], '*', MUST_EXIST);
        $quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $quiz->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        // Check if the user is the owner of the attempt or has grading permissions
        if ($attempt->userid != $USER->id) {
            require_capability('mod/quiz:viewreports', $context);
        }

        $questionid = 0;
        if ($slot > 0) {
            try {
                $quba = \question_engine::load_questions_usage_by_activity($attempt->uniqueid);
                $qa = $quba->get_question_attempt($params['slot']);
                $questionid = $qa->get_question_id();
            } catch (\Exception $e) {
                // Ignore
            }
        }

        $conditions = ['attemptid' => $params['attemptid']];
        if ($questionid > 0) {
            $conditions['questionid'] = $questionid;
        }

        $logs = $DB->get_records('local_ai_grader_logs', $conditions, 'timecreated ASC');

        $results = [];
        foreach ($logs as $log) {
            $results[] = [
                'id' => $log->id,
                'ai_mark' => (float) $log->ai_mark,
                'ai_comment' => $log->ai_comment,
                'timecreated' => (int) $log->timecreated,
                'questionid' => (int) $log->questionid
            ];
        }

        return $results;
    }

    public static function get_logs_returns()
    {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id' => new \external_value(PARAM_INT, 'Log ID'),
                'ai_mark' => new \external_value(PARAM_FLOAT, 'AI Mark'),
                'ai_comment' => new \external_value(PARAM_RAW, 'AI Comment'),
                'timecreated' => new \external_value(PARAM_INT, 'Time created'),
                'questionid' => new \external_value(PARAM_INT, 'Question ID')
            ])
        );
    }

    public static function grade_essay_returns()
    {
        return new \external_single_structure([
            'mark' => new \external_value(PARAM_FLOAT, 'The suggested mark'),
            'comment' => new \external_value(PARAM_RAW, 'The suggested comment')
        ]);
    }
}
