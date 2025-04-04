<?php
session_start();
require_once 'conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar se o ID do agendamento foi fornecido
if (!isset($_GET['id'])) {
    header('Location: meus_agendamentos.php');
    exit;
}

$agendamento_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$agendamento_id) {
    header('Location: meus_agendamentos.php');
    exit;
}

// Verificar se o agendamento pertence ao usuário e está em um status que pode ser cancelado
try {
    $stmt = $pdo->prepare("
        SELECT status 
        FROM agendamentos 
        WHERE id = ? AND usuario_id = ? AND status IN ('pendente', 'confirmado')
    ");
    $stmt->execute([$agendamento_id, $_SESSION['usuario_id']]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        $_SESSION['erro'] = "Agendamento não encontrado ou não pode ser cancelado.";
        header('Location: meus_agendamentos.php');
        exit;
    }

    // Atualizar o status do agendamento para cancelado
    $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = ?");
    $stmt->execute([$agendamento_id]);

    $_SESSION['mensagem'] = "Agendamento cancelado com sucesso.";
    header('Location: meus_agendamentos.php');
    exit;
} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro ao cancelar agendamento. Tente novamente.";
    header('Location: meus_agendamentos.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelar Agendamento - Barbearia</title>
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
                        <a class="nav-link" href="servicos.php">Serviços</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="agendar.php">Agendar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="meus_agendamentos.php">Meus Horários</a>
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
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <h2 class="mb-4">Cancelando Agendamento</h2>
                        <div class="spinner-border text-primary mb-4" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p>Por favor, aguarde enquanto processamos seu cancelamento...</p>
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