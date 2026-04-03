<?php
/**
 * Форма для генерации терминов глоссария
 *
 * @package    local_glossary_ai
 * @author     Смолий Алена
 * @copyright  2026 Алтайский государственный университет
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_glossary_ai\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class generation_form extends \moodleform {
    
    protected function definition() {
        global $COURSE, $DB;
        
        $mform = $this->_form;
        
        // Скрытое поле с ID курса
        $mform->addElement('hidden', 'course_id');
        $mform->setType('course_id', PARAM_INT);
        $mform->setDefault('course_id', $COURSE->id);
        
        // Заголовок
        $mform->addElement('html', '
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    border-radius: 12px; 
                    padding: 20px; 
                    margin-bottom: 20px;
                    color: white;
                    text-align: center;">
            <h2 style="color: white; margin: 0;">🤖 Глоссарий ИИ</h2>
            <p style="margin: 5px 0 0 0;">Генерация терминов с помощью нейросети GigaChat</p>
        </div>
        ');
        
        // Получаем список глоссариев
        $glossaries = $DB->get_records_select_menu(
            'glossary',
            'course = ?',
            [$COURSE->id],
            'name',
            'id, name'
        );
        
        if (empty($glossaries)) {
            $mform->addElement('html', '
            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 10px; padding: 20px; text-align: center;">
                <span style="font-size: 36px;">📚</span>
                <p style="margin: 10px 0 0 0;">Сначала создайте глоссарий в курсе</p>
            </div>
            ');
            return;
        }
        
        $mform->addElement('select', 'glossary_id', get_string('select_glossary', 'local_glossary_ai'), $glossaries);
        $mform->addRule('glossary_id', null, 'required');
        
        $mform->addElement('text', 'topic', get_string('topic', 'local_glossary_ai'), [
            'placeholder' => 'Например: Основы программирования',
            'style' => 'width: 100%; padding: 8px;'
        ]);
        $mform->setType('topic', PARAM_TEXT);
        $mform->addRule('topic', null, 'required');
        
        // Количество терминов
        $count_options = [10 => '10 терминов', 25 => '25 терминов', 50 => '50 терминов'];
        $mform->addElement('select', 'terms_count', get_string('terms_count', 'local_glossary_ai'), $count_options);
        $mform->setDefault('terms_count', 10);
        
        // Язык
        $mform->addElement('select', 'language', get_string('language', 'local_glossary_ai'), [
            'ru' => 'Русский',
            'en' => 'English'
        ]);
        $mform->setDefault('language', 'ru');
        
        // Дополнительные настройки
        $mform->addElement('filepicker', 'source_file', get_string('source_file', 'local_glossary_ai'), null, [
            'accepted_types' => ['.txt', '.docx'],
            'maxbytes' => 10485760
        ]);
        
        $mform->addElement('textarea', 'custom_prompt', get_string('custom_prompt', 'local_glossary_ai'), [
            'rows' => 2,
            'placeholder' => 'Например: добавить примеры использования',
            'style' => 'width: 100%;'
        ]);
        $mform->setType('custom_prompt', PARAM_TEXT);
        
        $this->add_action_buttons(false, get_string('generate_btn', 'local_glossary_ai'));
    }
}
