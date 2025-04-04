<?php
session_start();
require_once 'conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensagem'] = "Você precisa estar logado para agendar um horário.";
    $_SESSION['redirect_after_login'] = "agendar.php" . (isset($_GET['servico']) ? "?servico=" . $_GET['servico'] : "");
    header("Location: login.php");
    exit();
}

// Obter o serviço selecionado, se houver
$servico_selecionado = isset($_GET['servico']) ? (int)$_GET['servico'] : null;

// Buscar serviços disponíveis
$servicos = []; // Inicializa como array vazio
try {
    $stmt = $pdo->prepare("SELECT id, nome, preco, duracao, descricao FROM servicos WHERE status = 1 ORDER BY nome");
    $stmt->execute();
    $servicos = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = "Erro ao carregar serviços. Tente novamente.";
}

// Buscar barbeiros disponíveis
$barbeiros = []; // Inicializa como array vazio
try {
    $stmt = $pdo->prepare("SELECT id, nome, especialidade, descricao FROM barbeiros WHERE status = 1 ORDER BY nome");
    $stmt->execute();
    $barbeiros = $stmt->fetchAll();
    
    // Debug
    if (empty($barbeiros)) {
        error_log("Nenhum barbeiro encontrado na tabela barbeiros");
    } else {
        error_log("Barbeiros encontrados: " . count($barbeiros));
        foreach ($barbeiros as $barbeiro) {
            error_log("Barbeiro: " . $barbeiro['nome']);
        }
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar barbeiros: " . $e->getMessage());
    $erro = "Erro ao carregar barbeiros. Tente novamente.";
}

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servico_id = filter_input(INPUT_POST, 'servico', FILTER_VALIDATE_INT);
    $barbeiro_id = filter_input(INPUT_POST, 'barbeiro', FILTER_VALIDATE_INT);
    $data = filter_input(INPUT_POST, 'data', FILTER_SANITIZE_STRING);
    $horario = filter_input(INPUT_POST, 'horario', FILTER_SANITIZE_STRING);

    // Verificar se todos os campos foram preenchidos
    if (!$servico_id || !$barbeiro_id || !$data || !$horario) {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        try {
            // Verificar se o serviço existe e está ativo
            $stmt = $pdo->prepare("SELECT id, duracao FROM servicos WHERE id = ? AND status = 1");
            $stmt->execute([$servico_id]);
            $servico = $stmt->fetch();

            if (!$servico) {
                $erro = "Serviço inválido ou indisponível.";
            } else {
                // Verificar se a data é válida (não é domingo e está dentro do período permitido)
                $data_obj = new DateTime($data);
                $hoje = new DateTime();
                $max_data = (new DateTime())->modify('+14 days');

                if ($data_obj < $hoje) {
                    $erro = "A data selecionada é anterior a hoje.";
                } elseif ($data_obj > $max_data) {
                    $erro = "A data selecionada está muito distante. O agendamento deve ser feito com até 14 dias de antecedência.";
                } elseif ($data_obj->format('w') == 0) { // 0 = Domingo
                    $erro = "Não é possível agendar aos domingos.";
                } else {
                    // Verificar se o barbeiro está disponível no horário
                    $stmt = $pdo->prepare("
                        SELECT b.id, b.nome
                        FROM barbeiros b
                        WHERE b.id = ?
                        AND b.status = 1
                        AND NOT EXISTS (
                            SELECT 1 
                            FROM agendamentos a 
                            WHERE a.barbeiro_id = b.id 
                            AND a.data_agendamento = ? 
                            AND a.hora_agendamento = ? 
                            AND a.status != 'cancelado'
                        )
                    ");
                    $stmt->execute([$barbeiro_id, $data, $horario]);
                    $barbeiro_disponivel = $stmt->fetch();

                    if (!$barbeiro_disponivel) {
                        $erro = "O barbeiro selecionado não está disponível para este horário.";
                    } else {
                        // Inserir o agendamento
                        $stmt = $pdo->prepare("
                            INSERT INTO agendamentos 
                            (usuario_id, servico_id, barbeiro_id, data_agendamento, hora_agendamento, status) 
                            VALUES (?, ?, ?, ?, ?, 'pendente')
                        ");
                        $stmt->execute([
                            $_SESSION['usuario_id'],
                            $servico_id,
                            $barbeiro_id,
                            $data,
                            $horario
                        ]);

                        $_SESSION['sucesso'] = "Agendamento realizado com sucesso!";
                        header("Location: meus_agendamentos.php");
                        exit;
                    }
                }
            }
        } catch (PDOException $e) {
            $erro = "Erro ao realizar agendamento. Tente novamente.";
        }
    }
}

// Gerar horários disponíveis
$horarios = [];
$inicio = new DateTime('09:00');
$fim = new DateTime('20:00');
$intervalo = new DateInterval('PT30M');

while ($inicio < $fim) {
    $horarios[] = $inicio->format('H:i');
    $inicio->add($intervalo);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar - Barbearia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .service-card, .barbeiro-card, .horario-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .service-card:hover, .barbeiro-card:hover, .horario-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .service-card.selected, .barbeiro-card.selected, .horario-card.selected {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.05);
        }

        .step {
            display: none;
        }

        .step.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .btn-next, .btn-prev {
            min-width: 120px;
        }

        .alert {
            animation: fadeIn 0.3s ease;
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
                        <a class="nav-link active" href="agendar.php">Agendar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="meus_agendamentos.php">Meus Horários</a>
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
        <h2 class="text-center mb-4">Agendar Horário</h2>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <!-- Progresso -->
                        <div class="progress mb-4">
                            <div class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">1/4</div>
                        </div>

                        <!-- Formulário de Agendamento -->
                        <form id="agendamentoForm" method="POST" action="">
                            <!-- Passo 1: Serviço -->
                            <div class="step active" id="step1">
                                <h4 class="mb-4">Escolha o Serviço</h4>
                                <div class="row">
                                    <?php foreach ($servicos as $servico): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card service-card h-100 <?php echo (isset($_POST['servico']) && $_POST['servico'] == $servico['id']) ? 'selected' : ''; ?>">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($servico['nome']); ?></h5>
                                                    <p class="card-text"><?php echo htmlspecialchars($servico['descricao']); ?></p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-primary fw-bold">R$ <?php echo number_format($servico['preco'], 2, ',', '.'); ?></span>
                                                        <span class="text-muted"><?php echo $servico['duracao']; ?> min</span>
                                                    </div>
                                                    <input type="radio" name="servico" value="<?php echo $servico['id']; ?>" class="d-none" <?php echo (isset($_POST['servico']) && $_POST['servico'] == $servico['id']) ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Passo 2: Barbeiro -->
                            <div class="step" id="step2">
                                <h4 class="mb-4">Escolha o Barbeiro</h4>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="card barbeiro-card h-100 <?php echo (isset($_POST['barbeiro']) && $_POST['barbeiro'] == '0') ? 'selected' : ''; ?>">
                                            <div class="card-body">
                                                <h5 class="card-title">Qualquer Barbeiro Disponível</h5>
                                                <p class="card-text">
                                                    <strong>Especialidade:</strong> Todos os serviços<br>
                                                    <small class="text-muted">Será atribuído o primeiro barbeiro disponível no horário escolhido.</small>
                                                </p>
                                                <input type="radio" name="barbeiro" value="0" class="d-none" <?php echo (isset($_POST['barbeiro']) && $_POST['barbeiro'] == '0') ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>
                                    <?php foreach ($barbeiros as $barbeiro): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card barbeiro-card h-100 <?php echo (isset($_POST['barbeiro']) && $_POST['barbeiro'] == $barbeiro['id']) ? 'selected' : ''; ?>">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($barbeiro['nome']); ?></h5>
                                                    <p class="card-text">
                                                        <strong>Especialidade:</strong> <?php echo htmlspecialchars($barbeiro['especialidade']); ?><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($barbeiro['descricao']); ?></small>
                                                    </p>
                                                    <input type="radio" name="barbeiro" value="<?php echo $barbeiro['id']; ?>" class="d-none" <?php echo (isset($_POST['barbeiro']) && $_POST['barbeiro'] == $barbeiro['id']) ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Passo 3: Data -->
                            <div class="step" id="step3">
                                <h4 class="mb-4">Escolha a Data</h4>
                                <div class="mb-3">
                                    <label for="data" class="form-label">Data do Agendamento</label>
                                    <input type="date" class="form-control" id="data" name="data" min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+14 days')); ?>" value="<?php echo isset($_POST['data']) ? htmlspecialchars($_POST['data']) : ''; ?>">
                                </div>
                            </div>

                            <!-- Passo 4: Horário -->
                            <div class="step" id="step4">
                                <h4 class="mb-4">Escolha o Horário</h4>
                                <div id="horarios" class="row">
                                    <!-- Horários serão carregados via AJAX -->
                                </div>
                            </div>

                            <!-- Botões de Navegação -->
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary" id="btnVoltar" style="display: none;">Voltar</button>
                                <button type="button" class="btn btn-primary" id="btnProximo">Próximo</button>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('agendamentoForm');
            const steps = document.querySelectorAll('.step');
            const btnProximo = document.getElementById('btnProximo');
            const btnVoltar = document.getElementById('btnVoltar');
            let currentStep = 0;

            // Restaurar o passo atual se houver erro
            <?php if (isset($_POST['servico']) && !isset($_POST['barbeiro'])): ?>
                currentStep = 1;
            <?php elseif (isset($_POST['barbeiro']) && !isset($_POST['data'])): ?>
                currentStep = 2;
            <?php elseif (isset($_POST['data']) && !isset($_POST['horario'])): ?>
                currentStep = 3;
            <?php endif; ?>

            // Mostrar o passo atual
            steps.forEach((step, index) => {
                if (index === currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });

            // Atualizar visibilidade dos botões
            function updateButtons() {
                btnVoltar.style.display = currentStep > 0 ? 'block' : 'none';
                btnProximo.textContent = currentStep === steps.length - 1 ? 'Confirmar' : 'Próximo';
            }

            // Carregar horários se já tiver data selecionada
            <?php if (isset($_POST['data']) && isset($_POST['servico']) && isset($_POST['barbeiro'])): ?>
                loadHorarios();
            <?php endif; ?>

            // Função para carregar horários disponíveis
            function loadHorarios() {
                const data = document.getElementById('data').value;
                const servico = document.querySelector('input[name="servico"]:checked');
                const barbeiro = document.querySelector('input[name="barbeiro"]:checked');

                if (!data || !servico || !barbeiro) {
                    return;
                }

                const horariosDiv = document.getElementById('horarios');
                horariosDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>';

                fetch('verificar_horarios.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `data=${data}&servico=${servico.value}&barbeiro=${barbeiro.value}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        horariosDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }

                    if (data.horarios.length === 0) {
                        horariosDiv.innerHTML = '<div class="alert alert-warning">Não há horários disponíveis para esta data.</div>';
                        return;
                    }

                    let html = '';
                    data.horarios.forEach(horario => {
                        html += `
                            <div class="col-md-3 mb-3">
                                <div class="card horario-card h-100">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">${horario}</h5>
                                        <input type="radio" name="horario" value="${horario}" class="d-none">
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    horariosDiv.innerHTML = html;

                    // Adicionar evento de clique nos cards de horário
                    document.querySelectorAll('.horario-card').forEach(card => {
                        card.addEventListener('click', function() {
                            document.querySelectorAll('.horario-card').forEach(c => c.classList.remove('selected'));
                            this.classList.add('selected');
                            this.querySelector('input[type="radio"]').checked = true;
                        });
                    });
                })
                .catch(error => {
                    console.error('Erro:', error);
                    horariosDiv.innerHTML = '<div class="alert alert-danger">Erro ao carregar horários. Tente novamente.</div>';
                });
            }

            // Evento de mudança na data
            document.getElementById('data').addEventListener('change', loadHorarios);

            // Evento de clique nos cards de serviço
            document.querySelectorAll('.service-card').forEach(card => {
                card.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });

            // Evento de clique nos cards de barbeiro
            document.querySelectorAll('.barbeiro-card').forEach(card => {
                card.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    document.querySelectorAll('.barbeiro-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });

            // Evento de clique no botão "Próximo"
            btnProximo.addEventListener('click', function() {
                const currentStepDiv = steps[currentStep];
                let isValid = true;
                let errorMessage = '';

                // Validação do passo atual
                if (currentStep === 0) {
                    const servicoSelecionado = document.querySelector('input[name="servico"]:checked');
                    if (!servicoSelecionado) {
                        isValid = false;
                        errorMessage = 'Por favor, selecione um serviço.';
                    }
                } else if (currentStep === 1) {
                    const barbeiroSelecionado = document.querySelector('input[name="barbeiro"]:checked');
                    if (!barbeiroSelecionado) {
                        isValid = false;
                        errorMessage = 'Por favor, selecione um barbeiro.';
                    }
                } else if (currentStep === 2) {
                    const data = document.getElementById('data').value;
                    if (!data) {
                        isValid = false;
                        errorMessage = 'Por favor, selecione uma data.';
                    }
                } else if (currentStep === 3) {
                    const horarioSelecionado = document.querySelector('input[name="horario"]:checked');
                    if (!horarioSelecionado) {
                        isValid = false;
                        errorMessage = 'Por favor, selecione um horário.';
                    }
                }

                if (!isValid) {
                    alert(errorMessage);
                    return;
                }

                if (currentStep === steps.length - 1) {
                    // Último passo - submeter o formulário
                    form.submit();
                } else {
                    currentStepDiv.classList.remove('active');
                    currentStep++;
                    steps[currentStep].classList.add('active');
                    updateButtons();

                    // Se for o passo da data, carregar horários
                    if (currentStep === 3) {
                        loadHorarios();
                    }
                }
            });

            // Evento de clique no botão "Voltar"
            btnVoltar.addEventListener('click', function() {
                steps[currentStep].classList.remove('active');
                currentStep--;
                steps[currentStep].classList.add('active');
                updateButtons();
            });

            // Inicializar
            updateButtons();
        });
    </script>
</body>
</html> 