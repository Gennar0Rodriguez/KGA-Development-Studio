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
    nombre_cat VARCHAR(50) NOT NULL
    tipo_cat ENUM ('docs', 'encuestas')
);

CREATE TABLE documentos (
    id_documentos VARCHAR(50) PRIMARY KEY NOT NULL,
    ci_admin VARCHAR(50),
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
    ci_admin VARCHAR(8) NOT NULL,
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

INSERT INTO administrativo VALUES
('12345678', 'Ezequiel', 'Matta', 'Ari', 'administrador'),
('87654321', 'Juan', 'Gomez', '123', 'empleado'),
('11223344', 'Carlos', 'Perez', '456', 'empleado'),
('55667788', 'Marichal', 'Rodriguez', '789', 'administrador'),
('99887766', 'Juan', 'Lopez', '222', 'empleado');

INSERT INTO categoria VALUES
('CAT01', 'Urgencias'),
('CAT02', 'Pediatria'),
('CAT03', 'Cirugia General'),
('CAT04', 'Farmacia'),
('CAT05', 'Laboratorio Clinico');

INSERT INTO documentos VALUES
('DOC01', '12345678', 'CAT01', '08:30:00', '2026-06-01', 'Protocolo Triaje', 'Protocolo de recepcion de emergencias', 'triaje.pdf', '/docs/urgencias/'),
('DOC02', '87654321', 'CAT02', '09:15:00', '2026-06-02', 'Historial Pediatrico', 'Guia de controles pediatricos', 'pediatria.pdf', '/docs/pediatria/'),
('DOC03', '11223344', 'CAT03', '10:00:00', '2026-06-03', 'Checklist Quirurgico', 'Lista de verificacion de quirofano', 'quirofano.pdf', '/docs/cirugia/'),
('DOC04', '55667788', 'CAT04', '11:20:00', '2026-06-04', 'Inventario Insumos', 'Stock de medicamentos del mes', 'farmacia.pdf', '/docs/farmacia/'),
('DOC05', '99887766', 'CAT05', '14:00:00', '2026-06-05', 'Manual Muestras', 'Procedimiento de analiticas de sangre', 'analisis.pdf', '/docs/laboratorio/');

INSERT INTO encuesta VALUES
('ENC01', 'CAT01', 'Tiempo de atencion en guardia', 'Satisfaccion Urgencias'),
('ENC02', 'CAT02', 'Trato del personal medico infantil', 'Atencion Pediatrica'),
('ENC03', 'CAT03', 'Experiencia pre y post operatoria', 'Calidad Quirurgica'),
('ENC04', 'CAT04', 'Disponibilidad de medicamentos', 'Evaluacion Farmacia'),
('ENC05', 'CAT05', 'Entrega de resultados de analisis', 'Servicio Laboratorio');

INSERT INTO pregunta VALUES
('PRG01', 'ENC01', 'CAT01', '¿El tiempo de espera en guardia fue adecuado?', 'opcion multiple'),
('PRG02', 'ENC02', 'CAT02', 'Comentarios sobre el trato a los niños', 'texto'),
('PRG03', 'ENC03', 'CAT03', '¿Recibio informacion clara sobre su cirugia?', 'opcion multiple'),
('PRG04', 'ENC04', 'CAT04', 'Detalle sugerencias sobre la entrega de remedios', 'texto'),
('PRG05', 'ENC05', 'CAT05', '¿Los resultados se entregaron en fecha?', 'opcion multiple');

INSERT INTO respuesta VALUES
('RES01', 'PRG01', 'ENC01', 'CAT01', 'Si, fue muy rapido', 'opcion multiple'),
('RES02', 'PRG02', 'ENC02', 'CAT02', 'Excelente contencion del medico', 'texto'),
('RES03', 'PRG03', 'ENC03', 'CAT03', 'Si, todo me quedo claro', 'opcion multiple'),
('RES04', 'PRG04', 'ENC04', 'CAT04', 'Faltaba un remedio recetado', 'texto'),
('RES05', 'PRG05', 'ENC05', 'CAT05', 'Si, llegaron a tiempo', 'opcion multiple');

INSERT INTO chofer VALUES
('45678912', 'Esteban', 'Silva', '099123456'),
('32165498', 'Marcelo', 'Rios', '098654321'),
('78912345', 'Gabriel', 'Benitez', '097111222'),
('65498732', 'Diego', 'Fagundez', '096333444'),
('14725836', 'Fernando', 'Acosta', '095555666');

INSERT INTO vehiculo VALUES
('SCA1001', 'Mercedes Sprinter', '4', 'ambulancia', 1, 'Equipamiento UTI movil'),
('SCA1002', 'Fiat Ducato', '3', 'ambulancia', 1, 'Oxigeno disponible'),
('SCA1003', 'Renault Kangoo', '1', 'auto', 0, 'Traslado de personal o insumos'),
('SCA1004', 'Peugeot Partner', '1', 'auto', 0, 'Documentacion urgente'),
('SCA1005', 'Ford Fiesta-', '6', 'otro', 1, 'Minibus de traslado sanitario');

INSERT INTO conduce VALUES
('45678912', 'SCA1001'),
('32165498', 'SCA1002'),
('78912345', 'SCA1003'),
('65498732', 'SCA1004'),
('14725836', 'SCA1005');

INSERT INTO destino VALUES
('DES01', 'Hospital de Clinicas', 'Av. Italia s/n', 'Ruta 1'),
('DES02', 'Sanatorio Americano', 'Av. 8 de Octubre 2720', 'Ruta 2'),
('DES03', 'Hospital Maciel', '25 de Mayo 174', 'Ruta 3'),
('DES04', 'Centro de Asistencia Medica', 'Bv. Artigas 1550', 'Ruta 4'),
('DES05', 'Sanatorio Britanico', 'Avenida General Garibaldi 2764', 'Ruta 5');

INSERT INTO traslado VALUES
('TRS01', '45678912', 'SCA1001', 'DES01', '2026-06-01', '08:00:00', '08:45:00', 'Hospital Central', 'finalizado', '88888888', 'aprobado', 'Traslado de paciente critico'),
('TRS02', '32165498', 'SCA1002', 'DES02', '2026-06-02', '09:30:00', '10:15:00', 'Hospital Central', 'aprobado', '55667788', 'aprobado', 'Derivacion a cuidados moderados'),
('TRS03', '78912345', 'SCA1003', 'DES03', '2026-06-03', '11:00:00', '11:30:00', 'Hospital Central', 'en proceso', '11111111', 'aprobado', 'Envio de documentacion medica'),
('TRS04', '65498732', 'SCA1004', 'DES04', '2026-06-04', '13:00:00', '13:40:00', 'Hospital Central', 'pendiente', '55667788', 'pendiente', 'Solicitud en evaluacion'),
('TRS05', '14725836', 'SCA1005', 'DES05', '2026-06-05', '15:00:00', '15:50:00', 'Hospital Central', 'denegado', '22222222', 'rechazado', 'Suspendido por centro receptor');

INSERT INTO notifica VALUES
('TRS01', '45678912', 'SCA1001', 'DES01', '12345678', '12345678', '2026-06-01', 1, 'Asignación de Urgencia', 'Se ha asignado el traslado crítico al Hospital de Clínicas.', '87654321'),
('TRS02', '32165498', 'SCA1002', 'DES02', '55667788', '55667788', '2026-06-02', 1, 'Derivación Aprobada', 'Derivación a cuidados moderados aprobada por el sanatorio.', '11223344'),
('TRS03', '78912345', 'SCA1003', 'DES03', '12345678', '12345678', '2026-06-03', 0, 'Envío de Documentación', 'Documentación médica en camino hacia el Hospital Maciel.', '99887766'),
('TRS04', '65498732', 'SCA1004', 'DES04', '55667788', '55667788', '2026-06-04', 0, 'Solicitud Pendiente', 'La solicitud de traslado está bajo evaluación médica.', '87654321'),
('TRS05', '14725836', 'SCA1005', 'DES05', '12345678', '12345678', '2026-06-05', 1, 'Traslado Suspendido', 'El centro receptor ha suspendido la recepción temporalmente.', '11223344');

INSERT INTO enfermero VALUES
('22334455', 'Agustina', 'Duarte'),
('33445566', 'Matias', 'Suarez'),
('44556677', 'Sofia', 'Pereira'),
('55667788', 'Jefrery', 'Pereyra'),
('66778899', 'Bennet', 'Impact');

INSERT INTO transportable VALUES
('TRP01', '22334455', 'Habitacion 204', 'Piso 2'),
('TRP02', '33445566', 'Habitacion 310', 'Piso 3'),
('TRP03', '44556677', 'Sala de Esterilizacion', 'Planta Baja'),
('TRP04', '55667788', 'Habitacion 105', 'Piso 1'),
('TRP05', '66778899', 'Laboratorio Central', 'Subsuelo'),
('TRP06', '22334455', 'Cti Adultos', 'Piso 4'),
('TRP07', '33445566', 'Archivo Central', 'Subsuelo'),
('TRP08', '44556677', 'Farmacia', 'Planta Baja');

INSERT INTO paciente VALUES
('TRP01', '98765432', 'Roberto Gomez', 'silla', 1, 0),
('TRP02', '87654321', 'Ezequiel Matta Rodriguez', 'baston', 0, 1),
('TRP04', '76543210', 'Jorge Ramirez', 'otro', 1, 0);

INSERT INTO elemento VALUES
('TRP03', 'Valvula Cardiaca', 'Valvula biologica para cirugia cardiovascular', 'organo'),
('TRP05', 'Historias Clinicas Legales', 'Carpeta con expedientes archivados', 'documentacion'),
('TRP06', 'Riñon para Transplante', 'Tejido vivo conservado en frio', 'organo'),
('TRP07', 'Expedientes de Auditoria', 'Documentacion confidencial de pacientes', 'documentacion'),
('TRP08', 'Insumos Quirurgicos Especiales', 'Instrumental esterilizado de alta complejidad', 'otro');

INSERT INTO transporta VALUES
('TRP01', 'TRS01', '45678912', 'SCA1001', 'DES01'),
('TRP02', 'TRS02', '32165498', 'SCA1002', 'DES02'),
('TRP03', 'TRS03', '78912345', 'SCA1003', 'DES03'),
('TRP04', 'TRS04', '65498732', 'SCA1004', 'DES04'),
('TRP05', 'TRS05', '14725836', 'SCA1005', 'DES05');