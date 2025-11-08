# Proyecto: Sistema de Gestión (Arquitectura de Microservicios)

Este proyecto transforma una aplicación monolítica de PHP en una arquitectura moderna de microservicios, completamente orquestada y lista para producción usando Docker.

## 1. 🚀 Requisitos de Ejecución

Para ejecutar este proyecto completo, solo se necesitan **dos** herramientas:

1.  **Docker (con Docker Compose):**
    * Se requiere **Docker Desktop** (para Windows/Mac) o **Docker Engine** (para Linux).
    * El motor de Docker se encarga de construir y ejecutar los 5 contenedores de la aplicación (incluyendo los servidores Apache y todas las dependencias de PHP) y de gestionar la red interna.
2.  **Git:** Para clonar este repositorio.

*No es necesario instalar PHP, Apache, Composer, ni ninguna extensión de PHP (como `pdo_mysql` o `mongodb`) en la máquina local. Todo está incluido en las imágenes de Docker.*

---

## 2. 🔧 Arquitectura y Tecnologías

El proyecto está compuesto por 5 servicios independientes que se ejecutan en sus propios contenedores Docker y se comunican a través de una red privada.

### Componentes Principales

* **1. Frontend (`frontend/`):**
    * **Puerto:** `http://localhost:3000`
    * **Descripción:** Es el cliente que ve el usuario. Una aplicación de JavaScript pura que se comunica con el API Gateway. Sirve los archivos `index.php` (login) y `dashboard.php` (panel principal).

* **2. API Gateway (`api_gateway/`):**
    * **Puerto:** `http://localhost:8000`
    * **Descripción:** El **único punto de entrada** para el frontend. Actúa como un "peaje" que centraliza la seguridad (validación de tokens JWT), maneja el control de acceso basado en roles (RBAC) y redirige las peticiones al microservicio interno correspondiente.

* **3. Servicio de Usuarios (`servicio_usuarios/`):**
    * **Puerto:** (Interno, no expuesto)
    * **Descripción:** El único servicio que maneja la lógica de autenticación. Valida las credenciales del usuario y genera los tokens JWT.

* **4. Servicio de Productos (`servicio_productos/`):**
    * **Puerto:** (Interno, no expuesto)
    * **Descripción:** Gestiona toda la lógica de negocio (CRUD) para los productos. Es el único servicio que se conecta a la base de datos de MongoDB.

* **5. Servicio de Ventas (`servicio_ventas/`):**
    * **Puerto:** (Interno, no expuesto)
    * **Descripción:** Gestiona la lógica de registro de ventas. Se comunica internamente con el `servicio_productos` (vía Guzzle) para actualizar el stock.

### Tecnologías Utilizadas

| Categoría | Tecnología | Propósito |
| :--- | :--- | :--- |
| **Lenguaje** | PHP 8.3 | Lenguaje principal de todos los servicios de backend. |
| **Framework** | Slim (v4) | Framework minimalista para crear las APIs RESTful en cada servicio. |
| **Contenerización** | Docker | Para crear contenedores aislados para cada servicio. |
| **Orquestación** | Docker Compose | Para definir y ejecutar los 5 servicios con un solo comando. |
| **Servidor Web** | Apache | Servidor web que se ejecuta *dentro* de cada contenedor Docker. |
| **Seguridad** | Firebase/php-jwt | Para crear y validar JSON Web Tokens (JWT) para la autenticación. |
| **Comunicación**| Guzzle | Cliente HTTP para la comunicación entre servicios (Gateway -> Servicios, Ventas -> Productos). |
| **Base de Datos** | MongoDB | Base de datos NoSQL utilizada por el `servicio_productos`. |
| **Base de Datos** | MySQL | Base de datos relacional (simulada/lista para usar) en `servicio_usuarios` y `servicio_ventas`. |

---

## 3. 🏁 Instrucciones de Puesta en Marcha

Sigue estos pasos para levantar toda la aplicación:

**1. Clonar el Repositorio**
```bash
git clone [https://github.com/tu-usuario/tu-repositorio.git](https://github.com/tu-usuario/tu-repositorio.git)
cd tu-repositorio
```
**2. Configurar Variables de Entorno**

El proyecto requiere credenciales para la base de datos y un secreto para los tokens. Deberás crear un archivo .env en las carpetas correspondientes:

    Para servicio_usuarios/:

        Crea un archivo: servicio_usuarios/.env

        Añade el secreto para firmar los tokens:

        JWT_SECRET="123456789"

    Para api_gateway/:

        Crea un archivo: api_gateway/.env

        Añade el mismo secreto (debe coincidir con el de usuarios):

        JWT_SECRET="123456789"

    Para servicio_productos/:

        Crea un archivo: servicio_productos/.env

        Añade tu cadena de conexión de MongoDB Atlas:

        MONGO_URI="mongodb+srv://<usuario>:<password>@<tu-cluster>.mongodb.net/?retryWrites=true&w=majority"

    (Nota: Reemplaza <usuario>, <password> y <tu-cluster> con tus credenciales reales de Atlas).

**3. Levantar los Servicios**

Abre una terminal en la carpeta raíz del proyecto (donde está docker-compose.yml) y ejecuta:
Bash

docker compose up --build

    --build: Construirá las 5 imágenes de Docker la primera vez.

    up: Encenderá todos los servicios.

Verás los logs de todos los servicios en tiempo real en tu terminal.

**4. Acceder a la Aplicación**

¡Listo! Abre tu navegador web y ve a: http://localhost:3000

**4. 🔑 Credenciales de Prueba**

Puedes usar las siguientes credenciales para probar los diferentes roles:

    Rol de Administrador:

        Email: admin@admin.com

        Pass: 1234

        (Verá el panel de administración con la lista de empleados).

    Rol de Vendedor (Empleado):

        Email: empleado@tienda.com

        Pass: 1234

        (Verá el panel de vendedor con los formularios de productos y ventas).