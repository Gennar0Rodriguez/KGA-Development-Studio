DROP DATABASE IF EXISTS Proyecto;
CREATE DATABASE Proyecto;
USE Proyecto;

CREATE TABLE administrativo (
    ci_admin VARCHAR(8) PRIMARY KEY NOT NULL,
    nombre_admin VARCHAR(50) NOT NULL,
    apellido_admin VARCHAR(50) NOT NULL,
    contraseña_admin VARCHAR(50) NOT NULL,
    cargo ENUM('administrador', 'empleado') NOT NULL
);

CREATE TABLE categoria (
    id_categoria VARCHAR(50) PRIMARY KEY NOT NULL,
    nombre_cat VARCHAR(50) NOT NULL,
    tipo_cat ENUM ('docs', 'encuestas'),
    estado_cat ENUM('activa', 'deshabilitada') NOT NULL DEFAULT 'activa'
);

CREATE TABLE documentos (
    id_documentos VARCHAR(50) PRIMARY KEY NOT NULL,
    ci_admin VARCHAR(8),
    id_categoria VARCHAR(50),
    hora_carga TIME NOT NULL,
    fecha_carga DATE NOT NULL,
    titulo_docs VARCHAR(50) NOT NULL,
    descripcion_docs VARCHAR(50),
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria),
    FOREIGN KEY (ci_admin) REFERENCES administrativo(ci_admin) ON DELETE SET NULL
);

CREATE TABLE encuesta (
    id_encuesta VARCHAR(50) NOT NULL,
    id_categoria VARCHAR(50) NOT NULL,
    descripcion_enc VARCHAR(50),
    titulo_enc VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_encuesta, id_categoria),
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
);
 
CREATE TABLE pregunta (
    id_pregunta VARCHAR(50) NOT NULL,
    id_encuesta VARCHAR(50) NOT NULL,
    id_categoria VARCHAR(50) NOT NULL,
    nombre_pregunta VARCHAR(50) NOT NULL,
    tipo ENUM('opcion multiple', 'texto') NOT NULL,
    PRIMARY KEY (id_pregunta, id_encuesta, id_categoria),
    FOREIGN KEY (id_encuesta) REFERENCES encuesta(id_encuesta),
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
);

CREATE TABLE respuesta (
    id_respuesta VARCHAR(50) NOT NULL,
    id_pregunta VARCHAR(50) NOT NULL,
    id_encuesta VARCHAR(50) NOT NULL,
    id_categoria VARCHAR(50) NOT NULL,
    informacion VARCHAR(50),
    tipo ENUM('opcion multiple', 'texto') NOT NULL,
    PRIMARY KEY (id_respuesta, id_pregunta, id_encuesta),
    FOREIGN KEY (id_pregunta) REFERENCES pregunta(id_pregunta),
    FOREIGN KEY (id_encuesta) REFERENCES encuesta(id_encuesta),
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
);

CREATE TABLE chofer (
    ci_chofer VARCHAR(8) PRIMARY KEY NOT NULL,
    nombre_chofer VARCHAR(50) NOT NULL,
    apellido_chofer VARCHAR(50) NOT NULL,
    telefono VARCHAR(50) NOT NULL
);

CREATE TABLE vehiculo (
    matricula VARCHAR(15) PRIMARY KEY NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    capacidad VARCHAR(50) NOT NULL,
    tipo_vehiculo ENUM('ambulancia', 'auto', 'otro') NOT NULL,
    camilla BOOLEAN NOT NULL,
    observaciones VARCHAR(100)
);

CREATE TABLE conduce (
    ci_chofer VARCHAR(8) NOT NULL,
    matricula VARCHAR(15) NOT NULL,
    PRIMARY KEY (ci_chofer, matricula),
    FOREIGN KEY (ci_chofer) REFERENCES chofer(ci_chofer) ON DELETE CASCADE, 
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula) ON DELETE CASCADE
);

CREATE TABLE destino (
    id_destino VARCHAR(50) PRIMARY KEY NOT NULL,
    nombre_destino VARCHAR(50) NOT NULL,
    direccion VARCHAR(50) NOT NULL,
    ruta VARCHAR(50) NOT NULL
);

CREATE TABLE traslado (
    id_traslado VARCHAR(50) NOT NULL,
    ci_chofer VARCHAR(8) NOT NULL,
    matricula VARCHAR(15) NOT NULL,
    id_destino VARCHAR(50) NOT NULL,
    fecha_traslado DATE NOT NULL,
    hora_salida TIME,
    hora_llegada TIME,
    lugar_origen VARCHAR(50) NOT NULL,
    estado_traslado ENUM('finalizado', 'aprobado', 'denegado', 'en proceso', 'pendiente') NOT NULL,
    ci_solicitante VARCHAR(8) NOT NULL,
    estado_solicitud ENUM('aprobado', 'rechazado', 'pendiente') NOT NULL,
    observaciones VARCHAR(100),
    PRIMARY KEY (id_traslado, ci_chofer, matricula, id_destino),
    FOREIGN KEY (ci_chofer) REFERENCES chofer(ci_chofer) ON DELETE CASCADE,
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula) ON DELETE CASCADE,
    FOREIGN KEY (id_destino) REFERENCES destino(id_destino) ON DELETE CASCADE
);

CREATE TABLE notifica (
    numero_notificacion INT NOT NULL AUTO_INCREMENT,
    id_traslado VARCHAR(50) NOT NULL,
    ci_chofer VARCHAR(8) NOT NULL,
    matricula VARCHAR(15) NOT NULL,
    id_destino VARCHAR(50) NOT NULL,
    ci_admin VARCHAR(8),
    ci_emisor VARCHAR(8) NOT NULL,
    fecha_envio DATE NOT NULL,
    leido BOOLEAN,
    titulo_not VARCHAR(100) NOT NULL,
    descripcion_not VARCHAR(255) NOT NULL,
    ci_receptor VARCHAR(8) NOT NULL,
    PRIMARY KEY (numero_notificacion, id_traslado, ci_chofer, matricula, id_destino, ci_admin),
    FOREIGN KEY (id_traslado) REFERENCES traslado(id_traslado) ON DELETE CASCADE,
    FOREIGN KEY (ci_chofer) REFERENCES chofer(ci_chofer) ON DELETE CASCADE,
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula) ON DELETE CASCADE,
    FOREIGN KEY (id_destino) REFERENCES destino(id_destino) ON DELETE CASCADE,
    FOREIGN KEY (ci_admin) REFERENCES administrativo(ci_admin) ON DELETE CASCADE
);

CREATE TABLE enfermero (
    ci_enfermero VARCHAR(8) PRIMARY KEY NOT NULL,
    nombre_enfermero VARCHAR(50) NOT NULL,
    apellido_enfermero VARCHAR(50) NOT NULL
);

CREATE TABLE transportable (
    id_transportable VARCHAR(50) PRIMARY KEY NOT NULL,
    ci_enfermero VARCHAR(8) NOT NULL,
    habitacion_retiro VARCHAR(50) NOT NULL,
    piso_retiro VARCHAR(50) NOT NULL,
    FOREIGN KEY (ci_enfermero) REFERENCES enfermero(ci_enfermero) ON DELETE CASCADE
);

CREATE TABLE paciente (
    id_transportable VARCHAR(50) PRIMARY KEY NOT NULL,
    ci_paciente VARCHAR(8) NOT NULL,
    nombre_paciente VARCHAR(50) NOT NULL,
    accesorio ENUM('baston', 'silla', 'otro') NOT NULL,
    oxigeno BOOLEAN NOT NULL,
    aislamiento BOOLEAN NOT NULL,
    FOREIGN KEY (id_transportable) REFERENCES transportable(id_transportable) ON DELETE CASCADE
);

CREATE TABLE elemento (
    id_transportable VARCHAR(50) PRIMARY KEY NOT NULL,
    nombre_elemento VARCHAR(50) NOT NULL,
    descripcion_elemento VARCHAR(50) NOT NULL,
    tipo_elemento ENUM('organo', 'documentacion', 'otro') NOT NULL,
    FOREIGN KEY (id_transportable) REFERENCES transportable(id_transportable) ON DELETE CASCADE
);

CREATE TABLE transporta (
    id_transportable VARCHAR(50) NOT NULL,
    id_traslado VARCHAR(50) NOT NULL,
    ci_chofer VARCHAR(8) NOT NULL,
    matricula VARCHAR(15) NOT NULL,
    id_destino VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_transportable, id_traslado, ci_chofer, matricula, id_destino),
    FOREIGN KEY (id_transportable) REFERENCES transportable(id_transportable) ON DELETE CASCADE,
    FOREIGN KEY (id_traslado) REFERENCES traslado(id_traslado) ON DELETE CASCADE,
    FOREIGN KEY (ci_chofer) REFERENCES chofer(ci_chofer) ON DELETE CASCADE,
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula) ON DELETE CASCADE,
    FOREIGN KEY (id_destino) REFERENCES destino(id_destino) ON DELETE CASCADE
);

INSERT INTO administrativo (ci_admin, nombre_admin, apellido_admin, contraseña_admin, cargo) VALUES
('12345678', 'Ezequiel', 'Matta', 'Ari', 'administrador'),
('87654321', 'Ari', 'Matta', 'Eze', 'administrador'),
('11111111', 'Gennaro', 'Rodriguez', 'gen', 'administrador'),
('22222222', 'Santiago', 'Marichal', '789', 'administrador'),
('99887766', 'Juan', 'Lopez', '222', 'empleado');