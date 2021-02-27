build:
	docker build -t facebook-photos-downloader .

enter:
	docker run -v "$(shell pwd)":/app/ --rm -it facebook-photos-downloader /bin/bash

run:
	docker run -v "$(shell pwd)":/app/ --rm -it facebook-photos-downloader php run.php
