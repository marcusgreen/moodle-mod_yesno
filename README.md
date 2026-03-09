# Twenty Questions Activity Module for Moodle

A Moodle activity module that implements the classic "Twenty Questions" guessing game using AI to respond to student questions about a secret word or concept.

## Overview

The mod_yesno plugin allows teachers to create interactive guessing games where students ask yes/no questions to determine a secret word or concept. The AI evaluates each question and responds appropriately (Yes, No, or "No answer possible"), helping students develop critical thinking and strategic questioning skills.

## Features

### Core Functionality
- **AI-Powered Responses**: Uses a remote LLM to evaluate student questions and provide contextual responses
- **Multiple Secrets**: Teachers can create multiple secret/clue pairs for variety
- **Random Secret Selection**: Each student attempt randomly selects one secret, so different students can play with different secrets
- **Question Limits**: Configurable maximum number of questions per game
- **Character Limits**: Restrict question length to encourage concise, strategic queries
- **Grading**: Automatic grading based on number of questions needed to guess the secret
- **Conversation History**: Track complete question/response history for each attempt
- **Session Reset**: Teachers can reset individual student attempts

### For Teachers
- **Activity Configuration**:
  - Set activity name and description
  - Create multiple secret/clue pairs
  - Configure system prompt for AI behavior
  - Set maximum questions allowed
  - Set maximum grade
  - Set character limit per question

- **Monitoring & Testing**:
  - View secrets for current attempts while testing
  - Reset student sessions
  - Monitor student progress and scores

### For Students
- **Intuitive Interface**:
  - Ask yes/no questions via text input
  - See real-time character counter
  - View AI responses immediately
  - Track question count
  - Access conversation history

- **Game Mechanics**:
  - Receive random secret assignment
  - Get contextual AI feedback
  - Score based on efficiency (fewer questions = higher score)
  - Win when secret is guessed
  - Lose if max questions reached without guessing

## Installation

1. **Extract the plugin** to your Moodle installation:
   ```
   <moodle-root>/mod/yesno/
   ```

2. **Run Moodle upgrade**:
   - Visit Site Administration → Notifications
   - Follow the on-screen installation steps

3. **Configure Default Settings** (optional):
   - Site Administration → Plugins → Activity modules → Twenty Questions
   - Set default system prompt
   - Set default maximum grade

## Usage Guide

### For Teachers: Creating an Activity

1. **Navigate to Course**:
   - Go to your course and turn on editing
   - Click "Add an activity or resource" → "Twenty questions"

2. **Configure Basic Settings**:
   - **Activity Name**: Title shown to students
   - **Description**: Overview of the activity (shown above the game)
   - **Maximum Grade**: Highest possible score (default: 20)
   - **Maximum Characters per Question**: Character limit for student input (default: 200)
   - **Maximum Questions**: Questions allowed before loss (default: 20)

3. **Add Secrets and Clues**:
   - Click "Secrets and Clues" section
   - Enter the **Secret** (the word/concept to guess)
   - Enter optional **Clue** (hint shown to students)
   - Click "Add another secret" to create additional secret/clue pairs

4. **Configure AI Behavior** (optional):
   - Edit the **System Prompt** to customize how the AI responds
   - Default prompt: Standard Twenty Questions AI behavior
   - Use `{{target_word}}` placeholder for the secret

5. **Save and Make Available**:
   - Click "Save and display"
   - Activity is now available to students

### For Teachers: Testing & Monitoring

1. **Test the Activity**:
   - Enter the activity as you would as a student
   - Your randomly assigned secret is displayed at the top
   - Ask questions to test the AI responses
   - Reset your attempt to try again with a different secret

2. **Monitor Students**:
   - View student attempts and scores
   - See conversation history for each attempt
   - Reset individual student attempts if needed

### For Students: Playing the Game

1. **Start the Game**:
   - Click the activity
   - Begin asking yes/no questions in the input box

2. **Ask Strategic Questions**:
   - Type a yes/no question (within character limit)
   - Click "Submit question"
   - Read the AI response
   - Use responses to narrow down the secret

3. **Win or Lose**:
   - **Win**: Ask a question containing the secret word → automatic score calculation
   - **Lose**: Reach maximum questions without guessing → score of 0
   - View final score and conversation history

4. **Review Attempt**:
   - After game ends, review all questions and responses
   - Cannot ask more questions once game is finished

## Technical Details

### Database Tables

- **yesno**: Main activity instance records
- **yesno_secrets**: Stores multiple secret/clue pairs with sort order
- **yesno_attempts**: Tracks individual student attempts with selected secret
- **yesno_history**: Stores all questions and AI responses for each attempt

### AI Integration

- Uses Claude AI API for natural language processing
- Evaluates questions contextually
- Checks if secret appears in AI response to determine correctness
- Supports multiple secrets per activity

### Scoring Formula

```
Score = max(0, MaxGrade - (CurrentQuestion - 1))
```

Students earn higher scores by guessing the secret with fewer questions.

## Configuration

### Activity-Level Settings

When creating/editing an activity, configure:
- **System Prompt**: Instructions for AI behavior (supports `{{target_word}}` placeholder)
- **Maximum Grade**: Highest possible score
- **Maximum Questions**: Question limit per attempt
- **Maximum Characters**: Input limit per question

### Site-Level Settings

Site administrators can set defaults at:
**Site Administration → Plugins → Activity modules → Twenty Questions**

- **Default Prompt**: Default system prompt for new activities
- **Default Maximum Grade**: Default maximum grade value

## Advanced Features

### Multiple Secrets per Activity

- Add unlimited secret/clue pairs during activity setup
- Each student attempt gets a random secret
- All secrets checked against AI responses (any match = correct)
- Teachers see only their current attempt's secret while playing

### System Prompt Customization

Customize AI behavior using the system prompt. Use `{{target_word}}` for the secret:

**Example**: "You are a helpful AI in a Twenty Questions game. The secret target word is: {{target_word}}. Answer yes/no questions to help the player guess it."

### Question History

- Complete conversation history available after game ends
- Shows all questions asked and AI responses
- Helps with debugging and learning

## Troubleshooting

### AI Responses Not Appearing
- Check API configuration
- Verify AI bridge connection
- Review error messages in Moodle logs

### Secret Not Being Recognized
- Ensure secret is contained in AI response text
- Check exact spelling and capitalization
- Review AI prompt instructions

### Question Count Not Updating
- Refresh the page
- Check database for attempt records
- Verify game status (active/win/loss)

## Requirements

- Moodle 4.0 or later
- Claude AI API access
- PHP 7.4+
- MySQL/PostgreSQL database

## License

GNU General Public License v3 or later

## Support

For bug reports, feature requests, or questions, please refer to the Moodle plugin repository or contact the plugin maintainer.

## Changelog

### Version 1.5 (2026-03-02)
- Added random secret selection per attempt
- Added support for multiple secret/clue pairs
- Improved teacher testing experience
- Enhanced question validation

### Version 1.4 (2026-03-01)
- Added repeating secret/clue groups in form
- Implemented sortorder for secrets

### Version 1.3 (2026-03-01)
- Refactored secret/clue storage to separate table
- Improved database schema

### Earlier Versions
- Initial development and AI integration
