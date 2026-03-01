# Code Critique – Moodle ‘yesno’ Activity

## 1. Language‑string component mismatch
- The language file is **lang/en/yesno.php** (component `yesno`).
- Settings (`settings.php`) and some admin strings use `get_string('…', 'mod_yesno')` which looks for the component **mod_yesno**.  This will result in missing‑string warnings in the admin UI.
- **Fix**: rename the language file to `mod_yesno.php` (or change all `get_string` calls to use component `yesno`).

## 2. Debug code left in production
- `view.php` contains `xdebug_break();` (line 112).  If Xdebug is not installed the call will cause a fatal error, otherwise it will pause execution unintentionally.
- **Fix**: remove the line before deploying.

## 3. Inconsistent component usage for core strings
- In `mod_form.php` the rule `get_string('maximumchars', '', 255)` passes an empty component. While Moodle falls back to the core component, it is clearer to specify `'moodle'` or omit the component entirely.
- **Fix**: use `get_string('maximumchars', 'moodle', 255)` or `get_string('maximumchars', null, 255)`.

## 4. Potential missing AMD module
- `view.php` loads `mod_yesno/charcounter` via `$PAGE->requires->js_call_amd('mod_yesno/charcounter', 'init');`
- No `amd/src/charcounter.js` (or `amd/build/charcounter.min.js`) is present in the repo. This will cause a JavaScript error on the page.
- **Fix**: add the AMD module or remove the call.

## 5. Lack of input sanitisation before AI request
- The student question is sent directly to the AI bridge (`$aibridge->perform_request`).  If the bridge forwards the raw text to an external LLM, there is a risk of prompt injection.
- **Fix**: sanitise or escape the user input before embedding it in the system prompt.

## 6. Hard‑coded default prompt string
- The default prompt in `settings.php` ends with an unmatched double‑quote inside the single‑quoted string (`"Decision:`).  Though syntactically valid, the wording is confusing and may lead to malformed prompts.
- **Fix**: clarify the string and ensure the final part reads something like `"Decision:"`.

## 7. Missing capability checks for grade updates
- `yesno_update_gradebook` is called after a win/loss without checking whether the user has permission to be graded (e.g., teacher vs. student impersonation).
- **Fix**: verify `has_capability('mod/yesno:grade', $modulecontext)` before updating the gradebook.

## 8. Inconsistent naming of language strings vs. template keys
- Templates expect keys like `max_questions_label` and `max_questions_help`, which are provided by the rendering context, not language strings. This is fine but can be confusing for future developers.
- **Recommendation**: document the mapping in a comment or rename the context variables to match the language keys (`maxquestions_label`).

## 9. No unit tests for core functions
- The repository contains a `tests` directory but only basic DB‑related tests are present. Critical functions (`yesno_process_attempt`, `yesno_render_*`) lack coverage.
- **Recommendation**: add PHPUnit tests covering edge cases (max attempts, score calculation, AI failure handling).

---
**Overall priority**: Fix the language‑string component mismatch and remove the debugging call immediately, as they cause visible failures. Address the other items in subsequent development cycles.
