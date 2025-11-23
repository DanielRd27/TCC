INSERT INTO Funcionarios (nome, login, senha, telefone, cargo, created_at) VALUES
('Ana Silva', 'anasilva', 'hash_senha_ana', '11987654321', 'Administrador', NOW()),
('Bruno Mendes', 'brunom', 'hash_senha_bruno', '11998765432', 'Estoquista', NOW()),
('Carla Souza', 'carlinha', 'hash_senha_carla', '11976543210', 'Atendente', NOW());

INSERT INTO Alunos (nome, email, senha, telefone, created_at) VALUES
('David Lima', 'david.l@email.com', 'hash_senha_david', '(11) 91234-5678', NOW()),
('Eva Ferreira', 'eva.f@email.com', 'hash_senha_eva', '(11) 92345-6789', NOW()),
('Felipe Gomes', 'felipe.g@email.com', 'hash_senha_felipe', '(11) 93456-7890', NOW());

INSERT INTO Intervalos (nome, horario_inicio, horario_fim, created_by, created_at) VALUES
('Manhã', '09:30:00', '09:45:00', 1, NOW()),
('Tarde 1', '12:30:00', '13:00:00', 1, NOW()),
('Tarde 2', '15:30:00', '15:45:00', 1, NOW());

INSERT INTO Turmas (nome_turma, data_conclusao, created_by, created_at) VALUES
('ADS 2024.1 - Manhã', '2024-12-15', 1, NOW()),
('Redes 2023.2 - Noite', '2023-06-30', 1, NOW()),
('Gestão 2024.2 - Tarde', '2025-01-30', 1, NOW());

INSERT INTO Produtos (nome, descricao, preco_unitario, estoque, estoque_minimo, created_by, created_at, imagem, categoria) VALUES
('Hambúrguer', 'Hambuguer com tomate, alface e queijo', 12.00, 100, 10, 2, NOW(), "img/produtos/hamburguer", "Lanches"),
('Suco de Laranja', 'Copo de 300ml natural', 8.50, 50, 5, 2, NOW(), "img/produtos/sucoLaranja", "Bebidas"),
('Chocolate em Barra', 'Barra 90g ao leite', 7.00, 75, 15, 2, NOW(), "img/produtos/barraChocolate", "Doces");

-- David (id_aluno=1) está na turma ADS (id_turma=1)
INSERT INTO Alunos_Turmas (id_turma, id_aluno) VALUES (1, 1);
-- Eva (id_aluno=2) está na turma ADS (id_turma=1)
INSERT INTO Alunos_Turmas (id_turma, id_aluno) VALUES (1, 2);
-- Felipe (id_aluno=3) está na turma Redes (id_turma=2)
INSERT INTO Alunos_Turmas (id_turma, id_aluno) VALUES (2, 3);

-- Turma ADS (id_turma=1) pode retirar nos intervalos Manhã (id_intervalo=1) e Tarde 1 (id_intervalo=2)
INSERT INTO Turma_Intervalo (id_turma, id_intervalo) VALUES (1, 1);
INSERT INTO Turma_Intervalo (id_turma, id_intervalo) VALUES (1, 2);
-- Turma Redes (id_turma=2) pode retirar no intervalo Tarde 2 (id_intervalo=3)
INSERT INTO Turma_Intervalo (id_turma, id_intervalo) VALUES (2, 3);

INSERT INTO Pedidos (id_aluno, status, codigo_retirada, intervalo, forma_pagamento, created_at) VALUES
(1, 'Concluído', 'A1234', 2, 'Pix', NOW()),      
(2, 'Pendente', 'B5678', 3, 'Pix', NOW());       

INSERT INTO Itens_Pedido (id_pedido, id_produto, quantidade) VALUES
(1, 1, 2), -- 2 hamburguer
(1, 2, 1), -- 1 Suco de Laranja
(2, 1, 2), -- 2 hamburguer
(2, 3, 1); -- 1 barra de chocolate

-- Assume que o pedido 1 já foi feito e agora está sendo retirado.
INSERT INTO Retiradas (id_pedido, id_funcionario, data_retirada) VALUES
(1, 3, NOW());

-- Movimentação de entrada de estoque do Produto 1 (Pão de Queijo)
INSERT INTO Movimentacao_estoque (id_produto, tipo_movimentacao, quantidade, data_movimentacao, movimentado_by, observacao) VALUES
(1, 'Entrada', 100, NOW(), 1, 'Entrada inicial'),
(2, 'Entrada', 50, NOW(), 1, 'Entrada inicial'),
(3, 'Entrada', 75, NOW(), 1, 'Entrada inicial');