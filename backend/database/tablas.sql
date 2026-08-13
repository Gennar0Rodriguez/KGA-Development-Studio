-- administrativo(ci_admin, nombre_admin, apellido_admin, contraseña_admin, cargo)
CREATE TABLE administrativo (
    ci_admin varchar(8) PRIMARY KEY not null ,
    nombre_admin varchar,
    apellido_admin varchar,
    contraseña_admin,
    cargo varchar,
    constraint 
 )
-- documentos(id_documentos, ci_admin, id_categoria, titulo, descripcion, hora, fecha)
CREATE TABLE documentos(
    id_documentos varchar PRIMARY KEY not null,
    ci_admin varchar(8),
    id_categoria varchar not null,
    titulo varchar,
    descripcion varchar,
    hora varchar,
    fecha DATE

)
-- FK1: id_admin ⭢ administrativo(id_admin)
-- FK2: fecha ⭢ carga(fecha)
-- FK3: hora ⭢ carga(hora)
-- FK4: id_categoria ⭢ categorias(id_categoria)

-- pacientes(apodo)

-- acceden(apodo, id_documentos)
-- FK1: apodo ⭢ pacientes(apodo)
-- FK2: id_documentos ⭢ documentos(id_documentos)

-- categorias(id_categoria, nombre)

-- encuesta(id_encuesta, id_categoria, titulo, descripcion, preguntas)
-- FK1: id_categoria ⭢ categorias(id_categoria)

-- completa (id_encuesta, apodo)
-- FK1: apodo ⭢ pacientes(apodo)
-- FK2: id_encuesta ⭢ encuesta(id_encuesta)


----------

-- chofer(ci_chofer, nombre_chofer, apellido_chofer, telefono) 

-- enfermero(ci_enfermero, nombre_enfermero, apellido_enfermero)

-- traslado(id_traslado, ci_chofer, ci_enfermero, matricula,id_destino, fecha, hora_salida, hora_llegada, lugar_orgien estado) 
-- FK1: ci_chofer ⭢ chofer(ci_chofer)
-- FK2: ci_enfermero ⭢ enfermero(ci_enfermero)
-- FK3: matricula⭢ vehiculo(matricula)
-- FK4: id_destino⭢ destino(id_destino)

-- destino(id_destino, nombre, direccion, ruta)

-- vehiculo(matricula, modelo, capacidad, tipo_vehiculo)

-- transportable(id_transportable, habitacion_retiro, piso_retiro)

-- transporta(id_traslado, id_transportable)
-- FK1: id_traslado ⭢ traslado(id_traslado)
-- FK2: transportable⭢ transportable(id_transportable)

-- paciente(id_transportable, ci_paciente, nombre, accesorio, camilla, oxigeno, aislamiento)
-- FK1: id_transportable ⭢ transportable(id_transportable)

-- elemento(id_transportable, nombre, descripcion, tipo_elemento)
-- FK1: id_transportable ⭢ transportable(id_transportable)



