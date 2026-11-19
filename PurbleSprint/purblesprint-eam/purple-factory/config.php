<?php
declare(strict_types=1);

// Carrega variáveis do arquivo .env na raiz
if (file_exists(__DIR__ . '/.env')) {
    $linhas = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        if (str_starts_with(trim($linha), '#') || !str_contains($linha, '=')) continue;
        [$chave, $valor] = explode('=', $linha, 2);
        putenv(trim($chave) . '=' . trim($valor));
    }
}

/**
 * Conecta ao banco SQLite e inicializa as tabelas da Esteira PurpleSprint.
 */
function conectarBanco(): PDO {
    $dir = __DIR__ . '/database';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . $dir . '/purplesprint.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            senha_hash TEXT NOT NULL,
            perfil TEXT NOT NULL DEFAULT 'cliente',
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS sprints (
            codigo TEXT PRIMARY KEY,
            cliente_nome TEXT NOT NULL,
            receita_tipo TEXT NOT NULL,
            descricao_ideia TEXT NOT NULL,
            modalidade_video TEXT NOT NULL,
            data_agendada TEXT,
            horario_agendado TEXT,
            idioma TEXT NOT NULL,
            libras INTEGER NOT NULL DEFAULT 1,
            legendas INTEGER NOT NULL DEFAULT 1,
            zoom_link TEXT NOT NULL,
            etapa_atual INTEGER NOT NULL DEFAULT 1,
            validacao_sequencial INTEGER NOT NULL DEFAULT 0,
            status_aprovacao TEXT NOT NULL DEFAULT 'Em Produção',
            preview_url TEXT NOT NULL,
            stitch_project_id TEXT,
            stitch_screen_id TEXT,
            stitch_preview_path TEXT,
            stitch_status TEXT NOT NULL DEFAULT 'Pendente',
            antigravity_status TEXT NOT NULL DEFAULT 'Pendente',
            codigo_gerado_path TEXT,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Migração compatível com bancos criados antes do painel de clientes.
    $colunas = $pdo->query('PRAGMA table_info(sprints)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('usuario_id', $colunas, true)) {
        $pdo->exec('ALTER TABLE sprints ADD COLUMN usuario_id INTEGER');
    }
    $migracoes = [
        'stitch_project_id' => 'TEXT',
        'stitch_screen_id' => 'TEXT',
        'stitch_preview_path' => 'TEXT',
        'stitch_status' => "TEXT NOT NULL DEFAULT 'Pendente'",
        'antigravity_status' => "TEXT NOT NULL DEFAULT 'Pendente'",
        'codigo_gerado_path' => 'TEXT',
        'validacao_sequencial' => 'INTEGER NOT NULL DEFAULT 0',
    ];
    foreach ($migracoes as $coluna => $tipo) {
        if (!in_array($coluna, $colunas, true)) {
            $pdo->exec("ALTER TABLE sprints ADD COLUMN {$coluna} {$tipo}");
        }
    }
    $pdo->exec("UPDATE sprints SET etapa_atual = 1, validacao_sequencial = 1 WHERE validacao_sequencial = 0 AND status_aprovacao = 'Fornada Pronta'");
    $pdo->exec("UPDATE sprints SET etapa_atual = 6, validacao_sequencial = 1 WHERE validacao_sequencial = 0 AND status_aprovacao IN ('Publicado em Produção', 'Concluído')");
    $pdo->exec('UPDATE sprints SET validacao_sequencial = 1 WHERE validacao_sequencial = 0');

    $emailAdmin = getenv('ADMIN_EMAIL') ?: '';
    $senhaAdmin = getenv('ADMIN_PASSWORD') ?: '';
    if ($emailAdmin !== '' && $senhaAdmin !== '') {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES ('Administrador', :email, :senha, 'admin') ON CONFLICT(email) DO UPDATE SET senha_hash = excluded.senha_hash, perfil = 'admin'");
        $stmt->execute([':email' => $emailAdmin, ':senha' => password_hash($senhaAdmin, PASSWORD_DEFAULT)]);
    }

    return $pdo;
}

/**
 * Sanitiza strings para evitar XSS.
 */
function e(?string $valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function dadosEtapaAtual(array $sprint): array {
    $etapa = max(1, min(5, (int)($sprint['etapa_atual'] ?? 1)));
    return match ($etapa) {
        1 => ['titulo' => 'Envio da ideia e briefing', 'descricao' => $sprint['modalidade_video'] === 'sem_chamada' ? 'Confira sua ideia enviada sem chamada.' : 'Confira os dados do briefing e a reunião vinculada.'],
        2 => ['titulo' => 'Revisão do protótipo', 'descricao' => 'Confira o protótipo criado para sua ideia e valide esta etapa.'],
        3 => ['titulo' => 'Revisão do software', 'descricao' => 'Confira o código e o preview gerados para o projeto.'],
        4 => ['titulo' => 'Validação do cliente', 'descricao' => 'Confirme se o protótipo e o software atendem ao que você pediu.'],
        default => ['titulo' => 'Aprovação final', 'descricao' => 'Aprove o projeto para concluir e publicar esta fornada.'],
    };
}
