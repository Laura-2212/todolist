<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/zoom_api.php';
require_once __DIR__ . '/integrations.php';

$pdo = conectarBanco();
$alerta = '';
$salaZoomGerada = '';

// Processa criação de nova fornada na Esteira PurpleSprint + Sala Zoom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'iniciar_sprint' && empty($_SESSION['usuario'])) {
    $alerta = 'Entre ou crie uma conta de cliente antes de enviar sua ideia para acompanhar e validar as etapas.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'iniciar_sprint' && !empty($_SESSION['usuario'])) {
    $cliente    = trim($_POST['cliente_nome'] ?? 'Cliente PurpleSprint');
    $receita    = $_POST['receita_tipo'] ?? 'App SaaS MVP';
    $ideia      = trim($_POST['descricao_ideia'] ?? '');
    $modalidade = $_POST['modalidade_video'] ?? 'agora';
    $dataAgend  = $modalidade === 'agendar' ? ($_POST['data_agendada'] ?? 'Hoje') : '';
    $horaAgend  = $modalidade === 'agendar' ? ($_POST['horario_agendado'] ?? '09:30') : '';
    $idioma     = $_POST['idioma'] ?? 'Português';
    $libras     = isset($_POST['libras']);
    $legendas   = isset($_POST['legendas']);

    if ($ideia !== '') {
        $codigo = '#Sprint-' . rand(100, 999);
        $slug   = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $codigo));
        $previewUrl = '';

        // Integração com a API do Zoom
        $zoomLink = $modalidade === 'sem_chamada' ? '' : criarSalaBriefingZoom($codigo, $modalidade, $dataAgend, $horaAgend, $idioma, $libras, $legendas);
        $salaZoomGerada = $zoomLink;

        $stmt = $pdo->prepare("
            INSERT INTO sprints (codigo, cliente_nome, receita_tipo, descricao_ideia, modalidade_video, data_agendada, horario_agendado, idioma, libras, legendas, zoom_link, etapa_atual, validacao_sequencial, status_aprovacao, preview_url, usuario_id)
            VALUES (:cod, :cli, :rec, :ideia, :mod, :dt, :hr, :idi, :lib, :leg, :zlink, 1, 1, 'Em Produção', :prev, :uid)
        ");
        $stmt->execute([
            ':cod'   => $codigo,
            ':cli'   => $cliente,
            ':rec'   => $receita,
            ':ideia' => $ideia,
            ':mod'   => $modalidade,
            ':dt'    => $dataAgend,
            ':hr'    => $horaAgend,
            ':idi'   => $idioma,
            ':lib'   => $libras ? 1 : 0,
            ':leg'   => $legendas ? 1 : 0,
            ':zlink' => $zoomLink,
            ':prev'  => $previewUrl,
            ':uid'   => $_SESSION['usuario']['id']
        ]);

        ini_set('max_execution_time', '0');
        $geracoes = executarGeracaoIntegrada($codigo, $ideia, $receita);
        $stitch = $geracoes['stitch'];
        $agente = $geracoes['antigravity'];
        $upd = $pdo->prepare('UPDATE sprints SET stitch_project_id=:sp, stitch_screen_id=:ss, stitch_preview_path=:sh, stitch_status=:st, antigravity_status=:as, codigo_gerado_path=:cp, preview_url=:prev WHERE codigo=:c');
        $upd->execute([
            ':sp' => $stitch['projectId'] ?? null,
            ':ss' => $stitch['screenId'] ?? null,
            ':sh' => !empty($stitch['ok']) ? "generated-projects/{$slug}/stitch-preview.html" : null,
            ':st' => !empty($stitch['ok']) ? 'Design gerado' : 'Pendente: ' . ($stitch['erro'] ?? 'erro desconhecido'),
            ':as' => !empty($agente['ok']) ? 'Código gerado' : 'Pendente: ' . ($agente['erro'] ?? 'erro desconhecido'),
            ':cp' => $agente['path'] ?? null,
            ':prev' => !empty($agente['ok']) ? "generated-projects/{$slug}/index.php" : '',
            ':c' => $codigo,
        ]);
        $alerta = $modalidade === 'sem_chamada' ? "Ideia {$codigo} enviada sem chamada." : "Fornada {$codigo} enviada com briefing Zoom.";
        $alerta .= ' Stitch: ' . (!empty($stitch['ok']) ? 'protótipo gerado' : 'pendente') . '; Antigravity: ' . (!empty($agente['ok']) ? 'código gerado' : 'pendente') . '.';
    }
}

if (!empty($_SESSION['usuario']) && $_SESSION['usuario']['perfil'] === 'admin') {
    $sprintAtiva = $pdo->query('SELECT * FROM sprints ORDER BY criado_em DESC LIMIT 1')->fetch();
} elseif (!empty($_SESSION['usuario'])) {
    $consulta = $pdo->prepare('SELECT * FROM sprints WHERE usuario_id = :id ORDER BY criado_em DESC LIMIT 1');
    $consulta->execute([':id' => $_SESSION['usuario']['id']]);
    $sprintAtiva = $consulta->fetch();
} else {
    $sprintAtiva = false;
}
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PurpleSprint — Confeitaria de Software em 30 Minutos</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="header">
    <div class="logo-area">
        <a class="logo-home" href="index.php" aria-label="Voltar para o início"><span class="logo-icon">PurpleSprint</span></a>
        <div>
            <strong>30 MIN EXPRESS</strong><br>
            <small style="color: var(--texto-suave);">Sua ideia, construída etapa por etapa</small>
        </div>
    </div>

    <div class="timer-badge" id="cronometro">Fornalha Ativa • 24:13</div>

    <nav class="top-nav" aria-label="Conta">
        <?php if (!empty($_SESSION['usuario'])): ?>
            <a class="nav-link" href="cliente.php">Meus projetos</a>
            <?php if ($_SESSION['usuario']['perfil'] === 'admin'): ?><a class="nav-link" href="admin.php">Área ADM</a><?php endif; ?>
            <a class="nav-button" href="logout.php">Sair</a>
        <?php else: ?>
            <a class="nav-link" href="cliente.php">Meus projetos</a>
            <a class="nav-button" href="login.php">Entrar na conta</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container">

    <section class="hero-topo">
        <h1>Mande sua ideia. Receba seu software pronto <span>em até 30 minutos.</span></h1>
        <p style="color: var(--texto-suave); max-width: 760px; margin: 0 auto;">
            Envie sua ideia e acompanhe a construção do projeto. Você valida cada etapa antes de avançarmos.
        </p>
    </section>

    <?php if ($alerta !== ''): ?>
        <div class="card" style="border-color: var(--petroleo); background: rgba(24, 80, 106, 0.28); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <strong>Esteira Atualizada:</strong> <?= e($alerta) ?>
            </div>
            <?php if ($salaZoomGerada !== ''): ?>
                <a href="<?= e($salaZoomGerada) ?>" target="_blank" rel="noopener" class="btn btn-zoom">
                    Abrir Sala Zoom Agora
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="grid-workstation">

        <!-- COLUNA ESQUERDA: RECEITA BASE + BRIEFING ZOOM COM CALENDÁRIO & ACESSIBILIDADE -->
        <div>
            <form method="POST" action="index.php" class="card">
                <input type="hidden" name="acao" value="iniciar_sprint">

                <div class="card-header">
                    <h2 class="card-titulo">Selecione a Receita Base</h2>
                </div>

                <div class="chips-grid">
                    <label class="chip-radio">
                        <input type="radio" name="receita_tipo" value="Landing Page">
                        <span>Landing Page</span>
                    </label>
                    <label class="chip-radio">
                        <input type="radio" name="receita_tipo" value="E-commerce Express">
                        <span>E-commerce</span>
                    </label>
                    <label class="chip-radio">
                        <input type="radio" name="receita_tipo" value="App SaaS MVP" checked>
                        <span>App SaaS MVP</span>
                    </label>
                    <label class="chip-radio">
                        <input type="radio" name="receita_tipo" value="Automação & API">
                        <span>Automação</span>
                    </label>
                </div>

                <div class="campo">
                    <label for="cliente_nome">Seu Nome ou Projeto</label>
                    <input type="text" id="cliente_nome" name="cliente_nome" placeholder="Ex: Doceria Gourmet & Gestão de Pedidos" required>
                </div>

                <div class="campo">
                    <label for="descricao_ideia">Ingredientes da Ideia (Escopo Rápido)</label>
                    <textarea id="descricao_ideia" name="descricao_ideia" rows="2" placeholder="Descreva as funcionalidades principais do seu software..." required></textarea>
                </div>

                <!-- MÓDULO DE CHAMADA DE VÍDEO ZOOM & ACESSIBILIDADE (STITCH) -->
                <div class="zoom-panel">
                    <div class="card-header" style="margin-bottom: 0.6rem;">
                        <strong>Briefing por Chamada de Vídeo (Zoom 1-a-1)</strong>
                    </div>

                    <div class="modo-toggle">
                        <label class="chip-radio">
                            <input type="radio" name="modalidade_video" value="sem_chamada" onclick="alternarBriefing(false)">
                            <span>Enviar ideia sem chamada</span>
                        </label>
                        <label class="chip-radio">
                            <input type="radio" name="modalidade_video" value="agora" checked onclick="alternarBriefing(true); alternarCalendario(false)">
                            <span>Chamada Imediata (Agora)</span>
                        </label>
                        <label class="chip-radio">
                            <input type="radio" name="modalidade_video" value="agendar" onclick="alternarBriefing(true); alternarCalendario(true)">
                            <span>Agendar no Calendário</span>
                        </label>
                    </div>

                    <div id="opcoes-briefing">
                    <!-- Mini-Calendário Interativo -->
                    <div id="box-calendario" class="calendario-box" style="display: none;">
                        <label style="font-size: 0.8rem; color: var(--texto-suave); display:block; margin-bottom:0.4rem;">
                            Selecione a Data (Brasília GMT-3):
                        </label>
                        <div class="chips-grid" style="margin-bottom: 0.75rem;">
                            <label class="chip-radio"><input type="radio" name="data_agendada" value="Hoje" checked><span>Hoje</span></label>
                            <label class="chip-radio"><input type="radio" name="data_agendada" value="Amanhã"><span>Amanhã</span></label>
                            <label class="chip-radio"><input type="radio" name="data_agendada" value="Seg"><span>Seg</span></label>
                            <label class="chip-radio"><input type="radio" name="data_agendada" value="Ter"><span>Ter</span></label>
                        </div>

                        <label style="font-size: 0.8rem; color: var(--texto-suave); display:block; margin-bottom:0.4rem;">
                            Horários com Arquitetos Disponíveis (30 min):
                        </label>
                        <div class="chips-grid" style="margin-bottom: 0;">
                            <label class="chip-radio"><input type="radio" name="horario_agendado" value="09:30" checked><span>09:30</span></label>
                            <label class="chip-radio"><input type="radio" name="horario_agendado" value="11:00"><span>11:00</span></label>
                            <label class="chip-radio"><input type="radio" name="horario_agendado" value="14:30"><span>14:30</span></label>
                            <label class="chip-radio"><input type="radio" name="horario_agendado" value="16:00"><span>16:00</span></label>
                        </div>
                    </div>

                    <div class="campo" style="margin-bottom: 0.5rem;">
                        <label>Idioma de Preferência para o Briefing:</label>
                        <div class="chips-grid" style="margin-bottom: 0;">
                            <label class="chip-radio"><input type="radio" name="idioma" value="Português" checked><span>Português</span></label>
                            <label class="chip-radio"><input type="radio" name="idioma" value="English"><span>English</span></label>
                            <label class="chip-radio"><input type="radio" name="idioma" value="Español"><span>Español</span></label>
                            <label class="chip-radio"><input type="radio" name="idioma" value="Libras"><span>Libras</span></label>
                        </div>
                    </div>

                    <label style="font-size: 0.8rem; color: var(--texto-suave); display:block; margin-top: 0.65rem;">
                        Recursos de Acessibilidade & Inclusão (Gratuito):
                    </label>
                    <div class="acess-list">
                        <label class="acess-item">
                            <input type="checkbox" name="libras" value="1" checked>
                            <span>Intérprete de Libras</span>
                        </label>
                        <label class="acess-item">
                            <input type="checkbox" name="legendas" value="1" checked>
                            <span>Legendas ao Vivo IA</span>
                        </label>
                    </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-assado">Enviar minha ideia</button>
            </form>

            <!-- Telemetria da Fornada -->
            <div class="telemetry-row">
                <div class="telemetry-box">
                    <strong>3.420+</strong>
                    <small style="color: var(--texto-suave);">Softwares Assados</small>
                </div>
                <div class="telemetry-box">
                    <strong>100%</strong>
                    <small style="color: var(--texto-suave);">Aprovação QA</small>
                </div>
                <div class="telemetry-box" style="border-color: var(--carmesim);">
                    <strong style="color: #ff9999;">30:00m</strong>
                    <small style="color: var(--texto-suave);">Garantia Quente</small>
                </div>
            </div>
        </div>

        <!-- COLUNA DIREITA: ESTEIRA PURBLESPRINT EM TEMPO REAL -->
        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-titulo">
                        <?= $sprintAtiva ? 'Etapa atual · ' . e($sprintAtiva['codigo']) : 'Acompanhe sua ideia' ?>
                    </h2>
                    <small style="color: var(--texto-suave);">
                        Orquestração Antigravity + Prototipação Stitch + Zoom 1-a-1
                    </small>
                </div>
                <span class="timer-badge">SLA 30:00m MAX</span>
            </div>

            <div class="pipeline">
                <?php if ($sprintAtiva): ?>
                    <?php $numeroEtapa = (int)$sprintAtiva['etapa_atual']; $dadosEtapa = dadosEtapaAtual($sprintAtiva); $clientePodeValidar = !empty($_SESSION['usuario']) && $_SESSION['usuario']['perfil'] === 'cliente' && (int)$_SESSION['usuario']['id'] === (int)$sprintAtiva['usuario_id']; ?>
                    <?php if ($numeroEtapa >= 6): ?>
                        <article class="etapa-card concluida"><span class="etapa-num">✓</span><h3>Projeto concluído</h3><p><?=e($sprintAtiva['codigo'])?> foi aprovado e publicado.</p><?php if(!empty($sprintAtiva['preview_url'])): ?><a class="btn btn-zoom" href="<?=e($sprintAtiva['preview_url'])?>" target="_blank" rel="noopener">Abrir projeto</a><?php endif;?></article>
                    <?php else: ?>
                        <div class="progresso-etapas"><div><strong>Etapa <?=$numeroEtapa?> de 5</strong><span><?=e($sprintAtiva['status_aprovacao'])?></span></div><div class="barra-progresso"><span style="width: <?=($numeroEtapa * 20)?>%"></span></div></div>
                        <article class="etapa-card etapa-atual"><span class="etapa-num"><?=sprintf('%02d',$numeroEtapa)?></span><h3><?=e($dadosEtapa['titulo'])?></h3><p><?=e($dadosEtapa['descricao'])?></p>
                        <?php if($numeroEtapa===1): ?><p><strong>Cliente:</strong> <?=e($sprintAtiva['cliente_nome'])?> · <strong>Ideia:</strong> <?=e($sprintAtiva['descricao_ideia'])?></p><?php if(!empty($sprintAtiva['zoom_link'])): ?><a class="btn btn-zoom" href="<?=e($sprintAtiva['zoom_link'])?>" target="_blank" rel="noopener">Abrir reunião Zoom</a><?php endif;?><?php endif;?>
                        <?php if($numeroEtapa===2): ?><p>Stitch: <?=e($sprintAtiva['stitch_status'])?></p><?php if(!empty($sprintAtiva['stitch_preview_path'])): ?><a class="btn btn-zoom" href="<?=e($sprintAtiva['stitch_preview_path'])?>" target="_blank" rel="noopener">Ver protótipo</a><?php endif;?><?php endif;?>
                        <?php if($numeroEtapa===3): ?><p>Antigravity: <?=e($sprintAtiva['antigravity_status'])?></p><?php if(!empty($sprintAtiva['preview_url'])): ?><a class="btn btn-zoom" href="<?=e($sprintAtiva['preview_url'])?>" target="_blank" rel="noopener">Abrir software</a><?php endif;?><?php endif;?>
                        <?php if($numeroEtapa===4): ?><p>Confirme que o protótipo e o software correspondem à sua ideia antes da aprovação final.</p><?php endif;?>
                        <?php if($numeroEtapa===5 && !empty($sprintAtiva['preview_url'])): ?><a class="btn btn-zoom" href="<?=e($sprintAtiva['preview_url'])?>" target="_blank" rel="noopener">Revisar versão final</a><?php endif;?>
                        <?php if($clientePodeValidar): ?><form method="post" action="validar_etapa.php" class="validar-etapa-form"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>"><input type="hidden" name="codigo" value="<?=e($sprintAtiva['codigo'])?>"><input type="hidden" name="etapa" value="<?=$numeroEtapa?>"><button class="btn btn-aprovar" type="submit"><?=$numeroEtapa===5?'Aprovar e concluir projeto':'Validar esta etapa e seguir'?></button></form><?php elseif(empty($_SESSION['usuario'])): ?><p class="aviso-validacao"><a href="login.php">Entre na sua conta</a> para validar esta etapa.</p><?php endif;?>
                        </article>
                    <?php endif;?>
                <?php else: ?>
                    <div class="etapa-card"><h3>Envie sua ideia para começar</h3><p>Depois do envio, você verá uma etapa por vez e poderá validar o avanço.</p></div>
                <?php endif;?>
            </div>
        </section>

    </div>
</main>

<script>
function alternarBriefing(mostrar) {
    document.getElementById('opcoes-briefing').style.display = mostrar ? 'block' : 'none';
}

function alternarCalendario(mostrar) {
    document.getElementById('box-calendario').style.display = mostrar ? 'block' : 'none';
}

// Simulação de contagem regressiva ativa na esteira (24:13)
let segundosRestantes = 24 * 60 + 13;
setInterval(() => {
    if (segundosRestantes <= 0) return;
    segundosRestantes--;
    const min = String(Math.floor(segundosRestantes / 60)).padStart(2, '0');
    const seg = String(segundosRestantes % 60).padStart(2, '0');
    document.getElementById('cronometro').textContent = `Fornalha Ativa • ${min}:${seg}`;
}, 1000);
</script>

</body>
</html>
