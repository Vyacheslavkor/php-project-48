validate:
	composer validate

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src tests
	composer exec --verbose phpstan -- --level=8 --memory-limit=-1 --xdebug analyse src tests

install:
	composer install

test:
	composer exec --verbose phpunit tests

test-coverage:
	composer exec --verbose phpunit tests -- --coverage-clover build/logs/clover.xml

test-coverage-html:
	composer exec --verbose phpunit tests -- --coverage-html build/logs
