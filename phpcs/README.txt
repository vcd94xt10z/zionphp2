# PHP Code Sniffer
curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar
curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcbf.phar

# Analise de padrões de código
php phpcs.phar --standard=./vendor/vcd94xt10z/zion2/phpcs/zion.xml --extensions=php --ignore=*/view/*,*/artifacts/* ./src/

# Analise e correção automática
php phpcbf.phar --standard=./vendor/vcd94xt10z/zion2/phpcs/zion.xml --extensions=php --ignore=*/view/*,*/artifacts/* ./src/