# YouTube Channel Indexer (PHP + JS)

![PHP](https://img.shields.io/badge/PHP-777BB4?logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?logo=javascript&logoColor=000)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)

MVP simples: digite **tema**, **idioma** (ISO 639‑1) e **país** (ISO 3166‑1), o app lista **canais relacionados**.  
Back-end em **PHP** chama a **YouTube Data API v3**; front-end em **JS puro**.

## Stack & Serviços
[![Docker](https://img.shields.io/badge/Docker-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![Render](https://img.shields.io/badge/Render-46E3B7?logo=render&logoColor=000)](https://render.com/)
[![YouTube Data API v3](https://img.shields.io/badge/YouTube%20Data%20API%20v3-FF0000?logo=youtube&logoColor=white)](https://developers.google.com/youtube/v3)

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
