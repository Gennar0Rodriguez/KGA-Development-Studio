// ==========================================
// MODAL PARA CREAR Y EDITAR ENTIDADES
// ==========================================

document.addEventListener('DOMContentLoaded', () => {

    const modal = document.getElementById('modalNuevaEntidad');
    const btnAbrir = document.getElementById('btnAbrirModalEntidad');
    const btnCerrar = document.getElementById('btnCerrarModalEntidad');
    const btnCancelar = document.getElementById('btnCancelarModalEntidad');

    const formulario = document.getElementById('formNuevaEntidad');
    const selector = document.getElementById('tipoEntidad');
    const contenedorTipo = document.getElementById('contenedorTipoEntidad');
    const contenedorCampos = document.getElementById('camposEntidad');

    const mensaje = document.getElementById('mensajeEntidad');
    const btnGuardar = document.getElementById('btnGuardarEntidad');
    const tituloModal = document.getElementById('tituloModalEntidad');

    const RUTA_API = '../../API';

    const RUTAS_API = {
        chofer: `${RUTA_API}/choferes/choferes.php`,
        paciente: `${RUTA_API}/pacientes/paciente.php`,
        destino: `${RUTA_API}/destinos/destinos.php`,
        enfermero: `${RUTA_API}/enfermeros/enfermeros.php`,
        elemento: `${RUTA_API}/elementos/elementos.php`,
        vehiculo: `${RUTA_API}/ambulancias/ambulancias.php`
    };

    // Estado del modal.
    let modoModal = 'crear';
    let entidadEditada = null;
    let registroEditado = null;


    // ==========================================
    // ABRIR Y CERRAR
    // ==========================================

    function abrirModal() {

        modoModal = 'crear';
        entidadEditada = null;
        registroEditado = null;

        formulario.reset();

        selector.disabled = false;
        selector.value = '';

        contenedorTipo.style.display = 'block';
        contenedorCampos.innerHTML = '';

        tituloModal.textContent = 'Añadir entidad';

        btnGuardar.textContent = 'Guardar entidad';
        btnGuardar.disabled = true;

        limpiarMensaje();

        modal.style.display = 'flex';

    }

    function cerrarModal() {

        modal.style.display = 'none';

        formulario.reset();

        selector.disabled = false;
        selector.value = '';

        contenedorTipo.style.display = 'block';
        contenedorCampos.innerHTML = '';

        modoModal = 'crear';
        entidadEditada = null;
        registroEditado = null;

        btnGuardar.disabled = true;

        limpiarMensaje();

    }

    btnAbrir.addEventListener('click', abrirModal);
    btnCerrar.addEventListener('click', cerrarModal);
    btnCancelar.addEventListener('click', cerrarModal);

    modal.addEventListener('click', evento => {

        if (evento.target === modal) {
            cerrarModal();
        }

    });


    // ==========================================
    // MENSAJES
    // ==========================================

    function mostrarMensaje(texto, tipo) {

        mensaje.textContent = texto;
        mensaje.className = `mensaje-entidad ${tipo}`;

    }

    function limpiarMensaje() {

        mensaje.textContent = '';
        mensaje.className = 'mensaje-entidad';

    }


    // ==========================================
    // CREAR CAMPOS
    // ==========================================

    function crearCampo(etiqueta, nombre, tipo = 'text', opciones = {}) {

        const label = document.createElement('label');

        label.htmlFor = `entidad_${nombre}`;
        label.textContent = etiqueta;

        const input = document.createElement('input');

        input.type = tipo;
        input.id = `entidad_${nombre}`;
        input.name = nombre;

        input.required = opciones.required !== false;

        if (opciones.maxlength) {
            input.maxLength = opciones.maxlength;
        }

        if (opciones.placeholder) {
            input.placeholder = opciones.placeholder;
        }

        if (opciones.pattern) {
            input.pattern = opciones.pattern;
        }

        if (opciones.min !== undefined) {
            input.min = opciones.min;
        }

        contenedorCampos.append(label, input);

        return input;

    }


    function crearSelect(etiqueta, nombre, opciones) {

        const label = document.createElement('label');

        label.htmlFor = `entidad_${nombre}`;
        label.textContent = etiqueta;

        const select = document.createElement('select');

        select.id = `entidad_${nombre}`;
        select.name = nombre;
        select.required = true;

        opciones.forEach(opcion => {

            const option = document.createElement('option');

            option.value = opcion.value;
            option.textContent = opcion.texto;

            select.appendChild(option);

        });

        contenedorCampos.append(label, select);

        return select;

    }


    // ==========================================
    // CAMPOS DE ENFERMERO
    // ==========================================

    function mostrarCamposEnfermero() {

        crearCampo('Cédula de identidad', 'ci_enfermero', 'text', {
            maxlength: 8,
            pattern: '[0-9]{8}',
            placeholder: 'Ej: 12345678'
        });

        crearCampo('Nombre', 'nombre_enfermero', 'text', {
            maxlength: 50
        });

        crearCampo('Apellido', 'apellido_enfermero', 'text', {
            maxlength: 50
        });

    }


    // ==========================================
    // CAMPOS DE CHOFER
    // ==========================================

    function mostrarCamposChofer() {

        crearCampo('Cédula de identidad', 'ci_chofer', 'text', {
            maxlength: 8,
            pattern: '[0-9]{8}',
            placeholder: 'Ej: 12345678'
        });

        crearCampo('Nombre', 'nombre_chofer', 'text', {
            maxlength: 50
        });

        crearCampo('Apellido', 'apellido_chofer', 'text', {
            maxlength: 50
        });

        crearCampo('Teléfono', 'telefono', 'tel', {
            maxlength: 50
        });

    }


    // ==========================================
    // CAMPOS DE DESTINO
    // ==========================================

    function mostrarCamposDestino() {

        crearCampo('Nombre del destino', 'nombre_destino', 'text', {
            maxlength: 50
        });

        crearCampo('Dirección', 'direccion', 'text', {
            maxlength: 50
        });

        crearCampo('Ruta', 'ruta', 'text', {
            maxlength: 50
        });

    }


    // ==========================================
    // CARGAR ENFERMEROS
    // ==========================================

    async function crearSelectorEnfermeros() {

        const select = crearSelect('Enfermero responsable', 'ci_enfermero', [
            { value: '', texto: 'Cargando enfermeros...' }
        ]);

        try {

            const respuesta = await fetch(RUTAS_API.enfermero, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const datos = await respuesta.json();

            if (!respuesta.ok || !Array.isArray(datos)) {
                throw new Error('No se pudieron cargar los enfermeros.');
            }

            select.innerHTML = '';

            const opcionInicial = document.createElement('option');

            opcionInicial.value = '';
            opcionInicial.textContent = 'Seleccione un enfermero';

            select.appendChild(opcionInicial);

            datos.forEach(enfermero => {

                const opcion = document.createElement('option');

                opcion.value = enfermero.ci_enfermero;

                opcion.textContent =
                    `${enfermero.nombre_enfermero} ${enfermero.apellido_enfermero} - ${enfermero.ci_enfermero}`;

                select.appendChild(opcion);

            });

            if (datos.length === 0) {
                throw new Error('Primero debe registrar un enfermero.');
            }

            return select;

        } catch (error) {

            mostrarMensaje(error.message, 'error');

            select.disabled = true;

            throw error;

        }

    }


    // ==========================================
    // CAMPOS DE PACIENTE
    // ==========================================

    async function mostrarCamposPaciente() {

        crearCampo('Cédula de identidad', 'ci_paciente', 'text', {
            maxlength: 8,
            pattern: '[0-9]{8}'
        });

        crearCampo('Nombre completo', 'nombre_paciente', 'text', {
            maxlength: 50
        });

        crearSelect('Accesorio necesario', 'accesorio', [
            { value: '', texto: 'Seleccione una opción' },
            { value: 'baston', texto: 'Bastón' },
            { value: 'silla', texto: 'Silla de ruedas' },
            { value: 'otro', texto: 'Otro' }
        ]);

        crearSelect('¿Necesita oxígeno?', 'oxigeno', [
            { value: '', texto: 'Seleccione una opción' },
            { value: '1', texto: 'Sí' },
            { value: '0', texto: 'No' }
        ]);

        crearSelect('¿Requiere aislamiento?', 'aislamiento', [
            { value: '', texto: 'Seleccione una opción' },
            { value: '1', texto: 'Sí' },
            { value: '0', texto: 'No' }
        ]);

        crearCampo('Habitación de retiro', 'habitacion_retiro', 'text', {
            maxlength: 50
        });

        crearCampo('Piso de retiro', 'piso_retiro', 'text', {
            maxlength: 50
        });

        return await crearSelectorEnfermeros();

    }


    // ==========================================
    // CAMPOS DE ELEMENTO
    // ==========================================

    async function mostrarCamposElemento() {

        crearCampo('Nombre del elemento', 'nombre_elemento', 'text', {
            maxlength: 50
        });

        crearCampo('Descripción', 'descripcion_elemento', 'text', {
            maxlength: 50
        });

        crearSelect('Tipo de elemento', 'tipo_elemento', [
            { value: '', texto: 'Seleccione un tipo' },
            { value: 'organo', texto: 'Órgano' },
            { value: 'documentacion', texto: 'Documentación' },
            { value: 'otro', texto: 'Otro' }
        ]);

        crearCampo('Habitación de retiro', 'habitacion_retiro', 'text', {
            maxlength: 50
        });

        crearCampo('Piso de retiro', 'piso_retiro', 'text', {
            maxlength: 50
        });

        return await crearSelectorEnfermeros();

    }


    // ==========================================
    // CAMPOS DE VEHÍCULO
    // ==========================================

    function mostrarCamposVehiculo() {

        crearCampo('Matrícula', 'matricula', 'text', {
            maxlength: 15,
            placeholder: 'Ej: SCA1234'
        });

        crearCampo('Modelo', 'modelo', 'text', {
            maxlength: 50,
            placeholder: 'Ej: Mercedes Sprinter'
        });

        crearCampo('Capacidad', 'capacidad', 'number', {
            min: 1
        });

        crearSelect('Tipo de vehículo', 'tipo_vehiculo', [
            { value: '', texto: 'Seleccione un tipo' },
            { value: 'ambulancia', texto: 'Ambulancia' },
            { value: 'auto', texto: 'Auto' },
            { value: 'otro', texto: 'Otro' }
        ]);

        crearSelect('¿Dispone de camilla?', 'camilla', [
            { value: '', texto: 'Seleccione una opción' },
            { value: '1', texto: 'Sí' },
            { value: '0', texto: 'No' }
        ]);

    }


    // ==========================================
    // GENERAR CAMPOS SEGÚN LA ENTIDAD
    // ==========================================

    async function generarCampos(tipo) {

        contenedorCampos.innerHTML = '';
        limpiarMensaje();

        btnGuardar.disabled = true;

        try {

            switch (tipo) {

                case 'enfermero':
                    mostrarCamposEnfermero();
                    break;

                case 'chofer':
                    mostrarCamposChofer();
                    break;

                case 'destino':
                    mostrarCamposDestino();
                    break;

                case 'paciente':
                    await mostrarCamposPaciente();
                    break;

                case 'elemento':
                    await mostrarCamposElemento();
                    break;

                case 'vehiculo':
                    mostrarCamposVehiculo();
                    break;

                default:
                    return;

            }

            btnGuardar.disabled = false;

        } catch (error) {

            console.error(error);
            btnGuardar.disabled = true;

        }

    }


    selector.addEventListener('change', () => {

        generarCampos(selector.value);

    });


    // ==========================================
    // CARGAR DATOS EN LOS CAMPOS
    // ==========================================

    function cargarDatosEnFormulario(registro) {

        Object.entries(registro).forEach(([campo, valor]) => {

            const input = formulario.elements.namedItem(campo);

            if (!input) {
                return;
            }

            if (valor !== null && valor !== undefined) {
                input.value = String(valor);
            }

        });

        // Los valores de la base pueden venir como 0/1 o booleanos.
        ['oxigeno', 'aislamiento', 'camilla'].forEach(campo => {

            const input = formulario.elements.namedItem(campo);

            if (input && registro[campo] !== null && registro[campo] !== undefined) {
                input.value = Number(registro[campo]) === 1 ? '1' : '0';
            }

        });

        // Las claves principales no se pueden modificar.
        const identificadores = {
            enfermero: 'ci_enfermero',
            chofer: 'ci_chofer',
            paciente: 'ci_paciente',
            elemento: 'id_transportable',
            destino: 'id_destino',
            vehiculo: 'matricula'
        };

        const identificador = identificadores[entidadEditada];

        const campoIdentificador = formulario.elements.namedItem(identificador);

        if (campoIdentificador) {
            campoIdentificador.disabled = true;
        }

    }


    // ==========================================
    // ABRIR MODAL EN MODO EDICIÓN
    // ==========================================

    document.addEventListener('editar-entidad', async evento => {

        const { tipo, registro } = evento.detail;

        modoModal = 'editar';
        entidadEditada = tipo;
        registroEditado = registro;

        formulario.reset();

        selector.value = tipo;
        selector.disabled = true;

        contenedorTipo.style.display = 'none';

        tituloModal.textContent = 'Editar entidad';

        btnGuardar.textContent = 'Guardar cambios';
        btnGuardar.disabled = true;

        limpiarMensaje();

        modal.style.display = 'flex';

        try {

            await generarCampos(tipo);

            cargarDatosEnFormulario(registro);

            // Paciente y elemento necesitan el CI del enfermero.
            // El GET debe incluir ci_enfermero en esos registros.
            if (
                ['paciente', 'elemento'].includes(tipo) &&
                registro.ci_enfermero
            ) {
                const selectEnfermero =
                    formulario.elements.namedItem('ci_enfermero');

                if (selectEnfermero) {
                    selectEnfermero.value = registro.ci_enfermero;
                }
            }

            btnGuardar.disabled = false;

        } catch (error) {

            mostrarMensaje(
                'No se pudieron cargar los datos para editar.',
                'error'
            );

        }

    });


    // ==========================================
    // OBTENER DATOS DEL FORMULARIO
    // ==========================================

    
    function obtenerDatosFormulario() {

        const datosFormulario = new FormData(formulario);
        const datos = {};

        datosFormulario.forEach((valor, clave) => {

            if (clave !== 'tipoEntidad') {
                datos[clave] = valor.trim();
            }

        });

        // Recuperamos los identificadores que no aparecen
        // en FormData porque sus campos están deshabilitados.
        if (modoModal === 'editar') {

            const identificadores = {
                enfermero: 'ci_enfermero',
                chofer: 'ci_chofer',
                paciente: 'id_transportable',
                elemento: 'id_transportable',
                destino: 'id_destino',
                vehiculo: 'matricula'
            };

            const identificador = identificadores[entidadEditada];

            datos[identificador] = String(
                registroEditado[identificador]
            );

            // El controlador de pacientes también necesita la CI
            // original, aunque no se permita modificarla.
            if (entidadEditada === 'paciente') {

                datos.ci_paciente = String(
                    registroEditado.ci_paciente
                );

            }

        }

        return datos;

    }



    // ==========================================
    // GUARDAR O ACTUALIZAR
    // ==========================================

    formulario.addEventListener('submit', async evento => {

        evento.preventDefault();
        limpiarMensaje();

        const tipo = modoModal === 'crear'
            ? selector.value
            : entidadEditada;

        if (!RUTAS_API[tipo]) {
            mostrarMensaje('Seleccione una entidad válida.', 'error');
            return;
        }

        if (!formulario.reportValidity()) {
            return;
        }

        const datos = obtenerDatosFormulario();

        const metodo = modoModal === 'crear' ? 'POST' : 'PUT';

        btnGuardar.disabled = true;
        btnGuardar.textContent =
            modoModal === 'crear' ? 'Guardando...' : 'Actualizando...';

        try {

            const respuesta = await fetch(RUTAS_API[tipo], {
                method: metodo,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(datos)
            });

            const resultado = await respuesta.json();

            if (!respuesta.ok) {
                throw new Error(
                    resultado.mensaje ||
                    resultado.error ||
                    'No se pudo guardar la entidad.'
                );
            }

            mostrarMensaje(
                resultado.mensaje ||
                'Operación realizada correctamente.',
                'exito'
            );

            // Actualizamos la tabla cuando el servidor confirma el cambio.
            document.dispatchEvent(new CustomEvent('entidad-guardada'));

            setTimeout(cerrarModal, 900);

        } catch (error) {

            mostrarMensaje(error.message, 'error');

        } finally {

            btnGuardar.disabled = false;

            btnGuardar.textContent =
                modoModal === 'crear'
                    ? 'Guardar entidad'
                    : 'Guardar cambios';

        }

    });

});