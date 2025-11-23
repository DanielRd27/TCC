let movementItems = {}; // {id_produto: {name: 'Nome', qty: 0}}

// Função para adicionar/selecionar um item
// Função para adicionar/selecionar um item
function addItemToMovement(id, name) {
    const movimento = prompt(`Qual o tipo de movimentação para ${name}? Digite 'E' para ENTRADA ou 'S' para SAÍDA.`);
    
    let qtySign;
    let typeText;

    if (movimento && movimento.toUpperCase() === 'E') {
        qtySign = 1; // Entrada é Positivo
        typeText = 'Entrada';
    } else if (movimento && movimento.toUpperCase() === 'S') {
        qtySign = -1; // Saída é Negativo
        typeText = 'Saída';
    } else {
        alert("Opção inválida. Nenhuma movimentação adicionada.");
        return; // Sai da função se a resposta for inválida
    }

    // Se o item já existe, atualizamos o qty (mantendo o sinal original do item)
    if (movementItems[id]) {
        // Se o item já existe, precisamos saber qual era o sinal original
        const isCurrentlyPositive = movementItems[id].qty > 0;
        
        if (isCurrentlyPositive && qtySign === 1) {
             movementItems[id].qty += 1; // Já era Entrada, continua somando
        } else if (!isCurrentlyPositive && qtySign === -1) {
             movementItems[id].qty -= 1; // Já era Saída, continua subtraindo (mais negativo)
        } else {
            // Se o usuário tentar mudar o status de Entrada para Saída (ou vice-versa),
            // é melhor recomeçar ou alertar. Vamos apenas alertar.
            alert(`O item ${name} já está na lista como ${movementItems[id].qty > 0 ? 'ENTRADA' : 'SAÍDA'}. Remova-o antes de mudar o tipo.`);
            return;
        }

    } else {
        // Se o item não existe, inicia a movimentação com o sinal correto
        movementItems[id] = { name: name, qty: qtySign, type: typeText };
    }

    updateMovementList();
}

// Função para alterar a quantidade na lista
function changeQuantity(id, delta) {
    if (movementItems[id]) {
        movementItems[id].qty += delta;
        if (movementItems[id].qty === 0) {
            // Se a quantidade zerar, remove o item
            delete movementItems[id];
        }
    }
    updateMovementList();
}

// Função principal para renderizar a lista de movimentação
function updateMovementList() {
    const list = document.getElementById('movimentacao-list');
    list.innerHTML = '';
    let totalItems = 0;

    Object.keys(movementItems).forEach(id => {
        const item = movementItems[id];
        totalItems += Math.abs(item.qty);
        
        const listItem = document.createElement('li');
        
        // Determina se é Entrada (+) ou Saída (-)
        const type = item.qty > 0 ? 'Entrada' : 'Saída';
        const displayQty = Math.abs(item.qty);
        const sign = item.qty > 0 ? '+' : '-';

        listItem.innerHTML = `
            <span>${item.name}</span>
            <div class="item-controls">
                <button class="qty-button" style="background: ${item.qty > 0 ? 'green' : 'red'};" 
                    onclick="changeQuantity(${id}, ${item.qty > 0 ? -1 : 1})">
                    ${item.qty > 0 ? '-' : '+'}
                </button>
                <input type="text" class="qty-input" value="${sign} ${displayQty}" readonly>
                <button class="qty-button" style="background: ${item.qty > 0 ? 'red' : 'green'};" 
                    onclick="changeQuantity(${id}, ${item.qty > 0 ? 1 : -1})">
                    ${item.qty > 0 ? '+' : '-'}
                </button>
            </div>
        `;
        list.appendChild(listItem);
    });

    document.getElementById('total-itens-movimentados').textContent = totalItems;
}

// Função de Pesquisa (Filtra o painel esquerdo)
function filterProducts() {
    const filter = document.getElementById('pesquisar-produto').value.toLowerCase();
    const cards = document.querySelectorAll('.product-card');

    cards.forEach(card => {
        const name = card.getAttribute('data-name').toLowerCase();
        if (name.includes(filter)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// -----------------------------------------------------------
// 3. Função de Finalização (Envia dados via AJAX)
// -----------------------------------------------------------

function finalizeMovement() {
    const movements = Object.keys(movementItems).map(id => ({
        id_produto: parseInt(id),
        quantidade: movementItems[id].qty, // O sinal aqui define se é Entrada ou Saída
        is_saida: movementItems[id].qty < 0 // Novo campo para checagem rápida
    })).filter(item => item.quantidade !== 0); 

    if (movements.length === 0) {
        alert("Nenhuma movimentação para registrar.");
        return;
    }
    
    const funcionarioId = document.getElementById('funcionario-id').value;
    const observacao = document.getElementById('observacao').value.trim();

    // 1. CHECAGEM CRUCIAL: Verificar se há alguma SAÍDA na lista
    const hasSaida = movements.some(mov => mov.is_saida);

    // 2. Se houver SAÍDA e a Observação estiver vazia, impede a finalização
    if (hasSaida && observacao === '') {
        alert("A observação é OBRIGATÓRIA para movimentações de SAÍDA.");
        document.getElementById('observacao').focus();
        return;
    }
    
    // ... (resto do código AJAX)
    
    const data = {
        funcionario_id: funcionarioId,
        observacao: observacao,
        movimentos: movements
    };

    // Enviar dados via AJAX para o processamento PHP
    fetch('processar_movimentacao.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert("Movimentação registrada com sucesso!");
            window.location.reload(); // Recarrega para ver o estoque atualizado
        } else {
            alert("Erro ao registrar movimentação: " + result.message);
        }
    })
    .catch(error => {
        console.error('Erro na comunicação:', error);
        alert("Erro de comunicação com o servidor.");
    });
}