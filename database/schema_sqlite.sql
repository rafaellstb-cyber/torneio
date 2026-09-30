CREATE TABLE IF NOT EXISTS torneio (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    data TEXT NOT NULL,
    local TEXT NOT NULL,
    hora_inicio TEXT NOT NULL,
    duracao_rodada_min INTEGER NOT NULL DEFAULT 30,
    status TEXT NOT NULL DEFAULT 'nao_iniciado'
);

CREATE TABLE IF NOT EXISTS times (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo TEXT NOT NULL UNIQUE,
    nome TEXT NOT NULL,
    atletas TEXT,
    desempate_manual INTEGER
);

CREATE TABLE IF NOT EXISTS jogos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero INTEGER NOT NULL UNIQUE,
    fase TEXT NOT NULL,
    rodada TEXT,
    quadra INTEGER NOT NULL,
    horario_previsto TEXT,
    time1_id INTEGER,
    time2_id INTEGER,
    rotulo_slot TEXT,
    origem1 TEXT,
    origem2 TEXT,
    pontos1 INTEGER,
    pontos2 INTEGER,
    status TEXT NOT NULL DEFAULT 'pendente',
    encerrado_em TEXT,
    FOREIGN KEY (time1_id) REFERENCES times(id),
    FOREIGN KEY (time2_id) REFERENCES times(id)
);

CREATE INDEX IF NOT EXISTS idx_jogos_fase ON jogos(fase);
CREATE INDEX IF NOT EXISTS idx_jogos_rodada ON jogos(rodada);
CREATE INDEX IF NOT EXISTS idx_jogos_quadra ON jogos(quadra);
CREATE INDEX IF NOT EXISTS idx_jogos_status ON jogos(status);

CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario TEXT NOT NULL UNIQUE,
    senha_hash TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS log_alteracoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    jogo_id INTEGER NOT NULL,
    usuario TEXT,
    campo TEXT NOT NULL,
    valor_anterior TEXT,
    valor_novo TEXT,
    criado_em TEXT NOT NULL,
    FOREIGN KEY (jogo_id) REFERENCES jogos(id)
);

CREATE TABLE IF NOT EXISTS configuracoes (
    chave TEXT PRIMARY KEY,
    valor TEXT
);
