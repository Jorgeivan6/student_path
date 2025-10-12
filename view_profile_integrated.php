<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/blocks/student_path/lib.php');

require_login();

$userid = required_param('uid', PARAM_INT);
$courseid = required_param('cid', PARAM_INT);

// Verificar acceso del profesor
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_capability('moodle/course:manageactivities', $context);

// Obtener datos del estudiante
$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

// Obtener perfil integrado del estudiante
$profile = get_integrated_student_profile($userid, $courseid);

// Configurar página
$PAGE->set_url(new moodle_url('/blocks/student_path/view_profile.php', array('uid' => $userid, 'cid' => $courseid)));
$PAGE->set_context($context);
$PAGE->set_title(get_string('integrated_student_profile', 'block_student_path') . ': ' . fullname($user));
$PAGE->set_heading(get_string('integrated_student_profile', 'block_student_path') . ': ' . fullname($user));

// Agregar CSS personalizado
$PAGE->requires->css('/blocks/student_path/styles.css');

// Mostrar header
echo $OUTPUT->header();

// Breadcrumb navigation
echo '<nav aria-label="breadcrumb" class="mb-4">';
echo '<ol class="breadcrumb">';
echo '<li class="breadcrumb-item"><a href="' . new moodle_url('/course/view.php', array('id' => $courseid)) . '">' . $course->fullname . '</a></li>';
echo '<li class="breadcrumb-item"><a href="' . new moodle_url('/blocks/student_path/teacher_view.php', array('cid' => $courseid)) . '">' . get_string('identity_map_title', 'block_student_path') . '</a></li>';
echo '<li class="breadcrumb-item active" aria-current="page">' . fullname($user) . '</li>';
echo '</ol>';
echo '</nav>';

echo '<div class="integrated-profile-container">';

// Encabezado del estudiante
echo '<div class="student-header">';
echo '<div class="row align-items-center">';
echo '<div class="col-md-8">';
echo '<h2 class="mb-2">' . fullname($user) . '</h2>';
echo '<p class="text-muted mb-0">' . $user->email . '</p>';
echo '</div>';
echo '<div class="col-md-4 text-end">';
echo '<div class="completion-overview">';
echo '<div class="completion-circle" data-percentage="' . $profile->completion_percentage . '">';
echo '<span class="completion-text">' . $profile->completion_percentage . '%</span>';
echo '</div>';
echo '<p class="small text-muted mt-2">' . get_string('profile_completion', 'block_student_path') . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Indicadores de estado
echo '<div class="evaluation-status-bar">';
echo '<div class="status-indicators">';

// Student Path
echo '<div class="status-item ' . ($profile->holland_type ? 'completed' : 'pending') . '">';
echo '<div class="status-icon">SP</div>';
echo '<div class="status-label">' . get_string('student_path_test', 'block_student_path') . '</div>';
echo '</div>';

// Learning Style
echo '<div class="status-item ' . ($profile->learning_style ? 'completed' : 'pending') . '">';
echo '<div class="status-icon">LS</div>';
echo '<div class="status-label">' . get_string('learning_style_test', 'block_student_path') . '</div>';
echo '</div>';

// Personality Test
echo '<div class="status-item ' . ($profile->personality_traits ? 'completed' : 'pending') . '">';
echo '<div class="status-icon">PT</div>';
echo '<div class="status-label">' . get_string('personality_test', 'block_student_path') . '</div>';
echo '</div>';

echo '</div>';
echo '</div>';

// Contenido principal en tres columnas
echo '<div class="row mt-4">';

// Primera columna: Identidad Vocacional (Student Path)
echo '<div class="col-lg-4">';
echo '<div class="profile-section">';
echo '<h4 class="section-title">';
echo '<i class="icon fa fa-compass"></i>';
echo get_string('vocational_identity', 'block_student_path');
echo '</h4>';

if ($profile->holland_type) {
    echo '<div class="holland-result">';
    echo '<div class="holland-main-type">';
    echo '<span class="holland-badge holland-' . strtolower($profile->holland_type) . '">';
    echo $profile->holland_type;
    echo '</span>';
    echo '<div class="holland-type-name">' . get_string('holland_type_' . strtolower($profile->holland_type), 'block_student_path') . '</div>';
    echo '</div>';
    
    echo '<div class="holland-score-detail">';
    echo '<p><strong>' . get_string('dominant_score', 'block_student_path') . ':</strong> ' . $profile->holland_score . '</p>';
    echo '</div>';
    
    // Mostrar datos adicionales del perfil vocacional si están disponibles
    if ($profile->student_path_data) {
        $data = json_decode($profile->student_path_data, true);
        if (isset($data['program'])) {
            echo '<div class="additional-info">';
            echo '<p><strong>' . get_string('program', 'block_student_path') . ':</strong> ' . $data['program'] . '</p>';
            if (isset($data['admission_year'])) {
                echo '<p><strong>' . get_string('admission_year', 'block_student_path') . ':</strong> ' . $data['admission_year'] . '</p>';
            }
            if (isset($data['code'])) {
                echo '<p><strong>' . get_string('code', 'block_student_path') . ':</strong> ' . $data['code'] . '</p>';
            }
            echo '</div>';
        }
    }
    
    echo '</div>';
} else {
    echo '<div class="no-data">';
    echo '<p class="text-muted">' . get_string('vocational_test_not_completed', 'block_student_path') . '</p>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

// Segunda columna: Estilo de Aprendizaje
echo '<div class="col-lg-4">';
echo '<div class="profile-section">';
echo '<h4 class="section-title">';
echo '<i class="icon fa fa-graduation-cap"></i>';
echo get_string('learning_style', 'block_student_path');
echo '</h4>';

if ($profile->learning_style) {
    echo '<div class="learning-style-result">';
    $style_details = get_learning_style_summary($profile->learning_style_data);
    echo '<div class="learning-style-summary">' . $style_details . '</div>';
    
    // Mostrar detalles adicionales si están disponibles
    if ($profile->learning_style_data) {
        $ls_data = json_decode($profile->learning_style_data, true);
        if (is_array($ls_data)) {
            echo '<div class="learning-dimensions">';
            echo '<h6>' . get_string('learning_dimensions', 'block_student_path') . ':</h6>';
            echo '<ul class="dimension-list">';
            foreach ($ls_data as $dimension => $value) {
                if (is_numeric($value)) {
                    echo '<li><strong>' . $dimension . ':</strong> ' . $value . '</li>';
                }
            }
            echo '</ul>';
            echo '</div>';
        }
    }
    
    echo '</div>';
} else {
    echo '<div class="no-data">';
    echo '<p class="text-muted">' . get_string('learning_style_test_not_completed', 'block_student_path') . '</p>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

// Tercera columna: Personalidad
echo '<div class="col-lg-4">';
echo '<div class="profile-section">';
echo '<h4 class="section-title">';
echo '<i class="icon fa fa-user"></i>';
echo get_string('personality_profile', 'block_student_path');
echo '</h4>';

if ($profile->personality_traits) {
    echo '<div class="personality-result">';
    $personality_details = get_personality_summary($profile->personality_traits);
    echo '<div class="personality-summary">' . $personality_details . '</div>';
    
    // Mostrar detalles de los cinco grandes si están disponibles
    if ($profile->personality_data) {
        $pt_data = json_decode($profile->personality_data, true);
        if (is_array($pt_data)) {
            echo '<div class="big-five-traits">';
            echo '<h6>' . get_string('big_five_traits', 'block_student_path') . ':</h6>';
            echo '<div class="traits-list">';
            
            $big_five = ['openness', 'conscientiousness', 'extraversion', 'agreeableness', 'neuroticism'];
            foreach ($big_five as $trait) {
                if (isset($pt_data[$trait])) {
                    echo '<div class="trait-item">';
                    echo '<span class="trait-name">' . get_string($trait, 'block_student_path') . '</span>';
                    echo '<div class="trait-bar">';
                    echo '<div class="trait-fill" style="width: ' . ($pt_data[$trait] * 10) . '%"></div>';
                    echo '</div>';
                    echo '<span class="trait-value">' . $pt_data[$trait] . '/10</span>';
                    echo '</div>';
                }
            }
            
            echo '</div>';
            echo '</div>';
        }
    }
    
    echo '</div>';
} else {
    echo '<div class="no-data">';
    echo '<p class="text-muted">' . get_string('personality_test_not_completed', 'block_student_path') . '</p>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

echo '</div>';

// Sección de análisis integrado (si hay datos suficientes)
if ($profile->completion_percentage >= 67) { // Al menos 2 de 3 evaluaciones
    echo '<div class="integrated-analysis mt-4">';
    echo '<h4>' . get_string('integrated_analysis', 'block_student_path') . '</h4>';
    echo '<div class="analysis-content">';
    
    echo '<div class="row">';
    
    // Compatibilidad vocacional-personalidad
    if ($profile->holland_type && $profile->personality_traits) {
        echo '<div class="col-md-6">';
        echo '<div class="analysis-card">';
        echo '<h6>' . get_string('vocational_personality_match', 'block_student_path') . '</h6>';
        echo '<p>' . get_string('analysis_vocational_personality', 'block_student_path') . '</p>';
        echo '</div>';
        echo '</div>';
    }
    
    // Recomendaciones de aprendizaje
    if ($profile->learning_style && ($profile->holland_type || $profile->personality_traits)) {
        echo '<div class="col-md-6">';
        echo '<div class="analysis-card">';
        echo '<h6>' . get_string('learning_recommendations', 'block_student_path') . '</h6>';
        echo '<p>' . get_string('analysis_learning_recommendations', 'block_student_path') . '</p>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// Botones de acción
echo '<div class="action-buttons mt-4">';
echo '<a href="' . new moodle_url('/blocks/student_path/teacher_view.php', array('cid' => $courseid)) . '" class="btn btn-secondary">';
echo get_string('back_to_list', 'block_student_path');
echo '</a>';

if ($profile->completion_percentage > 0) {
    echo '<a href="#" class="btn btn-primary ms-2" onclick="window.print()">';
    echo get_string('print_profile', 'block_student_path');
    echo '</a>';
}

echo '</div>';

echo '</div>';

// CSS personalizado para el perfil integrado
echo '<style>
.integrated-profile-container {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    margin: 20px 0;
}

.student-header {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.completion-overview {
    text-align: center;
}

.completion-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: conic-gradient(#007bff 0deg, #007bff calc(var(--percentage) * 3.6deg), #e9ecef calc(var(--percentage) * 3.6deg), #e9ecef 360deg);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.completion-text {
    background: white;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #007bff;
}

.evaluation-status-bar {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.status-indicators {
    display: flex;
    justify-content: space-around;
    gap: 20px;
}

.status-item {
    text-align: center;
    flex: 1;
}

.status-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-weight: bold;
    color: white;
    font-size: 0.9rem;
}

.status-item.completed .status-icon {
    background-color: #28a745;
}

.status-item.pending .status-icon {
    background-color: #6c757d;
}

.status-label {
    font-size: 0.9rem;
    font-weight: 500;
}

.profile-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    height: 100%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.section-title .icon {
    color: #007bff;
}

.holland-result {
    text-align: center;
}

.holland-main-type {
    margin-bottom: 15px;
}

.holland-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: bold;
    font-size: 1.2rem;
    color: white;
    margin-bottom: 8px;
}

.holland-realistic { background-color: #28a745; }
.holland-investigative { background-color: #007bff; }
.holland-artistic { background-color: #e83e8c; }
.holland-social { background-color: #fd7e14; }
.holland-enterprising { background-color: #dc3545; }
.holland-conventional { background-color: #6f42c1; }

.holland-type-name {
    font-weight: 600;
    color: #333;
    margin-top: 5px;
}

.dimension-list {
    list-style: none;
    padding: 0;
}

.dimension-list li {
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

.traits-list {
    margin-top: 15px;
}

.trait-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.trait-name {
    flex: 0 0 120px;
    font-size: 0.9rem;
    font-weight: 500;
}

.trait-bar {
    flex: 1;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.trait-fill {
    height: 100%;
    background: #007bff;
    transition: width 0.3s ease;
}

.trait-value {
    flex: 0 0 40px;
    text-align: right;
    font-size: 0.9rem;
    font-weight: 500;
    color: #007bff;
}

.integrated-analysis {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.analysis-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    height: 100%;
}

.no-data {
    text-align: center;
    padding: 40px 20px;
}

.action-buttons {
    text-align: center;
}

@media (max-width: 768px) {
    .status-indicators {
        flex-direction: column;
        gap: 15px;
    }
    
    .trait-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .trait-name {
        flex: none;
    }
    
    .trait-bar {
        width: 100%;
    }
}

@media print {
    .action-buttons,
    .breadcrumb {
        display: none !important;
    }
    
    .integrated-profile-container {
        background: white !important;
        box-shadow: none !important;
    }
}
</style>';

// JavaScript para el círculo de progreso
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    const circles = document.querySelectorAll(".completion-circle");
    circles.forEach(circle => {
        const percentage = circle.dataset.percentage;
        circle.style.setProperty("--percentage", percentage);
    });
});
</script>';

echo $OUTPUT->footer();
?>