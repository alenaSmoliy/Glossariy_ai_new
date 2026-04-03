<?php
/**
 * Обработчик AJAX запросов для добавления терминов в глоссарий
 *
 * @package    local_glossary_ai
 * @author     Смолий Алена
 * @copyright  2026 Алтайский государственный университет
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/editor/term_editor.php');
require_once(__DIR__ . '/classes/glossary/term_manager.php');

header('Content-Type: application/json');

$action = required_param('action', PARAM_ALPHA);
$course_id = required_param('course_id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);
$context = \context_course::instance($course_id);
require_login($course);
require_capability('local/glossary_ai:use', $context);
require_sesskey();

$response = ['success' => false];

switch ($action) {
    case 'add_all_terms':
        $glossary_id = required_param('glossary_id', PARAM_INT);
        $terms_json = required_param('terms', PARAM_RAW);
        $terms = json_decode($terms_json, true);
        
        $manager = new \local_glossary_ai\glossary\term_manager($glossary_id);
        $result = $manager->add_terms_batch($terms);
        
        $response['success'] = true;
        $response['added'] = $result['added'];
        $response['duplicates'] = $result['duplicates'];
        $response['cm_id'] = $manager->get_cm_id();
        
        if ($result['added'] > 0) {
            \local_glossary_ai\editor\term_editor::clear_session();
        }
        break;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
