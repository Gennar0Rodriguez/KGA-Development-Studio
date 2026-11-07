-- ============================================================
-- 1. ADMINISTRATIVOS
-- ============================================================

INSERT INTO administrativo (
    ci_admin,
    nombre_admin,
    apellido_admin,
    contraseña_admin,
    cargo
) VALUES
('12345678', 'Ezequiel', 'Matta', 'Ari', 'administrador'),
('87654321', 'Juan', 'Gomez', '123', 'empleado'),
('11223344', 'Carlos', 'Perez', '456', 'empleado'),
('55667788', 'Marichal', 'Rodriguez', '789', 'administrador'),
('99887766', 'Juan', 'Lopez', '222', 'empleado');


-- ============================================================
-- 2. CATEGORÍAS
-- CAT01-CAT05: DOCUMENTOS
-- CAT06-CAT10: ENCUESTAS
-- ============================================================

INSERT INTO categoria (
    id_categoria,
    nombre_cat,
    tipo_cat,
    estado_cat
) VALUES
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


-- ============================================================
-- 3. DOCUMENTOS
-- Utilizan exclusivamente categorías de tipo docs.
-- ============================================================

INSERT INTO documentos (
    id_documentos,
    ci_admin,
    id_categoria,
    hora_carga,
    fecha_carga,
    titulo_docs,
    descripcion_docs,
    nombre_archivo,
    ruta_archivo
) VALUES
('DOC01', '12345678', 'CAT01', '08:30:00', '2026-06-01',
 'Protocolo Triaje', 'Protocolo de recepcion de emergencias',
 'triaje.pdf', 'uploads/documentos/triaje.pdf'),

('DOC02', '87654321', 'CAT02', '09:15:00', '2026-06-02',
 'Historial Pediatrico', 'Guia de controles pediatricos',
 'pediatria.pdf', 'uploads/documentos/pediatria.pdf'),

('DOC03', '11223344', 'CAT03', '10:00:00', '2026-06-03',
 'Checklist Quirurgico', 'Lista de verificacion de quirofano',
 'quirofano.pdf', 'uploads/documentos/quirofano.pdf'),

('DOC04', '55667788', 'CAT04', '11:20:00', '2026-06-04',
 'Inventario Insumos', 'Stock de medicamentos del mes',
 'farmacia.pdf', 'uploads/documentos/farmacia.pdf'),

('DOC05', '99887766', 'CAT05', '14:00:00', '2026-06-05',
 'Manual Muestras', 'Procedimiento de analiticas de sangre',
 'analisis.pdf', 'uploads/documentos/analisis.pdf');


-- ============================================================
-- 4. ENCUESTAS
-- Utilizan exclusivamente categorías de tipo encuestas.
-- ============================================================

INSERT INTO encuesta (
    id_encuesta,
    id_categoria,
    descripcion_enc,
    titulo_enc
) VALUES
('ENC01', 'CAT06', 'Evaluacion de limpieza hospitalaria',
 'Encuesta de Limpieza'),

('ENC02', 'CAT07', 'Evaluacion del trato recibido',
 'Encuesta de Trato del Personal'),

('ENC03', 'CAT08', 'Evaluacion de velocidad de atencion',
 'Encuesta de Velocidad del Personal'),

('ENC04', 'CAT09', 'Evaluacion de higiene del personal',
 'Encuesta de Higiene del Personal'),

('ENC05', 'CAT10', 'Evaluacion general del servicio',
 'Encuesta de Atencion General');


-- ============================================================
-- 5. PREGUNTAS
-- Cada pregunta utiliza una encuesta y su categoría.
-- ============================================================

INSERT INTO pregunta (
    id_pregunta,
    id_encuesta,
    id_categoria,
    nombre_pregunta,
    tipo
) VALUES
('PRG01', 'ENC01', 'CAT06',
 '¿Como calificaria la limpieza?', 'opcion multiple'),

('PRG02', 'ENC02', 'CAT07',
 '¿Como fue el trato recibido?', 'opcion multiple'),

('PRG03', 'ENC03', 'CAT08',
 '¿La atencion fue rapida?', 'opcion multiple'),

('PRG04', 'ENC04', 'CAT09',
 '¿El personal mantenia buena higiene?', 'opcion multiple'),

('PRG05', 'ENC05', 'CAT10',
 '¿Como calificaria el servicio?', 'opcion multiple');


-- ============================================================
-- 6. RESPUESTAS
-- Cada respuesta corresponde a una pregunta existente.
-- ============================================================

INSERT INTO respuesta (
    id_respuesta,
    id_pregunta,
    id_encuesta,
    id_categoria,
    informacion,
    tipo
) VALUES
('RES01', 'PRG01', 'ENC01', 'CAT06', '5', 'opcion multiple'),
('RES02', 'PRG02', 'ENC02', 'CAT07', '4', 'opcion multiple'),
('RES03', 'PRG03', 'ENC03', 'CAT08', '3', 'opcion multiple'),
('RES04', 'PRG04', 'ENC04', 'CAT09', '5', 'opcion multiple'),
('RES05', 'PRG05', 'ENC05', 'CAT10', '4', 'opcion multiple');


-- ============================================================
-- 7. CHOFERES
-- ============================================================

INSERT INTO chofer (
    ci_chofer,
    nombre_chofer,
    apellido_chofer,
    telefono
) VALUES
('45678912', 'Esteban', 'Silva', '099123456'),
('32165498', 'Marcelo', 'Rios', '098654321'),
('78912345', 'Gabriel', 'Benitez', '097111222'),
('65498732', 'Diego', 'Fagundez', '096333444'),
('14725836', 'Fernando', 'Acosta', '095555666');


-- ============================================================
-- 8. VEHÍCULOS
-- ============================================================

INSERT INTO vehiculo (
    matricula,
    modelo,
    capacidad,
    tipo_vehiculo,
    camilla,
    observaciones
) VALUES
('SCA1001', 'Mercedes Sprinter', '4', 'ambulancia', 1,
 'Equipamiento UTI movil'),

('SCA1002', 'Fiat Ducato', '3', 'ambulancia', 1,
 'Oxigeno disponible'),

('SCA1003', 'Renault Kangoo', '1', 'auto', 0,
 'Traslado de personal o insumos'),

('SCA1004', 'Peugeot Partner', '1', 'auto', 0,
 'Documentacion urgente'),

('SCA1005', 'Ford Fiesta', '6', 'otro', 0,
 'Vehiculo de traslado');


-- ============================================================
-- 9. RELACIÓN CHOFER - VEHÍCULO
-- ============================================================

INSERT INTO conduce (
    ci_chofer,
    matricula
) VALUES
('45678912', 'SCA1001'),
('32165498', 'SCA1002'),
('78912345', 'SCA1003'),
('65498732', 'SCA1004'),
('14725836', 'SCA1005');


-- ============================================================
-- 10. DESTINOS
-- ============================================================

INSERT INTO destino (
    id_destino,
    nombre_destino,
    direccion,
    ruta
) VALUES
('DES01', 'Hospital de Clinicas', 'Av. Italia s/n', 'Ruta 1'),
('DES02', 'Sanatorio Americano', 'Av. 8 de Octubre 2720', 'Ruta 2'),
('DES03', 'Hospital Maciel', '25 de Mayo 174', 'Ruta 3'),
('DES04', 'Centro de Asistencia Medica', 'Bv. Artigas 1550', 'Ruta 4'),
('DES05', 'Sanatorio Britanico', 'Av. Garibaldi 2764', 'Ruta 5');


-- ============================================================
-- 11. TRASLADOS
-- Choferes, vehículos y destinos deben existir previamente.
-- ============================================================

INSERT INTO traslado (
    id_traslado,
    ci_chofer,
    matricula,
    id_destino,
    fecha_traslado,
    hora_salida,
    hora_llegada,
    lugar_origen,
    estado_traslado,
    ci_solicitante,
    estado_solicitud,
    observaciones
) VALUES
('TRS01', '45678912', 'SCA1001', 'DES01',
 '2026-06-01', '08:00:00', '08:45:00',
 'Hospital Central', 'finalizado', '12345678',
 'aprobado', 'Traslado de paciente'),

('TRS02', '32165498', 'SCA1002', 'DES02',
 '2026-06-02', '09:30:00', '10:15:00',
 'Hospital Central', 'aprobado', '55667788',
 'aprobado', 'Derivacion medica'),

('TRS03', '78912345', 'SCA1003', 'DES03',
 '2026-06-03', '11:00:00', '11:30:00',
 'Hospital Central', 'en proceso', '12345678',
 'aprobado', 'Envio de documentacion'),

('TRS04', '65498732', 'SCA1004', 'DES04',
 '2026-06-04', '13:00:00', '13:40:00',
 'Hospital Central', 'pendiente', '55667788',
 'pendiente', 'Solicitud en evaluacion'),

('TRS05', '14725836', 'SCA1005', 'DES05',
 '2026-06-05', '15:00:00', '15:50:00',
 'Hospital Central', 'denegado', '12345678',
 'rechazado', 'Traslado cancelado');


-- ============================================================
-- 12. NOTIFICACIONES
-- Los traslados, choferes, vehículos, destinos y
-- administradores referenciados ya existen.
-- ============================================================

INSERT INTO notifica (
    id_traslado,
    ci_chofer,
    matricula,
    id_destino,
    ci_admin,
    ci_emisor,
    fecha_envio,
    leido,
    titulo_not,
    descripcion_not,
    ci_receptor
) VALUES
('TRS01', '45678912', 'SCA1001', 'DES01',
 '12345678', '12345678', '2026-06-01', 1,
 'Asignacion de traslado',
 'Se asigno un traslado al Hospital de Clinicas.',
 '87654321'),

('TRS02', '32165498', 'SCA1002', 'DES02',
 '55667788', '55667788', '2026-06-02', 1,
 'Derivacion aprobada',
 'La derivacion fue aprobada.',
 '11223344'),

('TRS03', '78912345', 'SCA1003', 'DES03',
 '12345678', '12345678', '2026-06-03', 0,
 'Envio de documentacion',
 'Documentacion medica en traslado.',
 '99887766'),

('TRS04', '65498732', 'SCA1004', 'DES04',
 '55667788', '55667788', '2026-06-04', 0,
 'Solicitud pendiente',
 'La solicitud se encuentra en evaluacion.',
 '87654321'),

('TRS05', '14725836', 'SCA1005', 'DES05',
 '12345678', '12345678', '2026-06-05', 1,
 'Traslado rechazado',
 'La solicitud de traslado fue rechazada.',
 '11223344');


-- ============================================================
-- 13. ENFERMEROS
-- ============================================================

INSERT INTO enfermero (
    ci_enfermero,
    nombre_enfermero,
    apellido_enfermero
) VALUES
('22334455', 'Agustina', 'Duarte'),
('33445566', 'Matias', 'Suarez'),
('44556677', 'Sofia', 'Pereira'),
('55667788', 'Jefrery', 'Pereyra'),
('66778899', 'Bennet', 'Impact');


-- ============================================================
-- 14. TRANSPORTABLES
-- ============================================================

INSERT INTO transportable (
    id_transportable,
    ci_enfermero,
    habitacion_retiro,
    piso_retiro
) VALUES
('TRP01', '22334455', 'Habitacion 204', 'Piso 2'),
('TRP02', '33445566', 'Habitacion 310', 'Piso 3'),
('TRP03', '44556677', 'Sala de Esterilizacion', 'Planta Baja'),
('TRP04', '55667788', 'Habitacion 105', 'Piso 1'),
('TRP05', '66778899', 'Laboratorio Central', 'Subsuelo'),
('TRP06', '22334455', 'CTI Adultos', 'Piso 4'),
('TRP07', '33445566', 'Archivo Central', 'Subsuelo'),
('TRP08', '44556677', 'Farmacia', 'Planta Baja');


-- ============================================================
-- 15. PACIENTES
-- Solo utilizan transportables existentes.
-- ============================================================

INSERT INTO paciente (
    id_transportable,
    ci_paciente,
    nombre_paciente,
    accesorio,
    oxigeno,
    aislamiento
) VALUES
('TRP01', '98765432', 'Roberto Gomez', 'silla', 1, 0),
('TRP02', '87654321', 'Ezequiel Matta Rodriguez', 'baston', 0, 1),
('TRP04', '76543210', 'Jorge Ramirez', 'otro', 1, 0);


-- ============================================================
-- 16. ELEMENTOS
-- ============================================================

INSERT INTO elemento (
    id_transportable,
    nombre_elemento,
    descripcion_elemento,
    tipo_elemento
) VALUES
('TRP03', 'Valvula Cardiaca',
 'Valvula biologica para cirugia', 'organo'),

('TRP05', 'Historias Clinicas',
 'Carpeta con expedientes archivados', 'documentacion'),

('TRP06', 'Rinon para Trasplante',
 'Tejido vivo conservado en frio', 'organo'),

('TRP07', 'Expedientes de Auditoria',
 'Documentacion confidencial', 'documentacion'),

('TRP08', 'Insumos Quirurgicos',
 'Instrumental esterilizado', 'otro');


-- ============================================================
-- 17. RELACIÓN TRANSPORTABLE - TRASLADO
-- ============================================================

INSERT INTO transporta (
    id_transportable,
    id_traslado,
    ci_chofer,
    matricula,
    id_destino
) VALUES
('TRP01', 'TRS01', '45678912', 'SCA1001', 'DES01'),
('TRP02', 'TRS02', '32165498', 'SCA1002', 'DES02'),
('TRP03', 'TRS03', '78912345', 'SCA1003', 'DES03'),
('TRP04', 'TRS04', '65498732', 'SCA1004', 'DES04'),
('TRP05', 'TRS05', '14725836', 'SCA1005', 'DES05');