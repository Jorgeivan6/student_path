<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

require_login();

$courseid = optional_param('cid', 0, PARAM_INT);

if ($courseid == SITEID && !$courseid) {
    redirect($CFG->wwwroot);
}

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$PAGE->set_course($course);
$context = $PAGE->context;

// Permitir acceso a administradores y profesores
$isadmin = is_siteadmin($USER);
$COURSE_ROLED_AS_TEACHER = $DB->get_record_sql("
    SELECT m.id
    FROM {user} m 
    LEFT JOIN {role_assignments} m2 ON m.id = m2.userid 
    LEFT JOIN {context} m3 ON m2.contextid = m3.id 
    LEFT JOIN {course} m4 ON m3.instanceid = m4.id 
    WHERE (m3.contextlevel = 50 AND m2.roleid IN (3, 4) AND m.id = {$USER->id}) 
    AND m4.id = {$courseid} 
");

if (!$isadmin && (!isset($COURSE_ROLED_AS_TEACHER->id) || !$COURSE_ROLED_AS_TEACHER->id)) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)), 
             get_string('no_teacher_access', 'block_student_path'));
}

$PAGE->set_url('/blocks/student_path/teacher_view.php', array('cid' => $courseid));

$title = get_string('identity_map_title', 'block_student_path');

$PAGE->set_pagelayout('standard');
$PAGE->set_title($title . " : " . $course->fullname);
$PAGE->set_heading($title . " : " . $course->fullname);

echo $OUTPUT->header();
echo "<link rel='stylesheet' href='" . $CFG->wwwroot . "/blocks/student_path/styles.css'>";
echo "<div class='block_student_path_container'>";

echo "<h1 class='title_student_path'>" . get_string('identity_map_title', 'block_student_path') . "</h1>";

// Obtener estadísticas integradas del curso
$stats = get_integrated_course_stats($courseid);

// Mostrar estadísticas resumidas integradas
echo "<div class='stats-dashboard'>";
echo "<div class='row'>";
echo "<div class='col-md-3'>";
echo "<div class='stat-card'>";
echo "<div class='stat-number'>" . $stats->total_students . "</div>";
echo "<div class='stat-label'>" . get_string('total_students', 'block_student_path') . "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<div class='stat-card'>";
echo "<div class='stat-number'>" . $stats->complete_profiles . "</div>";
echo "<div class='stat-label'>" . get_string('complete_profiles', 'block_student_path') . "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<div class='stat-card'>";
echo "<div class='stat-number'>" . $stats->complete_profiles_percentage . "%</div>";
echo "<div class='stat-label'>" . get_string('completion_rate', 'block_student_path') . "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<div class='stat-card'>";
echo "<div class='stat-number'>" . ($stats->total_students - $stats->complete_profiles) . "</div>";
echo "<div class='stat-label'>" . get_string('pending_profiles', 'block_student_path') . "</div>";
echo "</div>";
echo "</div>";

echo "</div>";
echo "</div>";

// Mostrar desglose por evaluaciones
echo "<div class='evaluation-breakdown'>";
echo "<h3>" . get_string('evaluation_summary', 'block_student_path') . "</h3>";
echo "<div class='row'>";

echo "<div class='col-md-3'>";
echo "<div class='breakdown-card'>";
echo "<div class='breakdown-title'>" . get_string('student_path_test', 'block_student_path') . "</div>";
echo "<div class='breakdown-stats'>";
echo "<span class='breakdown-number'>" . $stats->student_path_completed . "/" . $stats->total_students . "</span>";
echo "<span class='breakdown-percentage'>(" . $stats->student_path_percentage . "%)</span>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<div class='breakdown-card'>";
echo "<div class='breakdown-title'>" . get_string('learning_style_test', 'block_student_path') . "</div>";
echo "<div class='breakdown-stats'>";
echo "<span class='breakdown-number'>" . $stats->learning_style_completed . "/" . $stats->total_students . "</span>";
echo "<span class='breakdown-percentage'>(" . $stats->learning_style_percentage . "%)</span>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<div class='breakdown-card'>";
echo "<div class='breakdown-title'>" . get_string('personality_test', 'block_student_path') . "</div>";
echo "<div class='breakdown-stats'>";
echo "<span class='breakdown-number'>" . $stats->personality_test_completed . "/" . $stats->total_students . "</span>";
echo "<span class='breakdown-percentage'>(" . $stats->personality_test_percentage . "%)</span>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<div class='breakdown-card'>";
echo "<div class='breakdown-title'>" . get_string('tmms_24_test', 'block_student_path') . "</div>";
echo "<div class='breakdown-stats'>";
echo "<span class='breakdown-number'>" . $stats->tmms_24_completed . "/" . $stats->total_students . "</span>";
echo "<span class='breakdown-percentage'>(" . $stats->tmms_24_percentage . "%)</span>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>";

// Segunda fila para CHASIDE
echo "<div class='row mt-3'>";
echo "<div class='col-md-3'>";
echo "<div class='breakdown-card'>";
echo "<div class='breakdown-title'>" . get_string('chaside_test', 'block_student_path') . "</div>";
echo "<div class='breakdown-stats'>";
echo "<span class='breakdown-number'>" . $stats->chaside_completed . "/" . $stats->total_students . "</span>";
echo "<span class='breakdown-percentage'>(" . $stats->chaside_percentage . "%)</span>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div";

// Obtener lista de estudiantes con perfiles integrados
$students = enrol_get_course_users($course->id, true);

echo "<div class='students-table-container'>";
echo "<h3>" . get_string('integrated_students_list', 'block_student_path') . "</h3>";

if (empty($students)) {
    echo "<div class='alert alert-info'>" . get_string('no_students_found', 'block_student_path') . "</div>";
} else {
    // Filtros y búsqueda
    echo "<div class='table-filters mb-3'>";
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<input type='text' id='searchStudent' class='form-control' placeholder='" . get_string('search_student', 'block_student_path') . "'>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<select id='filterCompletion' class='form-select'>";
    echo "<option value='all'>" . get_string('all_students', 'block_student_path') . "</option>";
    echo "<option value='complete'>" . get_string('complete_profiles_only', 'block_student_path') . "</option>";
    echo "<option value='partial'>" . get_string('partial_profiles_only', 'block_student_path') . "</option>";
    echo "<option value='none'>" . get_string('no_profiles_only', 'block_student_path') . "</option>";
    echo "</select>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<div class='btn-group' role='group'>";
    echo "<button type='button' class='btn btn-success btn-sm' onclick='exportIntegratedData(\"csv\")'>📊 CSV</button>";
    echo "<button type='button' class='btn btn-primary btn-sm' onclick='exportIntegratedData(\"excel\")'>📋 Excel</button>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped table-hover' id='studentsTable'>";
    echo "<thead class='table-dark'>";
    echo "<tr>";
    echo "<th>" . get_string('student', 'block_student_path') . "</th>";
    echo "<th>" . get_string('learning_style', 'block_student_path') . "</th>";
    echo "<th>" . get_string('personality_traits', 'block_student_path') . "</th>";
    echo "<th>" . get_string('emotional_intelligence', 'block_student_path') . "</th>";
    echo "<th>" . get_string('chaside_test', 'block_student_path') . "</th>";
    echo "<th>" . get_string('completion_status', 'block_student_path') . "</th>";
    echo "<th>" . get_string('actions', 'block_student_path') . "</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($students as $student) {
        $profile = get_integrated_student_profile($student->id, $course->id);
        
        $completion_class = 'none';
        if ($profile->completion_percentage == 100) {
            $completion_class = 'complete';
        } elseif ($profile->completion_percentage > 0) {
            $completion_class = 'partial';
        }

        echo "<tr class='student-row' data-completion='" . $completion_class . "'>";
        
        // Información del estudiante
        echo "<td>";
        echo "<div class='student-info'>";
        echo "<strong>" . fullname($student) . "</strong>";
        echo "<div class='student-email'>" . $student->email . "</div>";
        echo "</div>";
        echo "</td>";
        
        // Estilo de aprendizaje (resumido)
        echo "<td>";
        if ($profile->learning_style) {
            $style_summary = get_learning_style_summary_short($profile->learning_style_data);
            echo "<div class='learning-style-summary-short'>" . $style_summary . "</div>";
        } else {
            echo "<span class='text-muted'>" . get_string('not_completed', 'block_student_path') . "</span>";
        }
        echo "</td>";
        
        // Rasgos de personalidad (resumido)
        echo "<td>";
        if ($profile->personality_traits) {
            $personality_summary = get_personality_summary_short($profile->personality_data);
            echo "<div class='personality-summary-short'>" . $personality_summary . "</div>";
        } else {
            echo "<span class='text-muted'>" . get_string('not_completed', 'block_student_path') . "</span>";
        }
        echo "</td>";
        
        // Inteligencia emocional (TMMS-24)
        echo "<td>";
        if ($profile->emotional_intelligence) {
            $tmms24_full = get_tmms24_summary($profile->tmms_24_data);
            echo "<div class='tmms24-summary-full'>" . $tmms24_full . "</div>";
        } else {
            echo "<span class='text-muted'>" . get_string('not_completed', 'block_student_path') . "</span>";
        }
        echo "</td>";
        
        // Test vocacional CHASIDE
        echo "<td>";
        if ($profile->chaside_completed) {
            $chaside_summary = get_chaside_summary_short($profile->chaside_data);
            echo "<div class='chaside-summary-short'>" . $chaside_summary . "</div>";
        } else {
            echo "<span class='text-muted'>" . get_string('chaside_test_not_completed', 'block_student_path') . "</span>";
        }
        echo "</td>";
        
        // Estado de finalización
        echo "<td>";
        echo "<div class='completion-indicators'>";
        echo "<span class='indicator " . ($profile->holland_type ? 'completed' : 'pending') . "' title='" . get_string('student_path_test', 'block_student_path') . "'>SP</span>";
        echo "<span class='indicator " . ($profile->learning_style ? 'completed' : 'pending') . "' title='" . get_string('learning_style_test', 'block_student_path') . "'>LS</span>";
        echo "<span class='indicator " . ($profile->personality_traits ? 'completed' : 'pending') . "' title='" . get_string('personality_test', 'block_student_path') . "'>PT</span>";
        echo "<span class='indicator " . ($profile->emotional_intelligence ? 'completed' : 'pending') . "' title='" . get_string('tmms_24_test', 'block_student_path') . "'>EI</span>";
        echo "<span class='indicator " . ($profile->chaside_completed ? 'completed' : 'pending') . "' title='" . get_string('chaside_test', 'block_student_path') . "'>CH</span>";
        echo "</div>";
        echo "<div class='completion-percentage'>" . $profile->completion_percentage . "% " . get_string('complete', 'block_student_path') . "</div>";
        echo "</td>";
        
        // Acciones
        echo "<td>";
        echo "<a href='" . $CFG->wwwroot . "/blocks/student_path/view_profile.php?uid=" . $student->id . "&cid=" . $courseid . "' class='btn btn-sm btn-primary'>" . get_string('view_profile', 'block_student_path') . "</a>";
        echo "</td>";
        
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";
}

echo "</div>";

echo "<div class='action-buttons mt-4'>";
echo "<a href='" . new moodle_url('/course/view.php', array('id' => $courseid)) . "' class='btn btn-secondary'>" . get_string('back_to_course', 'block_student_path') . "</a>";
echo "</div>";

echo "</div>";

// CSS adicional para las nuevas características
echo "<style>
.evaluation-breakdown {
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.breakdown-card {
    background: white;
    padding: 15px;
    border-radius: 6px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 15px;
}

.breakdown-title {
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
}

.breakdown-stats {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.breakdown-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #007bff;
}

.breakdown-percentage {
    color: #666;
    font-weight: 500;
}

.student-info {
    min-width: 150px;
}

.student-email {
    font-size: 0.9rem;
    color: #666;
    margin-top: 4px;
}

.holland-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 0.85rem;
    color: white;
}

.holland-realistic { background-color: #28a745; }
.holland-investigative { background-color: #007bff; }
.holland-artistic { background-color: #e83e8c; }
.holland-social { background-color: #fd7e14; }
.holland-enterprising { background-color: #dc3545; }
.holland-conventional { background-color: #6f42c1; }

.learning-style-summary,
.personality-summary {
    font-size: 0.9rem;
    line-height: 1.4;
}

.completion-indicators {
    display: flex;
    gap: 5px;
    margin-bottom: 5px;
}

.indicator {
    display: inline-block;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: bold;
    text-align: center;
    line-height: 24px;
    color: white;
}

.indicator.completed {
    background-color: #28a745;
}

.indicator.pending {
    background-color: #6c757d;
}

.completion-percentage {
    font-size: 0.9rem;
    color: #007bff;
    font-weight: 500;
}

.small-text {
    font-size: 0.8rem;
    color: #666;
    margin-top: 2px;
}

.text-muted {
    color: #6c757d !important;
    font-style: italic;
}

@media (max-width: 768px) {
    .breakdown-card {
        margin-bottom: 10px;
    }
    
    .completion-indicators {
        justify-content: center;
    }
}
</style>";

// JavaScript para funcionalidad de la tabla
echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    // Búsqueda en tiempo real
    const searchInput = document.getElementById('searchStudent');
    const filterCompletion = document.getElementById('filterCompletion');
    const table = document.getElementById('studentsTable');
    const rows = table.querySelectorAll('tbody tr');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const completionFilter = filterCompletion.value;

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const completion = row.dataset.completion;
            
            const matchesSearch = text.includes(searchTerm);
            const matchesCompletion = completionFilter === 'all' || completion === completionFilter;
            
            row.style.display = matchesSearch && matchesCompletion ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', filterTable);
    filterCompletion.addEventListener('change', filterTable);

    /* Función para exportar datos integrados */
    window.exportIntegratedData = function(format) {
        console.log('Exportando datos integrados en formato:', format);
        var url = '" . new moodle_url('/blocks/student_path/export_integrated.php') . "';
        var fullUrl = url + '?cid=" . $courseid . "&format=' + format;
        console.log('URL de exportación integrada:', fullUrl);
        window.location.href = fullUrl;
    };
});
</script>";

echo $OUTPUT->footer();
?>