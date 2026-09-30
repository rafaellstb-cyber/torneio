CREATE TABLE IF NOT EXISTS torneio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    data VARCHAR(10) NOT NULL,
    local VARCHAR(200) NOT NULL,
    hora_inicio VARCHAR(5) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'nao_iniciado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS times (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nome VARCHAR(200) NOT NULL,
    atletas TEXT,
    desempate_manual INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS jogos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL UNIQUE,
    fase VARCHAR(20) NOT NULL,
    rodada VARCHAR(20),
    quadra INT NOT NULL,
    time1_id INT NULL,
    time2_id INT NULL,
    rotulo_slot VARCHAR(50),
    origem1 VARCHAR(100),
    origem2 VARCHAR(100),
    pontos1 INT NULL,
    pontos2 INT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pendente',
    encerrado_em VARCHAR(30),
    FOREIGN KEY (time1_id) REFERENCES times(id),
    FOREIGN KEY (time2_id) REFERENCES times(id),
    INDEX idx_jogos_fase (fase),
    INDEX idx_jogos_rodada (rodada),
    INDEX idx_jogos_quadra (quadra),
    INDEX idx_jogos_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS log_alteracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jogo_id INT NOT NULL,
    usuario VARCHAR(100),
    campo VARCHAR(50) NOT NULL,
    valor_anterior VARCHAR(255),
    valor_novo VARCHAR(255),
    criado_em VARCHAR(30) NOT NULL,
    FOREIGN KEY (jogo_id) REFERENCES jogos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS configuracoes (
    chave VARCHAR(100) PRIMARY KEY,
    valor TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
