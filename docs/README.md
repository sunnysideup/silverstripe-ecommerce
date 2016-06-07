# Updating API?

http://phpdox.de/getting-started.html

### Bash script to run from the root dir of your site:

```sh

    # go to root of install
    cd /rootdir_of_website/

    # clean up svn / git
    svn up ./ecommerce/docs/
    svn cleanup ./ecommerce/docs
    svn up ./ecommerce/docs/
    svn cleanup ./ecommerce/docs
    svn delete ./ecommerce/docs/api --force
    svn delete ./ecommerce/docs/en/phpdox/xml --force
    svn ci ./ecommerce/docs/ --message "removing old docs"

    # install php dox
    wget http://phpdox.de/releases/phpdox.phar
    chmod +x phpdox.phar
    rm /usr/local/bin/phpdox/phpdox.phar
    mv phpdox.phar /usr/local/bin/phpdox
    phpdox --version

    #run php dox
    cd ./ecommerce/docs/en/phpdox/
    phpdox

    #cleanup
    cd /rootdir_of_website/
    rm phpdox.phar

    #add to svn / git
    cd ./ecommerce
    svn mkdir ./docs/api
    mv ./docs/en/phpdox/xml ./ecommerce/docs/api
    svn add ./docs/api --force
    svn add ./docs/en/phpdox/ --force
    svn ci ./docs/ --message "updating documentation"

```

