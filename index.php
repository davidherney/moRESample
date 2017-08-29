<?php

define('_DEBUG', true);

$errors = array();
$msgs = array();
$url = $token = '';
$courseid = null;
$users = null;
$activities = null;
if (isset($_POST['execute_query']) || isset($_POST['execute_grade'])) {

    if (empty($_POST['inputURL'])) {
        $errors[] = 'La URL de la plataforma es requerida';
    }
    else {
        $url = $_POST['inputURL'];
    }

    if (empty($_POST['inputToken'])) {
        $errors[] = 'El token es requerido';
    }
    else {
        $token = $_POST['inputToken'];
    }

    if (empty($_POST['inputCourse'])) {
        $errors[] = 'El id del curso es requerido';
    }
    else {
        $courseid = $_POST['inputCourse'];
    }

    if (count($errors) == 0) {
        query_activities();
        query_users();

        if (isset($_POST['execute_grade'])) {
            set_grades();
        }
    }

}

function query_users() {
    global $url, $token, $errors, $courseid, $users;

    $params = array(
        'wstoken' => $token,
        'wsfunction' => 'core_enrol_get_enrolled_users',
        'moodlewsrestformat' => 'json',
        'courseid' => $courseid,
        'options' => array(
                        array('name' => 'withcapability', 'value' => 'moodle/grade:view'),
                        array('name' => 'onlyactive', 'value' => 1)
                    )
    );

    $users = curl_post($url, $params);

    if ($users === false) {
        return false;
    }

    if (empty($users)) {
        $errors[] = 'No se encontraron estudiantes matriculados en el curso';
    }

}

function query_activities() {
    global $url, $token, $errors, $courseid, $activities;

    $params = array(
        'wstoken' => $token,
        'wsfunction' => 'core_course_get_contents',
        'moodlewsrestformat' => 'json',
        'courseid' => $courseid,
        'options' => array(
                        array('name' => 'excludecontents', 'value' => true)
                    )
    );

    $sections = curl_post($url, $params);

    if ($sections === false) {
        return false;
    }

    if (empty($sections)) {
        $errors[] = 'No se encontraron actividades en el curso';
    }

    $activities = array();
    foreach($sections as $section) {
        $activities += $section->modules;
    }

    if (count($activities) == 0) {
        $activities = null;
        $errors[] = 'No se encontraron actividades en el curso';
    }

}

function set_grades() {
    global $url, $token, $errors, $msgs, $courseid;

    $grades = array();

    foreach($_POST['inputUser'] as $key => $grade) {
        if (!empty($grade)) {
            $grades[] = array('studentid' => $key, 'grade' => $grade, 'str_feedback' => 'Asignado desde moRESample');
        }
    }

    if (count($grades) == 0) {
        $errors[] = 'No se reportaron calificaciones para asignar.';
        return;
    }

    $act = explode('|', $_POST['selectActivity']);

    $params = array(
        'wstoken' => $token,
        'wsfunction' => 'core_grades_update_grades',
        'moodlewsrestformat' => 'json',
        'source' => 'moRESample',
        'courseid' => $courseid,
        'component' => $act[0],
        'activityid' => $act[1],
        'itemnumber' => 0,
        'grades' => $grades
    );

    $res = curl_post($url, $params);

    if ($res === false) {
        return false;
    }

//    if ($res) {
        $msgs[] = 'Calificaciones guardadas con éxito';
//    }
//    else {
//        $errors[] = 'No se pudieron almacenar las calificaciones';
//    }
}

/**
 * Send a POST requst using cURL
 *
 * http://php.net/manual/es/function.curl-exec.php
 *
 * @param string $url to request
 * @param array $post values to send
 * @param array $options for cURL
 * @return string
 */
function curl_post($url, array $post = NULL, array $options = array()) {
    global $errors;
    $l_url = trim($url, '/') . '/webservice/rest/server.php';
    $defaults = array(
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_URL => $l_url,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_POSTFIELDS => http_build_query($post)
    );

    $ch = curl_init();
    curl_setopt_array($ch, ($options + $defaults));
    if(! $result = curl_exec($ch)) {
        if ($result !== "0" && $result !== 0) {
            $errors[] = curl_error($ch);
        }
    }
    curl_close($ch);

    $jresult = json_decode($result);

    if ($jresult === false) {
        $errors[] = 'La respuesta del servidor no es válida';

        if (_DEBUG) {
            echo '<h2>Web server result</h2';
            var_dump($result);
            echo '<hr />';
        }
        return false;
    }

    if (is_object($jresult)) {
        if (property_exists($jresult, 'errorcode') && !empty($jresult->errorcode)) {
            $errors[] = $jresult->message . ' <em>[' . $jresult->errorcode . ']</em>';

            if (_DEBUG) {
                echo '<h2>Web server result</h2>';
                var_dump($result);
                echo '<hr />';
            }
            return false;
        }
    }

    return $jresult;
}

?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Calificador</title>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />

</head>
<body>
    <h1>Ejemplo de calificador externo para moodle</h1>

    <div class="container">

<?php
    if (count($errors) > 0) {
?>
        <div class="row">
            <div class="col">
<?php
        foreach ($errors as $error) {
?>
                <div class="alert alert-dismissible alert-danger">
                    <?php echo $error; ?>
                </div>
<?php
        }
?>
            </div>
        </div>
<?php
    }
?>

<?php
    if (count($msgs) > 0) {
?>
        <div class="row">
            <div class="col">
<?php
        foreach ($msgs as $msg) {
?>
                <div class="alert alert-dismissible alert-success">
                    <?php echo $msg; ?>
                </div>
<?php
        }
?>
            </div>
        </div>
<?php
    }
?>
        <div class="row">
            <div class="col">
                <form class="form-horizontal" method="POST">
                <fieldset>
                    <legend>Configuración</legend>
                    <div class="form-group">
                        <label for="inputURL" class="col-lg-2 control-label">URL de la plataforma</label>
                        <div class="col-lg-10">
                            <input class="form-control" id="inputURL" name="inputURL" placeholder="https://dominio/plataforma/" type="text" value="<? echo $url; ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputToken" class="col-lg-2 control-label">Token</label>
                        <div class="col-lg-10">
                            <input class="form-control" id="inputToken" name="inputToken" placeholder="Token" type="text" value="<? echo $token; ?>" />
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Curso</legend>
                    <div class="form-group">
                        <label for="inputCourse" class="col-lg-2 control-label">Id del curso</label>
                        <div class="col-lg-10">
                            <input class="form-control" id="inputCourse" name="inputCourse" placeholder="##" type="text" value="<? echo $courseid; ?>" />
                        </div>
                    </div>
                    <div class="col-lg-10 col-lg-offset-2">
                        <button type="submit" class="btn btn-primary" name="execute_query">Consultar matriculados y actividades</button>
                    </div>
                </fieldset>
                </form>
            </div>
        </div>

<?php
    if ($users || $activities) {
?>
        <div class="row">
            <div class="col">
                <form class="form-horizontal" method="POST">
                <fieldset>
                    <legend>Actividad</legend>
<?php
    if ($activities) {
?>
                    <div class="form-group">
                        <label for="inputActivity" class="col-lg-2 control-label">Actividad</label>
                        <div class="col-lg-10">
                            <select class="form-control" id="selectActivity" name="selectActivity">
<?php
        foreach ($activities as $activity) {
?>
                            <option value="mod_<? echo $activity->modname . '|' . $activity->id; ?>"><? echo $activity->name . ' (' . $activity->modplural . ')'; ?></option>
<?php
        }
?>
                            </select>
                        </div>
                    </div>
<?php
    }
?>
                </fieldset>

                <fieldset>
                    <legend>Estudiantes</legend>
<?php
    if ($users) {
        foreach ($users as $user) {

?>
                    <div class="form-group">
                        <label for="inputUser_<? echo $user->id; ?>" class="col-lg-2 control-label"><? echo $user->fullname . (empty($user->idnumber) ? '' : ' (#' . $user->idnumber . ')'); ?></label>
                        <div class="col-lg-10">
                            <input class="form-control" id="inputUser_<? echo $user->id; ?>" name="inputUser[<? echo $user->id; ?>]" placeholder="##" type="text" />
                        </div>
                    </div>
<?php
        }
    }

    if ($users && $activities) {
?>
                    <div class="col-lg-10 col-lg-offset-2">
                        <button type="reset" class="btn btn-default">Limpiar</button>
                        <button type="submit" class="btn btn-primary" name="execute_grade">Asignar calificaciones</button>
                    </div>
<?php
    }
?>
                </fieldset>

                    <input name="inputURL" type="hidden" value="<? echo $url; ?>" />
                    <input name="inputToken" type="hidden" value="<? echo $token; ?>" />
                    <input name="inputCourse" type="hidden" value="<? echo $courseid; ?>" />
                </form>
            </div>
        </div>
<?php
}
?>
    </div>

    <script src="js/bootstrap.min.js"></script>

</body>
</html>