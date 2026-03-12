<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External function for generating secret/clue pairs via AI.
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_yesno\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;

/**
 * External function to generate secret/clue pairs using the configured AI backend.
 */
class generate_secrets extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'prompt' => new external_value(PARAM_TEXT, 'Prompt for generating secret/clue pairs'),
            'contextid' => new external_value(PARAM_INT, 'Context ID for capability check and AI bridge'),
        ]);
    }

    /**
     * Generate secret/clue pairs using the AI backend.
     *
     * @param string $prompt The generation prompt.
     * @param int $contextid The context ID.
     * @return string JSON-encoded array of {secret, clue} objects.
     */
    public static function execute(string $prompt, int $contextid): string {
        $params = self::validate_parameters(self::execute_parameters(), [
            'prompt'    => $prompt,
            'contextid' => $contextid,
        ]);

        $context = \context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        $jsoninstruction = "\n\nReturn ONLY a valid JSON array with no markdown, no extra text. " .
            'Each element must have "secret" (the word or phrase to guess) and ' .
            '"clue" (an optional hint string, or empty string if no clue). ' .
            'Example: [{"secret":"carrot","clue":"It is orange and crunchy"},{"secret":"potato","clue":""}]';

        $fullprompt = $params['prompt'] . $jsoninstruction;

        $backend = get_config('mod_yesno', 'backend') ?: 'tool_aiconnect';
        $aibridge = new \tool_ai_bridge\ai_bridge($params['contextid'], $backend);
        $response = $aibridge->perform_request($fullprompt, 'feedback');

        return self::extract_json($response);
    }

    /**
     * Extract a JSON array string from an AI response, handling markdown fences.
     *
     * @param string $response Raw AI response text.
     * @return string Validated JSON array string.
     * @throws \moodle_exception If no valid JSON array is found.
     */
    private static function extract_json(string $response): string {
        // Strip markdown code fences that LLMs sometimes add around JSON output.
        $fence = chr(96) . chr(96) . chr(96);
        $response = preg_replace('/' . $fence . '(?:json)?\s*([\s\S]*?)' . $fence . '/', '$1', $response);

        $start = strpos($response, '[');
        $end   = strrpos($response, ']');

        if ($start === false || $end === false || $end <= $start) {
            throw new \moodle_exception('errorparsingjson', 'yesno');
        }

        $jsonstr = substr($response, $start, $end - $start + 1);
        $decoded = json_decode($jsonstr, true);

        if (!is_array($decoded) || empty($decoded)) {
            throw new \moodle_exception('errorparsingjson', 'yesno');
        }

        return $jsonstr;
    }

    /**
     * Returns description of method return value.
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_RAW, 'JSON array of secret/clue pair objects');
    }
}
