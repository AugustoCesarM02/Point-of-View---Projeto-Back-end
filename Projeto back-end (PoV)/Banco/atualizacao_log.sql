-- ==========================================================
-- Atualização do log para bancos que JÁ existem (não apaga nada).
-- Execute uma vez, com o banco Povbd selecionado (aba SQL do phpMyAdmin
-- ou "mysql -u root Povbd < atualizacao_log.sql").
-- Pode ser executado de novo sem problema.
-- Bancos novos não precisam deste arquivo: o Pov.sql já traz tudo.
-- ==========================================================
USE Povbd;

-- 1) Colunas novas da tabela logs
SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'tipo');
SET @sql = IF(@existe = 0, 'ALTER TABLE logs ADD COLUMN tipo VARCHAR(10) NOT NULL DEFAULT ''acao'' AFTER id_log', 'SELECT 1');
PREPARE comando FROM @sql;
EXECUTE comando;
DEALLOCATE PREPARE comando;
SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'categoria');
SET @sql = IF(@existe = 0, 'ALTER TABLE logs ADD COLUMN categoria VARCHAR(20) NOT NULL DEFAULT ''sistema'' AFTER tipo', 'SELECT 1');
PREPARE comando FROM @sql;
EXECUTE comando;
DEALLOCATE PREPARE comando;
SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'nome_usuario');
SET @sql = IF(@existe = 0, 'ALTER TABLE logs ADD COLUMN nome_usuario VARCHAR(100) DEFAULT NULL AFTER id_usuario', 'SELECT 1');
PREPARE comando FROM @sql;
EXECUTE comando;
DEALLOCATE PREPARE comando;
SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'ip');
SET @sql = IF(@existe = 0, 'ALTER TABLE logs ADD COLUMN ip VARCHAR(45) DEFAULT NULL AFTER nome_usuario', 'SELECT 1');
PREPARE comando FROM @sql;
EXECUTE comando;
DEALLOCATE PREPARE comando;

-- Descrições maiores (o log.php grava até 1000 caracteres)
ALTER TABLE logs MODIFY descricao VARCHAR(1000) DEFAULT NULL;

-- Registros antigos, de antes das colunas novas, ficam como "acao / sistema"

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
