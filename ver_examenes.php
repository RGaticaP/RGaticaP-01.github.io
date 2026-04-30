<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'conexion.php';

$bd = new ConexionBD();

$conexion = $bd->getConexion();



// Obtener todos los exámenes con estadísticas

$resultado = $conexion->query("

    SELECT e.id, e.numero_grupo, e.fecha_generacion,

           t.nombre as tipo_examen, c.nombre as carrera,

           (SELECT COUNT(*) FROM examen_preguntas WHERE examen_id = e.id) as total_preguntas

    FROM examenes e

    JOIN tipos_examen t ON e.tipo_examen_id = t.id

    JOIN carreras c ON e.carrera_id = c.id

    ORDER BY e.fecha_generacion DESC

");

$examenes = $resultado->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>

<html>

<head>

    <title>Exámenes Guardados</title>
<div style="text-align: right; margin-bottom: 20px;">
    <span style="margin-right: 15px;">👤 <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
    <a href="logout.php" style="background: #dc3545; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none;">Cerrar Sesión</a>
</div>
    <link rel="stylesheet" href="styles.css">

    <style>

        .tabla-examenes {

            width: 100%;

            border-collapse: collapse;

            margin: 20px 0;

            background: white;

            border-radius: 10px;

            overflow: hidden;

            box-shadow: 0 4px 6px rgba(0,0,0,0.1);

        }

        .tabla-examenes th {

            background: #667eea;

            color: white;

            padding: 15px;

            font-size: 14px;

        }

        .tabla-examenes td {

            padding: 12px 15px;

            border-bottom: 1px solid #ddd;

            text-align: center;

        }

        .tabla-examenes tr:hover {

            background: #f5f5f5;

        }

        .btn-ver {

            background: #28a745;

            color: white;

            padding: 8px 15px;

            border-radius: 5px;

            text-decoration: none;

            font-size: 13px;

            transition: all 0.3s ease;

        }

        .btn-ver:hover {

            background: #218838;

            transform: scale(1.05);

        }

        .stats-card {

            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

            color: white;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 20px;

        }

        .stats-number {

            font-size: 32px;

            font-weight: bold;

        }

    </style>

</head>

<body>

    <div class="container">

        <h1>📋 Exámenes Guardados en BD</h1>

        

        <div class="stats-card">

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); text-align: center;">

                <div>

                    <div class="stats-number"><?php echo count($examenes); ?></div>

                    <div>Total Exámenes</div>

                </div>

                <div>

                    <div class="stats-number">

                        <?php 

                        $total_preguntas = 0;

                        foreach ($examenes as $e) $total_preguntas += $e['total_preguntas'];

                        echo $total_preguntas;

                        ?>

                    </div>

                    <div>Total Preguntas</div>

                </div>

                <div>

                    <div class="stats-number">

                        <?php

                        $carreras_distintas = array_unique(array_column($examenes, 'carrera'));

                        echo count($carreras_distintas);

                        ?>

                    </div>

                    <div>Carreras</div>

                </div>

            </div>

        </div>

        

        <?php if (empty($examenes)): ?>

            <div class="card" style="text-align: center; padding: 40px;">

                <p style="font-size: 18px; color: #666;">📭 No hay exámenes guardados aún</p>

                <p>Genera tu primer examen desde el <a href="index.html">generador</a></p>

            </div>

        <?php else: ?>

            <table class="tabla-examenes">

                <tr>

                    <th>ID</th>

                    <th>Grupo</th>

                    <th>Carrera</th>

                    <th>Tipo Examen</th>

                    <th>Preguntas</th>

                    <th>Fecha</th>

                    <th>Acción</th>

                </tr>

                <?php foreach ($examenes as $ex): ?>

                <tr>

                    <td><strong>#<?php echo $ex['id']; ?></strong></td>

                    <td><?php echo $ex['numero_grupo']; ?></td>

                    <td><?php echo $ex['carrera']; ?></td>

                    <td><?php echo $ex['tipo_examen']; ?></td>

                    <td><?php echo $ex['total_preguntas']; ?></td>

                    <td><?php echo date('d/m/Y H:i', strtotime($ex['fecha_generacion'])); ?></td>

                    <td>

                        <a href="ver_examen_detalle.php?id=<?php echo $ex['id']; ?>" class="btn-ver">

                            👁️ Ver detalles

                        </a>

                    </td>

                </tr>

                <?php endforeach; ?>

            </table>

        <?php endif; ?>

        

        <br>

        <div style="display: flex; gap: 10px; justify-content: center;">

            <a href="index.html" class="btn-regresar" style="background: #667eea; color: white; padding: 12px 30px; border-radius: 25px; text-decoration: none;">← Volver al generador</a>

        </div>

    </div>

</body>

</html>