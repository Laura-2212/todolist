import { createServer } from 'node:http';
import { readFileSync, promises as fs } from 'node:fs';
import { dirname, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';
import { Stitch } from '@google/stitch-sdk';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const outputRoot = resolve(root, 'generated-projects');
function carregarEnv() {
  const texto = readFileSync(resolve(root, '.env'), 'utf8').replace(/^\uFEFF/, '');
  for (const linha of texto.split(/\r?\n/)) {
    const m = linha.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)\s*$/);
    if (m && !m[1].startsWith('#') && process.env[m[1]] === undefined) process.env[m[1]] = m[2].replace(/^['"]|['"]$/g, '');
  }
}
carregarEnv();
const apiKey = process.env.STITCH_API_KEY || '';
const { stitch } = await import('@google/stitch-sdk');

async function lerCorpo(req) {
  let texto = '';
  for await (const parte of req) {
    texto += parte;
    if (texto.length > 100_000) throw new Error('Solicitação excede o limite permitido.');
  }
  return JSON.parse(texto || '{}');
}

const servidor = createServer(async (req, res) => {
  res.setHeader('Content-Type', 'application/json; charset=utf-8');
  if (req.method === 'GET' && req.url === '/health') {
    res.writeHead(200); res.end(JSON.stringify({ ok: true, chave: apiKey ? 'configurada' : 'ausente' })); return;
  }
  if (req.method !== 'POST' || req.url !== '/gerar') { res.writeHead(404); res.end('{"erro":"Rota não encontrada"}'); return; }
  try {
    if (!apiKey) throw new Error('STITCH_API_KEY não está configurada no .env.');
    const { codigo, ideia, receita } = await lerCorpo(req);
    if (typeof ideia !== 'string' || ideia.trim().length < 5) throw new Error('Descreva a ideia com pelo menos cinco caracteres.');
    const idSeguro = String(codigo || '').replace(/[^A-Za-z0-9_-]/g, '').slice(0, 50);
    if (!idSeguro) throw new Error('Código de projeto inválido.');
    let projeto;
    const idExistente = process.env.STITCH_PROJECT_ID || '';
    const projetos = idExistente && !idExistente.startsWith('seu_') ? await stitch.projects() : [];
    if (projetos.some((item) => item.projectId === idExistente || item.id === idExistente)) {
      projeto = stitch.project(idExistente);
    } else {
      projeto = await stitch.createProject({ title: `PurpleSprint ${idSeguro}` });
    }
    const geracao = await projeto.generate(
      `Crie uma tela web desktop responsiva e profissional para este projeto: ${String(receita || 'Aplicação web')}\n\nRequisitos do cliente:\n${ideia.trim()}\n\nUse português do Brasil. Entregue interface completa e acessível.`,
      { deviceType: 'DESKTOP' },
    );
    const tela = geracao.first;
    if (!tela) throw new Error('O Stitch não retornou uma tela.');
    const pasta = resolve(outputRoot, idSeguro);
    if (!pasta.startsWith(outputRoot + sep)) throw new Error('Destino do arquivo inválido.');
    await fs.mkdir(pasta, { recursive: true });
    const html = await tela.getHtml();
    const caminho = resolve(pasta, 'stitch-preview.html');
    await fs.writeFile(caminho, html, 'utf8');
    res.writeHead(200);
    res.end(JSON.stringify({ ok: true, projectId: projeto.projectId, screenId: tela.screenId, previewPath: caminho }));
  } catch (erro) {
    res.writeHead(502);
    res.end(JSON.stringify({ ok: false, erro: erro?.message || 'Falha na geração Stitch.' }));
  }
});
servidor.listen(8765, '127.0.0.1');
