# Torneio de Vôlei de Praia

Site completo para gerenciar e exibir ao vivo um torneio de vôlei de praia com 9 times (T1–T9),
3 quadras simultâneas, fase classificatória com calendário fixo (18 jogos) e mata-mata com os
8 melhores colocados (8 jogos): quartas, semifinais, disputa de 3º lugar e grande final.

PHP puro (sem framework, sem build), Bootstrap 5.3 via CDN, JavaScript puro para atualização
automática. Pensado mobile-first, porque os atletas consultam o site na areia.

> **Nota sobre o total de jogos:** o briefing original menciona "44 jogos" no total, mas a
> estrutura descrita (18 da classificatória + 4 quartas + 2 semifinais + 1 disputa de 3º + 1
> final) soma **26 jogos**. O sistema usa 26, que é o número consistente com a numeração 1–26
> e com o formato de mata-mata de 8 times descrito na seção 5.4.

## Requisitos

- PHP 8.1 ou superior, com extensão PDO (`pdo_sqlite` para o padrão, ou `pdo_mysql` se optar
  por MySQL).
- Qualquer hospedagem compartilhada com PHP funciona — não há etapa de build, nem Composer,
  nem Node.js em produção.

## Instalação local (desenvolvimento)

```bash
git clone <repositorio> torneio
cd torneio
php scripts/seed.php          # cadastra os times T1–T9, carrega o calendário e cria o admin padrão
php -S localhost:8000 -t public
```

Acesse `http://localhost:8000`. O `seed.php` cria um admin padrão:

- **usuário:** `admin`
- **senha:** `torneio123`

**Troque essa senha assim que possível** em Admin → Configurações, ou crie um admin próprio com:

```bash
php scripts/criar_admin.php meu_usuario minha_senha_forte
```

## Publicando em hospedagem compartilhada

1. Envie todos os arquivos do projeto para o servidor.
2. Aponte o **document root** do domínio/subdomínio para a pasta `public/`. Se sua hospedagem
   não permitir escolher o document root (algumas hospedagens só servem a partir de
   `public_html`), copie o *conteúdo* de `public/` para `public_html/` e mantenha `config/`,
   `src/`, `database/`, `scripts/` e `tests/` **fora** dela (um nível acima). Cada uma dessas
   pastas já tem um `.htaccess` com `Require all denied` como proteção extra caso isso não seja
   possível.
3. Garanta que a pasta `database/` tenha permissão de escrita (o SQLite é criado automaticamente
   no primeiro acesso).
4. Rode uma vez, via SSH ou por um script temporário, `php scripts/seed.php` para popular o
   banco. Se não tiver acesso SSH, crie um admin acessando `scripts/criar_admin.php` uma única
   vez por linha de comando (a maioria das hospedagens compartilhadas oferece PHP CLI via SSH ou
   painel), ou adapte a seção "Configuração inicial" abaixo.
5. Acesse `/admin/` e ajuste nome do torneio, data, local e horário em **Torneio**.

### Usando MySQL em vez de SQLite

Edite `config/config.php`:

```php
'db' => [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'nome_do_banco',
    'user' => 'usuario',
    'pass' => 'senha',
],
```

As tabelas são criadas automaticamente no primeiro acesso (`database/schema_mysql.sql`).

## Configurando os parâmetros do torneio

Tudo fica em **Admin → Torneio**: nome, data, local, horário de início e duração de cada
rodada (usada para calcular os horários previstos dos jogos). Nada disso é fixado no código —
os valores "NOME DO MEU TORNEIO" / "NOME DO LOCAL" no seed são apenas placeholders iniciais.

Em **Admin → Times** você renomeia os 9 times (T1–T9) e, opcionalmente, cadastra os atletas de
cada um (texto livre).

Em **Admin → Configurações**: PIN do dia (opcional, para lançar resultados publicamente), texto
do rodapé e troca de senha do admin.

## Fluxo de uso no dia do torneio

1. Em **Admin → Jogos**, clique em **"Carregar calendário da classificatória"** (18 jogos fixos,
   3 por rodada, 6 rodadas).
2. Durante o dia, os placares podem ser lançados por qualquer pessoa em **Lançar resultado**
   (pública, com confirmação antes de salvar) ou pelo admin em **Admin → Jogos**.
3. Quando os 18 jogos da classificatória estiverem encerrados, confira **Admin → Desempate** —
   se houver empate pendente entre as posições 1ª–9ª, defina a ordem manual dos times empatados.
4. Com a classificação 1º–9º totalmente definida, clique em **"Gerar mata-mata"**. O sistema monta
   quartas, semifinais, disputa de 3º lugar e grande final automaticamente, com o vencedor (e o
   perdedor, no caso das semifinais) avançando sozinho conforme os resultados são salvos.
5. As telas públicas (Início, Ranking, Jogos, Mata-mata) se atualizam sozinhas a cada 15 segundos.

Se um resultado precisar ser corrigido depois de encerrado, isso só pode ser feito pelo admin em
**Admin → Jogos**. Se o jogo corrigido/reaberto já tiver alimentado a fase seguinte do mata-mata,
os jogos dependentes são recalculados (e reabertos) automaticamente, em cascata.

## Simulação e dados de demonstração

Para testar o site com dados de verdade sem digitar nada:

```bash
php scripts/seed.php              # times + calendário + admin padrão
php scripts/simular.php           # simula toda a classificatória e o mata-mata com placares
                                   # válidos aleatórios e mostra o pódio final
```

Para forçar cenários de empate (para conferir a tela de Desempate manual):

```bash
php scripts/simular.php --empates
```

Esse modo usa margem fixa (21×15, vitória sempre do time de menor número) na classificatória,
o que propositalmente deixa vários grupos de times empatados em vitórias/saldo/pontos pró sem
terem se enfrentado — exatamente o cenário que exige decisão manual do admin.

Para recomeçar do zero, use **Admin → Torneio → Reiniciar torneio** (apaga jogos, placares e o
log de alterações; mantém times e configurações).

## Testes automatizados

Não há dependência externa (nada de PHPUnit) — os testes são scripts PHP simples que rodam com
o PHP da própria hospedagem:

```bash
php tests/run.php
```

Isso executa, em processos separados, todos os arquivos `tests/*_test.php`:

- `calendario_test.php` — confirma que o calendário fixo tem 18 jogos, cada time joga
  exatamente 4 vezes, ninguém joga duas vezes na mesma rodada, e que as folgas calculadas batem
  com o briefing.
- `validacao_test.php` — cobre placares válidos e inválidos (diferença mínima de 2, exigência de
  diferença exata de 2 acima de 21 pontos, empate proibido, etc.).
- `desempate_test.php` — um caso para cada critério de desempate (vitórias, saldo, pontos pró,
  confronto direto, empate pendente com 2 times que não se enfrentaram, empate pendente com 3+
  times, e resolução manual pelo admin).
- `mata_mata_test.php` — geração do chaveamento a partir da classificação, avanço automático de
  vencedores/perdedores, cálculo do pódio e recálculo em cascata ao reabrir um jogo já usado por
  fases seguintes.

## Estrutura de pastas

```
config/     config.php (banco de dados, timezone) — protegido por .htaccess
database/   schema_sqlite.sql, schema_mysql.sql, torneio.sqlite (gerado, git-ignorado)
src/        classes de domínio (Database, Auth, Jogos, Ranking, MataMata, Horarios, Views...)
            e as views compartilhadas (src/views/) — tudo protegido por .htaccess
public/     document root: páginas públicas, área admin (public/admin/) e assets estáticos
scripts/    seed.php, simular.php, criar_admin.php (linha de comando)
tests/      testes automatizados (tests/run.php roda todos)
```

## Segurança

- Senhas de admin com `password_hash`/`password_verify`.
- Sessão com cookie `httponly`, `SameSite=Lax`.
- Token CSRF em todos os formulários (`Csrf::field()` / `Csrf::requireValid()`).
- Todas as consultas usam PDO com parâmetros preparados.
- Toda saída para HTML passa pela função `e()` (escape).
- Validação de placar sempre no servidor (nunca confia apenas no JavaScript).
- Concorrência: salvar um resultado usa um `UPDATE ... WHERE status = 'pendente'` atômico — se
  duas pessoas tentarem lançar o mesmo jogo ao mesmo tempo, a segunda recebe o aviso de que o
  jogo já foi encerrado.
