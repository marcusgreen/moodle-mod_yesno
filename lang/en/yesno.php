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
 * String for component 'yesno', language 'en', key 'modulename'
 *
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_yesno
 */

$string['abandonattempt'] = 'Abandon attempt';
$string['abandonconfirm'] = 'Are you sure you want to abandon this attempt? Your progress will be lost and a new secret will be chosen.';
$string['activitydescription'] = 'This is a Twenty Questions activity where students can engage in the classic guessing game.';
$string['addsecret'] = 'Add another secret';
$string['adminonly'] = 'Teacher/Admin Notice';
$string['airesponse'] = 'AI Response';
$string['amiwarmbtn'] = 'Am I warm?';
$string['askquestion'] = 'Ask a question';
$string['attemptsinfo'] = 'You have asked {$a->count} out of {$a->max} questions.';
$string['attemptstarted'] = 'Your attempt has started!';
$string['backends'] = 'AI backend system';
$string['backends_desc'] = 'Tool AI Connect is from https://github.com/marcusgreen/moodle-tool_aiconnect. ' .
    'Core AI system was introduced with Moodle 4.5. Local AI System is from ' .
    'https://github.com/mebis-lp/moodle-local_ai_manager';
$string['charlimitinfo'] = 'You can ask questions with up to {$a} characters.';
$string['charsremaining'] = 'Characters remaining: {$a->remaining}/{$a->max}';
$string['clue'] = 'Clue for students';
$string['clue_help'] = 'An optional clue or hint to help students with the game. This will be displayed to students.';
$string['conversationhistory'] = 'Conversation History';
$string['coreaisubsystem'] = 'Core AI subsystem';
$string['correctanswer'] = 'Correct!';
$string['defaultattempts'] = 'Default attempts';
$string['defaultattempts_desc'] = 'Default number of question attempts for a new activity.';
$string['defaultprompt'] = 'Default prompt';
$string['defaultprompt_desc'] = 'Default prompt for the AI system.';
$string['defaultsystemprompt'] = 'Default System Prompt';
$string['deletesecret'] = 'Delete this secret';
$string['enteryourquestion'] = 'Enter your yes/no question here...';
$string['errorgettingresponse'] = 'Error getting AI response';
$string['errorparsingjson'] = 'Could not parse AI response as JSON. Try again or adjust the prompt.';
$string['eventattemptcompleted'] = 'Attempt completed';
$string['eventattemptstarted'] = 'Attempt started';
$string['eventquestionsubmitted'] = 'Question submitted';
$string['exportimportsecrets'] = 'Export / Import Secrets';
$string['exportsecrets'] = 'Export secrets (JSON)';
$string['finalscore'] = 'Final Score';
$string['finishsession'] = 'Finish Session';
$string['gamefinishedmsg'] = 'The game has finished. You can view your results above.';
$string['gamesettings'] = 'Game Settings';
$string['gamelost'] = 'Game Over';
$string['gameresult'] = 'Game Result';
$string['gamewon'] = 'Congratulations!';
$string['generatesecrets'] = 'Generate Secrets';
$string['generatesecrets_help'] = 'Use AI to automatically generate secret/clue pairs. ' .
    'Enter a topic or context in the prompt field and click "Generate Secrets" to populate the secrets list.';
$string['generatesecretserror'] = 'Error generating secrets';
$string['generating'] = 'Generating...';
$string['help'] = 'How to Play';
$string['helptext'] = '<strong>Welcome to Twenty Questions!</strong><br/>' .
    '<p>Your goal is to identify a secret word or phrase by asking strategic yes/no questions.</p>' .
    '<h4>How Scoring Works:</h4>' .
    '<ul>' .
    '<li>The fewer questions you ask, the higher your score</li>' .
    '<li>Maximum score: {$a} points</li>' .
    '<li>You have {$a->max} questions to find the secret</li>' .
    '<li>If you guess the secret correctly within your question limit, you win!</li>' .
    '<li>If you don\'t find it within the question limit, the game ends and you score 0 points</li>' .
    '</ul>' .
    '<h4>Tips for Success:</h4>' .
    '<ul>' .
    '<li>Ask questions that narrow down possibilities (e.g., "Is it a person?")</li>' .
    '<li>Avoid asking too many similar questions</li>' .
    '<li>Use the AI\'s responses to eliminate options</li>' .
    '<li>Be specific - vague questions get vague answers</li>' .
    '</ul>';
$string['importsecrets'] = 'Import secrets (JSON)';
$string['importsecretsempty'] = 'The JSON file must contain a non-empty array of secret/clue objects.';
$string['importsecretserror'] = 'Import error';
$string['importsecretsneeded'] = 'More fields required';
$string['importsecretsneededmsg'] = 'Your file has {$a->total} secrets but only {$a->needed} more field(s) are needed. Click "Add another secret" {$a->needed} more time(s), then import again.';
$string['incorrectanswer'] = 'Secret not found, keep trying';
$string['amiwarm'] = 'Include Am I warm? button';
$string['amiwarm_help'] = 'If enabled, students will see an "Am I warm?" button that asks the AI ' .
    'whether they are getting closer to the secret based on their last 3 questions.';
$string['localaimanager'] = 'Local AI manager';
$string['managemsg'] = 'You have management permissions for this activity.';
$string['maxcharacters'] = 'Maximum characters per question';
$string['maxcharacters_help'] = 'The maximum number of characters students can enter for each question.';
$string['maxgrade'] = 'Maximum grade';
$string['maxgrade_help'] = 'The highest possible grade that can be awarded for this activity.';
$string['maximumgrade'] = 'Maximum grade';
$string['maximumgrade_desc'] = 'Default maximum grade for the activity.';
$string['maxquestions'] = 'Maximum questions';
$string['maxquestions_help'] = 'The maximum number of questions a student can ask before the game ends. If the student does not guess the secret within this limit, the game ends with a score of 0.';
$string['maxquestionsreached'] = 'You have reached the maximum number of questions allowed for this activity.';
$string['modulename'] = 'Twenty questions';
$string['modulename_help'] = 'The Twenty Questions activity allows students to play the classic guessing game.';
$string['modulenameplural'] = 'Twenty questions';
$string['pluginadministration'] = 'Twenty questions administration';
$string['pluginname'] = 'Twenty questions';
$string['reset'] = 'Reset Session';
$string['resetconfirm'] = 'Are you sure you want to reset this student\'s session? This will delete all their attempts and history.';
$string['resetsession'] = 'Reset Session';
$string['score'] = 'Score';
$string['secret'] = 'Secret';
$string['secret_help'] = 'The secret that students need to guess in the Twenty Questions game.';
$string['secrets'] = 'Secrets and Clues';
$string['secretsprompt'] = 'Generate secrets prompt';
$string['secretsprompt_desc'] = 'Default prompt shown in the editing form for AI-generated secret/clue pairs.';
$string['secretspromptdefault'] = 'Create 10 secret/clue pairs on the topic of vegetables, ' .
    'half of the pairs should include a clue';
$string['sessionreset'] = 'Session has been reset successfully.';
$string['showanswer'] = 'Show correct answer when student does not guess it';
$string['showanswer_help'] = 'If enabled, the correct answer will be displayed to the student at the ' .
    'end of the game if they fail to guess it within the maximum number of questions. If disabled, ' .
    'students will not see the answer.';
$string['startattempt'] = 'Start your attempt';
$string['startinstructions'] = 'Click the button below to begin your Twenty Questions game. Choose your strategy wisely and ask strategic questions to narrow down the possibilities.';
$string['studentquestionprefix'] = 'Student question';
$string['submitquestion'] = 'Submit question';
$string['systemprompt'] = 'System prompt';
$string['systemprompt_help'] = 'Instructions for the LLM that will be used to guide the game.';
$string['toolaiconnect'] = 'Tool AI connect';
$string['tryanotherattempt'] = 'Try Another Secret';
$string['viewmsg'] = 'You can view this activity.';
$string['warmresultno'] = 'You\'re getting colder...';
$string['warmresultyes'] = 'You\'re getting warmer!';
$string['yesnoname'] = 'Activity name';
$string['yesnoname_help'] = 'The name of this Twenty Questions activity';
$string['yourquestion'] = 'Your question';
$string['yourquestion_help'] = 'Ask a yes/no question to help you guess the secret word. Your question ' .
    'must be answerable with "Yes", "No", or "No answer possible". You must ask only one question ' .
    'at a time, and questions are limited by the character count shown above. The AI will evaluate ' .
    'each question and provide a response to help you narrow down the possibilities. Ask strategic ' .
    'questions that help eliminate possibilities and be as specific as possible.';
