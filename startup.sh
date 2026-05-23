#!/bin/bash
cp /home/site/wwwroot/index.php /home/site/wwwroot/index.php
php -S 0.0.0.0:8080 -t /home/site/wwwroot
