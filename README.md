Beta Eggstar API
============
Dernière màj le 21/02/2014 par A.T.

Readme non à jour, préférez le guide d'utilisation Eggstar si disponible.
<ul>
  <li><a href="#preambule">Préambule</a></li>
  <li><a href="#restful-urls">RESTful URLs</a>
    <ul>
      <li><a href="#generalites">Généralités</a></li>
      <li><a href="#get">GET</a></li>
      <li><a href="#put">PUT - Insert</a></li>
      <li><a href="#post">POST - Update</a></li>
      <li><a href="#delete">DELETE</a></li>
    </ul>
  </li>
  <li><a href="#error-handling">Gestion des erreurs</a></li>
  <li><a href="#testing-tools">Outils de test</a>
    <ul>
      <li><a href="#phpstorm">PhpStorm</a></li>
      <li><a href="#postman">Postman REST Client pour Chrome</a></li>
    </ul>
  </li>
</ul>

<h2><a class="anchor" href="#preambule" name="preambule"></a>Préambule</h2>
- La création de cette API a grandement été aidée par l'article suivant:
<a href="http://coreymaynard.com/blog/creating-a-restful-api-with-php/">Creating a RESTful API with PHP</a><br />
- J'ai par ailleurs repris certains standards fournis par la Maison Blanche:
<a href="https://github.com/WhiteHouse/api-standards">WhiteHouse API Standards</a><br />
- L'ensemble des échanges se fait dans cette v1 de l'API uniquement en JSON. <br />
- Cette v1 de l'API n'incluera que ce dont nous sommes sûrs que le client mobile Sunbeam a besoin de requêter pour son bon fonctionnement.

<h2><a class="anchor" href="#restful-urls" name="restful-urls"></a>RESTful URLs</h2>
<h3><a class="anchor" href="#generalites" name="generalites"></a>Généralités</h3>
Les endpoint sont des noms au pluriel, tel que users. <br />
Pour certaines requêtes, une clé d'API sera demandée. Cette clé est un GUID créée à l'inscription de l'utilisateur.
<h3><a class="anchor" href="#get" name="get"></a>GET</h3>
L'URL d'une requête GET ressemble à ceci: http://localhost/eggstar/v1/users <br />
Ici, s'il y a dans le endpoint users un retour de GET de prévu, on devrait récupérer l'intégralité des utilisateurs. <br />
Exemple de requête qui retournerait quelque chose puisque implémenté (au 21/02/2014): <br />
GET http://localhost/eggstar/v1/users?email=toto@toto.com&password=zfije5rçf_heofuhf <br />
Il s'agit d'une requête type de demande d'authentification d'utilisateur. Le mot de passe doit être envoyé chiffré (sha1 d'un md5 du mot de passe). La présente requête renvoie une erreur ou les informations de l'utilisateur s'il a été trouvé.
<h3><a class="anchor" href="#put" name="put"></a>PUT</h3>
Pour les requêtes de type PUT, il est demandé d'envoyer les données dans le "request body". <br />
Pour inscrire un nouvel utilisateur, notre requête serait:
PUT http://localhost/eggstar/v1/users avec en request body ceci: <br /> {"name":"nom","firstName":"prénom","email":"adresse e-mail","password":"mot de passe"}
<h3><a class="anchor" href="#post" name="post"></a>POST</h3>
<h3><a class="anchor" href="#delete" name="delete"></a>DELETE</h3>
<h2><a class="anchor" href="#error-handling" name="error-handling"></a>Gestion des erreurs</h2>
La gestion des erreurs est intégrée à l'API. Une erreur est renvoyée en JSON sous la forme {'error':'message d'erreur user-friendly'}. Ce message d'erreur est plutôt destiné au développeur qui utilise l'API dans son application. De fait certaines informations (ID par exemple) peuvent se trouver dans ces messages pour une meilleure "traçabilité".
<h2><a class="anchor" href="#testing-tools" name="testing-tools"></a>Outils de test</h2>
<h3><a class="anchor" href="#phpstorm" name="phpstorm"></a>PhpStorm</h3>
PhpStorm dispose d'un client REST intégré qui se trouve dans <strong>Tools > Test RESTful Web Service</strong>. <br />
Dans le cas du PUT, nous parlions plus tôt de mettre les données dans le "request body". Dans PhpStorm, sous request body, cochez "Text:" et tapez votre JSON ici.
<h3><a class="anchor" href="#postman" name="postman"></a>Postman REST Client pour Chrome</h3>
Lien de téléchargement: cliquez <a href="https://chrome.google.com/webstore/detail/postman-rest-client/fdmmgilgnpjigdojojpjoooidkmcomcm/related?hl=en">ICI</a>.
Dans le cas du PUT, pour mettre des données dans le request body, il faut choisir "raw" et "JSON" dans le choix du type (sorte de menu déroulant).
