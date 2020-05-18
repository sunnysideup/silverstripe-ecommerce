#!/bin/bash
git add . -A
git commit . -m "PATCH: linting"
git push 
#dir=code php-sslint-ecs
dir=code php-sslint-stan
