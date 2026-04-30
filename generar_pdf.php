<?php
// Activar errores para depurar (solo temporalmente)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/pdf");

// Cargar TCPDF
require_once __DIR__ . '/vendor/autoload.php';
// Cargar conexión a BD
require_once __DIR__ . '/conexion.php';
$bd = new ConexionBD();

// Recibir datos
$input = json_decode(file_get_contents('php://input'), true);
$grupo = $input['grupo'] ?? [];
$numeroGrupo = (int)($input['numeroGrupo'] ?? 1);
$tipoExamen = $input['tipoExamen'] ?? 'Ordinario 1';
$carrera = $input['carrera'] ?? 'Ingeniería Industrial'; // 🔥 NUEVO: Carrera

if (empty($grupo)) {
    die(json_encode(['error' => 'No se recibieron datos']));
}

// Crear nuevo PDF con TCPDF (configuración UTF-8)
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Eliminar cabecera y pie por defecto
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Configurar márgenes
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 25);

// Agregar página
$pdf->AddPage();

// ===== DOS IMÁGENES CON TÍTULO CENTRADO =====
$ruta_imagen_izq = __DIR__ . '/imagenes/UPIICSA_LOGO.jpg';
$ruta_imagen_der = __DIR__ . '/imagenes/IPN.png';

$ancho_imagen = 30;
$alto_imagen_aprox = 30;
$ancho_imagen2 = 24;
$alto_imagen_aprox2 = 24;
$margen_superior = 15;

// Calcular posición Y para centrar el título
$y_imagenes = $margen_superior;
$y_imagenes2 = 11;
$y_titulo = $y_imagenes + ($alto_imagen_aprox / 2);

// Imagen izquierda
if (file_exists($ruta_imagen_izq)) {
    $pdf->Image($ruta_imagen_izq, 15, $y_imagenes, $ancho_imagen, 0, '');
}

// Imagen derecha
if (file_exists($ruta_imagen_der)) {
    $x_derecha = 210 - 15 - $ancho_imagen2;
    $pdf->Image($ruta_imagen_der, $x_derecha, $y_imagenes2, $ancho_imagen2, 0, '');
}

// TÍTULO INSTITUCIONAL
$pdf->SetY($y_titulo - 22);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(0, 15, "Unidad Profesional Interdisciplinaria", 0, 1, 'C');

$pdf->SetY($y_titulo - 12);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 15, "de Ingeniería y Ciencias Sociales", 0, 1, 'C');

$pdf->SetY($y_titulo - 2);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 15, "y Administrativas", 0, 1, 'C');

// Línea separadora
$pdf->SetY($y_titulo + 18);
$pdf->SetDrawColor(0, 0, 0);

// Cambia el grosor (por defecto es 0.2 mm)
$pdf->SetLineWidth(0.8); // Prueba con 0.5, 0.8 o 1.0 según qué tan gruesa la quieras

$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(10);

// ===== INFORMACIÓN DEL EXAMEN =====
$pdf->SetY($y_titulo + 23);

// Guardar la posición Y actual
$y_actual = $pdf->GetY();

// Calcular posiciones X exactas
$margen_izquierdo = 15;
$ancho_carrera = 60;
$ancho_tipo = 50;
$ancho_examen = 50;

// Carrera a la izquierda
$pdf->SetXY($margen_izquierdo, $y_actual);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell($ancho_carrera, 8, $carrera, 0, 0, 'L');

// Tipo de examen al centro
$pdf->SetXY($margen_izquierdo + $ancho_carrera + 10, $y_actual); // +10 de espacio
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell($ancho_tipo, 8, $tipoExamen, 0, 0, 'C');

// Examen Tipo a la derecha
$pdf->SetXY($margen_izquierdo + $ancho_carrera + $ancho_tipo + 20, $y_actual); // +20 de espacio
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell($ancho_examen, 8, "Examen Tipo $numeroGrupo", 0, 1, 'R');

// ===== CAMPOS DEL ALUMNO =====
$pdf->Ln(5);

$linea_nombre = str_repeat("_", 35);
$linea_fecha = str_repeat("_", 15);
$linea_secuencia = str_repeat("_", 18);
$linea_boleta = str_repeat("_", 20);

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(0, 8, "Nombre:" . $linea_nombre . "Fecha:" . $linea_fecha, 0, 1, 'L');
$pdf->Cell(0, 8, "Secuencia:" . $linea_secuencia . "No. Boleta:" . $linea_boleta, 0, 1, 'L');

$pdf->Ln(5);
// ===== FIN DE CABECERA =====

// ===== PREGUNTAS =====
$preguntaCount = 1;

foreach ($grupo as $pregunta) {
    // Verificar si hay espacio en la página
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
    }

    // Número de pregunta
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(0,0,0);
    $pdf->Cell(0, 8, "PREGUNTA $preguntaCount:", 0, 1);
    
    // Texto de pregunta
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetTextColor(51, 51, 51);
    $pdf->MultiCell(170, 6, $pregunta['texto'] ?? '');
    $pdf->Ln(2);
    
    // Opciones
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(64, 64, 64);
    foreach ($pregunta['opciones'] ?? [] as $opcion) {
        $pdf->Cell(0, 6, "   " . $opcion, 0, 1);
    }
    /*
    // Respuesta correcta
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(179, 179, 179);
    $pdf->Cell(0, 8, "RESPUESTA CORRECTA: " . ($pregunta['correcta'] ?? ''), 0, 1);
    */
    // Espacio entre preguntas
    $pdf->Ln(3);
    
    $preguntaCount++;
}
// Guardar el examen en la base de datos
try {
    $examen_id = $bd->guardarExamen($numeroGrupo, $tipoExamen, $carrera, $grupo);
    // Opcional: agregar el ID al PDF
    $pdf->SetY($y_titulo + 28);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 5, "ID Examen: $examen_id", 0, 1, 'R');
} catch (Exception $e) {
    error_log("Error al guardar en BD: " . $e->getMessage());
}
// Salida del PDF
ob_clean();
$pdf->Output("grupo_$numeroGrupo.pdf", 'D');
?>