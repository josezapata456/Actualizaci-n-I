MONIFY - Manual de Instalación y Uso

1. Introducción

Este documento sirve como manual de instalación y uso para MONIFY, una aplicación de cajero automático simple. La arquitectura del proyecto se basa en un backend desarrollado en PHP que funciona como una API y un frontend en HTML, CSS y JavaScript que consume dicha API.

El objetivo de este manual es guiar al usuario a través del proceso de configuración del entorno de desarrollo en Windows (utilizando XAMPP o una herramienta similar), detallar la estructura del proyecto, explicar la configuración de la base de datos y proporcionar notas importantes sobre su funcionamiento.

Resumen de la Arquitectura

* Frontend: Se encuentra en el directorio Frontend/ y está construido con HTML, CSS y JavaScript (app.js).
* Backend (API): Ubicado en Backend/Api/, implementado en PHP.
* Configuración de Base de Datos: El archivo de conexión se encuentra en Backend/Config/database.php.
* Modelos de Datos: La lógica de negocio para el usuario está en Backend/Models/User.php.

2. Requisitos Previos

Para ejecutar la aplicación en un entorno local, necesitarás el siguiente software:

* Sistema Operativo: Windows (las instrucciones de este manual utilizan PowerShell).
* Servidor Web y PHP: Se recomienda XAMPP, que incluye Apache y PHP 7.x o superior.
* Base de Datos: MySQL o MariaDB (incluidas en XAMPP).

Puedes descargar XAMPP desde su sitio web oficial: https://www.apachefriends.org/

3. Estructura del Proyecto

El proyecto está organizado en dos carpetas principales: Backend y Frontend.

* Backend/
    * Api/
        * auth.php: Gestiona el registro y login de usuarios.
        * deposit.php: Procesa los depósitos de fondos.
        * withdraw.php: Procesa los retiros de fondos.
        * transfer.php: Archivo preparado para la funcionalidad de transferencias (actualmente vacío).
    * Config/
        * database.php: Contiene la configuración de la conexión a la base de datos (PDO).
    * Models/
        * User.php: Contiene la lógica de negocio del usuario (registro, login, consulta de saldo, etc.).
* Frontend/
    * index.html: Página de inicio de sesión.
    * register.html: Página de registro de nuevos usuarios.
    * home.html: Panel principal del usuario una vez autenticado.
    * deposit.html: Formulario para realizar depósitos.
    * transfer.html: Formulario para realizar transferencias.
    * withdraw.html: Formulario para realizar retiros.
    * js/app.js: Contiene toda la lógica del cliente (peticiones a la API, manejo de sesión, etc.).
    * css/style.css: Hoja de estilos para la aplicación.

4. Configuración de la Base de Datos

Sigue estos pasos para crear y configurar la base de datos de MONIFY.

1.  Crear la base de datos: Utilizando una herramienta como phpMyAdmin o la consola de MySQL, crea una nueva base de datos. El nombre por defecto utilizado en la aplicación es monify_app.

2.  Crear las tablas: Ejecuta el siguiente script SQL para crear las tablas users y transactions.

    CREATE DATABASE IF NOT EXISTS monify_app CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
    USE monify_app;

    CREATE TABLE users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      username VARCHAR(100) NOT NULL UNIQUE,
      email VARCHAR(150) NOT NULL UNIQUE,
      password VARCHAR(255) NOT NULL,
      full_name VARCHAR(255) DEFAULT NULL,
      balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE transactions (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      type ENUM('deposit','withdraw','transfer') NOT NULL,
      amount DECIMAL(15,2) NOT NULL,
      description VARCHAR(255) DEFAULT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

3.  Ajustar credenciales: Abre el archivo Backend/Config/database.php y verifica que las credenciales (host, nombre de la base de datos, usuario y contraseña) coincidan con las de tu servidor MySQL. Por defecto, está configurado para un usuario root sin contraseña.

5. Pasos para la Ejecución Local (con XAMPP)

1.  Iniciar servicios: Abre el panel de control de XAMPP y arranca los módulos de Apache y MySQL.
2.  Copiar el proyecto: Copia la carpeta completa del proyecto al directorio htdocs de tu instalación de XAMPP. Por ejemplo, si el proyecto se llama monify-app, la ruta final debería ser C:\xampp\htdocs\monify-app.
3.  Configurar base de datos: Asegúrate de haber completado todos los pasos de la sección anterior.
4.  Acceder a la aplicación: Abre tu navegador web y navega a la siguiente URL para acceder al formulario de login:
    http://localhost/monify-app/Frontend/index.html
    (Reemplaza monify-app con el nombre que le hayas dado a la carpeta del proyecto).

6. Endpoints de la API Backend

El frontend se comunica con el backend a través de los siguientes endpoints:

* Backend/Api/auth.php (Método: POST)
    * action=register: Crea un nuevo usuario. Campos requeridos: username, email, password, full_name.
    * action=login: Autentica a un usuario. Campos requeridos: username, password.

* Backend/Api/deposit.php (Método: POST)
    * Realiza un depósito. Datos requeridos: user_id, amount.
    * Actualiza el saldo del usuario y registra la transacción.

* Backend/Api/withdraw.php (Método: POST)
    * Realiza un retiro. Datos requeridos: user_id, amount.
    * Verifica que el saldo sea suficiente antes de procesar el retiro.

* Backend/Api/transfer.php
    * No implementado. El archivo existe pero está vacío.

7. Problemas Conocidos y Recomendaciones

1.  Funcionalidad de Transferencia Incompleta: El endpoint transfer.php está vacío, por lo que las transferencias no funcionarán.
2.  Manejo de Asincronía en Frontend: Las llamadas a la API desde el frontend (ej. deposit.html) no utilizan async/await, lo que puede causar un manejo incorrecto de los mensajes de éxito o error. Se recomienda refactorizar estas llamadas.
3.  Rutas de API Fijas: La URL base de la API está configurada en Frontend/js/app.js como /chat/backend/api/. Asegúrate de que esta ruta coincida con la ubicación de tu proyecto en htdocs o modifícala según sea necesario.
4.  Seguridad de la API: La API no utiliza un sistema de autenticación basado en tokens (como JWT). Cualquier petición que conozca un user_id válido puede realizar operaciones. Para un entorno de producción, es crucial implementar un sistema de tokens para proteger los endpoints.

Sugerencia de Mejora (JavaScript)

Para corregir el manejo de la asincronía en los formularios, puedes usar async/await de la siguiente manera:

document.getElementById('depositForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const amount = parseFloat(document.getElementById('depositAmount').value);
  const user = MONIFY.getCurrentUser();
  if (!user) {
    // Redirigir al login si no hay usuario
    return;
  }
  try {
    await MONIFY.deposit(user.id, amount);
    // Mostrar mensaje de éxito
    alert('Depósito realizado con éxito');
    window.location.href = 'home.html';
  } catch (err) {
    // Mostrar mensaje de error
    alert('Error al realizar el depósito: ' + err.message);
  }
});

Comprobación de Errores Comunes
* Error de conexión PHP: Revisa las credenciales en Backend/Config/database.php y confirma que el servicio de MySQL está en ejecución.
* Error 404 en la API: Verifica la ruta MONIFY.apiBase en Frontend/js/app.js y asegúrate de que coincide con la estructura de carpetas en tu servidor local.

  Una vez ingresa al sistemas las credenciales de prueba son; Usuario: demo
                                                              Contraseña: password
<img width="424" height="502" alt="image" src="https://github.com/user-attachments/assets/2c54ab2c-fb84-4a9e-b873-cf5f95e2ffa3" />
<img width="409" height="536" alt="image" src="https://github.com/user-attachments/assets/5223b2eb-cd4e-49a9-bfb0-fe30cfd2d114" />


