<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>YouTube Indexer — tema + idioma + país</title>
  <link rel="stylesheet" href="/styles/styles.css">
  <script type="module" src="/scripts/main.js"></script>
</head>

<body>

  <header>
    <h1>Indexador de Canais do YouTube</h1>
    <p class="hint">
      Digite um <b>tema</b> e, opcionalmente, um <b>idioma (ISO 639-1)</b> e um <b>país (ISO 3166-1)</b>.
      Ex.: tema: <i>segurança da informação</i>, idioma: <i>pt</i>, país: <i>BR</i>.
    </p>
  </header>

  <div class="container">
    <div class="card">
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

        <div class="hint">
          A rota usada pelo front-end é
          <code>/channels.php?topic=&amp;lang=&amp;country=</code>
          (servida pelo PHP).
        </div>
      </form>

      <div id="error" class="err" style="display:none"></div>

      <div id="loader" class="loader" style="display:none">Buscando canais...</div>

      <div id="results" class="grid"></div>

      <div id="more" style="text-align:center; margin-top:12px;"></div>
    </div>
  </div>

  <footer>
    <div class="container">MVP em PHP + JS puro.</div>
  </footer>

</body>
</html>
