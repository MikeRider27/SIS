# 🚀 Visor SIS

Este proyecto está preparado para ejecutarse utilizando **Docker** y
**Docker Compose**, lo que permite levantar el entorno de desarrollo de
forma rápida y consistente.

------------------------------------------------------------------------

# 📋 Requisitos

Antes de comenzar, asegúrate de tener instalado en tu sistema:

-   🐳 Docker
-   🐳 Docker Compose
-   🌐 Git

Puedes verificar las instalaciones con:

``` bash
docker --version
docker compose version
git --version
```

------------------------------------------------------------------------

# 📥 Clonar el repositorio

Clonar el proyecto desde el repositorio:

``` bash
git clone https://github.com/MikeRider27/SIS.git
cd SIS
```

------------------------------------------------------------------------

# 🗄️ Crear la Base de Datos

1.  Crear una base de datos en **PostgreSQL**.

``` sql
CREATE DATABASE mspbs_sis;
```

2.  Ejecutar los scripts SQL en el siguiente orden:

### 1️⃣ Structures.sql

Este script crea todas las tablas y estructuras necesarias.

### 2️⃣ Inserts.sql

Este script inserta los datos iniciales del sistema.

------------------------------------------------------------------------

# ⚙️ Configuración del proyecto

Dentro del proyecto, ir a la carpeta:

    src/core/

Crear el archivo:

    config.php

Con el siguiente contenido:

``` php
<?php
// Definir la zona horaria predeterminada
date_default_timezone_set('America/Asuncion');

// Definimos las variables de conexión
define("DB_HOST", "DB_HOST");
define("DB_PORT", "DB_PORT");
define("DB_NAME", "DB_NAME");
define("DB_USER", "DB_USER");
define("DB_PASSWORD", "DB_PASSWORD");

// COUNTRY
define("APP_COUNTRY_CODE", "PY");

// FHIR SERVER ENDPOINT
define("APP_FHIR_SERVER", "https://fhir-conectaton.mspbs.gov.py/fhir");
```

------------------------------------------------------------------------

# 🐳 Levantar el proyecto con Docker

Una vez configurado todo, ejecutar:

``` bash
docker-compose up -d
```

Esto levantará los contenedores en segundo plano.

------------------------------------------------------------------------

# 🔎 Verificar contenedores activos

``` bash
docker ps
```

------------------------------------------------------------------------

# 🛑 Detener el proyecto

``` bash
docker-compose down
```

------------------------------------------------------------------------


# 👨‍💻 Notas

-   Asegúrate de que la **IP de la base de datos sea accesible desde
    Docker**.
-   Verifica que el puerto **5432 esté abierto** si usas PostgreSQL
    externo.
-   Si cambias la configuración de base de datos, modifica el archivo
    `src/core/config.php`.