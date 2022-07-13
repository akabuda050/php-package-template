install:
	@docker run --rm \
	 --interactive \
	 --user 1000:1000 \
	 --tty \
	 --volume ${PWD}:/app composer install

test:
	@docker run --rm \
	 --interactive \
	 --tty \
	 --volume ${PWD}:/app composer test

	@docker run --rm \
	 --interactive \
	 --tty \
	 --volume ${PWD}:/app \
	 composer psalm
