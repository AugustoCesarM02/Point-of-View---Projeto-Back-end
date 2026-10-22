-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 23/09/2026 às 04:14
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `projeto_back-end_faculdade`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `destaque`
--

CREATE TABLE `destaque` (
  `id_destaque` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfil`
--

CREATE TABLE `perfil` (
  `id_perfil` int(11) NOT NULL,
  `nome` varchar(20) DEFAULT NULL,
  `id_usuario_comum` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `postagem`
--

CREATE TABLE `postagem` (
  `id_post` int(11) NOT NULL,
  `nome_post` varchar(20) DEFAULT NULL,
  `tag` varchar(20) DEFAULT NULL,
  `id_usuario_comum` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuariocomum`
--

CREATE TABLE `usuariocomum` (
  `id_usuario_comum` int(11) NOT NULL,
  `email` varchar(60) DEFAULT NULL,
  `senha` varchar(50) DEFAULT NULL,
  `CPF` int(11) DEFAULT NULL,
  `data_de_nascimento` int(6) DEFAULT NULL,
  `sexo` varchar(10) DEFAULT NULL,
  `nome_materno` varchar(50) DEFAULT NULL,
  `telefone_celular` int(13) DEFAULT NULL,
  `telefone_fixo` int(13) DEFAULT NULL,
  `endereço` int(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuariocomum_post`
--

CREATE TABLE `usuariocomum_post` (
  `id_usuario_comum` int(11) NOT NULL,
  `id_post` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuariomaster`
--

CREATE TABLE `usuariomaster` (
  `id_usuario_master` int(11) NOT NULL,
  `nome` varchar(40) DEFAULT NULL,
  `senha` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `destaque`
--
ALTER TABLE `destaque`
  ADD PRIMARY KEY (`id_destaque`);

--
-- Índices de tabela `perfil`
--
ALTER TABLE `perfil`
  ADD PRIMARY KEY (`id_perfil`);

--
-- Índices de tabela `postagem`
--
ALTER TABLE `postagem`
  ADD PRIMARY KEY (`id_post`),
  ADD KEY `fk_id_usuario_comum` (`id_usuario_comum`);

--
-- Índices de tabela `usuariocomum`
--
ALTER TABLE `usuariocomum`
  ADD PRIMARY KEY (`id_usuario_comum`);

--
-- Índices de tabela `usuariocomum_post`
--
ALTER TABLE `usuariocomum_post`
  ADD PRIMARY KEY (`id_usuario_comum`,`id_post`),
  ADD KEY `fk_postagem` (`id_post`);

--
-- Índices de tabela `usuariomaster`
--
ALTER TABLE `usuariomaster`
  ADD PRIMARY KEY (`id_usuario_master`);

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `postagem`
--
ALTER TABLE `postagem`
  ADD CONSTRAINT `fk_id_usuario_comum` FOREIGN KEY (`id_usuario_comum`) REFERENCES `usuariocomum` (`id_usuario_comum`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Restrições para tabelas `usuariocomum_post`
--
ALTER TABLE `usuariocomum_post`
  ADD CONSTRAINT `fk_postagem` FOREIGN KEY (`id_post`) REFERENCES `postagem` (`id_post`),
  ADD CONSTRAINT `fk_usuariocomum` FOREIGN KEY (`id_usuario_comum`) REFERENCES `usuariocomum` (`id_usuario_comum`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
