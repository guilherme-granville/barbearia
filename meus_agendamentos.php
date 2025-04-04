<?php
session_start();
require_once 'conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Buscar agendamentos do usuário
try {
    $stmt = $pdo->prepare("
        SELECT a.*, s.nome as servico_nome, s.preco, b.nome as barbeiro_nome
        FROM agendamentos a
        JOIN servicos s ON a.servico_id = s.id
        LEFT JOIN barbeiros b ON a.barbeiro_id = b.id
        WHERE a.usuario_id = ?
        ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar agendamentos.";
}

// Processar cancelamento de agendamento
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancelar'])) {
    $agendamento_id = filter_input(INPUT_POST, 'agendamento_id', FILTER_VALIDATE_INT);
    
    if ($agendamento_id) {
        try {
            // Verificar se o agendamento pertence ao usuário
            $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$agendamento_id, $_SESSION['usuario_id']]);
            $agendamento = $stmt->fetch();
            
            if ($agendamento) {
                // Atualizar status do agendamento
                $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = ?");
                $stmt->execute([$agendamento_id]);
                
                $_SESSION['sucesso'] = "Agendamento cancelado com sucesso!";
                header("Location: meus_agendamentos.php");
                exit();
            } else {
                $erro = "Agendamento não encontrado.";
            }
        } catch (PDOException $e) {
            $erro = "Erro ao cancelar agendamento. Tente novamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos - Barbearia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .agendamento-card {
            transition: all 0.3s ease;
        }
        .agendamento-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-pendente {
            color: #ffc107;
        }
        .status-confirmado {
            color: #198754;
        }
        .status-cancelado {
            color: #dc3545;
        }
        .status-concluido {
            color: #0dcaf0;
        }
    </style>
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
        <h2 class="text-center mb-4">Meus Agendamentos</h2>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php elseif (empty($agendamentos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Você ainda não possui agendamentos.
                <a href="agendar.php" class="alert-link">Clique aqui para agendar</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($agendamentos as $agendamento): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card agendamento-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title"><?php echo htmlspecialchars($agendamento['servico_nome']); ?></h5>
                                    <span class="badge bg-<?php 
                                        switch($agendamento['status']) {
                                            case 'pendente': echo 'warning'; break;
                                            case 'confirmado': echo 'success'; break;
                                            case 'cancelado': echo 'danger'; break;
                                            case 'concluido': echo 'info'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($agendamento['status']); ?>
                                    </span>
                                </div>
                                
                                <p class="card-text">
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?>
                                    <br>
                                    <i class="fas fa-clock"></i> 
                                    <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                                    <br>
                                    <i class="fas fa-user"></i> 
                                    <?php echo $agendamento['barbeiro_nome'] ? htmlspecialchars($agendamento['barbeiro_nome']) : 'Qualquer Barbeiro Disponível'; ?>
                                    <br>
                                    <i class="fas fa-dollar-sign"></i> 
                                    R$ <?php echo number_format($agendamento['preco'], 2, ',', '.'); ?>
                                </p>

                                <?php if ($agendamento['status'] === 'pendente' || $agendamento['status'] === 'confirmado'): ?>
                                    <div class="d-grid gap-2">
                                        <a href="cancelar_agendamento.php?id=<?php echo $agendamento['id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Tem certeza que deseja cancelar este agendamento?')">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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