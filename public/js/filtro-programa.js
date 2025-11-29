/**
 * Filtro dinámico por Programa de Estudio
 * Filtra estudiantes EFSRT por programa seleccionado
 */
function inicializarFiltroProgramaEstudiante() {
    const filtroPrograma = document.getElementById('filtro_programa');
    const selectEstudiante = document.getElementById('id_EFSRT');
    const estudianteCount = document.getElementById('estudiante-count');
    
    if (!filtroPrograma || !selectEstudiante) {
        console.warn('Elementos de filtro no encontrados');
        return;
    }
    
    // Guardar todas las opciones originales
    const todasLasOpcionesEstudiante = Array.from(selectEstudiante.options).slice(1);
    
    filtroPrograma.addEventListener('change', function() {
        const programaSeleccionado = this.value;
        
        // Filtrar estudiantes
        selectEstudiante.innerHTML = '<option value="">Selecciona un estudiante...</option>';
        let estudiantesVisibles = 0;
        
        todasLasOpcionesEstudiante.forEach(option => {
            const programaEstudiante = option.getAttribute('data-programa');
            if (!programaSeleccionado || programaEstudiante === programaSeleccionado) {
                selectEstudiante.appendChild(option.cloneNode(true));
                estudiantesVisibles++;
            }
        });
        
        // Actualizar contador de estudiantes
        if (estudianteCount) {
            if (programaSeleccionado) {
                estudianteCount.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i>${estudiantesVisibles} estudiante(s) disponible(s). `;
            } else {
                estudianteCount.innerHTML = '';
            }
        }
        
        // Mostrar mensaje si no hay resultados
        if (programaSeleccionado && estudiantesVisibles === 0) {
            selectEstudiante.innerHTML += '<option value="" disabled>No hay estudiantes para este programa</option>';
        }
    });
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarFiltroProgramaEstudiante);
} else {
    inicializarFiltroProgramaEstudiante();
}
