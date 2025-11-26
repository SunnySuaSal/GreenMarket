-- ============================================
-- Usuarios de Ejemplo para GreenMarket
-- ============================================
-- Ejecuta este archivo después de database.sql
-- para tener varios usuarios de prueba

USE greenmarket;

-- Usuarios normales (clientes)
INSERT INTO users (name, email, password, role) VALUES
('María González', 'maria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Juan Pérez', 'juan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Ana Martínez', 'ana@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Carlos Rodríguez', 'carlos@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Laura Sánchez', 'laura@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user')
ON DUPLICATE KEY UPDATE email=email;

-- Nota: Todas las contraseñas son "password123" (hash bcrypt)
-- En producción, cada usuario debería tener su propia contraseña única

-- ============================================
-- Credenciales de los usuarios de ejemplo:
-- ============================================
-- 
-- ADMINISTRADOR:
-- Email: admin@greenmarket.com
-- Contraseña: admin123
--
-- USUARIOS NORMALES (todos con contraseña "password123"):
-- 1. maria@example.com
-- 2. juan@example.com
-- 3. ana@example.com
-- 4. carlos@example.com
-- 5. laura@example.com
--
-- ============================================

