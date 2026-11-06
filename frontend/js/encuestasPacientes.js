
document.addEventListener('DOMContentLoaded', () => {

    const comboCategoria = document.getElementById('categoriaEncuesta');
    const tituloEncuesta = document.getElementById('tituloEncuesta');
    const listaPreguntas = document.getElementById('listaPreguntas');
    const formulario = document.getElementById('formEncuesta');
    const mensaje = document.getElementById('mensajeEncuesta');

    const botonEnviar = formulario.querySelector('button[type="submit"]');

    const rutaAPI = '/kgade/KGA-Development-Studio/API';

    let encuestas = [];

    // Cargar únicamente las encuestas habilitadas.
    cargarEncuestas();

    // Cuando se selecciona otra categoría, cargar sus preguntas.
    comboCategoria.addEventListener('change', () => {

        const valorSeleccionado = comboCategoria.value;

        if (valorSeleccionado) {
            cargarPreguntas(valorSeleccionado);
        } else {
            tituloEncuesta.innerHTML = '';
            listaPreguntas.innerHTML =
                '<p>Seleccione una categoría para comenzar.</p>';
        }

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

            comboCategoria.innerHTML =
                '<option value="">Seleccione una categoría</option>';

            // Si no existen categorías activas, informar al paciente.
            if (encuestas.length === 0) {

                comboCategoria.innerHTML =
                    '<option value="">No hay encuestas disponibles</option>';

                tituloEncuesta.innerHTML = '';

                listaPreguntas.innerHTML =
                    '<p>Actualmente no hay encuestas habilitadas.</p>';

                botonEnviar.disabled = true;

                return;
            }

            botonEnviar.disabled = false;

            encuestas.forEach(encuesta => {

                const opcion = document.createElement('option');

                // Guardar ambos identificadores.
                opcion.value =
                    `${encuesta.id_encuesta}|${encuesta.id_categoria}`;

                opcion.textContent = encuesta.nombre_cat;

                comboCategoria.appendChild(opcion);

            });

        } catch (error) {

            console.error('Error al cargar encuestas:', error);

            comboCategoria.innerHTML =
                '<option value="">No se pudieron cargar las categorías</option>';

            listaPreguntas.innerHTML =
                '<p>No se pudieron cargar las encuestas.</p>';

            botonEnviar.disabled = true;
        }
    }


    // ==========================================
    // CARGAR PREGUNTAS
    // ==========================================

    async function cargarPreguntas(valorSeleccionado) {

        const [idEncuesta, idCategoria] = valorSeleccionado.split('|');

        const encuesta = encuestas.find(item =>
            item.id_encuesta === idEncuesta &&
            item.id_categoria === idCategoria
        );

        if (!encuesta) return;

        tituloEncuesta.innerHTML = '';

        const titulo = document.createElement('h3');

        titulo.textContent = encuesta.titulo_enc;

        tituloEncuesta.appendChild(titulo);

        listaPreguntas.innerHTML = '<p>Cargando preguntas...</p>';

        mensaje.textContent = '';

        try {

            const respuesta = await fetch(
                `${rutaAPI}/preguntas/preguntas.php?accion=obtenerPorEncuesta&id_encuesta=${encodeURIComponent(idEncuesta)}&id_categoria=${encodeURIComponent(idCategoria)}`
            );

            const preguntas = await leerJSON(respuesta);

            renderizarPreguntas(preguntas);

        } catch (error) {

            console.error('Error al cargar preguntas:', error);

            listaPreguntas.innerHTML =
                '<p>No se pudieron cargar las preguntas.</p>';
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

        const valorSeleccionado = comboCategoria.value;

        if (!valorSeleccionado) {

            mensaje.textContent = 'Seleccione una categoría.';

            return;
        }

        const [idEncuesta, idCategoria] = valorSeleccionado.split('|');

        const respuestas = [];

        const opcionesSeleccionadas =
            listaPreguntas.querySelectorAll('input[type="radio"]:checked');

        opcionesSeleccionadas.forEach(opcion => {

            respuestas.push({
                id_pregunta: opcion.name.replace('pregunta_', ''),
                valor: Number(opcion.value)
            });

        });

        if (respuestas.length === 0) {

            mensaje.textContent =
                'Seleccione al menos una respuesta para enviar la encuesta.';

            return;
        }

        try {

            const respuesta = await fetch(
                `${rutaAPI}/respuestas/respuestas.php?accion=guardar`,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify({
                        id_encuesta: idEncuesta,
                        id_categoria: idCategoria,
                        respuestas: respuestas
                    })
                }
            );

            const resultado = await leerJSON(respuesta);

            mensaje.textContent = resultado.mensaje;

            formulario.reset();

        } catch (error) {

            console.error('Error al enviar encuesta:', error);

            mensaje.textContent =
                error.message || 'No se pudo enviar la encuesta.';
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
