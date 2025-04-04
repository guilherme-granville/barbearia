CREATE DATABASE IF NOT EXISTS barbearia;
USE barbearia;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    duracao INT NOT NULL,
    status BOOLEAN DEFAULT TRUE
);

CREATE TABLE agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    servico_id INT,
    data_agendamento DATE NOT NULL,
    hora_agendamento TIME NOT NULL,
    status ENUM('pendente', 'confirmado', 'cancelado') DEFAULT 'pendente',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (servico_id) REFERENCES servicos(id)
);

CREATE TABLE barbeiros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    status BOOLEAN DEFAULT TRUE
);

CREATE TABLE horarios_barbeiro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barbeiro_id INT,
    dia_semana INT NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    FOREIGN KEY (barbeiro_id) REFERENCES barbeiros(id)
);

-- Inserir alguns serviços padrão
INSERT INTO servicos (nome, descricao, preco, duracao) VALUES
('Corte de Cabelo', 'Corte de cabelo tradicional', 40.00, 30),
('Barba', 'Fazer a barba', 30.00, 20),
('Corte + Barba', 'Corte de cabelo e barba', 60.00, 45),
('Sobrancelha', 'Design de sobrancelha', 20.00, 15); 