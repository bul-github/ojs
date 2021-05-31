-------------------------------------------------------------
Note générales
-------------------------------------------------------------

L'adresse pour télécharger la dernière version de certains installateurs est parfois fournie
à même les instructions.

-------------------------------------------------------------
PHP
-------------------------------------------------------------
Installer la dernière version 7.4 stable. La dernière version est disponible à l'adresse suivante :

    https://windows.php.net/download/

Dans :

    C:/Program Files/PHP/v7.4

Créer les dossiers suivants :

    session/
    upload/

Copier les extensions suivantes dans ext/

    php_xdebug-...-7.4-vc15-nts-x86_64.dll
    php_wincache.dll

-------------------------------------------------------------
IIS
-------------------------------------------------------------

Afin que la connexion à la BD fonctionne, il faut changer l'utilisateur qui permet de
faire rouler les programmes PHP dans IIS. Pour ce faire, on ouvre le IIS Manager.

Dans :

    BUL-XX-OJS01 --> Application Pools --> DefaultAppPool --> Advanced Settings
    Section "Process Model" --> Identity --> Cliquer sur les "..." à droite
    Custom account --> Set...

En fonction de la plate-forme, choisir le bon User name :

    DEV => BIBL\dev_ojs
    ACC => BIBL\ts_ojs
    PROD => BIBL\prod_ojs

Les mots de passe sont dans KeePass.

Créer le répertoire suivant :

    D:/inetpub/upload

OJS en aura besoin pour sauvegarder ses fichiers.

-------------------------------------------------------------
FastCGI
-------------------------------------------------------------

Toujours dans IIS Manager :

    BUL-XX-OJS01 --> Handler Mappings --> Add Module Mapping...

    Request path => *.php
    Module       => FastCgiModule
    Executable   => C:\Program Files\PHP\v7.4\php-cgi.exe
    Name         => PHP_via_FastCGI

-------------------------------------------------------------
Composer Update
-------------------------------------------------------------

L'adresse pour obtenir Composer :

    https://getcomposer.org/download/

Au moment d'une mise à jour importante, il est faut exécuter le gestionnaire
de dépendances du projet, Composer, à partir de la ligne de commande à 3 endroits
dans le dossier de déploiement, habituellement "D:/inetput/wwwroot/" :

    ojs/lib/pkp
    ojs/plugins/generic/citationStyleLanguage
    ojs/plugins/paymethod/paypal

La commande est :

    composer update

-------------------------------------------------------------
XDebug et DQGP proxy pour débogage multi-usager dans PhpStorm
-------------------------------------------------------------

Sur le serveur OJS DEV :

    bul-dv-ojs01.bibl.ulaval.ca (132.203.62.176)

L'extension XDebug a été ajoutée à cet emplacement :

    C:\Program Files\PHP\v7.4\ext\php_xdebug-...-7.4-vc15-nts-x86_64.dll

Dans :

    C:\Program Files\PHP\v7.4\php.ini

Les lignes suivantes ont été ajoutées :

    [Xdebug]
    zend_extension=C:\Program Files\PHP\v7.4\ext\php_xdebug-3.0.3-7.4-vc15-nts-x86_64.dll
    xdebug.mode=debug
	xdebug.client_host=localhost
	xdebug.client_port=9000

Afin d'activer l'extension XDebug avec sa configuration, un redémarrage de IIS est nécessaire.

Le dossier de DBGp a été créé dans :

    D:\DBGp

Et le contenu de l'archive suivante y a été copié :

    Komodo-PythonRemoteDebugging-11.0.2-90813-win32-x86.zip

La ligne de commande à utiliser pour lancer le proxy DBGp est :

    D:\DBGp\pydbgpproxy.exe -d 127.0.0.1:9000 -i 132.203.62.176:9001

Étant donné que ce processus doit être installé en service Windows, l'utilitaire NSSM :

    nssm-2.24.zip

Également disponible à l'adresse :

    http://nssm.cc/release/nssm-2.24.zip

A été installé dans :

    D:\nssm-2.24

Pour installer DBGp en service, il faut exécuter :

    D:\nssm-2.24\win64>nssm install DBGp

Dans l'onglet Application on saisit :

    Path              : D:\DBGp\pydbgpproxy.exe
    Startup directory : D:\DBGp
    Arguments         : -d 127.0.0.1:9000 -i 132.203.62.176:9001

Dans l'onglet Details :

    Display name : DBGp XDebug proxy

Dans l'onglet I/O on ajoute les chemins suivants :

    Output (stdout) : D:\DBGp\stdout.log
    Error (stderr)  : D:\DBGp\stderr.log

Dans l'onglet File rotation, cocher la case Replace existing Output and/or Error files. Le reste ne change pas. Cliquer
sur Install service.

Éventuellement, pour modifier le service ou consulter sa configuration :

    D:\nssm-2.24\win64>nssm edit DBGp

Dans le Firewall de Windows sur le serveur, ajouter une exception au traffic entrant pour le port 9001.
Dans le Firewall de Windows sur le poste client, ajouter une exception au traffic entrant pour le port 9000.

Dans PhpStorm sur le poste client, le menu Tools --> DBGp Proxy --> Register IDE :

    IDE Key : senad (ou une valeur différente, pourvu que ce ne soit pas la même d'un usager à l'autre)
    Host    : 132.203.62.176
    Port    : 9001

    DEV  -> 132.203.62.176
    ACC  -> 132.203.62.170
    PROD -> 132.203.62.218

Dans l'extension Xdebug helper, dans la section IDE Key, choisir Other dans le menu déroulant et saisir senad
(ou la même IDE Key que dans PhpStorm).

Si lors du démarrage de PhpStorm le débogage ne fonctionne pas avec le serveur OJS DEV, il faut retourner dans :

    Tools --> DBGp Proxy --> Register IDE

Pour toutes les infos du service DBGp sur le serveur OJS DEV, on peut consulter le fichier suivant :

    D:\DBGp\stderr.log

-------------------------------------------------------------
Exécuter une tâche planifiée en ligne de commande
-------------------------------------------------------------

Pour ce faire, il suffit d'exécuter une commande comme celle-ci :

    D:\inetpub\wwwroot\ojs\tools>php runScheduledTasks.php plugins/generic/duplicateDetector/scheduledTasks.xml

-------------------------------------------------------------
Groupes délégués BIBL
-------------------------------------------------------------

Avec l'autorisation de Guy Bilodeau, les groupes suivants ont été créés :

    - GD_BIBL_OJS_PERS_REVUES_CGQ --> Cahiers de géographie du Québec
    - GD_BIBL_OJS_PERS_REVUES_EI  --> Études internationales
    - GD_BIBL_OJS_PERS_REVUES_LTP --> Laval Théologique et philosophique
    - GD_BIBL_OJS_PERS_REVUES_RL  --> Études Littéraires
	- ...

Les comptes nibou230, senad et pilas9 ont été ajoutés à ces groupe sans date d'expiration.
