<?php
/**
 * MAPA - Funções do quiz vocacional (login, salvar, carregar, finalizar)
 */

// Ajuste estes valores para o e-mail do RH que deve receber a notificação.
define('RH_NOTIF_EMAILS', ['rh@parex.com.br']);
define('EMAIL_REMETENTE', 'no-reply@parex.com.br');

function processarLogin(PDO $db, array $dados): void
{
    $email = trim($dados['email'] ?? '');
    $senha = $dados['password'] ?? '';
    $perfil = $dados['perfil'] ?? '';

    $stmt = $db->prepare('SELECT id, nome, email, senha, perfil FROM usuarios WHERE email = :email AND perfil = :perfil');
    $stmt->execute(['email' => $email, 'perfil' => $perfil]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail, senha ou perfil incorretos.']);
        return;
    }

    echo json_encode([
        'sucesso' => true,
        'usuario' => [
            'id' => (int) $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'perfil' => $usuario['perfil'],
        ],
    ]);
}

function carregarQuiz(PDO $db, array $dados): void
{
    $usuarioId = (int) ($dados['usuario_id'] ?? 0);

    if ($usuarioId <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário inválido.']);
        return;
    }

    $stmt = $db->prepare('SELECT respostas, indice_atual, concluido FROM progresso_quiz WHERE usuario_id = :usuario_id');
    $stmt->execute(['usuario_id' => $usuarioId]);
    $progresso = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmtNotas = $db->prepare('SELECT lideranca, tecnico, comportamento, perfil_resultado, indice_preditivo, data_registro FROM notas_quiz WHERE usuario_id = :usuario_id ORDER BY id DESC LIMIT 1');
    $stmtNotas->execute(['usuario_id' => $usuarioId]);
    $notas = $stmtNotas->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'sucesso' => true,
        'progresso' => $progresso ? [
            'respostas' => json_decode($progresso['respostas'], true) ?? [],
            'indice_atual' => (int) $progresso['indice_atual'],
            'concluido' => (bool) $progresso['concluido'],
        ] : null,
        'notas' => $notas ?: null,
    ]);
}

function salvarQuiz(PDO $db, array $dados): void
{
    $usuarioId = (int) ($dados['usuario_id'] ?? 0);
    $respostas = $dados['respostas'] ?? [];
    $indiceAtual = (int) ($dados['indice_atual'] ?? 0);

    if ($usuarioId <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário inválido.']);
        return;
    }

    if (!is_array($respostas)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Respostas inválidas.']);
        return;
    }

    $jsonRespostas = json_encode($respostas, JSON_UNESCAPED_UNICODE);

    $stmt = $db->prepare('
        INSERT INTO progresso_quiz (usuario_id, respostas, indice_atual, concluido, atualizado_em)
        VALUES (:usuario_id, :respostas, :indice_atual, 0, datetime("now", "localtime"))
        ON CONFLICT(usuario_id) DO UPDATE SET
            respostas = excluded.respostas,
            indice_atual = excluded.indice_atual,
            concluido = 0,
            atualizado_em = datetime("now", "localtime")
    ');

    $stmt->execute([
        'usuario_id' => $usuarioId,
        'respostas' => $jsonRespostas,
        'indice_atual' => $indiceAtual,
    ]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Progresso salvo. Você pode continuar depois.']);
}

function atualizarEsquemaSQLite(PDO $db): void
{
    $colunas = [];
    $stmt = $db->query('PRAGMA table_info(notas_quiz)');
    foreach ($stmt as $row) {
        $colunas[] = $row['name'];
    }

    if (!in_array('perfil_resultado', $colunas, true)) {
        $db->exec('ALTER TABLE notas_quiz ADD COLUMN perfil_resultado TEXT');
    }

    if (!in_array('indice_preditivo', $colunas, true)) {
        $db->exec('ALTER TABLE notas_quiz ADD COLUMN indice_preditivo REAL');
    }
}

function buscarUsuario(PDO $db, int $usuarioId): ?array
{
    $stmt = $db->prepare('SELECT id, nome, email FROM usuarios WHERE id = :id');
    $stmt->execute(['id' => $usuarioId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    return $usuario ?: null;
}

function gerarPerfilPredictivo(array $notas): array
{
    $valores = [
        'tecnico' => $notas['tecnico'] * 0.9,
        'comportamento' => $notas['comportamento'] * 1.1,
        'lideranca' => $notas['lideranca'] * 1.5,
    ];

    arsort($valores);
    $categoriaDominante = key($valores);
    $indicePreditivo = round(array_sum($valores) / array_sum([0.9, 1.1, 1.5]), 1);

    $nomesDePerfil = [
        'tecnico' => 'Perfil Técnico',
        'comportamento' => 'Perfil Comportamental',
        'lideranca' => 'Perfil de Liderança',
    ];

    return [
        'perfil' => $nomesDePerfil[$categoriaDominante] ?? 'Perfil Genérico',
        'indice' => $indicePreditivo,
    ];
}

function enviarNotificacaoRH(array $emails, array $usuario, string $perfil, float $indice, array $notas): void
{
    if (empty($emails)) {
        return;
    }

    $assunto = 'Novo perfil de quiz disponível: ' . $usuario['nome'];
    $mensagem = "Olá RH,\n\n" .
        "O colaborador {$usuario['nome']} ({$usuario['email']}) concluiu o quiz de perfil.\n\n" .
        "Resultado preditivo:\n" .
        "- Perfil estimado: {$perfil}\n" .
        "- Índice preditivo: {$indice}\n" .
        "- Hard Skills (Técnico): {$notas['tecnico']}\n" .
        "- Soft Skills (Comportamento): {$notas['comportamento']}\n" .
        "- Liderança: {$notas['lideranca']}\n\n" .
        "Acesse o sistema para revisar o perfil completo.";

    $cabecalhos = [
        'From: ' . EMAIL_REMETENTE,
        'Reply-To: ' . EMAIL_REMETENTE,
        'Content-Type: text/plain; charset=UTF-8',
    ];

    @mail(implode(',', $emails), $assunto, $mensagem, implode("\r\n", $cabecalhos));
}

function calcularNotas(array $respostas, array $perguntas): array
{
    $pesos = ['tecnico' => 1.0, 'comportamento' => 1.0, 'lideranca' => 1.5];
    $somas = ['tecnico' => 0, 'comportamento' => 0, 'lideranca' => 0];
    $totais = ['tecnico' => 0, 'comportamento' => 0, 'lideranca' => 0];

    foreach ($perguntas as $pergunta) {
        $id = $pergunta['id'];
        $categoria = $pergunta['categoria'];

        if (!isset($respostas[$id])) {
            continue;
        }

        $valor = (float) $respostas[$id];
        $somas[$categoria] += $valor * $pesos[$categoria];
        $totais[$categoria] += $pesos[$categoria];
    }

    $notas = [];
    foreach ($somas as $categoria => $soma) {
        if ($totais[$categoria] === 0) {
            $notas[$categoria] = 0;
            continue;
        }

        // Escala 1-5 convertida para 0-10 com ponderação de categoria.
        $notas[$categoria] = round(($soma / $totais[$categoria]) * 2, 1);
    }

    return $notas;
}

function finalizarQuiz(PDO $db, array $dados): void
{
    require_once __DIR__ . '/perguntas_quiz.php';

    $usuarioId = (int) ($dados['usuario_id'] ?? 0);
    $respostas = $dados['respostas'] ?? [];

    if ($usuarioId <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário inválido.']);
        return;
    }

    if (!is_array($respostas)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Respostas inválidas.']);
        return;
    }

    foreach ($PERGUNTAS_QUIZ as $pergunta) {
        $id = $pergunta['id'];
        if (!isset($respostas[$id]) || $respostas[$id] < 1 || $respostas[$id] > 5) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Responda todas as perguntas antes de terminar.']);
            return;
        }
    }

    $notas = calcularNotas($respostas, $PERGUNTAS_QUIZ);

    $db->beginTransaction();

    try {
        $usuario = buscarUsuario($db, $usuarioId);
        if (!$usuario) {
            throw new Exception('Usuário não encontrado.');
        }

        $resultadoPerfil = gerarPerfilPredictivo($notas);

        $stmtExiste = $db->prepare('SELECT id FROM notas_quiz WHERE usuario_id = :usuario_id LIMIT 1');
        $stmtExiste->execute(['usuario_id' => $usuarioId]);
        $notaExistente = $stmtExiste->fetch(PDO::FETCH_ASSOC);

        if ($notaExistente) {
            $stmtNotas = $db->prepare('
                UPDATE notas_quiz
                SET lideranca = :lideranca, tecnico = :tecnico, comportamento = :comportamento,
                    perfil_resultado = :perfil_resultado, indice_preditivo = :indice_preditivo,
                    data_registro = datetime("now", "localtime")
                WHERE usuario_id = :usuario_id
            ');
        } else {
            $stmtNotas = $db->prepare('
                INSERT INTO notas_quiz (usuario_id, lideranca, tecnico, comportamento, perfil_resultado, indice_preditivo)
                VALUES (:usuario_id, :lideranca, :tecnico, :comportamento, :perfil_resultado, :indice_preditivo)
            ');
        }

        $stmtNotas->execute([
            'usuario_id' => $usuarioId,
            'lideranca' => $notas['lideranca'],
            'tecnico' => $notas['tecnico'],
            'comportamento' => $notas['comportamento'],
            'perfil_resultado' => $resultadoPerfil['perfil'],
            'indice_preditivo' => $resultadoPerfil['indice'],
        ]);

        $stmtProgresso = $db->prepare('
            INSERT INTO progresso_quiz (usuario_id, respostas, indice_atual, concluido, atualizado_em)
            VALUES (:usuario_id, :respostas, :indice_atual, 1, datetime("now", "localtime"))
            ON CONFLICT(usuario_id) DO UPDATE SET
                respostas = excluded.respostas,
                indice_atual = excluded.indice_atual,
                concluido = 1,
                atualizado_em = datetime("now", "localtime")
        ');

        $stmtProgresso->execute([
            'usuario_id' => $usuarioId,
            'respostas' => json_encode($respostas, JSON_UNESCAPED_UNICODE),
            'indice_atual' => count($PERGUNTAS_QUIZ) - 1,
        ]);

        $db->commit();

        enviarNotificacaoRH(RH_NOTIF_EMAILS, $usuario, $resultadoPerfil['perfil'], $resultadoPerfil['indice'], $notas);

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Quiz finalizado com sucesso!',
            'notas' => array_merge($notas, [
                'perfil_resultado' => $resultadoPerfil['perfil'],
                'indice_preditivo' => $resultadoPerfil['indice'],
            ]),
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao finalizar: ' . $e->getMessage()]);
    }
}

/**
 * Busca todos os colaboradores e as notas para a tela do gestor.
 * Também calcula a média das notas para vermos as lacunas de treinamento.
 */
function obterDadosGestor(PDO $db, array $dados): void
{
    $gestorId = (int) ($dados['gestor_id'] ?? 0);
    
    // Verifica se quem chamou a função é mesmo um gestor
    $stmtGestor = $db->prepare('SELECT perfil FROM usuarios WHERE id = :id');
    $stmtGestor->execute(['id' => $gestorId]);
    $gestor = $stmtGestor->fetch(PDO::FETCH_ASSOC);
    
    if (!$gestor || $gestor['perfil'] !== 'gestor') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado. Apenas gestores podem ver esta lista.']);
        return;
    }

    // Consulta simples para juntar o usuário colaborador, suas notas e progresso do teste
    $stmt = $db->query('
        SELECT 
            u.id, 
            u.nome, 
            u.email,
            nq.tecnico, 
            nq.comportamento, 
            nq.lideranca, 
            nq.perfil_resultado, 
            nq.indice_preditivo,
            pq.concluido,
            pq.atualizado_em
        FROM usuarios u
        LEFT JOIN notas_quiz nq ON u.id = nq.usuario_id
        LEFT JOIN progresso_quiz pq ON u.id = pq.usuario_id
        WHERE u.perfil = "colaborador"
        ORDER BY u.nome ASC
    ');
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Listas e variáveis para preparar os dados e calcular as médias
    $listaFormatada = [];
    $somaTecnico = 0;
    $somaComportamento = 0;
    $somaLideranca = 0;
    $totalConcluidos = 0;

    foreach ($colaboradores as $c) {
        $concluido = (bool) ($c['concluido'] ?? false);
        
        // Define o status simples baseado se terminou, começou ou não fez nada
        $status = 'Não iniciado';
        if ($concluido) {
            $status = 'Concluído';
        } else if ($c['atualizado_em'] !== null) {
            $status = 'Em andamento';
        }

        // Adiciona à nossa lista simples
        $listaFormatada[] = [
            'id' => (int) $c['id'],
            'nome' => $c['nome'],
            'email' => $c['email'],
            'status' => $status,
            'tecnico' => $c['tecnico'] !== null ? (float) $c['tecnico'] : null,
            'comportamento' => $c['comportamento'] !== null ? (float) $c['comportamento'] : null,
            'lideranca' => $c['lideranca'] !== null ? (float) $c['lideranca'] : null,
            'perfil' => $c['perfil_resultado'] ?: 'Não definido',
            'indice' => $c['indice_preditivo'] !== null ? (float) $c['indice_preditivo'] : null,
        ];

        // Se o funcionário concluiu o teste, usamos as notas dele para a média geral
        if ($concluido) {
            $somaTecnico += (float) $c['tecnico'];
            $somaComportamento += (float) $c['comportamento'];
            $somaLideranca += (float) $c['lideranca'];
            $totalConcluidos++;
        }
    }

    // Calcula a média das notas da empresa (se ninguém terminou, a média é 0)
    $medias = [
        'tecnico' => $totalConcluidos > 0 ? round($somaTecnico / $totalConcluidos, 1) : 0,
        'comportamento' => $totalConcluidos > 0 ? round($somaComportamento / $totalConcluidos, 1) : 0,
        'lideranca' => $totalConcluidos > 0 ? round($somaLideranca / $totalConcluidos, 1) : 0,
    ];

    // Envia a resposta final para o JavaScript ler
    echo json_encode([
        'sucesso' => true,
        'colaboradores' => $listaFormatada,
        'medias' => $medias,
        'metas' => [
            'tecnico' => 8.0,       // Meta que a empresa deseja atingir em técnico
            'comportamento' => 8.0, // Meta desejada em comportamento
            'lideranca' => 7.0,     // Meta desejada em liderança
        ]
    ]);
}

/**
 * Cria um novo usuário no banco de dados.
 * Valida e-mail único, cria hash da senha e insere o usuário na tabela.
 */
function criarUsuario(PDO $db, array $dados): void
{
    $nome = trim($dados['nome'] ?? '');
    $email = trim($dados['email'] ?? '');
    $perfil = trim($dados['perfil'] ?? '');
    $senha = $dados['password'] ?? '';

    // Validações básicas
    if (!$nome || !$email || !$perfil || !$senha) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Todos os campos são obrigatórios.']);
        return;
    }

    if (strlen($senha) < 6) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A palavra-passe deve ter no mínimo 6 caracteres.']);
        return;
    }

    if ($perfil !== 'colaborador' && $perfil !== 'gestor') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Perfil inválido.']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail inválido.']);
        return;
    }

    try {
        // Verifica se o e-mail já existe
        $stmtVerifica = $db->prepare('SELECT id FROM usuarios WHERE email = :email');
        $stmtVerifica->execute(['email' => $email]);

        if ($stmtVerifica->fetch()) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Este e-mail já está registrado.']);
            return;
        }

        // Cria o hash da senha
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        // Insere o novo usuário
        $stmtInsere = $db->prepare('
            INSERT INTO usuarios (nome, email, perfil, senha)
            VALUES (:nome, :email, :perfil, :senha)
        ');

        $stmtInsere->execute([
            'nome' => $nome,
            'email' => $email,
            'perfil' => $perfil,
            'senha' => $senhaHash,
        ]);

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Usuário criado com sucesso! Você já pode fazer login.',
        ]);
    } catch (PDOException $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar usuário: ' . $e->getMessage()]);
    }
}

