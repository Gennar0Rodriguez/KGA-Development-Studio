
document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // ELEMENTOS DEL PANEL PÚBLICO
    // ==========================================

    const comboCategoria = document.getElementById('categoriaEncuesta');
    const buscadorEncuestas = document.getElementById('buscarEncuesta');

    const listaEncuestas = document.getElementById('listaEncuestas');

    const formulario = document.getElementById('formEncuesta');
    const tituloEncuesta = document.getElementById('tituloEncuesta');
    const listaPreguntas = document.getElementById('listaPreguntas');

    const mensaje = document.getElementById('mensajeEncuesta');

    const botonEnviar = formulario.querySelector('button[type="submit"]');
    const botonVolver = document.getElementById('btnVolverEncuestas');

    const panelBusqueda = document.querySelector('.busqueda-encuestas');

    const rutaAPI = '/kgade/KGA-Development-Studio/API';


    // ==========================================
    // VARIABLES
    // ==========================================

    let encuestas = [];

    let encuestaSeleccionada = null;


    // ==========================================
    // INICIALIZACIÓN
    // ==========================================

    cargarEncuestas();


    // ==========================================
    // EVENTOS DE BÚSQUEDA
    // ==========================================

    // Buscar por título.
    buscadorEncuestas.addEventListener('input', () => {
        renderizarEncuestas();
    });


    // Filtrar por categoría.
    comboCategoria.addEventListener('change', () => {
        renderizarEncuestas();
    });


    // Volver a la lista de encuestas.
    botonVolver.addEventListener('click', () => {

        formulario.hidden = true;

        panelBusqueda.hidden = false;

        formulario.reset();

        encuestaSeleccionada = null;

        mensaje.textContent = '';
    });


    // ==========================================
    // CARGAR ENCUESTAS ACTIVAS
    // ==========================================

    async function cargarEncuestas() {

        try {

            const respuesta = await fetch(
                `${rutaAPI}/encuestas/encuestas.php?accion=obtenerActivas`
            );

            encuestas = await leerJSON(respuesta);


            // Cargar las categorías disponibles.
            cargarCategorias();


            // Mostrar todas las encuestas inicialmente.
            renderizarEncuestas();


            if (encuestas.length === 0) {

                listaEncuestas.innerHTML =
                    '<p>Actualmente no hay encuestas habilitadas.</p>';
            }

        } catch (error) {

            console.error('Error al cargar encuestas:', error);

            listaEncuestas.innerHTML =
                '<p>No se pudieron cargar las encuestas.</p>';
        }
    }


    // ==========================================
    // CARGAR CATEGORÍAS
    // ==========================================

    function cargarCategorias() {

        comboCategoria.innerHTML =
            '<option value="">Todas las categorías</option>';

        // Obtener categorías sin repetirlas.
        const categorias = new Map();

        encuestas.forEach(encuesta => {

            categorias.set(
                encuesta.id_categoria,
                encuesta.nombre_cat
            );
        });


        categorias.forEach((nombre, idCategoria) => {

            const opcion = document.createElement('option');

            opcion.value = idCategoria;
            opcion.textContent = nombre;

            comboCategoria.appendChild(opcion);
        });
    }


    // ==========================================
    // FILTRAR Y MOSTRAR ENCUESTAS
    // ==========================================

    function renderizarEncuestas() {

        listaEncuestas.innerHTML = '';

        const textoBusqueda = buscadorEncuestas.value
            .trim()
            .toLowerCase();

        const categoriaSeleccionada = comboCategoria.value;


        // Filtrar por título y categoría.
        const encuestasFiltradas = encuestas.filter(encuesta => {

            const coincideTitulo = encuesta.titulo_enc
                .toLowerCase()
                .includes(textoBusqueda);

            const coincideCategoria = !categoriaSeleccionada ||
                encuesta.id_categoria === categoriaSeleccionada;

            return coincideTitulo && coincideCategoria;
        });


        if (encuestasFiltradas.length === 0) {

            const mensajeVacio = document.createElement('p');

            mensajeVacio.textContent =
                'No se encontraron encuestas con esos criterios.';

            listaEncuestas.appendChild(mensajeVacio);

            return;
        }


        // Mostrar las encuestas encontradas.
        encuestasFiltradas.forEach(encuesta => {

            const tarjeta = document.createElement('article');

            tarjeta.classList.add('encuesta-opcion');


            const informacion = document.createElement('div');

            informacion.classList.add('encuesta-opcion-info');


            const titulo = document.createElement('h4');

            titulo.textContent = encuesta.titulo_enc;


            const categoria = document.createElement('span');

            categoria.textContent = encuesta.nombre_cat;


            const descripcion = document.createElement('p');

            descripcion.textContent = encuesta.descripcion_enc ||
                'Encuesta de opinión del Hospital de Clínicas.';


            informacion.appendChild(titulo);
            informacion.appendChild(categoria);
            informacion.appendChild(descripcion);


            const boton = document.createElement('button');

            boton.type = 'button';
            boton.classList.add('btn-seleccionar-encuesta');

            boton.textContent = 'Responder';

            boton.addEventListener('click', () => {
                seleccionarEncuesta(encuesta);
            });


            tarjeta.appendChild(informacion);
            tarjeta.appendChild(boton);

            listaEncuestas.appendChild(tarjeta);
        });
    }


    // ==========================================
    // SELECCIONAR UNA ENCUESTA
    // ==========================================

    async function seleccionarEncuesta(encuesta) {

        encuestaSeleccionada = encuesta;

        mensaje.textContent = '';

        formulario.reset();


        panelBusqueda.hidden = true;

        formulario.hidden = false;


        tituloEncuesta.innerHTML = '';


        const titulo = document.createElement('h3');

        titulo.textContent = encuesta.titulo_enc;

        tituloEncuesta.appendChild(titulo);


        const categoria = document.createElement('p');

        categoria.textContent = encuesta.nombre_cat;

        tituloEncuesta.appendChild(categoria);


        listaPreguntas.innerHTML =
            '<p>Cargando preguntas...</p>';

        botonEnviar.disabled = true;


        try {

            const parametros = new URLSearchParams({
                accion: 'obtenerPorEncuesta',
                titulo_enc: encuesta.titulo_enc,
                id_categoria: encuesta.id_categoria
            });


            const respuesta = await fetch(
                `${rutaAPI}/preguntas/preguntas.php?${parametros}`
            );

            const preguntas = await leerJSON(respuesta);


            renderizarPreguntas(preguntas);


            if (preguntas.length === 0) {

                mensaje.textContent =
                    'Esta encuesta todavía no tiene preguntas.';

                botonEnviar.disabled = true;

            } else {

                botonEnviar.disabled = false;
            }


        } catch (error) {

            console.error('Error al cargar preguntas:', error);

            listaPreguntas.innerHTML =
                '<p>No se pudieron cargar las preguntas.</p>';

            botonEnviar.disabled = true;
        }
    }


    // ==========================================
    // MOSTRAR PREGUNTAS
    // ==========================================

    function renderizarPreguntas(preguntas) {

        listaPreguntas.innerHTML = '';

        if (preguntas.length === 0) {

            listaPreguntas.innerHTML =
                '<p>Esta encuesta todavía no tiene preguntas.</p>';

            return;
        }


        preguntas.forEach((pregunta, index) => {

            const contenedor = document.createElement('div');

            contenedor.classList.add('pregunta');


            const texto = document.createElement('label');

            texto.textContent =
                `${index + 1}. ${pregunta.nombre_pregunta}`;

            contenedor.appendChild(texto);


            const escala = document.createElement('div');

            escala.classList.add('escala');


            for (let numero = 1; numero <= 5; numero++) {

                const opcion = document.createElement('label');

                const input = document.createElement('input');

                input.type = 'radio';
                input.name = `pregunta_${pregunta.id_pregunta}`;
                input.value = numero;
                input.required = true;


                const circulo = document.createElement('span');

                circulo.textContent = numero;


                opcion.appendChild(input);
                opcion.appendChild(circulo);

                escala.appendChild(opcion);
            }


            contenedor.appendChild(escala);

            listaPreguntas.appendChild(contenedor);
        });
    }


    // ==========================================
    // ENVIAR RESPUESTAS
    // ==========================================

    formulario.addEventListener('submit', async (evento) => {

        evento.preventDefault();


        if (!encuestaSeleccionada) {

            mensaje.textContent = 'Seleccione una encuesta.';

            return;
        }


        const respuestas = [];

        const opcionesSeleccionadas =
            listaPreguntas.querySelectorAll('input[type="radio"]:checked');


        opcionesSeleccionadas.forEach(opcion => {

            respuestas.push({
                id_pregunta: opcion.name.replace('pregunta_', ''),
                valor: Number(opcion.value)
            });

        });


        const cantidadPreguntas =
            listaPreguntas.querySelectorAll('.pregunta').length;


        // Comprobar que todas las preguntas estén respondidas.
        if (respuestas.length !== cantidadPreguntas) {

            mensaje.textContent =
                'Debe responder todas las preguntas antes de enviar.';

            return;
        }


        botonEnviar.disabled = true;

        mensaje.textContent = 'Enviando respuestas...';


        try {

            const respuesta = await fetch(
                `${rutaAPI}/respuestas/respuestas.php?accion=guardar`,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify({
                        titulo_enc: encuestaSeleccionada.titulo_enc,
                        id_categoria: encuestaSeleccionada.id_categoria,
                        respuestas: respuestas
                    })
                }
            );


            const resultado = await leerJSON(respuesta);

            mensaje.textContent = resultado.mensaje;

            formulario.reset();

            botonEnviar.disabled = true;


        } catch (error) {

            console.error('Error al enviar encuesta:', error);

            mensaje.textContent =
                error.message || 'No se pudo enviar la encuesta.';

            botonEnviar.disabled = false;
        }
    });


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
                'El servidor devolvió una respuesta inválida.'
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
