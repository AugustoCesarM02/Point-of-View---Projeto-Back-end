-- Atualização para o perfil público (biografia e fotos salvas no servidor).
-- Execute UMA vez no banco que já existe (phpMyAdmin > Povbd > SQL).
-- Se aparecer "Duplicate column name 'biografia'", essa parte já foi executada: pode ignorar.

USE Povbd;

-- Biografia do usuário (até 150 caracteres)
ALTER TABLE usuarios ADD COLUMN biografia VARCHAR(150) DEFAULT NULL AFTER endereco;

-- Fotos da galeria de cada usuário
-- ON DELETE CASCADE: ao excluir o usuário, os registros das fotos dele também são apagados
CREATE TABLE IF NOT EXISTS fotos (
  id_foto INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  arquivo VARCHAR(255) NOT NULL,
  criada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_fotos_usuario (id_usuario, criada_em),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
