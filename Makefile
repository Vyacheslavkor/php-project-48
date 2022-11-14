validate:
	composer validate

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src bin
	composer exec --verbose phpstan -- --level=8 --xdebug analyse src bin

install:
	composer install
