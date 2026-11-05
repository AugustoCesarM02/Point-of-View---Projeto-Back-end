CREATE DATABASE IF NOT EXISTS Povbd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Povbd;

-- Tipos de usuário: 1 = normal, 2 = admin
CREATE TABLE IF NOT EXISTS tipos_usuario (
    tipo_id INT PRIMARY KEY,
    descricao VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tipos_usuario (tipo_id, descricao) VALUES (1, 'normal'), (2, 'admin')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);


CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(60) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  cpf CHAR(11) DEFAULT NULL UNIQUE,
  data_de_nascimento DATE DEFAULT NULL,
  sexo VARCHAR(10) DEFAULT NULL,
  nome_materno VARCHAR(50) DEFAULT NULL,
  telefone_celular VARCHAR(15) DEFAULT NULL,
  telefone_fixo VARCHAR(15) DEFAULT NULL,
  endereco VARCHAR(150) DEFAULT NULL,
  tipo_id INT NOT NULL DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tipo_id) REFERENCES tipos_usuario(tipo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destaque (
  id_destaque INT AUTO_INCREMENT PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perfil (
  id_perfil INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT DEFAULT NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postagem (
  id_post INT AUTO_INCREMENT PRIMARY KEY,
  nome_post VARCHAR(20) NOT NULL,
  tag VARCHAR(20) NOT NULL,
  id_usuario INT NOT NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs (
id_log INT AUTO_INCREMENT PRIMARY KEY,
id_usuario INT DEFAULT NULL,
acao VARCHAR(100) NOT NULL,
descricao VARCHAR(255),
data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin: troque o hash pelo gerado com password_hash() no PHP
INSERT INTO usuarios (nome, email, senha, tipo_id) VALUES
('Augusto', 'admin@admin.com', 'COLE_AQUI_O_HASH_GERADO_PELO_PHP', 2);