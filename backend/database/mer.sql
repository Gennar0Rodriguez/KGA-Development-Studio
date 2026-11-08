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
('23456789', 'Gennaro', 'Rodriguez', 'gen', 'empleado'),
('34567890', 'Ari', 'Matta', 'Eze', 'administrador'),
('45678901', 'Ana', 'Rodriguez', 'clave123', 'empleado'),
('56789012', 'Luis', 'Martinez', 'pwd321', 'empleado');

INSERT INTO categoria (id_categoria, nombre_cat, tipo_cat, estado_cat) VALUES
('cat_doc_1', 'Manuales', 'docs', 'activa'),
('cat_doc_2', 'Protocolos', 'docs', 'activa'),
('cat_enc_1', 'Satisfaccion', 'encuestas', 'activa'),
('cat_enc_2', 'Calidad de Servicio', 'encuestas', 'activa'),
('cat_doc_3', 'Reglamentos', 'docs', 'deshabilitada');

INSERT INTO documentos (id_documentos, ci_admin, id_categoria, hora_carga, fecha_carga, titulo_docs, descripcion_docs, nombre_archivo, ruta_archivo) VALUES
('doc_001', '12345678', 'cat_doc_1', '08:00:00', '2026-06-01', 'Manual General', 'Manual operativo', 'manual.pdf', '/docs/manual.pdf'),
('doc_002', '12345678', 'cat_doc_2', '09:30:00', '2026-06-02', 'Protocolo COVID', 'Normas sanitarias', 'protocolo.pdf', '/docs/protocolo.pdf'),
('doc_003', '34567890', 'cat_doc_1', '10:15:00', '2026-06-03', 'Guia Rapida', 'Guia para nuevos', 'guia.pdf', '/docs/guia.pdf'),
('doc_004', '34567890', 'cat_doc_2', '11:00:00', '2026-06-04', 'Protocolo Urgencias', 'Atencion rapida', 'urgencias.pdf', '/docs/urgencias.pdf'),
('doc_005', '12345678', 'cat_doc_3', '12:00:00', '2026-06-05', 'Reglamento Antiguo', 'Version pasada', 'reglamento.pdf', '/docs/reglamento.pdf');

INSERT INTO encuesta (titulo_enc, id_categoria, descripcion_enc) VALUES
('Encuesta Satisfaccion 2026', 'cat_enc_1', 'Evaluacion anual'),
('Calidad Atencion', 'cat_enc_2', 'Servicio de traslado'),
('Clima Laboral', 'cat_enc_1', 'Ambiente de trabajo'),
('Evaluacion Choferes', 'cat_enc_2', 'Desempeno en ruta'),
('Feedback General', 'cat_enc_1', 'Opinion general');

INSERT INTO pregunta (id_pregunta, titulo_enc, id_categoria, nombre_pregunta, tipo) VALUES
('p_01', 'Encuesta Satisfaccion 2026', 'cat_enc_1', 'Como califica el servicio?', 'opcion multiple'),
('p_02', 'Encuesta Satisfaccion 2026', 'cat_enc_1', 'Comentarios adicionales', 'texto'),
('p_01', 'Calidad Atencion', 'cat_enc_2', 'Fue puntual?', 'opcion multiple'),
('p_01', 'Clima Laboral', 'cat_enc_1', 'Se siente comodo?', 'opcion multiple'),
('p_01', 'Feedback General', 'cat_enc_1', 'Sugerencias', 'texto');

INSERT INTO respuesta (id_respuesta, id_pregunta, titulo_enc, id_categoria, informacion, tipo) VALUES
('r_01', 'p_01', 'Encuesta Satisfaccion 2026', 'cat_enc_1', 'Excelente', 'opcion multiple'),
('r_02', 'p_02', 'Encuesta Satisfaccion 2026', 'cat_enc_1', 'Todo muy bien', 'texto'),
('r_03', 'p_01', 'Calidad Atencion', 'cat_enc_2', 'Si', 'opcion multiple'),
('r_04', 'p_01', 'Clima Laboral', 'cat_enc_1', 'Siempre', 'opcion multiple'),
('r_05', 'p_01', 'Feedback General', 'cat_enc_1', 'Ninguna', 'texto');

INSERT INTO chofer (ci_chofer, nombre_chofer, apellido_chofer, telefono) VALUES
('11111111', 'Pedro', 'Gomez', '099123456'),
('22222222', 'Sofia', 'Fernandez', '098234567'),
('33333333', 'Jorge', 'Silva', '097345678'),
('44444444', 'Lucia', 'Ramirez', '096456789'),
('55555555', 'Martin', 'Acosta', '095567890');

INSERT INTO vehiculo (matricula, modelo, capacidad, tipo_vehiculo, camilla, observaciones) VALUES
('ABC1234', 'Sprinter', '3', 'ambulancia', 1, 'Equipada'),
('DEF5678', 'Kangoo', '2', 'auto', 0, 'Sin camilla'),
('GHI9012', 'Transit', '4', 'ambulancia', 1, 'Unidad movil 2'),
('JKL3456', 'Corolla', '4', 'auto', 0, 'Auto administrativo'),
('MNO7890', 'Master', '3', 'otro', 0, 'Utilitario');

INSERT INTO conduce (ci_chofer, matricula) VALUES
('11111111', 'ABC1234'),
('22222222', 'DEF5678'),
('33333333', 'GHI9012'),
('44444444', 'JKL3456'),
('55555555', 'MNO7890');

INSERT INTO destino (id_destino, nombre_destino, direccion, ruta) VALUES
('dest_01', 'Hospital Central', 'Av. Italia 1234', 'Ruta 1'),
('dest_02', 'Clinica Norte', 'Bv. Artigas 567', 'Ruta 5'),
('dest_03', 'Sanatorio Sur', '8 de Octubre 890', 'Ruta 8'),
('dest_04', 'Centro Medico', 'Garzon 432', 'Ruta 6'),
('dest_05', 'Hospital de Clinicas', 'Italia s/n', 'Ruta 10');

INSERT INTO traslado (id_traslado, ci_chofer, matricula, id_destino, fecha_traslado, fecha_llegada, hora_salida, hora_llegada, lugar_origen, estado_traslado, ci_solicitante, estado_solicitud, observaciones) VALUES
('tras_01', '11111111', 'ABC1234', 'dest_01', '2026-06-01', '2026-06-01', '08:00:00', '08:30:00', 'Casa Central', 'finalizado', '12345678', 'aprobado', 'Sin novedad'),
('tras_02', '22222222', 'DEF5678', 'dest_02', '2026-06-02', '2026-06-02', '09:00:00', '09:45:00', 'Sucursal Norte', 'aprobado', '23456789', 'aprobado', 'Trafico denso'),
('tras_03', '33333333', 'GHI9012', 'dest_03', '2026-06-03', '2026-06-03', '10:00:00', '10:20:00', 'Base Sur', 'en proceso', '34567890', 'pendiente', 'En curso'),
('tras_04', '44444444', 'JKL3456', 'dest_04', '2026-06-04', NULL, '11:00:00', NULL, 'Oficina Este', 'pendiente', '45678901', 'pendiente', 'Esperando aprobacion'),
('tras_05', '55555555', 'MNO7890', 'dest_05', '2026-06-05', '2026-06-05', '12:00:00', '12:50:00', 'Estacion Central', 'denegado', '56789012', 'rechazado', 'Cancelado por clima');

INSERT INTO notifica (numero_notificacion, id_traslado, ci_chofer, matricula, id_destino, ci_admin, ci_emisor, fecha_envio, leido, titulo_not, descripcion_not, ci_receptor) VALUES
(1, 'tras_01', '11111111', 'ABC1234', 'dest_01', '12345678', '12345678', '2026-06-01 08:00:00', 1, 'Traslado Finalizado', 'El traslado se completo con exito', '11111111'),
(2, 'tras_02', '22222222', 'DEF5678', 'dest_02', '12345678', '12345678', '2026-06-02 09:00:00', 0, 'Nuevo Traslado', 'Tienes un traslado aprobado', '22222222'),
(3, 'tras_03', '33333333', 'GHI9012', 'dest_03', '34567890', '34567890', '2026-06-03 10:00:00', 0, 'En Proceso', 'Traslado en curso', '33333333'),
(4, 'tras_04', '44444444', 'JKL3456', 'dest_04', '34567890', '34567890', '2026-06-04 11:00:00', 1, 'Solicitud Pendiente', 'Su solicitud esta pendiente', '44444444'),
(5, 'tras_05', '55555555', 'MNO7890', 'dest_05', '12345678', '12345678', '2026-06-05 12:00:00', 1, 'Traslado Denegado', 'El traslado fue rechazado', '55555555');

INSERT INTO enfermero (ci_enfermero, nombre_enfermero, apellido_enfermero) VALUES
('66666666', 'Carmen', 'Perez'),
('77777777', 'Roberto', 'Diaz'),
('88888888', 'Elena', 'Suarez'),
('99999999', 'Gabriel', 'Morales'),
('10101010', 'Valeria', 'Rios');

INSERT INTO transportable (id_transportable, ci_enfermero, habitacion_retiro, piso_retiro) VALUES
('trans_01', '66666666', 'Hab 101', 'Piso 1'),
('trans_02', '77777777', 'Hab 202', 'Piso 2'),
('trans_03', '88888888', 'Hab 303', 'Piso 3'),
('trans_04', '99999999', 'Hab 404', 'Piso 4'),
('trans_05', '10101010', 'Hab 505', 'Piso 5'),
('trans_06', '66666666', 'Lab Central', 'Planta Baja'),
('trans_07', '77777777', 'Farmacia', 'Piso 1'),
('trans_08', '88888888', 'Banco Sangre', 'Subsuelo'),
('trans_09', '99999999', 'Archivo', 'Piso 3'),
('trans_10', '10101010', 'Quirofano', 'Piso 4');

INSERT INTO paciente (id_transportable, ci_paciente, nombre_paciente, accesorio, oxigeno, aislamiento) VALUES
('trans_01', '12121212', 'Juan Perez', 'silla', 1, 0),
('trans_02', '13131313', 'Maria Lopez', 'baston', 0, 1),
('trans_03', '14141414', 'Carlos Gomez', 'otro', 1, 1),
('trans_04', '15151515', 'Ana Rodriguez', 'silla', 0, 0),
('trans_05', '16161616', 'Luis Martinez', 'baston', 1, 0);

INSERT INTO elemento (id_transportable, nombre_elemento, descripcion_elemento, tipo_elemento) VALUES
('trans_06', 'Riñones', 'Organo para trasplante', 'organo'),
('trans_07', 'Historial Medico', 'Documentacion confidencial', 'documentacion'),
('trans_08', 'Sangre Tipo A', 'Unidades de sangre', 'otro'),
('trans_09', 'Consentimiento', 'Fichas firmadas', 'documentacion'),
('trans_10', 'Corazon', 'Organo refrigerado', 'organo');

INSERT INTO transporta (id_transportable, id_traslado, ci_chofer, matricula, id_destino) VALUES
('trans_01', 'tras_01', '11111111', 'ABC1234', 'dest_01'),
('trans_02', 'tras_02', '22222222', 'DEF5678', 'dest_02'),
('trans_03', 'tras_03', '33333333', 'GHI9012', 'dest_03'),
('trans_04', 'tras_04', '44444444', 'JKL3456', 'dest_04'),
('trans_05', 'tras_05', '55555555', 'MNO7890', 'dest_05');
