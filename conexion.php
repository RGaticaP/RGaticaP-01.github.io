<?php
class ConexionBD {
    private $host = "sql311.infinityfree.com";
    private $usuario = "if0_41435652";
    private $password = "Gatica2005";
    private $base_datos = "if0_41435652_examenes_db";
    private $conexion;

    public function __construct() {
        try {
            $this->conexion = new mysqli(
                $this->host, 
                $this->usuario, 
                $this->password, 
                $this->base_datos
            );
            
            if ($this->conexion->connect_error) {
                throw new Exception("Error de conexión: " . $this->conexion->connect_error);
            }
            
            $this->conexion->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            die("Error al conectar a la base de datos: " . $e->getMessage());
        }
    }

    public function getConexion() {
        return $this->conexion;
    }

    public function cerrar() {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }

    public function guardarExamen($numero_grupo, $tipo_examen_nombre, $carrera_nombre, $preguntas) {
        $tipo_id = $this->obtenerIdTipoExamen($tipo_examen_nombre);
        $carrera_id = $this->obtenerIdCarrera($carrera_nombre);
        
        $stmt = $this->conexion->prepare(
            "INSERT INTO examenes (numero_grupo, tipo_examen_id, carrera_id) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iii", $numero_grupo, $tipo_id, $carrera_id);
        $stmt->execute();
        $examen_id = $stmt->insert_id;
        $stmt->close();

        $orden = 1;
        foreach ($preguntas as $pregunta) {
            $pregunta_id = $this->guardarPregunta($pregunta, $carrera_id);
            
            $stmt = $this->conexion->prepare(
                "INSERT INTO examen_preguntas (examen_id, pregunta_id, orden) VALUES (?, ?, ?)"
            );
            $stmt->bind_param("iii", $examen_id, $pregunta_id, $orden);
            $stmt->execute();
            $stmt->close();
            $orden++;
        }

        return $examen_id;
    }

    private function obtenerIdTipoExamen($nombre) {
        $stmt = $this->conexion->prepare("SELECT id FROM tipos_examen WHERE nombre = ?");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();
        return $fila ? $fila['id'] : null;
    }

    private function obtenerIdCarrera($nombre) {
        $stmt = $this->conexion->prepare("SELECT id FROM carreras WHERE nombre = ?");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();
        return $fila ? $fila['id'] : null;
    }

    private function guardarPregunta($pregunta, $carrera_id) {
        $stmt = $this->conexion->prepare("SELECT id FROM preguntas WHERE texto = ?");
        $stmt->bind_param("s", $pregunta['texto']);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            $stmt->close();
            return $fila['id'];
        }
        $stmt->close();

        $opciones = $this->parsearOpciones($pregunta['opciones']);
        
        // 🔥 CORREGIDO: Asegurar que la respuesta sea una letra minúscula (a, b, c, d)
        $respuesta = strtolower(trim($pregunta['correcta']));
        
        // Si la respuesta es una letra mayúscula o tiene espacios, la limpiamos
        if (strlen($respuesta) > 1) {
            // Si viene algo como "b)" o "b." o "B", extraemos solo la letra
            if (preg_match('/[a-d]/i', $respuesta, $matches)) {
                $respuesta = strtolower($matches[0]);
            } else {
                $respuesta = 'a'; // Valor por defecto si no se reconoce
            }
        }
        
        $stmt = $this->conexion->prepare(
            "INSERT INTO preguntas (texto, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, carrera_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param(
            "ssssssi", 
            $pregunta['texto'],
            $opciones['a'],
            $opciones['b'],
            $opciones['c'],
            $opciones['d'],
            $respuesta,  // 🔥 AHORA GUARDA LA LETRA CORRECTA
            $carrera_id
        );
        
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        
        return $id;
    }

    private function parsearOpciones($opciones_array) {
        $resultado = ['a' => '', 'b' => '', 'c' => '', 'd' => ''];
        
        foreach ($opciones_array as $opcion) {
            if (preg_match('/^([a-d])[\.\)]\s*(.+)$/i', $opcion, $matches)) {
                $letra = strtolower($matches[1]);
                $texto = $matches[2];
                $resultado[$letra] = $texto;
            }
        }
        
        return $resultado;
    }
}
?>