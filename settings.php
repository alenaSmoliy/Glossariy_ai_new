<?php
/**
 * Настройки плагина "Глоссарий ИИ"
 *
 * @package    local_glossary_ai
 * @author     Смолий Алена
 * @copyright  2026 Алтайский государственный университет
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings_page = new admin_settingpage('local_glossary_ai', get_string('pluginname', 'local_glossary_ai'));

    $settings_page->add(new admin_setting_configtext(
        'local_glossary_ai/instruction_link',
        get_string('instruction_link', 'local_glossary_ai'),
        get_string('instruction_link_desc', 'local_glossary_ai'),
        '',
        PARAM_URL
    ));

    $ADMIN->add('localplugins', $settings_page);
}
