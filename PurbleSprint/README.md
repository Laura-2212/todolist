# PurpleSprint EAM — Fábrica de Software & Esteira Inteligente de Chamados Internos

<div align="center">

![Status](https://img.shields.io/badge/Status-MVP%20Funcional-903765?style=for-the-badge)
![Evento](https://img.shields.io/badge/INTEGRA%20SENAI-2026-18506A?style=for-the-badge)
![Metodologia](https://img.shields.io/badge/MSEP-F%C3%A1brica%20de%20Software-462648?style=for-the-badge)
![LGPD](https://img.shields.io/badge/LGPD-100%25%20Conformidade-810A0A?style=for-the-badge)
![Acessibilidade](https://img.shields.io/badge/Inclus%C3%A3o-Libras%20%26%20Legendas%20IA-EAE8E8?style=for-the-badge)

**Do registro expresso à resolução auditada em esteira contínua: agilidade, rastreabilidade e inclusão na gestão industrial.**

</div>

---

## Sumário
1. [Visão Geral e Contexto Industrial](#1-visão-geral-e-contexto-industrial)
2. [A Essência PurpleSprint (Analogia *Purble Place*)](#2-a-essência-purplesprint-analogia-purble-place)
3. [Integração Oficial via Zoom & Acessibilidade](#3-integração-oficial-via-zoom--acessibilidade)
4. [Arquitetura do Fluxo Operacional (Mermaid)](#4-arquitetura-do-fluxo-operacional-mermaid)
5. [Detalhamento das 5 Estações da Esteira](#5-detalhamento-das-5-estações-da-esteira)
6. [Regras de Negócio e Restrições Mandatórias (SENAI)](#6-regras-de-negócio-e-restrições-mandatórias-senai)
7. [Requisitos Funcionais (RF)](#7-requisitos-funcionais-rf)
8. [Requisitos Não Funcionais (RNF)](#8-requisitos-não-funcionais-rnf)
9. [Modelagem de Dados Relacional (SQLite / LocalStorage)](#9-modelagem-de-dados-relacional-sqlite--localstorage)
10. [Design System & Identidade Visual (*Cyber Arcade*)](#10-design-system--identidade-visual-cyber-arcade)
11. [Stack Tecnológica e Governança Antigravity](#11-stack-tecnológica-e-governança-antigravity)
12. [Estrutura de Diretórios do Projeto](#12-estrutura-de-diretórios-do-projeto)
13. [Registro de Uso Responsável de IA (Art. 68 & 73)](#13-registro-de-uso-responsável-de-ia-art-68--73)

---

## 1. Visão Geral e Contexto Industrial

O **PurpleSprint EAM** é um Sistema de Gestão de Manutenção e Chamados Internos (*Enterprise Asset Management*) desenvolvido sob o modelo de **Fábrica de Softwares** para a **Situação de Aprendizagem (SA 01) do INTEGRA SENAI 2026** (Categoria *INTEGRA Soluções Industriais*).

### O Problema Real
Em ambientes industriais e operacionais modernos, a comunicação informal de ocorrências (via grupos de WhatsApp ou recados verbais) compromete a segurança do trabalho e a eficiência produtiva, gerando:
* **Perda de histórico** e chamados esquecidos sem responsável;
* **Duplicidade de atendimentos** no mesmo setor;
* **Ausência de métricas gerenciais** como Tempo Médio de Atendimento (**TMA**) e Tempo Médio de Resolução (**TMR**);
* **Exposição indevida de dados pessoais** em telas compartilhadas (violação à **LGPD**);
* **Demora crítica** na triagem e resposta a situações de risco operacional.

### A Solução
Uma plataforma web 100% responsiva (compatível com Desktop, Tablets e Monitores de Parede em **Modo Telão/Kiosk**) que centraliza, tria e acompanha cada chamado em uma **esteira visual interativa**, combinando automação de roteamento com **presença humana assistida via Zoom** e meta de triagem ágil de até **30 minutos**.

---

## 2. A Essência PurpleSprint (Analogia *Purble Place*)

O projeto preserva integralmente sua identidade criativa inspirada na linha de montagem de bolos do jogo ***Purble Place***, aplicando-a à engenharia de manutenção e suporte operacional:

| Elemento *Purble Place* | Tradução no *PurpleSprint EAM* | Papel na Operação |
| :--- | :--- | :--- |
| **Entrada de Ingredientes** | **Abertura Expressa ou Triagem Zoom** | Coleta estruturada da ocorrência (setor, categoria, prioridade, descrição/vídeo) gerando um protocolo único (`#EAM-XXX`). |
| **Estações de Montagem** | **Pipeline de 5 Estações Operacionais** | O chamado avança visualmente pelas estações de triagem de risco, despacho setorial, execução técnica e auditoria. |
| **Controle de Qualidade** | **Validação & Log Imutável** | Conferência final (com opção de vídeo-vistoria Zoom), exigência de justificativa de encerramento e cálculo de SLA. |
| **Presença Humana Constante** | **Atendimento 1-a-1 Inclusivo** | O sistema nunca isola o usuário: operadores humanos dão suporte via salas Zoom com Libras e legendas ao vivo. |

---

## 3. Integração Oficial via Zoom & Acessibilidade

Em estrita conformidade com a proibição de uso do WhatsApp, o **Zoom** atua como o canal oficial de comunicação síncrona do sistema em **dois momentos estratégicos**:

1. **Na Estação 01 (Triagem Assistida 1-a-1):**
   * Ideal para colaboradores em áreas operacionais ruidosas, usando EPIs (luvas) ou que necessitam de suporte inclusivo.
   * Permite **Conexão Imediata (1-clique)** ou **Agendamento em Calendário** (sincronizado com Google Agenda/Outlook, em blocos de 30 minutos).
   * **Inclusão Nativa:** Seleção de idioma (*Português*, *English*, *Español*) e ativação gratuita de **Intérprete de Libras** simultâneo e **Legendas ao Vivo (Closed Captions IA)**.
   * **Rastreabilidade:** Ao iniciar a chamada, o sistema gera e vincula automaticamente a URL da sala ao protocolo (`ex: #EAM-774`) e grava o evento no log de auditoria.

2. **Na Estação 05 (Vídeo-Vistoria de Encerramento):**
   * Permite que o técnico ou gestor valide visualmente a resolução de um problema crítico junto ao solicitante antes de aprovar a conclusão definitiva do protocolo.

---

## 4. Arquitetura do Fluxo Operacional (Mermaid)

```mermaid
flowchart TD
    A[Solicitante: Acessa Portal Web] --> B{Modalidade de Abertura}
    B -- Formulário Rápido --> C[Registro Expresso & Protocolo #EAM]
    B -- Triagem Assistida --> D[Sala Zoom Imediata ou Agendada c/ Libras]
    D --> C
    C --> E{Duplicidade no Setor?}
    E -- Sim --> F[Alerta de Chamado Existente / Vincular]
    E -- Não --> G{É Situação de Risco?}
    G -- Sim --> H[Alerta Vermelho Piscante #810A0A / Prioridade Máxima]
    G -- Não --> I[Roteamento Setorial Automático]
    H --> J[Painel Kiosk: Guarita / Oficina / Cozinha]
    I --> J
    J --> K[Técnico Assume Chamado & Registra Log de Auditoria]
    K --> L[Estação 05: Validação & Vídeo-Vistoria Zoom Opcional]
    L --> M{Problema Resolvido?}
    M -- Sim --> N[Chamado Concluído & Métricas TMA/TMR Atualizadas]
    M -- Não --> O[Reabertura com Justificativa Obrigatória]
    O --> J
