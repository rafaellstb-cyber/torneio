<?php

final class Views
{
    public static function faseLabel(string $fase, ?string $rodada): string
    {
        return match ($fase) {
            'classificatoria' => 'Classificatória · Rodada ' . $rodada,
            'quartas' => 'Quartas de Final' . ($rodada === 'leva1' ? ' · 1ª leva' : ' · 2ª leva'),
            'semifinal' => 'Semifinal',
            'terceiro_lugar' => 'Disputa de 3º Lugar',
            'final' => 'Grande Final',
            default => ucfirst($fase),
        };
    }

    public static function secaoLabel(string $fase, ?string $rodada): string
    {
        return match ($fase) {
            'classificatoria' => 'CLASSIFICATÓRIA · RODADA ' . $rodada,
            'quartas' => 'QUARTAS DE FINAL',
            'semifinal' => 'SEMIFINAIS',
            'terceiro_lugar' => 'DISPUTA DE 3º LUGAR',
            'final' => 'GRANDE FINAL',
            default => mb_strtoupper($fase),
        };
    }

    public static function statusBadgeHtml(string $status): string
    {
        return $status === 'encerrado'
            ? '<span class="badge badge-encerrado"><i class="bi bi-check-circle me-1"></i>Encerrado</span>'
            : '<span class="badge badge-pendente">Pendente</span>';
    }

    public static function quadraBadgeHtml(int $quadra): string
    {
        return '<span class="badge badge-quadra" data-quadra="' . $quadra . '">Quadra ' . $quadra . '</span>';
    }

    public static function nomeTime(?array $time): string
    {
        return $time ? $time['nome'] : 'A definir';
    }

    private static function atletas(?array $time): string
    {
        return $time && !empty($time['atletas']) ? trim($time['atletas']) : '';
    }

    /** Renderiza o card padrão de um jogo (usado em Início, Jogos e listas). */
    public static function cardJogo(array $jogo, array $timesMap): string
    {
        $t1 = isset($jogo['time1_id']) && $jogo['time1_id'] ? ($timesMap[(int) $jogo['time1_id']] ?? null) : null;
        $t2 = isset($jogo['time2_id']) && $jogo['time2_id'] ? ($timesMap[(int) $jogo['time2_id']] ?? null) : null;
        $encerrado = $jogo['status'] === 'encerrado';
        $venceT1 = $encerrado && (int) $jogo['pontos1'] > (int) $jogo['pontos2'];
        $venceT2 = $encerrado && (int) $jogo['pontos2'] > (int) $jogo['pontos1'];

        $classeT1 = $venceT1 ? 'vencedor' : ($venceT2 ? 'perdedor' : '');
        $classeT2 = $venceT2 ? 'vencedor' : ($venceT1 ? 'perdedor' : '');

        $origem1 = $jogo['origem1'] ?? null;
        $origem2 = $jogo['origem2'] ?? null;

        ob_start();
        ?>
        <div class="card jogo-card shadow-sm">
            <div class="jogo-cabecalho">
                <span><strong>Jogo <?= (int) $jogo['numero'] ?></strong> · <?= e(self::faseLabel($jogo['fase'], $jogo['rodada'])) ?></span>
                <span class="d-flex align-items-center gap-2">
                    <?= self::quadraBadgeHtml((int) $jogo['quadra']) ?>
                    <span><?= e($jogo['horario_previsto'] ?? '--:--') ?></span>
                    <?= self::statusBadgeHtml($jogo['status']) ?>
                </span>
            </div>
            <div class="jogo-bloco-time <?= $classeT1 ?>">
                <div>
                    <span class="nome-time"><?= e(self::nomeTime($t1)) ?></span>
                    <?php if (self::atletas($t1) !== ''): ?><span class="atletas-time"><?= e(self::atletas($t1)) ?></span><?php endif; ?>
                    <?php if ($origem1): ?><span class="origem-time"><?= e($origem1) ?></span><?php endif; ?>
                </div>
                <span class="placar-grande"><?= $encerrado ? (int) $jogo['pontos1'] : '–' ?><?= $venceT1 ? ' <i class="bi bi-trophy-fill icone-trofeu"></i>' : '' ?></span>
            </div>
            <div class="jogo-bloco-time <?= $classeT2 ?>">
                <div>
                    <span class="nome-time"><?= e(self::nomeTime($t2)) ?></span>
                    <?php if (self::atletas($t2) !== ''): ?><span class="atletas-time"><?= e(self::atletas($t2)) ?></span><?php endif; ?>
                    <?php if ($origem2): ?><span class="origem-time"><?= e($origem2) ?></span><?php endif; ?>
                </div>
                <span class="placar-grande"><?= $encerrado ? (int) $jogo['pontos2'] : '–' ?><?= $venceT2 ? ' <i class="bi bi-trophy-fill icone-trofeu"></i>' : '' ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function indicadorAtualizacao(): string
    {
        ob_start();
        ?>
        <div class="indicador-atualizacao justify-content-end mb-2">
            <span id="bolinha-viva" class="bolinha-viva"></span>
            <span id="hora-atualizacao">Atualizado às --:--:--</span>
            <span>·</span>
            <span id="contagem-regressiva">próxima em 15s</span>
            <button type="button" id="btn-pausar-auto" class="btn btn-sm btn-outline-secondary ms-2">Pausar atualização</button>
        </div>
        <?php
        return ob_get_clean();
    }

    /** Pódio (vice/campeão/3º) usado em Início e Mata-mata. $podio vem de MataMata::podio(). */
    public static function podioHtml(array $podio): string
    {
        ob_start();
        ?>
        <div class="podio-flex mb-4">
            <div class="podio-card podio-campeao">
                <div class="podio-medalha">🏆</div>
                <p class="podio-titulo mb-0">Campeão</p>
                <p class="h3 mb-0"><?= e($podio['campeao']['nome']) ?></p>
                <?php if (self::atletas($podio['campeao']) !== ''): ?><span class="atletas-podio"><?= e(self::atletas($podio['campeao'])) ?></span><?php endif; ?>
            </div>
            <div class="podio-card podio-vice">
                <div class="podio-medalha">🥈</div>
                <p class="podio-titulo mb-0">Vice-campeão</p>
                <p class="h5 mb-0"><?= e($podio['vice']['nome']) ?></p>
                <?php if (self::atletas($podio['vice']) !== ''): ?><span class="atletas-podio"><?= e(self::atletas($podio['vice'])) ?></span><?php endif; ?>
            </div>
            <div class="podio-card podio-terceiro">
                <div class="podio-medalha">🥉</div>
                <p class="podio-titulo mb-0">3º lugar</p>
                <p class="h5 mb-0"><?= e($podio['terceiro']['nome']) ?></p>
                <?php if (self::atletas($podio['terceiro']) !== ''): ?><span class="atletas-podio"><?= e(self::atletas($podio['terceiro'])) ?></span><?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /** Card compacto usado no chaveamento do mata-mata. */
    public static function cardChave(array $jogo, array $timesMap): string
    {
        $t1 = $jogo['time1_id'] ? ($timesMap[(int) $jogo['time1_id']] ?? null) : null;
        $t2 = $jogo['time2_id'] ? ($timesMap[(int) $jogo['time2_id']] ?? null) : null;
        $encerrado = $jogo['status'] === 'encerrado';
        $venceT1 = $encerrado && (int) $jogo['pontos1'] > (int) $jogo['pontos2'];
        $venceT2 = $encerrado && (int) $jogo['pontos2'] > (int) $jogo['pontos1'];

        ob_start();
        ?>
        <div class="chave-jogo">
            <div class="cabecalho-chave">Jogo <?= (int) $jogo['numero'] ?> · <?= e($jogo['rotulo_slot'] ?? '') ?> · Quadra <?= (int) $jogo['quadra'] ?></div>
            <div class="linha-time <?= $venceT1 ? 'vencedor' : ($venceT2 ? 'perdedor' : '') ?>">
                <span>
                    <strong><?= e(self::nomeTime($t1)) ?></strong><?php if (self::atletas($t1) !== ''): ?><span class="atletas-chave"><?= e(self::atletas($t1)) ?></span><?php endif; ?>
                    <?php if (!$t1 && !empty($jogo['origem1'])): ?><br><small class="text-muted"><?= e($jogo['origem1']) ?></small><?php endif; ?>
                </span>
                <span><?= $encerrado ? (int) $jogo['pontos1'] : '' ?></span>
            </div>
            <div class="linha-time <?= $venceT2 ? 'vencedor' : ($venceT1 ? 'perdedor' : '') ?>">
                <span>
                    <strong><?= e(self::nomeTime($t2)) ?></strong><?php if (self::atletas($t2) !== ''): ?><span class="atletas-chave"><?= e(self::atletas($t2)) ?></span><?php endif; ?>
                    <?php if (!$t2 && !empty($jogo['origem2'])): ?><br><small class="text-muted"><?= e($jogo['origem2']) ?></small><?php endif; ?>
                </span>
                <span><?= $encerrado ? (int) $jogo['pontos2'] : '' ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
