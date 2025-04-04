<?php
session_start();
require_once 'conexao.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados da requisição
$data = $_POST['data'] ?? '';
$servico_id = $_POST['servico'] ?? '';
$barbeiro_id = $_POST['barbeiro'] ?? '';

// Validar dados
if (empty($data) || empty($servico_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    // Buscar duração do serviço
    $stmt = $pdo->prepare("SELECT duracao FROM servicos WHERE id = ?");
    $stmt->execute([$servico_id]);
    $servico = $stmt->fetch();

    if (!$servico) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Serviço não encontrado']);
        exit;
    }

    // Gerar horários disponíveis
    $horarios = [];
    $hora_inicio = strtotime('09:00');
    $hora_fim = strtotime('20:00');
    $duracao = $servico['duracao'] * 60; // Converter minutos para segundos

    // Verificar se é domingo
    if (date('w', strtotime($data)) == 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Não trabalhamos aos domingos']);
        exit;
    }

    // Verificar se a data é válida (não pode ser no passado e máximo 14 dias no futuro)
    $hoje = strtotime(date('Y-m-d'));
    $data_agendamento = strtotime($data);
    $max_data = strtotime('+14 days', $hoje);

    if ($data_agendamento < $hoje || $data_agendamento > $max_data) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Data inválida']);
        exit;
    }

    // Gerar todos os horários possíveis
    for ($hora = $hora_inicio; $hora < $hora_fim; $hora += $duracao) {
        $horario = date('H:i', $hora);
        $horarios[] = $horario;
    }

    // Verificar horários já agendados
    $stmt = $pdo->prepare("
        SELECT hora_agendamento 
        FROM agendamentos 
        WHERE data_agendamento = ? 
        AND status IN ('pendente', 'confirmado')
        " . ($barbeiro_id ? "AND (barbeiro_id = ? OR barbeiro_id IS NULL)" : "")
    );
    
    $params = [$data];
    if ($barbeiro_id) {
        $params[] = $barbeiro_id;
    }
    
    $stmt->execute($params);
    $horarios_ocupados = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Remover horários ocupados
    $horarios_disponiveis = array_diff($horarios, $horarios_ocupados);

    // Formatar horários
    $horarios_formatados = array_map(function($hora) {
        return date('H:i', strtotime($hora));
    }, $horarios_disponiveis);

    // Ordenar horários
    sort($horarios_formatados);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'horarios' => $horarios_formatados
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao verificar horários disponíveis'
    ]);
}
?> 