CREATE TABLE IF NOT EXISTS roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO roles (name, description) 
VALUES 
    ('admin', 'Administrador del sistema')
ON CONFLICT (name) DO NOTHING;

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    date_of_birth DATE,
    role_id INTEGER NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP,
    failed_login_attempts INTEGER DEFAULT 0,
    account_locked_until TIMESTAMP,
    password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_user_role 
        FOREIGN KEY (role_id) 
        REFERENCES roles(id)
        ON DELETE RESTRICT
);

INSERT INTO users (
    username, 
    email, 
    password_hash, 
    first_name, 
    last_name, 
    role_id,
    email_verified
) VALUES (
    'admin',
    'admin@example.com',
    -- Contraseña: 'admin123' (usar bcrypt en tu aplicación)
    '$2y$10$YourHashedPasswordHere', -- Reemplazar con hash real
    'Administrador',
    'Del Sistema',
    (SELECT id FROM roles WHERE name = 'admin'),
    TRUE
) ON CONFLICT (username) DO NOTHING;






SELECT u.username, u.email, u.first_name, u.last_name, u.role_id, r.name, r.description, u.is_active FROM users u inner join roles r on u.role_id = r.id;












