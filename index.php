<?php
session_start();
require_once 'conexao.php';

// Buscar serviços disponíveis
try {
    $stmt = $pdo->prepare("SELECT id, nome, descricao, preco, duracao FROM servicos WHERE status = 1 ORDER BY nome");
    $stmt->execute();
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar serviços. Tente novamente.";
}

// Buscar barbeiros disponíveis
try {
    $stmt = $pdo->prepare("SELECT id, nome, especialidade, descricao FROM barbeiros WHERE status = 1 ORDER BY nome");
    $stmt->execute();
    $barbeiros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar barbeiros. Tente novamente.";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbearia - Início</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .service-card, .barbeiro-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .service-card:hover, .barbeiro-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .social-links a {
            color: white;
            text-decoration: none;
            margin-right: 10px;
        }
        .social-links a:hover {
            color: #0d6efd;
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
                        <a class="nav-link active" href="index.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="servicos.php">Serviços</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="agendar.php">Agendar</a>
                    </li>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="meus_agendamentos.php">Meus Horários</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Sair</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cadastro.php">Cadastro</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <div class="hero-section text-white text-center py-5">
        <div class="container">
            <h1 class="display-4 mb-4">Bem-vindo à Barbearia</h1>
            <p class="lead mb-4">Agende seu horário online e tenha o melhor atendimento</p>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a href="agendar.php" class="btn btn-primary btn-lg">Agende Agora</a>
            <?php else: ?>
                <div class="d-flex justify-content-center gap-3">
                    <a href="login.php" class="btn btn-primary btn-lg">Faça Login</a>
                    <a href="cadastro.php" class="btn btn-outline-light btn-lg">Cadastre-se</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Serviços em Destaque -->
    <div class="container py-5">
        <h2 class="text-center mb-5">Serviços em Destaque</h2>
        <div class="row">
            <?php foreach ($servicos as $servico): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow fade-in">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($servico['nome']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($servico['descricao']); ?></p>
                            <p class="card-text">
                                <strong>R$ <?php echo number_format($servico['preco'], 2, ',', '.'); ?></strong>
                            </p>
                            <?php if (isset($_SESSION['usuario_id'])): ?>
                                <a href="agendar.php?servico=<?php echo $servico['id']; ?>" class="btn btn-primary w-100">
                                    Agendar
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled title="Faça login para agendar">
                                    Agendar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="servicos.php" class="btn btn-outline-primary">Ver Todos os Serviços</a>
        </div>
    </div>
    
    <!-- Por que nos escolher -->
    <div class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">Por que nos escolher?</h2>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="text-center fade-in">
                        <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                        <h5>Agendamento Online</h5>
                        <p>Agende seu horário de forma rápida e prática</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="text-center fade-in">
                        <i class="fas fa-cut fa-3x text-primary mb-3"></i>
                        <h5>Profissionais Qualificados</h5>
                        <p>Equipe experiente e atualizada</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="text-center fade-in">
                        <i class="fas fa-star fa-3x text-primary mb-3"></i>
                        <h5>Qualidade Garantida</h5>
                        <p>Melhores produtos e equipamentos</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="text-center fade-in">
                        <i class="fas fa-heart fa-3x text-primary mb-3"></i>
                        <h5>Atendimento Personalizado</h5>
                        <p>Cuidamos de você com dedicação</p>
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
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 