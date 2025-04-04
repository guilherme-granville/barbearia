# Sistema de Agendamento para Barbearia

Um sistema web completo para gerenciamento de agendamentos de uma barbearia, desenvolvido com PHP, MySQL, HTML, CSS e JavaScript.

## Funcionalidades

- Cadastro e login de usuários
- Agendamento de horários de 30 em 30 minutos
- Visualização dos agendamentos do usuário
- Cancelamento de agendamentos
- Interface responsiva e moderna
- Validações de formulários
- Animações suaves
- Máscara de telefone
- Calendário interativo
- Proteção contra SQL Injection
- Senhas criptografadas

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache, Nginx, etc.)
- Navegador web moderno com JavaScript habilitado

## Instalação

1. Clone este repositório para seu servidor web:
```bash
git clone https://seu-repositorio/barbearia.git
```

2. Crie um banco de dados MySQL:
```sql
CREATE DATABASE barbearia;
```

3. Importe o arquivo `database/schema.sql` para criar as tabelas:
```bash
mysql -u seu_usuario -p barbearia < database/schema.sql
```

4. Configure a conexão com o banco de dados em `config/database.php`:
```php
$host = 'localhost';
$dbname = 'barbearia';
$username = 'seu_usuario';
$password = 'sua_senha';
```

5. Certifique-se de que o servidor web tem permissões de escrita nas pastas necessárias.

## Estrutura do Projeto

```
barbearia/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── config/
│   └── database.php
├── database/
│   └── schema.sql
├── index.php
├── login.php
├── cadastro.php
├── agendar.php
├── agendamentos.php
└── logout.php
```

## Uso

1. Acesse o sistema através do navegador:
```
http://seu-servidor/barbearia
```

2. Crie uma conta de usuário
3. Faça login
4. Agende seu horário
5. Gerencie seus agendamentos

## Segurança

- Todas as senhas são armazenadas com hash seguro
- Proteção contra SQL Injection usando prepared statements
- Validação de dados em todos os formulários
- Proteção contra XSS usando htmlspecialchars
- Sessões seguras

## Contribuição

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## Contato

Seu Nome - [@seu_twitter](https://twitter.com/seu_twitter) - email@exemplo.com

Link do Projeto: [https://github.com/seu-usuario/barbearia](https://github.com/seu-usuario/barbearia) 