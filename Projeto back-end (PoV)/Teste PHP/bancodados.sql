DROP DATABASE IF EXISTS bancodados;
CREATE DATABASE if NOT EXISTS bancodados;

Use bancodados;

CREATE TABLE IF not exists produto (
  id_produto int primary key not null auto_increment,
  nome VARCHAR(10) not null,
  quantidade int not null,
  valor float not null)