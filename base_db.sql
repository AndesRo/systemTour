CREATE DATABASE IF NOT EXISTS sistema_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE sistema_db;

CREATE TABLE clientes (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  telefono VARCHAR(20) DEFAULT NULL,
  fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE usuarios_admin (
  id INT(11) NOT NULL AUTO_INCREMENT,
  usuario VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  last_login DATETIME DEFAULT NULL,
  failed_attempts INT(11) DEFAULT 0,
  locked_until DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE reclamos (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  telefono VARCHAR(20) DEFAULT NULL,
  fecha_incidente DATE NOT NULL,
  lugar VARCHAR(200) NOT NULL,
  descripcion TEXT NOT NULL,
  estado ENUM('Pendiente','En proceso','Resuelto') DEFAULT 'Pendiente',
  comentario_admin TEXT DEFAULT NULL,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ticket_id VARCHAR(20) DEFAULT NULL,
  cliente_id INT(11) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ticket_id (ticket_id),
  KEY cliente_id (cliente_id),
  CONSTRAINT reclamos_ibfk_1 FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




INSERT INTO clientes (id, nombre, email, password, telefono, fecha_registro) VALUES

INSERT INTO usuarios_admin (id, usuario, password, last_login, failed_attempts, locked_until) VALUES


INSERT INTO reclamos (id, nombre, email, telefono, fecha_incidente, lugar, descripcion, estado, comentario_admin, fecha_creacion, ticket_id, cliente_id) VALUES
