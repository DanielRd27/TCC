let debounceTimer;

// Função chamada ao digitar no input
function buscarPedido() {
    clearTimeout(debounceTimer);
    
    // Espera 500ms antes de enviar a requisição (debounce)
    debounceTimer = setTimeout(() => {
        const codigo = document.getElementById('codigo-retirada').value.trim();
        
        document.getElementById('status-busca').textContent = 'Buscando...';
        document.getElementById('id-pedido-concluir').value = '';
        document.getElementById('finalizar-registro').disabled = true;
        document.getElementById('pedido-info').innerHTML = '<p>Aguardando o Código de Retirada...</p>';
        document.getElementById('total-itens-exibicao').textContent = 'Total: 0 itens';


        if (codigo.length < 5) { // Evita buscas muito curtas
            document.getElementById('status-busca').textContent = '';
            return;
        }

        fetch('buscar_pedido_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `codigo_retirada=${codigo}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderPedidoDetails(data.pedido);
                document.getElementById('status-busca').textContent = `Pedido #${data.pedido.id_pedido} encontrado!`;
                document.getElementById('id-pedido-concluir').value = data.pedido.id_pedido;
                document.getElementById('finalizar-registro').disabled = false;

            } else {
                document.getElementById('pedido-info').innerHTML = `<p style="color: red;">${data.message}</p>`;
                document.getElementById('status-busca').textContent = '';
            }
        })
        .catch(error => {
            console.error('Erro na comunicação:', error);
            document.getElementById('pedido-info').innerHTML = '<p style="color: red;">Erro de comunicação com o servidor.</p>';
        });

    }, 500); // 500 milissegundos
}

// Função para renderizar os detalhes do pedido no painel direito
function renderPedidoDetails(pedido) {
    const infoDiv = document.getElementById('pedido-info');
    let totalItens = 0;

    // Cabeçalho e detalhes
    let html = `
        <p><strong>ID do Pedido:</strong> #${pedido.id_pedido}</p>
        <p><strong>Status:</strong> ${pedido.status}</p>
        <p><strong>Cliente (Aluno):</strong> ${pedido.nome_aluno || 'N/A'}</p>
        <hr/>
        <p><strong>Itens do Pedido:</strong></p>
        <ul class="item-list">
    `;

    // Lista de itens
    if (pedido.itens && pedido.itens.length > 0) {
        pedido.itens.forEach(item => {
            html += `
                <li>
                    <span class="item-qty">${item.quantidade}x</span>
                    <span>${item.nome_produto}</span>
                </li>
            `;
            totalItens += parseInt(item.quantidade);
        });
    }

    html += `</ul>`;
    infoDiv.innerHTML = html;
    document.getElementById('total-itens-exibicao').textContent = `Total: ${totalItens} itens`;
}

// Função de remoção de item (apenas para referência, pois a busca é automática)
function removeItemFromMovement(id) {
    // Não aplicável nesta tela, mas é bom ter o placeholder se o seu template exige.
}