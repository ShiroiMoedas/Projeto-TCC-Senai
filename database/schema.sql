-- ============================================================
-- MAPA - Esquema do banco de dados (SQLite)
-- ============================================================
-- Este arquivo mostra a estrutura das tabelas em SQL puro.
-- Para criar o banco de fato, use o script: criar_banco.php
-- ============================================================

-- Tabela de usuários (colaboradores e gestores de RH)
CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    senha TEXT NOT NULL,
    -- perfil: 'colaborador' = funcionário | 'gestor' = RH
    perfil TEXT NOT NULL CHECK (perfil IN ('colaborador', 'gestor'))
);

-- Tabela de notas do quiz de competências
CREATE TABLE IF NOT EXISTS notas_quiz (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    lideranca REAL NOT NULL,
    tecnico REAL NOT NULL,
    comportamento REAL NOT NULL,
    perfil_resultado TEXT,
    indice_preditivo REAL,
    data_registro TEXT DEFAULT (datetime('now', 'localtime')),
    --Jeito de herdar a relação entre as tabelas: cada nota pertence a um usuário;
    --atraves da chave estrangeira refenciando o id do usuário na tabela de usuários.
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de progresso parcial do quiz (salvar e continuar depois)
CREATE TABLE IF NOT EXISTS progresso_quiz (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL UNIQUE,
    respostas TEXT NOT NULL,
    indice_atual INTEGER NOT NULL DEFAULT 0,
    concluido INTEGER NOT NULL DEFAULT 0,
    atualizado_em TEXT DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
