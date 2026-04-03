<?php
/**
 * Класс для работы с записями глоссария
 *
 * @package    local_glossary_ai
 * @author     Смолий Алена
 * @copyright  2026 Алтайский государственный университет
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_glossary_ai\glossary;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/glossary/lib.php');

class term_manager {
    
    private $glossary_id;
    private $cm;
    private $glossary;
    private $context;
    
    public function __construct($glossary_id) {
        global $DB;
        
        $this->glossary_id = $glossary_id;
        $this->glossary = $DB->get_record('glossary', ['id' => $glossary_id], '*', MUST_EXIST);
        $this->cm = get_coursemodule_from_instance('glossary', $glossary_id, $this->glossary->course);
        $this->context = \context_module::instance($this->cm->id);
        
        require_capability('mod/glossary:write', $this->context);
    }
    
    public function add_term($term, $definition) {
        global $DB, $USER;
        
        if ($this->term_exists($term)) {
            return ['success' => false, 'error' => 'duplicate'];
        }
        
        $entry = new \stdClass();
        $entry->glossaryid = $this->glossary_id;
        $entry->userid = $USER->id;
        $entry->concept = trim($term);
        $entry->definition = format_text(trim($definition), FORMAT_HTML);
        $entry->definitionformat = FORMAT_HTML;
        $entry->timecreated = time();
        $entry->timemodified = $entry->timecreated;
        $entry->approved = 1;
        $entry->teacherentry = 1;
        
        $entry_id = $DB->insert_record('glossary_entries', $entry);
        
        if ($entry_id) {
            \mod_glossary\local\search\entry_activity::update_index($this->cm, $entry);
            return ['success' => true, 'entry_id' => $entry_id];
        }
        
        return ['success' => false, 'error' => 'db_error'];
    }
    
    public function add_terms_batch($terms) {
        $result = [
            'added' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'entries' => []
        ];
        
        foreach ($terms as $term_data) {
            if (!empty($term_data['term']) && !empty($term_data['definition'])) {
                $res = $this->add_term($term_data['term'], $term_data['definition']);
                
                if ($res['success']) {
                    $result['added']++;
                    $result['entries'][] = $res['entry_id'];
                } elseif ($res['error'] === 'duplicate') {
                    $result['duplicates']++;
                } else {
                    $result['errors']++;
                }
            }
        }
        
        return $result;
    }
    
    public function term_exists($term) {
        global $DB;
        return $DB->record_exists('glossary_entries', [
            'glossaryid' => $this->glossary_id,
            'concept' => trim($term)
        ]);
    }
    
    public function get_cm_id() {
        return $this->cm->id;
    }
    
    public function get_glossary_url() {
        return new \moodle_url('/mod/glossary/view.php', ['id' => $this->cm->id]);
    }
}
