# Plan de Pruebas - MONIFY

## 1. Pruebas de Registro

### 1.1 Validaciones de Formulario
- [ ] Campo nombre completo vacío → debe mostrar "El nombre completo es obligatorio"
- [ ] Campo usuario vacío → debe mostrar "El usuario es obligatorio"
- [ ] Campo usuario solo con espacios → debe mostrar error
- [ ] Contraseñas no coinciden → debe mostrar "Las contraseñas no coinciden"
- [ ] Contraseña menor a 4 caracteres → debe mostrar error de longitud mínima

### 1.2 Casos de Éxito/Error
- [ ] Registro exitoso → debe redirigir a home.html
- [ ] Usuario ya existe → debe mostrar "El usuario o email ya existe"

## 2. Pruebas de Login

### 2.1 Validaciones
- [ ] Campos vacíos → debe mostrar "Datos incompletos"
- [ ] Credenciales incorrectas → debe mostrar "Credenciales incorrectas"

### 2.2 Casos de Éxito
- [ ] Login exitoso → debe redirigir a home.html y mostrar saldo
- [ ] Datos de sesión guardados → debe persistir al recargar página

## 3. Pruebas de Depósito

### 3.1 Validaciones de Monto
- [ ] Monto negativo → debe mostrar "Ingrese un monto mayor que 0"
- [ ] Monto cero → debe mostrar "Ingrese un monto mayor que 0"
- [ ] Monto no numérico → debe mostrar error de validación

### 3.2 Casos de Éxito/Error
- [ ] Depósito exitoso → debe mostrar nuevo saldo y redirigir
- [ ] Error de conexión → debe mostrar mensaje de error
- [ ] Sesión expirada → debe redirigir a login

## 4. Pruebas de Retiro

### 4.1 Validaciones de Monto
- [ ] Monto negativo → debe mostrar "Ingrese un monto mayor que 0"
- [ ] Monto cero → debe mostrar "Ingrese un monto mayor que 0"
- [ ] Monto no numérico → debe mostrar error de validación
- [ ] Monto mayor al saldo → debe mostrar "Usted no cuenta con el saldo suficiente para retirar"

### 4.2 Casos de Éxito/Error
- [ ] Retiro exitoso → debe mostrar nuevo saldo y redirigir
- [ ] Error de conexión → debe mostrar mensaje de error
- [ ] Sesión expirada → debe redirigir a login

## 5. Pruebas de Seguridad

### 5.1 Protección de Rutas
- [ ] Acceder a home.html sin sesión → debe redirigir a login
- [ ] Acceder a deposit.html sin sesión → debe redirigir a login
- [ ] Acceder a withdraw.html sin sesión → debe redirigir a login

### 5.2 Manejo de Sesión
- [ ] Cerrar sesión → debe limpiar localStorage y redirigir
- [ ] Token inválido → debe redirigir a login
- [ ] Múltiples pestañas → debe mantener sesión sincronizada

## 6. Pruebas de UI/UX

### 6.1 Responsividad
- [ ] Vista móvil → todos los elementos deben ser visibles/usables
- [ ] Tablet → formularios deben mantener estructura
- [ ] Desktop → diseño debe adaptarse correctamente

### 6.2 Mensajes al Usuario
- [ ] Errores backend → deben mostrarse claramente en rojo
- [ ] Éxito operaciones → debe mostrar confirmación en verde
- [ ] Tiempos de espera → debe mostrar loading mientras procesa

## 7. Casos Especiales

### 7.1 Manejo de Errores
- [ ] Sin conexión a internet → debe mostrar mensaje amigable
- [ ] Error de servidor → debe mostrar mensaje descriptivo
- [ ] Timeout de petición → debe informar al usuario

### 7.2 Validaciones de Negocio
- [ ] Límite máximo de depósito → validar si aplica
- [ ] Límite máximo de retiro → validar si aplica
- [ ] Frecuencia de operaciones → validar si hay límites

## Instrucciones para Ejecutar Pruebas

1. Preparación del Ambiente:
```bash
# Iniciar servidor local
cd c:\laragon\www\chat
php -S localhost:8000

# Limpiar base de datos (si es necesario)
# Ejecutar queries de reseteo en PHPMyAdmin
```

2. Datos de Prueba Recomendados:
- Usuario test: testuser1
- Contraseña: test1234
- Saldo inicial: $1000

3. Flujo de Pruebas:
- Ejecutar pruebas en orden secuencial
- Documentar cualquier error encontrado
- Verificar mensajes exactos mostrados al usuario
- Comprobar persistencia de datos después de cada operación

4. Herramientas Útiles:
- DevTools > Network para monitorear peticiones
- DevTools > Console para ver errores JavaScript
- DevTools > Application > Local Storage para verificar sesión
- DevTools > Network > Disable cache durante pruebas