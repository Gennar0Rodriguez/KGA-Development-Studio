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
    estado_cat ENUM('activa', 'deshabilitada') NOT NULL DEFAULT 'activa',
    CONSTRAINT UNIQUE_nombre_cat UNIQUE (nombre_cat)
);

CREATE TABLE documentos (
    id_documentos VARCHAR(50) PRIMARY KEY NOT NULL,
    ci_admin VARCHAR(8),
    id_categoria VARCHAR(50) NOT NULL,
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
    titulo_enc VARCHAR(50) NOT NULL,
    id_categoria VARCHAR(50) NOT NULL,
    descripcion_enc VARCHAR(50),
    PRIMARY KEY (titulo_enc, id_categoria),
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
);

CREATE TABLE pregunta (
    id_pregunta VARCHAR(50) NOT NULL,
    titulo_enc VARCHAR(50) NOT NULL,
    id_categoria VARCHAR(50) NOT NULL,
    nombre_pregunta VARCHAR(50) NOT NULL,
    tipo ENUM('opcion multiple', 'texto') NOT NULL,
    PRIMARY KEY (id_pregunta, titulo_enc),
    FOREIGN KEY (titulo_enc) REFERENCES encuesta(titulo_enc),
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
);

CREATE TABLE respuesta (
    id_respuesta VARCHAR(50) NOT NULL,
    id_pregunta VARCHAR(50) NOT NULL,
    titulo_enc VARCHAR(50) NOT NULL,
    id_categoria VARCHAR(50) NOT NULL,
    informacion VARCHAR(50),
    tipo ENUM('opcion multiple', 'texto') NOT NULL,
    PRIMARY KEY (id_respuesta, id_pregunta, id_categoria, titulo_enc),
    FOREIGN KEY (id_pregunta) REFERENCES pregunta(id_pregunta),
    FOREIGN KEY (titulo_enc) REFERENCES encuesta(titulo_enc),
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
    fecha_llegada DATE,
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
    fecha_envio DATETIME NOT NULL,
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
    ci_enfermero VARCHAR(8),
    habitacion_retiro VARCHAR(50) NOT NULL,
    piso_retiro VARCHAR(50) NOT NULL,
    FOREIGN KEY (ci_enfermero) REFERENCES enfermero(ci_enfermero) ON DELETE SET NULL
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
('87654321', 'Joaquin', 'Fida', 'Trunks', 'administrador'),
('34567890', 'Ari', 'Matta', 'Eze', 'administrador'),
('56789012', 'Cristiano', 'Ronaldo', 'cr7', 'empleado'),
('56789843', 'Connor', 'Mcgregor', 'ufc', 'empleado'),
('58729012', 'Islam', 'Makhachev', 'ufc', 'empleado'),
('23456789', 'Gennaro', 'Rodriguez', 'gen', 'administrador'),
('45678901', 'Santiago', 'Marichal', 'cracken', 'administrador');

INSERT INTO categoria (id_categoria, nombre_cat, tipo_cat, estado_cat) VALUES
('CAT-01', 'Normativas', 'docs', 'activa'),
('CAT-02', 'Formularios', 'docs', 'activa'),
('CAT-03', 'Satisfaccion', 'encuestas', 'activa'),
('CAT-04', 'Calidad', 'encuestas', 'activa'),
('CAT-05', 'Manuales', 'docs', 'deshabilitada');

INSERT INTO documentos (id_documentos, ci_admin, id_categoria, hora_carga, fecha_carga, titulo_docs, descripcion_docs, nombre_archivo, ruta_archivo) VALUES
('DOC-101', '12345678', 'CAT-01', '08:30:00', '2026-01-10', 'Reglamento', 'Reglamento interno', 'reglamento.pdf', 'API/uploads/documentos/testdocumentos1.pdf'),
('DOC-102', '87654321', 'CAT-01', '09:15:00', '2026-01-11', 'Protocolo', 'Protocolo de seguridad', 'protocolo.pdf', 'API/uploads/documentos/testdocumentos2.pdf'),
('DOC-103', '34567890', 'CAT-02', '10:00:00', '2026-01-12', 'Formulario A', 'Formulario de ingreso', 'form_a.pdf', 'API/uploads/documentos/testdocumentos1.pdf'),
('DOC-104', '23456789', 'CAT-02', '11:20:00', '2026-01-13', 'Formulario B', 'Formulario de salida', 'form_b.pdf', 'API/uploads/documentos/testdocumentos2.pdf'),
('DOC-105', '45678901', 'CAT-05', '14:00:00', '2026-01-14', 'Manual Antiguo', 'Manual viejo', 'manual.pdf', 'API/uploads/documentos/testdocumentos1.pdf');

INSERT INTO encuesta (titulo_enc, id_categoria, descripcion_enc) VALUES
('Evaluacion Servicio', 'CAT-03', 'Encuesta sobre el servicio general'),
('Atencion Telefonica', 'CAT-03', 'Calificacion de operadores'),
('Ambiente Laboral', 'CAT-04', 'Encuesta interna para empleados'),
('Logistica', 'CAT-04', 'Evaluacion de traslados'),
('Feedback General', 'CAT-03', 'Opiniones generales');

INSERT INTO pregunta (id_pregunta, titulo_enc, id_categoria, nombre_pregunta, tipo) VALUES
('P-01', 'Evaluacion Servicio', 'CAT-03', 'Como califica el servicio?', 'opcion multiple'),
('P-02', 'Evaluacion Servicio', 'CAT-03', 'Comentarios adicionales', 'texto'),
('P-03', 'Atencion Telefonica', 'CAT-03', 'Fue atendido rapidamente?', 'opcion multiple'),
('P-04', 'Ambiente Laboral', 'CAT-04', 'Se siente comodo en su area?', 'opcion multiple'),
('P-05', 'Logistica', 'CAT-04', 'Describa su experiencia', 'texto');

INSERT INTO respuesta (id_respuesta, id_pregunta, titulo_enc, id_categoria, informacion, tipo) VALUES
('R-01', 'P-01', 'Evaluacion Servicio', 'CAT-03', 'Excelente', 'opcion multiple'),
('R-02', 'P-02', 'Evaluacion Servicio', 'CAT-03', 'Todo muy bien', 'texto'),
('R-03', 'P-03', 'Atencion Telefonica', 'CAT-03', 'Si', 'opcion multiple'),
('R-04', 'P-04', 'Ambiente Laboral', 'CAT-04', 'Si', 'opcion multiple'),
('R-05', 'P-05', 'Logistica', 'CAT-04', 'Puntual y eficiente', 'texto');

INSERT INTO chofer (ci_chofer, nombre_chofer, apellido_chofer, telefono) VALUES
('11111111', 'Georges', 'St-Pierre', '099111111'),
('22222222', 'Antonio', 'Pacheco', '099222222'),
('33333333', 'Rodrigo', 'Verrone', '099333333'),
('44444444', 'Martin', 'Benitez', '099444444'),
('55555555', 'Facundo', 'Suarez', '099555555');

INSERT INTO vehiculo (matricula, modelo, capacidad, tipo_vehiculo, camilla, observaciones) VALUES
('SCA-1234', 'Mercedes Sprinter', '3', 'ambulancia', 1, 'Equipada'),
('SCA-5678', 'Ford Transit', '3', 'ambulancia', 1, 'Sin novedad'),
('SCI-9012', 'Renault Kangoo', '2', 'auto', 0, 'Urbano'),
('SCI-3456', 'Fiat Fiorino', '2', 'auto', 0, 'Buen estado'),
('SCO-7890', 'Toyota Hilux', '4', 'otro', 0, '4x4');

INSERT INTO conduce (ci_chofer, matricula) VALUES
('11111111', 'SCA-1234'),
('22222222', 'SCA-5678'),
('33333333', 'SCI-9012'),
('44444444', 'SCI-3456'),
('55555555', 'SCO-7890');

INSERT INTO destino (id_destino, nombre_destino, direccion, ruta) VALUES
('DEST-01', 'Hospital Central', 'Av. Italia 1500', 'Ruta 1'),
('DEST-02', 'Sanatorio Americano', 'Bv. Artigas 1600', 'Ruta 2'),
('DEST-03', 'Clinica Modelo', '8 de Octubre 2300', 'Ruta 3'),
('DEST-04', 'Hospital de Clinicas', 'Italia s/n', 'Ruta 4'),
('DEST-05', 'Sanatorio Britanico', 'Avenida Garibaldi 1700', 'Ruta 5');

INSERT INTO traslado (id_traslado, ci_chofer, matricula, id_destino, fecha_traslado, fecha_llegada, hora_salida, hora_llegada, lugar_origen, estado_traslado, ci_solicitante, estado_solicitud, observaciones) VALUES
('TR-01', '11111111', 'SCA-1234', 'DEST-01', '2026-02-01', '2026-02-01', '08:00:00', '08:45:00', 'Barrio Sur', 'finalizado', '12345678', 'aprobado', 'Sin contratiempos'),
('TR-02', '22222222', 'SCA-5678', 'DEST-02', '2026-02-02', '2026-02-02', '09:00:00', '09:50:00', 'Pocitos', 'finalizado', '87654321', 'aprobado', 'Traslado rapido'),
('TR-03', '33333333', 'SCI-9012', 'DEST-03', '2026-02-03', '2026-02-03', '10:00:00', '10:30:00', 'Centro', 'aprobado', '34567890', 'aprobado', 'Normal'),
('TR-04', '44444444', 'SCI-3456', 'DEST-04', '2026-02-04', NULL, '11:00:00', NULL, 'Malvin', 'en proceso', '23456789', 'pendiente', 'En camino'),
('TR-05', '55555555', 'SCO-7890', 'DEST-05', '2026-02-05', NULL, '12:00:00', NULL, 'Buceo', 'pendiente', '45678901', 'pendiente', 'Esperando autorizacion');

INSERT INTO notifica (id_traslado, ci_chofer, matricula, id_destino, ci_admin, ci_emisor, fecha_envio, leido, titulo_not, descripcion_not, ci_receptor) VALUES
('TR-01', '11111111', 'SCA-1234', 'DEST-01', '12345678', '12345678', '2026-02-01 07:30:00', 1, 'Asignacion', 'Se le asigno un traslado', '11111111'),
('TR-02', '22222222', 'SCA-5678', 'DEST-02', '87654321', '87654321', '2026-02-02 08:30:00', 1, 'Asignacion', 'Nuevo traslado confirmado', '22222222'),
('TR-03', '33333333', 'SCI-9012', 'DEST-03', '34567890', '34567890', '2026-02-03 09:30:00', 0, 'Aviso', 'Traslado programado', '33333333'),
('TR-04', '44444444', 'SCI-3456', 'DEST-04', '23456789', '23456789', '2026-02-04 10:30:00', 0, 'Pendiente', 'Revision de solicitud', '44444444'),
('TR-05', '55555555', 'SCO-7890', 'DEST-05', '45678901', '45678901', '2026-02-05 11:30:00', 0, 'Alerta', 'Solicitud en espera', '55555555');

INSERT INTO enfermero (ci_enfermero, nombre_enfermero, apellido_enfermero) VALUES
('66666666', 'Agustina', 'Duarte'),
('77777777', 'Sofia', 'Pereira'),
('88888888', 'Kenia', 'Kronberg'),
('99999999', 'Jorge', 'Gimenez'),
('10101010', 'Pedro', 'Picapiedra');

INSERT INTO transportable (id_transportable, ci_enfermero, habitacion_retiro, piso_retiro) VALUES
('TRANS-01', '66666666', 'Hab 101', 'Piso 1'),
('TRANS-02', '77777777', 'Hab 202', 'Piso 2'),
('TRANS-03', '88888888', 'Hab 303', 'Piso 3'),
('TRANS-04', '99999999', 'Hab 404', 'Piso 4'),
('TRANS-05', '10101010', 'Hab 505', 'Piso 5');

INSERT INTO paciente (id_transportable, ci_paciente, nombre_paciente, accesorio, oxigeno, aislamiento) VALUES
('TRANS-01', '12121212', 'Juan Perez', 'baston', 0, 0),
('TRANS-02', '13131313', 'Roberto Gomez', 'silla', 1, 0),
('TRANS-03', '14141414', 'Carlos Ruiz', 'otro', 0, 1),
('TRANS-04', '15151515', 'Miguel Angel', 'baston', 0, 0),
('TRANS-05', '16161616', 'Alberto Fernandez', 'silla', 1, 1);

INSERT INTO elemento (id_transportable, nombre_elemento, descripcion_elemento, tipo_elemento) VALUES
('TRANS-01', 'Riñón', 'Organo para trasplante', 'organo'),
('TRANS-02', 'Historial', 'Carpeta clinica', 'documentacion'),
('TRANS-03', 'Corazon', 'Organo vital', 'organo'),
('TRANS-04', 'Consentimiento', 'Firma de autorizacion', 'documentacion'),
('TRANS-05', 'Kit Medico', 'Insumos varios', 'otro');

INSERT INTO transporta (id_transportable, id_traslado, ci_chofer, matricula, id_destino) VALUES
('TRANS-01', 'TR-01', '11111111', 'SCA-1234', 'DEST-01'),
('TRANS-02', 'TR-02', '22222222', 'SCA-5678', 'DEST-02'),
('TRANS-03', 'TR-03', '33333333', 'SCI-9012', 'DEST-03'),
('TRANS-04', 'TR-04', '44444444', 'SCI-3456', 'DEST-04'),
('TRANS-05', 'TR-05', '55555555', 'SCO-7890', 'DEST-05');
