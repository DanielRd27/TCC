# 📦 Sistema de Gestão Integrada (TCC)

Este projeto de conclusão de curso consiste em um Web App Full-Stack desenvolvido para o gerenciamento de pedidos, estoque e controle de pessoal. A arquitetura foi planejada de forma modular, separando as entidades principais em camadas para facilitar a manutenção e escalabilidade do código.

---

## 🏗️ Arquitetura do Projeto

O diferencial deste projeto é a organização por camadas de domínio, garantindo que as regras de negócio e as interfaces fiquem bem distribuídas:

- **`EmployeeLayer/`**: Gerenciamento completo do ciclo de vida dos funcionários.
- **`UserLayer/`**: Controle de usuários do sistema, permissões e perfis.
- **`db_create/`**: Contém os scripts SQL para provisionamento do banco de dados e arquivos de estilo específicos.
- **`img/`**: Repositório de ativos visuais do sistema.

### Fluxo de Autenticação e Base
- **`login.php` & `register.php`**: Interfaces de entrada e criação de novas contas.
- **`autenticacao.php`**: Lógica centralizada para validação de sessões e segurança.
- **`db.php`**: Singleton/Script de conexão centralizada com o banco de dados MySQL.

---

## 🛠️ Tecnologias Utilizadas

- **Backend:** ![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white) (Lógica de servidor e processamento)
- **Frontend:** ![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black) ![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat-square&logo=css3&logoColor=white) (Interface dinâmica e responsiva)
- **Database:** ![MySQL](https://img.shields.io/badge/MySQL-00000F?style=flat-square&logo=mysql&logoColor=white) (Armazenamento relacional de dados)

---

## ⚙️ Instalação e Uso

1. **Servidor Local:** Utilize o XAMPP ou WAMP para rodar o servidor Apache e MySQL.
2. **Banco de Dados:** Importe o script SQL localizado na pasta `db_create/` para o seu banco de dados local.
3. **Configuração:** Verifique se as credenciais em `db.php` correspondem às do seu ambiente local.
4. **Acesso:** Clone o repositório na pasta `htdocs` e acesse `localhost/TCC` no navegador.

---

## 🧠 Aprendizados de Engenharia de Software

Este TCC foi um laboratório para a aplicação de práticas essenciais:
- **Segurança:** Implementação de `logout.php` e `autenticacao.php` para proteção contra acessos não autorizados.
- **Modularização:** Uso de camadas (`Layers`) para organizar entidades distintas do sistema.
- **Persistência de Dados:** Estruturação de um banco de dados robusto para suportar operações de CRUD simultâneas.

---

## 👨‍💻 Desenvolvedor
**Daniel Roque** *Técnico em Desenvolvimento de Sistemas* [LinkedIn](https://www.linkedin.com/in/daniel-roque-165732254) | [GitHub](https://github.com/DanielRd27)