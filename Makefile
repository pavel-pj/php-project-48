install:
	composer install
validate:
	composer validate
up-ul:
	composer dump-autoload
git-graph:
	git log --pretty=format:"%h %s" --graph
lint:
	composer exec --verbose phpcs -- --standard=PSR12 src
	composer exec --verbose phpcs -- --standard=PSR12 tests
gendiff:
	./bin/gendiff
test:
	composer exec --verbose phpunit tests
test-coverage:
	composer exec --verbose phpunit tests -- --coverage-clover build/logs/clover.xml
