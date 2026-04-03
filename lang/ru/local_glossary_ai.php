<?php
/**
 * Русский языковой пакет для плагина "Глоссарий ИИ"
 *
 * @package    local_glossary_ai
 * @author     Смолий Алена
 * @copyright  2026 Алтайский государственный университет
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Глоссарий ИИ';
$string['pluginname_desc'] = 'Автоматическая генерация терминов для глоссария с помощью нейросети GigaChat';

$string['builder'] = 'Глоссарий ИИ';
$string['generate_new'] = 'Создать новые термины';
$string['edit_terms'] = 'Редактирование терминов';

$string['select_glossary'] = 'Выберите глоссарий';
$string['topic'] = 'Тема глоссария';
$string['topic_help'] = 'Укажите тему, по которой будут создаваться термины';
$string['terms_count'] = 'Количество терминов';
$string['terms_count_help'] = 'Выберите количество терминов (10, 25 или 50)';
$string['language'] = 'Язык терминов';
$string['source_file'] = 'Файл-источник (необязательно)';
$string['source_file_help'] = 'Загрузите .txt или .docx для извлечения контекста';
$string['custom_prompt'] = 'Дополнительные указания';
$string['custom_prompt_help'] = 'Например: "Добавить примеры использования"';
$string['generate_btn'] = 'Сгенерировать термины';

$string['error_no_glossary'] = 'В этом курсе нет глоссариев. Сначала создайте глоссарий.';
$string['error_generation'] = 'Ошибка при генерации терминов';
$string['error_api_not_configured'] = 'API GigaChat не настроен';

$string['instruction_link'] = 'Ссылка на инструкцию';
$string['instruction_link_desc'] = 'URL страницы с инструкцией по использованию плагина';

$string['glossary_ai:use'] = 'Использовать плагин "Глоссарий ИИ"';
$string['glossary_ai:manage'] = 'Управлять настройками плагина "Глоссарий ИИ"';
