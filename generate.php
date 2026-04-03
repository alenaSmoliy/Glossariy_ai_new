<?php
/**
 * Страница просмотра и добавления терминов в глоссарий
 *
 * @package    local_glossary_ai
 * @author     Смолий Алена
 * @copyright  2026 Алтайский государственный университет
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/api/gigachat_client.php');
require_once(__DIR__ . '/classes/editor/term_editor.php');
require_once(__DIR__ . '/classes/glossary/term_manager.php');

$course_id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);
$context = \context_course::instance($course_id);

require_login($course);
require_capability('local/glossary_ai:use', $context);

$generation_data = $SESSION->glossary_generation_data ?? null;
$session_terms = \local_glossary_ai\editor\term_editor::get_terms_from_session();

if (!$generation_data && !$session_terms) {
    redirect(new moodle_url('/local/glossary_ai/index.php', ['id' => $course_id]));
}

$terms = null;
$glossary_id = null;
$api_error = null;

if ($session_terms) {
    $terms = $session_terms['terms'];
    $glossary_id = $session_terms['glossary_id'];
} else if ($generation_data) {
    $client = new \local_glossary_ai\api\gigachat_client();
    
    if (!$client->is_configured()) {
        $api_error = 'API GigaChat не настроен. Проверьте файл конфигурации.';
    } else {
        $result = $client->generate_terms(
            $generation_data['topic'],
            $generation_data['terms_count'],
            $generation_data['language'],
            $generation_data['context'] ?? '',
            $generation_data['custom_prompt'] ?? ''
        );
        
        if (is_array($result) && isset($result['error'])) {
            $api_error = 'Ошибка генерации терминов';
        } elseif (is_array($result) && !empty($result)) {
            $terms = $result;
            \local_glossary_ai\editor\term_editor::save_terms_to_session($terms, $generation_data['glossary_id']);
            $glossary_id = $generation_data['glossary_id'];
        } else {
            $api_error = 'Не удалось сгенерировать термины';
        }
    }
    
    unset($SESSION->glossary_generation_data);
}

$sesskey = sesskey();

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Глоссарий ИИ - просмотр терминов</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        h2 {
            margin: 0;
            color: #1a1a2e;
            font-size: 24px;
        }
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0069d9; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-success:disabled { background: #6c757d; cursor: not-allowed; }
        .terms-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .terms-table th {
            background: #f8f9fa;
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        .terms-table td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }
        .terms-table tr:hover {
            background: #f8f9fa;
        }
        .term-cell {
            font-weight: 500;
            color: #1a1a2e;
        }
        .definition-cell {
            color: #495057;
            line-height: 1.5;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 12px 24px;
            border-radius: 10px;
            color: white;
            z-index: 9999;
            font-weight: 500;
            animation: slideIn 0.3s ease, fadeOut 0.5s ease 2.5s forwards;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .notification-success { background: #28a745; }
        .notification-error { background: #dc3545; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes fadeOut {
            to { opacity: 0; visibility: hidden; }
        }
        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container" id="app">';

if ($api_error) {
    echo '<div class="alert alert-danger">❌ ' . htmlspecialchars($api_error) . '</div>';
    echo '<div style="text-align: center;"><a href="' . $CFG->wwwroot . '/local/glossary_ai/index.php?id=' . $course_id . '" class="btn btn-primary">← Вернуться к генерации</a></div>';
} elseif (!$terms || empty($terms)) {
    echo '<div class="alert alert-warning">⚠️ Нет сгенерированных терминов</div>';
    echo '<div style="text-align: center;"><a href="' . $CFG->wwwroot . '/local/glossary_ai/index.php?id=' . $course_id . '" class="btn btn-primary">← Попробовать снова</a></div>';
} else {
    echo '<div class="header">';
    echo '<h2>📚 Сгенерированные термины</h2>';
    echo '<div>';
    echo '<button id="add-all-btn" class="btn btn-success" type="button">✓ Добавить все в глоссарий</button>';
    echo '<a href="' . $CFG->wwwroot . '/local/glossary_ai/index.php?id=' . $course_id . '" class="btn btn-secondary" style="margin-left: 10px;">← Новые термины</a>';
    echo '</div>';
    echo '</div>';
    
    echo '<table class="terms-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width: 25%">Термин</th>';
    echo '<th style="width: 75%">Определение</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody id="terms-tbody">';
    
    foreach ($terms as $index => $term_data) {
        echo '<tr data-index="' . $index . '">';
        echo '<td class="term-cell">' . htmlspecialchars($term_data['term']) . '</td>';
        echo '<td class="definition-cell">' . nl2br(htmlspecialchars($term_data['definition'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    echo '<input type="hidden" id="glossary-id" value="' . $glossary_id . '">';
    echo '<input type="hidden" id="course-id" value="' . $course_id . '">';
    echo '<input type="hidden" id="wwwroot" value="' . $CFG->wwwroot . '">';
    echo '<input type="hidden" id="sesskey" value="' . $sesskey . '">';
    echo '<div class="footer">Алтайский государственный университет © 2026</div>';
}

echo '    </div>
    <script>
        (function() {
            const glossaryId = document.getElementById("glossary-id")?.value;
            const courseId = document.getElementById("course-id")?.value;
            const wwwroot = document.getElementById("wwwroot")?.value;
            const sesskey = document.getElementById("sesskey")?.value;
            
            if (!glossaryId || !courseId || !wwwroot || !sesskey) {
                console.error("Не найдены параметры");
                return;
            }
            
            function showNotification(message, type) {
                const notification = document.createElement("div");
                notification.className = "notification notification-" + type;
                notification.textContent = message;
                document.body.appendChild(notification);
                setTimeout(function() { notification.remove(); }, 3000);
            }
            
            async function addAllTerms() {
                const rows = document.querySelectorAll("#terms-tbody tr");
                const terms = [];
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const termCell = row.querySelector(".term-cell");
                    const defCell = row.querySelector(".definition-cell");
                    if (termCell && defCell && termCell.textContent.trim()) {
                        terms.push({
                            term: termCell.textContent.trim(),
                            definition: defCell.textContent.trim()
                        });
                    }
                }
                
                if (terms.length === 0) {
                    showNotification("Нет терминов для добавления", "error");
                    return;
                }
                
                const addBtn = document.getElementById("add-all-btn");
                if (addBtn) {
                    addBtn.disabled = true;
                    addBtn.textContent = "⏳ Добавление...";
                }
                
                const formData = new FormData();
                formData.append("action", "add_all_terms");
                formData.append("course_id", courseId);
                formData.append("glossary_id", glossaryId);
                formData.append("terms", JSON.stringify(terms));
                formData.append("sesskey", sesskey);
                
                try {
                    const response = await fetch(wwwroot + "/local/glossary_ai/ajax_handler.php", {
                        method: "POST",
                        body: formData
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        showNotification("✅ Добавлено " + data.added + " терминов. Пропущено: " + (data.duplicates || 0), "success");
                        if (data.cm_id) {
                            setTimeout(function() {
                                if (confirm("Термины добавлены! Перейти к глоссарию?")) {
                                    window.location.href = wwwroot + "/mod/glossary/view.php?id=" + data.cm_id;
                                }
                            }, 500);
                        }
                    } else {
                        showNotification("❌ Ошибка: " + (data.error || "неизвестная"), "error");
                    }
                } catch (error) {
                    console.error("Error:", error);
                    showNotification("❌ Ошибка сервера", "error");
                } finally {
                    if (addBtn) {
                        addBtn.disabled = false;
                        addBtn.textContent = "✓ Добавить все в глоссарий";
                    }
                }
            }
            
            const addAllBtn = document.getElementById("add-all-btn");
            if (addAllBtn) {
                addAllBtn.addEventListener("click", addAllTerms);
            }
        })();
    </script>
</body>
</html>';
