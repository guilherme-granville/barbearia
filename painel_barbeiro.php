<?php
session_start();
require_once 'conexao.php';

// Verificar se o usuário está logado como barbeiro
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'barbeiro') {
    $_SESSION['mensagem'] = "Acesso não autorizado.";
    header("Location: index.php");
    exit();
}

// Buscar informações do barbeiro
try {
    $stmt = $pdo->prepare("SELECT * FROM barbeiros WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $barbeiro = $stmt->fetch();
} catch (PDOException $e) {
    $erro = "Erro ao carregar informações do barbeiro.";
}

// Buscar agendamentos do dia
$data_atual = date('Y-m-d');
try {
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            u.nome as nome_cliente,
            u.telefone as telefone_cliente,
            s.nome as nome_servico,
            s.duracao as duracao_servico,
            s.preco as preco_servico
        FROM agendamentos a
        JOIN usuarios u ON a.usuario_id = u.id
        JOIN servicos s ON a.servico_id = s.id
        WHERE a.data_agendamento = ?
        AND a.barbeiro_id = ?
        ORDER BY a.hora_agendamento ASC
    ");
    $stmt->execute([$data_atual, $_SESSION['usuario_id']]);
    $agendamentos_hoje = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = "Erro ao carregar agendamentos.";
}

// Buscar próximos agendamentos
try {
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            u.nome as nome_cliente,
            u.telefone as telefone_cliente,
            s.nome as nome_servico,
            s.duracao as duracao_servico,
            s.preco as preco_servico
        FROM agendamentos a
        JOIN usuarios u ON a.usuario_id = u.id
        JOIN servicos s ON a.servico_id = s.id
        WHERE a.data_agendamento >= ?
        AND a.barbeiro_id = ?
        AND a.status != 'cancelado'
        ORDER BY a.data_agendamento ASC, a.hora_agendamento ASC
        LIMIT 10
    ");
    $stmt->execute([$data_atual, $_SESSION['usuario_id']]);
    $proximos_agendamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = "Erro ao carregar próximos agendamentos.";
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && isset($_POST['agendamento_id'])) {
        $agendamento_id = filter_input(INPUT_POST, 'agendamento_id', FILTER_VALIDATE_INT);
        
        switch ($_POST['acao']) {
            case 'confirmar':
                try {
                    $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'confirmado' WHERE id = ? AND barbeiro_id = ?");
                    $stmt->execute([$agendamento_id, $_SESSION['usuario_id']]);
                    $_SESSION['sucesso'] = "Agendamento confirmado com sucesso!";
                } catch (PDOException $e) {
                    $erro = "Erro ao confirmar agendamento.";
                }
                break;
                
            case 'cancelar':
                try {
                    $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = ? AND barbeiro_id = ?");
                    $stmt->execute([$agendamento_id, $_SESSION['usuario_id']]);
                    $_SESSION['sucesso'] = "Agendamento cancelado com sucesso!";
                } catch (PDOException $e) {
                    $erro = "Erro ao cancelar agendamento.";
                }
                break;
                
            case 'concluir':
                try {
                    $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'concluido' WHERE id = ? AND barbeiro_id = ?");
                    $stmt->execute([$agendamento_id, $_SESSION['usuario_id']]);
                    $_SESSION['sucesso'] = "Agendamento concluído com sucesso!";
                } catch (PDOException $e) {
                    $erro = "Erro ao concluir agendamento.";
                }
                break;
        }
        
        // Redirecionar para atualizar a página
        header("Location: painel_barbeiro.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Barbeiro - Barbearia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .agendamento-card {
            transition: all 0.3s ease;
        }
        .agendamento-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status-pendente { color: #ffc107; }
        .status-confirmado { color: #198754; }
        .status-cancelado { color: #dc3545; }
        .status-concluido { color: #0d6efd; }
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
                        <a class="nav-link active" href="painel_barbeiro.php">Painel do Barbeiro</a>
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
        <div class="row">
            <!-- Informações do Barbeiro -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Bem-vindo, <?php echo htmlspecialchars($barbeiro['nome']); ?></h5>
                        <p class="card-text">
                            <strong>Especialidade:</strong> <?php echo htmlspecialchars($barbeiro['especialidade']); ?><br>
                            <strong>Status:</strong> <?php echo $barbeiro['status'] == 1 ? 'Ativo' : 'Inativo'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Agendamentos do Dia -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Agendamentos de Hoje (<?php echo date('d/m/Y'); ?>)</h5>
                        <?php if (empty($agendamentos_hoje)): ?>
                            <p class="text-muted">Nenhum agendamento para hoje.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($agendamentos_hoje as $agendamento): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card agendamento-card">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="fas fa-clock"></i> <?php echo $agendamento['hora_agendamento']; ?>
                                                </h6>
                                                <p class="card-text">
                                                    <strong>Cliente:</strong> <?php echo htmlspecialchars($agendamento['nome_cliente']); ?><br>
                                                    <strong>Telefone:</strong> <?php echo htmlspecialchars($agendamento['telefone_cliente']); ?><br>
                                                    <strong>Serviço:</strong> <?php echo htmlspecialchars($agendamento['nome_servico']); ?><br>
                                                    <strong>Duração:</strong> <?php echo $agendamento['duracao_servico']; ?> min<br>
                                                    <strong>Valor:</strong> R$ <?php echo number_format($agendamento['preco_servico'], 2, ',', '.'); ?>
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="status-<?php echo $agendamento['status']; ?>">
                                                        <?php echo ucfirst($agendamento['status']); ?>
                                                    </span>
                                                    <?php if ($agendamento['status'] === 'pendente'): ?>
                                                        <div>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="acao" value="confirmar">
                                                                <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                                <button type="submit" class="btn btn-success btn-sm">
                                                                    <i class="fas fa-check"></i> Confirmar
                                                                </button>
                                                            </form>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="acao" value="cancelar">
                                                                <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm">
                                                                    <i class="fas fa-times"></i> Cancelar
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php elseif ($agendamento['status'] === 'confirmado'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="acao" value="concluir">
                                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                            <button type="submit" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-check-double"></i> Concluir
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Próximos Agendamentos -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Próximos Agendamentos</h5>
                        <?php if (empty($proximos_agendamentos)): ?>
                            <p class="text-muted">Nenhum agendamento futuro.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Horário</th>
                                            <th>Cliente</th>
                                            <th>Telefone</th>
                                            <th>Serviço</th>
                                            <th>Duração</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($proximos_agendamentos as $agendamento): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></td>
                                                <td><?php echo $agendamento['hora_agendamento']; ?></td>
                                                <td><?php echo htmlspecialchars($agendamento['nome_cliente']); ?></td>
                                                <td><?php echo htmlspecialchars($agendamento['telefone_cliente']); ?></td>
                                                <td><?php echo htmlspecialchars($agendamento['nome_servico']); ?></td>
                                                <td><?php echo $agendamento['duracao_servico']; ?> min</td>
                                                <td>R$ <?php echo number_format($agendamento['preco_servico'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <span class="status-<?php echo $agendamento['status']; ?>">
                                                        <?php echo ucfirst($agendamento['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($agendamento['status'] === 'pendente'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="acao" value="confirmar">
                                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="acao" value="cancelar">
                                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    <?php elseif ($agendamento['status'] === 'confirmado'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="acao" value="concluir">
                                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                            <button type="submit" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-check-double"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
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