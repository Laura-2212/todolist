<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] !== 'cliente') {
    http_response_code(403);
    exit('A validação exige uma conta de cliente conectada.');
}
$tokenSessao = $_SESSION['csrf_token'] ?? '';
if ($tokenSessao === '' || !hash_equals($tokenSessao, (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Não foi possível validar esta solicitação. Atualize a página e tente novamente.');
}

$pdo = conectarBanco();
$codigo = trim((string)($_POST['codigo'] ?? ''));
$etapaEnviada = filter_input(INPUT_POST, 'etapa', FILTER_VALIDATE_INT);
$consulta = $pdo->prepare('SELECT etapa_atual FROM sprints WHERE codigo = :codigo AND usuario_id = :usuario');
$consulta->execute([':codigo' => $codigo, ':usuario' => $_SESSION['usuario']['id']]);
$sprint = $consulta->fetch();
if (!$sprint || $etapaEnviada !== (int)$sprint['etapa_atual']) {
    http_response_code(409);
    exit('A etapa mudou ou este projeto não pertence à sua conta. Atualize a página.');
}

$proximaEtapa = (int)$sprint['etapa_atual'] + 1;
if ($proximaEtapa > 5) {
    $alterar = $pdo->prepare("UPDATE sprints SET etapa_atual = 6, status_aprovacao = 'Publicado em Produção' WHERE codigo = :codigo AND usuario_id = :usuario AND etapa_atual = :etapa");
    $alterar->execute([':codigo' => $codigo, ':usuario' => $_SESSION['usuario']['id'], ':etapa' => $etapaEnviada]);
} else {
    $alterar = $pdo->prepare("UPDATE sprints SET etapa_atual = :proxima, status_aprovacao = 'Em Produção' WHERE codigo = :codigo AND usuario_id = :usuario AND etapa_atual = :etapa");
    $alterar->execute([':proxima' => $proximaEtapa, ':codigo' => $codigo, ':usuario' => $_SESSION['usuario']['id'], ':etapa' => $etapaEnviada]);
}

header('Location: cliente.php?validacao=ok');
exit;
