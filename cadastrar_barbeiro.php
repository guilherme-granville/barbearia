<?php
session_start();
require_once 'conexao.php';

// Verificar se o usuário está logado como administrador
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $_SESSION['mensagem'] = "Acesso não autorizado.";
    header("Location: index.php");
    exit();
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $especialidade = filter_input(INPUT_POST, 'especialidade', FILTER_SANITIZE_STRING);
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);

    // Validações
    if (!$nome || !$email || !$senha || !$confirmar_senha || !$especialidade) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        try {
            // Verificar se o email já existe
            $stmt = $pdo->prepare("SELECT id FROM barbeiros WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $erro = "Este email já está cadastrado.";
            } else {
                // Inserir novo barbeiro
                $stmt = $pdo->prepare("
                    INSERT INTO barbeiros (nome, email, senha, especialidade, descricao, status) 
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $nome,
                    $email,
                    password_hash($senha, PASSWORD_DEFAULT),
                    $especialidade,
                    $descricao
                ]);

                $sucesso = "Barbeiro cadastrado com sucesso!";
            }
        } catch (PDOException $e) {
            $erro = "Erro ao cadastrar barbeiro. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Barbeiro - Barbearia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cut"></i> Barbearia
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">Painel Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Cadastrar Novo Barbeiro</h2>
                        
                        <?php if ($erro): ?>
                            <div class="alert alert-danger"><?php echo $erro; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($sucesso): ?>
                            <div class="alert alert-success"><?php echo $sucesso; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha *</label>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="especialidade" class="form-label">Especialidade *</label>
                                <input type="text" class="form-control" id="especialidade" name="especialidade" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Cadastrar Barbeiro
                                </button>
                                <a href="admin.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Voltar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Contato</h5>
                    <p><i class="fas fa-phone"></i> (11) 99999-9999</p>
                    <p><i class="fas fa-envelope"></i> contato@barbearia.com</p>
                </div>
                <div class="col-md-4">
                    <h5>Horário de Funcionamento</h5>
                    <p>Segunda a Sexta: 9h às 20h</p>
                    <p>Sábado: 9h às 18h</p>
                    <p>Domingo: Fechado</p>
                </div>
                <div class="col-md-4">
                    <h5>Redes Sociais</h5>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 