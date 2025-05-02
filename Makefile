install:
	composer install
validate:
	composer validate
up-ul:
	composer dump-autoload
git-graph:
	git log --pretty=format:"%h %s" --graph
lint:
	composer exec --verbose phpcs -- --standard=PSR12 src tests
gendiff:
	./bin/gendiff
diff:
	bin/gendiff tests/fixtures/file1.json tests/fixtures/file2.json
diff2:
	bin/gendiff --format plain tests/fixtures/file1.json tests/fixtures/file2.json
diff3:
	bin/gendiff --format json tests/fixtures/file1.json tests/fixtures/file2.json
test:
	composer exec --verbose phpunit tests
test-coverage:
	composer exec --verbose phpunit tests -- --coverage-clover build/logs/clover.xml

	
