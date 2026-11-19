<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';
$pdo = conectarBanco();
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'entrar';
    $email = strtolower(trim($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    if ($acao === 'cadastro') {
        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 8) {
            $erro = 'Informe nome, e-mail válido e senha com pelo menos 8 caracteres.';
        } else {
            try {
                $q = $pdo->prepare("INSERT INTO usuarios (nome,email,senha_hash,perfil) VALUES (:n,:e,:s,'cliente')");
                $q->execute([':n'=>$nome, ':e'=>$email, ':s'=>password_hash($senha,PASSWORD_DEFAULT)]);
                $_SESSION['usuario'] = ['id'=>(int)$pdo->lastInsertId(),'nome'=>$nome,'email'=>$email,'perfil'=>'cliente'];
                header('Location: index.php'); exit;
            } catch (PDOException $e) { $erro = 'Este e-mail já possui cadastro.'; }
        }
    } else {
        $q = $pdo->prepare('SELECT id,nome,email,senha_hash,perfil FROM usuarios WHERE email=:e'); $q->execute([':e'=>$email]); $u=$q->fetch();
        if ($u && password_verify($senha,$u['senha_hash'])) {
            session_regenerate_id(true); unset($u['senha_hash']); $_SESSION['usuario']=$u;
            header('Location: index.php'); exit;
        }
        $erro='E-mail ou senha incorretos. Confira os dados e tente novamente.';
    }
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acesso PurpleSprint</title><link rel="stylesheet" href="assets/style.css"><style>.auth{max-width:560px;margin:3rem auto}.auth input{width:100%;margin:.4rem 0 1rem;padding:.8rem;border-radius:8px;background:#140c20;color:#eae8e8;border:1px solid #462648}.auth h2{margin:1.2rem 0 .6rem}.auth a{color:#9bd5f0}</style></head><body><main class="container"><section class="card auth"><a href="index.php">← PurpleSprint</a><h1>Acesso à plataforma</h1><?php if($erro):?><p role="alert" style="color:#ffb3b3"><?=e($erro)?></p><?php endif;?><form method="post"><h2>Entrar</h2><label>E-mail<input type="email" name="email" required autocomplete="username"></label><label>Senha<input type="password" name="senha" required autocomplete="current-password"></label><button class="btn btn-assado">Entrar</button></form><hr style="margin:1.5rem 0;opacity:.2"><form method="post"><input type="hidden" name="acao" value="cadastro"><h2>Criar conta de cliente</h2><label>Nome<input name="nome" required autocomplete="name"></label><label>E-mail<input type="email" name="email" required autocomplete="email"></label><label>Senha (mínimo 8 caracteres)<input type="password" name="senha" minlength="8" required autocomplete="new-password"></label><button class="btn btn-zoom">Criar conta</button></form></section></main></body></html>
