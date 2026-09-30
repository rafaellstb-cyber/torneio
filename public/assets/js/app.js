(function () {
    'use strict';

    var INTERVALO_MS = 15000;

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function horaAgora() {
        var d = new Date();
        return pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    }

    function iniciarAutoAtualizacao() {
        var container = document.querySelector('[data-autorefresh]');
        if (!container) return;

        var horaEl = document.getElementById('hora-atualizacao');
        var contagemEl = document.getElementById('contagem-regressiva');
        var bolinhaEl = document.getElementById('bolinha-viva');
        var btnPausar = document.getElementById('btn-pausar-auto');

        var pausado = false;
        var restante = INTERVALO_MS / 1000;
        var urlFragmento = buildFragUrl();

        function buildFragUrl() {
            var url = new URL(window.location.href);
            url.searchParams.set('_frag', '1');
            return url.toString();
        }

        function atualizarContagem() {
            if (contagemEl) contagemEl.textContent = 'próxima em ' + restante + 's';
        }

        function tick() {
            if (pausado) return;
            restante -= 1;
            if (restante <= 0) {
                buscarConteudo();
                restante = INTERVALO_MS / 1000;
            }
            atualizarContagem();
        }

        function buscarConteudo() {
            fetch(urlFragmento, { headers: { 'X-Requested-With': 'fetch' } })
                .then(function (resp) { return resp.ok ? resp.text() : Promise.reject(resp.status); })
                .then(function (html) {
                    container.innerHTML = html;
                    if (horaEl) horaEl.textContent = 'Atualizado às ' + horaAgora();
                })
                .catch(function () { /* mantém o conteúdo atual em caso de falha de rede */ });
        }

        if (btnPausar) {
            btnPausar.addEventListener('click', function () {
                pausado = !pausado;
                btnPausar.textContent = pausado ? 'Retomar atualização' : 'Pausar atualização';
                btnPausar.classList.toggle('btn-outline-secondary', !pausado);
                btnPausar.classList.toggle('btn-secondary', pausado);
                if (bolinhaEl) bolinhaEl.classList.toggle('pausado', pausado);
                if (contagemEl) contagemEl.textContent = pausado ? 'pausado' : ('próxima em ' + restante + 's');
            });
        }

        atualizarContagem();
        setInterval(tick, 1000);
    }

    function iniciarConfirmacaoPlacar() {
        document.querySelectorAll('form[data-confirmar-placar]').forEach(function (form) {
            form.addEventListener('submit', function (ev) {
                var nome1 = form.dataset.time1 || 'Time 1';
                var nome2 = form.dataset.time2 || 'Time 2';
                var p1 = form.querySelector('[name="pontos1"]').value;
                var p2 = form.querySelector('[name="pontos2"]').value;
                var msg = 'Confirmar resultado?\n\n' + nome1 + '  ' + p1 + '  x  ' + p2 + '  ' + nome2 +
                    '\n\nDepois de salvo, só o administrador pode corrigir.';
                if (!window.confirm(msg)) {
                    ev.preventDefault();
                }
            });
        });
    }

    function iniciarLimparFiltros() {
        document.querySelectorAll('[data-limpar-filtros]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = btn.closest('form');
                if (!form) return;
                form.querySelectorAll('input, select').forEach(function (campo) {
                    if (campo.type === 'submit' || campo.type === 'button') return;
                    campo.value = '';
                });
                form.submit();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        iniciarAutoAtualizacao();
        iniciarConfirmacaoPlacar();
        iniciarLimparFiltros();
    });
})();
