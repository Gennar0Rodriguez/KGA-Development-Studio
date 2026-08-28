CREATE DATABASE EZEQUIELIN;
USE EZEQUIELIN;



CREATE TABLE administrativo (
    ci_admin VARCHAR(8) PRIMARY KEY NOT NULL,
    nombre_admin VARCHAR(50) NOT NULL,
    apellido_admin VARCHAR(50) NOT NULL,
    contraseña_admin VARCHAR(50) NOT NULL,
    cargo ENUM('administrador', 'empleado') NOT NULL
);

CREATE TABLE categoria (
    id_categoria VARCHAR(50) PRIMARY KEY NOT NULL,
    nombre_cat VARCHAR(50) NOT NULL
);

CREATE TABLE carga(
    ci_admin VARCHAR(8) NOT NULL,
    id_categoria VARCHAR(50) NOT NULL,
    hora TIME NOT NULL,
    fecha DATE NOT NULL,
    PRIMARY KEY (ci_admin, id_categoria),
    FOREIGN KEY (ci_admin) REFERENCES administrativo(ci_admin),
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
);

CREATE TABLE documentos (
    id_documentos VARCHAR(50) PRIMARY KEY NOT NULL,
    ci_admin VARCHAR(8),
    id_categoria VARCHAR(50),
    titulo_docs VARCHAR(50) NOT NULL,
    descripcion_docs VARCHAR(50),
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
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
    id_pregunta VARCHAR(50) PRIMARY KEY NOT NULL,
    nombre_pregunta VARCHAR(50) NOT NULL,
    tipo ENUM('opcion multiple', 'texto') NOT NULLS
);

CREATE TABLE contiene (
    id_pregunta VARCHAR(50) NOT NULL,
    id_encuesta VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_pregunta, id_encuesta),
    FOREIGN KEY (id_pregunta) REFERENCES pregunta(id_pregunta)
);

CREATE TABLE respuesta (
    id_respuesta VARCHAR(50) PRIMARY KEY NOT NULL,
    informacion VARCHAR(50),
    tipo ENUM('opcion multiple', 'texto') NOT NULL
);

CREATE TABLE guarda (
    id_respuesta VARCHAR(50) NOT NULL,
    id_pregunta VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_respuesta, id_pregunta),
    FOREIGN KEY (id_respuesta) REFERENCES respuesta(id_respuesta),
    FOREIGN KEY (id_pregunta) REFERENCES pregunta(id_pregunta)
);


INSERT INTO administrativo (ci_admin, nombre_admin, apellido_admin, contraseña_admin, cargo) VALUES
('12345678', 'Ezequiel', 'Matta', 'pass123', 'administrador'),
('23456789', 'Juan', 'Perez', 'pass456', 'empleado'),
('34567890', 'Maria', 'Gomez', 'pass789', 'empleado'),
('45678901', 'Ana', 'Lopez', 'pass101', 'empleado'),
('56789012', 'Carlos', 'Silva', 'pass202', 'empleado');

INSERT INTO categoria (id_categoria, nombre_cat) VALUES
('CAT1', 'Cardiologia'),
('CAT2', 'Pediatria'),
('CAT3', 'Traumatologia'),
('CAT4', 'Neurologia'),
('CAT5', 'Medicina General');

INSERT INTO carga (ci_admin, id_categoria, hora, fecha) VALUES
('12345678', 'CAT1', '08:00:00', '2026-01-10'),
('23456789', 'CAT2', '09:30:00', '2026-01-11'),
('34567890', 'CAT3', '11:15:00', '2026-01-12'),
('45678901', 'CAT4', '14:00:00', '2026-01-13'),
('56789012', 'CAT5', '16:45:00', '2026-01-14');

INSERT INTO documentos (id_documentos, ci_admin, id_categoria, titulo_docs, descripcion_docs) VALUES
('DOC1', '12345678', 'CAT1', 'Protocolo Cardiologia', 'Guia de urgencias cardiacas'),
('DOC2', '23456789', 'CAT2', 'Ficha Pediatrica', 'Registro de vacunacion'),
('DOC3', '34567890', 'CAT3', 'Informe Traumatologia', 'Inventario de protesis'),
('DOC4', '45678901', 'CAT4', 'Guia Neurologia', 'Procedimiento de EEG'),
('DOC5', '56789012', 'CAT5', 'Control Triaje', 'Manual de atencion rapida');

INSERT INTO encuesta (id_encuesta, id_categoria, descripcion_enc, titulo_enc) VALUES
('ENC1', 'CAT1', 'Evaluacion atencion en cardiologia', 'Satisfaccion Cardiologia'),
('ENC2', 'CAT2', 'Encuesta a padres en pediatria', 'Atencion Pediatrica'),
('ENC3', 'CAT3', 'Consulta sobre tiempos de espera', 'Tiempo Traumatologia'),
('ENC4', 'CAT4', 'Evaluacion de insumos medicos', 'Estado Equipos Neurologia'),
('ENC5', 'CAT5', 'Calidad de atencion general', 'Encuesta Medicina General');

INSERT INTO pregunta (id_pregunta, nombre_pregunta, tipo) VALUES
('PREG1', '¿Como evalua la atencion en el especialista?', 'opcion multiple'),
('PREG2', 'Describa la comodidad de la sala de espera', 'texto'),
('PREG3', '¿El medico explico bien el tratamiento?', 'opcion multiple'),
('PREG4', 'Escriba sugerencias para mejorar el servicio', 'texto'),
('PREG5', '¿Consiguio turno con rapidez?', 'opcion multiple');

INSERT INTO contiene (id_pregunta, id_encuesta) VALUES
('PREG1', 'ENC1'),
('PREG2', 'ENC1'),
('PREG3', 'ENC2'),
('PREG4', 'ENC3'),
('PREG5', 'ENC5');

INSERT INTO respuesta (id_respuesta, informacion, tipo) VALUES
('RESP1', 'Excelente', 'opcion multiple'),
('RESP2', 'Hacen falta mas asientos en el pasillo', 'texto'),
('RESP3', 'Bueno', 'opcion multiple'),
('RESP4', 'Agilizar la entrega de resultados', 'texto'),
('RESP5', 'Regular', 'opcion multiple');

INSERT INTO guarda (id_respuesta, id_pregunta) VALUES
('RESP1', 'PREG1'),
('RESP2', 'PREG2'),
('RESP3', 'PREG3'),
('RESP4', 'PREG4'),
('RESP5', 'PREG5');INSERT INTO administrativo (ci_admin, nombre_admin, apellido_admin, contraseña_admin, cargo) VALUES
('12345678', 'Ezequiel', 'Matta', 'pass123', 'administrador'),
('23456789', 'Juan', 'Perez', 'pass456', 'empleado'),
('34567890', 'Maria', 'Gomez', 'pass789', 'empleado'),
('45678901', 'Ana', 'Lopez', 'pass101', 'empleado'),
('56789012', 'Carlos', 'Silva', 'pass202', 'empleado');

INSERT INTO categoria (id_categoria, nombre_cat) VALUES
('CAT1', 'Cardiologia'),
('CAT2', 'Pediatria'),
('CAT3', 'Traumatologia'),
('CAT4', 'Neurologia'),
('CAT5', 'Medicina General');

INSERT INTO carga (ci_admin, id_categoria, hora, fecha) VALUES
('12345678', 'CAT1', '08:00:00', '2026-01-10'),
('23456789', 'CAT2', '09:30:00', '2026-01-11'),
('34567890', 'CAT3', '11:15:00', '2026-01-12'),
('45678901', 'CAT4', '14:00:00', '2026-01-13'),
('56789012', 'CAT5', '16:45:00', '2026-01-14');

INSERT INTO documentos (id_documentos, ci_admin, id_categoria, titulo_docs, descripcion_docs) VALUES
('DOC1', '12345678', 'CAT1', 'Protocolo Cardiologia', 'Guia de urgencias cardiacas'),
('DOC2', '23456789', 'CAT2', 'Ficha Pediatrica', 'Registro de vacunacion'),
('DOC3', '34567890', 'CAT3', 'Informe Traumatologia', 'Inventario de protesis'),
('DOC4', '45678901', 'CAT4', 'Guia Neurologia', 'Procedimiento de EEG'),
('DOC5', '56789012', 'CAT5', 'Control Triaje', 'Manual de atencion rapida');

INSERT INTO encuesta (id_encuesta, id_categoria, descripcion_enc, titulo_enc) VALUES
('ENC1', 'CAT1', 'Evaluacion atencion en cardiologia', 'Satisfaccion Cardiologia'),
('ENC2', 'CAT2', 'Encuesta a padres en pediatria', 'Atencion Pediatrica'),
('ENC3', 'CAT3', 'Consulta sobre tiempos de espera', 'Tiempo Traumatologia'),
('ENC4', 'CAT4', 'Evaluacion de insumos medicos', 'Estado Equipos Neurologia'),
('ENC5', 'CAT5', 'Calidad de atencion general', 'Encuesta Medicina General');

INSERT INTO pregunta (id_pregunta, nombre_pregunta, tipo) VALUES
('PREG1', '¿Como evalua la atencion en el especialista?', 'opcion multiple'),
('PREG2', 'Describa la comodidad de la sala de espera', 'texto'),
('PREG3', '¿El medico explico bien el tratamiento?', 'opcion multiple'),
('PREG4', 'Escriba sugerencias para mejorar el servicio', 'texto'),
('PREG5', '¿Consiguio turno con rapidez?', 'opcion multiple');

INSERT INTO contiene (id_pregunta, id_encuesta) VALUES
('PREG1', 'ENC1'),
('PREG2', 'ENC1'),
('PREG3', 'ENC2'),
('PREG4', 'ENC3'),
('PREG5', 'ENC5');

INSERT INTO respuesta (id_respuesta, informacion, tipo) VALUES
('RESP1', 'Excelente', 'opcion multiple'),
('RESP2', 'Hacen falta mas asientos en el pasillo', 'texto'),
('RESP3', 'Bueno', 'opcion multiple'),
('RESP4', 'Agilizar la entrega de resultados', 'texto'),
('RESP5', 'Regular', 'opcion multiple');

INSERT INTO guarda (id_respuesta, id_pregunta) VALUES
('RESP1', 'PREG1'),
('RESP2', 'PREG2'),
('RESP3', 'PREG3'),
('RESP4', 'PREG4'),
('RESP5', 'PREG5');











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
    camilla BOOLEAN NOT NULL
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

CREATE TABLE enfermero (
    ci_enfermero VARCHAR(8) PRIMARY KEY NOT NULL,
    nombre_enfermero VARCHAR(50) NOT NULL,
    apellido_enfermero VARCHAR(50) NOT NULL
);

CREATE TABLE transportable (
    id_transportable VARCHAR(50) PRIMARY KEY NOT NULL,
    habitacion_retiro VARCHAR(50) NOT NULL,
    piso_retiro VARCHAR(50) NOT NULL
);

CREATE TABLE transporta (
    id_transportable VARCHAR(50) NOT NULL,
    ci_enfermero VARCHAR(8) NOT NULL,
    PRIMARY KEY (id_transportable, ci_enfermero),
    FOREIGN KEY (id_transportable) REFERENCES transportable(id_transportable),
    FOREIGN KEY (ci_enfermero) REFERENCES enfermero(ci_enfermero)
);

CREATE TABLE paciente (
    id_transportable VARCHAR(50) PRIMARY KEY NOT NULL,
    ci_paciente VARCHAR(8) NOT NULL,
    nombre_paciente VARCHAR(50) NOT NULL,
    accesorio ENUM('oxigeno', 'silla de ruedas', 'baston', 'otro') NOT NULL,
    oxigeno BOOLEAN NOT NULL,
    aislamiento BOOLEAN NOT NULL,
    FOREIGN KEY (id_transportable) REFERENCES transportable(id_transportable)
);

CREATE TABLE elemento (
    id_transportable VARCHAR(50) PRIMARY KEY NOT NULL,
    nombre_elemento VARCHAR(50) NOT NULL,
    descripcion_elemento VARCHAR(50) NOT NULL,
    tipo_elemento ENUM('organo', 'instrumento', 'otro') NOT NULL,
    FOREIGN KEY (id_transportable) REFERENCES transportable(id_transportable)
);

CREATE TABLE traslado (
    id_traslado VARCHAR(50) PRIMARY KEY NOT NULL,
    ci_chofer VARCHAR(8) NOT NULL,
    matricula VARCHAR(15) NOT NULL,
    id_destino VARCHAR(50) NOT NULL,
    ci_enfermero VARCHAR(8) NOT NULL,
    fecha_traslado DATE NOT NULL,
    hora_salida TIME NOT NULL,
    hora_llegada TIME NOT NULL,
    lugar_origen VARCHAR(50) NOT NULL,
    estado ENUM('Disponible', 'No Disponible') NOT NULL,
    FOREIGN KEY (ci_chofer) REFERENCES chofer(ci_chofer),
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula),
    FOREIGN KEY (id_destino) REFERENCES destino(id_destino),
    FOREIGN KEY (ci_enfermero) REFERENCES enfermero(ci_enfermero)
);


INSERT INTO chofer (ci_chofer, nombre_chofer, apellido_chofer, telefono) VALUES
('12345678', 'Ezequiel', 'Matta', '099123456'),
('22222222', 'Roberto', 'Gomez', '099234567'),
('33333333', 'Gonzalo', 'Rodriguez', '099345678'),
('44444444', 'Martin', 'Fernandez', '099456789'),
('55555555', 'Diego', 'Morales', '099567890');

INSERT INTO vehiculo (matricula, modelo, capacidad, tipo_vehiculo, camilla) VALUES
('SCH1234', 'Mercedes Benz Sprinter', '4', 'ambulancia', TRUE),
('SAB5678', 'Peugeot Partner', '2', 'auto', FALSE),
('SBX9012', 'Renault Master', '4', 'ambulancia', TRUE),
('SCA3456', 'Fiat Fiorino', '2', 'auto', FALSE),
('SDY7890', 'Volkswagen Delivery', '3', 'otro', FALSE);

INSERT INTO conduce (ci_chofer, matricula) VALUES
('12345678', 'SCH1234'),
('22222222', 'SAB5678'),
('33333333', 'SBX9012'),
('44444444', 'SCA3456'),
('55555555', 'SDY7890');

INSERT INTO destino (id_destino, nombre_destino, direccion, ruta) VALUES
('DEST1', 'Sanatorio Americano', 'Av. Italia 2420', 'Ruta Central'),
('DEST2', 'Hospital Maciel', '25 de Mayo 174', 'Ruta Sur'),
('DEST3', 'Hospital Pasteur', 'Larrañaga 2580', 'Ruta Este'),
('DEST4', 'Hospital Pereira Rossell', 'Bulevar Artigas 1550', 'Ruta Centro'),
('DEST5', 'Sanatorio CRAMI', 'Rocha 123', 'Ruta Interbalnearia');

INSERT INTO enfermero (ci_enfermero, nombre_enfermero, apellido_enfermero) VALUES
('66666666', 'Laura', 'Martinez'),
('77777777', 'Lucia', 'Suarez'),
('88888888', 'Sofia', 'Pereyra'),
('99999999', 'Valeria', 'Rios'),
('10101010', 'Camila', 'Silva');

INSERT INTO transportable (id_transportable, habitacion_retiro, piso_retiro) VALUES
('TR1', '101', 'Piso 1'),
('TR2', '205', 'Piso 2'),
('TR3', '312', 'Piso 3'),
('TR4', '408', 'Piso 4'),
('TR5', '502', 'Piso 5');

INSERT INTO transporta (id_transportable, ci_enfermero) VALUES
('TR1', '66666666'),
('TR2', '77777777'),
('TR3', '88888888'),
('TR4', '99999999'),
('TR5', '10101010');

INSERT INTO paciente (id_transportable, ci_paciente, nombre_paciente, accesorio, oxigeno, aislamiento) VALUES
('TR1', '12121212', 'Carlos Pereira', 'silla de ruedas', FALSE, FALSE),
('TR2', '13131313', 'Maria Delgado', 'oxigeno', TRUE, TRUE),
('TR3', '14141414', 'Jorge Gutierrez', 'baston', FALSE, FALSE),
('TR4', '15151515', 'Ana Torres', 'otro', TRUE, FALSE),
('TR5', '16161616', 'Luis Fernandez', 'silla de ruedas', FALSE, TRUE);

INSERT INTO elemento (id_transportable, nombre_elemento, descripcion_elemento, tipo_elemento) VALUES
('TR1', 'Caja Termica Corazon', 'Transporte para trasplante organico', 'organo'),
('TR2', 'Kit Cirugia Cardiaca', 'Caja de bisturis y pinzas', 'instrumento'),
('TR3', 'Monitor Multiparametrico', 'Equipo de monitoreo de signos vitales', 'otro'),
('TR4', 'Set de Traqueostomia', 'Material esteril urgente', 'instrumento'),
('TR5', 'Caja Termica Riñon', 'Trasplante renal urgente', 'organo');

INSERT INTO traslado (id_traslado, ci_chofer, matricula, id_destino, ci_enfermero, fecha_traslado, hora_salida, hora_llegada, lugar_origen, estado) VALUES
('TRAS1', '12345678', 'SCH1234', 'DEST1', '66666666', '2026-08-01', '08:00:00', '08:45:00', 'Hospital de Clinicas', 'No Disponible'),
('TRAS2', '22222222', 'SAB5678', 'DEST2', '77777777', '2026-08-02', '09:30:00', '10:15:00', 'Hospital de Clinicas', 'No Disponible'),
('TRAS3', '33333333', 'SBX9012', 'DEST3', '88888888', '2026-08-03', '11:00:00', '11:30:00', 'Hospital de Clinicas', 'Disponible'),
('TRAS4', '44444444', 'SCA3456', 'DEST4', '99999999', '2026-08-04', '14:15:00', '15:00:00', 'Hospital de Clinicas', 'Disponible'),
('TRAS5', '55555555', 'SDY7890', 'DEST5', '10101010', '2026-08-05', '16:00:00', '17:20:00', 'Hospital de Clinicas', 'Disponible');