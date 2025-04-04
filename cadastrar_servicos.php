<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'barbearia';
$username = 'root';
$password = '';

try {
    $mysqli = new mysqli($host, $username, $password, $dbname);
    if ($mysqli->connect_error) {
        throw new Exception("Erro de conexão: " . $mysqli->connect_error);
    }

    // Serviços padrão
    $servicos = [
        ['Corte de Cabelo', 30, 35.00],
        ['Barba', 20, 25.00],
        ['Corte + Barba', 45, 50.00],
        ['Sobrancelha', 15, 15.00],
        ['Hidratação', 30, 40.00]
    ];

    // Verificar se os serviços já existem
    $result = $mysqli->query("SELECT COUNT(*) as total FROM servicos");
    $row = $result->fetch_assoc();
    
    if ($row['total'] == 0) {
        // Inserir serviços
        $stmt = $mysqli->prepare("INSERT INTO servicos (nome, duracao, preco, status) VALUES (?, ?, ?, 1)");
        
        foreach ($servicos as $servico) {
            $stmt->bind_param("sid", $servico[0], $servico[1], $servico[2]);
            $stmt->execute();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Serviços cadastrados com sucesso!'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Serviços já cadastrados!'
        ]);
    }

    $mysqli->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 