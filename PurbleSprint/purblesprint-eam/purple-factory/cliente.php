<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';
$pdo = conectarBanco();
if (empty($_SESSION['usuario'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$q = $pdo->prepare('SELECT * FROM sprints WHERE usuario_id = :id ORDER BY criado_em DESC');
$q->execute([':id' => $_SESSION['usuario']['id']]);
$projetos = $q->fetchAll();
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Meus projetos — PurpleSprint</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="header"><div class="logo-area"><a class="logo-home" href="index.php" aria-label="Voltar para a tela inicial"><span class="logo-icon">PurpleSprint</span></a><span>Olá, <?=e($_SESSION['usuario']['nome'])?></span></div><nav><a class="tag-tech" href="index.php">Nova ideia</a> <a class="tag-tech" href="logout.php">Sair</a></nav></header>
<main class="container"><section class="hero-topo"><h1>Meus projetos</h1><p>Acompanhe uma etapa por vez e valide para avançar.</p></section>
<?php if (($_GET['validacao'] ?? '') === 'ok'): ?><div class="card aviso-sucesso" role="status">Etapa validada. O projeto avançou para a próxima etapa.</div><?php endif; ?>
<?php if (!$projetos): ?><section class="card"><p>Seus projetos aparecerão aqui depois que você enviar uma ideia.</p><a class="btn btn-assado" href="index.php">Enviar uma ideia</a></section>
<?php else: foreach ($projetos as $p): $numeroEtapa = (int)$p['etapa_atual']; ?>
<section class="card projeto-cliente"><div class="projeto-cabecalho"><div><span class="codigo-projeto"><?=e($p['codigo'])?></span><h2><?=e($p['receita_tipo'])?></h2></div><span class="tag-tech"><?=e($p['status_aprovacao'])?></span></div>
<p class="descricao-projeto"><?=e($p['descricao_ideia'])?></p>
<?php if ($numeroEtapa >= 6): ?>
    <article class="etapa-card concluida"><span class="etapa-num">✓</span><h3>Projeto concluído</h3><p>Você validou todas as etapas.</p><?php if(!empty($p['preview_url'])): ?><a class="btn btn-zoom" href="<?=e($p['preview_url'])?>" target="_blank" rel="noopener">Abrir projeto</a><?php endif;?></article>
<?php else: $dadosEtapa = dadosEtapaAtual($p); ?>
    <div class="progresso-etapas"><div><strong>Etapa <?= $numeroEtapa ?> de 5</strong><span><?=e($p['stitch_status'])?> · <?=e($p['antigravity_status'])?></span></div><div class="barra-progresso"><span style="width: <?= (int)($numeroEtapa * 20) ?>%"></span></div></div>
    <article class="etapa-card etapa-atual"><span class="etapa-num"><?=sprintf('%02d',$numeroEtapa)?></span><h3><?=e($dadosEtapa['titulo'])?></h3><p><?=e($dadosEtapa['descricao'])?></p>
    <?php if ($numeroEtapa === 1 && !empty($p['zoom_link'])): ?><a class="btn btn-zoom" href="<?=e($p['zoom_link'])?>" target="_blank" rel="noopener">Abrir reunião Zoom</a><?php endif;?>
    <?php if ($numeroEtapa === 2 && !empty($p['stitch_preview_path'])): ?><a class="btn btn-zoom" href="<?=e($p['stitch_preview_path'])?>" target="_blank" rel="noopener">Ver protótipo</a><?php endif;?>
    <?php if (in_array($numeroEtapa,[3,4,5],true) && !empty($p['preview_url'])): ?><a class="btn btn-zoom" href="<?=e($p['preview_url'])?>" target="_blank" rel="noopener">Abrir software</a><?php endif;?>
    <form method="post" action="validar_etapa.php" class="validar-etapa-form"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>"><input type="hidden" name="codigo" value="<?=e($p['codigo'])?>"><input type="hidden" name="etapa" value="<?=$numeroEtapa?>"><button class="btn btn-aprovar" type="submit"><?=$numeroEtapa === 5 ? 'Aprovar e concluir projeto' : 'Validar esta etapa e seguir'?></button></form>
    </article>
<?php endif; ?>
<div class="links-projeto"><?php if(!empty($p['preview_url'])): ?><a href="<?=e($p['preview_url'])?>" target="_blank" rel="noopener">Preview do projeto</a><?php endif;?><?php if(!empty($p['stitch_preview_path'])): ?> · <a href="<?=e($p['stitch_preview_path'])?>" target="_blank" rel="noopener">Tela do protótipo</a><?php endif;?><?php if($p['modalidade_video']==='agendar'): ?> · Reunião <?=e($p['data_agendada'])?> às <?=e($p['horario_agendado'])?><?php endif;?></div>
</section>
<?php endforeach; endif; ?></main></body></html>
