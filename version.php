<?php
/**
 * Плагин "Глоссарий ИИ" - интеллектуальная генерация терминов глоссария
 * 
 * Использует нейросеть GigaChat для автоматического создания
 * терминов и определений
 *
 * @package    local_glossary_ai
 * @author     Смолий Алена
 * @copyright  2026 Алтайский государственный университет
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_glossary_ai';
$plugin->version = 2026040301;
$plugin->requires = 2022112800;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';

$plugin->author = 'Смолий Алена';
$plugin->author_contacts = 'Алтайский государственный университет';
