# Guia de Configuração e Teste: Login Social com Google

Este documento apresenta o passo a passo para configurar as credenciais no Google Cloud Console, preencher as variáveis do projeto e rodar o aplicativo para testar o Login com o Google nativamente através do Capacitor.

---

## Passo 1: Configurar o Google Cloud Console (Obter as Chaves)

1. Acesse o [Google Cloud Console](https://console.cloud.google.com/) e crie um novo projeto ou selecione o projeto do seu aplicativo.
2. No menu lateral esquerdo, vá em **APIs e Serviços > Tela de permissão OAuth**.
   - Configure a tela (pode ser configurada como **Externa** e mantida em modo de **Teste** para testes iniciais).
   - Preencha os campos obrigatórios (nome do app, e-mails de suporte).
3. Vá em **Clientes > ID do cliente OAuth 2.0 > Criar Cliente**.

### 1.1 Criar o Cliente WEB (Usada no Backend e no App)
1. Tipo de aplicativo: Selecione **Aplicativo da Web**.
2. Nome: Por exemplo, "Cliente Web NomeDoAPP".
3. Você receberá um **Client ID** no formato `123456789-xyz.apps.googleusercontent.com`.
4. Copie e guarde este ID. Ele será injetado nas variáveis de ambiente.

### 1.2 Criar o Cliente ANDROID para Desenvolvimento (Debug)
1. Crie outro cliente, selecionando o tipo **Android**.
2. Nome do pacote: No Capacitor, isso é o `appId` definido no `capacitor.config.json` (ou `.ts`).
3. **Assinatura SHA-1:** Para testar localmente em seu computador, você precisará da assinatura do seu ambiente de debug.
   - Abra seu terminal e rode o seguinte comando:
     ```bash
     keytool -keystore ~/.android/debug.keystore -list -v -alias androiddebugkey -storepass android -keypass android
     ```
   - Vai sair um bloco de texto grande. Procure a linha que começa com `SHA1:`
   - Copia só o valor depois de SHA1:  (com os dois-pontos) e cola no campo "Impressão digital para certificação SHA-1" da tela do Google Cloud.

### 1.3 Criar o Cliente ANDROID para Produção (Google Play Store)
Quando você compilar o aplicativo final (Release / AAB) e enviar para a Google Play Store, o Google Play assinará o aplicativo novamente com uma chave própria (Google Play App Signing). O login falhará se você não registrar essa chave de produção!

1. Acesse o [Google Play Console](https://play.google.com/console).
2. Selecione o seu aplicativo.
3. No menu lateral esquerdo, vá em **Testar e lançar** > **Integridade do app** > **Proteção da Google Play Store**.
4. Procure pela opção ****Gerencie a assinatura de apps  do Google Play****.
5. Copie a **Impressão digital do certificado SHA-1** fornecida ali.
6. Volte ao **Google Cloud Console** (onde criou os IDs anteriores).
7. Crie um **novo Cliente OAuth** do tipo **Android**.
8. **Nome do pacote:** (o mesmo do passo 1.2).
9. **Assinatura SHA-1:** Cole a chave SHA-1 que você copiou da Google Play Console.
10. Salve. *(Nota: Não é necessário mudar as variáveis `.env` no seu projeto. O seu `VITE_GOOGLE_WEB_CLIENT_ID` continuará sendo o mesmo do Passo 1.1! Essa configuração apenas diz ao Google que ele pode confiar no app baixado da loja).*

---

## Passo 2: Atualizar as Variáveis de Ambiente (.env)

O Client ID da WEB criado no Passo 1.1 deve ser inserido em ambos os sistemas (Backend e Frontend Mobile). O ID do Android não precisa ir para os `.env`, ele serve apenas para o Google autenticar seu App por trás dos panos.

### 2.1 Backend API (Slim PHP)
No arquivo `.env` da raiz da sua API (`api/.env`), adicione:
```env
GOOGLE_WEB_CLIENT_ID=SEU_CLIENT_ID_DA_WEB_AQUI.apps.googleusercontent.com
```
*Dica: Não esqueça de reiniciar o container da API (ex: `docker compose restart api`) para aplicar a nova variável.*

### 2.2 Frontend Mobile (Vue 3 / Capacitor)
No arquivo `.env` (arquivo padrão base) da pasta raiz do seu projeto mobile (`mobile/.env`), certifique-se de que a variável exista e possua o mesmo Client ID Web:
```env
VITE_GOOGLE_WEB_CLIENT_ID=SEU_CLIENT_ID_DA_WEB_AQUI.apps.googleusercontent.com
```
*(Usamos o `.env` global para garantir que o Vite injete esses valores quando realizar o `build` de produção via Capacitor).*

---

## Passo 3: Compilar e Testar

Como o plugin de login social (`@capgo/capacitor-social-login`) requer a interação com bibliotecas nativas do Google no Android, o teste real deve ser feito no emulador ou dispositivo físico e não no navegador web (`npm run dev`).

1. Abra o projeto no emulador Android ou em seu dispositivo conectado.
2. Vá para a tela de **Login** ou **Cadastro**.
3. Toque no botão **"Entrar com Google"**.
4. O Popup nativo de consentimento do Google aparecerá listando as contas disponíveis no celular.
5. Ao selecionar a conta, a janela será fechada automaticamente.
6. A partir desse momento, a integração acionará nosso backend, o token JWT será salvo e o usuário redirecionado para a Home do App.

### Troubleshooting (Resolução de Problemas Comuns)

- **Popup pisca e fecha rapidamente (ou dá erro):** 
  - Verifique se a chave **SHA-1** colocada no console do Google é realmente a mesma usada pelo emulador/celular no momento do teste. (Muitas vezes, a chave de debug muda de máquina para máquina).
  - Verifique se o **Nome do Pacote (Package Name)** está exato no Console do Google.
- **Variáveis de Ambiente `undefined` no Console:**
  - Lembre-se que o Vite só constrói variáveis começando com `VITE_`.
  - Certifique-se de não estar usando apenas arquivos como `.env.development` ao fazer o `npm run build` do Capacitor, pois o Vite lerá as chaves de Produção.
- **Erro de Autenticação na API Backend:**
  - Verifique os logs (`docker compose logs -f api`). Pode ser que o Backend esteja comparando o Token contra um `GOOGLE_WEB_CLIENT_ID` desatualizado ou diferente. Ambos (Front e Back) devem estar conversando com a mesma chave WEB.
