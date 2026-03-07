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

// Función para procesar preguntas
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
        
        if (linea.includes('Tema:')) {
            continue;
        }
        
        if (linea.match(/^\d+[\.\)]/)) {
            if (preguntaActual && preguntaActual.opciones.length > 0) {
                for (let j = i - 1; j < lineas.length && j <= i + 5; j++) {
                    if (j >= 0 && lineas[j] && lineas[j].toLowerCase().includes('respuesta correcta:')) {
                        const match = lineas[j].match(/[a-d]/i);
                        if (match) {
                            preguntaActual.correcta = match[0].toUpperCase();
                        }
                        break;
                    }
                }
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
                const match = linea.match(/[a-d]/i);
                if (match) {
                    preguntaActual.correcta = match[0].toUpperCase();
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
        for (let j = lineas.length - 1; j >= lineas.length - 5 && j >= 0; j--) {
            if (lineas[j] && lineas[j].toLowerCase().includes('respuesta correcta:')) {
                const match = lineas[j].match(/[a-d]/i);
                if (match) {
                    preguntaActual.correcta = match[0].toUpperCase();
                }
                break;
            }
        }
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

// Función para generar PDF de un grupo específico
function generarPDFGrupo(numeroGrupo) {
    if (gruposCompletados.length === 0 || numeroGrupo < 0 || numeroGrupo >= gruposCompletados.length) {
        mostrarError('Grupo no válido para generar PDF.');
        return;
    }

    const grupoSeleccionado = gruposCompletados[numeroGrupo];
    
    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Título - CORREGIDO: eliminados caracteres especiales
        doc.setFontSize(22);
        doc.setTextColor(0, 51, 102);
        doc.text(`GRUPO DE PREGUNTAS #${numeroGrupo + 1}`, 105, 20, { align: 'center' });
        
        // Subtítulo
        doc.setFontSize(12);
        doc.setTextColor(100, 100, 100);
        const fecha = new Date().toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        doc.text(`Generado el: ${fecha}`, 105, 30, { align: 'center' });
        
        // Línea separadora
        doc.setDrawColor(200, 200, 200);
        doc.line(20, 35, 190, 35);
        
        let yPos = 45;
        let preguntaCount = 1;
        
        // Recorrer las 15 preguntas del grupo
        grupoSeleccionado.forEach((pregunta) => {
            // Verificar si necesitamos nueva página
            if (yPos > 250) {
                doc.addPage();
                yPos = 20;
                
                // Encabezado en nuevas páginas
                doc.setFontSize(16);
                doc.setTextColor(0, 51, 102);
                doc.text(`Grupo #${numeroGrupo + 1} (continuación)`, 105, 15, { align: 'center' });
                yPos = 25;
            }
            
            // Número de pregunta con fondo
            doc.setFillColor(240, 240, 255);
            doc.rect(15, yPos - 4, 180, 8, 'F');
            
            doc.setFontSize(12);
            doc.setTextColor(0, 51, 102);
            doc.setFont(undefined, 'bold');
            doc.text(`PREGUNTA ${preguntaCount}:`, 20, yPos);
            yPos += 8;
            
            // Texto de la pregunta
            doc.setFontSize(11);
            doc.setTextColor(0, 0, 0);
            doc.setFont(undefined, 'normal');
            
            const lineasPregunta = doc.splitTextToSize(pregunta.texto, 170);
            doc.text(lineasPregunta, 20, yPos);
            yPos += (lineasPregunta.length * 6) + 2;
            
            // Opciones
            doc.setFontSize(10);
            doc.setTextColor(64, 64, 64);
            pregunta.opciones.forEach((opcion) => {
                doc.text(opcion, 25, yPos);
                yPos += 6;
            });
            
            // Respuesta correcta destacada
            doc.setFillColor(232, 245, 233);
            doc.rect(15, yPos - 2, 180, 8, 'F');
            
            doc.setFontSize(11);
            doc.setTextColor(46, 125, 50);
            doc.setFont(undefined, 'bold');
            doc.text(`✓ RESPUESTA CORRECTA: ${pregunta.correcta}`, 20, yPos);
            yPos += 10;
            
            // Línea separadora entre preguntas
            doc.setDrawColor(220, 220, 220);
            doc.line(20, yPos - 5, 190, yPos - 5);
            yPos += 2;
            
            preguntaCount++;
        });
        
        // Pie de página
        const totalPaginas = doc.internal.getNumberOfPages();
        for (let i = 1; i <= totalPaginas; i++) {
            doc.setPage(i);
            doc.setFontSize(8);
            doc.setTextColor(150, 150, 150);
            doc.text(`Página ${i} de ${totalPaginas}`, 180, 285);
        }
        
        // Guardar el PDF
        doc.save(`grupo_${numeroGrupo + 1}_preguntas.pdf`);
        mostrarExito(`✅ PDF del Grupo #${numeroGrupo + 1} generado correctamente.`);
        
    } catch (error) {
        console.error('Error al generar PDF:', error);
        mostrarError('Error al generar el PDF. Verifica la consola.');
    }
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

    if (preguntasDisponibles.length < 15) {
        mostrarError(`⚠️ No hay suficientes preguntas (${preguntasDisponibles.length}/15). Reinicia el banco.`);
        return;
    }

    const grupoActual = [];
    const copiaDisponibles = [...preguntasDisponibles];
    
    for (let i = 0; i < 15; i++) {
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
    const totalGrupos = Math.floor(bancoPreguntas.length / 15);
    const gruposRestantes = totalGrupos - gruposCompletados.length;
    
    totalBanco.textContent = bancoPreguntas.length;
    preguntasDisponiblesEl.textContent = preguntasDisponibles.length;
    gruposRestantesEl.textContent = gruposRestantes;
    gruposCompletadosEl.textContent = gruposCompletados.length;
    
    const progreso = totalGrupos > 0 ? (gruposCompletados.length / totalGrupos) * 100 : 0;
    progressBar.style.width = `${progreso}%`;
    
    generarBtn.disabled = preguntasDisponibles.length < 15;
}

// Función para actualizar historial con botones de PDF
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
                        <span class="badge">15 preguntas</span>
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

// Función para toggle grupo
function toggleGrupo(index) {
    const elemento = document.getElementById(`grupo-${index}`);
    if (elemento) {
        elemento.classList.toggle('show');
    }
}

// Función para reiniciar grupos
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

// Función para actualizar UI
function actualizarUI() {
    statsCard.style.display = 'block';
    actualizarEstadisticas();
}

// Funciones para mostrar mensajes
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

// Hacer funciones globales
window.procesarPreguntas = procesarPreguntas;
window.limpiarTodo = limpiarTodo;
window.generarNuevoGrupo = generarNuevoGrupo;
window.reiniciarGrupos = reiniciarGrupos;
window.toggleGrupo = toggleGrupo;
window.generarPDFGrupo = generarPDFGrupo;
