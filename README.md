# YouTube Channel Indexer (PHP + JS)

MVP simples: digite **tema**, **idioma** (ISO 639‑1) e **país** (ISO 3166‑1), o app lista **canais relacionados**.  
Back-end em **PHP** chama a **YouTube Data API v3**; front-end em **JS puro**.

## Rotas
- `GET /channels?topic=<str>&lang=<pt|en|...>&country=<BR|US|...>&pageToken=<token>` → JSON com canais
- `GET /` → Página com formulário e cards

---

## Configuração da API Key

### Arquivo `config.php`
Copie `config.example.php` para `config.php` e coloque sua chave:
```php
define('YT_API_KEY', 'SUA_CHAVE_AQUI');
```

---

## Rodando localmente
Requer **PHP 8+** com cURL.
```bash
export YT_API_KEY=YOUR_KEY
php -S 127.0.0.1:8080 -t public
# abra http://127.0.0.1:8080
```

---

## Como funciona (resumo)
1. `search` → `type=video` (+ `regionCode` + `relevanceLanguage`) para refletir melhor a regionalidade do conteúdo.  
2. Dedupe de `channelId`.  
3. `channels.list` (`snippet,statistics,topicDetails`) para enriquecer e ordenar por inscritos.  
4. Resposta JSON para o front-end.

---

## Limites/Quota
- `search.list` custa **100 unidades** por chamada; `channels.list` custa **1**.  

---

## Estrutura
```
.
├─ api/
│  └─ channels.php         # Proxy para YouTube API
├─ public/
│  └─ index.php            # UI (form + cards) com JS puro
├─ .htaccess               # Reescreve /channels → api/channels.php
├─ config.example.php
├─ render.yaml
└─ README.md
```

---

## Licença

MIT License

Copyright (c) 2025 Luciano Barros

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
