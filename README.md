A development license has now been issued to your account.  Your new license key is included below for reference:

    Dev-d0efb0ef4bd1568ed243

If at any point you should need to change any of the details on your development license such as the directory, domain or IP address, please don't hesitate to contact our friendly License Support Team with your updated information.

www.whmcs.com/members/clientarea.php

ad@plisio.net/ndn8ey385nZs


Installation instructions can be found here: http://docs.whmcs.com/Installing_WHMCS

Run:
```
docker run -d --name="whmcs" \
  -v ~/Projects/php/plisio/docs/plugins/whmcs/modules/gateways/plisio.php:/var/www/public/modules/gateways/plisio.php \
  -v ~/Projects/php/plisio/docs/plugins/whmcs/modules/gateways/Plisio:/var/www/public/modules/gateways/Plisio \
  -v ~/Projects/php/plisio/docs/plugins/whmcs/modules/gateways/callback/plisio.php:/var/www/public/modules/gateways/callback/plisio.php \
  -v ~/Projects/php/plisio/docs/plugins/whmcs/configuration.php:/var/www/public/configuration.php  \
  --add-host="plisio.loc:${HOST_IP}" \
  -p 1080:80 -p 1443:443 -t gembit/whmcs

docker exec -it whmcs
mkdir attachments downloads templates_c && chown -R www-data:www-data attachments downloads templates_c modules/gateways
```