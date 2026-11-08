
document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // ELEMENTOS DEL PANEL DE ENCUESTAS
    // ==========================================

    const comboEncuestas = document.getElementById('encuesta');
    const contenedorPreguntas = document.querySelector('.lista-preguntas');
    const contadorPreguntas = document.querySelector('.titulo-preguntas span');

    const btnAgregarPregunta = document.querySelector('.btn-agregar-pregunta');
    const btnGuardar = document.querySelector('.btn-guardar');

    const elemPromedio = document.querySelector('.promedio strong');
    const contenedorResultados = document.querySelector('.resultados');
    const elemTotalRespuestas = document.querySelector('.cantidad-respuestas strong');

    // Elementos de administración de categorías.
    const btnNuevaCategoria = document.getElementById('btnNuevaCategoria');
    const modalCategoria = document.getElementById('modalCategoria');
    const formCategoria = document.getElementById('formCategoria');
    const inputNombreCategoria = document.getElementById('nombreCategoria');

    const btnCerrarModal = document.getElementById('cerrarModalCategoria');
    const btnCancelarCategoria = document.getElementById('cancelarCategoria');

    const tablaCategorias = document.getElementById('tablaCategorias');
    const contadorCategorias = document.getElementById('contadorCategorias');

    // Elementos de administración de encuestas.
    const btnNuevaEncuesta = document.getElementById('btnNuevaEncuesta');
    const modalEncuesta = document.getElementById('modalEncuesta');
    const formEncuesta = document.getElementById('formEncuesta');

    const comboCategoriaNueva = document.getElementById('categoriaNuevaEncuesta');
    const inputTituloEncuesta = document.getElementById('tituloNuevaEncuesta');
    const inputDescripcionEncuesta = document.getElementById('descripcionNuevaEncuesta');

    const btnCerrarEncuesta = document.getElementById('cerrarModalEncuesta');
    const btnCancelarEncuesta = document.getElementById('cancelarEncuesta');

    const rutaAPI = '/kgade/KGA-Development-Studio/API';


    // ==========================================
    // INICIALIZACIÓN
    // ==========================================

    cargarEncuestas();
    cargarCategorias();


    // ==========================================
    // EVENTOS DE ENCUESTAS
    // ==========================================

    // Cuando se selecciona otra encuesta, cargar su información.
    comboEncuestas.addEventListener('change', () => {

        const seleccion = obtenerSeleccionEncuesta();

        if (seleccion) {

            cargarDetalleEncuesta(
                seleccion.tituloEncuesta,
                seleccion.idCategoria
            );

        } else {

            limpiarDetalleEncuesta();
        }
    });


    // Abrir el modal para crear una encuesta.
    btnNuevaEncuesta.addEventListener('click', async () => {

        formEncuesta.reset();

        await cargarCategoriasSelector();

        modalEncuesta.showModal();

        inputTituloEncuesta.focus();
    });


    // Cerrar el modal de encuesta.
    btnCerrarEncuesta.addEventListener('click', () => {
        modalEncuesta.close();
    });

    btnCancelarEncuesta.addEventListener('click', () => {
        modalEncuesta.close();
    });


    // Crear una encuesta.
    formEncuesta.addEventListener('submit', crearEncuesta);


    // Agregar una pregunta nueva.
    btnAgregarPregunta.addEventListener('click', agregarPregunta);


    // Guardar las modificaciones.
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
    // EVENTOS DE CATEGORÍAS
    // ==========================================

    // Abrir el modal.
    btnNuevaCategoria.addEventListener('click', () => {

        formCategoria.reset();

        modalCategoria.showModal();

        inputNombreCategoria.focus();
    });


    // Cerrar el modal.
    btnCerrarModal.addEventListener('click', () => {
        modalCategoria.close();
    });

    btnCancelarCategoria.addEventListener('click', () => {
        modalCategoria.close();
    });


    // Crear una categoría.
    formCategoria.addEventListener('submit', crearCategoria);


    // Cambiar el estado de una categoría.
    tablaCategorias.addEventListener('click', (evento) => {

        const boton = evento.target.closest('.btn-cambiar-estado');

        if (!boton) return;

        cambiarEstadoCategoria(
            boton.dataset.id,
            boton.dataset.estado
        );
    });


    // ==========================================
    // OBTENER SELECCIÓN DE ENCUESTA
    // ==========================================

    // Utilizamos JSON para guardar ambos identificadores.
    // Así evitamos problemas si el título contiene un separador.

    function obtenerSeleccionEncuesta() {

        if (!comboEncuestas.value) {
            return null;
        }

        try {

            const [tituloEncuesta, idCategoria] =
                JSON.parse(comboEncuestas.value);

            return {
                tituloEncuesta,
                idCategoria
            };

        } catch (error) {

            return null;
        }
    }


    // ==========================================
    // CARGAR ENCUESTAS
    // ==========================================

    async function cargarEncuestas(valorSeleccionado = '') {

        try {

            const respuesta = await fetch(
                `${rutaAPI}/encuestas/encuestas.php?accion=obtenerTodas`
            );

            const encuestas = await leerJSON(respuesta);

            comboEncuestas.innerHTML = '';

            encuestas.forEach(encuesta => {

                const opcion = document.createElement('option');

                opcion.value = JSON.stringify([
                    encuesta.titulo_enc,
                    encuesta.id_categoria
                ]);

                // Mostrar título y categoría.
                opcion.textContent = encuesta.estado_cat === 'deshabilitada'
                    ? `${encuesta.titulo_enc} - ${encuesta.nombre_cat} (Deshabilitada)`
                    : `${encuesta.titulo_enc} - ${encuesta.nombre_cat}`;

                comboEncuestas.appendChild(opcion);
            });


            if (encuestas.length === 0) {

                comboEncuestas.innerHTML =
                    '<option value="">No hay encuestas disponibles</option>';

                limpiarDetalleEncuesta();

                return;
            }


            // Mantener seleccionada la encuesta indicada, si existe.
            if (
                valorSeleccionado &&
                [...comboEncuestas.options].some(
                    opcion => opcion.value === valorSeleccionado
                )
            ) {

                comboEncuestas.value = valorSeleccionado;
            }


            // Cargar la encuesta seleccionada.
            const seleccion = obtenerSeleccionEncuesta();

            if (seleccion) {

                await cargarDetalleEncuesta(
                    seleccion.tituloEncuesta,
                    seleccion.idCategoria
                );
            }

        } catch (error) {

            console.error('Error al cargar encuestas:', error);

            alert(error.message || 'No se pudieron cargar las encuestas.');
        }
    }


    // ==========================================
    // CARGAR CATEGORÍAS
    // ==========================================

    async function cargarCategorias() {

        try {

            const respuesta = await fetch(
                `${rutaAPI}/encuestas/encuestas.php?accion=obtenerCategorias`
            );

            const categorias = await leerJSON(respuesta);

            tablaCategorias.innerHTML = '';

            const cantidadActivas = categorias.filter(
                categoria => categoria.estado_cat === 'activa'
            ).length;

            contadorCategorias.textContent =
                `${cantidadActivas} de 6 activas`;


            if (categorias.length === 0) {

                tablaCategorias.innerHTML = `
                    <tr>
                        <td colspan="3">
                            Todavía no hay categorías creadas.
                        </td>
                    </tr>
                `;

                return;
            }


            categorias.forEach(categoria => {

                const fila = document.createElement('tr');

                // Nombre de la categoría.
                const celdaNombre = document.createElement('td');

                celdaNombre.textContent = categoria.nombre_cat;


                // Estado actual.
                const celdaEstado = document.createElement('td');

                const etiquetaEstado = document.createElement('span');

                etiquetaEstado.classList.add('estado-categoria');

                if (categoria.estado_cat === 'activa') {

                    etiquetaEstado.classList.add('estado-activa');
                    etiquetaEstado.textContent = 'Activa';

                } else {

                    etiquetaEstado.classList.add('estado-deshabilitada');
                    etiquetaEstado.textContent = 'Deshabilitada';
                }

                celdaEstado.appendChild(etiquetaEstado);


                // Botón para modificar el estado.
                const celdaAccion = document.createElement('td');

                const boton = document.createElement('button');

                boton.type = 'button';
                boton.classList.add('btn-cambiar-estado');

                boton.dataset.id = categoria.id_categoria;

                if (categoria.estado_cat === 'activa') {

                    boton.textContent = 'Deshabilitar';
                    boton.dataset.estado = 'deshabilitada';
                    boton.classList.add('btn-deshabilitar');

                } else {

                    boton.textContent = 'Habilitar';
                    boton.dataset.estado = 'activa';
                    boton.classList.add('btn-habilitar');
                }

                celdaAccion.appendChild(boton);


                fila.appendChild(celdaNombre);
                fila.appendChild(celdaEstado);
                fila.appendChild(celdaAccion);

                tablaCategorias.appendChild(fila);
            });


        } catch (error) {

            console.error('Error al cargar categorías:', error);

            tablaCategorias.innerHTML = `
                <tr>
                    <td colspan="3">
                        No se pudieron cargar las categorías.
                    </td>
                </tr>
            `;
        }
    }


    // ==========================================
    // CARGAR CATEGORÍAS EN EL MODAL DE ENCUESTAS
    // ==========================================

    async function cargarCategoriasSelector() {

        try {

            const respuesta = await fetch(
                `${rutaAPI}/encuestas/encuestas.php?accion=obtenerCategorias`
            );

            const categorias = await leerJSON(respuesta);

            comboCategoriaNueva.innerHTML = '';

            // Solamente permitimos crear encuestas en categorías activas.
            const categoriasActivas = categorias.filter(
                categoria => categoria.estado_cat === 'activa'
            );


            if (categoriasActivas.length === 0) {

                comboCategoriaNueva.innerHTML =
                    '<option value="">No hay categorías activas</option>';

                btnNuevaEncuesta.disabled = true;

                return;
            }

            btnNuevaEncuesta.disabled = false;


            const opcionInicial = document.createElement('option');

            opcionInicial.value = '';
            opcionInicial.textContent = 'Seleccione una categoría';
            opcionInicial.disabled = true;
            opcionInicial.selected = true;

            comboCategoriaNueva.appendChild(opcionInicial);


            categoriasActivas.forEach(categoria => {

                const opcion = document.createElement('option');

                opcion.value = categoria.id_categoria;
                opcion.textContent = categoria.nombre_cat;

                comboCategoriaNueva.appendChild(opcion);
            });


        } catch (error) {

            console.error('Error al cargar categorías del modal:', error);

            comboCategoriaNueva.innerHTML =
                '<option value="">No se pudieron cargar las categorías</option>';

            throw error;
        }
    }


    // ==========================================
    // CREAR CATEGORÍA
    // ==========================================

    async function crearCategoria(evento) {

        evento.preventDefault();

        const nombre = inputNombreCategoria.value.trim();

        if (nombre === '') {

            alert('Ingresá un nombre para la categoría.');

            inputNombreCategoria.focus();

            return;
        }


        try {

            const respuesta = await fetch(
                `${rutaAPI}/encuestas/encuestas.php?accion=crearCategoria`,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify({
                        nombre_cat: nombre
                    })
                }
            );

            const resultado = await leerJSON(respuesta);


            modalCategoria.close();

            formCategoria.reset();

            alert(resultado.mensaje);


            // Actualizar la tabla de categorías.
            await cargarCategorias();


            // Actualizar las categorías del modal de encuestas.
            await cargarCategoriasSelector();


        } catch (error) {

            console.error('Error al crear categoría:', error);

            alert(error.message || 'No se pudo crear la categoría.');
        }
    }


    // ==========================================
    // CREAR ENCUESTA
    // ==========================================

    async function crearEncuesta(evento) {

        evento.preventDefault();

        const idCategoria = comboCategoriaNueva.value;
        const tituloEncuesta = inputTituloEncuesta.value.trim();
        const descripcion = inputDescripcionEncuesta.value.trim();


        if (!idCategoria || tituloEncuesta === '') {

            alert('Seleccioná una categoría e ingresá un título.');

            return;
        }


        try {

            const respuesta = await fetch(
                `${rutaAPI}/encuestas/encuestas.php?accion=crearEncuesta`,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify({
                        titulo_enc: tituloEncuesta,
                        id_categoria: idCategoria,
                        descripcion_enc: descripcion
                    })
                }
            );

            const resultado = await leerJSON(respuesta);


            modalEncuesta.close();

            formEncuesta.reset();

            alert(resultado.mensaje);


            // Recargar las encuestas y seleccionar la nueva.
            const nuevoValor = JSON.stringify([
                resultado.titulo_enc,
                resultado.id_categoria
            ]);

            await cargarEncuestas(nuevoValor);


        } catch (error) {

            console.error('Error al crear encuesta:', error);

            alert(error.message || 'No se pudo crear la encuesta.');
        }
    }


    // ==========================================
    // CAMBIAR ESTADO DE UNA CATEGORÍA
    // ==========================================

    async function cambiarEstadoCategoria(idCategoria, nuevoEstado) {

        const accion = nuevoEstado === 'activa'
            ? 'habilitar'
            : 'deshabilitar';

        if (!confirm(`¿Querés ${accion} esta categoría?`)) {
            return;
        }


        try {

            const respuesta = await fetch(
                `${rutaAPI}/encuestas/encuestas.php?accion=cambiarEstado`,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify({
                        id_categoria: idCategoria,
                        estado_cat: nuevoEstado
                    })
                }
            );

            const resultado = await leerJSON(respuesta);

            alert(resultado.mensaje);


            await cargarCategorias();

            // Actualizar también los selectores.
            await cargarCategoriasSelector();

            const valorActual = comboEncuestas.value;

            await cargarEncuestas(valorActual);


        } catch (error) {

            console.error('Error al cambiar el estado:', error);

            alert(error.message || 'No se pudo cambiar el estado.');
        }
    }


    // ==========================================
    // CARGAR PREGUNTAS Y ESTADÍSTICAS
    // ==========================================

    async function cargarDetalleEncuesta(tituloEncuesta, idCategoria) {

        try {

            const parametros = new URLSearchParams({
                accion: 'obtenerPorEncuesta',
                titulo_enc: tituloEncuesta,
                id_categoria: idCategoria
            });


            const respuestaPreguntas = await fetch(
                `${rutaAPI}/preguntas/preguntas.php?${parametros}`
            );

            const preguntas = await leerJSON(respuestaPreguntas);

            renderizarPreguntas(preguntas);


            const parametrosEstadisticas = new URLSearchParams({
                accion: 'obtenerEstadisticas',
                titulo_enc: tituloEncuesta,
                id_categoria: idCategoria
            });


            const respuestaEstadisticas = await fetch(
                `${rutaAPI}/respuestas/respuestas.php?${parametrosEstadisticas}`
            );

            const estadisticas = await leerJSON(respuestaEstadisticas);

            renderizarEstadisticas(estadisticas);


        } catch (error) {

            console.error('Error al cargar la encuesta:', error);

            alert('No se pudo cargar la información de la encuesta.');
        }
    }


    // ==========================================
    // LIMPIAR EL DETALLE DE LA ENCUESTA
    // ==========================================

    function limpiarDetalleEncuesta() {

        contenedorPreguntas.innerHTML = '';

        contadorPreguntas.textContent = '0 preguntas';

        elemPromedio.innerHTML = '0.0 <small>/ 5</small>';

        elemTotalRespuestas.textContent = '0';

        const titulo = contenedorResultados.querySelector('h4');

        contenedorResultados.innerHTML = '';

        if (titulo) {
            contenedorResultados.appendChild(titulo);
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

        // Conservamos el promedio general del panel actual.
        elemPromedio.innerHTML =
            `${datos.promedioGeneral || 0} <small>/ 5</small>`;

        elemTotalRespuestas.textContent =
            datos.totalRespuestas || 0;


        const titulo = contenedorResultados.querySelector('h4');

        contenedorResultados.innerHTML = '';

        if (titulo) {
            contenedorResultados.appendChild(titulo);
        }


        // Si la encuesta todavía no tiene preguntas.
        if (
            !datos.promediosPregunta ||
            datos.promediosPregunta.length === 0
        ) {

            const mensaje = document.createElement('p');

            mensaje.textContent = 'Esta encuesta todavía no tiene preguntas.';

            contenedorResultados.appendChild(mensaje);

            return;
        }


        // Mostrar el resultado individual de cada pregunta.
        datos.promediosPregunta.forEach(item => {

            const resultado = document.createElement('div');

            resultado.classList.add('resultado');


            // Texto real de la pregunta.
            const nombre = document.createElement('span');

            nombre.textContent = item.nombre_pregunta;


            // Promedio individual.
            const promedio = document.createElement('strong');

            if (
                item.promedio === null ||
                Number(item.cantidad_respuestas) === 0
            ) {

                promedio.textContent = 'Sin respuestas';

            } else {

                promedio.textContent = `${item.promedio} / 5`;
            }


            // Cantidad de respuestas utilizadas para ese promedio.
            const cantidad = document.createElement('small');

            cantidad.textContent =
                `${item.cantidad_respuestas} respuestas`;


            resultado.appendChild(nombre);
            resultado.appendChild(promedio);
            resultado.appendChild(cantidad);

            contenedorResultados.appendChild(resultado);
        });
    }


    // ==========================================
    // AGREGAR PREGUNTA
    // ==========================================

    function agregarPregunta() {

        if (!comboEncuestas.value) {

            alert('Primero seleccioná una encuesta.');

            return;
        }


        const fila = document.createElement('div');

        fila.classList.add('pregunta');

        // Las preguntas nuevas todavía no tienen identificador de BD.
        fila.dataset.idPregunta = '';


        const informacion = document.createElement('div');

        informacion.classList.add('pregunta-info');


        const numero = document.createElement('span');

        numero.classList.add('numero-pregunta');
        numero.textContent = 'Nueva pregunta';


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

        const seleccion = obtenerSeleccionEncuesta();

        if (!seleccion) {

            alert('Seleccione una encuesta.');

            return;
        }


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


            // Generar un ID para las preguntas nuevas.
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
                        titulo_enc: seleccion.tituloEncuesta,
                        id_categoria: seleccion.idCategoria,
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
            await cargarDetalleEncuesta(
                seleccion.tituloEncuesta,
                seleccion.idCategoria
            );


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
