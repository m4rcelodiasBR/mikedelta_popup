# MikeDelta Popup

## Visão Geral

O MikeDelta Popup é um módulo para Drupal 10 que permite a criação de um pop-up customizável para ser exibido na página inicial do site Drupal. Ele suporta conteúdo de texto formatado ou uma galeria de até 10 imagens rotativas, onde cada imagem pode ter um link de destino individual.

## Funcionalidades

* Ativação por período (data de início e fim).
* Exibição exclusiva na página inicial.
* Galeria de imagens com rotação a cada nova sessão do usuário.
* Links individuais para cada imagem da galeria.
* Design moderno e responsivo respeitando as melhores boas práticas de UI/UX.

## Instalação e Configuração

1.  Coloque a pasta `mikedelta_popup` dentro do diretório `/modules/custom` da sua instalação Drupal.
2.  Navegue até a página "Estender" (`/admin/modules`) e instale o módulo "MikeDelta Popup".
3.  Configure o módulo em `Configurações > Criação de Conteúdo > MikeDelta Popup` (ou `/admin/config/content/md-popup`).

## Histórico de Versões

### **Versão 1.1.2 - 15/07/2025**
>[!NOTE]
>Testado com Drupal 10.4.x

**Melhorias:**
* adicionado botões de atalho para Gerenciador de Arquivos e Ajuda do Módulo.
* adicionado o campo para definir uma imagem de popup a partir de um link externo.
* atualizado página de ajuda do módulo.
* atualizado diretório interno do servidor para organização dos arquivos de imagens.

### **Versão 1.1.1 - 14/07/2025**
>[!NOTE]
>Testado com Drupal 10.4.x

**Melhorias:**
* Adicionado botão para limpar o slot da imagem em questão. Limpra a imagem e o link.
* Adicionado aviso de confoguração salva e alerta para limpar os caches (com link direto) para as configurações entrarem em vigor.


### **Versão 1.1.0 - 14/07/2025**
>[!NOTE]
>Testado com Drupal 10.4.x

**Melhorias:**
* Ajustado o tamanho máximo do pop-up de imagem para ocupar menos espaço na tela (reduzido para 70% da altura/largura da janela).
* Melhorado o design do pop-up de imagem para que a "borda de vidro" se ajuste perfeitamente ao redor da imagem, independentemente de suas dimensões.

**Correções de Bugs:**
* Corrigido um bug crítico de cache que impedia a rotação de imagens de funcionar corretamente na página inicial. O pop-up agora respeita a rotação da sessão mesmo com o cache da página ativo.
* Corrigida uma regressão que causava um erro fatal (`InvalidArgumentException`) quando uma imagem da galeria não possuía um link associado.
* Garantido que o pop-up seja exibido estritamente na página inicial, não aparecendo mais em páginas internas ou administrativas.

### **Versão 1.0.0 - 14/07/2025**
>[!NOTE]
>Testado com Drupal 10.4.x

* Lançamento inicial do módulo.

---

## Autoria e Créditos

* **Autor Principal:** Marcelo Dias da Silva
* **Data de Criação:** Julho/2025
* **Processo de Desenvolvimento:** Este módulo foi desenvolvido por Marcelo Dias da Silva com o auxílio da inteligência artificial Gemini do Google como uma ferramenta de programação, interpretação, análise e geração de código e depuração. Todo o processo foi iterativo, baseado nas solicitações e testes do autor.

## Licença

Este projeto é licenciado sob a GNU General Public License, versão 3 or superior. Veja o arquivo `LICENSE.txt` para mais detalhes.