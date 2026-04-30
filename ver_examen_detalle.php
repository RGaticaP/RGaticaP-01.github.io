<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'conexion.php';



$examen_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;



if ($examen_id === 0) {

    header('Location: ver_examenes.php');

    exit;

}



$bd = new ConexionBD();

$conexion = $bd->getConexion();



// Obtener información del examen

$stmt = $conexion->prepare("

    SELECT e.id, e.numero_grupo, e.fecha_generacion,

           t.nombre as tipo_examen, c.nombre as carrera

    FROM examenes e

    JOIN tipos_examen t ON e.tipo_examen_id = t.id

    JOIN carreras c ON e.carrera_id = c.id

    WHERE e.id = ?

");

$stmt->bind_param("i", $examen_id);

$stmt->execute();

$examen = $stmt->get_result()->fetch_assoc();

$stmt->close();



if (!$examen) {

    header('Location: ver_examenes.php');

    exit;

}



// Obtener las preguntas del examen

$stmt = $conexion->prepare("

    SELECT ep.orden, p.texto, 

           p.opcion_a, p.opcion_b, p.opcion_c, p.opcion_d,

           p.respuesta_correcta

    FROM examen_preguntas ep

    JOIN preguntas p ON ep.pregunta_id = p.id

    WHERE ep.examen_id = ?

    ORDER BY ep.orden

");

$stmt->bind_param("i", $examen_id);

$stmt->execute();

$preguntas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detalle del Examen #<?php echo $examen_id; ?></title>

    <link rel="stylesheet" href="styles.css">

    <style>

        .examen-info {

            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

            color: white;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 20px;

        }

        

        .pregunta-card {

            background: white;

            border-radius: 8px;

            padding: 20px;

            margin-bottom: 20px;

            border-left: 4px solid #667eea;

            box-shadow: 0 2px 4px rgba(0,0,0,0.1);

        }

        

        .pregunta-numero {

            font-size: 18px;

            font-weight: bold;

            color: #667eea;

            margin-bottom: 10px;

        }

        

        .pregunta-texto {

            font-size: 16px;

            margin-bottom: 15px;

            color: #333;

        }

        

        .opciones {

            margin-left: 20px;

        }

        

        .opcion {

            padding: 8px 12px;

            margin: 5px 0;

            border-radius: 5px;

            background: #f8f9fa;

        }

        

        .opcion-correcta {

            background: #d4edda;

            border-left: 4px solid #28a745;

            font-weight: bold;

        }

        

        .correcta-label {

            display: inline-block;

            background: #28a745;

            color: white;

            padding: 3px 10px;

            border-radius: 15px;

            font-size: 12px;

            margin-top: 10px;

        }

        

        .btn-regresar {

            display: inline-block;

            background: #667eea;

            color: white;

            padding: 12px 30px;

            border-radius: 25px;

            text-decoration: none;

            margin: 20px 0;

            transition: all 0.3s ease;

        }

        

        .btn-regresar:hover {

            background: #764ba2;

            transform: translateY(-2px);

            box-shadow: 0 5px 15px rgba(0,0,0,0.3);

        }

    </style>

</head>

<body>

    <div class="container">

        <h1>📋 Detalle del Examen</h1>

        

        <div class="examen-info">

            <h2>Examen #<?php echo $examen['id']; ?></h2>

            <p><strong>Grupo:</strong> <?php echo $examen['numero_grupo']; ?></p>

            <p><strong>Carrera:</strong> <?php echo $examen['carrera']; ?></p>

            <p><strong>Tipo de Examen:</strong> <?php echo $examen['tipo_examen']; ?></p>

            <p><strong>Fecha:</strong> <?php echo $examen['fecha_generacion']; ?></p>

            <p><strong>Total de Preguntas:</strong> <?php echo count($preguntas); ?></p>

        </div>

        

        <h2>📝 Preguntas y Respuestas</h2>

        

        <?php foreach ($preguntas as $index => $p): ?>

        <div class="pregunta-card">

            <div class="pregunta-numero">Pregunta <?php echo $p['orden']; ?></div>

            <div class="pregunta-texto"><?php echo htmlspecialchars($p['texto']); ?></div>

            

            <div class="opciones">

                <div class="opcion <?php echo $p['respuesta_correcta'] == 'a' ? 'opcion-correcta' : ''; ?>">

                    a) <?php echo htmlspecialchars($p['opcion_a']); ?>

                    <?php if ($p['respuesta_correcta'] == 'a'): ?>

                        <span style="color: #28a745; margin-left: 10px;">✓</span>

                    <?php endif; ?>

                </div>

                

                <div class="opcion <?php echo $p['respuesta_correcta'] == 'b' ? 'opcion-correcta' : ''; ?>">

                    b) <?php echo htmlspecialchars($p['opcion_b']); ?>

                    <?php if ($p['respuesta_correcta'] == 'b'): ?>

                        <span style="color: #28a745; margin-left: 10px;">✓</span>

                    <?php endif; ?>

                </div>

                

                <div class="opcion <?php echo $p['respuesta_correcta'] == 'c' ? 'opcion-correcta' : ''; ?>">

                    c) <?php echo htmlspecialchars($p['opcion_c']); ?>

                    <?php if ($p['respuesta_correcta'] == 'c'): ?>

                        <span style="color: #28a745; margin-left: 10px;">✓</span>

                    <?php endif; ?>

                </div>

                

                <div class="opcion <?php echo $p['respuesta_correcta'] == 'd' ? 'opcion-correcta' : ''; ?>">

                    d) <?php echo htmlspecialchars($p['opcion_d']); ?>

                    <?php if ($p['respuesta_correcta'] == 'd'): ?>

                        <span style="color: #28a745; margin-left: 10px;">✓</span>

                    <?php endif; ?>

                </div>

            </div>

            

            <div class="correcta-label">

                ✓ Respuesta correcta: <?php echo strtoupper($p['respuesta_correcta']); ?>

            </div>

        </div>

        <?php endforeach; ?>

        

        <div style="text-align: center;">

            <a href="ver_examenes.php" class="btn-regresar">← Volver a la lista de exámenes</a>

            <a href="index.html" class="btn-regresar" style="background: #6c757d;">← Ir al generador</a>

        </div>

    </div>

</body>

</html>