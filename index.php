<?php
/**
 * Главная страница - форма для генерации терминов
 *
 * @package    local_glossary_ai
 * @author     Смолий Алена
 * @copyright  2026 Алтайский государственный университет
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/form/generation_form.php');
require_once(__DIR__ . '/classes/editor/term_editor.php');
require_once(__DIR__ . '/classes/api/gigachat_client.php');

$course_id = optional_param('id', 0, PARAM_INT);

if (!$course_id) {
    redirect(new moodle_url('/course/'));
    exit;
}

$course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);
$context = \context_course::instance($course_id);

require_login($course);
require_capability('local/glossary_ai:use', $context);

\local_glossary_ai\editor\term_editor::clear_session();

$PAGE->set_pagelayout('course');
$PAGE->set_url('/local/glossary_ai/index.php', ['id' => $course_id]);
$PAGE->set_title(get_string('pluginname', 'local_glossary_ai'));
$PAGE->set_heading($course->fullname);
$PAGE->requires->css('/local/glossary_ai/styles.css');

$client = new \local_glossary_ai\api\gigachat_client();
$api_configured = $client->is_configured();

$mform = new \local_glossary_ai\form\generation_form(null, ['course_id' => $course_id]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $course_id]));
    
} else if ($data = $mform->get_data()) {
    
    $file_content = '';
    if (!empty($data->source_file)) {
        $user_context = \context_user::instance($USER->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($user_context->id, 'user', 'draft', $data->source_file, 'id DESC', false);
        
        if ($files) {
            $file = reset($files);
            $temp_file = tempnam(sys_get_temp_dir(), 'glossary_');
            $file->copy_content_to($temp_file);
            
            $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
            
            if ($ext === 'txt') {
                $file_content = file_get_contents($temp_file);
            } elseif ($ext === 'docx') {
                $file_content = $client->extract_docx_text($temp_file);
            }
            
            unlink($temp_file);
        }
    }
    
    $save_course_id = $data->course_id ?? $course_id;
    
    $SESSION->glossary_generation_data = [
        'glossary_id' => $data->glossary_id,
        'topic' => $data->topic,
        'terms_count' => $data->terms_count,
        'language' => $data->language,
        'context' => $file_content,
        'custom_prompt' => $data->custom_prompt ?? ''
    ];
    
    redirect(new moodle_url('/local/glossary_ai/generate.php', ['id' => $save_course_id]));
}

echo $OUTPUT->header();

$instruction_url = get_config('local_glossary_ai', 'instruction_link');
if ($instruction_url) {
    echo html_writer::div(
        html_writer::link($instruction_url, '📖 Инструкция по использованию', ['target' => '_blank']),
        'instruction-link'
    );
}

if (!$api_configured) {
    echo '<div class="alert alert-warning">⚠️ API GigaChat не настроен. Проверьте файл gigachat_config.php</div>';
}

$mform->display();

echo $OUTPUT->footer();
