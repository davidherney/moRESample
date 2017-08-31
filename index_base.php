<?php

define('_DEBUG', true);

$errors = array();
$msgs = array();

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

    $defaults = array(
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_URL => $url,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_POSTFIELDS => http_build_query($post)
    );

    $ch = curl_init();
    curl_setopt_array($ch, ($options + $defaults));
    $result = curl_exec($ch);
    if( $result === false ) {
        $errors[] = 'CUrl error: ' . curl_error($ch);
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

    <div class="container">
        <h1>Ejemplo de calificador externo para moodle</h1>

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
                            <input class="form-control" id="inputURL" name="inputURL" placeholder="https://dominio/plataforma/" type="text" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputToken" class="col-lg-2 control-label">Token</label>
                        <div class="col-lg-10">
                            <input class="form-control" id="inputToken" name="inputToken" placeholder="Token" type="text" />
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Curso</legend>
                    <div class="form-group">
                        <label for="inputCourse" class="col-lg-2 control-label">Id del curso</label>
                        <div class="col-lg-10">
                            <input class="form-control" id="inputCourse" name="inputCourse" placeholder="##" type="text" />
                        </div>
                    </div>
                    <div class="col-lg-10 col-lg-offset-2">
                        <button type="submit" class="btn btn-primary" name="execute_query">Consultar matriculados y actividades</button>
                    </div>
                </fieldset>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <form class="form-horizontal" method="POST">
                <fieldset>
                    <legend>Actividad</legend>
                    <div class="form-group">
                        <label for="inputActivity" class="col-lg-2 control-label">Actividad</label>
                        <div class="col-lg-10">
                            <select class="form-control" id="selectActivity" name="selectActivity">
                            <option value="mod_forum">Un foro</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Estudiantes</legend>
                    <div class="form-group">
                        <label for="inputUser" class="col-lg-2 control-label">[Nombre del usuario]</label>
                        <div class="col-lg-10">
                            <input class="form-control" id="inputUser" name="inputUser" placeholder="##" type="text" />
                        </div>
                    </div>
                    <div class="col-lg-10 col-lg-offset-2">
                        <button type="reset" class="btn btn-default">Limpiar</button>
                        <button type="submit" class="btn btn-primary" name="execute_grade">Asignar calificaciones</button>
                    </div>
                </fieldset>

                    <input name="inputURL" type="hidden" value="" />
                    <input name="inputToken" type="hidden" value="" />
                    <input name="inputCourse" type="hidden" value="" />
                </form>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.min.js"></script>

</body>
</html>