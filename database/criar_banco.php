<?php
/**
 * MAPA - Script para criar o banco de dados SQLite
 * 
 * ============================================================================
 * EXPLICAÇÃO PARA LEIGOS:
 * Este arquivo funciona como o "arquiteto" e "construtor" do nosso banco de dados.
 * Ele cria o arquivo que guardará todas as informações do sistema (banco de dados)
 * e desenha as "tabelas" (que são como planilhas do Excel) onde cada informação 
 * será organizada (usuários, notas do quiz e progresso).
 * ============================================================================
 *
 * O que este arquivo faz:
 * 1. Cria o arquivo mapa.db (se ainda não existir)
 * 2. Cria a tabela de usuários (como uma planilha de funcionários)
 * 3. Cria a tabela de notas do quiz (onde ficam os resultados dos testes)
 * 4. Insere alguns usuários de exemplo para testes
 *
 * Como usar:
 * - Abra no navegador: http://localhost/Projeto-TCC-Senai/database/criar_banco.php
 * - Ou no terminal: php database/criar_banco.php
 *
 * Execute apenas UMA vez. Depois, o backend (backend/index.php) já usa este banco.
 */

// EXPLICAÇÃO: Aqui definimos onde o arquivo do banco de dados será salvo. 
// O '__DIR__' significa "a pasta atual onde este script PHP está guardado".
// O banco de dados será um arquivo chamado 'mapa.db' localizado nessa mesma pasta.
$caminhoBanco = __DIR__ . '/mapa.db';

// EXPLICAÇÃO: O bloco "try" (tentar) serve para o PHP tentar executar as instruções abaixo.
// Se acontecer qualquer erro no meio do caminho, o código para e vai direto para o bloco "catch" (capturar) no final,
// evitando que o sistema trave de forma feia e mostrando uma mensagem de erro compreensível.
try {
    
    // EXPLICAÇÃO: Criamos uma nova conexão com o banco de dados SQLite.
    // PDO (PHP Data Objects) é uma ferramenta do PHP para conversar com bancos de dados.
    // "sqlite:" avisa ao PHP que usaremos o SQLite (um tipo de banco de dados super leve que guarda tudo em um único arquivo).
    // Se o arquivo 'mapa.db' não existir ainda na pasta, o próprio PHP cria ele na hora.
    $db = new PDO('sqlite:' . $caminhoBanco);
    
    // EXPLICAÇÃO: Configura a conexão para nos avisar imediatamente (lançar uma exceção) caso ocorra qualquer erro de SQL.
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // -------------------------------------------------------------------------
    // TABELA 1: usuarios
    // Guarda quem pode entrar no sistema (funcionário ou gestor/RH)
    // -------------------------------------------------------------------------
    // EXPLICAÇÃO: "$db->exec" executa uma ordem no banco de dados.
    // "CREATE TABLE IF NOT EXISTS" significa: "Crie uma tabela (planilha) chamada 'usuarios', 
    // mas só faça isso se ela já não existir".
    $db->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            -- 'id': Um número de identificação único para cada usuário. 
            -- Ele começa em 1 e aumenta sozinho (+1) a cada novo cadastro (AUTOINCREMENT).
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            
            -- 'nome': Nome da pessoa. TEXT significa texto e NOT NULL significa que é obrigatório preencher.
            nome TEXT NOT NULL,
            
            -- 'email': E-mail da pessoa. UNIQUE garante que duas pessoas não usem o mesmo e-mail no sistema.
            email TEXT NOT NULL UNIQUE,
            
            -- 'senha': A senha secreta de acesso (que será guardada criptografada).
            senha TEXT NOT NULL,
            
            -- 'perfil': Diz qual é o papel do usuário no sistema. 
            -- O comando 'CHECK' é uma regra/filtro que só permite cadastrar se for 'colaborador' ou 'gestor'.
            perfil TEXT NOT NULL CHECK (perfil IN ('colaborador', 'gestor'))
        )
    ");

    // -------------------------------------------------------------------------
    // TABELA 2: notas_quiz
    // Guarda as notas de cada colaborador no quiz (teste/questionário)
    // -------------------------------------------------------------------------
    $db->exec("
        CREATE TABLE IF NOT EXISTS notas_quiz (
            -- Identificador único para cada resultado de quiz registrado.
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            
            -- 'usuario_id': Indica de qual usuário da tabela 'usuarios' é este resultado.
            usuario_id INTEGER NOT NULL,
            
            -- 'lideranca', 'tecnico', 'comportamento': Notas em formato decimal (REAL, ex: 7.5).
            lideranca REAL NOT NULL,
            tecnico REAL NOT NULL,
            comportamento REAL NOT NULL,
            
            -- 'perfil_resultado': O diagnóstico final gerado pelo quiz (ex: 'Perfil Técnico').
            perfil_resultado TEXT,
            
            -- 'indice_preditivo': Um cálculo matemático/estatístico baseado nas respostas.
            indice_preditivo REAL,
            
            -- 'data_registro': Guarda o dia e hora exatos em que o teste foi concluído.
            -- Por padrão (DEFAULT), pega o horário atual do servidor ajustado para o horário local.
            data_registro TEXT DEFAULT (datetime('now', 'localtime')),
            
            -- 'FOREIGN KEY' (Chave Estrangeira): É um elo de ligação.
            -- Garante que o campo 'usuario_id' desta tabela aponte obrigatoriamente para um 'id' válido
            -- que realmente exista na tabela 'usuarios'. Isso evita registros 'órfãos'.
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");

    // -------------------------------------------------------------------------
    // TABELA 3: progresso_quiz
    // Guarda respostas temporárias do quiz para o colaborador poder continuar depois
    // -------------------------------------------------------------------------
    $db->exec("
        CREATE TABLE IF NOT EXISTS progresso_quiz (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            
            -- 'usuario_id': Cada usuário pode ter apenas uma sessão de progresso ativa por vez (UNIQUE).
            usuario_id INTEGER NOT NULL UNIQUE,
            
            -- 'respostas': Guarda as respostas temporárias em formato de texto estruturado (JSON).
            respostas TEXT NOT NULL,
            
            -- 'indice_atual': Guarda em qual pergunta (número) o usuário parou (por padrão começa em 0).
            indice_atual INTEGER NOT NULL DEFAULT 0,
            
            -- 'concluido': Indica se o quiz já foi finalizado (0 para não concluído, 1 para concluído).
            concluido INTEGER NOT NULL DEFAULT 0,
            
            -- 'atualizado_em': Guarda quando o progresso foi modificado pela última vez.
            atualizado_em TEXT DEFAULT (datetime('now', 'localtime')),
            
            -- Liga esta tabela à tabela de usuários.
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");

    // -------------------------------------------------------------------------
    // Dados de exemplo (opcional) — só insere se a tabela estiver vazia
    // Senha de todos: 123456 (sempre guardada de forma segura/embaralhada, nunca pura)
    // -------------------------------------------------------------------------
    // EXPLICAÇÃO: Aqui nós perguntamos ao banco quantos usuários já estão cadastrados.
    // 'SELECT COUNT(*)' conta as linhas e 'fetchColumn()' pega o resultado numérico.
    $totalUsuarios = (int) $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

    // EXPLICAÇÃO: Se a contagem for zero (ou seja, o banco acabou de ser criado e está vazio),
    // nós inserimos alguns dados fictícios para que o sistema possa ser testado de imediato.
    if ($totalUsuarios === 0) {
        
        // EXPLICAÇÃO: password_hash embaralha a senha "123456" de um jeito que ninguém consiga
        // ler a senha original no banco de dados. Isso protege as senhas em caso de invasão.
        $senhaHash = password_hash('123456', PASSWORD_DEFAULT);

        // EXPLICAÇÃO: Criamos um "modelo" de comando para inserir usuários de forma segura.
        // O uso de ":nome", ":email", etc., evita ataques de invasores (conhecidos como SQL Injection).
        $inserirUsuario = $db->prepare("
            INSERT INTO usuarios (nome, email, senha, perfil)
            VALUES (:nome, :email, :senha, :perfil)
        ");

        // EXPLICAÇÃO: Executamos o modelo acima preenchendo os dados do primeiro usuário (Colaborador/Funcionário).
        $inserirUsuario->execute([
            'nome'   => 'João Silva',
            'email'  => 'joao@parex.com.br',
            'senha'  => $senhaHash,
            'perfil' => 'colaborador',
        ]);

        // EXPLICAÇÃO: Executamos o mesmo modelo para criar o segundo usuário (Gestor/RH).
        $inserirUsuario->execute([
            'nome'   => 'Maria Santos',
            'email'  => 'maria@parex.com.br',
            'senha'  => $senhaHash,
            'perfil' => 'gestor',
        ]);

        // EXPLICAÇÃO: Insere também uma nota fictícia para o João Silva (usuario_id = 1),
        // permitindo que o painel de gráficos já tenha dados para exibir nos testes.
        $db->prepare("
            INSERT INTO notas_quiz (usuario_id, lideranca, tecnico, comportamento, perfil_resultado, indice_preditivo)
            VALUES (1, 7.5, 8.0, 6.5, 'Perfil Técnico', 8.1)
        ")->execute();
    }

    // EXPLICAÇÃO: Se tudo deu certo até aqui, guardamos a mensagem de sucesso com a localização do arquivo criado.
    $mensagem = "Banco criado com sucesso em: {$caminhoBanco}";

} catch (PDOException $e) {
    // EXPLICAÇÃO: Se acontecer algum problema em qualquer parte do banco (como falta de permissão para criar o arquivo),
    // o código vem direto para cá e guarda o motivo do erro na variável.
    $mensagem = "Erro ao criar o banco: " . $e->getMessage();
}

// -------------------------------------------------------------------------
// Exibição do Resultado
// -------------------------------------------------------------------------
// EXPLICAÇÃO: Aqui verificamos como este script foi executado.
// Se 'php_sapi_name()' NÃO for 'cli' (Command Line Interface/Linha de Comando), significa que ele foi aberto
// no navegador (Chrome, Edge, etc.). Então ele desenha uma tela bonita em HTML com os detalhes.
if (php_sapi_name() !== 'cli') {
    // Avisa ao navegador que vamos enviar uma página de texto em formato HTML estruturado.
    header('Content-Type: text/html; charset=utf-8');
    
    // Imprime na tela o design básico da página contendo a mensagem de sucesso/erro e os dados de login de teste.
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>MAPA - Banco</title></head><body>';
    echo '<h1>MAPA - Criação do banco</h1>';
    echo '<p>' . htmlspecialchars($mensagem) . '</p>';
    echo '<p><strong>Usuários de teste</strong> (senha: 123456):</p>';
    echo '<ul>';
    echo '<li>joao@parex.com.br — colaborador (funcionário)</li>';
    echo '<li>maria@parex.com.br — gestor (RH)</li>';
    echo '</ul>';
    echo '</body></html>';
} else {
    // EXPLICAÇÃO: Se o script foi rodado direto pelo terminal (linha de comando), 
    // ele apenas imprime a mensagem de texto pura seguida por uma quebra de linha.
    echo $mensagem . PHP_EOL;
}

