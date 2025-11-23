const inputSSPreview = document.getElementById("inputSSPreview");
const resultSSPreview = document.getElementById("resultSSPreview");

if (inputSSPreview && resultSSPreview) {
    inputSSPreview.addEventListener("input", () => {
        let price = 39.99
        let weight = parseFloat(inputSSPreview.value);
        let calculatedPrice = (weight/1000) * price;

        if (isNaN(calculatedPrice) || calculatedPrice < 0) {
            resultSSPreview.textContent = "R$ 0.00";
            return;
        }

        resultSSPreview.textContent = `R$ ${calculatedPrice.toFixed(2)}`;
    });
}

function returnMenuLogin() {
    const mainPage = document.getElementById('first-card-login');
    const loginPage = document.getElementById('card-login');
    const registerPage = document.getElementById('card-register');

    if (mainPage) {
        mainPage.classList.remove('d-none');
        mainPage.classList.add('card-form');
    }

    if (loginPage) {
        loginPage.classList.add('d-none');
        loginPage.classList.remove('card-login');
    }

    if (registerPage) {
        registerPage.classList.add('d-none');
        registerPage.classList.remove('card-register');
    }
}

function goToLoginPage() {
    const mainPage = document.getElementById('first-card-login');
    const loginPage = document.getElementById('card-login');

    if (mainPage) {
        mainPage.classList.add('d-none');
        mainPage.classList.remove('card-form');
    }

    if (loginPage) {
        loginPage.classList.remove('d-none');
        loginPage.classList.add('card-login');
    }
}

function goToRegisterPage() {
    const mainPage = document.getElementById('first-card-login');
    const registerPage = document.getElementById('card-register');

    if (mainPage) {
        mainPage.classList.add('d-none');
        mainPage.classList.remove('card-form');
    }

    if (registerPage) {
        registerPage.classList.remove('d-none');
        registerPage.classList.add('card-register');
    }
}

// Adicionar quantidade ao produto 
function increaseQuantity(valor) {
    let quantityElement = document.getElementById('quantidade-produto');
    let quantity = parseInt(quantityElement.innerText);
    quantity += valor;
    quantityElement.innerText = quantity;
}

function filtrar() {
    const busca = document.getElementById('busca').value;
    let categoria = document.getElementById('filtro')?.value;

    // se filtro for vazio, null ou undefined → define como 'todos'
    if (!categoria || categoria === "undefined") {
        categoria = "todos";
    }

    fetch(`busca.php?busca=${encodeURIComponent(busca)}&filtro=${encodeURIComponent(categoria)}`)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const novaLista = doc.querySelector('#lista-produtos');

            document.querySelector('#lista-produtos').innerHTML = novaLista.innerHTML;
        });
}
