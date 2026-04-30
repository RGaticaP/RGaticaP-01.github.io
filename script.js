window.alert = function() {};
// Variables globales
let bancoPreguntas = [];
let preguntasDisponibles = [];
let gruposCompletados = [];

// Elementos del DOM
const preguntasInput = document.getElementById('preguntasInput');
const statsCard = document.getElementById('statsCard');
const totalBanco = document.getElementById('totalBanco');
const preguntasDisponiblesEl = document.getElementById('preguntasDisponibles');
const gruposRestantesEl = document.getElementById('gruposRestantes');
const gruposCompletadosEl = document.getElementById('gruposCompletados');
const progressBar = document.getElementById('progressBar');
const generarBtn = document.getElementById('generarBtn');
const grupoActualContainer = document.getElementById('grupoActualContainer');
const grupoActualCards = document.getElementById('grupoActualCards');
const gruposList = document.getElementById('gruposList');

function getCantidadPorGrupo() {
    const input = document.getElementById('cantidadPreguntas');
    if (input) {
        let valor = parseInt(input.value);
        if (isNaN(valor)) return 15;
        if (valor < 1) return 1;
        if (valor > 100) return 100;
        return valor;
    }
    return 15;
}

// Función para procesar preguntas (CORREGIDA)
function procesarPreguntas() {
    const texto = preguntasInput.value.trim();
    
    if (!texto) {
        mostrarError('Por favor, ingresa algunas preguntas.');
        return;
    }

    const lineas = texto.split('\n');
    const preguntasProcesadas = [];
    let preguntaActual = null;
    
    for (let i = 0; i < lineas.length; i++) {
        const linea = lineas[i].trim();
        
        if (linea === '') continue;
        if (linea.includes('Tema:')) continue;
        
        if (linea.match(/^\d+[\.\)]/)) {
            if (preguntaActual && preguntaActual.opciones.length > 0) {
                preguntasProcesadas.push(preguntaActual);
            }
            preguntaActual = {
                texto: linea.replace(/^\d+[\.\)]\s*/, ''),
                opciones: [],
                correcta: ''
            };
        }
        else if (linea.match(/^[a-d][\.\)]/i)) {
            if (preguntaActual) {
                preguntaActual.opciones.push(linea);
            }
        }
        else if (linea.toLowerCase().includes('respuesta correcta:')) {
            if (preguntaActual) {
                // CORREGIDO: Buscar la letra después de los dos puntos
                const partes = linea.split(':');
                if (partes.length > 1) {
                    const textoRespuesta = partes[1].trim();
                    const match = textoRespuesta.match(/[a-d]/i);
                    if (match) {
                        preguntaActual.correcta = match[0].toUpperCase();
                        alert("✅ Respuesta capturada: " + preguntaActual.correcta);
                    }
                }
            }
        }
        else if (preguntaActual && linea.length > 0 && !linea.includes('Tema:')) {
            if (preguntaActual.opciones.length === 0) {
                preguntaActual.texto += ' ' + linea;
            }
        }
    }
    
    if (preguntaActual && preguntaActual.opciones.length > 0) {
        preguntasProcesadas.push(preguntaActual);
    }
    
    const preguntasValidas = preguntasProcesadas.filter(p => 
        p.opciones.length >= 2 && p.correcta !== ''
    );
    
    if (preguntasValidas.length === 0) {
        mostrarError('No se pudieron procesar las preguntas. Verifica el formato.');
        return;
    }
    
    bancoPreguntas = preguntasValidas;
    preguntasDisponibles = [...bancoPreguntas];
    gruposCompletados = [];
    
    actualizarUI();
    mostrarExito(`✅ Se procesaron ${preguntasValidas.length} preguntas correctamente.`);
}

// Función para generar PDF
function generarPDFGrupo(numeroGrupo) {
    if (gruposCompletados.length === 0 || numeroGrupo < 0 || numeroGrupo >= gruposCompletados.length) {
        mostrarError('Grupo no válido para generar PDF.');
        return;
    }

    const grupoSeleccionado = gruposCompletados[numeroGrupo];
    const tipoExamen = document.getElementById('tipoExamen').value;
    const carrera = document.getElementById('carrera').value;
    const cantidadPreguntas = getCantidadPorGrupo();
    
    mostrarExito('⏳ Generando PDF...');
    
    fetch('generar_pdf.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            grupo: grupoSeleccionado,
            numeroGrupo: numeroGrupo + 1,
            tipoExamen: tipoExamen,
            carrera: carrera,
            cantidadPreguntas: cantidadPreguntas
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Error en el servidor');
        return response.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `grupo_${numeroGrupo + 1}_preguntas.pdf`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        mostrarExito(`✅ PDF del Grupo #${numeroGrupo + 1} generado correctamente.`);
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error al generar el PDF. Verifica la consola.');
    });
}

// Función para limpiar todo
function limpiarTodo() {
    if (confirm('¿Estás seguro de que quieres limpiar todo?')) {
        preguntasInput.value = '';
        bancoPreguntas = [];
        preguntasDisponibles = [];
        gruposCompletados = [];
        statsCard.style.display = 'none';
        grupoActualContainer.style.display = 'none';
        gruposList.innerHTML = '';
    }
}

// Función para generar nuevo grupo
function generarNuevoGrupo() {
    if (bancoPreguntas.length === 0) {
        mostrarError('Primero procesa algunas preguntas.');
        return;
    }

    const cantidadPorGrupo = getCantidadPorGrupo();
    
    if (preguntasDisponibles.length < cantidadPorGrupo) {
        mostrarError(`⚠️ No hay suficientes preguntas (${preguntasDisponibles.length}/${cantidadPorGrupo}). Reinicia el banco.`);
        return;
    }

    const grupoActual = [];
    const copiaDisponibles = [...preguntasDisponibles];
    
    for (let i = 0; i < cantidadPorGrupo; i++) {
        const indiceAleatorio = Math.floor(Math.random() * copiaDisponibles.length);
        grupoActual.push(copiaDisponibles[indiceAleatorio]);
        copiaDisponibles.splice(indiceAleatorio, 1);
    }

    preguntasDisponibles = copiaDisponibles;
    gruposCompletados.push(grupoActual);
    
    mostrarGrupoActual(grupoActual);
    actualizarEstadisticas();
    actualizarHistorial();
}

// Función para mostrar grupo actual
function mostrarGrupoActual(grupo) {
    grupoActualContainer.style.display = 'block';
    
    let html = '';
    grupo.forEach((pregunta, index) => {
        html += `
            <div class="question-card">
                <div class="question-number">Pregunta #${index + 1}</div>
                <div class="question-text">${pregunta.texto}</div>
                <ul class="options-list">
                    ${pregunta.opciones.map(op => `<li>${op}</li>`).join('')}
                </ul>
                <div class="correct-answer">
                    ✅ Respuesta correcta: ${pregunta.correcta}
                </div>
            </div>
        `;
    });
    
    grupoActualCards.innerHTML = html;
}

// Función para actualizar estadísticas
function actualizarEstadisticas() {
    const cantidadPorGrupo = getCantidadPorGrupo();
    const totalGrupos = Math.floor(bancoPreguntas.length / cantidadPorGrupo);
    const gruposRestantes = totalGrupos - gruposCompletados.length;
    
    totalBanco.textContent = bancoPreguntas.length;
    preguntasDisponiblesEl.textContent = preguntasDisponibles.length;
    gruposRestantesEl.textContent = gruposRestantes;
    gruposCompletadosEl.textContent = gruposCompletados.length;
    
    const progreso = totalGrupos > 0 ? (gruposCompletados.length / totalGrupos) * 100 : 0;
    progressBar.style.width = `${progreso}%`;
    
    generarBtn.disabled = preguntasDisponibles.length < cantidadPorGrupo;
}

// Función para actualizar historial
function actualizarHistorial() {
    let historialHtml = '';
    
    gruposCompletados.forEach((grupo, index) => {
        let preguntasHtml = '';
        grupo.forEach((pregunta, i) => {
            preguntasHtml += `
                <div class="group-question">
                    <p><strong>Pregunta ${i + 1}:</strong> ${pregunta.texto}</p>
                    <p>Opciones: ${pregunta.opciones.join(' | ')}</p>
                    <p><strong>✅ Correcta: ${pregunta.correcta}</strong></p>
                </div>
            `;
        });
        
        historialHtml += `
            <div class="group-item">
                <div class="group-item-header" onclick="toggleGrupo(${index})">
                    <span>📋 Grupo #${index + 1}</span>
                    <div style="display: flex; gap: 10px;">
                        <span class="badge">${grupo.length} preguntas</span>
                        <button class="pdf-btn" onclick="event.stopPropagation(); generarPDFGrupo(${index})" style="background: #dc3545; padding: 3px 10px; font-size: 12px;">
                            📄 PDF
                        </button>
                    </div>
                </div>
                <div class="group-item-questions" id="grupo-${index}">
                    ${preguntasHtml}
                </div>
            </div>
        `;
    });
    
    gruposList.innerHTML = historialHtml || '<p style="color: #999; text-align: center;">Aún no hay grupos generados</p>';
}

function toggleGrupo(index) {
    const elemento = document.getElementById(`grupo-${index}`);
    if (elemento) {
        elemento.classList.toggle('show');
    }
}

function reiniciarGrupos() {
    if (bancoPreguntas.length === 0) {
        mostrarError('No hay preguntas en el banco');
        return;
    }
    
    if (confirm('¿Reiniciar todos los grupos?')) {
        preguntasDisponibles = [...bancoPreguntas];
        gruposCompletados = [];
        grupoActualContainer.style.display = 'none';
        
        actualizarEstadisticas();
        gruposList.innerHTML = '<p style="color: #999; text-align: center;">Reiniciado. Genera un nuevo grupo.</p>';
        mostrarExito('✅ Banco reiniciado correctamente');
    }
}

function actualizarUI() {
    statsCard.style.display = 'block';
    actualizarEstadisticas();
}

function mostrarError(mensaje) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = mensaje;
    const existingError = document.querySelector('.error-message');
    if (existingError) existingError.remove();
    document.querySelector('.card').appendChild(errorDiv);
    setTimeout(() => errorDiv.remove(), 3000);
}

function mostrarExito(mensaje) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = mensaje;
    const existingSuccess = document.querySelector('.success-message');
    if (existingSuccess) existingSuccess.remove();
    document.querySelector('.card').appendChild(successDiv);
    setTimeout(() => successDiv.remove(), 3000);
}

window.procesarPreguntas = procesarPreguntas;
window.limpiarTodo = limpiarTodo;
window.generarNuevoGrupo = generarNuevoGrupo;
window.reiniciarGrupos = reiniciarGrupos;
window.toggleGrupo = toggleGrupo;
window.generarPDFGrupo = generarPDFGrupo;