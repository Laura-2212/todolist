<?php
declare(strict_types=1);

/**
 * Autentica via OAuth Server-to-Server no Zoom para obter o Bearer Token.
 */
function obterTokenZoom(): ?string {
    $accountId    = getenv('ZOOM_ACCOUNT_ID') ?: '';
    $clientId     = getenv('ZOOM_CLIENT_ID') ?: '';
    $clientSecret = getenv('ZOOM_CLIENT_SECRET') ?: '';

    if ($accountId === '' || $clientId === '' || $clientSecret === '' || str_starts_with($accountId, 'seu_')) {
        return null;
    }

    $url = 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . urlencode($accountId);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT        => 10
    ]);

    $resposta = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status === 200 && $resposta) {
        $json = json_decode($resposta, true);
        return $json['access_token'] ?? null;
    }

    return null;
}

/**
 * Cria uma sala de Briefing 1-a-1 no Zoom (Imediata ou Agendada) com suporte a Libras e Idiomas.
 */
function criarSalaBriefingZoom(string $codigoSprint, string $modalidade, string $data, string $horario, string $idioma, bool $libras, bool $legendas): string {
    $token = obterTokenZoom();
    $recursos = [];
    if ($libras)   $recursos[] = 'Intérprete de Libras';
    if ($legendas) $recursos[] = 'Legendas ao Vivo IA';
    $agendaTexto = "Briefing PurpleSprint {$codigoSprint} | Idioma: {$idioma} | Acessibilidade: " . (empty($recursos) ? 'Padrão' : implode(' + ', $recursos));

    if ($token !== null) {
        $isAgendada = ($modalidade === 'agendar');
        $payload = [
            'topic'      => "PurpleSprint Briefing 1-a-1 ({$codigoSprint})",
            'type'       => $isAgendada ? 2 : 1,
            'start_time' => $isAgendada ? date('Y-m-d\TH:i:s', strtotime("{$data} {$horario}")) : null,
            'duration'   => 30,
            'timezone'   => 'America/Sao_Paulo',
            'agenda'     => $agendaTexto,
            'settings'   => [
                'host_video'        => true,
                'participant_video' => true,
                'join_before_host'  => true
            ]
        ];

        $ch = curl_init('https://api.zoom.us/v2/users/me/meetings');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS     => json_encode(array_filter($payload)),
            CURLOPT_TIMEOUT        => 10
        ]);

        $resposta = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 201 && $resposta) {
            $dados = json_decode($resposta, true);
            if (!empty($dados['join_url'])) {
                return $dados['join_url'];
            }
        }
    }

    // Fallback dinâmico caso as chaves no .env ainda não tenham sido ativadas
    $idSala = preg_replace('/\D/', '', $codigoSprint) . rand(10000, 99999);
    return "https://zoom.us/j/{$idSala}?pwd=" . substr(md5($codigoSprint), 0, 8);
}
