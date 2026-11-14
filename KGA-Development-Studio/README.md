# KGA Development Studio - Sistema de Gestión Hospitalaria (S.I.G.P.D.)

KGA Development Studio es una solución de software integral orientada a la gestión y optimización de procesos hospitalarios, desarrollada como parte de los proyectos académicos y prácticos del curso de desarrollo de software.

El sistema centraliza el control de ambulancias, pacientes, traslados, encuestas, documentos y personal médico, estructurado bajo una arquitectura modular.

---

## Objetivo del Proyecto

Diseñar e implementar una plataforma web que automatice y facilite la administración hospitalaria, garantizando un flujo de datos eficiente entre el personal médico, administrativo, pacientes y los servicios de transporte (ambulancias).

---

## Tecnologías Utilizadas

- *Frontend:* HTML, CSS y JavaScript.
- *Backend:* PHP.
- *Base de Datos:* MySQL.
- *Acceso a datos:* PHP PDO para la conexión y gestión de consultas a la base de datos.
- *Entorno de desarrollo local:* XAMPP.
- *Control de versiones:* GitHub.

---

## Gestión de Ramas (Branches)

El repositorio mantiene un flujo de trabajo ordenado mediante ramas orientadas a entregas y evolución del software:

| Rama | Descripción |
| -------------------- | ----------------------------------------------------------------------------------------------------------------- |
| main | Rama principal y estable del sistema, que contiene la versión consolidada y funcional del proyecto. |
| dev | Rama de desarrollo activo para la integración de nuevas funcionalidades, módulos y correcciones antes de pasar a main. |
| PrimeraEntrega | Estructura inicial y documentación correspondiente a la primera fase del proyecto. |
| SegundaEntrega | Avances de arquitectura, modelos de base de datos (mer.sql) e integración de componentes de backend. |
| TerceraEntrega | Integración final del sistema completo y documentación de entrega final. |

---

## Estructura del Proyecto

El sistema se organiza dentro del directorio principal KGA-Development-Studio/, siguiendo una estructura modular que separa las interfaces, los endpoints y la lógica de negocio.

text
KGA-Development-Studio/
│
├── API/
│   ├── ambulancias/
│   ├── categorias/
│   ├── documentos/
│   ├── notificaciones/
│   ├── traslados/
│   └── ... 
│
├── backend/
│   ├── config/
│   │   └── database.php
│   ├── database/
│   │   └── mer.sql
│   └── models/
│       └── ...
│
├── frontend/
│   ├── css/
│   ├── html/
│   ├── img/
│   └── js/
│
└── README.md


> *Organización modular:*
>
> - La carpeta frontend/ contiene las interfaces del sistema, sus estilos y los scripts JavaScript que gestionan la interacción con el usuario.
> - La carpeta API/ contiene los endpoints PHP encargados de recibir y procesar las solicitudes del frontend.
> - La carpeta backend/ concentra la configuración de conexión a la base de datos, los modelos y los recursos relacionados con la base de datos.
> - El archivo backend/database/mer.sql contiene el script de creación y configuración de la base de datos.

---

## ⚙️ Despliegue y Ejecución

### 1. Clonar el repositorio

bash
git clone https://github.com/Gennar0Rodriguez/KGA-Development-Studio.git


### 2. Configuración del entorno

Asegúrate de tener instalado un entorno de servidor local compatible con PHP y MySQL, como XAMPP.

Coloca el proyecto dentro del directorio correspondiente al servidor web. En XAMPP, normalmente se utiliza:

text
htdocs/


### 3. Configuración de la base de datos

- Inicia los servicios de Apache y MySQL.
- Accede a phpMyAdmin.
- Importa el archivo backend/database/mer.sql.
- Verifica la configuración de conexión en backend/config/database.php.

### 4. Ejecución

Una vez configurado el entorno y la base de datos, inicia Apache y MySQL desde XAMPP.

Abre el navegador e ingresa a la ruta local correspondiente al proyecto, por ejemplo:

text
http://localhost/kgade/KGA-Development-Studio/frontend/html/


---

## Equipo de Desarrollo

*KGA Development Studio* – Proyecto de Gestión Hospitalaria (S.I.G.P.D.).

---

## Licencia

Este proyecto fue desarrollado con fines académicos y educativos. Todos los derechos reservados a sus autores.
