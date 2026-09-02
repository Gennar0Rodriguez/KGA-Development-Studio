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
    );

    CREATE TABLE documentos (
        id_documentos VARCHAR(50) PRIMARY KEY NOT NULL,
        id_categoria VARCHAR(50),
        titulo_docs VARCHAR(50) NOT NULL,
        descripcion_docs VARCHAR(50),
        FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
    );

    CREATE TABLE carga(
        ci_admin VARCHAR(8) NOT NULL,
        id_documentos VARCHAR(50) NOT NULL,
        hora TIME NOT NULL,
        fecha DATE NOT NULL,
        PRIMARY KEY (ci_admin, id_documentos),
        FOREIGN KEY (ci_admin) REFERENCES administrativo(ci_admin) ON DELETE CASCADE,
        FOREIGN KEY (id_documentos) REFERENCES documentos(id_documentos)
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
        nombre_pregunta VARCHAR(50) NOT NULL,
        tipo ENUM('opcion multiple', 'texto') NOT NULL,
        PRIMARY KEY (id_pregunta, id_encuesta),
        FOREIGN KEY (id_encuesta) REFERENCES encuesta(id_encuesta)
    );


    CREATE TABLE respuesta (
        id_respuesta VARCHAR(50) NOT NULL,
        id_pregunta VARCHAR(50) NOT NULL,
        id_encuesta VARCHAR(50) NOT NULL,
        informacion VARCHAR(50),
        tipo ENUM('opcion multiple', 'texto') NOT NULL,
        PRIMARY KEY (id_respuesta, id_pregunta, id_encuesta),
        FOREIGN KEY (id_pregunta) REFERENCES pregunta(id_pregunta),
        FOREIGN KEY (id_encuesta) REFERENCES encuesta(id_encuesta)
    );

INSERT INTO administrativo (ci_admin, nombre_admin, apellido_admin, contraseña_admin, cargo) VALUES
('11111111', 'Ezequiel', 'Matta', 'ari', 'administrador'),
('22222222', 'Laura', 'Gomez', '123', 'empleado'),
('33333333', 'Carlos', 'Perez', '456', 'empleado'),
('44444444', 'Ana', 'Rios', '789', 'empleado'),
('55555555', 'Jorge', 'Sosa', 'jorge', 'empleado');

INSERT INTO categoria (id_categoria, nombre_cat) VALUES
('C1', 'Recursos Humanos'),
('C2', 'Logistica y Flota'),
('C3', 'Calidad y Normativas'),
('C4', 'Administracion'),
('C5', 'Atencion al Paciente');

INSERT INTO documentos (id_documentos, id_categoria, titulo_docs, descripcion_docs) VALUES
('DOC1', 'C1', 'Manual de Empleado', 'Normas generales del personal'),
('DOC2', 'C2', 'Guia de Traslados', 'Protocolo logistico de ambulancias'),
('DOC3', 'C3', 'Estandares de Calidad', 'Normas y procedimientos ISO'),
('DOC4', 'C4', 'Balance Mensual', 'Reporte financiero mensual'),
('DOC5', 'C5', 'Protocolo de Ingreso', 'Actualizacion de normas de atencion');

INSERT INTO carga (ci_admin, id_documentos, hora, fecha) VALUES
('11111111', 'DOC1', '10:00:00', '2026-08-01'),
('22222222', 'DOC2', '11:15:00', '2026-08-05'),
('33333333', 'DOC3', '14:30:00', '2026-08-10'),
('44444444', 'DOC4', '09:45:00', '2026-08-15'),
('11111111', 'DOC5', '16:20:00', '2026-08-20');

INSERT INTO encuesta (id_encuesta, id_categoria, descripcion_enc, titulo_enc) VALUES
('E1', 'C1', 'Satisfaccion anual', 'Clima Laboral'),
('E2', 'C2', 'Estado vehicular actual', 'Evaluacion de Flota'),
('E3', 'C3', 'Procesos medicos', 'Auditoria Interna'),
('E4', 'C5', 'Atencion recibida', 'Satisfaccion de Pacientes'),
('E5', 'C4', 'Mejoras en la oficina', 'Buzon de Sugerencias');

INSERT INTO pregunta (id_pregunta, id_encuesta, nombre_pregunta, tipo) VALUES
('P1', 'E1', 'Como califica su entorno de trabajo?', 'opcion multiple'),
('P2', 'E2', 'Describa cualquier falla vehicular notada', 'texto'),
('P3', 'E3', 'Se cumplio el protocolo al 100%?', 'opcion multiple'),
('P4', 'E4', 'Deje una sugerencia para mejorar la atencion', 'texto'),
('P5', 'E5', 'Del 1 al 5, evalue las instalaciones', 'opcion multiple');

INSERT INTO respuesta (id_respuesta, id_pregunta, id_encuesta, informacion, tipo) VALUES
('R1', 'P1', 'E1', 'Excelente', 'opcion multiple'),
('R2', 'P2', 'E2', 'Frenos un poco largos en la SAA1234', 'texto'),
('R3', 'P3', 'E3', 'Si, completamente', 'opcion multiple'),
('R4', 'P4', 'E4', 'Deberian mejorar los tiempos de espera', 'texto'),
('R5', 'P5', 'E5', '5', 'opcion multiple');








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

CREATE TABLE traslado (
    id_traslado VARCHAR(50) PRIMARY KEY NOT NULL,
    ci_chofer VARCHAR(8) NOT NULL,
    matricula VARCHAR(15) NOT NULL,
    id_destino VARCHAR(50) NOT NULL,
    fecha_traslado DATE NOT NULL,
    hora_salida TIME,
    hora_llegada TIME,
    lugar_origen VARCHAR(50) NOT NULL,
    estado ENUM('aprobado', 'denegado', 'en proceso') NOT NULL,
    FOREIGN KEY (ci_chofer) REFERENCES chofer(ci_chofer),
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula),
    FOREIGN KEY (id_destino) REFERENCES destino(id_destino)
);

CREATE TABLE transportable (
    id_transportable VARCHAR(50) PRIMARY KEY NOT NULL,
    ci_enfermero VARCHAR (8) NOT NULL,
    habitacion_retiro VARCHAR(50) NOT NULL,
    piso_retiro VARCHAR(50) NOT NULL,
    FOREIGN KEY (ci_enfermero) REFERENCES enfermero(ci_enfermero)
);

CREATE TABLE transporta (
    id_transportable VARCHAR(50) NOT NULL,
    id_traslado VARCHAR(50) NOT NULL,
    ci_chofer VARCHAR(8) NOT NULL,
    matricula VARCHAR(15) NOT NULL,
    id_destino VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_transportable, id_traslado),
    FOREIGN KEY (id_transportable) REFERENCES transportable(id_transportable),
    FOREIGN KEY (id_traslado) REFERENCES traslado(id_traslado),
    FOREIGN KEY (ci_chofer) REFERENCES chofer(ci_chofer),
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula),
    FOREIGN KEY (id_destino) REFERENCES destino(id_destino)
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
    tipo_elemento ENUM('organo', 'otro') NOT NULL,
    FOREIGN KEY (id_transportable) REFERENCES transportable(id_transportable)
);

INSERT INTO chofer (ci_chofer, nombre_chofer, apellido_chofer, telefono) VALUES
('48291023', 'Ezequiel', 'Matta', '099123456'),
('51928374', 'Juan', 'Perez', '098765432'),
('39281726', 'Carlos', 'Gomez', '091234567'),
('44556677', 'Martin', 'Rodriguez', '092345678'),
('50123459', 'Diego', 'Silva', '093456789');

INSERT INTO vehiculo (matricula, modelo, capacidad, tipo_vehiculo, camilla) VALUES
('SCA1234', 'Mercedes Sprinter', '3', 'ambulancia', TRUE),
('SBB5678', 'Renault Master', '3', 'ambulancia', TRUE),
('SFA9012', 'Fiat Fiorino', '2', 'auto', FALSE),
('SCD3456', 'Peugeot Partner', '2', 'otro', FALSE),
('SEE7890', 'Ford Transit', '4', 'ambulancia', TRUE);

INSERT INTO conduce (ci_chofer, matricula) VALUES
('48291023', 'SCA1234'),
('51928374', 'SBB5678'),
('39281726', 'SFA9012'),
('44556677', 'SCD3456'),
('50123459', 'SEE7890');

INSERT INTO destino (id_destino, nombre_destino, direccion, ruta) VALUES
('DEST01', 'Hospital de Clinicas', 'Av. Italia s/n', 'Ruta 1'),
('DEST02', 'Hospital Maciel', '25 de Mayo 174', 'Ruta 1'),
('DEST03', 'Sanatorio Americano', 'Av. Italia 2445', 'Ruta 5'),
('DEST04', 'Hospital Militar', 'Av. 8 de Octubre 3020', 'Ruta 8'),
('DEST05', 'Sanatorio Britanico', 'Av. Garibaldi 1720', 'Ruta 102');

INSERT INTO enfermero (ci_enfermero, nombre_enfermero, apellido_enfermero) VALUES
('38192837', 'Maria', 'Lopez'),
('42192834', 'Ana', 'Fernandez'),
('46192839', 'Lucia', 'Martinez'),
('37192831', 'Sofia', 'Suarez'),
('49192835', 'Valentina', 'Acosta');

INSERT INTO traslado (id_traslado, ci_chofer, matricula, id_destino, fecha_traslado, hora_salida, hora_llegada, lugar_origen, estado) VALUES
('TRAS01', '48291023', 'SCA1234', 'DEST01', '2026-04-14', '08:00:00', '09:00:00', 'Piriapolis', 'aprobado'),
('TRAS02', '51928374', 'SBB5678', 'DEST02', '2026-04-14', '10:30:00', NULL, 'Montevideo', 'en proceso'),
('TRAS03', '39281726', 'SFA9012', 'DEST03', '2026-04-15', NULL, NULL, 'Montevideo', 'denegado'),
('TRAS04', '44556677', 'SCD3456', 'DEST04', '2026-04-16', '14:00:00', '15:15:00', 'Piriapolis', 'aprobado'),
('TRAS05', '50123459', 'SEE7890', 'DEST05', '2026-04-17', '16:00:00', NULL, 'Montevideo', 'en proceso');

INSERT INTO transportable (id_transportable, ci_enfermero, habitacion_retiro, piso_retiro) VALUES
('TRAN01', '38192837', '204', '2'),
('TRAN02', '42192834', '312', '3'),
('TRAN03', '46192839', '105', '1'),
('TRAN04', '37192831', '410', '4'),
('TRAN05', '49192835', '520', '5'),
('TRAN06', '38192837', '101', '1'),
('TRAN07', '42192834', '202', '2'),
('TRAN08', '46192839', '303', '3'),
('TRAN09', '37192831', '404', '4'),
('TRAN10', '49192835', '505', '5');

INSERT INTO transporta (id_transportable, id_traslado, ci_chofer, matricula, id_destino) VALUES
('TRAN01', 'TRAS01', '48291023', 'SCA1234', 'DEST01'),
('TRAN02', 'TRAS01', '48291023', 'SCA1234', 'DEST01'),
('TRAN03', 'TRAS02', '51928374', 'SBB5678', 'DEST02'),
('TRAN04', 'TRAS02', '51928374', 'SBB5678', 'DEST02'),
('TRAN05', 'TRAS03', '39281726', 'SFA9012', 'DEST03'),
('TRAN06', 'TRAS03', '39281726', 'SFA9012', 'DEST03'),
('TRAN07', 'TRAS04', '44556677', 'SCD3456', 'DEST04'),
('TRAN08', 'TRAS04', '44556677', 'SCD3456', 'DEST04'),
('TRAN09', 'TRAS05', '50123459', 'SEE7890', 'DEST05'),
('TRAN10', 'TRAS05', '50123459', 'SEE7890', 'DEST05');

INSERT INTO paciente (id_transportable, ci_paciente, nombre_paciente, accesorio, oxigeno, aislamiento) VALUES
('TRAN01', '41234567', 'Roberto Gomez', 'baston', FALSE, FALSE),
('TRAN02', '45678912', 'Marta Sanchez', 'silla', TRUE, FALSE),
('TRAN03', '39876543', 'Pedro Diaz', 'otro', FALSE, TRUE),
('TRAN04', '38234567', 'Lucia Mendez', 'baston', TRUE, TRUE),
('TRAN05', '37456789', 'Jorge Rivas', 'silla', FALSE, FALSE);

INSERT INTO elemento (id_transportable, nombre_elemento, descripcion_elemento, tipo_elemento) VALUES
('TRAN06', 'Corazon', 'Organo para trasplante urgente', 'organo'),
('TRAN07', 'Kit quirurgico', 'Instrumental medico esterilizado', 'otro'),
('TRAN08', 'Riñon', 'Organo para trasplante', 'organo'),
('TRAN09', 'Sangre', 'Unidades de sangre plasma', 'otro'),
('TRAN10', 'Higado', 'Organo para trasplante', 'organo');
