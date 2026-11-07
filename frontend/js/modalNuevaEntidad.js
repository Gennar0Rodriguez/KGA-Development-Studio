
document.addEventListener("DOMContentLoaded", () => {

    // ==========================================
    // ELEMENTOS DEL DOM
    // ==========================================

    const modal = document.getElementById("modalNuevaEntidad");
    const btnAbrir = document.getElementById("btnAbrirModalEntidad");
    const btnCerrar = document.getElementById("btnCerrarModalEntidad");
    const btnCancelar = document.getElementById("btnCancelarModalEntidad");

    const formulario = document.getElementById("formNuevaEntidad");
    const selector = document.getElementById("tipoEntidad");
    const contenedorCampos = document.getElementById("camposEntidad");
    const mensaje = document.getElementById("mensajeEntidad");
    const btnGuardar = document.getElementById("btnGuardarEntidad");

    // Rutas de los controladores PHP.
    // Se mantiene la misma ruta base utilizada por el proyecto.

    const RUTAS_API = {
        chofer: "/kgade/KGA-Development-Studio/API/choferes/choferes.php",
        paciente: "/kgade/KGA-Development-Studio/API/pacientes/paciente.php",
        destino: "/kgade/KGA-Development-Studio/API/destinos/destinos.php",
        enfermero: "/kgade/KGA-Development-Studio/API/enfermeros/enfermeros.php"
    };

    // ==========================================
    // ABRIR Y CERRAR EL MODAL
    // ==========================================

    function abrirModal() {
        modal.style.display = "flex";
        formulario.reset();
        contenedorCampos.innerHTML = "";
        limpiarMensaje();
        btnGuardar.disabled = true;
    }

    function cerrarModal() {
        modal.style.display = "none";
        formulario.reset();
        contenedorCampos.innerHTML = "";
        limpiarMensaje();
        btnGuardar.disabled = true;
    }

    btnAbrir.addEventListener("click", abrirModal);
    btnCerrar.addEventListener("click", cerrarModal);
    btnCancelar.addEventListener("click", cerrarModal);

    // Cerrar al hacer clic fuera del contenido.

    modal.addEventListener("click", (evento) => {
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
        mensaje.textContent = "";
        mensaje.className = "mensaje-entidad";
    }

    // ==========================================
    // CREACIÓN DE CAMPOS
    // ==========================================

    function crearCampo(etiqueta, nombre, tipo = "text", opciones = {}) {

        const label = document.createElement("label");
        label.htmlFor = `entidad_${nombre}`;
        label.textContent = etiqueta;

        const input = document.createElement("input");

        input.type = tipo;
        input.id = `entidad_${nombre}`;
        input.name = nombre;
        input.required = true;

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

        const label = document.createElement("label");
        label.htmlFor = `entidad_${nombre}`;
        label.textContent = etiqueta;

        const select = document.createElement("select");

        select.id = `entidad_${nombre}`;
        select.name = nombre;
        select.required = true;

        opciones.forEach((opcion) => {

            const elemento = document.createElement("option");

            elemento.value = opcion.value;
            elemento.textContent = opcion.texto;

            select.appendChild(elemento);

        });

        contenedorCampos.append(label, select);

        return select;
    }

    // ==========================================
    // CAMPOS DE CADA ENTIDAD
    // ==========================================

    function mostrarCamposChofer() {

        crearCampo("Cédula de identidad", "ci_chofer", "text", {
            maxlength: 8,
            pattern: "[0-9]{8}",
            placeholder: "Ej: 12345678"
        });

        crearCampo("Nombre", "nombre_chofer", "text", {
            maxlength: 50,
            placeholder: "Nombre del chofer"
        });

        crearCampo("Apellido", "apellido_chofer", "text", {
            maxlength: 50,
            placeholder: "Apellido del chofer"
        });

        crearCampo("Teléfono", "telefono", "tel", {
            maxlength: 50,
            placeholder: "Teléfono de contacto"
        });
    }

    function mostrarCamposEnfermero() {

        crearCampo("Cédula de identidad", "ci_enfermero", "text", {
            maxlength: 8,
            pattern: "[0-9]{8}",
            placeholder: "Ej: 12345678"
        });

        crearCampo("Nombre", "nombre_enfermero", "text", {
            maxlength: 50,
            placeholder: "Nombre del enfermero"
        });

        crearCampo("Apellido", "apellido_enfermero", "text", {
            maxlength: 50,
            placeholder: "Apellido del enfermero"
        });
    }

    function mostrarCamposDestino() {

        crearCampo("Nombre del destino", "nombre_destino", "text", {
            maxlength: 50,
            placeholder: "Ej: Hospital de Clínicas"
        });

        crearCampo("Dirección", "direccion", "text", {
            maxlength: 50,
            placeholder: "Dirección del destino"
        });

        crearCampo("Ruta", "ruta", "text", {
            maxlength: 50,
            placeholder: "Ruta o referencia"
        });
    }

    async function mostrarCamposPaciente() {

        crearCampo("Cédula de identidad", "ci_paciente", "text", {
            maxlength: 8,
            pattern: "[0-9]{8}",
            placeholder: "Ej: 12345678"
        });

        crearCampo("Nombre completo", "nombre_paciente", "text", {
            maxlength: 50,
            placeholder: "Nombre y apellido"
        });

        crearSelect("Accesorio necesario", "accesorio", [
            { value: "", texto: "Seleccione una opción" },
            { value: "baston", texto: "Bastón" },
            { value: "silla", texto: "Silla de ruedas" },
            { value: "otro", texto: "Otro" }
        ]);

        crearSelect("¿Necesita oxígeno?", "oxigeno", [
            { value: "", texto: "Seleccione una opción" },
            { value: "1", texto: "Sí" },
            { value: "0", texto: "No" }
        ]);

        crearSelect("¿Requiere aislamiento?", "aislamiento", [
            { value: "", texto: "Seleccione una opción" },
            { value: "1", texto: "Sí" },
            { value: "0", texto: "No" }
        ]);

        crearCampo("Habitación de retiro", "habitacion_retiro", "text", {
            maxlength: 50,
            placeholder: "Número de habitación"
        });

        crearCampo("Piso de retiro", "piso_retiro", "text", {
            maxlength: 50,
            placeholder: "Número de piso"
        });

        const selectEnfermero = document.createElement("select");
        selectEnfermero.id = "entidad_ci_enfermero";
        selectEnfermero.name = "ci_enfermero";
        selectEnfermero.required = true;

        const labelEnfermero = document.createElement("label");
        labelEnfermero.htmlFor = "entidad_ci_enfermero";
        labelEnfermero.textContent = "Enfermero responsable";

        const opcionInicial = document.createElement("option");
        opcionInicial.value = "";
        opcionInicial.textContent = "Cargando enfermeros...";
        selectEnfermero.appendChild(opcionInicial);

        contenedorCampos.append(labelEnfermero, selectEnfermero);

        // Se solicitan los enfermeros al servidor para que el
        // administrador pueda seleccionar uno ya registrado.

        try {

            const respuesta = await fetch(RUTAS_API.enfermero, {
                method: "GET",
                credentials: "same-origin"
            });

            const datos = await respuesta.json();

            if (!respuesta.ok) {
                throw new Error(datos.mensaje || "No se pudieron cargar los enfermeros.");
            }

            selectEnfermero.innerHTML = "";

            const opcionSeleccione = document.createElement("option");
            opcionSeleccione.value = "";
            opcionSeleccione.textContent = "Seleccione un enfermero";
            selectEnfermero.appendChild(opcionSeleccione);

            datos.forEach((enfermero) => {

                const opcion = document.createElement("option");

                opcion.value = enfermero.ci_enfermero;
                opcion.textContent =
                    `${enfermero.nombre_enfermero} ${enfermero.apellido_enfermero} - ${enfermero.ci_enfermero}`;

                selectEnfermero.appendChild(opcion);

            });

            if (datos.length === 0) {
                selectEnfermero.innerHTML = "";
                const opcion = document.createElement("option");
                opcion.value = "";
                opcion.textContent = "Primero debe registrar un enfermero";
                selectEnfermero.appendChild(opcion);
            }

        } catch (error) {

            selectEnfermero.innerHTML = "";

            const opcion = document.createElement("option");
            opcion.value = "";
            opcion.textContent = "Error al cargar enfermeros";
            selectEnfermero.appendChild(opcion);

            mostrarMensaje(error.message, "error");
        }
    }

    // ==========================================
    // CAMBIAR LOS CAMPOS SEGÚN LA ENTIDAD
    // ==========================================

    selector.addEventListener("change", async () => {

        contenedorCampos.innerHTML = "";
        limpiarMensaje();

        const tipo = selector.value;

        btnGuardar.disabled = true;

        switch (tipo) {

            case "chofer":
                mostrarCamposChofer();
                break;

            case "enfermero":
                mostrarCamposEnfermero();
                break;

            case "destino":
                mostrarCamposDestino();
                break;

            case "paciente":
                await mostrarCamposPaciente();
                break;

            default:
                return;
        }

        btnGuardar.disabled = false;
    });

    // ==========================================
    // ENVIAR LOS DATOS AL SERVIDOR
    // ==========================================

    formulario.addEventListener("submit", async (evento) => {

        evento.preventDefault();
        limpiarMensaje();

        const tipo = selector.value;

        if (!RUTAS_API[tipo]) {
            mostrarMensaje("Seleccione una entidad válida.", "error");
            return;
        }

        if (!formulario.reportValidity()) {
            return;
        }

        const datosFormulario = new FormData(formulario);
        const datos = {};

        datosFormulario.forEach((valor, clave) => {
            if (clave !== "tipoEntidad") {
                datos[clave] = valor.trim();
            }
        });

        btnGuardar.disabled = true;
        btnGuardar.textContent = "Guardando...";

        try {

            const respuesta = await fetch(RUTAS_API[tipo], {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(datos)
            });

            const resultado = await respuesta.json();

            if (!respuesta.ok) {
                throw new Error(resultado.mensaje || "No se pudo guardar la entidad.");
            }

            mostrarMensaje(
                resultado.mensaje || "La entidad se registró correctamente.",
                "exito"
            );

            formulario.reset();
            contenedorCampos.innerHTML = "";

        } catch (error) {

            mostrarMensaje(error.message, "error");

        } finally {

            btnGuardar.disabled = false;
            btnGuardar.textContent = "Guardar entidad";
        }
    });

});
