<?php
/**
 * Клиент для работы с API GigaChat
 *
 * @package    local_glossary_ai
 * @author     Смолий Алена
 * @copyright  2026 Алтайский государственный университет
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_glossary_ai\api;

defined('MOODLE_INTERNAL') || die();

class gigachat_client {
    
    private $api_url = 'https://gigachat.devices.sberbank.ru';
    private $client_id;
    private $client_secret;
    private $scope;
    private $model;
    private $temperature;
    private $timeout;
    private $access_token = null;
    private $token_expires = 0;
    
    public function __construct() {
        $this->load_config();
    }
    
    private function load_config() {
        $paths = [
            __DIR__ . '/../../gigachat_config.php',
            '/etc/moodle/gigachat_config.php',
            '/var/www/moodle/local/glossary_ai/gigachat_config.php',
        ];
        
        $config = null;
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $config = include($path);
                break;
            }
        }
        
        if ($config && is_array($config)) {
            $this->client_id = $config['client_id'] ?? '';
            $this->client_secret = $config['client_secret'] ?? '';
            $this->scope = $config['scope'] ?? 'GIGACHAT_API_B2B';
            $this->model = $config['model'] ?? 'GigaChat-2-Pro';
            $this->temperature = (float)($config['temperature'] ?? 0.7);
            $this->timeout = (int)($config['timeout'] ?? 120);
        }
    }
    
    public function is_configured() {
        return !empty($this->client_id) && !empty($this->client_secret);
    }
    
    private function get_access_token() {
        if (!$this->is_configured()) {
            return false;
        }
        
        if ($this->access_token && time() < $this->token_expires) {
            return $this->access_token;
        }
        
        $auth_key = base64_encode($this->client_id . ':' . $this->client_secret);
        
        $ch = curl_init('https://ngw.devices.sberbank.ru:9443/api/v2/oauth');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic {$auth_key}",
                "RqUID: " . $this->client_secret,
                "Accept: application/json",
                "Content-Type: application/x-www-form-urlencoded"
            ],
            CURLOPT_POSTFIELDS => http_build_query(['scope' => $this->scope]),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['access_token'])) {
            $this->access_token = $data['access_token'];
            $this->token_expires = time() + ($data['expires_in'] ?? 3600) - 60;
            return $this->access_token;
        }
        
        return false;
    }
    
    public function extract_docx_text($filepath) {
        $zip = new \ZipArchive();
        if ($zip->open($filepath) !== true) return '';
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) return '';
        $xml = str_replace('</w:p>', "\n", $xml);
        $text = strip_tags($xml);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s*\n/', "\n", $text);
        return trim($text);
    }
    
    private function build_prompt($topic, $count, $language, $context = '', $custom_prompt = '') {
        $lang_text = ($language === 'ru') ? 'на русском языке' : 'in English';
        
        $prompt = "Создай глоссарий по теме '{$topic}'.\n";
        $prompt .= "Сгенерируй {$count} терминов {$lang_text}.\n";
        $prompt .= "Для каждого термина дай четкое определение.\n";
        
        if (!empty($custom_prompt)) {
            $prompt .= "\nДополнительные требования: {$custom_prompt}\n";
        }
        
        $prompt .= "\nФормат вывода - JSON массив. Пример: [{\"term\": \"термин1\", \"definition\": \"определение1\"}, {\"term\": \"термин2\", \"definition\": \"определение2\"}]\n";
        $prompt .= "Не добавляй никаких пояснений, комментариев или дополнительного текста. Только JSON массив.\n";
        
        if (!empty($context)) {
            $prompt .= "\nИспользуй этот текст как источник:\n" . mb_substr($context, 0, 4000);
        }
        
        return $prompt;
    }
    
    private function parse_response($response) {
        $json_start = strpos($response, '[');
        $json_end = strrpos($response, ']');
        
        if ($json_start !== false && $json_end !== false && $json_end > $json_start) {
            $json_string = substr($response, $json_start, $json_end - $json_start + 1);
            $json_string = preg_replace('/,\s*]/', ']', $json_string);
            $json_string = preg_replace('/,\s*}/', '}', $json_string);
            
            $terms = json_decode($json_string, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($terms)) {
                $valid = [];
                foreach ($terms as $item) {
                    if (isset($item['term']) && isset($item['definition'])) {
                        $valid[] = [
                            'term' => trim($item['term']),
                            'definition' => trim($item['definition']),
                            'status' => 'pending'
                        ];
                    }
                }
                if (!empty($valid)) {
                    return $valid;
                }
            }
        }
        
        return false;
    }
    
    public function generate_terms($topic, $count, $language = 'ru', $context = '', $custom_prompt = '') {
        if (!$this->is_configured()) {
            return ['error' => 'api_not_configured'];
        }
        
        $token = $this->get_access_token();
        if (!$token) {
            return ['error' => 'token_failed'];
        }
        
        $prompt = $this->build_prompt($topic, $count, $language, $context, $custom_prompt);
        
        $headers = [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json"
        ];
        
        $body = json_encode([
            "model" => $this->model,
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ],
            "temperature" => $this->temperature,
            "max_tokens" => 4000
        ]);
        
        $ch = curl_init($this->api_url . '/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => $this->timeout
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];
            return $this->parse_response($content);
        }
        
        return false;
    }
}
