-- INSERTs COM SENHA "password" JÁ HASHED
INSERT INTO Funcionarios (nome, login, senha, telefone, cargo, created_at) VALUES
('Daniel Roque', 'daniel', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '11987654321', 'Administrador', NOW()),
('Leticia', 'leticia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '11998765432', 'Estoquista', NOW()),
('Carla Souza', 'carlinha', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '11976543210', 'Atendente', NOW());

INSERT INTO Alunos (nome, email, senha, telefone, created_at) VALUES
('Sarah Veiga', 'sarah@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(11) 92345-6789', NOW()),
('Eloisa Veiga', 'elo@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(11) 93456-7890', NOW());

INSERT INTO Turmas (nome_turma, data_conclusao, created_by, created_at) VALUES
('2DB', '2025-12-09', 1, NOW()),
('2EB', '2025-12-09', 1, NOW());

INSERT INTO Produtos (nome, descricao, preco_unitario, estoque, estoque_minimo, created_by, created_at, imagem, categoria) VALUES
('Hambúrguer', 'Hambuguer com tomate, alface e queijo', 12.00, 100, 10, 2, NOW(), "../img/Hamburguer.png", "Lanches"),
('Suco de Laranja', 'Copo de 300ml natural', 8.50, 50, 5, 2, NOW(), "../img/SucoLaranja.png", "Bebidas"),
('Chocolate em Barra - Lacta', 'Barra 90g ao leite', 7.00, 75, 15, 2, NOW(), "../img/BarraChocolate.png", "Doces");

-- Sarah (id_aluno=1) está na turma ADS (id_turma=1)
INSERT INTO Alunos_Turmas (id_turma, id_aluno) VALUES (1, 1);
-- Elo (id_aluno=2) está na turma ADS (id_turma=1)
INSERT INTO Alunos_Turmas (id_turma, id_aluno) VALUES (2, 2);

-- Movimentação de entrada de estoque do Produto 1 (Pão de Queijo)
INSERT INTO Movimentacao_estoque (id_produto, tipo_movimentacao, quantidade, data_movimentacao, movimentado_by, observacao) VALUES
(1, 'Entrada', 100, NOW(), 1, 'Entrada inicial'),
(2, 'Entrada', 50, NOW(), 1, 'Entrada inicial'),
(3, 'Entrada', 75, NOW(), 1, 'Entrada inicial');