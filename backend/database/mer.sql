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
    FOREIGN KEY (ci_admin) REFERENCES administrativo(ci_admin)
        ON DELETE SET NULL
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
    FOREIGN KEY (ci_chofer) REFERENCES chofer(ci_chofer),
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula)
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
    FOREIGN KEY (ci_chofer) REFERENCES chofer(ci_chofer),
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula),
    FOREIGN KEY (id_destino) REFERENCES destino(id_destino)
);

CREATE TABLE notifica (
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
    PRIMARY KEY (id_traslado, ci_chofer, matricula, id_destino, ci_admin),
    FOREIGN KEY (id_traslado) REFERENCES traslado(id_traslado),
    FOREIGN KEY (ci_chofer) REFERENCES chofer(ci_chofer),
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula),
    FOREIGN KEY (id_destino) REFERENCES destino(id_destino),
    FOREIGN KEY (ci_admin) REFERENCES administrativo(ci_admin)
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
    FOREIGN KEY (ci_enfermero) REFERENCES enfermero(ci_enfermero)
);

CREATE TABLE paciente (
    id_transportable VARCHAR(50) PRIMARY KEY NOT NULL,
    ci_paciente VARCHAR(8) NOT NULL,
    nombre_paciente VARCHAR(50) NOT NULL,
    accesorio ENUM('baston', 'silla', 'otro') NOT NULL,
    oxigeno BOOLEAN NOT NULL,
    aislamiento BOOLEAN NOT NULL,
    FOREIGN KEY (id_transportable) REFERENCES transportable(id_transportable)
);

CREATE TABLE elemento (
    id_transportable VARCHAR(50) PRIMARY KEY NOT NULL,
    nombre_elemento VARCHAR(50) NOT NULL,
    descripcion_elemento VARCHAR(50) NOT NULL,
    tipo_elemento ENUM('organo', 'documentacion', 'otro') NOT NULL,
    FOREIGN KEY (id_transportable) REFERENCES transportable(id_transportable)
);

CREATE TABLE transporta (
    id_transportable VARCHAR(50) NOT NULL,
    id_traslado VARCHAR(50) NOT NULL,
    ci_chofer VARCHAR(8) NOT NULL,
    matricula VARCHAR(15) NOT NULL,
    id_destino VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_transportable, id_traslado, ci_chofer, matricula, id_destino),
    FOREIGN KEY (id_transportable) REFERENCES transportable(id_transportable),
    FOREIGN KEY (id_traslado) REFERENCES traslado(id_traslado),
    FOREIGN KEY (ci_chofer) REFERENCES chofer(ci_chofer),
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula),
    FOREIGN KEY (id_destino) REFERENCES destino(id_destino)
);

INSERT INTO administrativo (ci_admin, nombre_admin, apellido_admin, contraseña_admin, cargo) VALUES
('12345678', 'Ezequiel', 'Matta', 'Ari', 'administrador'),
('87654321', 'Ari', 'Matta', 'Eze', 'administrador'),
('11223344', 'Carlos', 'Perez', '456', 'empleado'),
('55667788', 'Marichal', 'Rodriguez', '789', 'empleado'),
('99887766', 'Juan', 'Lopez', '222', 'empleado');

INSERT INTO categoria (id_categoria, nombre_cat, tipo_cat, estado_cat) VALUES
('CAT01', 'Urgencias', 'docs', 'activa'),
('CAT02', 'Pediatria', 'docs', 'activa'),
('CAT03', 'Cirugia General', 'docs', 'activa'),
('CAT04', 'Farmacia', 'docs', 'activa'),
('CAT05', 'Laboratorio Clinico', 'docs', 'activa'),
('CAT06', 'Limpieza', 'encuestas', 'activa'),
('CAT07', 'Trato del personal', 'encuestas', 'activa'),
('CAT08', 'Velocidad del personal', 'encuestas', 'activa'),
('CAT09', 'Higiene del personal', 'encuestas', 'activa'),
('CAT10', 'Atencion general', 'encuestas', 'activa');

INSERT INTO documentos (id_documentos, ci_admin, id_categoria, hora_carga, fecha_carga, titulo_docs, descripcion_docs, nombre_archivo, ruta_archivo) VALUES
('DOC01', '12345678', 'CAT01', '08:30:00', '2026-06-01', 'Protocolo Triaje', 'Protocolo de recepcion de emergencias', 'triaje.pdf', 'uploads/documentos/triaje.pdf'),
('DOC02', '87654321', 'CAT02', '09:15:00', '2026-06-02', 'Historial Pediatrico', 'Guia de controles pediatricos', 'pediatria.pdf', 'uploads/documentos/pediatria.pdf'),
('DOC03', '11223344', 'CAT03', '10:00:00', '2026-06-03', 'Checklist Quirurgico', 'Lista de verificacion de quirofano', 'quirofano.pdf', 'uploads/documentos/quirofano.pdf'),
('DOC04', '55667788', 'CAT04', '11:20:00', '2026-06-04', 'Inventario Insumos', 'Stock de medicamentos del mes', 'farmacia.pdf', 'uploads/documentos/farmacia.pdf'),
('DOC05', '99887766', 'CAT05', '14:00:00', '2026-06-05', 'Manual Muestras', 'Procedimiento de analiticas de sangre', 'analisis.pdf', 'uploads/documentos/analisis.pdf');

INSERT INTO encuesta (id_encuesta, id_categoria, descripcion_enc, titulo_enc) VALUES
('ENC01', 'CAT06', 'Evaluacion de limpieza hospitalaria', 'Encuesta de Limpieza'),
('ENC02', 'CAT07', 'Evaluacion del trato recibido', 'Encuesta de Trato'),
('ENC03', 'CAT08', 'Evaluacion de velocidad de atencion', 'Encuesta de Velocidad'),
('ENC04', 'CAT09', 'Evaluacion de higiene del personal', 'Encuesta de Higiene'),
('ENC05', 'CAT10', 'Evaluacion general del servicio', 'Encuesta de Atencion');

INSERT INTO pregunta (id_pregunta, id_encuesta, id_categoria, nombre_pregunta, tipo) VALUES
('PRG01', 'ENC01', 'CAT06', 'Como calificaria la limpieza?', 'opcion multiple'),
('PRG02', 'ENC02', 'CAT07', 'Como fue el trato recibido?', 'opcion multiple'),
('PRG03', 'ENC03', 'CAT08', 'La atencion fue rapida?', 'opcion multiple'),
('PRG04', 'ENC04', 'CAT09', 'El personal mantenia buena higiene?', 'opcion multiple'),
('PRG05', 'ENC05', 'CAT10', 'Como calificaria el servicio?', 'opcion multiple');

INSERT INTO respuesta (id_respuesta, id_pregunta, id_encuesta, id_categoria, informacion, tipo) VALUES
('RES01', 'PRG01', 'ENC01', 'CAT06', '5', 'opcion multiple'),
('RES02', 'PRG02', 'ENC02', 'CAT07', '4', 'opcion multiple'),
('RES03', 'PRG03', 'ENC03', 'CAT08', '3', 'opcion multiple'),
('RES04', 'PRG04', 'ENC04', 'CAT09', '5', 'opcion multiple'),
('RES05', 'PRG05', 'ENC05', 'CAT10', '4', 'opcion multiple');

INSERT INTO chofer (ci_chofer, nombre_chofer, apellido_chofer, telefono) VALUES
('45678912', 'Esteban', 'Silva', '099123456'),
('32165498', 'Marcelo', 'Rios', '098654321'),
('78912345', 'Gabriel', 'Benitez', '097111222'),
('65498732', 'Diego', 'Fagundez', '096333444'),
('14725836', 'Fernando', 'Acosta', '095555666');

INSERT INTO vehiculo (matricula, modelo, capacidad, tipo_vehiculo, camilla, observaciones) VALUES
('SCA1001', 'Mercedes Sprinter', '4', 'ambulancia', 1, 'Equipamiento UTI movil'),
('SCA1002', 'Fiat Ducato', '3', 'ambulancia', 1, 'Oxigeno disponible'),
('SCA1003', 'Renault Kangoo', '1', 'auto', 0, 'Traslado de personal o insumos'),
('SCA1004', 'Peugeot Partner', '1', 'auto', 0, 'Documentacion urgente'),
('SCA1005', 'Ford Fiesta', '6', 'otro', 0, 'Vehiculo de traslado');

INSERT INTO conduce (ci_chofer, matricula) VALUES
('45678912', 'SCA1001'),
('32165498', 'SCA1002'),
('78912345', 'SCA1003'),
('65498732', 'SCA1004'),
('14725836', 'SCA1005');

INSERT INTO destino (id_destino, nombre_destino, direccion, ruta) VALUES
('DES01', 'Hospital de Clinicas', 'Av. Italia s/n', 'Ruta 1'),
('DES02', 'Sanatorio Americano', 'Av. 8 de Octubre 2720', 'Ruta 2'),
('DES03', 'Hospital Maciel', '25 de Mayo 174', 'Ruta 3'),
('DES04', 'Centro de Asistencia Medica', 'Bv. Artigas 1550', 'Ruta 4'),
('DES05', 'Sanatorio Britanico', 'Av. Garibaldi 2764', 'Ruta 5');

INSERT INTO traslado (id_traslado, ci_chofer, matricula, id_destino, fecha_traslado, hora_salida, hora_llegada, lugar_origen, estado_traslado, ci_solicitante, estado_solicitud, observaciones) VALUES
('TRS01', '45678912', 'SCA1001', 'DES01', '2026-06-01', '08:00:00', '08:45:00', 'Hospital Central', 'finalizado', '12345678', 'aprobado', 'Traslado de paciente'),
('TRS02', '32165498', 'SCA1002', 'DES02', '2026-06-02', '09:30:00', '10:15:00', 'Hospital Central', 'aprobado', '55667364', 'aprobado', 'Derivacion medica'),
('TRS03', '78912345', 'SCA1003', 'DES03', '2026-06-03', '11:00:00', '11:30:00', 'Hospital Central', 'en proceso', '12345678', 'aprobado', 'Envio de documentacion'),
('TRS04', '65498732', 'SCA1004', 'DES04', '2026-06-04', '13:00:00', '13:40:00', 'Hospital Central', 'pendiente', '55667364', 'pendiente', 'Solicitud en evaluacion'),
('TRS05', '14725836', 'SCA1005', 'DES05', '2026-06-05', '15:00:00', '15:50:00', 'Hospital Central', 'denegado', '12345678', 'rechazado', 'Traslado cancelado');

INSERT INTO notifica (id_traslado, ci_chofer, matricula, id_destino, ci_admin, ci_emisor, fecha_envio, leido, titulo_not, descripcion_not, ci_receptor) VALUES
('TRS01', '45678912', 'SCA1001', 'DES01', '12345678', '12345678', '2026-06-01', 1, 'Asignacion de traslado', 'Se asigno un traslado al Hospital de Clinicas.', '87654321'),
('TRS02', '32165498', 'SCA1002', 'DES02', '55667788', '55667788', '2026-06-02', 1, 'Derivacion aprobada', 'La derivacion fue aprobada.', '11223344'),
('TRS03', '78912345', 'SCA1003', 'DES03', '12345678', '12345678', '2026-06-03', 0, 'Envio de documentacion', 'Documentacion medica en traslado.', '99887766'),
('TRS04', '65498732', 'SCA1004', 'DES04', '55667788', '55667788', '2026-06-04', 0, 'Solicitud pendiente', 'La solicitud se encuentra en evaluacion.', '87654321'),
('TRS05', '14725836', 'SCA1005', 'DES05', '12345678', '12345678', '2026-06-05', 1, 'Traslado rechazado', 'La solicitud de traslado fue rechazada.', '11223344');

INSERT INTO enfermero (ci_enfermero, nombre_enfermero, apellido_enfermero) VALUES
('22334455', 'Agustina', 'Duarte'),
('33445566', 'Matias', 'Suarez'),
('44556677', 'Sofia', 'Pereira'),
('55667788', 'Jefrery', 'Pereyra'),
('66778899', 'Bennet', 'Impact');

INSERT INTO transportable (id_transportable, ci_enfermero, habitacion_retiro, piso_retiro) VALUES
('TRP01', '22334455', 'Habitacion 204', 'Piso 2'),
('TRP02', '33445566', 'Habitacion 310', 'Piso 3'),
('TRP03', '44556677', 'Sala de Esterilizacion', 'Planta Baja'),
('TRP04', '55667788', 'Habitacion 105', 'Piso 1'),
('TRP05', '66778899', 'Laboratorio Central', 'Subsuelo'),
('TRP06', '22334455', 'CTI Adultos', 'Piso 4'),
('TRP07', '33445566', 'Archivo Central', 'Subsuelo'),
('TRP08', '44556677', 'Farmacia', 'Planta Baja');

INSERT INTO paciente (id_transportable, ci_paciente, nombre_paciente, accesorio, oxigeno, aislamiento) VALUES
('TRP01', '98765432', 'Roberto Gomez', 'silla', 1, 0),
('TRP02', '87654321', 'Ezequiel Matta Rodriguez', 'baston', 0, 1),
('TRP04', '76543210', 'Jorge Ramirez', 'otro', 1, 0);

INSERT INTO elemento (id_transportable, nombre_elemento, descripcion_elemento, tipo_elemento) VALUES
('TRP03', 'Valvula Cardiaca', 'Valvula biologica para cirugia', 'organo'),
('TRP05', 'Historias Clinicas', 'Carpeta con expedientes archivados', 'documentacion'),
('TRP06', 'Rinon para Trasplante', 'Tejido vivo conservado en frio', 'organo'),
('TRP07', 'Expedientes de Auditoria', 'Documentacion confidencial', 'documentacion'),
('TRP08', 'Insumos Quirurgicos', 'Instrumental esterilizado', 'otro');

INSERT INTO transporta (id_transportable, id_traslado, ci_chofer, matricula, id_destino) VALUES
('TRP01', 'TRS01', '45678912', 'SCA1001', 'DES01'),
('TRP02', 'TRS02', '32165498', 'SCA1002', 'DES02'),
('TRP03', 'TRS03', '78912345', 'SCA1003', 'DES03'),
('TRP04', 'TRS04', '65498732', 'SCA1004', 'DES04'),
('TRP05', 'TRS05', '14725836', 'SCA1005', 'DES05');

