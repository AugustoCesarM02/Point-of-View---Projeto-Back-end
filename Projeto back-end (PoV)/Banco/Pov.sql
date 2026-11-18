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
  biografia VARCHAR(150) DEFAULT NULL,
  foto_usuario VARCHAR(255) DEFAULT NULL,
  tipo_id INT NOT NULL DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tipo_id) REFERENCES tipos_usuario(tipo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fotos da galeria de cada usuário (os arquivos ficam em Perfil/fotos/)
CREATE TABLE IF NOT EXISTS fotos (
  id_foto INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  arquivo VARCHAR(255) NOT NULL,
  criada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_fotos_usuario (id_usuario, criada_em),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
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
  tipo VARCHAR(10) NOT NULL DEFAULT 'acao',          -- acao, mudanca ou erro
  categoria VARCHAR(20) NOT NULL DEFAULT 'sistema',  -- acesso, perfil, fotos, posts, admin ou sistema
  acao VARCHAR(100) NOT NULL,
  descricao VARCHAR(1000) DEFAULT NULL,
  id_usuario INT DEFAULT NULL,
  nome_usuario VARCHAR(100) DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_logs_data (data_hora),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- Gatilhos: gravam no log as MUDANÇAS nos dados (tipo "mudanca"),
-- não importa qual arquivo PHP fez a alteração.
-- O PHP avisa quem está logado em @log_usuario, @log_nome e @log_ip
-- (Controladores/log.php, função log_definir_ator).
-- Senhas nunca vão para o log: só aparece que a senha foi alterada.
-- ==========================================================
DELIMITER $$

DROP TRIGGER IF EXISTS trg_usuarios_ai$$
CREATE TRIGGER trg_usuarios_ai AFTER INSERT ON usuarios
FOR EACH ROW
BEGIN
  INSERT INTO logs (tipo, categoria, acao, descricao, id_usuario, nome_usuario, ip)
  VALUES ('mudanca', 'acesso', 'Usuário criado',
          CONCAT('Usuário #', NEW.id_usuario, ' (', NEW.nome, ')'),
          COALESCE(@log_usuario, NEW.id_usuario), COALESCE(@log_nome, NEW.nome), @log_ip);
END$$

DROP TRIGGER IF EXISTS trg_usuarios_au$$
CREATE TRIGGER trg_usuarios_au AFTER UPDATE ON usuarios
FOR EACH ROW
BEGIN
  DECLARE campos VARCHAR(300);

  SET campos = CONCAT_WS(', ',
    IF(NOT (OLD.nome <=> NEW.nome), 'nome', NULL),
    IF(NOT (OLD.email <=> NEW.email), 'e-mail', NULL),
    IF(NOT (OLD.senha <=> NEW.senha), 'senha', NULL),
    IF(NOT (OLD.cpf <=> NEW.cpf), 'CPF', NULL),
    IF(NOT (OLD.data_de_nascimento <=> NEW.data_de_nascimento), 'data de nascimento', NULL),
    IF(NOT (OLD.sexo <=> NEW.sexo), 'gênero', NULL),
    IF(NOT (OLD.nome_materno <=> NEW.nome_materno), 'nome da mãe', NULL),
    IF(NOT (OLD.telefone_celular <=> NEW.telefone_celular), 'celular', NULL),
    IF(NOT (OLD.telefone_fixo <=> NEW.telefone_fixo), 'telefone fixo', NULL),
    IF(NOT (OLD.endereco <=> NEW.endereco), 'endereço', NULL),
    IF(NOT (OLD.biografia <=> NEW.biografia), 'biografia', NULL),
    IF(NOT (OLD.foto_usuario <=> NEW.foto_usuario), 'foto de perfil', NULL));

  IF campos <> '' THEN
    INSERT INTO logs (tipo, categoria, acao, descricao, id_usuario, nome_usuario, ip)
    VALUES ('mudanca', 'perfil', 'Perfil atualizado',
            CONCAT('Usuário #', NEW.id_usuario, ' - campos alterados: ', campos),
            @log_usuario, @log_nome, @log_ip);
  END IF;

  IF NOT (OLD.tipo_id <=> NEW.tipo_id) THEN
    INSERT INTO logs (tipo, categoria, acao, descricao, id_usuario, nome_usuario, ip)
    VALUES ('mudanca', 'admin', 'Tipo de usuário alterado',
            CONCAT('Usuário #', NEW.id_usuario, ': tipo ', OLD.tipo_id, ' para ', NEW.tipo_id),
            @log_usuario, @log_nome, @log_ip);
  END IF;
END$$

DROP TRIGGER IF EXISTS trg_usuarios_ad$$
CREATE TRIGGER trg_usuarios_ad AFTER DELETE ON usuarios
FOR EACH ROW
BEGIN
  -- Se o próprio usuário se excluiu, id_usuario fica nulo (a chave estrangeira não aceitaria um id que não existe mais)
  INSERT INTO logs (tipo, categoria, acao, descricao, id_usuario, nome_usuario, ip)
  VALUES ('mudanca', 'admin', 'Usuário excluído',
          CONCAT('Usuário #', OLD.id_usuario, ' (', OLD.nome, ')'),
          IF(@log_usuario <=> OLD.id_usuario, NULL, @log_usuario), @log_nome, @log_ip);
END$$

DROP TRIGGER IF EXISTS trg_fotos_ai$$
CREATE TRIGGER trg_fotos_ai AFTER INSERT ON fotos
FOR EACH ROW
BEGIN
  INSERT INTO logs (tipo, categoria, acao, descricao, id_usuario, nome_usuario, ip)
  VALUES ('mudanca', 'fotos', 'Foto adicionada',
          CONCAT('Foto #', NEW.id_foto, ' do usuário #', NEW.id_usuario),
          @log_usuario, @log_nome, @log_ip);
END$$

DROP TRIGGER IF EXISTS trg_fotos_ad$$
CREATE TRIGGER trg_fotos_ad AFTER DELETE ON fotos
FOR EACH ROW
BEGIN
  INSERT INTO logs (tipo, categoria, acao, descricao, id_usuario, nome_usuario, ip)
  VALUES ('mudanca', 'fotos', 'Foto excluída',
          CONCAT('Foto #', OLD.id_foto, ' do usuário #', OLD.id_usuario),
          IF(@log_usuario <=> OLD.id_usuario AND NOT EXISTS (SELECT 1 FROM usuarios WHERE id_usuario = OLD.id_usuario), NULL, @log_usuario),
          @log_nome, @log_ip);
END$$

DROP TRIGGER IF EXISTS trg_postagem_ai$$
CREATE TRIGGER trg_postagem_ai AFTER INSERT ON postagem
FOR EACH ROW
BEGIN
  INSERT INTO logs (tipo, categoria, acao, descricao, id_usuario, nome_usuario, ip)
  VALUES ('mudanca', 'posts', 'Post criado',
          CONCAT('Post #', NEW.id_post, ' (', NEW.nome_post, ') do usuário #', NEW.id_usuario),
          @log_usuario, @log_nome, @log_ip);
END$$

DROP TRIGGER IF EXISTS trg_postagem_au$$
CREATE TRIGGER trg_postagem_au AFTER UPDATE ON postagem
FOR EACH ROW
BEGIN
  INSERT INTO logs (tipo, categoria, acao, descricao, id_usuario, nome_usuario, ip)
  VALUES ('mudanca', 'posts', 'Post editado',
          CONCAT('Post #', NEW.id_post, ' (', NEW.nome_post, ')'),
          @log_usuario, @log_nome, @log_ip);
END$$

DROP TRIGGER IF EXISTS trg_postagem_ad$$
CREATE TRIGGER trg_postagem_ad AFTER DELETE ON postagem
FOR EACH ROW
BEGIN
  INSERT INTO logs (tipo, categoria, acao, descricao, id_usuario, nome_usuario, ip)
  VALUES ('mudanca', 'posts', 'Post excluído',
          CONCAT('Post #', OLD.id_post, ' (', OLD.nome_post, ')'),
          @log_usuario, @log_nome, @log_ip);
END$$

DELIMITER ;
