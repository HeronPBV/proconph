# Proconph - Oficina mecânica

![GitHub repo size](https://img.shields.io/github/repo-size/HeronPBV/onfly-api?logo=github)
![Laravel](https://img.shields.io/badge/Laravel-9.52.16-c2363a?style=flat&logo=laravel)

Sistema desenvolvido em Laravel para o gerenciamento eficiente de uma oficina mecânica. 🚗
<br>O projeto é parte do teste técnico para o cargo de Desenvolvedor PHP no time da Proconph, sendo esta a sua única função: demonstrar conhecimento sólido em desenvolvimento de sistemas com Laravel e Vue.js.
<br>Este projeto segue as melhores práticas de desenvolvimento, garantindo código limpo, escalável e de fácil manutenção.

### Tecnologias utilizadas

<table>
  <tr>
    <td>PHP</td>
    <td>Laravel</td>
    <td>Vue.js</td>
    <td>MySQL</td>
  </tr>
  
  <tr>
    <td>8.1.28</td>
    <td>9.52.16</td>
    <td>3.2.32</td>
    <td>8.3.0</td>
  </tr>
</table>

### Padrões de projeto
- Arquitetura MVC (Laravel)
- PSR4
- API Rest
- Clean Code

## Instruções para a execução do projeto

### 💻 Pré-requisitos

Antes de começar, verifique se você:

- Possui instalado em sua maquina o `Composer`, `Git`, `PHP`, `npm` e `MySQL`, nas versões supracitadas.
- Leu cuidadosamente todos os passos de instalação desta documentação.

### Para instalar e executar o projeto localmente

1º - Execute os seguintes comandos no seu terminal:
~~~
git clone https://github.com/HeronPBV/proconph.git
~~~
~~~
cd proconph
~~~

<br>2º - Localize o arquivo proconph/.env.example e renomeie-o para .env, em seguida insira os seus dados de acesso ao MySQL nas linhas:
~~~
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=projeto_estagio
DB_USERNAME=root
DB_PASSWORD=
~~~
Há um arquivo BancoDados.sql dentro da pasta database com os comandos para criar o banco de dados inteiro com as respectivas colunas. Execute-o.

<br>  3° - Execute os seguintes comandos no seu terminal para instalar e testar a aplicação:
~~~
composer install
~~~
~~~
php artisan key:generate
~~~
~~~
npm install
~~~
~~~
npm run dev
~~~
Se todas as instalações ocorrerem sem problemas, você está pronto para prosseguir.

<br> 4º - Em terminais separados, execute os seguintes comandos:
~~~
php artisan serve
~~~
~~~
php artisan queue:work --tries=3
~~~

⚠️ Atenção ⚠️ 
<br>Qualquer problema com o autoload pode ser resolvido com o seguinte comando:
~~~
composer dump
~~~

E pronto! Seu projeto já está configurado e pronto para rodar.
<br> Acesse o endereço retornado ao executar o comando `php artisan serve` e bons testes!
<br> Lembrando que o usuário padrão cadastrado tem o e-mail: `admin@admin.com` e a senha `123`
