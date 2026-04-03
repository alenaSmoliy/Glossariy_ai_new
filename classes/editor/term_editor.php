<?php
/**
 * Класс для управления терминами в сессии
 *
 * @package    local_glossary_ai
 * @author     Смолий Алена
 * @copyright  2026 Алтайский государственный университет
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_glossary_ai\editor;

defined('MOODLE_INTERNAL') || die();

class term_editor {
    
    const SESSION_KEY = 'glossary_ai_terms';
    
    public static function save_terms_to_session($terms, $glossary_id) {
        global $SESSION;
        
        $SESSION->{self::SESSION_KEY} = [
            'glossary_id' => $glossary_id,
            'terms' => $terms,
            'created' => time()
        ];
    }
    
    public static function get_terms_from_session() {
        global $SESSION;
        
        if (isset($SESSION->{self::SESSION_KEY})) {
            return $SESSION->{self::SESSION_KEY};
        }
        
        return null;
    }
    
    public static function clear_session() {
        global $SESSION;
        unset($SESSION->{self::SESSION_KEY});
    }
}
