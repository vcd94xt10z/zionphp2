![Zion Framework](https://raw.githubusercontent.com/vcd94xt10z/zionphp/master/frontend/zion/img/zion-framework.png)

Você não veio aqui para fazer uma escolha, você já fez. Você esta aqui para entender porque fez sua escolha.

A maioria dos usuários não está preparado para despertar. E muitos deles estão tão inertes, tão desesperadamente dependentes de outros frameworks, que irão lutar para protegê-los.

Eu só posso lhe mostrar a porta. Você tem que atravessá-la.

## Instalação

Para instalar ou atualizar para ultima versão, execute o comando abaixo:

```bash
composer require vcd94xt10z/zion2:dev-main
```

## Como começar

Após instalar o framework, você já pode começar a chamar as classes do sistema. Lembrando que nada que possa afetar seu projeto será executado sem que o desenvolvedor
invoke alguma funcionalidade. O Zion pode te ajudar a fazer as tarefas mais frequêntes em projetos de desenvolvimento Web. 

O framework foi pensado e desenvolvido para funcionar na versão PHP >=7 com apache.

Para mais informações, acesse a sessão de funcionalidades e a documentação para entender melhor o funcionamento com exemplos.

## Inicialização

Esse não é um passo obrigatório dependendo do que você utilizar no framework mas recomendamos que você chame esse método após carregar o autoload do composer para setar coisas como:
- Definições do ambiente: DEV, QAS e PRD
- Codificação UTF-8
- Criação de constantes
- Gerenciamento de erros
- Carregar configurações do seu projeto (config.json)
- Fuso horário
- Formatação de data, hora, moeda etc

```bash
\zion\core\System::configure();
```

## Recomendações

### Fluxo da aplicação
Encaminhe o fluxo da aplicação para o index.php, isso pode ser feito no .htaccess, você pode encontrar um exemplo em

```bash
/vendor/vcd94xt10z/zion2/app-kit/webserver/sites/localhost/public/.htaccess
```

## Documentação

Infelizmente, é impossível dizer o que é Zion, você tem de ver por si mesmo. 

Esta é sua última chance, depois não há como voltar.

- Se tomar a pílula [azul](https://www.youtube.com/watch?v=dQw4w9WgXcQ), a história acaba, e você acordará na sua cama acreditando no que quiser acreditar.
- Se tomar a pílula [vermelha](https://htmlpreview.github.io/?https://github.com/vcd94xt10z/zionphp/blob/master/docs/index.html), ficará no País das Maravilhas e eu te mostrarei até onde vai a toca do coelho.

Lembre-se, tudo que ofereço é a verdade, nada mais.  

## Funcionalidades

- Plataforma para aplicações MVC
- Persistência de dados: Trabalhe com bancos como MySQL, SQLServer entre outros que serão incluidos futuramente
- Gerenciamento de E-mails: Envie, receba e gerencie
- Gerenciamento de Erros: Exceções, erros de código, erros de banco
- Segurança: WAF, suporte a SSL e criptografia
- Gerador de Módulos: Gere CRUD para módulos totalmente funcionais com as melhores práticas, flexível e extensível
- Internacionalização: Use textos em seu sistema em qualquer idioma