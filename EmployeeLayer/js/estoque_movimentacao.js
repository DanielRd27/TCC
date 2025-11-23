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
        quantidade: movementItems[id].qty
    })).filter(item => item.quantidade !== 0); 

    if (movements.length === 0) {
        alert("Nenhuma movimentação para registrar.");
        return;
    }
    
    const funcionarioId = document.getElementById('funcionario-id').value;
    const observacao = document.getElementById('observacao').value.trim();

    const hasSaida = movements.some(mov => mov.quantidade < 0);
    if (hasSaida && observacao === '') {
        alert("A observação é OBRIGATÓRIA para movimentações de SAÍDA.");
        document.getElementById('observacao').focus();
        return;
    }
    
    const data = {
        funcionario_id: funcionarioId,
        observacao: observacao,
        movimentos: movements
    };

    console.log('🔍 DEBUG - Dados enviados:', data);
    console.log('🔍 DEBUG - URL do fetch: processar_movimentacao.php');

    // FAZER REQUISIÇÃO COM DEBUG COMPLETO
    fetch('processar_movimentacao.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    })
    .then(response => {
        console.log('🔍 DEBUG - Status HTTP:', response.status);
        console.log('🔍 DEBUG - URL da resposta:', response.url);
        console.log('🔍 DEBUG - Headers:', response.headers);
        
        // Primeiro leia como texto para ver o que realmente vem
        return response.text().then(text => {
            console.log('🔍 DEBUG - Resposta BRUTA:', text);
            
            // Tenta parsear como JSON
            try {
                const json = JSON.parse(text);
                console.log('🔍 DEBUG - JSON parseado:', json);
                return json;
            } catch (e) {
                console.error('🔍 DEBUG - Erro ao parsear JSON:', e);
                console.log('🔍 DEBUG - Primeiros 500 chars da resposta:', text.substring(0, 500));
                throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
            }
        });
    })
    .then(result => {
        console.log('✅ DEBUG - Resultado final:', result);
        if (result.success) {
            alert("🎉 " + result.message);
            movementItems = {};
            updateMovementList();
        } else {
            alert("❌ " + result.message);
        }
    })
    .catch(error => {
        console.error('💥 DEBUG - Erro completo:', error);
        console.error('💥 DEBUG - Stack trace:', error.stack);
        alert("Erro de comunicação: " + error.message);
    });
}