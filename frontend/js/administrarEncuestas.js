document.addEventListener('DOMContentLoaded', () => {
    // Referencias al DOM
    const comboEncuestas = document.getElementById('encuesta');
    const contenedorPreguntas = document.querySelector('.lista-preguntas');
    const contadorPreguntas = document.querySelector('.titulo-preguntas span');
    
    // Botones principales
    const btnAgregarEncuesta = document.querySelector('.btn-agregar-encuesta');
    const btnAgregarPregunta = document.querySelector('.btn-agregar-pregunta');
    const btnGuardarCambios = document.querySelector('.btn-guardar');

    // Referencias de Estadísticas
    const elemPromedioGeneral = document.querySelector('.promedio strong');
    const contenedorResultados = document.querySelector('.resultados');
    const elemTotalRespuestas = document.querySelector('.cantidad-respuestas strong');

    // Cargar encuestas al iniciar la vista
    cargarEncuestas();

    // Event Listeners
    comboEncuestas.addEventListener('change', (e) => {
        const idEncuesta = e.target.value;
        if (idEncuesta) {
            cargarDetalleEncuesta(idEncuesta);
        }
    });

    btnAgregarEncuesta.addEventListener('click', agregarNuevaEncuesta);
    btnAgregarPregunta.addEventListener('click', agregarFilaPregunta);
    btnGuardarCambios.addEventListener('click', guardarCambiosEncuesta);

    // Delegación de eventos para botones dinámicos (Editar y Eliminar preguntas)
    contenedorPreguntas.addEventListener('click', (e) => {
        const btnEliminar = e.target.closest('.btn-eliminar');
        const btnEditar = e.target.closest('.btn-editar');

        if (btnEliminar) {
            eliminarPregunta(btnEliminar.closest('.pregunta'));
        } else if (btnEditar) {
            habilitarEdicionPregunta(btnEditar.closest('.pregunta'));
        }
    });

    /**
     * Obtiene y pobla la lista desplegable de encuestas.
     */
    async function cargarEncuestas() {
        try {
            const respuesta = await fetch('/kgade/KGA-Development-Studio/API/encuestas/encuestas.php?accion=obtenerTodas');
            const encuestas = await respuesta.json();

            comboEncuestas.innerHTML = '';

            if (encuestas.length === 0) {
                comboEncuestas.innerHTML = '<option value="">No hay encuestas disponibles</option>';
                return;
            }

            encuestas.forEach(encuesta => {
                const option = document.createElement('option');
                option.value = encuesta.id_encuesta;
                option.textContent = encuesta.titulo_enc;
                comboEncuestas.appendChild(option);
            });

            // Carga inicial de la primera encuesta
            if (comboEncuestas.value) {
                cargarDetalleEncuesta(comboEncuestas.value);
            }

        } catch (error) {
            console.error('Error al cargar encuestas:', error);
        }
    }

    /**
     * Obtiene las preguntas y estadísticas de la encuesta seleccionada.
     */
    async function cargarDetalleEncuesta(idEncuesta) {
        try {
            // Petición paralela para preguntas y estadísticas apuntando a la carpeta API en mayúscula
            const [resPreguntas, resEstadisticas] = await Promise.all([
                fetch(`/kgade/KGA-Development-Studio/API/preguntas/preguntas.php?accion=obtenerPorEncuesta&id_encuesta=${idEncuesta}`),
                fetch(`/kgade/KGA-Development-Studio/API/respuestas/respuestas.php?accion=obtenerEstadisticas&id_encuesta=${idEncuesta}`)
            ]);

            const preguntas = await resPreguntas.json();
            const estadisticas = await resEstadisticas.json();

            renderizarPreguntas(preguntas);
            renderizarEstadisticas(estadisticas);

        } catch (error) {
            console.error('Error al cargar detalle de la encuesta:', error);
        }
    }

    /**
     * Renderiza el listado de preguntas en el DOM.
     */
    function renderizarPreguntas(preguntas) {
        contenedorPreguntas.innerHTML = '';
        contadorPreguntas.textContent = `${preguntas.length} preguntas`;

        preguntas.forEach((pregunta, index) => {
            const htmlPregunta = document.createElement('div');
            htmlPregunta.classList.add('pregunta');
            htmlPregunta.dataset.idPregunta = pregunta.id_pregunta;

            htmlPregunta.innerHTML = `
                <div class="pregunta-info">
                    <span class="numero-pregunta">Pregunta ${index + 1}</span>
                    <p class="texto-pregunta">${pregunta.nombre_pregunta}</p>
                    <div class="opciones">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>
                </div>
                <div class="acciones">
                    <button class="btn-editar" type="button">
                        <i class="fa-solid fa-pen"></i>
                        Editar
                    </button>
                    <button class="btn-eliminar" type="button">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            contenedorPreguntas.appendChild(htmlPregunta);
        });
    }

    /**
     * Renderiza los promedios y datos en el panel lateral de estadísticas.
     */
    function renderizarEstadisticas(data) {
        elemPromedioGeneral.innerHTML = `${data.promedioGeneral || 0} <small>/ 5</small>`;
        elemTotalRespuestas.textContent = data.totalRespuestas || 0;

        // Limpiar el contenedor de resultados individuales manteniendo el título <h4>
        const tituloResultados = contenedorResultados.querySelector('h4');
        contenedorResultados.innerHTML = '';
        contenedorResultados.appendChild(tituloResultados);

        if (data.promediosPregunta && data.promediosPregunta.length > 0) {
            data.promediosPregunta.forEach((item, index) => {
                const divResultado = document.createElement('div');
                divResultado.classList.add('resultado');
                divResultado.innerHTML = `
                    <span>Pregunta ${index + 1}</span>
                    <strong>${item.promedio} / 5</strong>
                `;
                contenedorResultados.appendChild(divResultado);
            });
        }
    }

    /**
     * Agrega un nuevo elemento de pregunta en la interfaz para ser guardado luego.
     */
    function agregarFilaPregunta() {
        const totalPreguntas = contenedorPreguntas.querySelectorAll('.pregunta').length + 1;
        const divPregunta = document.createElement('div');
        divPregunta.classList.add('pregunta', 'nueva-pregunta');

        divPregunta.innerHTML = `
            <div class="pregunta-info" style="width: 100%;">
                <span class="numero-pregunta">Pregunta ${totalPreguntas}</span>
                <input type="text" class="input-nueva-pregunta" placeholder="Escriba la nueva pregunta aquí..." style="width: 80%; padding: 8px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px;">
                <select class="select-tipo-pregunta">
                    <option value="opcion multiple">Opción Múltiple</option>
                    <option value="texto">Texto Libre</option>
                </select>
            </div>
            <div class="acciones">
                <button class="btn-eliminar" type="button">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        `;

        contenedorPreguntas.appendChild(divPregunta);
        contadorPreguntas.textContent = `${totalPreguntas} preguntas`;
    }

    /**
     * Habilita un campo de entrada (input) para modificar la pregunta seleccionada.
     */
    function habilitarEdicionPregunta(nodoPregunta) {
        const elemTexto = nodoPregunta.querySelector('.texto-pregunta');
        if (!elemTexto) return;

        const textoActual = elemTexto.textContent;
        const input = document.createElement('input');
        input.type = 'text';
        input.value = textoActual;
        input.classList.add('input-editar-pregunta');
        input.style.cssText = 'width: 80%; padding: 6px; margin: 5px 0; border: 1px solid #007bff; border-radius: 4px;';

        elemTexto.replaceWith(input);
        input.focus();

        input.addEventListener('blur', () => {
            const nuevoTexto = input.value.trim() || textoActual;
            const nuevoP = document.createElement('p');
            nuevoP.classList.add('texto-pregunta');
            nuevoP.textContent = nuevoTexto;
            input.replaceWith(nuevoP);
        });
    }

    /**
     * Elimina el bloque de la pregunta del DOM.
     */
    function eliminarPregunta(nodoPregunta) {
        if (confirm('¿Desea quitar esta pregunta de la lista?')) {
            nodoPregunta.remove();
            actualizarNumeracionPreguntas();
        }
    }

    /**
     * Recalcula los números de orden de las preguntas tras eliminar una.
     */
    function actualizarNumeracionPreguntas() {
        const lista = contenedorPreguntas.querySelectorAll('.pregunta');
        contadorPreguntas.textContent = `${lista.length} preguntas`;
        lista.forEach((nodo, index) => {
            const spanNumero = nodo.querySelector('.numero-pregunta');
            if (spanNumero) {
                spanNumero.textContent = `Pregunta ${index + 1}`;
            }
        });
    }

    /**
     * Crea una nueva encuesta mediante prompt básico y recarga el selector.
     */
    async function agregarNuevaEncuesta() {
        const titulo = prompt('Ingrese el título de la nueva encuesta:');
        if (!titulo) return;

        const idCategoria = prompt('Ingrese el ID de la categoría asociada:');
        if (!idCategoria) return;

        const idEncuesta = 'ENC_' + Date.now();

        try {
            const respuesta = await fetch('/kgade/KGA-Development-Studio/API/encuestas/encuestas.php?accion=crear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_encuesta: idEncuesta,
                    id_categoria: idCategoria,
                    titulo_enc: titulo,
                    descripcion_enc: ''
                })
            });
            const resultado = await respuesta.json();
            if (resultado.exito) {
                await cargarEncuestas();
                comboEncuestas.value = idEncuesta;
                cargarDetalleEncuesta(idEncuesta);
            } else {
                alert('No se pudo crear la encuesta.');
            }
        } catch (error) {
            console.error('Error al crear encuesta:', error);
        }
    }

    /**
     * Sincroniza y guarda los cambios de todas las preguntas de la encuesta actual.
     */
    async function guardarCambiosEncuesta() {
        const idEncuestaActual = comboEncuestas.value;
        if (!idEncuestaActual) return;

        const listaNodos = contenedorPreguntas.querySelectorAll('.pregunta');
        const preguntasAEnviar = [];

        listaNodos.forEach(nodo => {
            const idPregunta = nodo.dataset.idPregunta || ('PREG_' + Math.random().toString(36).substr(2, 9));
            
            let texto = '';
            let tipo = 'opcion multiple';

            const elemP = nodo.querySelector('.texto-pregunta');
            const elemInputNuevas = nodo.querySelector('.input-nueva-pregunta');
            const elemTipo = nodo.querySelector('.select-tipo-pregunta');

            if (elemP) {
                texto = elemP.textContent;
            } else if (elemInputNuevas) {
                texto = elemInputNuevas.value;
            }

            if (elemTipo) {
                tipo = elemTipo.value;
            }

            if (texto.trim() !== '') {
                preguntasAEnviar.push({
                    id_pregunta: idPregunta,
                    id_encuesta: idEncuestaActual,
                    nombre_pregunta: texto,
                    tipo: tipo
                });
            }
        });

        try {
            const respuesta = await fetch('/kgade/KGA-Development-Studio/API/preguntas/preguntas.php?accion=guardarLote', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_encuesta: idEncuestaActual,
                    preguntas: preguntasAEnviar
                })
            });

            const resultado = await respuesta.json();
            if (resultado.exito) {
                alert('Cambios guardados con éxito.');
                cargarDetalleEncuesta(idEncuestaActual);
            } else {
                alert('Error al guardar cambios.');
            }
        } catch (error) {
            console.error('Error al guardar preguntas:', error);
        }
    }
});