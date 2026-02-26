CREATE TABLE IF NOT EXISTS roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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


CREATE TABLE IF NOT EXISTS document_type (
    id VARCHAR(2) PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);





CREATE TABLE IF NOT EXISTS patient (
    id SERIAL PRIMARY KEY,  
    type_code VARCHAR(2) NOT NULL,
    document VARCHAR(300) NOT NULL,
    first_name VARCHAR(100),
    middle_name VARCHAR(100),
    last_name VARCHAR(100),
    second_last_name VARCHAR(100),
    birth_date DATE,
    gender VARCHAR(20),
    code VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_document_type 
        FOREIGN KEY (type_code) 
        REFERENCES document_type(id)
        ON DELETE RESTRICT
);


CREATE TABLE IF NOT EXISTS allergies (
    id serial4 NOT NULL,
    local_code varchar(50) NULL,
    local_term varchar(255) NULL,
    "type" varchar NULL,
    category varchar NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT allergies_pkey1 PRIMARY KEY (id)
);


CREATE TABLE IF NOT EXISTS icd10 (
    icd10_code_part_a varchar(3) NOT NULL,
    icd10_code_part_b varchar(1) NOT NULL,
    name varchar(250) NULL,
    description text NULL,   
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT icd10_pkey PRIMARY KEY (icd10_code_part_a, icd10_code_part_b)
);


CREATE TABLE IF NOT EXISTS organization (
    id serial4 PRIMARY KEY,
    identifier varchar(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    "type" varchar NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);





