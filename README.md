# simple-router

A simple lightweight routing class for PHP with syntax inspired by Express.js

**Why?** I really like the way Express handles routing. But sometimes I can't use Node for a production app. After looking at several routers made for PHP, none of them seemed to have the simple routing capabilities that one would find in Express.


**Is this a full emulation of Express?** No. This is a really really paired down subset of features. This does not do exotic regex matching, does not support the wildcard `.all` method, and it only contains a select few methods for responses - just enough to get the job done. If you need exotic route matching or faster performance, look to another well tested routing solution/framework.


### Setup
.htaccess for Apache
```
Options +FollowSymLinks
RewriteEngine On
RewriteRule ^(.*)$ index.php [NC,L]
```

Include the class in your main controller
```php
<?php

require('SimpleRouter.class.php');
$router = new SimpleRouter($basePath, $allowedMethods);
```
Optional arguments:
* $basepath (string) - set the basepath of the app if located in a subdirectory 
* $allowedMethods (array) - a list of allowed request methods, app will close with an error if request is made that's not on the list


### Routing
Route matching is functionally close to Express's by passing an anonymous callback when a route is matched. Examples:

```php
//respond with "hello world" when a GET request is made to the homepage
$router->get('/', function($req, $res){
	$res->send('hello world');
});


//respond with JSON
$router->get('/data.json', function($req, $res){
	$res->json(['foo' => 'bar', 'baz' => 'boo']);
});


//respond to a POST request where :id is a parameter
$router->post('/staff/:id', function($req, $res){
	$res->send('Got POST request for ' . $req['params']['id']);
});


//Match the route and allow optional query strings to be passed in
$router->delete('/person/firstname/:first/lastname/:last?dept=accounting', function($req, $res){
	$res->send('Deleting ' . $req['params']['last'] . ', ' . $req['params']['first'] . 'from ' . $req['query']['dept']; 
});
```


### $req
Similar to Express's $request object. Is a keyed array containing data about the request that was made.

* originalUrl - the original URL that was passed in
* path - the actual path, minus any query strings
* query - keyed array of query strings if they were attached to the route `?KEY=VALUE`
* params - keyed array of parameters if matched route segment contained a `:`
* headers - keyed array of all headers passed in
* ip - left most IP address in x-Forwarded-For header
* body - keyed array of request body


### $res
Similar to Express's $response, has chainable methods used to send data to the client.

* send($str) - Send a plain string
* json($arr) - Send a keyed array as a json object, automatically sets `Content-Type: application/json` header
* status($num) - Send an http status code
* set($key, $val) - Set a http header
* end() - Terminate the script immediately. Useful to halt execution of a completed route after sending a response, script no longer has to match any more routes

While these methods are all chainable, you will want them to follow the flow of `$res->status()->set()->send/json()->end()` for logical reasons.



