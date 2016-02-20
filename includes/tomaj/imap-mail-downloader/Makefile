vendor/autoload.php:
	composer install

sniff: vendor/autoload.php
	vendor/bin/phpcs --standard=PSR2 src -n

test: vendor/autoload.php
	vendor/bin/phpunit --coverage-clover build/logs/clover.xml

