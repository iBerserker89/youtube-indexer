<!doctype html>
<!--
  Indexador de Canais do YouTube — página estática (HTML) que renderiza a UI do app.

  Função desta página:
  - Exibir formulário (tema/idioma/país) e listar resultados retornados pelo back-end PHP.
  - O front-end (scripts/main.js) faz fetch para /channels.php?topic=&lang=&country= e
    renderiza cards no container #results.

  Observações importantes:
  - A chave da YouTube API fica SEMPRE no servidor (env/config.php). Nada de expor no JS.
  - Se você usar router.php (servidor embutido do PHP ou Render), pode optar por /channels (sem .php)
    e ajustar o fetch no JS. Para Apache/cPanel, o .htaccess em public/ também pode mapear /channels.

  Estrutura principal:
  - <header> com título e dica.
  - <div class="container"> envolvendo um "card" com o <form> e áreas de estado (erro/loader/resultados).
  - <footer> simples.
  - <script src="/scripts/main.js"> para a lógica (eventos, fetch e renderização).

  Acessibilidade/UX:
  - Meta viewport para responsividade.
  - Inputs com placeholder e required no "tema".
  - Estados de erro/loader controlados via JS (#error, #loader).
-->
<html lang="pt-br">
<head>
  <!-- Metadados essenciais -->
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Título da aba/navegador -->
  <title>YouTube Indexer — tema + idioma + país</title>

  <!-- CSS principal do site (ajuste o caminho se o app estiver em subpasta) -->
  <link rel="stylesheet" href="/styles/styles.css">
  <script type="module" src="/scripts/main.js"></script>
</head>
<body>
  <!-- Cabeçalho com título e instruções rápidas -->
  <header>
    <h1>Indexador de Canais do YouTube</h1>
    <p class="hint">
      Digite um <b>tema</b> e, opcionalmente, um <b>idioma (ISO 639-1)</b> e um <b>país (ISO 3166-1)</b>.
      Ex.: tema: <i>segurança da informação</i>, idioma: <i>pt</i>, país: <i>BR</i>.
    </p>
  </header>

  <!-- Container central com largura máxima e padding -->
  <div class="container">
    <div class="card">
      <!-- Formulário de busca:
           - #topic (obrigatório)
           - #lang  (opcional, ex.: pt/en)
           - #country (opcional, ex.: BR/US)
           O submit é interceptado pelo JS (prevenindo reload) e dispara runSearch(). -->
      <form id="search-form">
        <div class="row">
          <!-- Campo do tema (termo de busca principal) -->
          <input type="text" id="topic" placeholder="Tema (obrigatório)" required />

          <!-- Campo do idioma, código ISO 639-1 (ex.: pt, en) -->
          <input type="text" id="lang" placeholder="Idioma (ex.: pt, en)" />

          <!-- Campo do país, código ISO 3166-1 alpha-2 (ex.: BR, US) -->
          <input type="text" id="country" placeholder="País (ex.: BR, US)" />

          <!-- Botão de submit; o JS controla estados de loading/disabled conforme necessário -->
          <button type="submit" id="btn">Buscar</button>
        </div>

        <!-- Dica sobre a rota usada no front-end
             Obs.: Se estiver usando router.php ou .htaccess, você pode preferir /channels (sem .php)
             e ajustar no scripts/main.js -->
        <div class="hint">
          A rota usada pelo front-end é
          <code>/channels.php?topic=&amp;lang=&amp;country=</code>
          (servida pelo PHP).
        </div>
      </form>

      <!-- Área de erro (exibida pelo JS quando houver falha de rede/HTTP/API) -->
      <div id="error" class="err" style="display:none"></div>

      <!-- Indicador de carregamento (aparece durante o fetch) -->
      <div id="loader" class="loader" style="display:none">Buscando canais...</div>

      <!-- Container onde os cards de canais são inseridos (via renderChannels no JS) -->
      <div id="results" class="grid"></div>

      <!-- Container para paginação (botão "Carregar mais" é inserido aqui pelo JS) -->
      <div id="more" style="text-align:center; margin-top:12px;"></div>
    </div>
  </div>

  <!-- Rodapé simples -->
  <footer>
    <div class="container">MVP em PHP + JS puro.</div>
  </footer>

  <!-- Script principal do front-end (eventos do form, fetch, renderização)
       Dica: você pode adicionar "defer" para evitar bloquear parsing do HTML:
       <script src="/scripts/main.js" defer></script> -->
</body>
</html>
