-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS barbearia;
USE barbearia;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de barbeiros
CREATE TABLE IF NOT EXISTS barbeiros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    foto VARCHAR(255),
    especialidade VARCHAR(100),
    descricao TEXT,
    status TINYINT(1) DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de serviços
CREATE TABLE IF NOT EXISTS servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    duracao INT NOT NULL COMMENT 'Duração em minutos',
    status TINYINT(1) DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de agendamentos
CREATE TABLE IF NOT EXISTS agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    servico_id INT NOT NULL,
    barbeiro_id INT NOT NULL,
    data_agendamento DATE NOT NULL,
    hora_agendamento TIME NOT NULL,
    status ENUM('agendado', 'cancelado', 'concluido') DEFAULT 'agendado',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (servico_id) REFERENCES servicos(id),
    FOREIGN KEY (barbeiro_id) REFERENCES barbeiros(id)
);

-- Inserir dados de exemplo
INSERT INTO barbeiros (nome, especialidade, descricao) VALUES
('João Silva', 'Corte Clássico', 'Especialista em cortes tradicionais e modernos'),
('Pedro Santos', 'Barba', 'Especialista em barba e bigode'),
('Carlos Oliveira', 'Corte Degradê', 'Especialista em cortes degradê e desenhos');

INSERT INTO servicos (nome, descricao, preco, duracao) VALUES
('Corte de Cabelo', 'Corte tradicional com tesoura e máquina', 50.00, 30),
('Barba', 'Aparação e modelagem de barba', 30.00, 20),
('Corte + Barba', 'Corte de cabelo e barba completo', 70.00, 50),
('Sobrancelha', 'Design e modelagem de sobrancelhas', 20.00, 15); 