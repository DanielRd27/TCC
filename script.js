// Checa se o input do SS foi alterado e mostra o valor na tela

function updateSSResult() {
    const ssPrice = 39.99;
    const ssValue = ssInput.value*0.001; // converte para kg
    const ssResult = document.getElementById('resultSSPreview');
    // formata o valor para duas casas decimais com virgula e R$
    const formattedValue = (ssValue * ssPrice).toFixed(2).replace('.', ',');
    ssResult.textContent = `R$ ${formattedValue}`;
}

const ssInput = document.getElementById('inputSSPreview');

ssInput.addEventListener('change', updateSSResult);

// Funções para navegação entre telas de login e registro
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