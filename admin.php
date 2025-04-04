<?php
session_start();
require_once 'conexao.php';

// Verificar se o usuário está logado como administrador
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    $_SESSION['mensagem'] = "Acesso não autorizado.";
    header("Location: index.php");
    exit();
}

// Buscar estatísticas
try {
    // Total de agendamentos
    $stmt = $pdo->query("SELECT COUNT(*) FROM agendamentos");
    $total_agendamentos = $stmt->fetchColumn();

    // Agendamentos do dia
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE data_agendamento = ?");
    $stmt->execute([date('Y-m-d')]);
    $agendamentos_hoje = $stmt->fetchColumn();

    // Total de clientes
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'cliente'");
    $total_clientes = $stmt->fetchColumn();

    // Total de barbeiros
    $stmt = $pdo->query("SELECT COUNT(*) FROM barbeiros");
    $total_barbeiros = $stmt->fetchColumn();

    // Últimos agendamentos
    $stmt = $pdo->query("
        SELECT 
            a.*,
            u.nome as nome_cliente,
            b.nome as nome_barbeiro,
            s.nome as nome_servico
        FROM agendamentos a
        JOIN usuarios u ON a.usuario_id = u.id
        JOIN barbeiros b ON a.barbeiro_id = b.id
        JOIN servicos s ON a.servico_id = s.id
        ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC
        LIMIT 5
    ");
    $ultimos_agendamentos = $stmt->fetchAll();

    // Lista de barbeiros
    $stmt = $pdo->query("SELECT * FROM barbeiros ORDER BY nome");
    $barbeiros = $stmt->fetchAll();

} catch (PDOException $e) {
    $erro = "Erro ao carregar dados.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Barbearia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .card-stats {
            transition: all 0.3s ease;
        }
        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status-ativo { color: #198754; }
        .status-inativo { color: #dc3545; }
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
                        <a class="nav-link active" href="admin.php">Painel Admin</a>
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
        <h2 class="text-center mb-4">Painel Administrativo</h2>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total de Agendamentos</h5>
                        <h2 class="card-text"><?php echo $total_agendamentos; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Agendamentos Hoje</h5>
                        <h2 class="card-text"><?php echo $agendamentos_hoje; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total de Clientes</h5>
                        <h2 class="card-text"><?php echo $total_clientes; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total de Barbeiros</h5>
                        <h2 class="card-text"><?php echo $total_barbeiros; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Últimos Agendamentos -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Últimos Agendamentos</h5>
                        <?php if (empty($ultimos_agendamentos)): ?>
                            <p class="text-muted">Nenhum agendamento encontrado.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Data/Hora</th>
                                            <th>Cliente</th>
                                            <th>Barbeiro</th>
                                            <th>Serviço</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ultimos_agendamentos as $agendamento): ?>
                                            <tr>
                                                <td>
                                                    <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?><br>
                                                    <?php echo $agendamento['hora_agendamento']; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($agendamento['nome_cliente']); ?></td>
                                                <td><?php echo htmlspecialchars($agendamento['nome_barbeiro']); ?></td>
                                                <td><?php echo htmlspecialchars($agendamento['nome_servico']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $agendamento['status'] === 'pendente' ? 'warning' : 
                                                            ($agendamento['status'] === 'confirmado' ? 'success' : 
                                                            ($agendamento['status'] === 'cancelado' ? 'danger' : 'primary')); 
                                                    ?>">
                                                        <?php echo ucfirst($agendamento['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Lista de Barbeiros -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title">Barbeiros</h5>
                            <a href="cadastrar_barbeiro.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-user-plus"></i> Novo Barbeiro
                            </a>
                        </div>
                        <?php if (empty($barbeiros)): ?>
                            <p class="text-muted">Nenhum barbeiro cadastrado.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($barbeiros as $barbeiro): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($barbeiro['nome']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($barbeiro['especialidade']); ?></small>
                                            </div>
                                            <span class="status-<?php echo $barbeiro['status'] == 1 ? 'ativo' : 'inativo'; ?>">
                                                <?php echo $barbeiro['status'] == 1 ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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