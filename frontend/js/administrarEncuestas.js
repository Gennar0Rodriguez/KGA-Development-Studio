document.addEventListener('DOMContentLoaded', () => {

    const comboEncuestas = document.getElementById('encuesta');
    const contenedorPreguntas = document.querySelector('.lista-preguntas');
    const contadorPreguntas = document.querySelector('.titulo-preguntas span');

    const btnAgregarPregunta = document.querySelector('.btn-agregar-pregunta');
    const btnGuardar = document.querySelector('.btn-guardar');

    const elemPromedio = document.querySelector('.promedio strong');
    const contenedorResultados = document.querySelector('.resultados');
    const elemTotalRespuestas = document.querySelector('.cantidad-respuestas strong');

    const rutaAPI = '/kgade/KGA-Development-Studio/API';

    // Cargar las encuestas al entrar en la página.
    cargarEncuestas();

    // Cuando se selecciona otra categoría, cargar sus preguntas.
    comboEncuestas.addEventListener('change', () => {

        const [idEncuesta, idCategoria] = comboEncuestas.value.split('|');

        if (idEncuesta && idCategoria) {
            cargarDetalleEncuesta(idEncuesta, idCategoria);
        }

    });

    // Agregar una pregunta nueva.
    btnAgregarPregunta.addEventListener('click', agregarPregunta);

    // Guardar todas las modificaciones.
    btnGuardar.addEventListener('click', guardarCambios);

    // Eliminar preguntas mediante delegación de eventos.
    contenedorPreguntas.addEventListener('click', (evento) => {

        const botonEliminar = evento.target.closest('.btn-eliminar');

        if (botonEliminar) {

            const pregunta = botonEliminar.closest('.pregunta');

            eliminarPregunta(pregunta);
        }

    });

    // ==========================================
    // CARGAR ENCUESTAS
    // ==========================================

    async function cargarEncuestas() {

        try {

            const respuesta = await fetch(
                `${rutaAPI}/encuestas/encuestas.php?accion=obtenerTodas`
            );

            const encuestas = await leerJSON(respuesta);

            comboEncuestas.innerHTML = '';

            encuestas.forEach(encuesta => {

                const opcion = document.createElement('option');

                opcion.value =
                    `${encuesta.id_encuesta}|${encuesta.id_categoria}`;

                opcion.textContent = encuesta.nombre_cat;

                comboEncuestas.appendChild(opcion);

            });

            if (encuestas.length === 0) {

                comboEncuestas.innerHTML =
                    '<option value="">No hay encuestas disponibles</option>';

                return;
            }

            // Seleccionar la primera encuesta automáticamente.
            const [idEncuesta, idCategoria] = comboEncuestas.value.split('|');

            cargarDetalleEncuesta(idEncuesta, idCategoria);

        } catch (error) {

            console.error('Error al cargar encuestas:', error);

            alert('No se pudieron cargar las encuestas.');
        }
    }

    // ==========================================
    // CARGAR PREGUNTAS Y ESTADÍSTICAS
    // ==========================================

    async function cargarDetalleEncuesta(idEncuesta, idCategoria) {

        try {

            const respuestaPreguntas = await fetch(
                `${rutaAPI}/preguntas/preguntas.php?accion=obtenerPorEncuesta&id_encuesta=${encodeURIComponent(idEncuesta)}&id_categoria=${encodeURIComponent(idCategoria)}`
            );

            const preguntas = await leerJSON(respuestaPreguntas);

            renderizarPreguntas(preguntas);

            const respuestaEstadisticas = await fetch(
                `${rutaAPI}/respuestas/respuestas.php?accion=obtenerEstadisticas&id_encuesta=${encodeURIComponent(idEncuesta)}&id_categoria=${encodeURIComponent(idCategoria)}`
            );

            const estadisticas = await leerJSON(respuestaEstadisticas);

            renderizarEstadisticas(estadisticas);

        } catch (error) {

            console.error('Error al cargar la encuesta:', error);

            alert('No se pudo cargar la información de la encuesta.');
        }
    }

    // ==========================================
    // MOSTRAR PREGUNTAS
    // ==========================================

    function renderizarPreguntas(preguntas) {

        contenedorPreguntas.innerHTML = '';

        contadorPreguntas.textContent =
            `${preguntas.length} preguntas`;

        preguntas.forEach((pregunta, index) => {

            const fila = document.createElement('div');

            fila.classList.add('pregunta');

            fila.dataset.idPregunta = pregunta.id_pregunta;

            const informacion = document.createElement('div');

            informacion.classList.add('pregunta-info');

            const numero = document.createElement('span');

            numero.classList.add('numero-pregunta');
            numero.textContent = `Pregunta ${index + 1}`;

            const input = document.createElement('input');

            input.type = 'text';
            input.classList.add('input-pregunta');
            input.value = pregunta.nombre_pregunta;
            input.maxLength = 50;

            input.style.cssText =
                'width:100%; padding:10px; margin:8px 0; border:1px solid #d5dce5; border-radius:8px;';

            const escala = document.createElement('div');

            escala.classList.add('opciones');

            for (let numero = 1; numero <= 5; numero++) {

                const opcion = document.createElement('span');

                opcion.textContent = numero;

                escala.appendChild(opcion);
            }

            informacion.appendChild(numero);
            informacion.appendChild(input);
            informacion.appendChild(escala);

            const acciones = document.createElement('div');

            acciones.classList.add('acciones');

            const botonEliminar = document.createElement('button');

            botonEliminar.type = 'button';
            botonEliminar.classList.add('btn-eliminar');
            botonEliminar.innerHTML =
                '<i class="fa-solid fa-trash"></i>';

            acciones.appendChild(botonEliminar);

            fila.appendChild(informacion);
            fila.appendChild(acciones);

            contenedorPreguntas.appendChild(fila);

        });
    }

    // ==========================================
    // MOSTRAR ESTADÍSTICAS
    // ==========================================

    function renderizarEstadisticas(datos) {

        elemPromedio.innerHTML =
            `${datos.promedioGeneral || 0} <small>/ 5</small>`;

        elemTotalRespuestas.textContent =
            datos.totalRespuestas || 0;

        const titulo = contenedorResultados.querySelector('h4');

        contenedorResultados.innerHTML = '';

        contenedorResultados.appendChild(titulo);

        if (
            datos.promediosPregunta &&
            datos.promediosPregunta.length > 0
        ) {

            datos.promediosPregunta.forEach((item, index) => {

                const resultado = document.createElement('div');

                resultado.classList.add('resultado');

                const nombre = document.createElement('span');

                nombre.textContent = `Pregunta ${index + 1}`;

                const promedio = document.createElement('strong');

                promedio.textContent = `${item.promedio} / 5`;

                resultado.appendChild(nombre);
                resultado.appendChild(promedio);

                contenedorResultados.appendChild(resultado);

            });
        }
    }

    // ==========================================
    // AGREGAR PREGUNTA
    // ==========================================

    function agregarPregunta() {

        const fila = document.createElement('div');

        fila.classList.add('pregunta');

        // Las preguntas nuevas todavía no tienen identificador de BD.
        fila.dataset.idPregunta = '';

        const informacion = document.createElement('div');

        informacion.classList.add('pregunta-info');

        const numero = document.createElement('span');

        numero.classList.add('numero-pregunta');

        const input = document.createElement('input');

        input.type = 'text';
        input.classList.add('input-pregunta');
        input.placeholder = 'Escriba la nueva pregunta...';
        input.maxLength = 50;

        input.style.cssText =
            'width:100%; padding:10px; margin:8px 0; border:1px solid #d5dce5; border-radius:8px;';

        const escala = document.createElement('div');

        escala.classList.add('opciones');

        for (let numero = 1; numero <= 5; numero++) {

            const opcion = document.createElement('span');

            opcion.textContent = numero;

            escala.appendChild(opcion);
        }

        informacion.appendChild(numero);
        informacion.appendChild(input);
        informacion.appendChild(escala);

        const acciones = document.createElement('div');

        acciones.classList.add('acciones');

        const botonEliminar = document.createElement('button');

        botonEliminar.type = 'button';
        botonEliminar.classList.add('btn-eliminar');
        botonEliminar.innerHTML =
            '<i class="fa-solid fa-trash"></i>';

        acciones.appendChild(botonEliminar);

        fila.appendChild(informacion);
        fila.appendChild(acciones);

        contenedorPreguntas.appendChild(fila);

        actualizarNumeracion();

        input.focus();
    }

    // ==========================================
    // ELIMINAR PREGUNTA
    // ==========================================

    function eliminarPregunta(fila) {

        if (!confirm('¿Desea eliminar esta pregunta?')) {
            return;
        }

        fila.remove();

        actualizarNumeracion();
    }

    // ==========================================
    // ACTUALIZAR NUMERACIÓN
    // ==========================================

    function actualizarNumeracion() {

        const preguntas =
            contenedorPreguntas.querySelectorAll('.pregunta');

        contadorPreguntas.textContent =
            `${preguntas.length} preguntas`;

        preguntas.forEach((pregunta, index) => {

            const numero = pregunta.querySelector('.numero-pregunta');

            numero.textContent = `Pregunta ${index + 1}`;

        });
    }

    // ==========================================
    // GUARDAR CAMBIOS
    // ==========================================

    async function guardarCambios() {

        const valorSeleccionado = comboEncuestas.value;

        if (!valorSeleccionado) {
            alert('Seleccione una encuesta.');
            return;
        }

        const [idEncuesta, idCategoria] = valorSeleccionado.split('|');

        const filas =
            contenedorPreguntas.querySelectorAll('.pregunta');

        const preguntas = [];

        for (const fila of filas) {

            const input = fila.querySelector('.input-pregunta');

            const texto = input.value.trim();

            if (texto === '') {

                alert('No puede haber preguntas vacías.');

                input.focus();

                return;
            }

            // Si es una pregunta nueva, generamos un ID.
            const idPregunta = fila.dataset.idPregunta ||
                'PRG' + Date.now() +
                Math.random().toString(36).substring(2, 7);

            preguntas.push({

                id_pregunta: idPregunta,

                nombre_pregunta: texto

            });
        }

        try {

            const respuesta = await fetch(
                `${rutaAPI}/preguntas/preguntas.php?accion=guardarLote`,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify({

                        id_encuesta: idEncuesta,

                        id_categoria: idCategoria,

                        preguntas: preguntas

                    })
                }
            );

            const resultado = await leerJSON(respuesta);

            if (!resultado.exito) {

                alert(resultado.mensaje || 'No se pudieron guardar los cambios.');

                return;
            }

            alert('Las preguntas se guardaron correctamente.');

            // Volver a cargar las preguntas desde MySQL.
            cargarDetalleEncuesta(idEncuesta, idCategoria);

        } catch (error) {

            console.error('Error al guardar preguntas:', error);

            alert(error.message || 'Ocurrió un error al guardar los cambios.');
        }
    }

    // ==========================================
    // LEER RESPUESTAS JSON
    // ==========================================

    async function leerJSON(respuesta) {

        const contenido = await respuesta.text();

        let datos;

        try {

            datos = JSON.parse(contenido);

        } catch (error) {

            throw new Error(
                `El servidor devolvió una respuesta inválida. HTTP ${respuesta.status}`
            );
        }

        if (!respuesta.ok) {

            throw new Error(
                datos.error || datos.mensaje || 'Error del servidor.'
            );
        }

        return datos;
    }

});