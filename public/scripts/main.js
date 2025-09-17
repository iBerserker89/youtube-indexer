const form = document.getElementById('search-form');
const btn = document.getElementById('btn');
const topicEl = document.getElementById('topic');
const langEl = document.getElementById('lang');
const countryEl = document.getElementById('country');
const resultsEl = document.getElementById('results');
const errorEl = document.getElementById('error');
const loaderEl = document.getElementById('loader');
const moreEl = document.getElementById('more');

let nextPageToken = null;
let lastQuery = null;

/**
 * Formata números para exibição amigável ao usuário.
 * - Se o valor for null/undefined, retorna um traço (—).
 * - Usa Intl.NumberFormat quando disponível; fallback para String(n).
 *
 * @param {number|null|undefined} n Valor numérico a formatar.
 * @returns {string} Número formatado (ex.: "12.345") ou "—".
 */
function fmtNumber(n) {
  if (n === null || n === undefined) return '—';
  try { return new Intl.NumberFormat().format(n); } catch { return String(n); }
}

/**
 * Converte uma URL de tópico (geralmente Wikipédia) em um rótulo legível.
 * - Extrai o último segmento do path e substitui "_" por espaço.
 * - Em caso de erro de parsing, retorna a URL original.
 *
 * @param {string} url URL do tópico (ex.: "https://en.wikipedia.org/wiki/Technology").
 * @returns {string} Rótulo legível (ex.: "Technology").
 */
function topicLabel(url) {
  // topicCategories are Wikipedia URLs; extract last segment
  try {
    const u = new URL(url);
    const segs = u.pathname.split('/').filter(Boolean);
    return decodeURIComponent(segs[segs.length - 1]).replace(/_/g,' ');
  } catch { return url; }
}

/**
 * Renderiza a grade de canais no container de resultados.
 * - Aceita um array de canais no formato retornado pela API (já enriquecidos).
 * - Quando vazio/não-array, mostra uma dica de "Nenhum canal encontrado".
 * - Faz append do HTML gerado (não limpa resultados existentes).
 *
 * @param {Array<Object>} channels Lista de canais a exibir.
 * @returns {void}
 */
function renderChannels(channels) {
  if (!Array.isArray(channels) || channels.length === 0) {
    resultsEl.innerHTML = '<p class="hint">Nenhum canal encontrado.</p>';
    return;
  }
  const html = channels.map(ch => {
    const thumb = ch.thumbnail || '';
    const title = ch.title || 'Sem título';
    const url = ch.channelUrl || '#';
    const subs = ch.statistics?.subscriberCount ?? null;
    const vids = ch.statistics?.videoCount ?? null;
    const country = ch.country || '—';
    const topics = Array.isArray(ch.topicCategories) ? ch.topicCategories : [];

    return `
      <div class="ch-card">
        <img class="thumb" src="${thumb}" alt="thumb" />
        <div>
          <h3 class="title"><a href="${url}" target="_blank" rel="noopener">${title}</a></h3>
          <div class="meta">Inscritos: <b>${fmtNumber(subs)}</b> · Vídeos: <b>${fmtNumber(vids)}</b> · País: <b>${country}</b></div>
          ${topics.length ? `<div class="topics">${topics.slice(0,4).map(t => `<span class="chip">${topicLabel(t)}</span>`).join('')}</div>` : ''}
        </div>
      </div>
    `;
  }).join('');
  resultsEl.insertAdjacentHTML('beforeend', html);
}

/**
 * Executa a busca de canais chamando o endpoint PHP e atualiza a UI.
 * - Monta a query string a partir do formulário (topic/lang/country e pageToken).
 * - Exibe loader, trata erros de rede/HTTP, e renderiza os resultados.
 * - Controla paginação via nextPageToken e botão "Carregar mais".
 *
 * @param {boolean} [loadMore=false] Se true, mantém resultados e carrega a próxima página.
 * @returns {Promise<void>}
 * @throws {Error} Propaga erro quando a resposta HTTP não for OK.
 */
async function runSearch(loadMore=false) {
  errorEl.style.display = 'none';
  loaderEl.style.display = 'block';
  moreEl.innerHTML = '';

  const params = new URLSearchParams();
  params.set('topic', topicEl.value.trim());
  if (langEl.value.trim()) params.set('lang', langEl.value.trim());
  if (countryEl.value.trim()) params.set('country', countryEl.value.trim());
  if (loadMore && nextPageToken) params.set('pageToken', nextPageToken);

  const qs = params.toString();
  lastQuery = qs;

  try {
    const res = await fetch(`/channels?${qs}`);
    if (!res.ok) {
      const text = await res.text();
      throw new Error(`Erro HTTP ${res.status}: ${text}`);
    }
    const data = await res.json();
    if (loadMore === false) resultsEl.innerHTML = '';
    renderChannels(data.channels || []);
    nextPageToken = data.nextPageToken || null;
    if (nextPageToken) {
      moreEl.innerHTML = `<button id="btnMore">Carregar mais</button>`;
      document.getElementById('btnMore').onclick = () => runSearch(true);
    } else {
      moreEl.innerHTML = '';
    }
  } catch (err) {
    errorEl.textContent = err.message || 'Erro ao buscar canais.';
    errorEl.style.display = 'block';
  } finally {
    loaderEl.style.display = 'none';
  }
}

/**
 * Handler do submit do formulário de busca.
 * - Previne o submit padrão (page reload).
 * - Exige `topic` preenchido; inicia a busca na primeira página.
 */
form.addEventListener('submit', ev => {
  ev.preventDefault();
  if (!topicEl.value.trim()) return;
  runSearch(false);
});
