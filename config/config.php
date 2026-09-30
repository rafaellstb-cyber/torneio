<?php
/**
 * Configuração de ambiente. Copie/ajuste este arquivo em cada instalação.
 * Não guarde segredos de produção em controle de versão público.
 */
return [
    'db' => [
        // 'sqlite' (padrão, zero configuração) ou 'mysql'
        'driver' => 'sqlite',

        // Usado quando driver = sqlite
        'sqlite_path' => __DIR__ . '/../database/torneio.sqlite',

        // Usado quando driver = mysql
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'torneio_volei',
        'user' => 'root',
        'pass' => '',
    ],

    // Nome do cookie de sessão do admin
    'session_name' => 'torneio_admin_sess',

    // Timezone usada pelo sistema (datas, log de alterações etc.)
    'timezone' => 'America/Sao_Paulo',
];
