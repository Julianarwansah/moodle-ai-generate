<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_ai_grader', get_string('pluginname', 'local_ai_grader'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_ai_grader/apikey',
        get_string('apikey', 'local_ai_grader'),
        get_string('apikey_desc', 'local_ai_grader'),
        '',
        PARAM_TEXT
    ));

    $default_prompt = "You are an expert grader. Please grade the following student answer for an essay question.
Question: {question}
Student Answer: {answer}
Maximum Marks: {maxmark}

Return ONLY a valid JSON object with the following format:
{
  \"mark\": <numerical score>,
  \"comment\": \"<feedback for the student>\"
}";

    $settings->add(new admin_setting_configtextarea(
        'local_ai_grader/prompt_template',
        get_string('prompt_template', 'local_ai_grader'),
        get_string('prompt_template_desc', 'local_ai_grader'),
        $default_prompt,
        PARAM_RAW
    ));
}
