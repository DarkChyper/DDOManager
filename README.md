# DDOManager

DDOManager is a small tool to deal with dynamic IP at home when you need to self-hosting personnal web app.

## How to use ? 

Fork or clone this repo on your host.  
Create .env.local from .env file, edit the file with your configuration.  
In terminal : 
  * `php bin/console doctrine:database:create`
  * `php bin/console doctrine:schema:create`
  * make install
Then you have to edit your crontab to call the command periodicaly, ie every 30 minutes everyday:  
`*/30 * * * * /usr/bin/php /home/{your_user}/public_html/DDOManager/bin/console ddom:runtime` 

