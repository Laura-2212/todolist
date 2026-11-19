<?php
declare(strict_types=1);

/** Chama os adaptadores locais de Stitch e Antigravity sem expor chaves no navegador. */
function executarGeracaoIntegrada(string $codigo, string $ideia, string $receita): array {
    $payload = json_encode(['codigo' => $codigo, 'ideia' => $ideia, 'receita' => $receita], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $resultados = [];
    foreach (['stitch' => 8765, 'antigravity' => 8766] as $servico => $porta) {
        $curl = curl_init("http://127.0.0.1:{$porta}/gerar");
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 300,
        ]);
        $corpo = curl_exec($curl);
        $erroCurl = curl_error($curl);
        $statusHttp = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $dados = is_string($corpo) ? json_decode($corpo, true) : null;
        if ($statusHttp >= 200 && $statusHttp < 300 && is_array($dados) && !empty($dados['ok'])) {
            $resultados[$servico] = $dados;
        } else {
            $mensagemErro = is_array($dados) && !empty($dados['erro']) ? $dados['erro'] : ($erroCurl ?: "Serviço indisponível (HTTP {$statusHttp}).");
            $resultados[$servico] = ['ok' => false, 'erro' => mb_substr((string)$mensagemErro, 0, 500)];
        }
    }
    return $resultados;
}
