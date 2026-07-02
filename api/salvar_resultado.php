<?php
/**
 * API para persistência do resultado PI no banco SQLite.
 * Recebe JSON via POST e grava o resultado associado ao usuário autenticado.
 */

session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$input = file_get_contents('php://input');
$dados = json_decode($input, true);

if (!is_array($dados)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
    exit;
}

$usuarioId = (int) ($dados['usuario_id'] ?? $_SESSION['usuario_id'] ?? 0);
$resultadoPi = $dados['resultado_pi'] ?? null;

if ($usuarioId <= 0 || !is_array($resultadoPi)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário ou resultado PI inválidos.']);
    exit;
}

try {
    $db = new PDO('sqlite:' . dirname(__DIR__) . '/database/mapa.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec(
        "
        CREATE TABLE IF NOT EXISTS resultados_pi (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            dominancia REAL NOT NULL,
            influencia REAL NOT NULL,
            paciencia REAL NOT NULL,
            formalidade REAL NOT NULL,
            data_avaliacao TEXT DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
        "
    );

    $stmt = $db->prepare(
        'INSERT INTO resultados_pi (usuario_id, dominancia, influencia, paciencia, formalidade) VALUES (:usuario_id, :dominancia, :influencia, :paciencia, :formalidade)'
    );

    $stmt->execute([
        'usuario_id' => $usuarioId,
        'dominancia' => (float) ($resultadoPi['dominancia'] ?? 0),
        'influencia' => (float) ($resultadoPi['influencia'] ?? 0),
        'paciencia' => (float) ($resultadoPi['paciencia'] ?? 0),
        'formalidade' => (float) ($resultadoPi['formalidade'] ?? 0),
    ]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Resultado PI salvo com sucesso.']);
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar resultado PI: ' . $e->getMessage()]);
}
