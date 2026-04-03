<?php
/**
 * Основные функции плагина "Глоссарий ИИ"
 *
 * @package    local_glossary_ai
 * @author     Смолий Алена
 * @copyright  2026 Алтайский государственный университет
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Добавление пункта в навигацию курса
 */
function local_glossary_ai_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('local/glossary_ai:use', $context)) {
        $url = new moodle_url('/local/glossary_ai/index.php', ['id' => $course->id]);
        $navigation->add(
            get_string('pluginname', 'local_glossary_ai'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'glossary_ai',
            new pix_icon('i/calendar', get_string('pluginname', 'local_glossary_ai'))
        );
    }
}
