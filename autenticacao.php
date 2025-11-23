<?php
require_once 'db.php';

/**
 * Autentica um usuário e armazena seus dados na sessão.
 */
function autenticar($login, $senha) {
    $pdo = conectar();
    $stmt = $pdo->prepare('SELECT id_aluno, nome, senha FROM alunos WHERE email = ?');
    $stmt->execute([$login]);
    $usuario_aluno = $stmt->fetch();
    
    // Compara a senha fornecida (em MD5) com a armazenada no banco
    if ($usuario_aluno) {
  
        if (password_verify($senha, $usuario_aluno['senha'])) { 
            $_SESSION['aluno_id'] = $usuario_aluno['id_aluno'];
            $_SESSION['aluno_nome'] = $usuario_aluno['nome'];
            return true;
        }
    }
    # Realiza busca na tabela funcionarios
    $stmt = $pdo->prepare('SELECT id_funcionario, nome, senha, cargo FROM funcionarios WHERE login = ?');
    $stmt->execute([$login]);
    $usuario_funcionario = $stmt->fetch();

    if ($usuario_funcionario) {
        if (password_verify($senha, $usuario_funcionario['senha'])) { 
            $_SESSION['funcionario_id'] = $usuario_funcionario['id_funcionario'];
            $_SESSION['funcionario_cargo'] = $usuario_funcionario['cargo'];
            $_SESSION['funcionario_nome'] = $usuario_funcionario['nome'];
            $_SESSION['nivel'] = 'funcionario';
            return true;
        }
    }
    return false;
}

/**
 * Verifica se o usuário está logado; caso contrário, redireciona para a tela de login.
 */
function verifica_aluno() {
    if (!isset($_SESSION['aluno_id'])) {
        header('Location: ../index.php');
        exit;
    }
}


function verifica_funcionario() {
    if (!isset($_SESSION['funcionario_id'])) {
        header('Location: ../index.php');
        exit;
    }
}


/**
 * Realiza o logout do usuário, limpando a sessão.
 */
function logout() {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}