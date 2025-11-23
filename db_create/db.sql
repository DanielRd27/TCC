-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS RCL_DB;
USE RCL_DB;

-- ===========================
-- TABELA: Alunos
-- ===========================
CREATE TABLE Alunos (
    id_aluno INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone NUMBER,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ===========================
-- TABELA: Funcionarios
-- ===========================
CREATE TABLE Funcionarios (
    id_funcionario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    login VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone NUMBER NOT NULL,
    cargo VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL
);

-- ===========================
-- TABELA: Turmas
-- ===========================
CREATE TABLE Turmas (
    id_turma INT AUTO_INCREMENT PRIMARY KEY,
    nome_turma VARCHAR(100) NOT NULL,
    data_conclusao DATE NOT NULL,
    
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    update_by INT NULL, 
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_turmas_created_by 
        FOREIGN KEY (created_by) REFERENCES Funcionarios(id_funcionario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    
    CONSTRAINT fk_turmas_update_by 
        FOREIGN KEY (update_by) REFERENCES Funcionarios(id_funcionario)
        ON UPDATE CASCADE
        ON DELETE SET NULL 
);

-- ===========================
-- TABELA: Produtos
-- ===========================
CREATE TABLE Produtos (
    id_produto INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    estoque INT NOT NULL,
    estoque_minimo INT NOT NULL,
    imagen VARCHAR(225) NOT NUll,
    
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    update_by INT NULL, 
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_produtos_created_by 
        FOREIGN KEY (created_by) REFERENCES Funcionarios(id_funcionario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_produtos_update_by 
        FOREIGN KEY (update_by) REFERENCES Funcionarios(id_funcionario)
        ON UPDATE CASCADE
        ON DELETE SET NULL 
);

-- ===========================
-- TABELA: Intervalos
-- ===========================
CREATE TABLE Intervalos (
    id_intervalo INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    horario_inicio TIME NOT NULL,
    horario_fim TIME NOT NULL,
    
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    update_by INT NULL, 
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_intervalos_created_by
        FOREIGN KEY (created_by) REFERENCES Funcionarios(id_funcionario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_intervalos_update_by 
        FOREIGN KEY (update_by) REFERENCES Funcionarios(id_funcionario)
        ON UPDATE CASCADE
        ON DELETE SET NULL 
);

-- ===========================
-- TABELA: Pedidos
-- ===========================
CREATE TABLE Pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_aluno INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    codigo_retirada VARCHAR(20) NOT NULL,
    intervalo INT NOT NULL,
    forma_pagamento VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL,

    CONSTRAINT fk_pedidos_aluno
        FOREIGN KEY (id_aluno) REFERENCES Alunos(id_aluno)
        ON UPDATE CASCADE
        ON DELETE RESTRICT, 

    CONSTRAINT fk_pedidos_intervalo
        FOREIGN KEY (intervalo) REFERENCES Intervalos(id_intervalo)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- ===========================
-- TABELA: Itens_Pedido
-- ===========================
CREATE TABLE Itens_Pedido (
    id_item_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL,

    CONSTRAINT fk_itensp_pedido
        FOREIGN KEY (id_pedido) REFERENCES Pedidos(id_pedido)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_itensp_produto
        FOREIGN KEY (id_produto) REFERENCES Produtos(id_produto)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

-- ===========================
-- TABELA: Alunos_Turmas
-- ===========================
CREATE TABLE Alunos_Turmas (
    id_turma INT NOT NULL,
    id_aluno INT NOT NULL,
    PRIMARY KEY (id_turma, id_aluno),

    CONSTRAINT fk_alunosturmas_turma
        FOREIGN KEY (id_turma) REFERENCES Turmas(id_turma)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_alunosturmas_aluno
        FOREIGN KEY (id_aluno) REFERENCES Alunos(id_aluno)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- ===========================
-- TABELA: Turma_Intervalo
-- ===========================
CREATE TABLE Turma_Intervalo (
    id_turma INT NOT NULL,
    id_intervalo INT NOT NULL,
    ordem INT NOT NULL DEFAULT 1,
    PRIMARY KEY (id_turma, id_intervalo),

    CONSTRAINT fk_turmaintervalo_turma
        FOREIGN KEY (id_turma) REFERENCES Turmas(id_turma)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_turmaintervalo_intervalo
        FOREIGN KEY (id_intervalo) REFERENCES Intervalos(id_intervalo)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- ===========================
-- TABELA: Retiradas
-- ===========================
CREATE TABLE Retiradas (
    id_pedido INT PRIMARY KEY,
    id_funcionario INT NOT NULL,
    data_retirada DATETIME NOT NULL,

    CONSTRAINT fk_retiradas_pedido
        FOREIGN KEY (id_pedido) REFERENCES Pedidos(id_pedido)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_retiradas_funcionario
        FOREIGN KEY (id_funcionario) REFERENCES Funcionarios(id_funcionario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- ===========================
-- TABELA: Movimentacao_estoque
-- ===========================
CREATE TABLE Movimentacao_estoque (
    id_movimentacao INT AUTO_INCREMENT PRIMARY KEY,
    id_produto INT NOT NULL,
    tipo_movimentacao VARCHAR(20) NOT NULL,
    quantidade INT NOT NULL,
    data_movimentacao DATETIME NOT NULL,
    observacao varchar(225) NOT NULL,
    movimentado_by INT NOT NULL,

    CONSTRAINT fk_mov_produto
        FOREIGN KEY (id_produto) REFERENCES Produtos(id_produto)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_mov_funcionario
        FOREIGN KEY (movimentado_by) REFERENCES Funcionarios(id_funcionario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);
